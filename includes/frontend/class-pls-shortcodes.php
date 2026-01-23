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
     */
    public static function init() {
        add_shortcode( 'pls_product', array( __CLASS__, 'product_shortcode' ) );
        add_shortcode( 'pls_configurator', array( __CLASS__, 'configurator_shortcode' ) );
        add_shortcode( 'pls_bundle', array( __CLASS__, 'bundle_shortcode' ) );
        add_shortcode( 'pls_product_page', array( __CLASS__, 'product_page_shortcode' ) );
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
     * Configurator shortcode.
     * 
     * Usage: [pls_configurator product_id="123"]
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function configurator_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'product_id' => 0,
            ),
            $atts,
            'pls_configurator'
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

        $wc_product = wc_get_product( $product_id );
        if ( ! $wc_product || ! $wc_product->is_type( 'variable' ) ) {
            return '<div class="pls-note">' . esc_html__( 'Variable product required.', 'pls-private-label-store' ) . '</div>';
        }

        // Enqueue required scripts/styles
        wp_enqueue_style( 'pls-offers' );
        wp_enqueue_script( 'pls-offers' );

        $variation_attributes = $wc_product->get_variation_attributes();
        $pack_tiers = isset( $variation_attributes['pa_pack-tier'] ) ? (array) $variation_attributes['pa_pack-tier'] : array();

        if ( empty( $pack_tiers ) ) {
            return '<div class="pls-note">' . esc_html__( 'No pack tiers found.', 'pls-private-label-store' ) . '</div>';
        }

        ob_start();
        ?>
        <div class="pls-configurator" data-product-id="<?php echo esc_attr( $product_id ); ?>">
            <div class="pls-configurator__title"><?php echo esc_html__( 'Configure your pack', 'pls-private-label-store' ); ?></div>
            <div class="pls-configurator__block">
                <strong><?php echo esc_html__( 'Pack tiers', 'pls-private-label-store' ); ?></strong>
                <div class="pls-configurator__tiers">
                    <?php foreach ( $pack_tiers as $tier_slug ) : ?>
                        <?php
                        $tier_term = get_term_by( 'slug', $tier_slug, 'pa_pack-tier' );
                        if ( ! $tier_term ) {
                            continue;
                        }
                        ?>
                        <label class="pls-configurator__tier">
                            <input type="radio" name="pls_pack_tier" value="<?php echo esc_attr( $tier_slug ); ?>" />
                            <span><?php echo esc_html( $tier_term->name ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
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
}
