<?php
/**
 * Onboarding system for PLS plugin.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Onboarding {

    /**
     * Initialize onboarding system.
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_ajax_pls_start_onboarding', array( __CLASS__, 'start_onboarding' ) );
        add_action( 'wp_ajax_pls_update_onboarding_step', array( __CLASS__, 'update_step' ) );
        add_action( 'wp_ajax_pls_complete_onboarding', array( __CLASS__, 'complete_onboarding' ) );
        add_action( 'wp_ajax_pls_skip_onboarding', array( __CLASS__, 'skip_onboarding' ) );
        add_action( 'wp_ajax_pls_get_onboarding_steps', array( __CLASS__, 'get_steps' ) );
        add_action( 'wp_ajax_pls_delete_test_product', array( __CLASS__, 'delete_test_product' ) );
    }

    /**
     * Enqueue onboarding assets.
     */
    public static function enqueue_assets( $hook ) {
        if ( false === strpos( (string) $hook, 'pls-' ) && false === strpos( (string) $hook, 'woocommerce_page_pls' ) ) {
            return;
        }

        wp_enqueue_style(
            'pls-onboarding',
            PLS_PLS_URL . 'assets/css/onboarding.css',
            array(),
            PLS_PLS_VERSION
        );

        wp_enqueue_script(
            'pls-onboarding',
            PLS_PLS_URL . 'assets/js/onboarding.js',
            array( 'jquery' ),
            PLS_PLS_VERSION,
            true
        );

        $current_user = wp_get_current_user();
        $progress = self::get_progress( $current_user->ID );
        $current_page = self::get_current_page_from_hook( $hook );

        wp_localize_script(
            'pls-onboarding',
            'PLS_Onboarding',
            array(
                'nonce' => wp_create_nonce( 'pls_onboarding_nonce' ),
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'progress' => $progress,
                'current_page' => $current_page,
                'steps' => self::get_steps_definition(),
                'is_active' => $progress && ! $progress->completed_at,
            )
        );
    }

    /**
     * Get steps definition.
     *
     * @return array
     */
    public static function get_steps_definition() {
        return array(
            'dashboard' => array(
                'title' => __( 'Dashboard Overview', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'Review summary cards showing key metrics', 'pls-private-label-store' ),
                    __( 'Explore quick links to all sections', 'pls-private-label-store' ),
                ),
            ),
            'products' => array(
                'title' => __( 'Products & Packs', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'Click "Add product" to create a test product', 'pls-private-label-store' ),
                    __( 'Fill in General info (name, categories, images)', 'pls-private-label-store' ),
                    __( 'Add Pack Tiers with units and pricing', 'pls-private-label-store' ),
                    __( 'Configure Product Options (Package Type, Color, Cap)', 'pls-private-label-store' ),
                    __( 'Save product and sync to WooCommerce', 'pls-private-label-store' ),
                ),
            ),
            'orders' => array(
                'title' => __( 'Orders', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'View orders containing PLS products', 'pls-private-label-store' ),
                    __( 'Understand commission calculation', 'pls-private-label-store' ),
                ),
            ),
            'custom-orders' => array(
                'title' => __( 'Custom Orders', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'Explore the Kanban board with stages', 'pls-private-label-store' ),
                    __( 'Drag and drop cards between stages', 'pls-private-label-store' ),
                    __( 'Click a card to view order details', 'pls-private-label-store' ),
                    __( 'Update financials and commission', 'pls-private-label-store' ),
                ),
            ),
            'revenue' => array(
                'title' => __( 'Revenue', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'View sales revenue summary', 'pls-private-label-store' ),
                    __( 'Explore revenue charts and trends', 'pls-private-label-store' ),
                    __( 'Filter orders by date and product', 'pls-private-label-store' ),
                ),
            ),
            'commission' => array(
                'title' => __( 'Commission', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'View monthly commission summary', 'pls-private-label-store' ),
                    __( 'Switch to detailed list view', 'pls-private-label-store' ),
                    __( 'Mark commissions as invoiced or paid', 'pls-private-label-store' ),
                    __( 'Send monthly commission reports', 'pls-private-label-store' ),
                ),
            ),
            'categories' => array(
                'title' => __( 'Categories', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'Manage product categories', 'pls-private-label-store' ),
                ),
            ),
            'attributes' => array(
                'title' => __( 'Product Options', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'Create and manage product attributes', 'pls-private-label-store' ),
                    __( 'Set tier-based pricing rules', 'pls-private-label-store' ),
                ),
            ),
            'bundles' => array(
                'title' => __( 'Bundles', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'Create bundle products', 'pls-private-label-store' ),
                    __( 'Configure bundle pricing', 'pls-private-label-store' ),
                ),
            ),
            'settings' => array(
                'title' => __( 'Settings', 'pls-private-label-store' ),
                'steps' => array(
                    __( 'Configure commission rates for tiers and bundles', 'pls-private-label-store' ),
                    __( 'Set custom order commission percentage', 'pls-private-label-store' ),
                    __( 'Configure label pricing', 'pls-private-label-store' ),
                ),
            ),
        );
    }

    /**
     * Get current page from hook.
     *
     * @param string $hook Admin hook.
     * @return string
     */
    private static function get_current_page_from_hook( $hook ) {
        if ( strpos( $hook, 'pls-dashboard' ) !== false ) {
            return 'dashboard';
        } elseif ( strpos( $hook, 'pls-products' ) !== false ) {
            return 'products';
        } elseif ( strpos( $hook, 'pls-orders' ) !== false ) {
            return 'orders';
        } elseif ( strpos( $hook, 'pls-custom-orders' ) !== false ) {
            return 'custom-orders';
        } elseif ( strpos( $hook, 'pls-revenue' ) !== false ) {
            return 'revenue';
        } elseif ( strpos( $hook, 'pls-commission' ) !== false ) {
            return 'commission';
        } elseif ( strpos( $hook, 'pls-categories' ) !== false ) {
            return 'categories';
        } elseif ( strpos( $hook, 'pls-attributes' ) !== false ) {
            return 'attributes';
        } elseif ( strpos( $hook, 'pls-bundles' ) !== false ) {
            return 'bundles';
        } elseif ( strpos( $hook, 'pls-settings' ) !== false ) {
            return 'settings';
        }
        return '';
    }

    /**
     * Get onboarding progress for user.
     *
     * @param int $user_id User ID.
     * @return object|null
     */
    public static function get_progress( $user_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_onboarding_progress';

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $user_id ),
            OBJECT
        );
    }

    /**
     * Start onboarding.
     */
    public static function start_onboarding() {
        check_ajax_referer( 'pls_onboarding_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'pls_onboarding_progress';

        // Check if already exists
        $existing = self::get_progress( $user_id );
        if ( $existing ) {
            // Reset progress
            $wpdb->update(
                $table,
                array(
                    'current_step' => null,
                    'completed_steps' => '[]',
                    'test_product_id' => null,
                    'completed_at' => null,
                    'started_at' => current_time( 'mysql' ),
                ),
                array( 'user_id' => $user_id ),
                array( '%s', '%s', '%d', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            // Create new
            $wpdb->insert(
                $table,
                array(
                    'user_id' => $user_id,
                    'started_at' => current_time( 'mysql' ),
                ),
                array( '%d', '%s' )
            );
        }

        wp_send_json_success( array( 'message' => __( 'Onboarding started.', 'pls-private-label-store' ) ) );
    }

    /**
     * Update onboarding step.
     */
    public static function update_step() {
        check_ajax_referer( 'pls_onboarding_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $user_id = get_current_user_id();
        $page = isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : '';
        $step_index = isset( $_POST['step_index'] ) ? absint( $_POST['step_index'] ) : 0;

        if ( ! $page ) {
            wp_send_json_error( array( 'message' => __( 'Invalid page.', 'pls-private-label-store' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pls_onboarding_progress';

        $progress = self::get_progress( $user_id );
        $completed_steps = $progress && $progress->completed_steps ? json_decode( $progress->completed_steps, true ) : array();

        $step_key = $page . '_' . $step_index;
        if ( ! in_array( $step_key, $completed_steps, true ) ) {
            $completed_steps[] = $step_key;
        }

        $wpdb->update(
            $table,
            array(
                'current_step' => $page,
                'completed_steps' => wp_json_encode( $completed_steps ),
            ),
            array( 'user_id' => $user_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        wp_send_json_success();
    }

    /**
     * Complete onboarding.
     */
    public static function complete_onboarding() {
        check_ajax_referer( 'pls_onboarding_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'pls_onboarding_progress';

        $progress = self::get_progress( $user_id );
        $test_product_id = $progress ? $progress->test_product_id : null;

        $wpdb->update(
            $table,
            array( 'completed_at' => current_time( 'mysql' ) ),
            array( 'user_id' => $user_id ),
            array( '%s' ),
            array( '%d' )
        );

        wp_send_json_success(
            array(
                'message' => __( 'Onboarding completed!', 'pls-private-label-store' ),
                'test_product_id' => $test_product_id,
            )
        );
    }

    /**
     * Skip onboarding.
     */
    public static function skip_onboarding() {
        check_ajax_referer( 'pls_onboarding_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'pls_onboarding_progress';

        $wpdb->update(
            $table,
            array( 'completed_at' => current_time( 'mysql' ) ),
            array( 'user_id' => $user_id ),
            array( '%s' ),
            array( '%d' )
        );

        wp_send_json_success( array( 'message' => __( 'Onboarding skipped.', 'pls-private-label-store' ) ) );
    }

    /**
     * Get onboarding steps (AJAX endpoint).
     */
    public static function get_steps() {
        check_ajax_referer( 'pls_onboarding_nonce', 'nonce' );

        wp_send_json_success( array( 'steps' => self::get_steps_definition() ) );
    }

    /**
     * Delete test product.
     */
    public static function delete_test_product() {
        check_ajax_referer( 'pls_onboarding_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'pls-private-label-store' ) ) );
        }

        $product = PLS_Repo_Base_Product::get( $product_id );
        if ( ! $product ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'pls-private-label-store' ) ) );
        }

        // Delete from WooCommerce if synced
        if ( $product->wc_product_id ) {
            wp_delete_post( $product->wc_product_id, true );
        }

        // Delete from PLS
        global $wpdb;
        $table = $wpdb->prefix . 'pls_base_product';
        $wpdb->delete( $table, array( 'id' => $product_id ), array( '%d' ) );

        // Clear test_product_id from progress
        $user_id = get_current_user_id();
        $progress_table = $wpdb->prefix . 'pls_onboarding_progress';
        $wpdb->update(
            $progress_table,
            array( 'test_product_id' => null ),
            array( 'user_id' => $user_id ),
            array( '%d' ),
            array( '%d' )
        );

        wp_send_json_success( array( 'message' => __( 'Test product deleted.', 'pls-private-label-store' ) ) );
    }

    /**
     * Set test product ID.
     *
     * @param int $user_id User ID.
     * @param int $product_id Product ID.
     */
    public static function set_test_product( $user_id, $product_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_onboarding_progress';

        $wpdb->update(
            $table,
            array( 'test_product_id' => $product_id ),
            array( 'user_id' => $user_id ),
            array( '%d' ),
            array( '%d' )
        );
    }
}
