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

        // Localize script with AJAX data
        wp_localize_script( 'pls-offers', 'plsOffers', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'pls_offers' ),
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

        // Product Header: Images + Short Description + CTA Button
        if ( $options['show_images'] && $profile ) {
            self::render_product_header( $product, $profile, $options );
        }

        // Configurator Modal (hidden by default, opened via button)
        if ( $options['show_configurator'] && $product->is_type( 'variable' ) ) {
            self::render_configurator_modal( $product, $profile );
        }

        // Product Information Sections (Tabs/Accordion)
        if ( $profile ) {
            if ( $options['show_description'] ) {
                self::render_product_info_sections( $product, $profile );
            }

            if ( $options['show_ingredients'] && ! empty( $profile->ingredients_list ) ) {
                self::render_ingredients( $profile );
            }
        }

        // Bundle offers section
        if ( $options['show_bundles'] ) {
            self::render_bundles();
        }

        echo '</div>'; // .pls-product-page
        return ob_get_clean();
    }

    /**
     * Render product header with images and short description.
     *
     * @param WC_Product $product The product.
     * @param object     $profile The product profile.
     * @param array      $options Display options.
     */
    private static function render_product_header( $product, $profile, $options = array() ) {
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
        
        // Get gallery images
        $gallery_images = array();
        foreach ( $gallery_ids as $gallery_id ) {
            $img = wp_get_attachment_image_src( $gallery_id, 'woocommerce_thumbnail' );
            if ( $img ) {
                $gallery_images[] = array(
                    'id'  => $gallery_id,
                    'url' => $img[0],
                    'full' => wp_get_attachment_image_src( $gallery_id, 'full' )[0] ?? $img[0],
                    'alt' => get_post_meta( $gallery_id, '_wp_attachment_image_alt', true ),
                );
            }
        }
        
        // If no featured but have gallery, use first gallery image
        if ( ! $featured_url && ! empty( $gallery_images ) ) {
            $featured_url = $gallery_images[0]['full'];
            $featured_alt = $gallery_images[0]['alt'];
        }
        
        ?>
        <div class="pls-product-header">
            <div class="pls-product-header__images">
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
                            <button type="button" 
                                    class="pls-gallery-thumb <?php echo $index === 0 && ! $featured_image_id ? 'is-active' : ''; ?>"
                                    data-image-id="<?php echo esc_attr( $gallery_img['id'] ); ?>"
                                    data-image-url="<?php echo esc_url( $gallery_img['full'] ); ?>"
                                    aria-label="<?php echo esc_attr( sprintf( __( 'View image %d', 'pls-private-label-store' ), $index + 1 ) ); ?>">
                                <img src="<?php echo esc_url( $gallery_img['url'] ); ?>" 
                                     alt="<?php echo esc_attr( $gallery_img['alt'] ?: $product->get_name() ); ?>" />
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="pls-product-header__info">
                <h1 class="pls-product-title"><?php echo esc_html( $product->get_name() ); ?></h1>
                <?php if ( ! empty( $profile->short_description ) ) : ?>
                    <div class="pls-product-short-description">
                        <?php echo wp_kses_post( wpautop( $profile->short_description ) ); ?>
                    </div>
                <?php endif; ?>
                
                <?php
                // Product Basics (Icons) - Display before price
                if ( ! empty( $profile->basics_json ) ) {
                    $basics_data = json_decode( $profile->basics_json, true );
                    if ( is_array( $basics_data ) && ! empty( $basics_data ) ) {
                        // Extract all selected values from basics
                        $basics_values = array();
                        foreach ( $basics_data as $attr ) {
                            if ( isset( $attr['values'] ) && is_array( $attr['values'] ) ) {
                                foreach ( $attr['values'] as $value ) {
                                    if ( isset( $value['label'] ) && ! empty( $value['label'] ) ) {
                                        $basics_values[] = $value;
                                    }
                                }
                            }
                        }
                        
                        if ( ! empty( $basics_values ) ) {
                            ?>
                            <div class="pls-product-basics">
                                <?php foreach ( $basics_values as $basic ) : 
                                    $label = isset( $basic['label'] ) ? $basic['label'] : '';
                                    $icon_url = isset( $basic['icon'] ) && ! empty( $basic['icon'] ) ? $basic['icon'] : '';
                                    ?>
                                    <div class="pls-basic-icon">
                                        <?php if ( $icon_url ) : ?>
                                            <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $label ); ?>" />
                                        <?php else : ?>
                                            <span class="pls-basic-icon-placeholder">✓</span>
                                        <?php endif; ?>
                                        <span class="pls-basic-label"><?php echo esc_html( $label ); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php
                        }
                    }
                }
                
                // Show starting price PER UNIT for variable products
                if ( $product->is_type( 'variable' ) ) {
                    $variation_attributes = $product->get_variation_attributes();
                    $pack_tiers = isset( $variation_attributes['pa_pack-tier'] ) ? (array) $variation_attributes['pa_pack-tier'] : array();
                    if ( ! empty( $pack_tiers ) ) {
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
                                <span style="font-size: 0.875rem; font-weight: 400; color: var(--pls-gray-600);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                            </div>
                            <?php
                        }
                    }
                } else {
                    ?>
                    <div class="pls-product-price">
                        <?php echo $product->get_price_html(); ?>
                    </div>
                    <?php
                }
                ?>
                
                <!-- CTA Button: Configure & Order -->
                <?php if ( $product->is_type( 'variable' ) ) : ?>
                    <div class="pls-product-cta">
                        <button type="button" class="pls-configure-button" id="pls-open-configurator">
                            <?php esc_html_e( 'Configure & Order', 'pls-private-label-store' ); ?>
                        </button>
                        <div class="pls-product-trust-signals">
                            <span class="pls-trust-badge">✓ Free Shipping</span>
                            <span class="pls-trust-badge">✓ Secure Checkout</span>
                            <span class="pls-trust-badge">✓ Fast Delivery</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render configurator as modal overlay.
     *
     * @param WC_Product $product The product.
     * @param object     $profile The product profile.
     */
    private static function render_configurator_modal( $product, $profile ) {
        if ( ! $product || ! $profile ) {
            return;
        }
        ?>
        <div class="pls-configurator-modal" id="pls-configurator-modal">
            <div class="pls-configurator-modal__overlay"></div>
            <div class="pls-configurator-modal__content">
                <button type="button" class="pls-configurator-modal__close" aria-label="<?php esc_attr_e( 'Close', 'pls-private-label-store' ); ?>">×</button>
                <?php self::render_full_configurator( $product, $profile ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render full configurator with options and add-to-cart.
     *
     * @param WC_Product $product The product.
     * @param object     $profile The product profile.
     */
    private static function render_full_configurator( $product, $profile ) {
        $variation_attributes = $product->get_variation_attributes();
        $pack_tiers = isset( $variation_attributes['pa_pack-tier'] ) ? (array) $variation_attributes['pa_pack-tier'] : array();

        if ( empty( $pack_tiers ) ) {
            return;
        }

        // Parse basics_json for product options
        $product_options = array();
        if ( ! empty( $profile->basics_json ) ) {
            $basics_data = json_decode( $profile->basics_json, true );
            if ( is_array( $basics_data ) ) {
                foreach ( $basics_data as $attr ) {
                    if ( isset( $attr['attribute_label'] ) && isset( $attr['values'] ) && is_array( $attr['values'] ) ) {
                        $product_options[] = $attr;
                    }
                }
            }
        }

        ?>
        <div class="pls-configurator-section">
            <h2 class="pls-configurator-title"><?php esc_html_e( 'Configure Your Order', 'pls-private-label-store' ); ?></h2>
            <p class="pls-configurator-subtitle"><?php esc_html_e( 'Select your pack size and customize your order', 'pls-private-label-store' ); ?></p>
            
            <!-- Pack Tier Selection -->
            <div class="pls-configurator-block">
                <h3 class="pls-configurator-block__title"><?php esc_html_e( 'Select Your Pack Size', 'pls-private-label-store' ); ?></h3>
                <div class="pls-tier-cards">
                    <?php foreach ( $pack_tiers as $tier_slug ) :
                        $tier_term = get_term_by( 'slug', $tier_slug, 'pa_pack-tier' );
                        if ( ! $tier_term ) {
                            continue;
                        }
                        
                        $units = (int) get_term_meta( $tier_term->term_id, '_pls_default_units', true );
                        $tier_key = get_term_meta( $tier_term->term_id, '_pls_tier_key', true );
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
                        
                        $tier_level = self::get_tier_level( $tier_key );
                        ?>
                        <div class="pls-tier-card" 
                             data-tier="<?php echo esc_attr( $tier_slug ); ?>" 
                             data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
                             data-units="<?php echo esc_attr( $units ); ?>"
                             data-price-per-unit="<?php echo esc_attr( $price_per_unit ); ?>"
                             data-total-price="<?php echo esc_attr( $variation_price ); ?>">
                            <?php if ( $tier_level ) : ?>
                                <span class="pls-tier-card__badge pls-tier-card__badge--<?php echo esc_attr( $tier_level ); ?>">
                                    <?php echo esc_html( self::get_tier_badge_label( $tier_level ) ); ?>
                                </span>
                            <?php endif; ?>
                            
                            <h4 class="pls-tier-card__title"><?php echo esc_html( $tier_term->name ); ?></h4>
                            
                            <?php if ( $units > 0 ) : ?>
                                <div class="pls-tier-card__units">
                                    <span class="pls-tier-card__units-number"><?php echo number_format( $units ); ?></span>
                                    <span class="pls-tier-card__units-label"><?php esc_html_e( 'units', 'pls-private-label-store' ); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( $price_per_unit ) : ?>
                                <div class="pls-tier-card__price-per-unit" style="font-size: 1.5rem; font-weight: 600; color: var(--pls-primary); margin: 0.5rem 0;">
                                    <?php echo wc_price( $price_per_unit ); ?> <span style="font-size: 0.875rem; font-weight: 400; color: var(--pls-gray-600);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                </div>
                                <?php if ( $variation_price ) : ?>
                                    <div class="pls-tier-card__price" style="font-size: 0.875rem; color: var(--pls-gray-600);">
                                        <?php esc_html_e( 'Total:', 'pls-private-label-store' ); ?> <?php echo wc_price( $variation_price ); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <button type="button" class="pls-tier-card__select button" data-tier="<?php echo esc_attr( $tier_slug ); ?>">
                                <?php esc_html_e( 'Select', 'pls-private-label-store' ); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Options -->
            <?php if ( ! empty( $product_options ) ) : ?>
                <div class="pls-configurator-block pls-product-options">
                    <h3 class="pls-configurator-block__title"><?php esc_html_e( 'Package Options', 'pls-private-label-store' ); ?></h3>
                    <div class="pls-product-options-list">
                        <?php foreach ( $product_options as $option ) : 
                            $attr_label = isset( $option['attribute_label'] ) ? $option['attribute_label'] : '';
                            $values = isset( $option['values'] ) && is_array( $option['values'] ) ? $option['values'] : array();
                            
                            // Skip pack-tier (already shown) and label application (handled separately)
                            if ( stripos( $attr_label, 'pack tier' ) !== false || stripos( $attr_label, 'label application' ) !== false ) {
                                continue;
                            }
                            
                            if ( empty( $values ) ) {
                                continue;
                            }
                            ?>
                            <div class="pls-product-option-group" data-attribute-label="<?php echo esc_attr( $attr_label ); ?>">
                                <label class="pls-product-option-label"><?php echo esc_html( $attr_label ); ?></label>
                                <div class="pls-product-option-values">
                                    <?php foreach ( $values as $value ) : 
                                        $value_id = isset( $value['id'] ) ? absint( $value['id'] ) : 0;
                                        $value_label = isset( $value['label'] ) ? $value['label'] : '';
                                        $value_price = isset( $value['price'] ) ? floatval( $value['price'] ) : 0;
                                        $tier_overrides = isset( $value['tier_price_overrides'] ) && is_array( $value['tier_price_overrides'] ) ? $value['tier_price_overrides'] : null;
                                        
                                        // Check if this is a "standard" option (usually free)
                                        $is_standard = ( stripos( strtolower( $value_label ), 'standard' ) !== false || 
                                                         stripos( strtolower( $value_label ), 'clear' ) !== false ||
                                                         stripos( strtolower( $value_label ), 'black' ) !== false );
                                        ?>
                                        <label class="pls-option-value-card <?php echo $is_standard ? 'is-standard' : ''; ?>">
                                            <input type="radio" 
                                                   name="pls_option_<?php echo esc_attr( sanitize_title( $attr_label ) ); ?>" 
                                                   value="<?php echo esc_attr( $value_id ); ?>"
                                                   data-value-id="<?php echo esc_attr( $value_id ); ?>"
                                                   data-price="<?php echo esc_attr( $value_price ); ?>"
                                                   data-tier-prices="<?php echo esc_attr( $tier_overrides ? wp_json_encode( $tier_overrides ) : '' ); ?>"
                                                   <?php echo $is_standard ? 'checked' : ''; ?> />
                                            <span class="pls-option-value-label"><?php echo esc_html( $value_label ); ?></span>
                                            <?php if ( ! $is_standard && ( $value_price > 0 || $tier_overrides ) ) : ?>
                                                <span class="pls-option-price-badge">
                                                    <?php
                                                    if ( $tier_overrides && isset( $tier_overrides[1] ) ) {
                                                        echo '+$' . number_format( floatval( $tier_overrides[1] ), 2 ) . ' ' . esc_html__( '(Tier 1)', 'pls-private-label-store' );
                                                    } elseif ( $value_price > 0 ) {
                                                        echo '+$' . number_format( $value_price, 2 );
                                                    }
                                                    ?>
                                                </span>
                                            <?php else : ?>
                                                <span class="pls-option-price-badge"><?php esc_html_e( 'Included', 'pls-private-label-store' ); ?></span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Price Summary -->
            <div class="pls-configurator-block pls-price-summary">
                <h3 class="pls-configurator-block__title"><?php esc_html_e( 'Price Summary', 'pls-private-label-store' ); ?></h3>
                <div class="pls-price-breakdown">
                    <div class="pls-price-row">
                        <span class="pls-price-label"><?php esc_html_e( 'Base Price (total)', 'pls-private-label-store' ); ?></span>
                        <span class="pls-price-value" id="pls-price-base"><?php echo wc_price( 0 ); ?></span>
                    </div>
                    <div class="pls-price-row" id="pls-price-options-row" style="display: none;">
                        <span class="pls-price-label"><?php esc_html_e( 'Options (total)', 'pls-private-label-store' ); ?></span>
                        <span class="pls-price-value" id="pls-price-options"><?php echo wc_price( 0 ); ?></span>
                    </div>
                    <div class="pls-price-row pls-price-row--total">
                        <span class="pls-price-label"><?php esc_html_e( 'Order Total', 'pls-private-label-store' ); ?></span>
                        <span class="pls-price-value" id="pls-price-total"><?php echo wc_price( 0 ); ?></span>
                    </div>
                    <div class="pls-price-row pls-price-row--per-unit" style="background: var(--pls-gray-50); padding: 0.75rem; border-radius: var(--pls-radius-sm); margin-top: 0.5rem;">
                        <span class="pls-price-label"><strong><?php esc_html_e( 'Price Per Unit', 'pls-private-label-store' ); ?></strong></span>
                        <span class="pls-price-value" id="pls-price-per-unit" style="font-size: 1.25rem; color: var(--pls-primary);"><strong><?php echo wc_price( 0 ); ?></strong></span>
                    </div>
                </div>
            </div>

            <!-- Add to Cart Form -->
            <div class="pls-configurator-block pls-add-to-cart-block">
                <form class="pls-cart-form variations_form cart" method="post" enctype="multipart/form-data" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>">
                    <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>">
                    <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>">
                    <input type="hidden" name="variation_id" value="" class="pls-variation-id" />
                    
                    <!-- Hidden quantity field (always 1 pack) -->
                    <input type="hidden" name="quantity" value="1" />
                    
                    <!-- Units Display -->
                    <div class="pls-units-display" id="pls-units-display" style="margin-bottom: 1.5rem; padding: 1rem; background: var(--pls-gray-50); border-radius: var(--pls-radius-sm); text-align: center;">
                        <div style="font-size: 0.875rem; color: var(--pls-gray-600); margin-bottom: 0.5rem;">
                            <?php esc_html_e( 'Pack includes', 'pls-private-label-store' ); ?>
                        </div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: var(--pls-primary);">
                            <span id="pls-selected-units">0</span> <span style="font-size: 1rem; font-weight: 400; color: var(--pls-gray-700);"><?php esc_html_e( 'units', 'pls-private-label-store' ); ?></span>
                        </div>
                    </div>
                    
                    <!-- Add to Cart Button -->
                    <button type="submit" 
                            class="pls-add-to-cart-button button button-primary button-large" 
                            disabled>
                        <span class="pls-add-to-cart-text"><?php esc_html_e( 'Select a pack size', 'pls-private-label-store' ); ?></span>
                    </button>
                    
                    <div class="pls-cart-messages" id="pls-cart-messages"></div>
                </form>
            </div>
        </div>
        <?php
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
        $has_benefits = ! empty( $profile->benefits_json );
        
        if ( ! $has_description && ! $has_directions && ! $has_skin_types && ! $has_benefits ) {
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
                
                <?php if ( $has_skin_types || $has_benefits ) : ?>
                    <div class="pls-tab-panel" data-tab="info">
                        <?php if ( $has_skin_types ) : 
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
                        <?php endif; ?>
                        
                        <?php if ( $has_benefits ) : 
                            $benefits_data = json_decode( $profile->benefits_json, true );
                            if ( is_array( $benefits_data ) && ! empty( $benefits_data ) ) :
                                ?>
                                <div class="pls-product-benefits">
                                    <h3><?php esc_html_e( 'The Benefits', 'pls-private-label-store' ); ?></h3>
                                    <div class="pls-benefits-grid">
                                        <?php foreach ( $benefits_data as $benefit ) : 
                                            $label = is_array( $benefit ) && isset( $benefit['label'] ) ? $benefit['label'] : $benefit;
                                            ?>
                                            <div class="pls-benefit-card">
                                                <span class="pls-benefit-icon">✓</span>
                                                <span class="pls-benefit-label"><?php echo esc_html( $label ); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
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
                    <div class="pls-tier-card" data-tier="<?php echo esc_attr( $tier_slug ); ?>" data-variation-id="<?php echo esc_attr( $variation_id ); ?>">
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
                $all_ingredients[] = array(
                    'id'     => $term_id,
                    'name'   => $term->name,
                    'desc'   => $term->description,
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
                <div class="pls-ingredients-key">
                    <h3><?php esc_html_e( 'Key Ingredients', 'pls-private-label-store' ); ?></h3>
                    <div class="pls-ingredients-key__grid">
                        <?php foreach ( $key_ingredients as $ingredient ) : ?>
                            <div class="pls-ingredient-card pls-ingredient-card--key">
                                <span class="pls-ingredient-card__badge">★</span>
                                <span class="pls-ingredient-card__name"><?php echo esc_html( $ingredient['name'] ); ?></span>
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
