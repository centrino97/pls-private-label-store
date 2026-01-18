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
        add_action( 'admin_post_pls_save_label_settings', array( __CLASS__, 'save_label_settings' ) );
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
            __( 'PLS – Orders', 'pls-private-label-store' ),
            __( 'Orders', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-orders',
            array( __CLASS__, 'render_orders' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Custom Orders', 'pls-private-label-store' ),
            __( 'Custom Orders', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-custom-orders',
            array( __CLASS__, 'render_custom_orders' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Revenue', 'pls-private-label-store' ),
            __( 'Revenue', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-revenue',
            array( __CLASS__, 'render_revenue' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Commission', 'pls-private-label-store' ),
            __( 'Commission', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-commission',
            array( __CLASS__, 'render_commission' )
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
            __( 'PLS – Product Options', 'pls-private-label-store' ),
            __( 'Product Options', 'pls-private-label-store' ),
            PLS_Capabilities::CAP_ATTRS,
            'pls-attributes',
            array( __CLASS__, 'render_attributes' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Bundles & Deals', 'pls-private-label-store' ),
            __( 'Bundles', 'pls-private-label-store' ),
            PLS_Capabilities::CAP_BUNDLES,
            'pls-bundles',
            array( __CLASS__, 'render_bundles' )
        );

        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Settings', 'pls-private-label-store' ),
            __( 'Settings', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-settings',
            array( __CLASS__, 'render_settings' )
        );

        // Hidden preview page (accessed via direct link)
        add_submenu_page(
            null, // Hidden from menu
            __( 'Product Preview', 'pls-private-label-store' ),
            __( 'Product Preview', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-product-preview',
            array( __CLASS__, 'render_product_preview' )
        );
    }

    public static function assets( $hook ) {
        if ( false === strpos( (string) $hook, 'pls-' ) && false === strpos( (string) $hook, 'woocommerce_page_pls' ) ) {
            return;
        }

        // Ensure media frames exist for featured/gallery pickers and icons.
        wp_enqueue_media();

        wp_enqueue_style(
            'pls-admin',
            PLS_PLS_URL . 'assets/css/admin.css',
            array(),
            PLS_PLS_VERSION
        );

        wp_enqueue_script(
            'pls-admin',
            PLS_PLS_URL . 'assets/js/admin.js',
            array( 'jquery', 'jquery-ui-sortable' ),
            PLS_PLS_VERSION,
            true
        );

        // Enqueue custom orders script on custom orders page
        if ( 'pls-custom-orders' === $hook ) {
            wp_enqueue_script(
                'pls-custom-orders',
                PLS_PLS_URL . 'assets/js/custom-orders.js',
                array( 'jquery', 'jquery-ui-sortable' ),
                PLS_PLS_VERSION,
                true
            );
        }

        // Enqueue commission script on commission page
        if ( 'pls-commission' === $hook ) {
            wp_enqueue_script(
                'pls-commission',
                PLS_PLS_URL . 'assets/js/commission.js',
                array( 'jquery' ),
                PLS_PLS_VERSION,
                true
            );
        }

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

    public static function render_product_preview() {
        require PLS_PLS_DIR . 'includes/admin/screens/product-preview.php';
    }

    public static function render_orders() {
        require PLS_PLS_DIR . 'includes/admin/screens/orders.php';
    }

    public static function render_custom_orders() {
        require PLS_PLS_DIR . 'includes/admin/screens/custom-orders.php';
    }

    public static function render_revenue() {
        require PLS_PLS_DIR . 'includes/admin/screens/revenue.php';
    }

    public static function render_commission() {
        require PLS_PLS_DIR . 'includes/admin/screens/commission.php';
    }

    public static function render_settings() {
        require PLS_PLS_DIR . 'includes/admin/screens/settings.php';
    }

    /**
     * Handle label settings form submission.
     */
    public static function save_label_settings() {
        check_admin_referer( 'pls_save_label_settings', 'pls_label_settings_nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'pls-private-label-store' ) );
        }

        $label_price = isset( $_POST['label_price_tier_1_2'] ) ? round( floatval( $_POST['label_price_tier_1_2'] ), 2 ) : 0.50;
        
        if ( $label_price < 0 ) {
            $label_price = 0;
        }

        update_option( 'pls_label_price_tier_1_2', $label_price );

        wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'admin.php?page=pls-dashboard' ) ) );
        exit;
    }
}
