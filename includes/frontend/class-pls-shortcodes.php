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
                <div class="pls-product-info__ingredients">
                    <h3><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></h3>
                    <p><?php echo esc_html( $profile->ingredients_list ); ?></p>
                </div>
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
}
