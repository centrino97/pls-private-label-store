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
        add_action( 'admin_notices', array( __CLASS__, 'render_custom_header' ), 1 );
        add_action( 'admin_init', array( __CLASS__, 'restrict_bodoci_users' ) );
        add_action( 'wp_login', array( __CLASS__, 'redirect_bodoci_on_login' ), 10, 2 );
        add_action( 'admin_head', array( __CLASS__, 'hide_wp_admin_menu' ) );
    }

    /**
     * Check if current user is Nikola (full access, no restrictions).
     *
     * @param WP_User|null $user Optional user object. If not provided, uses current user.
     * @return bool
     */
    public static function is_nikola_user( $user = null ) {
        if ( null === $user ) {
            $user = wp_get_current_user();
        }
        
        if ( ! $user || ! $user->ID ) {
            return false;
        }

        // Check by username (case-insensitive)
        $username = strtolower( $user->user_login );
        if ( 'nikola' === $username ) {
            return true;
        }

        // Check by display name (case-insensitive)
        $display_name = strtolower( $user->display_name );
        if ( 'nikola' === $display_name ) {
            return true;
        }

        return false;
    }

    /**
     * Check if current user should have restricted access (only PLS pages).
     * Note: Nikola, administrators, and PLS users have full access.
     *
     * @return bool
     */
    public static function is_restricted_user() {
        $current_user = wp_get_current_user();
        if ( ! $current_user || ! $current_user->ID ) {
            return false;
        }

        // Nikola has full access - skip all restrictions
        if ( self::is_nikola_user( $current_user ) ) {
            return false;
        }

        // Administrators have full access
        if ( current_user_can( 'manage_options' ) ) {
            return false;
        }

        // PLS users have full access to PLS pages (no restrictions)
        if ( in_array( PLS_Capabilities::ROLE_PLS_USER, $current_user->roles, true ) ) {
            return false;
        }

        // Shop managers have full access
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return false;
        }

        // Check if user has @bodocibiophysics.com email domain
        $user_email = $current_user->user_email;
        if ( $user_email && strpos( $user_email, '@bodocibiophysics.com' ) !== false ) {
            return true; // Bodoci users are restricted to PLS pages only
        }

        // All other users are restricted to PLS pages only
        return true;
    }

    /**
     * Restrict Bodoci users to PLS pages only.
     */
    public static function restrict_bodoci_users() {
        if ( ! self::is_restricted_user() ) {
            return;
        }

        // Allow PLS pages
        $pls_pages = array(
            'pls-dashboard',
            'pls-products',
            'pls-bundles',
            'pls-custom-orders',
            'pls-orders',
            'pls-categories',
            'pls-attributes',
            'pls-bi',
            'pls-commission',
            'pls-revenue',
            'pls-product-preview',
            'pls-settings',
            'pls-system-test', // System test page (admin only)
        );

        $current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        // Allow AJAX requests
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        // Allow PLS pages
        if ( in_array( $current_page, $pls_pages, true ) ) {
            return;
        }

        // Allow admin-ajax.php
        if ( strpos( $_SERVER['REQUEST_URI'], 'admin-ajax.php' ) !== false ) {
            return;
        }

        // Redirect to PLS dashboard
        wp_safe_redirect( admin_url( 'admin.php?page=pls-dashboard' ) );
        exit;
    }

    /**
     * Redirect restricted users to PLS dashboard on login.
     * Note: Nikola, administrators, and PLS users are excluded from redirects.
     *
     * @param string $user_login User login name.
     * @param WP_User $user User object.
     */
    public static function redirect_bodoci_on_login( $user_login, $user ) {
        if ( ! $user || ! $user->ID ) {
            return;
        }

        // Nikola has full access - skip redirect
        if ( self::is_nikola_user( $user ) ) {
            return;
        }

        // Administrators have full access
        if ( user_can( $user, 'manage_options' ) ) {
            return;
        }

        // PLS users have full access to PLS pages
        if ( in_array( PLS_Capabilities::ROLE_PLS_USER, $user->roles, true ) ) {
            return;
        }

        // Shop managers have full access
        if ( user_can( $user, 'manage_woocommerce' ) ) {
            return;
        }

        // Check if user has @bodocibiophysics.com email domain
        $user_email = $user->user_email;
        if ( $user_email && strpos( $user_email, '@bodocibiophysics.com' ) !== false ) {
            // Redirect to PLS dashboard
            wp_safe_redirect( admin_url( 'admin.php?page=pls-dashboard' ) );
            exit;
        }

        // All other restricted users redirect to PLS dashboard
        if ( self::is_restricted_user() ) {
            wp_safe_redirect( admin_url( 'admin.php?page=pls-dashboard' ) );
            exit;
        }
    }

    /**
     * Hide WordPress admin menu for Bodoci users.
     */
    public static function hide_wp_admin_menu() {
        if ( ! self::is_restricted_user() ) {
            return;
        }

        echo '<style>
            #adminmenuback,
            #adminmenuwrap,
            #wpadminbar {
                display: none !important;
            }
            #wpcontent {
                margin-left: 0 !important;
            }
            #wpbody {
                padding-top: 0 !important;
            }
        </style>';
    }

    /**
     * Render custom admin header on PLS pages.
     */
    public static function render_custom_header() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'pls-' ) === false ) {
            return;
        }

        require PLS_PLS_DIR . 'includes/admin/pls-admin-header.php';
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
            __( 'PLS – Product Options', 'pls-private-label-store' ),
            __( 'Product Options', 'pls-private-label-store' ),
            PLS_Capabilities::CAP_ATTRS,
            'pls-attributes',
            array( __CLASS__, 'render_attributes' )
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
            __( 'PLS – BI Dashboard', 'pls-private-label-store' ),
            __( 'Analytics', 'pls-private-label-store' ),
            'manage_options',
            'pls-bi',
            array( __CLASS__, 'render_bi_dashboard' )
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
            __( 'PLS – Ingredients Base', 'pls-private-label-store' ),
            __( 'Ingredients Base', 'pls-private-label-store' ),
            PLS_Capabilities::CAP_ATTRS,
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

        // Settings page with commission settings and sample data
        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – Settings', 'pls-private-label-store' ),
            __( 'Settings', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-settings',
            array( __CLASS__, 'render_settings' )
        );

        // System Test page (admin only)
        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – System Test', 'pls-private-label-store' ),
            __( 'System Test', 'pls-private-label-store' ),
            'manage_options',
            'pls-system-test',
            array( __CLASS__, 'render_system_test' )
        );

        // User Setup page (admin only, for handoff)
        add_submenu_page(
            'pls-dashboard',
            __( 'PLS – User Setup', 'pls-private-label-store' ),
            __( 'User Setup', 'pls-private-label-store' ),
            'manage_options',
            'pls-user-setup',
            array( __CLASS__, 'render_user_setup' )
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

        // Hidden order detail page (accessed via direct link)
        add_submenu_page(
            null, // Hidden from menu
            __( 'Order Details', 'pls-private-label-store' ),
            __( 'Order Details', 'pls-private-label-store' ),
            'manage_woocommerce',
            'pls-order-detail',
            array( __CLASS__, 'render_order_detail' )
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

        // Hide WordPress admin elements on PLS pages
        $hide_admin_css = '
            #wpadminbar,
            #adminmenuback,
            #adminmenuwrap,
            #wpfooter,
            .update-nag,
            .notice:not(.pls-notice) {
                display: none !important;
            }
            #wpcontent {
                margin-left: 0 !important;
                padding-left: 20px !important;
            }
            #wpbody {
                padding-top: 0 !important;
            }
            .pls-wrap {
                padding-top: 0;
            }
        ';

        // Hide WP admin menu for restricted users everywhere
        if ( self::is_restricted_user() ) {
            $hide_admin_css .= '
                #adminmenuback,
                #adminmenuwrap {
                    display: none !important;
                }
            ';
        }

        wp_add_inline_style( 'pls-admin', $hide_admin_css );

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

        // Enqueue system test assets on system test page
        if ( strpos( $hook, 'pls-system-test' ) !== false ) {
            wp_enqueue_style(
                'pls-system-test',
                PLS_PLS_URL . 'assets/css/system-test.css',
                array(),
                PLS_PLS_VERSION
            );

            wp_enqueue_script(
                'pls-system-test',
                PLS_PLS_URL . 'assets/js/system-test.js',
                array( 'jquery', 'wp-util' ),
                PLS_PLS_VERSION,
                true
            );

            $upload_dir = wp_upload_dir();
            wp_localize_script(
                'pls-system-test',
                'plsSystemTest',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'pls_system_test_nonce' ),
                    'uploadUrl' => $upload_dir['baseurl'] . '/',
                )
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

    public static function render_bi_dashboard() {
        require PLS_PLS_DIR . 'includes/admin/screens/bi-dashboard.php';
    }

    public static function render_settings() {
        require PLS_PLS_DIR . 'includes/admin/screens/settings.php';
    }

    public static function render_system_test() {
        require PLS_PLS_DIR . 'includes/admin/screens/system-test.php';
    }

    public static function render_user_setup() {
        require PLS_PLS_DIR . 'includes/admin/screens/user-setup.php';
    }

    public static function render_order_detail() {
        require PLS_PLS_DIR . 'includes/admin/screens/order-detail.php';
    }

}
