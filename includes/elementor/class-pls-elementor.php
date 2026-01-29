<?php
/**
 * Elementor integration: dynamic tags and frontend assets.
 *
 * Note: PLS uses shortcodes for frontend display, not Elementor widgets.
 * Use [pls_single_product], [pls_single_category], or [pls_shop_page] shortcodes
 * in Elementor templates via the Shortcode widget.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Elementor {

    public static function init() {
        // Register dynamic tags (Pack Units)
        add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register_dynamic_tags' ) );

        // Enqueue frontend assets for shortcode functionality
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ) );
    }

    public static function register_dynamic_tags( $dynamic_tags ) {
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        require_once PLS_PLS_DIR . 'includes/elementor/dynamic-tags/class-pls-dtag-pack-units.php';

        // Group registration.
        $dynamic_tags->register_group(
            'pls',
            array(
                'title' => __( 'PLS', 'pls-private-label-store' ),
            )
        );

        $dynamic_tags->register( new \PLS_DTag_Pack_Units() );
    }

    /**
     * Frontend assets for shortcode functionality.
     * Loads CSS and JavaScript required for PLS shortcodes to work properly.
     */
    public static function frontend_assets() {
        if ( is_admin() ) {
            return;
        }

        wp_register_style(
            'pls-offers',
            PLS_PLS_URL . 'assets/css/offers.css',
            array(),
            PLS_PLS_VERSION
        );

        wp_register_script(
            'pls-offers',
            PLS_PLS_URL . 'assets/js/offers.js',
            array( 'jquery' ),
            PLS_PLS_VERSION,
            true
        );

        wp_localize_script(
            'pls-offers',
            'plsOffers',
            array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( 'pls_offers' ),
                'addToCartNonce' => wp_create_nonce( 'pls_add_to_cart' ),
                'cartUrl'        => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
            )
        );
    }
}
