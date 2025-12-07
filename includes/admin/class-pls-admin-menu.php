<?php
/**
 * Admin menu + screen routing.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Admin_Menu {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
    }

    public static function register_menu() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            // Still allow configuration pages, but place under Tools if Woo absent.
            add_management_page(
                'Private Label (PLS)',
                'Private Label (PLS)',
                'manage_options',
                'pls-private-label',
                array( __CLASS__, 'render_dashboard' )
            );
            return;
        }

        add_submenu_page(
            'woocommerce',
            'Private Label (PLS)',
            'Private Label (PLS)',
            'manage_woocommerce',
            'pls-private-label',
            array( __CLASS__, 'render_dashboard' )
        );

        add_submenu_page(
            'woocommerce',
            'PLS – Products & Packs',
            'PLS – Products & Packs',
            PLS_Capabilities::CAP_PRODUCTS,
            'pls-products',
            array( __CLASS__, 'render_products' )
        );

        add_submenu_page(
            'woocommerce',
            'PLS – Attributes & Swatches',
            'PLS – Attributes & Swatches',
            PLS_Capabilities::CAP_ATTRS,
            'pls-attributes',
            array( __CLASS__, 'render_attributes' )
        );

        add_submenu_page(
            'woocommerce',
            'PLS – Bundles & Deals',
            'PLS – Bundles & Deals',
            PLS_Capabilities::CAP_BUNDLES,
            'pls-bundles',
            array( __CLASS__, 'render_bundles' )
        );
    }

    public static function assets( $hook ) {
        if ( false === strpos( (string) $hook, 'pls-' ) && false === strpos( (string) $hook, 'woocommerce_page_pls' ) ) {
            return;
        }

        wp_enqueue_style(
            'pls-admin',
            PLS_PLS_URL . 'assets/css/admin.css',
            array(),
            PLS_PLS_VERSION
        );

        wp_enqueue_script(
            'pls-admin',
            PLS_PLS_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            PLS_PLS_VERSION,
            true
        );

        wp_localize_script(
            'pls-admin',
            'PLS_Admin',
            array(
                'nonce' => wp_create_nonce( 'pls_admin_nonce' ),
            )
        );
    }

    public static function render_dashboard() {
        require PLS_PLS_DIR . 'includes/admin/screens/dashboard.php';
    }

    public static function render_products() {
        require PLS_PLS_DIR . 'includes/admin/screens/products.php';
    }

    public static function render_attributes() {
        require PLS_PLS_DIR . 'includes/admin/screens/attributes.php';
    }

    public static function render_bundles() {
        require PLS_PLS_DIR . 'includes/admin/screens/bundles.php';
    }
}
