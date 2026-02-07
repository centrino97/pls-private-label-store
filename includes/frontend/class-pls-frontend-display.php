<?php
/**
 * Frontend display handler for auto-injecting PLS content on WooCommerce pages.
 *
 * v3.0.0: Auto-inject PLS content on single product pages and tier badges on shop/category pages.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Frontend_Display {

    /**
     * Initialize frontend display hooks.
     * NOTE: Auto-injection disabled - use shortcodes instead (pls_single_product, pls_single_category, pls_shop_page)
     */
    public static function init() {
        // Only load on frontend
        if ( is_admin() ) {
            return;
        }

        // Register assets (for shortcodes)
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );

        // Auto-injection disabled - use shortcodes instead
        // Removed: add_action( 'wp', array( __CLASS__, 'setup_product_hooks' ) );
        // Removed: Tier badges auto-injection hooks
    }

    /**
     * Register and enqueue frontend assets.
     */
    public static function register_assets() {
        // Register main offers CSS
        wp_register_style(
            'pls-offers',
            PLS_PLS_URL . 'assets/css/offers.css',
            array(),
            PLS_PLS_VERSION
        );

        // Register frontend display CSS
        wp_register_style(
            'pls-frontend-display',
            PLS_PLS_URL . 'assets/css/frontend-display.css',
            array( 'pls-offers' ),
            PLS_PLS_VERSION
        );

        // Register main offers JS
        wp_register_script(
            'pls-offers',
            PLS_PLS_URL . 'assets/js/offers.js',
            array( 'jquery' ),
            PLS_PLS_VERSION,
            true
        );

        // v5.7.0: Build bundle data for frontend nudge system
        $bundle_nudge_data = array();
        $bundles = PLS_Repo_Bundle::all();
        if ( ! empty( $bundles ) ) {
            foreach ( $bundles as $bundle ) {
                if ( 'live' !== $bundle->status ) {
                    continue;
                }
                $rules = ! empty( $bundle->offer_rules_json ) ? json_decode( $bundle->offer_rules_json, true ) : array();
                if ( empty( $rules ) ) {
                    continue;
                }
                $bundle_nudge_data[] = array(
                    'id'              => $bundle->id,
                    'name'            => $bundle->name,
                    'sku_count'       => isset( $rules['sku_count'] ) ? (int) $rules['sku_count'] : 0,
                    'units_per_sku'   => isset( $rules['units_per_sku'] ) ? (int) $rules['units_per_sku'] : 0,
                    'price_per_unit'  => isset( $rules['price_per_unit'] ) ? floatval( $rules['price_per_unit'] ) : 0,
                    'bundle_type'     => isset( $rules['bundle_type'] ) ? $rules['bundle_type'] : '',
                );
            }
        }

        // Localize script with AJAX data + bundle info
        wp_localize_script( 'pls-offers', 'plsOffers', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'pls_offers' ),
            'addToCartNonce' => wp_create_nonce( 'pls_add_to_cart' ),
            'cartUrl'        => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
            'bundles'        => $bundle_nudge_data,
            'i18n'           => array(
                'bundleSave'         => __( 'Save more!', 'pls-private-label-store' ),
                'bundleNudge'        => __( 'Configure %d products to unlock %s bundle pricing at %s/unit', 'pls-private-label-store' ),
                'bundleQualified'    => __( 'Bundle pricing unlocked! You qualify for %s', 'pls-private-label-store' ),
                'perUnit'            => __( 'per unit', 'pls-private-label-store' ),
                'youSave'            => __( 'You save', 'pls-private-label-store' ),
                'bundleSavingsLabel' => __( 'Bundle Savings Available', 'pls-private-label-store' ),
            ),
        ) );
    }

    /**
     * Setup product page hooks based on settings.
     */
    public static function setup_product_hooks() {
        // Only on single product pages
        if ( ! is_product() ) {
            return;
        }

        // Check if auto-injection is enabled
        $settings = self::get_settings();
        if ( ! $settings['auto_inject_enabled'] ) {
            return;
        }

        // Add hook based on position setting
        $priority = 25; // After summary
        switch ( $settings['injection_position'] ) {
            case 'after_summary':
                add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'inject_pls_content' ), $priority );
                break;
            case 'after_add_to_cart':
                add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'inject_pls_content' ), 35 );
                break;
            case 'before_tabs':
                add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'inject_pls_content' ), 5 );
                break;
            case 'in_tabs':
                add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'add_pls_tab' ) );
                break;
            default:
                add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'inject_pls_content' ), $priority );
        }

        // Enqueue assets
        wp_enqueue_style( 'pls-frontend-display' );
        wp_enqueue_script( 'pls-offers' );
    }

    /**
     * Get display settings.
     *
     * @return array Settings with defaults.
     */
    public static function get_settings() {
        // Defaults reflect shortcode-only approach (auto-injection disabled in v4.5.2)
        $defaults = array(
            'auto_inject_enabled'  => false, // Disabled - use shortcodes instead
            'injection_position'   => 'after_summary',
            'show_configurator'    => true,
            'show_description'     => true,
            'show_ingredients'     => true,
            'show_bundles'         => true,
            'show_tier_badges'     => false, // Disabled - badges not auto-displayed
            'show_starting_price'  => false, // Disabled - badges not auto-displayed
        );

        $saved = get_option( 'pls_frontend_display_settings', array() );
        
        return wp_parse_args( $saved, $defaults );
    }

    /**
     * Inject PLS content on product page.
     */
    public static function inject_pls_content() {
        global $product;

        if ( ! $product instanceof WC_Product ) {
            return;
        }

        $product_id = $product->get_id();

        // Check if this is a PLS product
        global $wpdb;
        $base_product = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d LIMIT 1",
                $product_id
            ),
            OBJECT
        );

        if ( ! $base_product ) {
            return; // Not a PLS product, skip
        }

        $settings = self::get_settings();
        $profile = PLS_Repo_Product_Profile::get( $base_product->id );

        // Start output
        echo '<div class="pls-auto-inject" id="pls-product-content">';

        // Configurator section (pack tier selector)
        if ( $settings['show_configurator'] && $product->is_type( 'variable' ) ) {
            self::render_configurator( $product );
        }

        // Product info section
        if ( $profile ) {
            if ( $settings['show_description'] && ! empty( $profile->long_description ) ) {
                self::render_description( $profile );
            }

            if ( $settings['show_ingredients'] && ! empty( $profile->ingredients_list ) ) {
                self::render_ingredients( $profile );
            }
        }

        // Bundle offers section
        if ( $settings['show_bundles'] ) {
            self::render_bundles();
        }

        echo '</div>'; // .pls-auto-inject
    }

    /**
     * Get PLS content as string for shortcode use.
     * 
     * @param int    $product_id Product ID.
     * @param array  $options Display options (show_configurator, show_description, show_ingredients, show_bundles).
     * @return string HTML content.
     */
    public static function get_pls_content( $product_id, $options = array() ) {
        $defaults = array(
            'show_images'        => true,
            'show_configurator'  => true,
            'show_description'   => true,
            'show_ingredients'    => true,
            'show_bundles'        => true,
        );
        $options = wp_parse_args( $options, $defaults );

        $product = wc_get_product( $product_id );
        if ( ! $product instanceof WC_Product ) {
            return '';
        }

        // Check if this is a PLS product
        global $wpdb;
        $base_product = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d LIMIT 1",
                $product_id
            ),
            OBJECT
        );

        if ( ! $base_product ) {
            return '';
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );

        ob_start();
        echo '<div class="pls-product-page" id="pls-product-content" data-product-id="' . esc_attr( $product_id ) . '">';

        // v5.7.0: Two-column sticky layout
        if ( $options['show_images'] && $profile ) {
            echo '<div class="pls-product-layout">';

            // Left Column (Sticky) - Images + Gallery + Video
            echo '<div class="pls-product-layout__left">';
            self::render_product_images( $product, $profile );
            echo '</div>';

            // Right Column (Scrollable) - Product Details + Accordions
            echo '<div class="pls-product-layout__right">';
            self::render_product_right_column( $product, $profile, $base_product, $options );
            echo '</div>';

            echo '</div>'; // .pls-product-layout
        }

        // Configurator Modal (hidden by default, opened via CTA button)
        if ( $options['show_configurator'] && $product->is_type( 'variable' ) ) {
            self::render_configurator_modal( $product, $profile );
        }

        // Bundle offers section (full width below layout)
        if ( $options['show_bundles'] ) {
            self::render_bundles();
        }

        echo '</div>'; // .pls-product-page
        return ob_get_clean();
    }

    /**
     * Render product images (left column - sticky).
     * v5.7.0: Extracted from render_product_header, now includes video support.
     *
     * @param WC_Product $product The product.
     * @param object     $profile The product profile.
     */
    private static function render_product_images( $product, $profile ) {
        if ( ! $product || ! $profile ) {
            return;
        }

        $featured_image_id = ! empty( $profile->featured_image_id ) ? absint( $profile->featured_image_id ) : 0;
        $gallery_ids = ! empty( $profile->gallery_ids ) ? array_filter( array_map( 'absint', explode( ',', $profile->gallery_ids ) ) ) : array();

        // Get featured image URL
        $featured_url = '';
        $featured_alt = '';
        if ( $featured_image_id ) {
            $featured_image = wp_get_attachment_image_src( $featured_image_id, 'woocommerce_single' );
            $featured_url = $featured_image ? $featured_image[0] : '';
            $featured_alt = get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true );
        }

        // Get gallery images + video support
        $gallery_images = array();
        foreach ( $gallery_ids as $gallery_id ) {
            $mime_type = get_post_mime_type( $gallery_id );
            $is_video = $mime_type && strpos( $mime_type, 'video/' ) === 0;

            if ( $is_video ) {
                $video_url = wp_get_attachment_url( $gallery_id );
                if ( $video_url ) {
                    $gallery_images[] = array(
                        'id'       => $gallery_id,
                        'url'      => $video_url,
                        'full'     => $video_url,
                        'alt'      => get_post_meta( $gallery_id, '_wp_attachment_image_alt', true ),
                        'is_video' => true,
                        'mime'     => $mime_type,
                    );
                }
            } else {
                $img = wp_get_attachment_image_src( $gallery_id, 'woocommerce_thumbnail' );
                if ( $img ) {
                    $gallery_images[] = array(
                        'id'       => $gallery_id,
                        'url'      => $img[0],
                        'full'     => wp_get_attachment_image_src( $gallery_id, 'full' )[0] ?? $img[0],
                        'alt'      => get_post_meta( $gallery_id, '_wp_attachment_image_alt', true ),
                        'is_video' => false,
                    );
                }
            }
        }

        // If no featured but have gallery, use first non-video image
        if ( ! $featured_url && ! empty( $gallery_images ) ) {
            foreach ( $gallery_images as $gi ) {
                if ( empty( $gi['is_video'] ) ) {
                    $featured_url = $gi['full'];
                    $featured_alt = $gi['alt'];
                    break;
                }
            }
        }
        ?>
        <div class="pls-product-images">
            <?php if ( $featured_url ) : ?>
                <div class="pls-product-image-main">
                    <img src="<?php echo esc_url( $featured_url ); ?>"
                         alt="<?php echo esc_attr( $featured_alt ?: $product->get_name() ); ?>"
                         class="pls-product-image-main__img"
                         data-image-id="<?php echo esc_attr( $featured_image_id ); ?>" />
                </div>
            <?php else : ?>
                <div class="pls-product-image-main pls-product-image-placeholder">
                    <svg width="600" height="600" viewBox="0 0 600 600" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="600" height="600" fill="#f3f4f6"/>
                        <path d="M300 200L250 250L300 300L350 250L300 200Z" fill="#d1d5db"/>
                        <text x="300" y="350" text-anchor="middle" fill="#9ca3af" font-family="Arial" font-size="18">No Image</text>
                    </svg>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $gallery_images ) ) : ?>
                <div class="pls-product-gallery-thumbnails">
                    <?php foreach ( $gallery_images as $index => $gallery_img ) : ?>
                        <?php if ( ! empty( $gallery_img['is_video'] ) ) : ?>
                            <button type="button"
                                    class="pls-gallery-thumb pls-gallery-thumb--video"
                                    data-video-url="<?php echo esc_url( $gallery_img['full'] ); ?>"
                                    data-mime="<?php echo esc_attr( $gallery_img['mime'] ?? '' ); ?>"
                                    aria-label="<?php echo esc_attr( sprintf( __( 'Play video %d', 'pls-private-label-store' ), $index + 1 ) ); ?>">
                                <span class="pls-gallery-thumb__play-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                </span>
                            </button>
                        <?php else : ?>
                            <button type="button"
                                    class="pls-gallery-thumb <?php echo $index === 0 && ! $featured_image_id ? 'is-active' : ''; ?>"
                                    data-image-id="<?php echo esc_attr( $gallery_img['id'] ); ?>"
                                    data-image-url="<?php echo esc_url( $gallery_img['full'] ); ?>"
                                    aria-label="<?php echo esc_attr( sprintf( __( 'View image %d', 'pls-private-label-store' ), $index + 1 ) ); ?>">
                                <img src="<?php echo esc_url( $gallery_img['url'] ); ?>"
                                     alt="<?php echo esc_attr( $gallery_img['alt'] ?: $product->get_name() ); ?>" />
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the right column of the product page.
     * v5.7.0: Contains title, description, badges, price, CTA, accordions, ingredients.
     *
     * @param WC_Product $product      The WC product.
     * @param object     $profile      The product profile.
     * @param object     $base_product The PLS base product.
     * @param array      $options      Display options.
     */
    private static function render_product_right_column( $product, $profile, $base_product, $options ) {
        ?>
        <div class="pls-product-details">
            <!-- 1. Product Title -->
            <h1 class="pls-product-title"><?php echo esc_html( $product->get_name() ); ?></h1>

            <!-- 2. Short Description -->
            <?php if ( ! empty( $profile->short_description ) ) : ?>
                <div class="pls-product-short-description">
                    <?php echo wp_kses_post( wpautop( $profile->short_description ) ); ?>
                </div>
            <?php endif; ?>

            <!-- 3. Skin Type Badges -->
            <?php self::render_skin_type_badges( $profile ); ?>

            <!-- 4. Starting Price per unit -->
            <?php self::render_starting_price_block( $product ); ?>

            <!-- 5. Trust Badges Row -->
            <?php self::render_trust_badges_row(); ?>

            <!-- 6. "Get Started" CTA Button -->
            <?php if ( $product->is_type( 'variable' ) ) : ?>
                <div class="pls-product-cta">
                    <button type="button" class="pls-configure-button" id="pls-open-configurator">
                        <?php esc_html_e( 'Get Started', 'pls-private-label-store' ); ?>
                    </button>
                </div>
            <?php endif; ?>

            <!-- 7. Benefits Accordion -->
            <?php if ( ! empty( $profile->benefits_json ) ) :
                $benefits_data = json_decode( $profile->benefits_json, true );
                if ( is_array( $benefits_data ) && ! empty( $benefits_data ) ) : ?>
                <div class="pls-page-accordion is-open">
                    <button type="button" class="pls-page-accordion__header" aria-expanded="true">
                        <span class="pls-page-accordion__title"><?php esc_html_e( 'Benefits', 'pls-private-label-store' ); ?></span>
                        <span class="pls-page-accordion__icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                    <div class="pls-page-accordion__content">
                        <ul class="pls-benefits-list">
                            <?php foreach ( $benefits_data as $benefit ) :
                                $label = is_array( $benefit ) && isset( $benefit['label'] ) ? $benefit['label'] : $benefit;
                                $icon_url = is_array( $benefit ) && isset( $benefit['icon'] ) && ! empty( $benefit['icon'] ) ? $benefit['icon'] : '';
                                ?>
                                <li class="pls-benefits-list__item">
                                    <?php if ( $icon_url ) : ?>
                                        <img src="<?php echo esc_url( $icon_url ); ?>" alt="" class="pls-benefits-list__icon" />
                                    <?php else : ?>
                                        <span class="pls-benefits-list__check">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.7L6.5 11.5L2.7 7.7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                    <?php endif; ?>
                                    <span><?php echo esc_html( $label ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- 8. Description Accordion -->
            <?php if ( $options['show_description'] && ! empty( $profile->long_description ) ) : ?>
                <div class="pls-page-accordion">
                    <button type="button" class="pls-page-accordion__header" aria-expanded="false">
                        <span class="pls-page-accordion__title"><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></span>
                        <span class="pls-page-accordion__icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                    <div class="pls-page-accordion__content" style="display: none;">
                        <div class="pls-product-description">
                            <?php echo wp_kses_post( wpautop( $profile->long_description ) ); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 9. Directions Accordion -->
            <?php if ( ! empty( $profile->directions_text ) ) : ?>
                <div class="pls-page-accordion">
                    <button type="button" class="pls-page-accordion__header" aria-expanded="false">
                        <span class="pls-page-accordion__title"><?php esc_html_e( 'Directions', 'pls-private-label-store' ); ?></span>
                        <span class="pls-page-accordion__icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </button>
                    <div class="pls-page-accordion__content" style="display: none;">
                        <div class="pls-product-directions">
                            <?php echo wp_kses_post( wpautop( $profile->directions_text ) ); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 10. Base Ingredients INCI Section -->
            <?php if ( $options['show_ingredients'] && ! empty( $profile->ingredients_list ) ) :
                self::render_base_ingredients( $profile );
            endif; ?>
        </div>
        <?php
    }

    /**
     * Render skin type badges as inline pills.
     * v5.7.0: NEW - displays skin_types_json as rounded pill badges.
     *
     * @param object $profile The product profile.
     */
    private static function render_skin_type_badges( $profile ) {
        if ( empty( $profile->skin_types_json ) ) {
            return;
        }
        $skin_types_data = json_decode( $profile->skin_types_json, true );
        if ( ! is_array( $skin_types_data ) || empty( $skin_types_data ) ) {
            return;
        }
        ?>
        <div class="pls-skin-badges">
            <?php foreach ( $skin_types_data as $skin_type ) :
                $label = is_array( $skin_type ) && isset( $skin_type['label'] ) ? $skin_type['label'] : $skin_type;
                ?>
                <span class="pls-skin-badge"><?php echo esc_html( $label ); ?></span>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render the starting price block.
     * v5.7.0: Extracted from render_product_header for reuse.
     *
     * @param WC_Product $product The product.
     */
    private static function render_starting_price_block( $product ) {
        if ( ! $product->is_type( 'variable' ) ) {
            ?>
            <div class="pls-product-price">
                <?php echo $product->get_price_html(); ?>
            </div>
            <?php
            return;
        }

        $variation_attributes = $product->get_variation_attributes();
        $pack_tiers = isset( $variation_attributes['pa_pack-tier'] ) ? (array) $variation_attributes['pa_pack-tier'] : array();
        if ( empty( $pack_tiers ) ) {
            return;
        }

        $lowest_price_per_unit = null;
        foreach ( $pack_tiers as $tier_slug ) {
            $tier_term = get_term_by( 'slug', $tier_slug, 'pa_pack-tier' );
            if ( ! $tier_term ) {
                continue;
            }
            $units = (int) get_term_meta( $tier_term->term_id, '_pls_default_units', true );
            $variation_id = self::get_variation_for_tier( $product->get_id(), $tier_slug );
            if ( $variation_id && $units > 0 ) {
                $variation = wc_get_product( $variation_id );
                if ( $variation ) {
                    $total_price = $variation->get_price();
                    $price_per_unit = $total_price / $units;
                    if ( null === $lowest_price_per_unit || $price_per_unit < $lowest_price_per_unit ) {
                        $lowest_price_per_unit = $price_per_unit;
                    }
                }
            }
        }

        if ( $lowest_price_per_unit ) {
            ?>
            <div class="pls-product-starting-price">
                <?php esc_html_e( 'Starting from', 'pls-private-label-store' ); ?>
                <strong><?php echo wc_price( $lowest_price_per_unit ); ?></strong>
                <span class="pls-price-suffix"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
            </div>
            <?php
        }
    }

    /**
     * Render the expanded trust badges row.
     * v5.7.0: NEW - expanded set with icons.
     */
    private static function render_trust_badges_row() {
        $badges = array(
            array( 'icon' => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="1" y="4" width="14" height="9" rx="1" stroke="currentColor" stroke-width="1.5"/><path d="M1 7h14" stroke="currentColor" stroke-width="1.5"/><text x="8" y="11" text-anchor="middle" font-size="5" fill="currentColor" font-weight="bold">AU</text></svg>', 'label' => __( 'Australian Made', 'pls-private-label-store' ) ),
            array( 'icon' => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 2C8 2 4 5 4 9c0 2.2 1.8 4 4 4s4-1.8 4-4c0-4-4-7-4-7z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 8v3M6 10l2 2 2-2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'label' => __( 'Vegan', 'pls-private-label-store' ) ),
            array( 'icon' => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 14s6-4.5 6-8A4 4 0 008 2a4 4 0 00-6 4c0 3.5 6 8 6 8z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'label' => __( 'Cruelty Free', 'pls-private-label-store' ) ),
            array( 'icon' => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M8 1v2M8 13v2M1 8h2M13 8h2M3.05 3.05l1.41 1.41M11.54 11.54l1.41 1.41M3.05 12.95l1.41-1.41M11.54 4.46l1.41-1.41" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>', 'label' => __( 'Clinically Tested', 'pls-private-label-store' ) ),
            array( 'icon' => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M5 7V5a3 3 0 016 0v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>', 'label' => __( 'Secure Checkout', 'pls-private-label-store' ) ),
            array( 'icon' => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M1 9h2l2-2h4l1 2h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="4.5" cy="12" r="1.5" stroke="currentColor" stroke-width="1.2"/><circle cx="11.5" cy="12" r="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M14 9V6H10l-1 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>', 'label' => __( 'Fast Delivery', 'pls-private-label-store' ) ),
        );
        ?>
        <div class="pls-trust-badges-row">
            <?php foreach ( $badges as $badge ) : ?>
                <span class="pls-trust-badge">
                    <span class="pls-trust-badge__icon"><?php echo $badge['icon']; ?></span>
                    <span class="pls-trust-badge__label"><?php echo esc_html( $badge['label'] ); ?></span>
                </span>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render base ingredients section as an accordion.
     * v5.7.0: NEW - enhanced visual display of base/INCI ingredients.
     * NO active/key ingredients here (those only appear in configurator Step 4).
     *
     * @param object $profile The product profile.
     */
    private static function render_base_ingredients( $profile ) {
        $ingredient_ids = array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) );

        // Get key ingredient IDs to exclude them
        $key_ingredient_ids = array();
        if ( ! empty( $profile->key_ingredients_json ) ) {
            $key_data = json_decode( $profile->key_ingredients_json, true );
            if ( is_array( $key_data ) ) {
                foreach ( $key_data as $ki ) {
                    $term_id = isset( $ki['term_id'] ) ? absint( $ki['term_id'] ) : ( isset( $ki['id'] ) ? absint( $ki['id'] ) : 0 );
                    if ( $term_id ) {
                        $key_ingredient_ids[] = $term_id;
                    }
                }
            }
        }

        // Build base ingredients list (excluding key/active)
        $base_ingredients = array();
        $all_names = array();
        foreach ( $ingredient_ids as $term_id ) {
            $term = get_term( $term_id, 'pls_ingredient' );
            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }
            $all_names[] = $term->name;

            // Only include base ingredients (not key/active)
            if ( ! in_array( $term_id, $key_ingredient_ids, true ) ) {
                $short_desc = get_term_meta( $term_id, 'pls_ingredient_short_desc', true );
                if ( empty( $short_desc ) ) {
                    $short_desc = $term->description;
                }
                $base_ingredients[] = array(
                    'id'   => $term_id,
                    'name' => $term->name,
                    'desc' => $short_desc,
                );
            }
        }

        if ( empty( $base_ingredients ) && empty( $all_names ) ) {
            return;
        }
        ?>
        <div class="pls-page-accordion">
            <button type="button" class="pls-page-accordion__header" aria-expanded="false">
                <span class="pls-page-accordion__title"><?php esc_html_e( 'Base Ingredients (INCI)', 'pls-private-label-store' ); ?></span>
                <span class="pls-page-accordion__icon">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </button>
            <div class="pls-page-accordion__content" style="display: none;">
                <?php if ( ! empty( $base_ingredients ) ) : ?>
                    <div class="pls-base-ingredients-grid">
                        <?php foreach ( $base_ingredients as $ingredient ) :
                            $icon_url = class_exists( 'PLS_Taxonomies' ) ? PLS_Taxonomies::icon_for_term( $ingredient['id'] ) : '';
                            $default_icon = class_exists( 'PLS_Taxonomies' ) ? PLS_Taxonomies::default_icon() : '';
                            ?>
                            <div class="pls-base-ingredient-card">
                                <?php if ( $icon_url && $icon_url !== $default_icon ) : ?>
                                    <div class="pls-base-ingredient-card__icon">
                                        <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $ingredient['name'] ); ?>" />
                                    </div>
                                <?php endif; ?>
                                <div class="pls-base-ingredient-card__info">
                                    <span class="pls-base-ingredient-card__name"><?php echo esc_html( $ingredient['name'] ); ?></span>
                                    <?php if ( ! empty( $ingredient['desc'] ) ) : ?>
                                        <span class="pls-base-ingredient-card__desc"><?php echo esc_html( $ingredient['desc'] ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $all_names ) ) : ?>
                    <div class="pls-inci-list">
                        <h4><?php esc_html_e( 'Full INCI List', 'pls-private-label-store' ); ?></h4>
                        <p class="pls-inci-text"><?php echo esc_html( implode( ', ', $all_names ) ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Legacy: Render product header with images and short description.
     * @deprecated 5.7.0 Use render_product_images() + render_product_right_column() instead.
     */
    private static function render_product_header( $product, $profile, $options = array() ) {
        // v5.7.0: Redirect to new methods via get_pls_content layout
        self::render_product_images( $product, $profile );
    }

    /**
     * Render configurator as multi-step modal overlay.
     * v5.7.0: Complete redesign as 6-step configurator with header, step indicator, footer.
     *
     * @param WC_Product $product The product.
     * @param object     $profile The product profile.
     */
    private static function render_configurator_modal( $product, $profile ) {
        if ( ! $product || ! $profile ) {
            return;
        }

        $variation_attributes = $product->get_variation_attributes();
        $pack_tiers = isset( $variation_attributes['pa_pack-tier'] ) ? (array) $variation_attributes['pa_pack-tier'] : array();

        if ( empty( $pack_tiers ) ) {
            return;
        }

        // Parse basics_json for product options
        $product_options = array();
        $label_application_option = null;
        if ( ! empty( $profile->basics_json ) ) {
            $basics_data = json_decode( $profile->basics_json, true );
            if ( is_array( $basics_data ) ) {
                foreach ( $basics_data as $attr ) {
                    if ( ! isset( $attr['attribute_label'] ) || ! isset( $attr['values'] ) || ! is_array( $attr['values'] ) ) {
                        continue;
                    }
                    if ( stripos( $attr['attribute_label'], 'pack tier' ) !== false ) {
                        continue; // Skip pack-tier
                    }
                    if ( stripos( $attr['attribute_label'], 'label application' ) !== false || stripos( $attr['attribute_label'], 'label' ) !== false ) {
                        $label_application_option = $attr;
                        continue;
                    }
                    $product_options[] = $attr;
                }
            }
        }

        // Get key ingredients (Tier 3+)
        $key_ingredient_ids = array();
        if ( ! empty( $profile->key_ingredients_json ) ) {
            $key_data = json_decode( $profile->key_ingredients_json, true );
            if ( is_array( $key_data ) ) {
                foreach ( $key_data as $ki ) {
                    $term_id = isset( $ki['term_id'] ) ? absint( $ki['term_id'] ) : ( isset( $ki['id'] ) ? absint( $ki['id'] ) : 0 );
                    if ( $term_id ) {
                        $is_active = get_term_meta( $term_id, 'pls_ingredient_is_active', true );
                        $min_tier_level = get_term_meta( $term_id, '_pls_ingredient_min_tier_level', true );
                        if ( '' === $min_tier_level || false === $min_tier_level ) {
                            $min_tier_level = $is_active ? 3 : 1;
                        } else {
                            $min_tier_level = absint( $min_tier_level );
                        }
                        if ( $min_tier_level >= 3 ) {
                            $key_ingredient_ids[] = $term_id;
                        }
                    }
                }
            }
        }

        // Determine number of steps (skip steps 3-4 if no premium options / ingredients)
        $has_premium_options = false; // TODO: detect premium options from options data
        $has_active_ingredients = ! empty( $key_ingredient_ids );
        $has_label_option = ! empty( $label_application_option );

        ?>
        <div class="pls-configurator-modal" id="pls-configurator-modal" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
            <div class="pls-configurator-modal__overlay"></div>
            <div class="pls-configurator-modal__content">
                <button type="button" class="pls-configurator-modal__close" aria-label="<?php esc_attr_e( 'Close', 'pls-private-label-store' ); ?>">&times;</button>

                <!-- Modal Header -->
                <div class="pls-modal-header">
                    <h2 class="pls-modal-header__title"><?php echo esc_html( $product->get_name() ); ?></h2>
                    <span class="pls-modal-header__pack" id="pls-modal-pack-info"></span>
                </div>

                <!-- Step Indicator -->
                <div class="pls-step-indicator" id="pls-step-indicator">
                    <div class="pls-step-dot is-active" data-step="1"><span>1</span></div>
                    <div class="pls-step-dot" data-step="2"><span>2</span></div>
                    <div class="pls-step-dot" data-step="3"><span>3</span></div>
                    <div class="pls-step-dot" data-step="4"><span>4</span></div>
                    <div class="pls-step-dot" data-step="5"><span>5</span></div>
                    <div class="pls-step-dot" data-step="6"><span>6</span></div>
                </div>

                <!-- Step Content Area -->
                <div class="pls-step-content" id="pls-step-content">

                    <!-- STEP 1: Pack Size -->
                    <div class="pls-step-panel is-active" data-step="1">
                        <h3 class="pls-step-panel__title"><?php esc_html_e( 'Select Your Pack Size', 'pls-private-label-store' ); ?></h3>
                        <p class="pls-step-panel__desc"><?php esc_html_e( 'Choose the pack size that best fits your needs.', 'pls-private-label-store' ); ?></p>
                        <div class="pls-step1-pack-select">
                            <select id="pls-pack-select" class="pls-pack-dropdown">
                                <option value=""><?php esc_html_e( '— Choose pack size —', 'pls-private-label-store' ); ?></option>
                                <?php foreach ( $pack_tiers as $tier_slug ) :
                                    $tier_term = get_term_by( 'slug', $tier_slug, 'pa_pack-tier' );
                                    if ( ! $tier_term ) continue;

                                    $units = (int) get_term_meta( $tier_term->term_id, '_pls_default_units', true );
                                    $tier_key = get_term_meta( $tier_term->term_id, '_pls_tier_key', true );
                                    $variation_id = self::get_variation_for_tier( $product->get_id(), $tier_slug );
                                    $variation_price = 0;
                                    $price_per_unit = 0;

                                    if ( $variation_id ) {
                                        $variation = wc_get_product( $variation_id );
                                        if ( $variation ) {
                                            $variation_price = floatval( $variation->get_price() );
                                            if ( $units > 0 && $variation_price > 0 ) {
                                                $price_per_unit = $variation_price / $units;
                                            }
                                        }
                                    }
                                    $tier_level = self::get_tier_level( $tier_key );
                                    ?>
                                    <option value="<?php echo esc_attr( $tier_slug ); ?>"
                                            data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
                                            data-units="<?php echo esc_attr( $units ); ?>"
                                            data-price-per-unit="<?php echo esc_attr( $price_per_unit ); ?>"
                                            data-total-price="<?php echo esc_attr( $variation_price ); ?>"
                                            data-tier-key="<?php echo esc_attr( $tier_key ); ?>"
                                            data-tier-level="<?php echo esc_attr( $tier_level ); ?>">
                                        <?php echo esc_html( sprintf( '%s units — $%s/unit', number_format( $units ), number_format( $price_per_unit, 2 ) ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- v5.7.0: Bundle Nudge - shown when qualifying pack size is selected -->
                        <div class="pls-bundle-nudge" id="pls-bundle-nudge" style="display: none;">
                            <div class="pls-bundle-nudge__icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            </div>
                            <div class="pls-bundle-nudge__content">
                                <strong class="pls-bundle-nudge__title"><?php esc_html_e( 'Save more!', 'pls-private-label-store' ); ?></strong>
                                <p class="pls-bundle-nudge__text" id="pls-bundle-nudge-text"></p>
                            </div>
                            <div class="pls-bundle-nudge__savings" id="pls-bundle-nudge-savings"></div>
                        </div>
                    </div>

                    <!-- STEP 2: Package Configuration -->
                    <div class="pls-step-panel" data-step="2">
                        <h3 class="pls-step-panel__title"><?php esc_html_e( 'Package Configuration', 'pls-private-label-store' ); ?></h3>
                        <p class="pls-step-panel__desc"><?php esc_html_e( 'Customize your packaging.', 'pls-private-label-store' ); ?></p>
                        <?php if ( ! empty( $product_options ) ) : ?>
                            <div class="pls-product-options-list">
                                <?php foreach ( $product_options as $option ) :
                                    $attr_label = isset( $option['attribute_label'] ) ? $option['attribute_label'] : '';
                                    $values = isset( $option['values'] ) && is_array( $option['values'] ) ? $option['values'] : array();
                                    if ( empty( $values ) ) continue;
                                    ?>
                                    <div class="pls-product-option-group" data-attribute-label="<?php echo esc_attr( $attr_label ); ?>">
                                        <label class="pls-product-option-label">
                                            <?php echo esc_html( $attr_label ); ?>
                                            <span class="pls-option-group-hint"><?php esc_html_e( '(Select one)', 'pls-private-label-store' ); ?></span>
                                        </label>
                                        <div class="pls-product-option-values">
                                            <?php foreach ( $values as $value ) :
                                                $value_id = isset( $value['id'] ) ? absint( $value['id'] ) : ( isset( $value['value_id'] ) ? absint( $value['value_id'] ) : 0 );
                                                $value_label = isset( $value['label'] ) ? $value['label'] : ( isset( $value['value_label'] ) ? $value['value_label'] : '' );
                                                $value_price = isset( $value['price'] ) ? floatval( $value['price'] ) : 0;
                                                $tier_overrides = isset( $value['tier_price_overrides'] ) && is_array( $value['tier_price_overrides'] ) ? $value['tier_price_overrides'] : null;
                                                $min_tier_level = isset( $value['min_tier_level'] ) ? absint( $value['min_tier_level'] ) : 1;
                                                if ( ! $min_tier_level && $value_id && class_exists( 'PLS_Repo_Attributes' ) ) {
                                                    $value_obj = PLS_Repo_Attributes::get_value( $value_id );
                                                    $min_tier_level = ( $value_obj && isset( $value_obj->min_tier_level ) ) ? absint( $value_obj->min_tier_level ) : 1;
                                                }
                                                $is_standard = ( stripos( strtolower( $value_label ), 'standard' ) !== false ||
                                                                 stripos( strtolower( $value_label ), 'clear' ) !== false ||
                                                                 stripos( strtolower( $value_label ), 'black' ) !== false ||
                                                                 stripos( strtolower( $value_label ), 'included' ) !== false );
                                                $has_price = ( $min_tier_level >= 3 && ! $is_standard && ( $value_price > 0 || $tier_overrides ) );
                                                ?>
                                                <label class="pls-option-value-card <?php echo $is_standard ? 'is-standard' : ''; ?> <?php echo $has_price ? 'has-price' : ''; ?>">
                                                    <input type="radio"
                                                           name="pls_option_<?php echo esc_attr( sanitize_title( $attr_label ) ); ?>"
                                                           value="<?php echo esc_attr( $value_id ); ?>"
                                                           data-value-id="<?php echo esc_attr( $value_id ); ?>"
                                                           data-price="<?php echo esc_attr( $value_price ); ?>"
                                                           data-min-tier-level="<?php echo esc_attr( $min_tier_level ); ?>"
                                                           data-tier-prices="<?php echo esc_attr( $tier_overrides ? wp_json_encode( $tier_overrides ) : '' ); ?>"
                                                           <?php echo $is_standard ? 'checked' : ''; ?>
                                                           class="pls-option-radio" />
                                                    <span class="pls-option-radio-indicator"></span>
                                                    <span class="pls-option-value-content">
                                                        <span class="pls-option-value-label"><?php echo esc_html( $value_label ); ?></span>
                                                        <?php if ( $has_price ) : ?>
                                                            <span class="pls-option-price-badge pls-option-price-badge--paid">
                                                                <span class="pls-option-price-prefix">+</span>
                                                                <?php
                                                                if ( $tier_overrides && isset( $tier_overrides[1] ) ) {
                                                                    echo '$' . number_format( floatval( $tier_overrides[1] ), 2 );
                                                                    echo ' <span class="pls-option-price-note">' . esc_html__( 'per unit', 'pls-private-label-store' ) . '</span>';
                                                                } elseif ( $value_price > 0 ) {
                                                                    echo '$' . number_format( $value_price, 2 );
                                                                    echo ' <span class="pls-option-price-note">' . esc_html__( 'per unit', 'pls-private-label-store' ) . '</span>';
                                                                }
                                                                ?>
                                                            </span>
                                                        <?php else : ?>
                                                            <span class="pls-option-price-badge pls-option-price-badge--included">
                                                                <span class="pls-option-included-icon">&#10003;</span>
                                                                <?php esc_html_e( 'Included', 'pls-private-label-store' ); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p class="pls-step-empty"><?php esc_html_e( 'No package options available for this product.', 'pls-private-label-store' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- STEP 3: Premium Options (Tier 3+ only) -->
                    <div class="pls-step-panel" data-step="3">
                        <h3 class="pls-step-panel__title"><?php esc_html_e( 'Premium Options', 'pls-private-label-store' ); ?></h3>
                        <div class="pls-step3-teaser" id="pls-step3-teaser">
                            <div class="pls-tier-upgrade-notice">
                                <p><?php esc_html_e( 'Upgrade to Tier 3 or higher to unlock premium options:', 'pls-private-label-store' ); ?></p>
                                <ul>
                                    <li><?php esc_html_e( 'Custom fragrance selection', 'pls-private-label-store' ); ?></li>
                                    <li><?php esc_html_e( 'Custom printed bottles (Tier 4+)', 'pls-private-label-store' ); ?></li>
                                    <li><?php esc_html_e( 'External box packaging (Tier 4+)', 'pls-private-label-store' ); ?></li>
                                </ul>
                            </div>
                        </div>
                        <div class="pls-step3-content" id="pls-step3-content" style="display: none;">
                            <p class="pls-step-panel__desc"><?php esc_html_e( 'Customize premium features for your product.', 'pls-private-label-store' ); ?></p>
                            <p class="pls-subtle"><?php esc_html_e( 'Premium options coming soon. Proceed to the next step.', 'pls-private-label-store' ); ?></p>
                        </div>
                    </div>

                    <!-- STEP 4: Active Ingredients (Tier 3+ only) -->
                    <div class="pls-step-panel" data-step="4">
                        <h3 class="pls-step-panel__title"><?php esc_html_e( 'Active Ingredients', 'pls-private-label-store' ); ?></h3>
                        <div class="pls-step4-teaser" id="pls-step4-teaser">
                            <div class="pls-tier-upgrade-notice">
                                <p><?php esc_html_e( 'Upgrade to Tier 3 or higher to customize your active ingredients.', 'pls-private-label-store' ); ?></p>
                            </div>
                        </div>
                        <div class="pls-step4-content" id="pls-step4-content" style="display: none;">
                            <p class="pls-step-panel__desc"><?php esc_html_e( 'Select additional active ingredients to include. Base ingredients are already included.', 'pls-private-label-store' ); ?></p>
                            <?php if ( ! empty( $key_ingredient_ids ) ) : ?>
                                <div class="pls-active-ingredients-grid">
                                    <?php foreach ( $key_ingredient_ids as $term_id ) :
                                        $term = get_term( $term_id, 'pls_ingredient' );
                                        if ( ! $term || is_wp_error( $term ) ) continue;

                                        $price_impact = floatval( get_term_meta( $term_id, '_pls_ingredient_price_impact', true ) );
                                        $has_price = $price_impact > 0;
                                        $icon = class_exists( 'PLS_Taxonomies' ) ? PLS_Taxonomies::icon_for_term( $term_id ) : '';
                                        $short_desc = get_term_meta( $term_id, 'pls_ingredient_short_desc', true );
                                        ?>
                                        <label class="pls-ingredient-select-card <?php echo $has_price ? 'has-price' : ''; ?>">
                                            <input type="checkbox"
                                                   name="pls_active_ingredients[]"
                                                   value="<?php echo esc_attr( $term_id ); ?>"
                                                   data-ingredient-id="<?php echo esc_attr( $term_id ); ?>"
                                                   data-price-impact="<?php echo esc_attr( $price_impact ); ?>"
                                                   class="pls-active-ingredient-checkbox" />
                                            <span class="pls-ingredient-select-card__inner">
                                                <?php if ( $icon ) : ?>
                                                    <span class="pls-ingredient-select-card__icon">
                                                        <img src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" />
                                                    </span>
                                                <?php endif; ?>
                                                <span class="pls-ingredient-select-card__name"><?php echo esc_html( $term->name ); ?></span>
                                                <?php if ( $short_desc ) : ?>
                                                    <span class="pls-ingredient-select-card__desc"><?php echo esc_html( $short_desc ); ?></span>
                                                <?php endif; ?>
                                                <?php if ( $has_price ) : ?>
                                                    <span class="pls-ingredient-select-card__price">+<?php echo wc_price( $price_impact ); ?>/unit</span>
                                                <?php else : ?>
                                                    <span class="pls-ingredient-select-card__price pls-ingredient-select-card__price--included"><?php esc_html_e( 'Included', 'pls-private-label-store' ); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p class="pls-step-empty"><?php esc_html_e( 'No active ingredients available for this product. Proceed to the next step.', 'pls-private-label-store' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- STEP 5: Label Application -->
                    <div class="pls-step-panel" data-step="5">
                        <h3 class="pls-step-panel__title"><?php esc_html_e( 'Label Application', 'pls-private-label-store' ); ?></h3>
                        <p class="pls-step-panel__desc"><?php esc_html_e( 'Choose how you want your labels handled.', 'pls-private-label-store' ); ?></p>
                        <div class="pls-label-options">
                            <label class="pls-label-option-card is-selected">
                                <input type="radio" name="pls_label_application" value="none" checked class="pls-label-radio" />
                                <span class="pls-label-option-card__inner">
                                    <span class="pls-label-option-card__title"><?php esc_html_e( 'No Labels Required', 'pls-private-label-store' ); ?></span>
                                    <span class="pls-label-option-card__desc"><?php esc_html_e( 'Products will be shipped without labels.', 'pls-private-label-store' ); ?></span>
                                    <span class="pls-label-option-card__price"><?php esc_html_e( 'Free', 'pls-private-label-store' ); ?></span>
                                </span>
                            </label>
                            <label class="pls-label-option-card">
                                <input type="radio" name="pls_label_application" value="professional" class="pls-label-radio"
                                       data-price="<?php echo esc_attr( $profile->label_price_per_unit ?? '0' ); ?>" />
                                <span class="pls-label-option-card__inner">
                                    <span class="pls-label-option-card__title"><?php esc_html_e( 'Yes - Professional Application', 'pls-private-label-store' ); ?></span>
                                    <span class="pls-label-option-card__desc"><?php esc_html_e( 'We apply your labels professionally before shipping.', 'pls-private-label-store' ); ?></span>
                                    <span class="pls-label-option-card__price" id="pls-label-pro-price">
                                        <?php
                                        $label_price = floatval( $profile->label_price_per_unit ?? 0 );
                                        if ( $label_price > 0 ) {
                                            printf( '+%s/unit', wc_price( $label_price ) );
                                        } else {
                                            esc_html_e( 'Free for Tier 3+', 'pls-private-label-store' );
                                        }
                                        ?>
                                    </span>
                                </span>
                            </label>
                            <label class="pls-label-option-card">
                                <input type="radio" name="pls_label_application" value="diy" class="pls-label-radio"
                                       data-price="<?php echo esc_attr( $profile->label_price_per_unit ?? '0' ); ?>" />
                                <span class="pls-label-option-card__inner">
                                    <span class="pls-label-option-card__title"><?php esc_html_e( 'Yes - DIY (Ship Labels Separately)', 'pls-private-label-store' ); ?></span>
                                    <span class="pls-label-option-card__desc"><?php esc_html_e( 'We ship printed labels separately for you to apply.', 'pls-private-label-store' ); ?></span>
                                    <span class="pls-label-option-card__price">
                                        <?php
                                        if ( $label_price > 0 ) {
                                            printf( '+%s/unit', wc_price( $label_price ) );
                                        } else {
                                            esc_html_e( 'Free for Tier 3+', 'pls-private-label-store' );
                                        }
                                        ?>
                                    </span>
                                </span>
                            </label>
                        </div>
                        <?php if ( ! empty( $profile->label_helper_text ) ) : ?>
                            <p class="pls-label-helper"><?php echo esc_html( $profile->label_helper_text ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $profile->label_guide_url ) ) : ?>
                            <a href="<?php echo esc_url( $profile->label_guide_url ); ?>" target="_blank" class="pls-label-guide-link"><?php esc_html_e( 'View Label Guide', 'pls-private-label-store' ); ?> &rarr;</a>
                        <?php endif; ?>
                    </div>

                    <!-- STEP 6: Review & Add to Cart -->
                    <div class="pls-step-panel" data-step="6">
                        <h3 class="pls-step-panel__title"><?php esc_html_e( 'Review Your Order', 'pls-private-label-store' ); ?></h3>
                        <p class="pls-step-panel__desc"><?php esc_html_e( 'Review your selections before adding to cart.', 'pls-private-label-store' ); ?></p>

                        <div class="pls-review-summary" id="pls-review-summary">
                            <!-- Populated by JS -->
                        </div>

                        <!-- v5.7.0: Bundle Savings Banner in Review -->
                        <div class="pls-bundle-savings-banner" id="pls-review-bundle-savings" style="display: none;">
                            <div class="pls-bundle-savings-banner__icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            </div>
                            <div class="pls-bundle-savings-banner__content">
                                <strong id="pls-review-bundle-title"></strong>
                                <p id="pls-review-bundle-text"></p>
                            </div>
                        </div>

                        <div class="pls-price-breakdown" id="pls-review-price-breakdown">
                            <div class="pls-price-row">
                                <span class="pls-price-label"><?php esc_html_e( 'Base Price (total)', 'pls-private-label-store' ); ?></span>
                                <span class="pls-price-value" id="pls-price-base"><?php echo wc_price( 0 ); ?></span>
                            </div>
                            <div class="pls-price-row" id="pls-price-options-row" style="display: none;">
                                <span class="pls-price-label"><?php esc_html_e( 'Options & Ingredients (total)', 'pls-private-label-store' ); ?></span>
                                <span class="pls-price-value" id="pls-price-options"><?php echo wc_price( 0 ); ?></span>
                            </div>
                            <!-- v5.7.0: Bundle savings row -->
                            <div class="pls-price-row pls-price-row--savings" id="pls-price-savings-row" style="display: none;">
                                <span class="pls-price-label" style="color: #0d9488;"><?php esc_html_e( 'Bundle Savings', 'pls-private-label-store' ); ?></span>
                                <span class="pls-price-value" id="pls-price-savings" style="color: #0d9488;"></span>
                            </div>
                            <div class="pls-price-row pls-price-row--total">
                                <span class="pls-price-label"><?php esc_html_e( 'Order Total', 'pls-private-label-store' ); ?></span>
                                <span class="pls-price-value" id="pls-price-total"><?php echo wc_price( 0 ); ?></span>
                            </div>
                            <div class="pls-price-row pls-price-row--per-unit">
                                <span class="pls-price-label"><strong><?php esc_html_e( 'Price Per Unit', 'pls-private-label-store' ); ?></strong></span>
                                <span class="pls-price-value" id="pls-price-per-unit"><strong><?php echo wc_price( 0 ); ?></strong></span>
                            </div>
                        </div>

                        <form class="pls-cart-form variations_form cart" method="post" enctype="multipart/form-data" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>">
                            <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>">
                            <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>">
                            <input type="hidden" name="variation_id" value="" class="pls-variation-id" />
                            <input type="hidden" name="quantity" value="1" />

                            <button type="submit"
                                    class="pls-add-to-cart-button button button-primary button-large"
                                    disabled>
                                <span class="pls-add-to-cart-text"><?php esc_html_e( 'Add to Cart', 'pls-private-label-store' ); ?></span>
                            </button>
                            <div class="pls-cart-messages" id="pls-cart-messages"></div>
                        </form>
                    </div>

                </div><!-- .pls-step-content -->

                <!-- Modal Footer (sticky) -->
                <div class="pls-modal-footer" id="pls-modal-footer">
                    <div class="pls-modal-footer__price">
                        <span class="pls-modal-footer__per-unit" id="pls-footer-per-unit">$0.00/unit</span>
                        <span class="pls-modal-footer__total" id="pls-footer-total"><?php esc_html_e( 'Total:', 'pls-private-label-store' ); ?> $0.00</span>
                    </div>
                    <div class="pls-modal-footer__nav">
                        <button type="button" class="pls-modal-nav-btn pls-modal-nav-btn--prev" id="pls-prev-step" style="display: none;">
                            &larr; <?php esc_html_e( 'Back', 'pls-private-label-store' ); ?>
                        </button>
                        <button type="button" class="pls-modal-nav-btn pls-modal-nav-btn--next" id="pls-next-step">
                            <?php esc_html_e( 'Next', 'pls-private-label-store' ); ?> &rarr;
                        </button>
                    </div>
                </div>

            </div><!-- .pls-configurator-modal__content -->
        </div><!-- .pls-configurator-modal -->
        <?php
    }

    /**
     * Legacy: Render full configurator (old single-page version).
     * @deprecated 5.7.0 Use render_configurator_modal() multi-step version instead.
     */
    private static function render_full_configurator( $product, $profile ) {
        // v5.7.0: This method is now handled by the multi-step modal.
        // Kept as empty stub for backward compatibility with inline configurator.
        return;
    }

    /**
     * Render product information sections (tabs/accordion).
     *
     * @param WC_Product $product The product.
     * @param object     $profile The product profile.
     */
    private static function render_product_info_sections( $product, $profile ) {
        $has_description = ! empty( $profile->long_description );
        $has_directions = ! empty( $profile->directions_text );
        $has_skin_types = ! empty( $profile->skin_types_json );
        // Benefits are now rendered separately, not in tabs
        $has_benefits = false;
        
        if ( ! $has_description && ! $has_directions && ! $has_skin_types ) {
            return;
        }
        
        ?>
        <div class="pls-product-info-sections">
            <div class="pls-product-tabs">
                <?php if ( $has_description ) : ?>
                    <button type="button" class="pls-tab-button is-active" data-tab="description">
                        <?php esc_html_e( 'Description', 'pls-private-label-store' ); ?>
                    </button>
                <?php endif; ?>
                
                <?php if ( $has_directions ) : ?>
                    <button type="button" class="pls-tab-button" data-tab="directions">
                        <?php esc_html_e( 'Directions', 'pls-private-label-store' ); ?>
                    </button>
                <?php endif; ?>
                
                <?php if ( $has_skin_types || $has_benefits ) : ?>
                    <button type="button" class="pls-tab-button" data-tab="info">
                        <?php esc_html_e( 'Product Info', 'pls-private-label-store' ); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="pls-product-tab-content">
                <?php if ( $has_description ) : ?>
                    <div class="pls-tab-panel is-active" data-tab="description">
                        <h2><?php esc_html_e( 'About This Product', 'pls-private-label-store' ); ?></h2>
                        <div class="pls-product-description">
                            <?php echo wp_kses_post( wpautop( $profile->long_description ) ); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ( $has_directions ) : ?>
                    <div class="pls-tab-panel" data-tab="directions">
                        <h2><?php esc_html_e( 'Directions for Use', 'pls-private-label-store' ); ?></h2>
                        <div class="pls-product-directions">
                            <?php echo wp_kses_post( wpautop( $profile->directions_text ) ); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ( $has_skin_types ) : ?>
                    <div class="pls-tab-panel" data-tab="info">
                        <?php 
                        $skin_types_data = json_decode( $profile->skin_types_json, true );
                        if ( is_array( $skin_types_data ) && ! empty( $skin_types_data ) ) :
                            ?>
                            <div class="pls-product-skin-types">
                                <h3><?php esc_html_e( 'Suitable for Skin Types', 'pls-private-label-store' ); ?></h3>
                                <div class="pls-skin-type-pills">
                                    <?php foreach ( $skin_types_data as $skin_type ) : 
                                        $label = is_array( $skin_type ) && isset( $skin_type['label'] ) ? $skin_type['label'] : $skin_type;
                                        ?>
                                        <span class="pls-skin-type-pill"><?php echo esc_html( $label ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render benefits section as standalone visual cards.
     *
     * @param object $profile The product profile.
     */
    private static function render_benefits_section( $profile ) {
        if ( empty( $profile->benefits_json ) ) {
            return;
        }

        $benefits_data = json_decode( $profile->benefits_json, true );
        if ( ! is_array( $benefits_data ) || empty( $benefits_data ) ) {
            return;
        }

        ?>
        <section class="pls-benefits-section">
            <h2 class="pls-section-title"><?php esc_html_e( 'The Benefits', 'pls-private-label-store' ); ?></h2>
            <div class="pls-benefits-grid">
                <?php foreach ( $benefits_data as $benefit ) : 
                    $label = is_array( $benefit ) && isset( $benefit['label'] ) ? $benefit['label'] : $benefit;
                    $icon_url = is_array( $benefit ) && isset( $benefit['icon'] ) && ! empty( $benefit['icon'] ) ? $benefit['icon'] : '';
                    $image_url = is_array( $benefit ) && isset( $benefit['image'] ) && ! empty( $benefit['image'] ) ? $benefit['image'] : '';
                    ?>
                    <div class="pls-benefit-card">
                        <div class="pls-benefit-icon">
                            <?php if ( $image_url ) : ?>
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $label ); ?>" />
                            <?php elseif ( $icon_url ) : ?>
                                <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $label ); ?>" />
                            <?php else : ?>
                                <span class="pls-benefit-icon-placeholder">✓</span>
                            <?php endif; ?>
                        </div>
                        <div class="pls-benefit-label"><?php echo esc_html( $label ); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Render inline configurator (v4.9.99 feature).
     * This renders the configurator directly in the page flow (not in a modal).
     *
     * @param WC_Product $product The product.
     * @param string     $instance_id Optional unique ID for multiple instances on same page.
     * @return string HTML output.
     */
    public static function render_configurator_inline( $product, $instance_id = '' ) {
        if ( ! $product ) {
            return '';
        }

        // Get product profile
        global $wpdb;
        $base_product = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d LIMIT 1",
            $product->get_id()
        ), OBJECT );

        if ( ! $base_product ) {
            return '';
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );
        if ( ! $profile ) {
            return '';
        }

        // Generate unique instance ID if not provided
        if ( empty( $instance_id ) ) {
            $instance_id = 'pls-config-' . $product->get_id() . '-' . wp_generate_password( 6, false );
        }

        ob_start();
        ?>
        <div class="pls-configurator-inline" id="<?php echo esc_attr( $instance_id ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
            <?php self::render_full_configurator( $product, $profile ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the pack tier configurator.
     *
     * @param WC_Product $product The product.
     */
    private static function render_configurator( $product ) {
        $variation_attributes = $product->get_variation_attributes();
        $pack_tiers = isset( $variation_attributes['pa_pack-tier'] ) ? (array) $variation_attributes['pa_pack-tier'] : array();

        if ( empty( $pack_tiers ) ) {
            return;
        }

        ?>
        <div class="pls-auto-inject__section pls-auto-inject__configurator">
            <h2 class="pls-auto-inject__title"><?php esc_html_e( 'Select Your Pack Size', 'pls-private-label-store' ); ?></h2>
            <div class="pls-tier-cards">
                <?php foreach ( $pack_tiers as $tier_slug ) :
                    $tier_term = get_term_by( 'slug', $tier_slug, 'pa_pack-tier' );
                    if ( ! $tier_term ) {
                        continue;
                    }
                    
                    // Get tier data
                    $units = (int) get_term_meta( $tier_term->term_id, '_pls_default_units', true );
                    $tier_key = get_term_meta( $tier_term->term_id, '_pls_tier_key', true );
                    
                    // Get variation for this tier
                    $variation_id = self::get_variation_for_tier( $product->get_id(), $tier_slug );
                    $variation_price = '';
                    $price_per_unit = '';
                    
                    if ( $variation_id ) {
                        $variation = wc_get_product( $variation_id );
                        if ( $variation ) {
                            $variation_price = $variation->get_price();
                            if ( $units > 0 && $variation_price > 0 ) {
                                $price_per_unit = $variation_price / $units;
                            }
                        }
                    }
                    
                    // Determine tier level for badge styling
                    $tier_level = self::get_tier_level( $tier_key );
                    ?>
                    <div class="pls-tier-card" data-tier="<?php echo esc_attr( $tier_slug ); ?>" data-variation-id="<?php echo esc_attr( $variation_id ); ?>" data-units="<?php echo esc_attr( $units ); ?>" data-price-per-unit="<?php echo esc_attr( $price_per_unit ); ?>" data-total-price="<?php echo esc_attr( $variation_price ); ?>" data-tier-key="<?php echo esc_attr( $tier_key ); ?>">
                        <?php if ( $tier_level ) : ?>
                            <span class="pls-tier-card__badge pls-tier-card__badge--<?php echo esc_attr( $tier_level ); ?>">
                                <?php echo esc_html( self::get_tier_badge_label( $tier_level ) ); ?>
                            </span>
                        <?php endif; ?>
                        
                        <h3 class="pls-tier-card__title"><?php echo esc_html( $tier_term->name ); ?></h3>
                        
                        <?php if ( $units > 0 ) : ?>
                            <div class="pls-tier-card__units">
                                <span class="pls-tier-card__units-number"><?php echo number_format( $units ); ?></span>
                                <span class="pls-tier-card__units-label"><?php esc_html_e( 'units', 'pls-private-label-store' ); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $variation_price ) : ?>
                            <?php if ( $price_per_unit ) : ?>
                                <div class="pls-tier-card__price-per-unit" style="font-size: 1.5rem; font-weight: 700; color: var(--pls-primary); margin: 0.5rem 0;">
                                    <?php echo wc_price( $price_per_unit ); ?> <span style="font-size: 0.875rem; font-weight: 400; color: var(--pls-gray-600);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="pls-tier-card__price" style="font-size: 0.875rem; color: var(--pls-gray-600); margin-top: 0.25rem;">
                                <?php printf( esc_html__( 'Total: %s (%d units)', 'pls-private-label-store' ), wc_price( $variation_price ), $units ); ?>
                            </div>
                        <?php endif; ?>
                        
                        <button type="button" class="pls-tier-card__select button" data-tier="<?php echo esc_attr( $tier_slug ); ?>">
                            <?php esc_html_e( 'Select', 'pls-private-label-store' ); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render product description section.
     *
     * @param object $profile The product profile.
     */
    private static function render_description( $profile ) {
        ?>
        <div class="pls-auto-inject__section pls-auto-inject__description">
            <h2 class="pls-auto-inject__title"><?php esc_html_e( 'About This Product', 'pls-private-label-store' ); ?></h2>
            <div class="pls-auto-inject__content">
                <?php echo wp_kses_post( wpautop( $profile->long_description ) ); ?>
            </div>
            
            <?php if ( ! empty( $profile->directions ) ) : ?>
                <div class="pls-auto-inject__directions">
                    <h3><?php esc_html_e( 'Directions', 'pls-private-label-store' ); ?></h3>
                    <?php echo wp_kses_post( wpautop( $profile->directions ) ); ?>
                </div>
            <?php endif; ?>
            
            <?php if ( ! empty( $profile->benefits ) ) : ?>
                <div class="pls-auto-inject__benefits">
                    <h3><?php esc_html_e( 'Benefits', 'pls-private-label-store' ); ?></h3>
                    <?php echo wp_kses_post( wpautop( $profile->benefits ) ); ?>
                </div>
            <?php endif; ?>
            
            <?php if ( ! empty( $profile->skin_types ) ) : ?>
                <div class="pls-auto-inject__skin-types">
                    <h3><?php esc_html_e( 'Suitable for', 'pls-private-label-store' ); ?></h3>
                    <p><?php echo esc_html( $profile->skin_types ); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render ingredients section.
     *
     * @param object $profile The product profile.
     */
    private static function render_ingredients( $profile ) {
        $ingredient_ids = array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) );
        $key_ingredient_ids = array();
        
        // Get key ingredients
        if ( ! empty( $profile->key_ingredients_json ) ) {
            $key_data = json_decode( $profile->key_ingredients_json, true );
            if ( is_array( $key_data ) ) {
                foreach ( $key_data as $ki ) {
                    $term_id = isset( $ki['term_id'] ) ? absint( $ki['term_id'] ) : ( isset( $ki['id'] ) ? absint( $ki['id'] ) : 0 );
                    if ( $term_id ) {
                        $key_ingredient_ids[] = $term_id;
                    }
                }
            }
        }
        
        $all_ingredients = array();
        foreach ( $ingredient_ids as $term_id ) {
            $term = get_term( $term_id, 'pls_ingredient' );
            if ( $term && ! is_wp_error( $term ) ) {
                // Get short description from meta, fallback to term description
                $short_desc = get_term_meta( $term_id, 'pls_ingredient_short_desc', true );
                if ( empty( $short_desc ) ) {
                    $short_desc = $term->description;
                }
                $all_ingredients[] = array(
                    'id'     => $term_id,
                    'name'   => $term->name,
                    'desc'   => $short_desc,
                    'is_key' => in_array( $term_id, $key_ingredient_ids, true ),
                );
            }
        }
        
        if ( empty( $all_ingredients ) ) {
            return;
        }
        
        // Separate key ingredients
        $key_ingredients = array_filter( $all_ingredients, function( $i ) { return $i['is_key']; } );
        $other_ingredients = array_filter( $all_ingredients, function( $i ) { return ! $i['is_key']; } );
        ?>
        <div class="pls-auto-inject__section pls-auto-inject__ingredients">
            <h2 class="pls-auto-inject__title"><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></h2>
            
            <?php if ( ! empty( $key_ingredients ) ) : ?>
                <div class="pls-key-ingredients-section">
                    <h2 class="pls-section-title"><?php esc_html_e( 'Key Ingredients', 'pls-private-label-store' ); ?></h2>
                    <div class="pls-ingredients-key-grid">
                        <?php foreach ( $key_ingredients as $ingredient ) : 
                            // Get ingredient icon using the standard method
                            $ingredient_icon_url = PLS_Taxonomies::icon_for_term( $ingredient['id'] );
                            ?>
                            <div class="pls-ingredient-card pls-ingredient-card--key">
                                <?php if ( $ingredient_icon_url && $ingredient_icon_url !== PLS_Taxonomies::default_icon() ) : ?>
                                    <div class="pls-ingredient-image">
                                        <img src="<?php echo esc_url( $ingredient_icon_url ); ?>" alt="<?php echo esc_attr( $ingredient['name'] ); ?>" />
                                    </div>
                                <?php elseif ( $ingredient_icon_url === PLS_Taxonomies::default_icon() ) : ?>
                                    <div class="pls-ingredient-image pls-ingredient-image--placeholder">
                                        <img src="<?php echo esc_url( $ingredient_icon_url ); ?>" alt="<?php echo esc_attr( $ingredient['name'] ); ?>" />
                                    </div>
                                <?php else : ?>
                                    <div class="pls-ingredient-image pls-ingredient-image--placeholder">
                                        <span class="pls-ingredient-placeholder-icon">★</span>
                                    </div>
                                <?php endif; ?>
                                <div class="pls-ingredient-name"><?php echo esc_html( $ingredient['name'] ); ?></div>
                                <?php if ( ! empty( $ingredient['desc'] ) ) : ?>
                                    <p class="pls-ingredient-card__desc"><?php echo esc_html( $ingredient['desc'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php 
            // Show full ingredient list (all ingredients including key ones)
            if ( ! empty( $all_ingredients ) ) : 
                $all_names = array_map( function( $i ) { return $i['name']; }, $all_ingredients );
                ?>
                <div class="pls-ingredients-all">
                    <h3><?php esc_html_e( 'Full Ingredient List (INCI)', 'pls-private-label-store' ); ?></h3>
                    <div class="pls-ingredients-list pls-ingredients-inci">
                        <?php echo esc_html( implode( ', ', $all_names ) ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render bundle offers section.
     */
    private static function render_bundles() {
        $bundles = PLS_Repo_Bundle::all();
        $active_bundles = array_filter( $bundles, function( $b ) { return 'live' === $b->status; } );
        
        if ( empty( $active_bundles ) ) {
            return;
        }
        ?>
        <div class="pls-auto-inject__section pls-auto-inject__bundles">
            <h2 class="pls-auto-inject__title"><?php esc_html_e( 'Bundle & Save', 'pls-private-label-store' ); ?></h2>
            <div class="pls-bundle-cards">
                <?php foreach ( $active_bundles as $bundle ) :
                    $rules = ! empty( $bundle->offer_rules_json ) ? json_decode( $bundle->offer_rules_json, true ) : array();
                    if ( empty( $rules ) ) {
                        continue;
                    }
                    $sku_count = isset( $rules['sku_count'] ) ? (int) $rules['sku_count'] : 0;
                    $units_per_sku = isset( $rules['units_per_sku'] ) ? (int) $rules['units_per_sku'] : 0;
                    $price_per_unit = isset( $rules['price_per_unit'] ) ? floatval( $rules['price_per_unit'] ) : 0;
                    ?>
                    <div class="pls-bundle-card">
                        <h3 class="pls-bundle-card__title"><?php echo esc_html( $bundle->name ); ?></h3>
                        <div class="pls-bundle-card__details">
                            <p>
                                <?php
                                printf(
                                    esc_html__( '%d products × %d units each', 'pls-private-label-store' ),
                                    $sku_count,
                                    $units_per_sku
                                );
                                ?>
                            </p>
                            <p class="pls-bundle-card__total">
                                <?php
                                $total_units = $sku_count * $units_per_sku;
                                printf(
                                    esc_html__( 'Total: %s units', 'pls-private-label-store' ),
                                    number_format( $total_units )
                                );
                                ?>
                            </p>
                        </div>
                        <?php if ( $price_per_unit > 0 ) : ?>
                            <div class="pls-bundle-card__price">
                                <?php echo wc_price( $price_per_unit ); ?>
                                <span class="pls-bundle-card__price-label"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Add PLS tab to product tabs.
     *
     * @param array $tabs Existing tabs.
     * @return array Modified tabs.
     */
    public static function add_pls_tab( $tabs ) {
        global $product;

        if ( ! $product instanceof WC_Product ) {
            return $tabs;
        }

        // Check if PLS product
        global $wpdb;
        $base_product = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d LIMIT 1",
                $product->get_id()
            ),
            OBJECT
        );

        if ( ! $base_product ) {
            return $tabs;
        }

        $tabs['pls_info'] = array(
            'title'    => __( 'Pack Options', 'pls-private-label-store' ),
            'priority' => 15,
            'callback' => array( __CLASS__, 'render_pls_tab' ),
        );

        return $tabs;
    }

    /**
     * Render PLS tab content.
     */
    public static function render_pls_tab() {
        self::inject_pls_content();
    }

    /**
     * Maybe show tier badge on shop/category product cards.
     */
    public static function maybe_show_tier_badge() {
        $settings = self::get_settings();
        if ( ! $settings['show_tier_badges'] ) {
            return;
        }

        global $product;
        if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
            return;
        }

        // Check if PLS product
        global $wpdb;
        $base_product = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d LIMIT 1",
                $product->get_id()
            ),
            OBJECT
        );

        if ( ! $base_product ) {
            return;
        }

        // Get lowest tier available
        $variation_attributes = $product->get_variation_attributes();
        $pack_tiers = isset( $variation_attributes['pa_pack-tier'] ) ? (array) $variation_attributes['pa_pack-tier'] : array();

        if ( empty( $pack_tiers ) ) {
            return;
        }

        // Get first tier info
        $first_tier = reset( $pack_tiers );
        $tier_term = get_term_by( 'slug', $first_tier, 'pa_pack-tier' );
        
        if ( ! $tier_term ) {
            return;
        }

        $units = (int) get_term_meta( $tier_term->term_id, '_pls_default_units', true );
        
        // Enqueue CSS
        wp_enqueue_style( 'pls-frontend-display' );
        ?>
        <div class="pls-product-badge">
            <span class="pls-product-badge__label"><?php esc_html_e( 'From', 'pls-private-label-store' ); ?></span>
            <span class="pls-product-badge__units"><?php echo number_format( $units ); ?></span>
            <span class="pls-product-badge__suffix"><?php esc_html_e( 'units', 'pls-private-label-store' ); ?></span>
        </div>
        <?php
    }

    /**
     * Maybe show starting price on shop/category product cards.
     */
    public static function maybe_show_starting_price() {
        $settings = self::get_settings();
        if ( ! $settings['show_starting_price'] ) {
            return;
        }

        global $product;
        if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
            return;
        }

        // Check if PLS product
        global $wpdb;
        $base_product = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d LIMIT 1",
                $product->get_id()
            ),
            OBJECT
        );

        if ( ! $base_product ) {
            return;
        }

        // Get highest tier (best price per unit)
        $variation_attributes = $product->get_variation_attributes();
        $pack_tiers = isset( $variation_attributes['pa_pack-tier'] ) ? (array) $variation_attributes['pa_pack-tier'] : array();

        if ( empty( $pack_tiers ) ) {
            return;
        }

        // Find best price per unit
        $best_ppu = null;
        foreach ( $pack_tiers as $tier_slug ) {
            $tier_term = get_term_by( 'slug', $tier_slug, 'pa_pack-tier' );
            if ( ! $tier_term ) {
                continue;
            }
            
            $units = (int) get_term_meta( $tier_term->term_id, '_pls_default_units', true );
            $variation_id = self::get_variation_for_tier( $product->get_id(), $tier_slug );
            
            if ( $variation_id && $units > 0 ) {
                $variation = wc_get_product( $variation_id );
                if ( $variation ) {
                    $price = $variation->get_price();
                    $ppu = $price / $units;
                    if ( null === $best_ppu || $ppu < $best_ppu ) {
                        $best_ppu = $ppu;
                    }
                }
            }
        }

        if ( $best_ppu ) {
            wp_enqueue_style( 'pls-frontend-display' );
            ?>
            <div class="pls-starting-price">
                <span class="pls-starting-price__label"><?php esc_html_e( 'As low as', 'pls-private-label-store' ); ?></span>
                <span class="pls-starting-price__value"><?php echo wc_price( $best_ppu ); ?></span>
                <span class="pls-starting-price__suffix"><?php esc_html_e( '/unit', 'pls-private-label-store' ); ?></span>
            </div>
            <?php
        }
    }

    /**
     * Get variation ID for a specific tier.
     *
     * @param int    $product_id Product ID.
     * @param string $tier_slug  Tier slug.
     * @return int|false Variation ID or false.
     */
    private static function get_variation_for_tier( $product_id, $tier_slug ) {
        global $wpdb;
        
        // Find variation with this tier attribute
        $variation_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'product_variation'
             AND p.post_parent = %d
             AND pm.meta_key = 'attribute_pa_pack-tier'
             AND pm.meta_value = %s
             LIMIT 1",
            $product_id,
            $tier_slug
        ) );

        return $variation_id ? (int) $variation_id : false;
    }

    /**
     * Get tier level from tier key.
     *
     * @param string $tier_key Tier key like tier_1, tier_2, etc.
     * @return string|false Tier level or false.
     */
    private static function get_tier_level( $tier_key ) {
        if ( ! $tier_key ) {
            return false;
        }
        
        $levels = array(
            'tier_1' => 'trial',
            'tier_2' => 'starter',
            'tier_3' => 'brand',
            'tier_4' => 'growth',
            'tier_5' => 'wholesale',
        );
        
        return isset( $levels[ $tier_key ] ) ? $levels[ $tier_key ] : false;
    }

    /**
     * Get tier badge label.
     *
     * @param string $level Tier level.
     * @return string Badge label.
     */
    private static function get_tier_badge_label( $level ) {
        $labels = array(
            'trial'     => __( 'Trial', 'pls-private-label-store' ),
            'starter'   => __( 'Starter', 'pls-private-label-store' ),
            'brand'     => __( 'Popular', 'pls-private-label-store' ),
            'growth'    => __( 'Best Value', 'pls-private-label-store' ),
            'wholesale' => __( 'Pro', 'pls-private-label-store' ),
        );
        
        return isset( $labels[ $level ] ) ? $labels[ $level ] : '';
    }
}
