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
            'show_configurator' => true,
            'show_description'  => true,
            'show_ingredients'   => true,
            'show_bundles'       => true,
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
        echo '<div class="pls-auto-inject" id="pls-product-content">';

        // Configurator section (pack tier selector with visual cards)
        if ( $options['show_configurator'] && $product->is_type( 'variable' ) ) {
            self::render_configurator( $product );
        }

        // Product info section
        if ( $profile ) {
            if ( $options['show_description'] && ! empty( $profile->long_description ) ) {
                self::render_description( $profile );
            }

            if ( $options['show_ingredients'] && ! empty( $profile->ingredients_list ) ) {
                self::render_ingredients( $profile );
            }
        }

        // Bundle offers section
        if ( $options['show_bundles'] ) {
            self::render_bundles();
        }

        echo '</div>'; // .pls-auto-inject
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
                            <div class="pls-tier-card__price">
                                <?php echo wc_price( $variation_price ); ?>
                            </div>
                            <?php if ( $price_per_unit ) : ?>
                                <div class="pls-tier-card__price-per-unit">
                                    <?php echo wc_price( $price_per_unit ); ?> <?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?>
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
            
            <?php if ( ! empty( $other_ingredients ) ) : ?>
                <div class="pls-ingredients-all">
                    <h3><?php esc_html_e( 'Full Ingredient List', 'pls-private-label-store' ); ?></h3>
                    <div class="pls-ingredients-list">
                        <?php 
                        $names = array_map( function( $i ) { return $i['name']; }, $other_ingredients );
                        echo esc_html( implode( ', ', $names ) );
                        ?>
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
