<?php
/**
 * Shortcode handlers for PLS elements.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Shortcodes {

    /**
     * Initialize shortcodes.
     * Only full-page shortcodes are enabled - use these in Elementor templates.
     */
    public static function init() {
        // Old shortcodes removed - use full-page shortcodes instead
        // Removed: pls_product, pls_configurator, pls_bundle, pls_product_page
        
        // Full-page shortcodes for Elementor templates (these render complete pages)
        add_shortcode( 'pls_single_product', array( __CLASS__, 'single_product_shortcode' ) );
        add_shortcode( 'pls_single_category', array( __CLASS__, 'single_category_shortcode' ) );
        add_shortcode( 'pls_shop_page', array( __CLASS__, 'shop_page_shortcode' ) );
        
        // Category archive shortcode with full data and product loop (v5.5.0)
        add_shortcode( 'pls_category_archive', array( __CLASS__, 'category_archive_shortcode' ) );
        
        // Inline configurator shortcode (v4.9.99 feature)
        add_shortcode( 'pls_configurator', array( __CLASS__, 'configurator_shortcode' ) );
        add_shortcode( 'pls_configurator_inline', array( __CLASS__, 'configurator_shortcode' ) );
        
        // Preview endpoint for admin preview
        add_action( 'template_redirect', array( __CLASS__, 'handle_preview_request' ) );
    }

    /**
     * Handle preview requests from admin.
     */
    public static function handle_preview_request() {
        // Check if this is a preview request
        if ( ! isset( $_GET['pls_preview'] ) ) {
            return;
        }

        // Handle category preview
        if ( isset( $_GET['category_id'] ) ) {
            self::render_category_preview( absint( $_GET['category_id'] ) );
            return;
        }

        // Handle product preview
        if ( ! isset( $_GET['product_id'] ) ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_GET['pls_preview_nonce'] ) || ! wp_verify_nonce( $_GET['pls_preview_nonce'], 'pls_admin_nonce' ) ) {
            wp_die( __( 'Invalid preview request.', 'pls-private-label-store' ) );
        }

        // Check permissions
        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_die( __( 'Insufficient permissions.', 'pls-private-label-store' ) );
        }

        $product_id = absint( $_GET['product_id'] );
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            wp_die( __( 'Product not found.', 'pls-private-label-store' ) );
        }

        // Set up global $product
        global $wp_query;
        $wp_query->is_product = true;
        $wp_query->is_singular = true;

        // Render preview
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( sprintf( __( 'Preview: %s', 'pls-private-label-store' ), $product->get_name() ) ); ?></title>
            <?php wp_head(); ?>
        </head>
        <body>
            <div style="padding: 20px; background: #f0f0f1; min-height: 100vh;">
                <div style="max-width: 1200px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px;">
                    <?php
                    // Render using new simplified shortcode
                    echo do_shortcode( '[pls_single_product product_id="' . $product_id . '"]' );
                    ?>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Render category preview.
     */
    private static function render_category_preview( $category_id ) {
        // Verify nonce
        if ( ! isset( $_GET['pls_preview_nonce'] ) || ! wp_verify_nonce( $_GET['pls_preview_nonce'], 'pls_admin_nonce' ) ) {
            wp_die( __( 'Invalid preview request.', 'pls-private-label-store' ) );
        }

        // Check permissions
        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_die( __( 'Insufficient permissions.', 'pls-private-label-store' ) );
        }

        $category = get_term( $category_id, 'product_cat' );
        if ( ! $category || is_wp_error( $category ) ) {
            wp_die( __( 'Category not found.', 'pls-private-label-store' ) );
        }

        // Set up query for category page
        global $wp_query;
        $wp_query->is_product_category = true;
        $wp_query->is_archive = true;
        $wp_query->queried_object = $category;
        $wp_query->queried_object_id = $category_id;

        // Render preview
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( sprintf( __( 'Preview: %s', 'pls-private-label-store' ), $category->name ) ); ?></title>
            <?php wp_head(); ?>
        </head>
        <body>
            <div style="padding: 20px; background: #f0f0f1; min-height: 100vh;">
                <div style="max-width: 1200px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px;">
                    <h1><?php echo esc_html( $category->name ); ?></h1>
                    <?php if ( ! empty( $category->description ) ) : ?>
                        <div class="category-description">
                            <?php echo wp_kses_post( wpautop( $category->description ) ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Use new category shortcode for preview
                    echo do_shortcode( '[pls_single_category]' );
                    ?>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Render shop page preview.
     */
    private static function render_shop_preview() {
        // Verify nonce
        if ( ! isset( $_GET['pls_preview_nonce'] ) || ! wp_verify_nonce( $_GET['pls_preview_nonce'], 'pls_admin_nonce' ) ) {
            wp_die( __( 'Invalid preview request.', 'pls-private-label-store' ) );
        }

        // Check permissions
        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_die( __( 'Insufficient permissions.', 'pls-private-label-store' ) );
        }

        // Set up query for shop page
        global $wp_query;
        $wp_query->is_shop = true;
        $wp_query->is_archive = true;
        $wp_query->is_post_type_archive = true;
        $wp_query->post_type = 'product';

        // Render preview
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php esc_html_e( 'Preview: Shop Page', 'pls-private-label-store' ); ?></title>
            <?php wp_head(); ?>
        </head>
        <body>
            <div style="padding: 20px; background: #f0f0f1; min-height: 100vh;">
                <div style="max-width: 1200px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px;">
                    <h1><?php esc_html_e( 'Shop', 'pls-private-label-store' ); ?></h1>
                    <?php
                    // Render shop page using shortcode
                    echo do_shortcode( '[pls_shop_page]' );
                    ?>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Product info shortcode.
     * 
     * Usage: [pls_product product_id="123" show_description="yes" show_ingredients="yes"]
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function product_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'product_id'      => 0,
                'show_description' => 'yes',
                'show_ingredients' => 'yes',
            ),
            $atts,
            'pls_product'
        );

        $product_id = absint( $atts['product_id'] );
        if ( ! $product_id ) {
            // Try to get from current WooCommerce product
            global $product;
            if ( $product instanceof WC_Product ) {
                $product_id = $product->get_id();
            }
        }

        if ( ! $product_id ) {
            return '<div class="pls-note">' . esc_html__( 'Product ID required.', 'pls-private-label-store' ) . '</div>';
        }

        // Get PLS product data by WooCommerce product ID
        global $wpdb;
        $table = $wpdb->prefix . 'pls_base_product';
        $base_product = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE wc_product_id = %d LIMIT 1", $product_id ),
            OBJECT
        );
        if ( ! $base_product ) {
            return '<div class="pls-note">' . esc_html__( 'PLS product not found.', 'pls-private-label-store' ) . '</div>';
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );
        if ( ! $profile ) {
            return '<div class="pls-note">' . esc_html__( 'Product profile not found.', 'pls-private-label-store' ) . '</div>';
        }

        ob_start();
        ?>
        <div class="pls-product-info">
            <?php if ( 'yes' === $atts['show_description'] && ! empty( $profile->long_description ) ) : ?>
                <div class="pls-product-info__description">
                    <?php echo wp_kses_post( wpautop( $profile->long_description ) ); ?>
                </div>
            <?php endif; ?>

            <?php if ( 'yes' === $atts['show_ingredients'] && ! empty( $profile->ingredients_list ) ) : ?>
                <?php
                // Get all ingredient term IDs
                $ingredient_ids = array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) );
                $all_ingredients = array();
                $key_ingredient_ids = array();
                
                // Get key ingredients if available
                if ( ! empty( $profile->key_ingredients_json ) ) {
                    $key_ingredients_data = json_decode( $profile->key_ingredients_json, true );
                    if ( is_array( $key_ingredients_data ) ) {
                        foreach ( $key_ingredients_data as $key_ing ) {
                            $term_id = isset( $key_ing['term_id'] ) ? absint( $key_ing['term_id'] ) : ( isset( $key_ing['id'] ) ? absint( $key_ing['id'] ) : 0 );
                            if ( $term_id ) {
                                $key_ingredient_ids[] = $term_id;
                            }
                        }
                    }
                }
                
                // Get ingredient terms
                foreach ( $ingredient_ids as $term_id ) {
                    $term = get_term( $term_id, 'pls_ingredient' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $all_ingredients[] = array(
                            'id' => $term_id,
                            'name' => $term->name,
                            'is_key' => in_array( $term_id, $key_ingredient_ids, true ),
                        );
                    }
                }
                ?>
                <?php if ( ! empty( $all_ingredients ) ) : ?>
                    <div class="pls-product-info__ingredients">
                        <h3><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></h3>
                        <div class="pls-ingredients-list">
                            <?php foreach ( $all_ingredients as $ingredient ) : ?>
                                <span class="pls-ingredient-item<?php echo $ingredient['is_key'] ? ' pls-ingredient-item--key' : ''; ?>" title="<?php echo $ingredient['is_key'] ? esc_attr__( 'Key Ingredient', 'pls-private-label-store' ) : ''; ?>">
                                    <?php echo esc_html( $ingredient['name'] ); ?>
                                    <?php if ( $ingredient['is_key'] ) : ?>
                                        <span class="pls-key-badge" title="<?php esc_attr_e( 'Key Ingredient', 'pls-private-label-store' ); ?>">★</span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Inline configurator shortcode (v4.9.99 feature).
     * Usage: [pls_configurator product_id="123"] or [pls_configurator_inline product_id="123"]
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function configurator_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'product_id' => 0,
            'instance_id' => '',
        ), $atts, 'pls_configurator' );

        $product_id = absint( $atts['product_id'] );
        
        // If no product_id provided, try to detect from current product page
        if ( ! $product_id && is_product() ) {
            global $product;
            if ( $product ) {
                $product_id = $product->get_id();
            }
        }

        if ( ! $product_id ) {
            return '<p class="pls-error">' . esc_html__( 'Product ID required. Usage: [pls_configurator product_id="123"]', 'pls-private-label-store' ) . '</p>';
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return '<p class="pls-error">' . esc_html__( 'Product not found.', 'pls-private-label-store' ) . '</p>';
        }

        // Ensure frontend display assets are loaded
        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-frontend-display.php';
        PLS_Frontend_Display::register_assets();
        wp_enqueue_style( 'pls-frontend-display' );
        wp_enqueue_script( 'pls-offers' );

        // Render inline configurator
        return PLS_Frontend_Display::render_configurator_inline( $product, $atts['instance_id'] );
    }

    /**
     * Bundle offer shortcode.
     * 
     * Usage: [pls_bundle bundle_id="123"]
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function bundle_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'bundle_id' => 0,
            ),
            $atts,
            'pls_bundle'
        );

        $bundle_id = absint( $atts['bundle_id'] );
        if ( ! $bundle_id ) {
            return '<div class="pls-note">' . esc_html__( 'Bundle ID required.', 'pls-private-label-store' ) . '</div>';
        }

        $bundle = PLS_Repo_Bundle::get( $bundle_id );
        if ( ! $bundle ) {
            return '<div class="pls-note">' . esc_html__( 'Bundle not found.', 'pls-private-label-store' ) . '</div>';
        }

        // Enqueue required scripts/styles
        wp_enqueue_style( 'pls-offers' );
        wp_enqueue_script( 'pls-offers' );

        $bundle_rules = ! empty( $bundle->offer_rules_json ) ? json_decode( $bundle->offer_rules_json, true ) : array();

        ob_start();
        ?>
        <div class="pls-bundle-offer" data-bundle-id="<?php echo esc_attr( $bundle_id ); ?>">
            <h3><?php echo esc_html( $bundle->name ); ?></h3>
            <?php if ( ! empty( $bundle_rules ) ) : ?>
                <div class="pls-bundle-offer__details">
                    <p><?php echo esc_html__( 'SKU Count:', 'pls-private-label-store' ); ?> <?php echo esc_html( $bundle_rules['sku_count'] ?? 0 ); ?></p>
                    <p><?php echo esc_html__( 'Units per SKU:', 'pls-private-label-store' ); ?> <?php echo esc_html( $bundle_rules['units_per_sku'] ?? 0 ); ?></p>
                    <p><?php echo esc_html__( 'Price per unit:', 'pls-private-label-store' ); ?> <?php echo wc_price( $bundle_rules['price_per_unit'] ?? 0 ); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Comprehensive product page shortcode.
     * 
     * Usage: [pls_product_page product_id="123"]
     * 
     * Combines configurator, product info, and bundle offers in one shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function product_page_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'product_id' => 0,
            ),
            $atts,
            'pls_product_page'
        );

        $product_id = absint( $atts['product_id'] );
        if ( ! $product_id ) {
            // Try to get from current WooCommerce product
            global $product;
            if ( $product instanceof WC_Product ) {
                $product_id = $product->get_id();
            }
        }

        if ( ! $product_id ) {
            return '<div class="pls-note">' . esc_html__( 'Product ID required.', 'pls-private-label-store' ) . '</div>';
        }

        // Get PLS product data
        global $wpdb;
        $table = $wpdb->prefix . 'pls_base_product';
        $base_product = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE wc_product_id = %d LIMIT 1", $product_id ),
            OBJECT
        );
        
        if ( ! $base_product ) {
            // Debug logging if enabled
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::warn( 'Product page shortcode: PLS product not found for WooCommerce product', array(
                    'wc_product_id' => $product_id,
                ) );
            }
            return '<div class="pls-note">' . esc_html__( 'PLS product not found. This product may need to be synced from PLS admin.', 'pls-private-label-store' ) . '</div>';
        }

        $wc_product = wc_get_product( $product_id );
        if ( ! $wc_product ) {
            return '<div class="pls-note">' . esc_html__( 'WooCommerce product not found.', 'pls-private-label-store' ) . '</div>';
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );
        
        // Enqueue required scripts/styles
        wp_enqueue_style( 'pls-offers' );
        wp_enqueue_script( 'pls-offers' );

        // Get applicable bundles for this product
        $bundles = PLS_Repo_Bundle::all();
        $applicable_bundles = array();
        foreach ( $bundles as $bundle ) {
            if ( 'live' === $bundle->status ) {
                $applicable_bundles[] = $bundle;
            }
        }

        ob_start();
        ?>
        <div class="pls-product-page" data-product-id="<?php echo esc_attr( $product_id ); ?>">
            
            <!-- Configurator Section -->
            <?php if ( $wc_product->is_type( 'variable' ) ) : ?>
                <div class="pls-product-page__configurator">
                    <?php echo self::configurator_shortcode( array( 'product_id' => $product_id ) ); ?>
                </div>
            <?php endif; ?>

            <!-- Product Info Section -->
            <div class="pls-product-page__info">
                <?php if ( $profile ) : ?>
                    <?php if ( ! empty( $profile->long_description ) ) : ?>
                        <div class="pls-product-info__description">
                            <h2><?php esc_html_e( 'Product Description', 'pls-private-label-store' ); ?></h2>
                            <?php echo wp_kses_post( wpautop( $profile->long_description ) ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $profile->directions ) ) : ?>
                        <div class="pls-product-info__directions">
                            <h3><?php esc_html_e( 'Directions', 'pls-private-label-store' ); ?></h3>
                            <?php echo wp_kses_post( wpautop( $profile->directions ) ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $profile->skin_types ) ) : ?>
                        <div class="pls-product-info__skin-types">
                            <h3><?php esc_html_e( 'Suitable for Skin Types', 'pls-private-label-store' ); ?></h3>
                            <p><?php echo esc_html( $profile->skin_types ); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $profile->benefits ) ) : ?>
                        <div class="pls-product-info__benefits">
                            <h3><?php esc_html_e( 'Benefits', 'pls-private-label-store' ); ?></h3>
                            <?php echo wp_kses_post( wpautop( $profile->benefits ) ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $profile->ingredients_list ) ) : ?>
                        <?php
                        // Get all ingredient term IDs
                        $ingredient_ids = array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) );
                        $all_ingredients = array();
                        $key_ingredient_ids = array();
                        
                        // Get key ingredients if available
                        if ( ! empty( $profile->key_ingredients_json ) ) {
                            $key_ingredients_data = json_decode( $profile->key_ingredients_json, true );
                            if ( is_array( $key_ingredients_data ) ) {
                                foreach ( $key_ingredients_data as $key_ing ) {
                                    $term_id = isset( $key_ing['term_id'] ) ? absint( $key_ing['term_id'] ) : ( isset( $key_ing['id'] ) ? absint( $key_ing['id'] ) : 0 );
                                    if ( $term_id ) {
                                        $key_ingredient_ids[] = $term_id;
                                    }
                                }
                            }
                        }
                        
                        // Get ingredient terms
                        foreach ( $ingredient_ids as $term_id ) {
                            $term = get_term( $term_id, 'pls_ingredient' );
                            if ( $term && ! is_wp_error( $term ) ) {
                                $all_ingredients[] = array(
                                    'id' => $term_id,
                                    'name' => $term->name,
                                    'is_key' => in_array( $term_id, $key_ingredient_ids, true ),
                                );
                            }
                        }
                        ?>
                        <?php if ( ! empty( $all_ingredients ) ) : ?>
                            <div class="pls-product-info__ingredients">
                                <h3><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></h3>
                                <div class="pls-ingredients-list">
                                    <?php foreach ( $all_ingredients as $ingredient ) : ?>
                                        <span class="pls-ingredient-item<?php echo $ingredient['is_key'] ? ' pls-ingredient-item--key' : ''; ?>" title="<?php echo $ingredient['is_key'] ? esc_attr__( 'Key Ingredient', 'pls-private-label-store' ) : ''; ?>">
                                            <?php echo esc_html( $ingredient['name'] ); ?>
                                            <?php if ( $ingredient['is_key'] ) : ?>
                                                <span class="pls-key-badge" title="<?php esc_attr_e( 'Key Ingredient', 'pls-private-label-store' ); ?>">★</span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Bundle Offers Section -->
            <?php if ( ! empty( $applicable_bundles ) ) : ?>
                <div class="pls-product-page__bundles">
                    <h2><?php esc_html_e( 'Frequently Bought Together', 'pls-private-label-store' ); ?></h2>
                    <div class="pls-bundle-banner">
                        <?php foreach ( $applicable_bundles as $bundle ) : ?>
                            <?php
                            $bundle_rules = ! empty( $bundle->offer_rules_json ) ? json_decode( $bundle->offer_rules_json, true ) : array();
                            if ( empty( $bundle_rules ) ) {
                                continue;
                            }
                            $required_sku_count = isset( $bundle_rules['sku_count'] ) ? (int) $bundle_rules['sku_count'] : 0;
                            $required_units_per_sku = isset( $bundle_rules['units_per_sku'] ) ? (int) $bundle_rules['units_per_sku'] : 0;
                            $bundle_price_per_unit = isset( $bundle_rules['price_per_unit'] ) ? floatval( $bundle_rules['price_per_unit'] ) : 0;
                            ?>
                            <div class="pls-bundle-banner__item">
                                <div class="pls-bundle-banner__content">
                                    <h3><?php echo esc_html( $bundle->name ); ?></h3>
                                    <p class="pls-bundle-banner__description">
                                        <?php
                                        printf(
                                            esc_html__( 'Buy %d or more products with at least %d units each and save!', 'pls-private-label-store' ),
                                            $required_sku_count,
                                            $required_units_per_sku
                                        );
                                        ?>
                                    </p>
                                    <div class="pls-bundle-banner__pricing">
                                        <span class="pls-bundle-banner__price-label"><?php esc_html_e( 'Bundle Price:', 'pls-private-label-store' ); ?></span>
                                        <span class="pls-bundle-banner__price-value"><?php echo wc_price( $bundle_price_per_unit ); ?> <?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Simplified single product shortcode for Elementor templates.
     * 
     * Usage: [pls_single_product show_configurator="yes" show_description="yes" show_ingredients="yes" show_bundles="yes"]
     * 
     * Auto-detects current product on single product pages.
     * Uses full PLS_Frontend_Display render methods for complete data display.
     * Perfect for use in Elementor Theme Builder templates.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function single_product_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'product_id'        => 0,
                'wc_id'             => 0, // Alias for product_id (WooCommerce product ID)
                'show_images'       => 'yes',
                'show_configurator' => 'yes',
                'show_description'  => 'yes',
                'show_ingredients'   => 'yes',
                'show_bundles'       => 'yes',
            ),
            $atts,
            'pls_single_product'
        );

        // Support both product_id and wc_id parameters (wc_id takes precedence if both provided)
        $product_id = absint( $atts['wc_id'] ) ?: absint( $atts['product_id'] );
        
        // Auto-detect product ID from current WooCommerce product if not provided
        if ( ! $product_id ) {
            global $product;
            if ( $product instanceof WC_Product ) {
                $product_id = $product->get_id();
            }
        }

        if ( ! $product_id ) {
            return '<div class="pls-note">' . esc_html__( 'Product not found. Use product_id or wc_id parameter, or use on single product pages.', 'pls-private-label-store' ) . '</div>';
        }

        // Get WooCommerce product (product_id can be WooCommerce product ID directly)
        $wc_product = wc_get_product( $product_id );
        if ( ! $wc_product ) {
            return '<div class="pls-note">' . esc_html__( 'WooCommerce product not found. Product ID: ', 'pls-private-label-store' ) . esc_html( $product_id ) . '</div>';
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
            return '<div class="pls-note">' . esc_html__( 'PLS product not found. This product may need to be synced from PLS admin.', 'pls-private-label-store' ) . '</div>';
        }

        // Enqueue required assets
        wp_enqueue_style( 'pls-frontend-display' );
        wp_enqueue_script( 'pls-offers' );
        
        // Enqueue WooCommerce variation scripts for add-to-cart functionality
        if ( function_exists( 'WC' ) && $wc_product->is_type( 'variable' ) ) {
            wp_enqueue_script( 'wc-add-to-cart-variation' );
        }

        // Use PLS_Frontend_Display to get full content with all data
        $options = array(
            'show_images'        => 'yes' === $atts['show_images'],
            'show_configurator'  => 'yes' === $atts['show_configurator'],
            'show_description'   => 'yes' === $atts['show_description'],
            'show_ingredients'   => 'yes' === $atts['show_ingredients'],
            'show_bundles'       => 'yes' === $atts['show_bundles'],
        );

        $content = PLS_Frontend_Display::get_pls_content( $product_id, $options );
        
        if ( empty( $content ) ) {
            return '<div class="pls-note">' . esc_html__( 'No PLS content available for this product.', 'pls-private-label-store' ) . '</div>';
        }

        return '<div class="pls-single-product" data-product-id="' . esc_attr( $product_id ) . '">' . $content . '</div>';
    }

    /**
     * Single category shortcode for Elementor templates.
     * 
     * Usage: [pls_single_category show_tier_badges="yes" show_starting_price="yes"]
     * 
     * Displays PLS-specific enhancements for category/archive pages.
     * Perfect for use in Elementor Theme Builder category templates.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function single_category_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'show_tier_badges'   => 'yes',
                'show_starting_price' => 'yes',
            ),
            $atts,
            'pls_single_category'
        );

        // Only works on category/archive pages
        if ( ! is_product_category() && ! is_shop() && ! is_product_tag() && ! is_product_taxonomy() ) {
            return '<div class="pls-note">' . esc_html__( 'This shortcode works on category, shop, and archive pages.', 'pls-private-label-store' ) . '</div>';
        }

        // Enqueue required styles
        wp_enqueue_style( 'pls-frontend-display' );

        // This shortcode enables the tier badges and starting prices via WooCommerce hooks
        // The actual display is handled by PLS_Frontend_Display hooks
        // We just need to ensure the hooks are active
        
        // Add inline CSS to show/hide elements based on attributes
        $css = '';
        if ( 'no' === $atts['show_tier_badges'] ) {
            $css .= '.pls-product-badge { display: none !important; }';
        }
        if ( 'no' === $atts['show_starting_price'] ) {
            $css .= '.pls-starting-price { display: none !important; }';
        }

        if ( $css ) {
            wp_add_inline_style( 'pls-frontend-display', $css );
        }

        // Return empty string - the hooks handle the display
        // But add a wrapper div for potential future enhancements
        return '<div class="pls-single-category" data-show-tier-badges="' . esc_attr( $atts['show_tier_badges'] ) . '" data-show-starting-price="' . esc_attr( $atts['show_starting_price'] ) . '"></div>';
    }

    /**
     * Category archive shortcode with full data display and product loop.
     * 
     * Usage: [pls_category_archive category_id="123" columns="3" limit="12" show_faq="yes"]
     * 
     * Displays a specific category with all data including:
     * - Category title, description, SEO meta
     * - FAQ schema (JSON-LD)
     * - Product loop with tier badges and starting prices
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function category_archive_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'category_id'        => 0,
                'columns'            => 3,
                'limit'              => 12,
                'orderby'            => 'menu_order',
                'order'              => 'ASC',
                'show_description'   => 'yes',
                'show_faq'           => 'yes',
                'show_tier_badges'   => 'yes',
                'show_starting_price' => 'yes',
            ),
            $atts,
            'pls_category_archive'
        );

        $category_id = absint( $atts['category_id'] );
        
        // Auto-detect category from current page if not provided
        if ( ! $category_id && is_product_category() ) {
            $queried_object = get_queried_object();
            if ( $queried_object && isset( $queried_object->term_id ) ) {
                $category_id = $queried_object->term_id;
            }
        }

        if ( ! $category_id ) {
            return '<div class="pls-note">' . esc_html__( 'Category ID required. Usage: [pls_category_archive category_id="123"] or use on category pages.', 'pls-private-label-store' ) . '</div>';
        }

        $category = get_term( $category_id, 'product_cat' );
        if ( ! $category || is_wp_error( $category ) ) {
            return '<div class="pls-note">' . esc_html__( 'Category not found.', 'pls-private-label-store' ) . '</div>';
        }

        // Get category meta
        $meta_title = get_term_meta( $category_id, '_pls_meta_title', true );
        $meta_desc  = get_term_meta( $category_id, '_pls_meta_desc', true );
        $faq_json   = get_term_meta( $category_id, '_pls_faq_json', true );
        $custom_order = get_term_meta( $category_id, '_pls_custom_order', true );

        // Enqueue styles
        wp_enqueue_style( 'pls-frontend-display' );

        ob_start();
        ?>
        <div class="pls-category-archive" data-category-id="<?php echo esc_attr( $category_id ); ?>">
            
            <!-- Category Header -->
            <div class="pls-category-archive__header">
                <h1 class="pls-category-archive__title"><?php echo esc_html( $category->name ); ?></h1>
                
                <?php if ( 'yes' === $atts['show_description'] && ! empty( $category->description ) ) : ?>
                    <div class="pls-category-archive__description">
                        <?php echo wp_kses_post( wpautop( $category->description ) ); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FAQ Schema (JSON-LD) -->
            <?php if ( 'yes' === $atts['show_faq'] && ! empty( $faq_json ) ) : ?>
                <?php
                $faq_data = json_decode( $faq_json, true );
                if ( is_array( $faq_data ) && ! empty( $faq_data ) ) :
                ?>
                    <script type="application/ld+json">
                    {
                        "@context": "https://schema.org",
                        "@type": "FAQPage",
                        "mainEntity": [
                            <?php
                            $faq_items = array();
                            foreach ( $faq_data as $faq ) {
                                if ( ! empty( $faq['question'] ) && ! empty( $faq['answer'] ) ) {
                                    $faq_items[] = '{
                                        "@type": "Question",
                                        "name": ' . wp_json_encode( wp_strip_all_tags( $faq['question'] ) ) . ',
                                        "acceptedAnswer": {
                                            "@type": "Answer",
                                            "text": ' . wp_json_encode( wp_strip_all_tags( $faq['answer'] ) ) . '
                                        }
                                    }';
                                }
                            }
                            if ( ! empty( $faq_items ) ) {
                                echo implode( ",\n", $faq_items );
                            }
                            ?>
                        ]
                    }
                    </script>
                    
                    <!-- Visual FAQ Section -->
                    <div class="pls-category-archive__faq">
                        <h2><?php esc_html_e( 'Frequently Asked Questions', 'pls-private-label-store' ); ?></h2>
                        <div class="pls-faq-list">
                            <?php foreach ( $faq_data as $faq ) : ?>
                                <?php if ( ! empty( $faq['question'] ) && ! empty( $faq['answer'] ) ) : ?>
                                    <div class="pls-faq-item">
                                        <h3 class="pls-faq-question"><?php echo esc_html( $faq['question'] ); ?></h3>
                                        <div class="pls-faq-answer"><?php echo wp_kses_post( $faq['answer'] ); ?></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Product Loop -->
            <div class="pls-category-archive__products">
                <?php
                $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => absint( $atts['limit'] ),
                    'orderby'        => sanitize_text_field( $atts['orderby'] ),
                    'order'          => sanitize_text_field( $atts['order'] ),
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => $category_id,
                        ),
                    ),
                    'meta_query'     => array(
                        array(
                            'key'     => '_pls_base_product_id',
                            'compare' => 'EXISTS',
                        ),
                    ),
                );

                $products = new WP_Query( $args );

                if ( $products->have_posts() ) :
                ?>
                    <ul class="products columns-<?php echo esc_attr( $atts['columns'] ); ?>">
                        <?php
                        while ( $products->have_posts() ) :
                            $products->the_post();
                            global $product;
                            
                            if ( ! $product ) {
                                continue;
                            }
                            
                            wc_get_template_part( 'content', 'product' );
                        endwhile;
                        ?>
                    </ul>
                <?php
                    wp_reset_postdata();
                else :
                ?>
                    <p class="pls-no-products"><?php esc_html_e( 'No products found in this category.', 'pls-private-label-store' ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .pls-category-archive {
            max-width: 1200px;
            margin: 0 auto;
        }
        .pls-category-archive__header {
            margin-bottom: 2rem;
        }
        .pls-category-archive__title {
            font-size: 2rem;
            font-weight: 600;
            margin: 0 0 1rem;
        }
        .pls-category-archive__description {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        .pls-category-archive__faq {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 8px;
        }
        .pls-category-archive__faq h2 {
            font-size: 1.5rem;
            margin: 0 0 1rem;
        }
        .pls-faq-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .pls-faq-item {
            background: #fff;
            padding: 1rem 1.5rem;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .pls-faq-question {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
            color: #111;
        }
        .pls-faq-answer {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .pls-category-archive__products {
            margin-top: 2rem;
        }
        .pls-no-products {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Shop page shortcode for displaying all PLS products.
     * 
     * Usage: [pls_shop_page columns="3" limit="12" orderby="date" order="DESC"]
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function shop_page_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'columns'            => 3,
                'limit'              => 12,
                'orderby'            => 'date',
                'order'              => 'DESC',
                'show_categories'    => 'yes',
                'show_tier_badges'   => 'yes',
            ),
            $atts,
            'pls_shop_page'
        );

        // Enqueue styles
        wp_enqueue_style( 'pls-frontend-display' );

        ob_start();
        ?>
        <div class="pls-shop-page">
            
            <?php if ( 'yes' === $atts['show_categories'] ) : ?>
                <!-- Category Navigation -->
                <div class="pls-shop-categories">
                    <?php
                    $categories = get_terms( array(
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => true,
                        'parent'     => 0, // Only parent categories
                        'meta_key'   => '_pls_custom_order',
                        'orderby'    => 'meta_value_num',
                        'order'      => 'ASC',
                    ) );
                    
                    if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) :
                    ?>
                        <ul class="pls-category-nav">
                            <li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pls-category-nav__item pls-category-nav__item--all"><?php esc_html_e( 'All Products', 'pls-private-label-store' ); ?></a></li>
                            <?php foreach ( $categories as $cat ) : ?>
                                <li>
                                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="pls-category-nav__item">
                                        <?php echo esc_html( $cat->name ); ?>
                                        <span class="pls-category-count"><?php echo esc_html( $cat->count ); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Product Grid -->
            <div class="pls-shop-products">
                <?php
                $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => absint( $atts['limit'] ),
                    'orderby'        => sanitize_text_field( $atts['orderby'] ),
                    'order'          => sanitize_text_field( $atts['order'] ),
                    'meta_query'     => array(
                        array(
                            'key'     => '_pls_base_product_id',
                            'compare' => 'EXISTS',
                        ),
                    ),
                );

                $products = new WP_Query( $args );

                if ( $products->have_posts() ) :
                ?>
                    <ul class="products columns-<?php echo esc_attr( $atts['columns'] ); ?>">
                        <?php
                        while ( $products->have_posts() ) :
                            $products->the_post();
                            wc_get_template_part( 'content', 'product' );
                        endwhile;
                        ?>
                    </ul>
                <?php
                    wp_reset_postdata();
                else :
                ?>
                    <p class="pls-no-products"><?php esc_html_e( 'No products found.', 'pls-private-label-store' ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .pls-shop-page {
            max-width: 1200px;
            margin: 0 auto;
        }
        .pls-shop-categories {
            margin-bottom: 2rem;
        }
        .pls-category-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .pls-category-nav__item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            border-radius: 999px;
            text-decoration: none;
            color: #374151;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .pls-category-nav__item:hover {
            background: #007AFF;
            color: #fff;
        }
        .pls-category-nav__item--all {
            background: #007AFF;
            color: #fff;
        }
        .pls-category-count {
            background: rgba(0,0,0,0.1);
            padding: 0.125rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}
