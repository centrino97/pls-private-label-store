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
        add_action( 'wp_ajax_pls_get_helper_content', array( __CLASS__, 'get_helper_content_ajax' ) );
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
                'admin_url' => admin_url(),
                'progress' => $progress,
                'current_page' => $current_page,
                'steps' => self::get_steps_definition(),
                'tutorial_flow' => self::get_tutorial_flow(),
                'is_active' => $progress && ! $progress->completed_at,
            )
        );
    }

    /**
     * Get helper content for a specific page/section.
     *
     * @param string $page Page identifier.
     * @param string $section Optional section identifier.
     * @return array Helper content array.
     */
    public static function get_helper_content( $page, $section = '' ) {
        $content = array();

        // Product creation modal sections
        if ( 'products' === $page ) {
            $content = array(
                'general' => array(
                    'title' => __( 'General Information', 'pls-private-label-store' ),
                    'tips' => array(
                        __( 'Product name will be used as the WooCommerce product title.', 'pls-private-label-store' ),
                        __( 'Select categories to organize products in your store.', 'pls-private-label-store' ),
                        __( 'Upload featured image and gallery images for product display.', 'pls-private-label-store' ),
                    ),
                    'validation' => array(
                        __( 'Product name is required.', 'pls-private-label-store' ),
                        __( 'At least one category must be selected.', 'pls-private-label-store' ),
                    ),
                ),
                'packs' => array(
                    'title' => __( 'Pack Tiers', 'pls-private-label-store' ),
                    'tips' => array(
                        __( 'Pack tiers define different quantities and pricing options.', 'pls-private-label-store' ),
                        __( 'Each tier will become a WooCommerce product variation.', 'pls-private-label-store' ),
                        __( 'Enable tiers that should be available for purchase.', 'pls-private-label-store' ),
                    ),
                    'validation' => array(
                        __( 'At least one pack tier must be enabled.', 'pls-private-label-store' ),
                        __( 'Units and price must be greater than 0.', 'pls-private-label-store' ),
                    ),
                ),
                'attributes' => array(
                    'title' => __( 'Product Options', 'pls-private-label-store' ),
                    'tips' => array(
                        __( 'Product options allow customers to customize their order.', 'pls-private-label-store' ),
                        __( 'Set tier-based pricing rules for each option value.', 'pls-private-label-store' ),
                        __( 'Options will sync to WooCommerce as product attributes.', 'pls-private-label-store' ),
                    ),
                ),
            );
        }

        // Bundle creation
        if ( 'bundles' === $page ) {
            $content = array(
                'create' => array(
                    'title' => __( 'Create Bundle', 'pls-private-label-store' ),
                    'tips' => array(
                        __( 'Bundles combine multiple products with special pricing.', 'pls-private-label-store' ),
                        __( 'SKU count is the number of different products in the bundle.', 'pls-private-label-store' ),
                        __( 'Units per SKU is the quantity for each product.', 'pls-private-label-store' ),
                        __( 'Cart will automatically detect when customers qualify for bundle pricing.', 'pls-private-label-store' ),
                    ),
                    'validation' => array(
                        __( 'Bundle name is required.', 'pls-private-label-store' ),
                        __( 'SKU count must be at least 2.', 'pls-private-label-store' ),
                        __( 'Units per SKU and price per unit must be greater than 0.', 'pls-private-label-store' ),
                    ),
                ),
            );
        }

        return $content;
    }

    /**
     * Get steps definition.
     *
     * @return array
     */
    /**
     * Get tutorial flow definition (sequential steps for guided tutorial).
     *
     * @return array Sequential tutorial steps.
     */
    public static function get_tutorial_flow() {
        return array(
            'attributes' => array(
                'step_number' => 1,
                'title' => __( 'Step 1: Product Options', 'pls-private-label-store' ),
                'page' => 'attributes',
                'steps' => array(
                    __( 'Review Pack Tier settings (units and prices)', 'pls-private-label-store' ),
                    __( 'Modify pricing if needed, or confirm defaults are OK', 'pls-private-label-store' ),
                    __( 'Review Package Type options (30ml, 50ml, 120ml, 50gr jar)', 'pls-private-label-store' ),
                    __( 'Review Package Color options (Standard, Frosted, Amber)', 'pls-private-label-store' ),
                    __( 'Review Package Cap options (Pump, Lid, Dropper)', 'pls-private-label-store' ),
                    __( 'Review Fragrances (available for Tier 3+)', 'pls-private-label-store' ),
                    __( 'Review Ingredients as product options', 'pls-private-label-store' ),
                ),
                'next_page' => 'products',
                'next_title' => __( 'Next: Create Your First Product', 'pls-private-label-store' ),
            ),
            'products' => array(
                'step_number' => 2,
                'title' => __( 'Step 2: Create Products', 'pls-private-label-store' ),
                'page' => 'products',
                'steps' => array(
                    __( 'Click "Add Product" button', 'pls-private-label-store' ),
                    __( 'Fill in General info: product name, select category, upload images', 'pls-private-label-store' ),
                    __( 'Configure Pack Tiers: enable/disable tiers, set units and prices', 'pls-private-label-store' ),
                    __( 'Select Product Options: Package Type, Color, and Cap', 'pls-private-label-store' ),
                    __( 'Add Fragrances (if using Tier 3 or higher)', 'pls-private-label-store' ),
                    __( 'Add Ingredients (if using Tier 3 or higher)', 'pls-private-label-store' ),
                    __( 'Configure Label Application settings', 'pls-private-label-store' ),
                    __( 'Save product and sync to WooCommerce', 'pls-private-label-store' ),
                    __( 'Understand sync states: Active, Inactive, Update Available, Not Synced', 'pls-private-label-store' ),
                    __( 'Use Activate/Deactivate buttons to control product visibility', 'pls-private-label-store' ),
                    __( 'Click [?] help buttons in modals for contextual guidance', 'pls-private-label-store' ),
                ),
                'next_page' => 'bundles',
                'next_title' => __( 'Next: Create Bundles', 'pls-private-label-store' ),
            ),
            'bundles' => array(
                'step_number' => 3,
                'title' => __( 'Step 3: Create Bundles', 'pls-private-label-store' ),
                'page' => 'bundles',
                'steps' => array(
                    __( 'Click "Create Bundle" button', 'pls-private-label-store' ),
                    __( 'Select bundle type (Mini Line, Starter Line, Growth Line, Premium Line)', 'pls-private-label-store' ),
                    __( 'Set SKU count (number of different products)', 'pls-private-label-store' ),
                    __( 'Set units per SKU (quantity for each product)', 'pls-private-label-store' ),
                    __( 'Configure price per unit', 'pls-private-label-store' ),
                    __( 'Set commission per unit', 'pls-private-label-store' ),
                    __( 'Save bundle and sync to WooCommerce', 'pls-private-label-store' ),
                    __( 'Understand that cart automatically detects bundle qualification', 'pls-private-label-store' ),
                ),
                'next_page' => 'categories',
                'next_title' => __( 'Next: Review Categories', 'pls-private-label-store' ),
            ),
            'categories' => array(
                'step_number' => 4,
                'title' => __( 'Step 4: Review Categories', 'pls-private-label-store' ),
                'page' => 'categories',
                'steps' => array(
                    __( 'Review existing product categories', 'pls-private-label-store' ),
                    __( 'Create a new category if needed for your products', 'pls-private-label-store' ),
                ),
                'next_page' => null,
                'next_title' => __( 'Tutorial Complete!', 'pls-private-label-store' ),
            ),
        );
    }

    /**
     * Get steps definition (legacy - for backward compatibility).
     *
     * @return array
     */
    public static function get_steps_definition() {
        // Return tutorial flow for active tutorial, otherwise return empty
        return self::get_tutorial_flow();
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

        // Redirect to first tutorial page (attributes)
        wp_send_json_success( array( 
            'message' => __( 'Tutorial started.', 'pls-private-label-store' ),
            'redirect' => admin_url( 'admin.php?page=pls-attributes' )
        ) );
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
        $mark_completed = isset( $_POST['mark_completed'] ) ? (bool) $_POST['mark_completed'] : true;

        if ( ! $page ) {
            wp_send_json_error( array( 'message' => __( 'Invalid page.', 'pls-private-label-store' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pls_onboarding_progress';

        $progress = self::get_progress( $user_id );
        $completed_steps = $progress && $progress->completed_steps ? json_decode( $progress->completed_steps, true ) : array();

        $step_key = $page . '_' . $step_index;
        if ( $mark_completed && ! in_array( $step_key, $completed_steps, true ) ) {
            $completed_steps[] = $step_key;
        } elseif ( ! $mark_completed && in_array( $step_key, $completed_steps, true ) ) {
            $completed_steps = array_values( array_diff( $completed_steps, array( $step_key ) ) );
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
     * AJAX: Get helper content for a page/section.
     */
    public static function get_helper_content_ajax() {
        check_ajax_referer( 'pls_onboarding_nonce', 'nonce' );

        $page = isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : '';
        $section = isset( $_POST['section'] ) ? sanitize_text_field( wp_unslash( $_POST['section'] ) ) : '';

        if ( empty( $page ) ) {
            wp_send_json_error( array( 'message' => __( 'Page parameter required.', 'pls-private-label-store' ) ), 400 );
        }

        $content = self::get_helper_content( $page, $section );

        if ( empty( $content ) ) {
            wp_send_json_error( array( 'message' => __( 'Helper content not found.', 'pls-private-label-store' ) ), 404 );
        }

        wp_send_json_success( array( 'content' => $content ) );
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
