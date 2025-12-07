<?php
/**
 * Elementor integration: widgets + dynamic tags.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Elementor {

    public static function init() {
        add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
        add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register_dynamic_tags' ) );

        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ) );
    }

    public static function register_widgets( $widgets_manager ) {
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        require_once PLS_PLS_DIR . 'includes/elementor/widgets/class-pls-widget-configurator.php';
        require_once PLS_PLS_DIR . 'includes/elementor/widgets/class-pls-widget-bundle-offer.php';

        $widgets_manager->register( new \PLS_Widget_Configurator() );
        $widgets_manager->register( new \PLS_Widget_Bundle_Offer() );
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
     * Frontend assets for offers widgets.
     * Widgets also declare deps, but we keep a minimal loader for safety.
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
            'PLS_Offers',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'pls_offers' ),
            )
        );
    }
}
