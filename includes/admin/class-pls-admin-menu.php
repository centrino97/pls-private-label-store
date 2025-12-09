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
        add_menu_page(
            __( 'Private Label Store', 'pls-private-label-store' ),
            __( 'PLS', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-dashboard',
            array( __CLASS__, 'render_dashboard' ),
            'dashicons-products',
            55
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS Dashboard', 'pls-private-label-store' ),
            __( 'Dashboard', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-dashboard',
            array( __CLASS__, 'render_dashboard' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Products & Packs', 'pls-private-label-store' ),
            __( 'Products', 'pls-private-label-store' ),
            PLS_Capabilities::CAP_PRODUCTS,
            'pls-products',
            array( __CLASS__, 'render_products' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Categories', 'pls-private-label-store' ),
            __( 'Categories', 'pls-private-label-store' ),
            PLS_Capabilities::CAP_PRODUCTS,
            'pls-categories',
            array( __CLASS__, 'render_categories' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Attributes & Swatches', 'pls-private-label-store' ),
            __( 'Attributes', 'pls-private-label-store' ),
            PLS_Capabilities::CAP_ATTRS,
            'pls-attributes',
            array( __CLASS__, 'render_attributes' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Ingredients', 'pls-private-label-store' ),
            __( 'Ingredients', 'pls-private-label-store' ),
            PLS_Capabilities::CAP_PRODUCTS,
            'pls-ingredients',
            array( __CLASS__, 'render_ingredients' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Bundles & Deals', 'pls-private-label-store' ),
            __( 'Bundles', 'pls-private-label-store' ),
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

    public static function render_ingredients() {
        require PLS_PLS_DIR . 'includes/admin/screens/ingredients.php';
    }

    public static function render_bundles() {
        require PLS_PLS_DIR . 'includes/admin/screens/bundles.php';
    }

    public static function render_categories() {
        require PLS_PLS_DIR . 'includes/admin/screens/categories.php';
    }
}
