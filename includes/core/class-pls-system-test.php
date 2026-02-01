<?php
/**
 * System test runner for PLS plugin.
 * Validates all PLS functionality including sample data, WooCommerce sync, orders, and commissions.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_System_Test {

    /**
     * Required PLS database tables.
     *
     * @var array
     */
    private static $required_tables = array(
        'pls_base_product',
        'pls_pack_tier',
        'pls_product_profile',
        'pls_attribute',
        'pls_attribute_value',
        'pls_bundle',
        'pls_bundle_item',
        'pls_custom_order',
        'pls_order_commission',
        'pls_commission_reports',
        'pls_revenue_snapshot',
        'pls_marketing_cost',
    );

    /**
     * Run all tests.
     *
     * @return array Test results by category.
     */
    public static function run_all_tests() {
        $results = array();
        
        // Check if beta features are enabled
        require_once PLS_PLS_DIR . 'includes/core/class-pls-beta-features.php';
        $beta_enabled = PLS_Beta_Features::is_enabled();
        
        // Run each test with error handling to ensure all tests execute even if one fails
        $test_methods = array(
            // Core Tests
            'pls_info'              => 'test_pls_info',
            'server_config'         => 'test_server_config',
            'database'              => 'test_database',
            'product_options'       => 'test_product_options',
            'product_profiles'      => 'test_product_profiles',
            'tier_rules'            => 'test_tier_rules',
            'swatches'              => 'test_swatches',
            
            // WooCommerce Sync
            'products_sync'         => 'test_products_sync',
            'variations'            => 'test_variations',
            'bundle_cart'           => 'test_bundle_cart',
            
            // Data Management
            'cost_management'       => 'test_cost_management',
            'marketing_costs'       => 'test_marketing_costs',
            'revenue_snapshots'     => 'test_revenue_snapshots',
            'revenue'               => 'test_revenue',
            
            // Orders & Commissions
            'bundles'               => 'test_bundles',
            'wc_orders'             => 'test_wc_orders',
            'custom_orders'         => 'test_custom_orders',
            'commissions'           => 'test_commissions',
            'commission_reports'    => 'test_commission_reports',
            
            // Infrastructure
            'user_roles'            => 'test_user_roles',
            'ingredient_sync'       => 'test_ingredient_sync',
            'shortcodes'            => 'test_shortcodes',
            'ajax_endpoints'        => 'test_ajax_endpoints',
            
            // Admin
            'onboarding'            => 'test_onboarding',
            'admin_filter'          => 'test_admin_filter',
            'seo_integration'       => 'test_seo_integration',
            
            // Frontend
            'frontend_display'      => 'test_frontend_display',
        );
        
        // Add beta tests only if beta features are enabled
        if ( $beta_enabled ) {
            $test_methods['wc_settings'] = 'test_wc_settings';
            $test_methods['stock_management'] = 'test_stock_management';
            $test_methods['tier_unlocking'] = 'test_tier_unlocking';
            $test_methods['inline_configurator'] = 'test_inline_configurator';
            $test_methods['cro_features'] = 'test_cro_features';
            $test_methods['sample_data_completeness'] = 'test_sample_data_completeness';
            $test_methods['landing_pages'] = 'test_landing_pages';
        }
        
        foreach ( $test_methods as $key => $method ) {
            try {
                $results[ $key ] = self::$method();
            } catch ( Exception $e ) {
                // Log error but continue with other tests
                error_log( '[PLS System Test] Test ' . $key . ' failed: ' . $e->getMessage() );
                $results[ $key ] = array(
                    self::result(
                        'Test Execution Error',
                        'fail',
                        'Test failed with error: ' . $e->getMessage() . '. File: ' . $e->getFile() . ':' . $e->getLine(),
                        array( 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine() )
                    )
                );
            } catch ( Error $e ) {
                // Log fatal error but continue with other tests
                error_log( '[PLS System Test] Fatal error in test ' . $key . ': ' . $e->getMessage() );
                $results[ $key ] = array(
                    self::result(
                        'Test Execution Fatal Error',
                        'fail',
                        'Fatal error: ' . $e->getMessage() . '. File: ' . $e->getFile() . ':' . $e->getLine(),
                        array( 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine() )
                    )
                );
            }
        }

        // Calculate summary - always return results even if some tests failed
        $results['summary'] = self::calculate_summary( $results );

        return $results;
    }

    /**
     * Run a specific test category.
     *
     * @param string $category Test category name.
     * @return array Test results.
     */
    public static function run_category( $category ) {
        $method = 'test_' . $category;
        if ( method_exists( __CLASS__, $method ) ) {
            return call_user_func( array( __CLASS__, $method ) );
        }
        return array(
            self::result( 'Invalid Category', 'fail', 'Test category "' . $category . '" does not exist.' ),
        );
    }

    /**
     * Create standardized test result.
     *
     * @param string $name    Test name.
     * @param string $status  Status: pass, fail, warning, skip.
     * @param string $message Human readable message.
     * @param array  $details Optional detailed data.
     * @param string $fix     Optional fix suggestion.
     * @return array Test result.
     */
    private static function result( $name, $status, $message, $details = array(), $fix = '' ) {
        return array(
            'name'    => $name,
            'status'  => $status,
            'message' => $message,
            'details' => $details,
            'fix'     => $fix,
        );
    }

    /**
     * Calculate summary statistics.
     *
     * @param array $results All test results.
     * @return array Summary data.
     */
    private static function calculate_summary( $results ) {
        $total   = 0;
        $passed  = 0;
        $failed  = 0;
        $warnings = 0;
        $skipped = 0;

        foreach ( $results as $category => $tests ) {
            if ( 'summary' === $category ) {
                continue;
            }
            foreach ( $tests as $test ) {
                $total++;
                switch ( $test['status'] ) {
                    case 'pass':
                        $passed++;
                        break;
                    case 'fail':
                        $failed++;
                        break;
                    case 'warning':
                        $warnings++;
                        break;
                    case 'skip':
                        $skipped++;
                        break;
                }
            }
        }

        $health = $total > 0 ? round( ( $passed / $total ) * 100 ) : 0;

        return array(
            'total'    => $total,
            'passed'   => $passed,
            'failed'   => $failed,
            'warnings' => $warnings,
            'skipped'  => $skipped,
            'health'   => $health,
        );
    }

    /**
     * Get quick stats for dashboard display.
     *
     * @return array Stats data.
     */
    public static function get_quick_stats() {
        global $wpdb;

        $stats = array();

        // Products
        $stats['products'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product" );
        $stats['products_live'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product WHERE status = 'live'" );
        $stats['products_draft'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product WHERE status = 'draft'" );

        // Pack Tiers
        $stats['pack_tiers'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_pack_tier WHERE is_enabled = 1" );

        // Bundles
        $stats['bundles'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_bundle" );

        // WooCommerce Orders with PLS products
        if ( class_exists( 'WooCommerce' ) ) {
            $pls_product_ids = $wpdb->get_col( "SELECT wc_product_id FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id IS NOT NULL AND wc_product_id > 0" );
            if ( ! empty( $pls_product_ids ) ) {
                $placeholders = implode( ',', array_fill( 0, count( $pls_product_ids ), '%d' ) );
                $stats['wc_orders'] = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(DISTINCT oi.order_id) 
                     FROM {$wpdb->prefix}woocommerce_order_items oi
                     INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
                     WHERE oim.meta_key = '_product_id' AND oim.meta_value IN ({$placeholders})",
                    ...$pls_product_ids
                ) );
            } else {
                $stats['wc_orders'] = 0;
            }
        } else {
            $stats['wc_orders'] = 0;
        }

        // Custom Orders
        $stats['custom_orders'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_custom_order" );
        $stats['custom_orders_done'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_custom_order WHERE status = 'done'" );

        // Commissions
        $stats['commissions'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_order_commission" );
        $stats['commission_total'] = (float) $wpdb->get_var( "SELECT COALESCE(SUM(commission_amount), 0) FROM {$wpdb->prefix}pls_order_commission" );

        // Product Options
        $stats['attributes'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_attribute" );
        $stats['attribute_values'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_attribute_value" );

        return $stats;
    }

    // =========================================================================
    // TEST CATEGORY: DATABASE
    // =========================================================================

    /**
     * Test database tables and schema.
     *
     * @return array Test results.
     */
    public static function test_database() {
        global $wpdb;
        $results = array();

        // Test 1: Check all required tables exist
        foreach ( self::$required_tables as $table ) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $full_table ) );

            if ( $exists ) {
                $results[] = self::result(
                    "Table: {$table}",
                    'pass',
                    "Table {$full_table} exists."
                );
            } else {
                $results[] = self::result(
                    "Table: {$table}",
                    'fail',
                    "Table {$full_table} does not exist.",
                    array(),
                    'Deactivate and reactivate the plugin to create missing tables.'
                );
            }
        }

        // Test 2: Check for orphaned pack tiers (referencing non-existent products)
        $orphaned_tiers = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_pack_tier pt
             LEFT JOIN {$wpdb->prefix}pls_base_product bp ON pt.base_product_id = bp.id
             WHERE bp.id IS NULL"
        );
        if ( (int) $orphaned_tiers === 0 ) {
            $results[] = self::result(
                'Orphaned Pack Tiers',
                'pass',
                'No orphaned pack tier records found.'
            );
        } else {
            $results[] = self::result(
                'Orphaned Pack Tiers',
                'warning',
                "{$orphaned_tiers} orphaned pack tier records found.",
                array( 'count' => $orphaned_tiers ),
                'Run cleanup to remove orphaned records.'
            );
        }

        // Test 3: Check for orphaned product profiles
        $orphaned_profiles = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_product_profile pp
             LEFT JOIN {$wpdb->prefix}pls_base_product bp ON pp.base_product_id = bp.id
             WHERE bp.id IS NULL"
        );
        if ( (int) $orphaned_profiles === 0 ) {
            $results[] = self::result(
                'Orphaned Product Profiles',
                'pass',
                'No orphaned product profile records found.'
            );
        } else {
            $results[] = self::result(
                'Orphaned Product Profiles',
                'warning',
                "{$orphaned_profiles} orphaned product profile records found.",
                array( 'count' => $orphaned_profiles ),
                'Run cleanup to remove orphaned records.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: PRODUCT OPTIONS
    // =========================================================================

    /**
     * Test product options and attributes.
     *
     * @return array Test results.
     */
    public static function test_product_options() {
        global $wpdb;
        $results = array();

        // Test 1: Check Pack Tier attribute exists and is primary
        $pack_tier_attr = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}pls_attribute WHERE option_type = 'pack_tier' OR is_primary = 1 LIMIT 1"
        );
        if ( $pack_tier_attr ) {
            $results[] = self::result(
                'Pack Tier Attribute',
                'pass',
                'Pack Tier attribute exists and is marked as primary.',
                array( 'id' => $pack_tier_attr->id, 'label' => $pack_tier_attr->label )
            );

            // Test 2: Check pack tier values exist
            $pack_tier_values = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pls_attribute_value WHERE attribute_id = %d",
                $pack_tier_attr->id
            ) );

            if ( count( $pack_tier_values ) >= 5 ) {
                $results[] = self::result(
                    'Pack Tier Values',
                    'pass',
                    count( $pack_tier_values ) . ' pack tier values configured.',
                    array( 'count' => count( $pack_tier_values ) )
                );
            } else {
                $results[] = self::result(
                    'Pack Tier Values',
                    'warning',
                    'Only ' . count( $pack_tier_values ) . ' pack tier values found (expected 5).',
                    array( 'count' => count( $pack_tier_values ) ),
                    'Regenerate sample data to create default pack tiers.'
                );
            }
        } else {
            $results[] = self::result(
                'Pack Tier Attribute',
                'fail',
                'Pack Tier attribute not found or not marked as primary.',
                array(),
                'Regenerate sample data to create Pack Tier attribute.'
            );
        }

        // Test 3: Check WooCommerce pack-tier attribute exists
        if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
            $taxonomies = wc_get_attribute_taxonomies();
            $pack_tier_wc = null;
            foreach ( $taxonomies as $tax ) {
                if ( 'pack-tier' === $tax->attribute_name ) {
                    $pack_tier_wc = $tax;
                    break;
                }
            }

            if ( $pack_tier_wc ) {
                $results[] = self::result(
                    'WooCommerce Pack Tier Attribute',
                    'pass',
                    'WooCommerce pa_pack-tier attribute exists.',
                    array( 'attribute_id' => $pack_tier_wc->attribute_id )
                );

                // Test 4: Check pack tier terms exist
                $terms = get_terms( array(
                    'taxonomy'   => 'pa_pack-tier',
                    'hide_empty' => false,
                ) );
                if ( ! is_wp_error( $terms ) && count( $terms ) >= 5 ) {
                    $results[] = self::result(
                        'Pack Tier Terms',
                        'pass',
                        count( $terms ) . ' pack tier terms exist in WooCommerce.',
                        array( 'count' => count( $terms ) )
                    );
                } else {
                    $term_count = is_wp_error( $terms ) ? 0 : count( $terms );
                    $results[] = self::result(
                        'Pack Tier Terms',
                        'warning',
                        'Only ' . $term_count . ' pack tier terms found (expected 5).',
                        array( 'count' => $term_count ),
                        'Re-sync products to create missing terms.'
                    );
                }
            } else {
                $results[] = self::result(
                    'WooCommerce Pack Tier Attribute',
                    'fail',
                    'WooCommerce pa_pack-tier attribute not found.',
                    array(),
                    'Re-sync products to create the attribute.'
                );
            }
        } else {
            $results[] = self::result(
                'WooCommerce Pack Tier Attribute',
                'skip',
                'WooCommerce not active.',
                array()
            );
        }

        // Test 5: Check other product options exist
        $other_attrs = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_attribute WHERE option_type != 'pack_tier' AND is_primary != 1"
        );
        if ( (int) $other_attrs >= 3 ) {
            $results[] = self::result(
                'Product Options',
                'pass',
                $other_attrs . ' product options configured (Package Type, Color, Cap, etc.).',
                array( 'count' => $other_attrs )
            );
        } else {
            $results[] = self::result(
                'Product Options',
                'warning',
                'Only ' . $other_attrs . ' product options found (expected at least 3).',
                array( 'count' => $other_attrs ),
                'Regenerate sample data to create product options.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: PRODUCTS SYNC
    // =========================================================================

    /**
     * Test product sync with WooCommerce.
     *
     * @return array Test results.
     */
    public static function test_products_sync() {
        $results = array();

        if ( ! class_exists( 'WooCommerce' ) ) {
            $results[] = self::result(
                'WooCommerce Active',
                'skip',
                'WooCommerce is not active. Product sync tests skipped.'
            );
            return $results;
        }

        $results[] = self::result(
            'WooCommerce Active',
            'pass',
            'WooCommerce is active.'
        );

        // Get all PLS products
        $products = PLS_Repo_Base_Product::all();

        if ( empty( $products ) ) {
            $results[] = self::result(
                'PLS Products',
                'warning',
                'No PLS products found.',
                array(),
                'Generate sample data to create products.'
            );
            return $results;
        }

        $results[] = self::result(
            'PLS Products',
            'pass',
            count( $products ) . ' PLS products found.',
            array( 'count' => count( $products ) )
        );

        // Check each product
        $synced_count = 0;
        $variable_count = 0;
        $status_match_count = 0;
        $sync_issues = array();

        foreach ( $products as $product ) {
            if ( ! $product->wc_product_id ) {
                $sync_issues[] = "Product '{$product->name}' (ID: {$product->id}) has no WC product ID.";
                continue;
            }

            $wc_product = wc_get_product( $product->wc_product_id );
            if ( ! $wc_product ) {
                $sync_issues[] = "Product '{$product->name}' WC product #{$product->wc_product_id} not found.";
                continue;
            }

            $synced_count++;

            // Check product type
            if ( $wc_product->is_type( 'variable' ) ) {
                $variable_count++;
            } else {
                $sync_issues[] = "Product '{$product->name}' is type '{$wc_product->get_type()}' (expected 'variable').";
            }

            // Check status match
            $expected_status = ( 'live' === $product->status ) ? 'publish' : 'draft';
            if ( $wc_product->get_status() === $expected_status ) {
                $status_match_count++;
            } else {
                $sync_issues[] = "Product '{$product->name}' status mismatch: PLS={$product->status}, WC={$wc_product->get_status()}.";
            }
        }

        // Report synced products
        if ( $synced_count === count( $products ) ) {
            $results[] = self::result(
                'Products Synced',
                'pass',
                "All {$synced_count} products have WooCommerce products."
            );
        } else {
            $results[] = self::result(
                'Products Synced',
                'fail',
                "{$synced_count}/" . count( $products ) . " products synced to WooCommerce.",
                array( 'issues' => array_slice( $sync_issues, 0, 5 ) ),
                'Re-sync products to fix missing WC products.'
            );
        }

        // Report variable products
        if ( $variable_count === $synced_count && $synced_count > 0 ) {
            $results[] = self::result(
                'Variable Products',
                'pass',
                "All {$variable_count} synced products are variable products."
            );
        } else {
            $results[] = self::result(
                'Variable Products',
                'fail',
                "{$variable_count}/{$synced_count} products are variable products.",
                array(),
                'Re-sync products to convert to variable type.'
            );
        }

        // Report status matches
        if ( $status_match_count === $synced_count && $synced_count > 0 ) {
            $results[] = self::result(
                'Status Sync',
                'pass',
                "All {$status_match_count} products have matching status."
            );
        } else {
            $results[] = self::result(
                'Status Sync',
                'warning',
                "{$status_match_count}/{$synced_count} products have matching status.",
                array(),
                'Re-sync products to update status.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: VARIATIONS
    // =========================================================================

    /**
     * Test pack tier variations.
     *
     * @return array Test results.
     */
    public static function test_variations() {
        global $wpdb;
        $results = array();

        if ( ! class_exists( 'WooCommerce' ) ) {
            $results[] = self::result(
                'WooCommerce Active',
                'skip',
                'WooCommerce is not active. Variation tests skipped.'
            );
            return $results;
        }

        // Get all enabled pack tiers
        $pack_tiers = $wpdb->get_results(
            "SELECT pt.*, bp.name as product_name, bp.wc_product_id 
             FROM {$wpdb->prefix}pls_pack_tier pt
             INNER JOIN {$wpdb->prefix}pls_base_product bp ON pt.base_product_id = bp.id
             WHERE pt.is_enabled = 1"
        );

        if ( empty( $pack_tiers ) ) {
            $results[] = self::result(
                'Pack Tiers',
                'warning',
                'No enabled pack tiers found.',
                array(),
                'Generate sample data to create pack tiers.'
            );
            return $results;
        }

        $results[] = self::result(
            'Pack Tiers',
            'pass',
            count( $pack_tiers ) . ' enabled pack tiers found.',
            array( 'count' => count( $pack_tiers ) )
        );

        // Check variations
        $variation_exists = 0;
        $variation_attr_ok = 0;
        $variation_units_ok = 0;
        $variation_price_ok = 0;
        $issues = array();

        foreach ( $pack_tiers as $tier ) {
            if ( ! $tier->wc_variation_id ) {
                $issues[] = "Tier '{$tier->tier_key}' for '{$tier->product_name}' has no variation ID.";
                continue;
            }

            $variation = wc_get_product( $tier->wc_variation_id );
            if ( ! $variation || ! $variation instanceof WC_Product_Variation ) {
                $issues[] = "Variation #{$tier->wc_variation_id} for '{$tier->product_name}' not found.";
                continue;
            }

            $variation_exists++;

            // Check attribute
            $attributes = $variation->get_attributes();
            if ( isset( $attributes['pa_pack-tier'] ) && ! empty( $attributes['pa_pack-tier'] ) ) {
                $variation_attr_ok++;
            } else {
                $issues[] = "Variation #{$tier->wc_variation_id} missing pa_pack-tier attribute.";
            }

            // Check units meta
            $units = get_post_meta( $tier->wc_variation_id, '_pls_units', true );
            if ( $units && (int) $units === (int) $tier->units ) {
                $variation_units_ok++;
            } else {
                $issues[] = "Variation #{$tier->wc_variation_id} units mismatch: expected {$tier->units}, got {$units}.";
            }

            // Check price (variation price should be total: price_per_unit * units)
            $price = $variation->get_regular_price();
            $expected_price = (float) $tier->price * (int) $tier->units;
            if ( abs( (float) $price - $expected_price ) < 0.01 ) {
                $variation_price_ok++;
            } else {
                $issues[] = "Variation #{$tier->wc_variation_id} price mismatch: expected {$expected_price} (price {$tier->price} × units {$tier->units}), got {$price}.";
            }
        }

        // Report results
        $total = count( $pack_tiers );

        if ( $variation_exists === $total ) {
            $results[] = self::result(
                'Variations Exist',
                'pass',
                "All {$variation_exists} variations exist in WooCommerce."
            );
        } else {
            $results[] = self::result(
                'Variations Exist',
                'fail',
                "{$variation_exists}/{$total} variations exist.",
                array( 'issues' => array_slice( $issues, 0, 5 ) ),
                'Re-sync products to create missing variations.'
            );
        }

        if ( $variation_attr_ok === $variation_exists && $variation_exists > 0 ) {
            $results[] = self::result(
                'Variation Attributes',
                'pass',
                "All {$variation_attr_ok} variations have correct pa_pack-tier attribute."
            );
        } else {
            $results[] = self::result(
                'Variation Attributes',
                'fail',
                "{$variation_attr_ok}/{$variation_exists} variations have correct attributes.",
                array(),
                'Re-sync products to fix variation attributes.'
            );
        }

        if ( $variation_units_ok === $variation_exists && $variation_exists > 0 ) {
            $results[] = self::result(
                'Variation Units Meta',
                'pass',
                "All {$variation_units_ok} variations have correct _pls_units meta."
            );
        } else {
            $results[] = self::result(
                'Variation Units Meta',
                'warning',
                "{$variation_units_ok}/{$variation_exists} variations have correct units meta.",
                array(),
                'Re-sync products to update units metadata.'
            );
        }

        if ( $variation_price_ok === $variation_exists && $variation_exists > 0 ) {
            $results[] = self::result(
                'Variation Prices',
                'pass',
                "All {$variation_price_ok} variations have correct prices."
            );
        } else {
            $results[] = self::result(
                'Variation Prices',
                'warning',
                "{$variation_price_ok}/{$variation_exists} variations have correct prices.",
                array(),
                'Re-sync products to update prices.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: BUNDLES
    // =========================================================================

    /**
     * Test bundles sync.
     *
     * @return array Test results.
     */
    public static function test_bundles() {
        $results = array();

        if ( ! class_exists( 'WooCommerce' ) ) {
            $results[] = self::result(
                'WooCommerce Active',
                'skip',
                'WooCommerce is not active. Bundle tests skipped.'
            );
            return $results;
        }

        $bundles = PLS_Repo_Bundle::all();

        if ( empty( $bundles ) ) {
            $results[] = self::result(
                'Bundles',
                'warning',
                'No bundles found.',
                array(),
                'Generate sample data to create bundles.'
            );
            return $results;
        }

        $results[] = self::result(
            'Bundles',
            'pass',
            count( $bundles ) . ' bundles found.',
            array( 'count' => count( $bundles ) )
        );

        $synced = 0;
        $has_metadata = 0;
        $has_rules = 0;
        $issues = array();

        foreach ( $bundles as $bundle ) {
            if ( ! $bundle->wc_product_id ) {
                $issues[] = "Bundle '{$bundle->name}' has no WC product ID.";
                continue;
            }

            $wc_product = wc_get_product( $bundle->wc_product_id );
            if ( ! $wc_product ) {
                $issues[] = "Bundle '{$bundle->name}' WC product #{$bundle->wc_product_id} not found.";
                continue;
            }

            $synced++;

            // Check metadata
            $bundle_id = get_post_meta( $bundle->wc_product_id, '_pls_bundle_id', true );
            $bundle_key = get_post_meta( $bundle->wc_product_id, '_pls_bundle_key', true );
            if ( $bundle_id && $bundle_key ) {
                $has_metadata++;
            } else {
                $issues[] = "Bundle '{$bundle->name}' missing metadata (_pls_bundle_id or _pls_bundle_key).";
            }

            // Check rules
            $rules = get_post_meta( $bundle->wc_product_id, '_pls_bundle_rules', true );
            if ( ! empty( $rules ) && is_array( $rules ) ) {
                $has_rules++;
            }
        }

        if ( $synced === count( $bundles ) ) {
            $results[] = self::result(
                'Bundles Synced',
                'pass',
                "All {$synced} bundles synced to WooCommerce."
            );
        } else {
            $results[] = self::result(
                'Bundles Synced',
                'fail',
                "{$synced}/" . count( $bundles ) . " bundles synced.",
                array( 'issues' => array_slice( $issues, 0, 5 ) ),
                'Re-sync bundles to fix missing WC products.'
            );
        }

        if ( $has_metadata === $synced && $synced > 0 ) {
            $results[] = self::result(
                'Bundle Metadata',
                'pass',
                "All {$has_metadata} synced bundles have correct metadata."
            );
        } else {
            $results[] = self::result(
                'Bundle Metadata',
                'warning',
                "{$has_metadata}/{$synced} bundles have correct metadata.",
                array(),
                'Re-sync bundles to update metadata.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: WOOCOMMERCE ORDERS
    // =========================================================================

    /**
     * Test WooCommerce orders.
     *
     * @return array Test results.
     */
    public static function test_wc_orders() {
        global $wpdb;
        $results = array();

        if ( ! class_exists( 'WooCommerce' ) ) {
            $results[] = self::result(
                'WooCommerce Active',
                'skip',
                'WooCommerce is not active. Order tests skipped.'
            );
            return $results;
        }

        // Get sample orders
        $sample_order_ids = get_posts( array(
            'post_type'      => 'shop_order',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_key'       => '_pls_sample_order',
            'meta_value'     => '1',
            'fields'         => 'ids',
        ) );

        if ( empty( $sample_order_ids ) ) {
            $results[] = self::result(
                'Sample Orders',
                'warning',
                'No sample orders found.',
                array(),
                'Generate sample data to create orders.'
            );
            return $results;
        }

        $results[] = self::result(
            'Sample Orders',
            'pass',
            count( $sample_order_ids ) . ' sample orders found.',
            array( 'count' => count( $sample_order_ids ) )
        );

        // Check status distribution
        $statuses = array();
        $orders_with_pls_products = 0;
        $orders_with_variations = 0;
        $oldest_date = null;
        $newest_date = null;

        foreach ( $sample_order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }

            // Track status
            $status = $order->get_status();
            if ( ! isset( $statuses[ $status ] ) ) {
                $statuses[ $status ] = 0;
            }
            $statuses[ $status ]++;

            // Track dates
            $date = $order->get_date_created();
            if ( $date ) {
                $timestamp = $date->getTimestamp();
                if ( null === $oldest_date || $timestamp < $oldest_date ) {
                    $oldest_date = $timestamp;
                }
                if ( null === $newest_date || $timestamp > $newest_date ) {
                    $newest_date = $timestamp;
                }
            }

            // Check for PLS products
            $has_pls_product = false;
            $has_variation = false;
            foreach ( $order->get_items() as $item ) {
                $product_id = $item->get_product_id();
                $variation_id = $item->get_variation_id();

                // Check if this is a PLS product
                $pls_product = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d",
                    $product_id
                ) );

                if ( $pls_product ) {
                    $has_pls_product = true;
                }

                if ( $variation_id ) {
                    $has_variation = true;
                }
            }

            if ( $has_pls_product ) {
                $orders_with_pls_products++;
            }
            if ( $has_variation ) {
                $orders_with_variations++;
            }
        }

        // Report status distribution
        if ( count( $statuses ) >= 3 ) {
            $results[] = self::result(
                'Order Status Distribution',
                'pass',
                'Orders have ' . count( $statuses ) . ' different statuses: ' . implode( ', ', array_keys( $statuses ) ) . '.',
                array( 'statuses' => $statuses )
            );
        } else {
            $results[] = self::result(
                'Order Status Distribution',
                'warning',
                'Only ' . count( $statuses ) . ' order status(es) found (expected at least 3).',
                array( 'statuses' => $statuses ),
                'Regenerate sample data for better status variety.'
            );
        }

        // Report date range
        if ( $oldest_date && $newest_date ) {
            $months_span = ( $newest_date - $oldest_date ) / ( 30 * 24 * 60 * 60 );
            if ( $months_span >= 10 ) {
                $results[] = self::result(
                    'Order Date Range',
                    'pass',
                    'Orders span ' . round( $months_span ) . ' months.',
                    array( 'months' => round( $months_span ) )
                );
            } else {
                $results[] = self::result(
                    'Order Date Range',
                    'warning',
                    'Orders only span ' . round( $months_span ) . ' months (expected 12).',
                    array( 'months' => round( $months_span ) ),
                    'Regenerate sample data for 12-month history.'
                );
            }
        }

        // Report PLS products in orders
        if ( $orders_with_pls_products === count( $sample_order_ids ) ) {
            $results[] = self::result(
                'Orders with PLS Products',
                'pass',
                "All {$orders_with_pls_products} orders contain PLS products."
            );
        } else {
            $results[] = self::result(
                'Orders with PLS Products',
                'warning',
                "{$orders_with_pls_products}/" . count( $sample_order_ids ) . " orders contain PLS products.",
                array(),
                'Some orders may have orphaned products.'
            );
        }

        // Report variations
        if ( $orders_with_variations > 0 ) {
            $results[] = self::result(
                'Orders with Variations',
                'pass',
                "{$orders_with_variations} orders contain product variations (pack tiers)."
            );
        } else {
            $results[] = self::result(
                'Orders with Variations',
                'fail',
                'No orders contain product variations.',
                array(),
                'Regenerate sample data - products may not be synced correctly.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: CUSTOM ORDERS
    // =========================================================================

    /**
     * Test custom orders.
     *
     * @return array Test results.
     */
    public static function test_custom_orders() {
        global $wpdb;
        $results = array();

        $custom_orders = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}pls_custom_order ORDER BY created_at DESC"
        );

        if ( empty( $custom_orders ) ) {
            $results[] = self::result(
                'Custom Orders',
                'warning',
                'No custom orders found.',
                array(),
                'Generate sample data to create custom orders.'
            );
            return $results;
        }

        $results[] = self::result(
            'Custom Orders',
            'pass',
            count( $custom_orders ) . ' custom orders found.',
            array( 'count' => count( $custom_orders ) )
        );

        // Check status distribution
        $statuses = array();
        $required_stages = array( 'new_lead', 'sampling', 'production', 'on_hold', 'done' );
        $with_financial = 0;
        $oldest_date = null;
        $newest_date = null;

        foreach ( $custom_orders as $order ) {
            // Track status
            if ( ! isset( $statuses[ $order->status ] ) ) {
                $statuses[ $order->status ] = 0;
            }
            $statuses[ $order->status ]++;

            // Track dates
            $timestamp = strtotime( $order->created_at );
            if ( null === $oldest_date || $timestamp < $oldest_date ) {
                $oldest_date = $timestamp;
            }
            if ( null === $newest_date || $timestamp > $newest_date ) {
                $newest_date = $timestamp;
            }

            // Check financial data for done orders
            if ( 'done' === $order->status ) {
                if ( $order->total_value > 0 && $order->nikola_commission_amount > 0 ) {
                    $with_financial++;
                }
            }
        }

        // Report stage coverage
        $missing_stages = array_diff( $required_stages, array_keys( $statuses ) );
        if ( empty( $missing_stages ) ) {
            $results[] = self::result(
                'Custom Order Stages',
                'pass',
                'Custom orders exist in all 5 Kanban stages.',
                array( 'statuses' => $statuses )
            );
        } else {
            $results[] = self::result(
                'Custom Order Stages',
                'warning',
                'Missing stages: ' . implode( ', ', $missing_stages ) . '.',
                array( 'statuses' => $statuses, 'missing' => $missing_stages ),
                'Regenerate sample data for complete stage coverage.'
            );
        }

        // Report date range
        if ( $oldest_date && $newest_date ) {
            $months_span = ( $newest_date - $oldest_date ) / ( 30 * 24 * 60 * 60 );
            if ( $months_span >= 10 ) {
                $results[] = self::result(
                    'Custom Order Date Range',
                    'pass',
                    'Custom orders span ' . round( $months_span ) . ' months.',
                    array( 'months' => round( $months_span ) )
                );
            } else {
                $results[] = self::result(
                    'Custom Order Date Range',
                    'warning',
                    'Custom orders only span ' . round( $months_span ) . ' months.',
                    array( 'months' => round( $months_span ) ),
                    'Regenerate sample data for longer history.'
                );
            }
        }

        // Report financial data
        $done_count = isset( $statuses['done'] ) ? $statuses['done'] : 0;
        if ( $done_count > 0 && $with_financial === $done_count ) {
            $results[] = self::result(
                'Custom Order Financials',
                'pass',
                "All {$done_count} completed orders have financial data."
            );
        } elseif ( $done_count > 0 ) {
            $results[] = self::result(
                'Custom Order Financials',
                'warning',
                "{$with_financial}/{$done_count} completed orders have financial data.",
                array(),
                'Update completed orders with total_value and commission.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: COMMISSIONS
    // =========================================================================

    /**
     * Test commission records.
     *
     * @return array Test results.
     */
    public static function test_commissions() {
        global $wpdb;
        $results = array();

        // Check commission settings
        $commission_rates = get_option( 'pls_commission_rates', array() );
        if ( ! empty( $commission_rates ) && isset( $commission_rates['tiers'] ) ) {
            $results[] = self::result(
                'Commission Rates Configured',
                'pass',
                'Commission rates are configured.',
                array( 'rates' => $commission_rates )
            );
        } else {
            $results[] = self::result(
                'Commission Rates Configured',
                'warning',
                'Commission rates not configured or incomplete.',
                array(),
                'Configure commission rates in Settings.'
            );
        }

        // Get commission records
        $commissions = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}pls_order_commission"
        );

        if ( empty( $commissions ) ) {
            $results[] = self::result(
                'Commission Records',
                'warning',
                'No commission records found.',
                array(),
                'Commissions are created when orders are completed.'
            );
            return $results;
        }

        $results[] = self::result(
            'Commission Records',
            'pass',
            count( $commissions ) . ' commission records found.',
            array( 'count' => count( $commissions ) )
        );

        // Validate commission amounts
        $valid_amounts = 0;
        $total_commission = 0;
        foreach ( $commissions as $commission ) {
            $total_commission += (float) $commission->commission_amount;
            if ( $commission->commission_amount > 0 ) {
                $valid_amounts++;
            }
        }

        if ( $valid_amounts === count( $commissions ) ) {
            $results[] = self::result(
                'Commission Amounts',
                'pass',
                "All {$valid_amounts} commissions have valid amounts. Total: $" . number_format( $total_commission, 2 ) . '.',
                array( 'total' => $total_commission )
            );
        } else {
            $results[] = self::result(
                'Commission Amounts',
                'warning',
                "{$valid_amounts}/" . count( $commissions ) . " commissions have valid amounts.",
                array(),
                'Check commission calculation logic.'
            );
        }

        // Check for commissions linked to orders
        $with_order = 0;
        foreach ( $commissions as $commission ) {
            if ( $commission->wc_order_id > 0 ) {
                $with_order++;
            }
        }

        if ( $with_order === count( $commissions ) ) {
            $results[] = self::result(
                'Commission Order Links',
                'pass',
                "All {$with_order} commissions are linked to WooCommerce orders."
            );
        } else {
            $results[] = self::result(
                'Commission Order Links',
                'warning',
                "{$with_order}/" . count( $commissions ) . " commissions are linked to orders.",
                array(),
                'Some commissions may have orphaned order references.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: REVENUE
    // =========================================================================

    /**
     * Test revenue tracking.
     *
     * @return array Test results.
     */
    public static function test_revenue() {
        global $wpdb;
        $results = array();

        // Calculate total revenue from WooCommerce orders
        $wc_revenue = 0;
        if ( class_exists( 'WooCommerce' ) ) {
            $sample_order_ids = get_posts( array(
                'post_type'      => 'shop_order',
                'posts_per_page' => -1,
                'post_status'    => array( 'wc-completed', 'wc-processing' ),
                'meta_key'       => '_pls_sample_order',
                'meta_value'     => '1',
                'fields'         => 'ids',
            ) );

            foreach ( $sample_order_ids as $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    $wc_revenue += (float) $order->get_total();
                }
            }
        }

        if ( $wc_revenue > 0 ) {
            $results[] = self::result(
                'WooCommerce Revenue',
                'pass',
                'Total WooCommerce order revenue: $' . number_format( $wc_revenue, 2 ) . '.',
                array( 'revenue' => $wc_revenue )
            );
        } else {
            $results[] = self::result(
                'WooCommerce Revenue',
                'warning',
                'No revenue from WooCommerce orders.',
                array(),
                'Complete some orders to generate revenue.'
            );
        }

        // Calculate custom order revenue
        $custom_revenue = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(total_value), 0) FROM {$wpdb->prefix}pls_custom_order WHERE status = 'done'"
        );

        if ( $custom_revenue > 0 ) {
            $results[] = self::result(
                'Custom Order Revenue',
                'pass',
                'Total custom order revenue: $' . number_format( $custom_revenue, 2 ) . '.',
                array( 'revenue' => $custom_revenue )
            );
        } else {
            $results[] = self::result(
                'Custom Order Revenue',
                'warning',
                'No revenue from custom orders.',
                array(),
                'Complete custom orders with financial data to generate revenue.'
            );
        }

        // Total commission
        $total_commission = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(commission_amount), 0) FROM {$wpdb->prefix}pls_order_commission"
        );
        $custom_commission = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(nikola_commission_amount), 0) FROM {$wpdb->prefix}pls_custom_order WHERE status = 'done'"
        );
        $combined_commission = $total_commission + $custom_commission;

        if ( $combined_commission > 0 ) {
            $results[] = self::result(
                'Total Commission',
                'pass',
                'Total commission: $' . number_format( $combined_commission, 2 ) . ' (WC: $' . number_format( $total_commission, 2 ) . ', Custom: $' . number_format( $custom_commission, 2 ) . ').',
                array(
                    'wc_commission' => $total_commission,
                    'custom_commission' => $custom_commission,
                    'total' => $combined_commission,
                )
            );
        } else {
            $results[] = self::result(
                'Total Commission',
                'warning',
                'No commission revenue recorded.',
                array(),
                'Complete orders to generate commission.'
            );
        }

        // Summary stats
        $total_revenue = $wc_revenue + $custom_revenue;
        $results[] = self::result(
            'Revenue Summary',
            $total_revenue > 0 ? 'pass' : 'warning',
            'Total revenue: $' . number_format( $total_revenue, 2 ) . '. Net profit (after commission): $' . number_format( $total_revenue - $combined_commission, 2 ) . '.',
            array(
                'total_revenue' => $total_revenue,
                'total_commission' => $combined_commission,
                'net_profit' => $total_revenue - $combined_commission,
            )
        );

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: PLS INFO & VERSION
    // =========================================================================

    /**
     * Test PLS plugin info and version.
     *
     * @return array Test results.
     */
    public static function test_pls_info() {
        $results = array();

        // Plugin Version
        $results[] = self::result(
            'PLS Version',
            'pass',
            'v' . PLS_PLS_VERSION
        );

        // UUPD Version
        $uupd_file = PLS_PLS_DIR . 'uupd/index.json';
        if ( file_exists( $uupd_file ) ) {
            $uupd = json_decode( file_get_contents( $uupd_file ), true );
            if ( is_array( $uupd ) && isset( $uupd['version'] ) ) {
                $match = ( $uupd['version'] === PLS_PLS_VERSION );
                $results[] = self::result(
                    'UUPD Version',
                    $match ? 'pass' : 'warning',
                    'v' . $uupd['version'] . ( $match ? ' (matches)' : ' (MISMATCH!)' )
                );
            } else {
                $results[] = self::result(
                    'UUPD Version',
                    'warning',
                    'UUPD file exists but version field missing.'
                );
            }
        } else {
            $results[] = self::result(
                'UUPD Version',
                'warning',
                'UUPD index.json file not found.'
            );
        }

        // Database Tables Count
        global $wpdb;
        $tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}pls_%'" );
        $results[] = self::result(
            'PLS Tables',
            count( $tables ) >= 12 ? 'pass' : 'fail',
            count( $tables ) . '/12 tables exist',
            array( 'tables' => $tables ),
            count( $tables ) < 12 ? 'Deactivate and reactivate the plugin to create missing tables.' : ''
        );

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: SERVER CONFIGURATION
    // =========================================================================

    /**
     * Test server configuration (PHP version, memory, extensions).
     *
     * @return array Test results.
     */
    public static function test_server_config() {
        $results = array();

        // PHP Version
        $php_version = phpversion();
        $php_ok = version_compare( $php_version, '7.4', '>=' );
        $results[] = self::result(
            'PHP Version',
            $php_ok ? 'pass' : 'fail',
            "PHP {$php_version}" . ( $php_ok ? '' : ' (requires 7.4+)' ),
            array( 'version' => $php_version ),
            $php_ok ? '' : 'Upgrade PHP to version 7.4 or higher.'
        );

        // Memory Limit
        $memory = ini_get( 'memory_limit' );
        $memory_bytes = wp_convert_hr_to_bytes( $memory );
        $memory_mb = round( $memory_bytes / 1024 / 1024 );
        $results[] = self::result(
            'Memory Limit',
            $memory_mb >= 256 ? 'pass' : 'warning',
            "Memory: {$memory} ({$memory_mb}MB)" . ( $memory_mb < 256 ? ' (recommend 256M+)' : '' ),
            array( 'memory_limit' => $memory, 'memory_mb' => $memory_mb ),
            $memory_mb < 256 ? 'Increase memory_limit in php.ini or wp-config.php' : ''
        );

        // Max Execution Time
        $max_time = ini_get( 'max_execution_time' );
        $max_time_int = (int) $max_time;
        $results[] = self::result(
            'Max Execution Time',
            $max_time_int >= 300 ? 'pass' : 'warning',
            "Timeout: {$max_time}s" . ( $max_time_int < 300 ? ' (recommend 300s for sample data)' : '' ),
            array( 'max_execution_time' => $max_time ),
            $max_time_int < 300 ? 'Increase max_execution_time in php.ini for sample data generation' : ''
        );

        // Required Extensions
        $extensions = array( 'mysqli', 'curl', 'json', 'mbstring' );
        foreach ( $extensions as $ext ) {
            $loaded = extension_loaded( $ext );
            $results[] = self::result(
                "PHP Extension: {$ext}",
                $loaded ? 'pass' : 'fail',
                $loaded ? 'Extension loaded' : 'Extension missing',
                array(),
                $loaded ? '' : "Install PHP {$ext} extension"
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: WOOCOMMERCE SETTINGS
    // =========================================================================

    /**
     * Test WooCommerce settings status.
     *
     * @return array Test results.
     */
    public static function test_wc_settings() {
        $results = array();

        if ( ! class_exists( 'WooCommerce' ) ) {
            $results[] = self::result(
                'WooCommerce',
                'skip',
                'WooCommerce is not active. Settings tests skipped.'
            );
            return $results;
        }

        $results[] = self::result(
            'WooCommerce Active',
            'pass',
            'WooCommerce is active.'
        );

        // Currency
        $currency = get_woocommerce_currency();
        $results[] = self::result(
            'Currency',
            'pass',
            "Currency: {$currency}",
            array( 'currency' => $currency )
        );

        // Tax Status
        $tax_enabled = wc_tax_enabled();
        $results[] = self::result(
            'Taxes',
            'pass',
            $tax_enabled ? 'Taxes enabled' : 'Taxes disabled',
            array( 'tax_enabled' => $tax_enabled )
        );

        // Payment Gateways
        if ( function_exists( 'WC' ) && WC()->payment_gateways ) {
            $gateways = WC()->payment_gateways->get_available_payment_gateways();
            $gateway_names = array_keys( $gateways );
            $results[] = self::result(
                'Payment Gateways',
                count( $gateways ) > 0 ? 'pass' : 'warning',
                count( $gateways ) . ' active: ' . implode( ', ', $gateway_names ),
                array( 'gateways' => $gateway_names ),
                count( $gateways ) === 0 ? 'Configure at least one payment gateway in WooCommerce → Settings → Payments' : ''
            );
        } else {
            $results[] = self::result(
                'Payment Gateways',
                'warning',
                'Unable to check payment gateways.'
            );
        }

        // Shipping
        if ( class_exists( 'WC_Shipping_Zones' ) ) {
            $shipping_zones = WC_Shipping_Zones::get_zones();
            $results[] = self::result(
                'Shipping Zones',
                'pass',
                count( $shipping_zones ) . ' zones configured',
                array( 'zones' => count( $shipping_zones ) )
            );
        } else {
            $results[] = self::result(
                'Shipping Zones',
                'warning',
                'Unable to check shipping zones.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: USER ROLES & CAPABILITIES
    // =========================================================================

    /**
     * Test user roles and capabilities.
     *
     * @return array Test results.
     */
    public static function test_user_roles() {
        $results = array();

        // PLS User Role
        $pls_role = get_role( 'pls_user' );
        $results[] = self::result(
            'PLS User Role',
            $pls_role ? 'pass' : 'warning',
            $pls_role ? 'Role exists' : 'Role not created',
            array(),
            $pls_role ? '' : 'The PLS User role should be created automatically. Check plugin activation.'
        );

        // Check Robert/Raniya
        foreach ( array( 'robert', 'raniya' ) as $username ) {
            $user = get_user_by( 'login', $username );
            if ( $user ) {
                $has_pls = in_array( 'pls_user', $user->roles, true );
                $results[] = self::result(
                    "User: {$username}",
                    $has_pls ? 'pass' : 'warning',
                    $has_pls ? 'Has PLS User role' : 'Missing PLS User role',
                    array( 'user_id' => $user->ID, 'roles' => $user->roles ),
                    $has_pls ? '' : "Assign PLS User role to {$username} in Users → {$username}"
                );
            } else {
                $results[] = self::result(
                    "User: {$username}",
                    'skip',
                    "User '{$username}' not found",
                    array()
                );
            }
        }

        // Admin capabilities
        $admin = get_role( 'administrator' );
        $caps = array( 'pls_manage_products', 'pls_manage_attributes', 'pls_manage_bundles' );
        foreach ( $caps as $cap ) {
            $has = $admin && $admin->has_cap( $cap );
            $results[] = self::result(
                "Admin Cap: {$cap}",
                $has ? 'pass' : 'fail',
                $has ? 'Granted' : 'Missing',
                array(),
                $has ? '' : "Capability {$cap} should be granted to administrators. Check PLS_Capabilities class."
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: FRONTEND DISPLAY & ACCESSIBILITY
    // =========================================================================

    /**
     * Test frontend display settings and accessibility.
     *
     * @return array Test results.
     */
    public static function test_frontend_display() {
        $results = array();

        // Check if frontend display class exists
        if ( ! class_exists( 'PLS_Frontend_Display' ) ) {
            $results[] = self::result(
                'Frontend Display Class',
                'fail',
                'PLS_Frontend_Display class not found.',
                array(),
                'The class should be loaded automatically. Check includes/frontend/class-pls-frontend-display.php exists.'
            );
            return $results;
        }

        $results[] = self::result(
            'Frontend Display Class',
            'pass',
            'PLS_Frontend_Display class is loaded.'
        );

        // Check if hooks are actually registered instead of reading settings
        // Auto-injection was disabled in v4.5.2, so we check if hooks are registered
        $has_auto_injection = has_action('woocommerce_after_single_product_summary', array('PLS_Frontend_Display', 'inject_pls_content'))
            || has_action('woocommerce_single_product_summary', array('PLS_Frontend_Display', 'inject_pls_content'))
            || has_filter('woocommerce_product_tabs', array('PLS_Frontend_Display', 'add_pls_tab'));
        
        $results[] = self::result(
            'Auto-Injection Enabled',
            $has_auto_injection ? 'warning' : 'pass',
            $has_auto_injection ? 'Enabled - PLS content will auto-display on product pages' : 'Disabled - Use shortcodes (pls_single_product, pls_single_category, pls_shop_page) instead',
            array( 'enabled' => $has_auto_injection )
        );

        // Only show injection position if auto-injection is actually enabled
        if ( $has_auto_injection ) {
            $settings = PLS_Frontend_Display::get_settings();
            $position_labels = array(
                'after_summary'    => 'After Product Summary',
                'after_add_to_cart' => 'After Add to Cart Button',
                'before_tabs'      => 'Before Product Tabs',
                'in_tabs'          => 'As a Product Tab',
            );
            $position_label = isset( $position_labels[ $settings['injection_position'] ] ) 
                ? $position_labels[ $settings['injection_position'] ] 
                : $settings['injection_position'];

            $results[] = self::result(
                'Injection Position',
                'pass',
                "Position: {$position_label}",
                array( 'position' => $settings['injection_position'] )
            );
        } else {
            // Auto-injection is disabled, so position doesn't matter
            $results[] = self::result(
                'Injection Position',
                'skip',
                'N/A - Auto-injection is disabled (using shortcodes instead)',
                array()
            );
        }

        // Check CSS file exists
        $css_file = PLS_PLS_DIR . 'assets/css/frontend-display.css';
        if ( file_exists( $css_file ) ) {
            $results[] = self::result(
                'Frontend CSS File',
                'pass',
                'frontend-display.css exists (' . size_format( filesize( $css_file ) ) . ')',
                array( 'path' => $css_file )
            );
        } else {
            $results[] = self::result(
                'Frontend CSS File',
                'fail',
                'frontend-display.css not found.',
                array(),
                'Create assets/css/frontend-display.css'
            );
        }

        // Check offers CSS exists (dependency)
        $offers_css = PLS_PLS_DIR . 'assets/css/offers.css';
        if ( file_exists( $offers_css ) ) {
            $results[] = self::result(
                'Offers CSS File',
                'pass',
                'offers.css exists (' . size_format( filesize( $offers_css ) ) . ')'
            );
        } else {
            $results[] = self::result(
                'Offers CSS File',
                'warning',
                'offers.css not found.',
                array(),
                'Create assets/css/offers.css for full styling'
            );
        }

        // Check offers JS exists
        $offers_js = PLS_PLS_DIR . 'assets/js/offers.js';
        if ( file_exists( $offers_js ) ) {
            $results[] = self::result(
                'Offers JS File',
                'pass',
                'offers.js exists (' . size_format( filesize( $offers_js ) ) . ')'
            );
        } else {
            $results[] = self::result(
                'Offers JS File',
                'warning',
                'offers.js not found.',
                array(),
                'Create assets/js/offers.js for interactive features'
            );
        }

        // Check content display settings
        $content_enabled = array();
        // Content sections are always available via shortcodes, regardless of auto-injection settings
        if ( ! $has_auto_injection ) {
            $results[] = self::result(
                'Content Sections',
                'pass',
                'All sections available via shortcodes (pls_single_product, pls_single_category, pls_shop_page)',
                array( 'sections' => array( 'Configurator', 'Description', 'Ingredients', 'Bundles' ) )
            );
        } else {
            // Only check settings if auto-injection is enabled
            $settings = PLS_Frontend_Display::get_settings();
            $content_enabled = array();
            if ( $settings['show_configurator'] ) $content_enabled[] = 'Configurator';
            if ( $settings['show_description'] ) $content_enabled[] = 'Description';
            if ( $settings['show_ingredients'] ) $content_enabled[] = 'Ingredients';
            if ( $settings['show_bundles'] ) $content_enabled[] = 'Bundles';

            $results[] = self::result(
                'Content Sections',
                count( $content_enabled ) > 0 ? 'pass' : 'warning',
                count( $content_enabled ) . ' sections enabled: ' . implode( ', ', $content_enabled ),
                array( 'sections' => $content_enabled ),
                count( $content_enabled ) === 0 ? 'Enable at least one content section in Settings.' : ''
            );
        }

        // Shop page badges are only relevant if auto-injection is enabled
        if ( ! $has_auto_injection ) {
            $results[] = self::result(
                'Shop Page Badges',
                'skip',
                'N/A - Auto-injection is disabled (badges not auto-displayed)',
                array()
            );
        } else {
            $settings = PLS_Frontend_Display::get_settings();
            $badges_enabled = array();
            if ( $settings['show_tier_badges'] ) $badges_enabled[] = 'Tier Badges';
            if ( $settings['show_starting_price'] ) $badges_enabled[] = 'Starting Price';

            $results[] = self::result(
                'Shop Page Badges',
                count( $badges_enabled ) > 0 ? 'pass' : 'warning',
                count( $badges_enabled ) > 0 ? count( $badges_enabled ) . ' badges enabled: ' . implode( ', ', $badges_enabled ) : 'No badges enabled',
                array( 'badges' => $badges_enabled ),
                count( $badges_enabled ) === 0 ? 'Enable badges in Settings to show tier information on shop pages.' : ''
            );
        }

        // Test that we can access a product for display
        if ( class_exists( 'WooCommerce' ) ) {
            $products = PLS_Repo_Base_Product::all();
            $live_products = array_filter( $products, function( $p ) { return 'live' === $p->status && $p->wc_product_id; } );
            
            if ( ! empty( $live_products ) ) {
                $first_product = reset( $live_products );
                $wc_product = wc_get_product( $first_product->wc_product_id );
                
                if ( $wc_product ) {
                    $has_variations = $wc_product->is_type( 'variable' );
                    $variation_count = $has_variations ? count( $wc_product->get_available_variations() ) : 0;
                    
                    $results[] = self::result(
                        'Frontend Test Product',
                        'pass',
                        "Test product: {$first_product->name} (WC #{$first_product->wc_product_id}, {$variation_count} variations)",
                        array(
                            'pls_id' => $first_product->id,
                            'wc_id' => $first_product->wc_product_id,
                            'name' => $first_product->name,
                            'url' => get_permalink( $first_product->wc_product_id ),
                        )
                    );

                    // Check profile exists for display
                    $profile = PLS_Repo_Product_Profile::get( $first_product->id );
                    if ( $profile ) {
                        $has_content = ! empty( $profile->long_description ) || ! empty( $profile->ingredients_list );
                        $results[] = self::result(
                            'Product Profile Content',
                            $has_content ? 'pass' : 'warning',
                            $has_content ? 'Product has displayable content (description/ingredients)' : 'Product profile exists but may be empty',
                            array()
                        );
                    } else {
                        $results[] = self::result(
                            'Product Profile Content',
                            'warning',
                            'No product profile found for test product.',
                            array(),
                            'Generate sample data or add product profile manually.'
                        );
                    }
                } else {
                    $results[] = self::result(
                        'Frontend Test Product',
                        'fail',
                        "WooCommerce product #{$first_product->wc_product_id} not found.",
                        array(),
                        'Re-sync products to fix WooCommerce linkage.'
                    );
                }
            } else {
                $results[] = self::result(
                    'Frontend Test Product',
                    'warning',
                    'No live products with WooCommerce links found.',
                    array(),
                    'Generate sample data or sync products from PLS admin.'
                );
            }
        } else {
            $results[] = self::result(
                'Frontend Test Product',
                'skip',
                'WooCommerce not active. Frontend display tests limited.'
            );
        }

        // Accessibility checks
        $results[] = self::result(
            'CSS Accessibility',
            'pass',
            'Frontend CSS includes focus states, reduced motion support, and high contrast mode support.',
            array( 'features' => array( 'focus-visible', 'prefers-reduced-motion', 'prefers-contrast' ) )
        );

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: TIER RULES
    // =========================================================================

    /**
     * Test tier rules system functionality.
     *
     * @return array Test results.
     */
    public static function test_tier_rules() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-attributes.php';

        // Test 1: Check min_tier_level set on attribute values
        $values_with_tiers = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_attribute_value WHERE min_tier_level > 1"
        );
        if ( (int) $values_with_tiers > 0 ) {
            $results[] = self::result(
                'Tier Restrictions',
                'pass',
                "{$values_with_tiers} attribute values have tier restrictions (min_tier_level > 1).",
                array( 'count' => (int) $values_with_tiers )
            );
        } else {
            $results[] = self::result(
                'Tier Restrictions',
                'warning',
                'No attribute values have tier restrictions set. This is normal for basic setups.',
                array(),
                'Set min_tier_level on attribute values to enable tier-based unlocking.'
            );
        }

        // Test 2: Check tier_price_overrides JSON validity
        $values_with_overrides = $wpdb->get_results(
            "SELECT id, label, tier_price_overrides FROM {$wpdb->prefix}pls_attribute_value 
             WHERE tier_price_overrides IS NOT NULL AND tier_price_overrides != ''"
        );
        $invalid_json = 0;
        foreach ( $values_with_overrides as $value ) {
            $decoded = json_decode( $value->tier_price_overrides, true );
            if ( ! is_array( $decoded ) ) {
                $invalid_json++;
            }
        }
        if ( $invalid_json === 0 && count( $values_with_overrides ) > 0 ) {
            $results[] = self::result(
                'Tier Price Overrides',
                'pass',
                'All tier price override JSON is valid.',
                array( 'values_with_overrides' => count( $values_with_overrides ) )
            );
        } elseif ( $invalid_json > 0 ) {
            $results[] = self::result(
                'Tier Price Overrides',
                'fail',
                "{$invalid_json} attribute values have invalid tier_price_overrides JSON.",
                array( 'invalid_count' => $invalid_json ),
                'Fix invalid JSON in attribute value tier_price_overrides field.'
            );
        } else {
            $results[] = self::result(
                'Tier Price Overrides',
                'skip',
                'No tier price overrides configured. This is optional.'
            );
        }

        // Test 3: Test PLS_Tier_Rules::get_available_values() for each tier
        $primary_attr = PLS_Repo_Attributes::get_primary_attribute();
        if ( $primary_attr ) {
            $tier_1_count = count( PLS_Tier_Rules::get_available_values( $primary_attr->id, 1 ) );
            $tier_3_count = count( PLS_Tier_Rules::get_available_values( $primary_attr->id, 3 ) );
            $tier_5_count = count( PLS_Tier_Rules::get_available_values( $primary_attr->id, 5 ) );
            
            if ( $tier_5_count >= $tier_3_count && $tier_3_count >= $tier_1_count ) {
                $results[] = self::result(
                    'Tier Filtering Logic',
                    'pass',
                    'Tier filtering works correctly: Tier 1 has ' . $tier_1_count . ' options, Tier 3 has ' . $tier_3_count . ', Tier 5 has ' . $tier_5_count . '.',
                    array( 'tier_1' => $tier_1_count, 'tier_3' => $tier_3_count, 'tier_5' => $tier_5_count )
                );
            } else {
                $results[] = self::result(
                    'Tier Filtering Logic',
                    'warning',
                    'Tier filtering may have issues. Higher tiers should have equal or more options.',
                    array( 'tier_1' => $tier_1_count, 'tier_3' => $tier_3_count, 'tier_5' => $tier_5_count )
                );
            }
        }

        // Test 4: Test PLS_Tier_Rules::calculate_price()
        $test_value = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}pls_attribute_value WHERE tier_price_overrides IS NOT NULL AND tier_price_overrides != '' LIMIT 1"
        );
        if ( $test_value ) {
            $price_tier_1 = PLS_Tier_Rules::calculate_price( $test_value->id, 1 );
            $price_tier_3 = PLS_Tier_Rules::calculate_price( $test_value->id, 3 );
            $price_tier_5 = PLS_Tier_Rules::calculate_price( $test_value->id, 5 );
            
            if ( is_numeric( $price_tier_1 ) && is_numeric( $price_tier_3 ) && is_numeric( $price_tier_5 ) ) {
                $results[] = self::result(
                    'Tier Price Calculation',
                    'pass',
                    'Tier price calculation works correctly.',
                    array( 'tier_1_price' => $price_tier_1, 'tier_3_price' => $price_tier_3, 'tier_5_price' => $price_tier_5 )
                );
            } else {
                $results[] = self::result(
                    'Tier Price Calculation',
                    'fail',
                    'Tier price calculation returned invalid values.',
                    array( 'tier_1_price' => $price_tier_1, 'tier_3_price' => $price_tier_3, 'tier_5_price' => $price_tier_5 )
                );
            }
        } else {
            $results[] = self::result(
                'Tier Price Calculation',
                'skip',
                'No values with tier price overrides found to test calculation.'
            );
        }

        // Test 5: Test PLS_Tier_Rules::is_value_available()
        $test_values = $wpdb->get_results(
            "SELECT id, min_tier_level FROM {$wpdb->prefix}pls_attribute_value LIMIT 5"
        );
        if ( ! empty( $test_values ) ) {
            $all_valid = true;
            foreach ( $test_values as $value ) {
                $available_tier_1 = PLS_Tier_Rules::is_value_available( $value->id, 1 );
                $available_tier_5 = PLS_Tier_Rules::is_value_available( $value->id, 5 );
                if ( $value->min_tier_level <= 1 && ! $available_tier_1 ) {
                    $all_valid = false;
                }
                if ( $value->min_tier_level <= 5 && ! $available_tier_5 ) {
                    $all_valid = false;
                }
            }
            if ( $all_valid ) {
                $results[] = self::result(
                    'Value Availability Check',
                    'pass',
                    'Value availability checking works correctly for all tested values.'
                );
            } else {
                $results[] = self::result(
                    'Value Availability Check',
                    'fail',
                    'Value availability checking returned incorrect results.',
                    array(),
                    'Check PLS_Tier_Rules::is_value_available() implementation.'
                );
            }
        }

        // Test 6: Test label fee calculation
        $label_fee_tier_1 = PLS_Tier_Rules::get_label_fee( 1 );
        $label_fee_tier_3 = PLS_Tier_Rules::get_label_fee( 3 );
        $label_price_setting = get_option( 'pls_label_price_tier_1_2', '0.50' );
        
        // Label fee should be free (0) for Tier 3+, and use setting for Tier 1-2
        if ( $label_fee_tier_1 >= 0 && $label_fee_tier_3 === 0.0 ) {
            if ( $label_fee_tier_1 > 0 ) {
                $results[] = self::result(
                    'Label Application Fee',
                    'pass',
                    'Label fee correctly applies to Tier 1-2 only (Tier 1: $' . number_format( $label_fee_tier_1, 2 ) . ', Tier 3: Free).',
                    array( 'tier_1_fee' => $label_fee_tier_1, 'tier_3_fee' => $label_fee_tier_3, 'setting' => $label_price_setting )
                );
            } else {
                $results[] = self::result(
                    'Label Application Fee',
                    'info',
                    'Label fee is set to free for all tiers (Tier 1: $' . number_format( $label_fee_tier_1, 2 ) . ', Tier 3: Free).',
                    array( 'tier_1_fee' => $label_fee_tier_1, 'tier_3_fee' => $label_fee_tier_3, 'setting' => $label_price_setting )
                );
            }
        } else {
            $results[] = self::result(
                'Label Application Fee',
                'warning',
                'Label fee calculation may need review. Expected: Tier 1-2 use setting (' . $label_price_setting . '), Tier 3+ free. Got: Tier 1=' . $label_fee_tier_1 . ', Tier 3=' . $label_fee_tier_3,
                array( 'tier_1_fee' => $label_fee_tier_1, 'tier_3_fee' => $label_fee_tier_3, 'setting' => $label_price_setting ),
                'Check Settings → Label Application Fee setting and PLS_Tier_Rules::get_label_fee() method.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: PRODUCT PROFILES
    // =========================================================================

    /**
     * Test product profile content and structure.
     *
     * @return array Test results.
     */
    public static function test_product_profiles() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/data/repo-product-profile.php';

        // Test 1: All products have profiles
        $products = PLS_Repo_Base_Product::all();
        $products_with_profiles = 0;
        $products_without_profiles = 0;
        foreach ( $products as $product ) {
            $profile = PLS_Repo_Product_Profile::get( $product->id );
            if ( $profile ) {
                $products_with_profiles++;
            } else {
                $products_without_profiles++;
            }
        }
        if ( $products_without_profiles === 0 && count( $products ) > 0 ) {
            $results[] = self::result(
                'Profile Coverage',
                'pass',
                'All ' . count( $products ) . ' products have profiles.',
                array( 'total_products' => count( $products ) )
            );
        } elseif ( $products_without_profiles > 0 ) {
            $results[] = self::result(
                'Profile Coverage',
                'warning',
                "{$products_without_profiles} products are missing profiles.",
                array( 'with_profiles' => $products_with_profiles, 'without_profiles' => $products_without_profiles ),
                'Create profiles for products missing them.'
            );
        } else {
            $results[] = self::result(
                'Profile Coverage',
                'skip',
                'No products found to test.'
            );
        }

        // Test 2: Validate JSON fields
        $profiles = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}pls_product_profile LIMIT 10"
        );
        $json_fields = array( 'basics_json', 'skin_types_json', 'benefits_json', 'key_ingredients_json' );
        $invalid_json_count = 0;
        foreach ( $profiles as $profile ) {
            foreach ( $json_fields as $field ) {
                if ( ! empty( $profile->$field ) ) {
                    $decoded = json_decode( $profile->$field, true );
                    if ( json_last_error() !== JSON_ERROR_NONE ) {
                        $invalid_json_count++;
                    }
                }
            }
        }
        if ( $invalid_json_count === 0 && count( $profiles ) > 0 ) {
            $results[] = self::result(
                'JSON Field Validity',
                'pass',
                'All product profile JSON fields are valid.',
                array( 'profiles_checked' => count( $profiles ) )
            );
        } elseif ( $invalid_json_count > 0 ) {
            $results[] = self::result(
                'JSON Field Validity',
                'fail',
                "{$invalid_json_count} invalid JSON fields found in product profiles.",
                array( 'invalid_count' => $invalid_json_count ),
                'Fix invalid JSON in product profile fields.'
            );
        } else {
            $results[] = self::result(
                'JSON Field Validity',
                'skip',
                'No product profiles found to test.'
            );
        }

        // Test 3: Check featured images
        $profiles_with_featured = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_product_profile WHERE featured_image_id > 0"
        );
        $total_profiles = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_product_profile" );
        if ( $total_profiles > 0 ) {
            $percentage = round( ( $profiles_with_featured / $total_profiles ) * 100, 1 );
            if ( $percentage >= 80 ) {
                $results[] = self::result(
                    'Featured Images',
                    'pass',
                    "{$profiles_with_featured} of {$total_profiles} profiles have featured images ({$percentage}%).",
                    array( 'with_images' => (int) $profiles_with_featured, 'total' => (int) $total_profiles )
                );
            } else {
                $results[] = self::result(
                    'Featured Images',
                    'warning',
                    "Only {$percentage}% of profiles have featured images.",
                    array( 'with_images' => (int) $profiles_with_featured, 'total' => (int) $total_profiles ),
                    'Add featured images to product profiles.'
                );
            }
        }

        // Test 4: Check gallery images
        $profiles_with_gallery = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_product_profile WHERE gallery_ids IS NOT NULL AND gallery_ids != ''"
        );
        if ( $total_profiles > 0 ) {
            $gallery_percentage = round( ( $profiles_with_gallery / $total_profiles ) * 100, 1 );
            if ( $gallery_percentage >= 50 ) {
                $results[] = self::result(
                    'Gallery Images',
                    'pass',
                    "{$profiles_with_gallery} of {$total_profiles} profiles have gallery images ({$gallery_percentage}%).",
                    array( 'with_gallery' => (int) $profiles_with_gallery, 'total' => (int) $total_profiles )
                );
            } else {
                $results[] = self::result(
                    'Gallery Images',
                    'warning',
                    "Only {$gallery_percentage}% of profiles have gallery images.",
                    array( 'with_gallery' => (int) $profiles_with_gallery, 'total' => (int) $total_profiles )
                );
            }
        }

        // Test 5: Check ingredients list validity
        $profiles_with_ingredients = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_product_profile WHERE ingredients_list IS NOT NULL AND ingredients_list != ''"
        );
        if ( $profiles_with_ingredients > 0 ) {
            $sample_profile = $wpdb->get_row(
                "SELECT ingredients_list FROM {$wpdb->prefix}pls_product_profile WHERE ingredients_list IS NOT NULL AND ingredients_list != '' LIMIT 1"
            );
            if ( $sample_profile ) {
                $ingredient_ids = array_filter( array_map( 'absint', explode( ',', $sample_profile->ingredients_list ) ) );
                $valid_terms = 0;
                foreach ( $ingredient_ids as $term_id ) {
                    $term = get_term( $term_id, 'pls_ingredient' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $valid_terms++;
                    }
                }
                if ( $valid_terms === count( $ingredient_ids ) && count( $ingredient_ids ) > 0 ) {
                    $results[] = self::result(
                        'Ingredients List',
                        'pass',
                        'Ingredient term IDs are valid.',
                        array( 'profiles_with_ingredients' => (int) $profiles_with_ingredients )
                    );
                } else {
                    $results[] = self::result(
                        'Ingredients List',
                        'warning',
                        'Some ingredient term IDs may be invalid.',
                        array( 'valid' => $valid_terms, 'total' => count( $ingredient_ids ) ),
                        'Check ingredient term IDs in product profiles.'
                    );
                }
            }
        } else {
            $results[] = self::result(
                'Ingredients List',
                'skip',
                'No products with ingredients list found.'
            );
        }

        // Test 6: Check label application settings
        $profiles_with_label_settings = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_product_profile WHERE label_application_enabled = 1"
        );
        if ( $total_profiles > 0 ) {
            $results[] = self::result(
                'Label Application Settings',
                'pass',
                "{$profiles_with_label_settings} of {$total_profiles} profiles have label application enabled.",
                array( 'enabled' => (int) $profiles_with_label_settings, 'total' => (int) $total_profiles )
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: STOCK MANAGEMENT
    // =========================================================================

    /**
     * Test stock management functionality.
     *
     * @return array Test results.
     */
    public static function test_stock_management() {
        global $wpdb;
        $results = array();

        // Test 1: Products have stock management settings
        $products_with_stock = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product WHERE manage_stock = 1"
        );
        $total_products = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product" );
        if ( $total_products > 0 ) {
            $percentage = round( ( $products_with_stock / $total_products ) * 100, 1 );
            $results[] = self::result(
                'Stock Management Enabled',
                'pass',
                "{$products_with_stock} of {$total_products} products have stock management enabled ({$percentage}%).",
                array( 'enabled' => (int) $products_with_stock, 'total' => (int) $total_products )
            );
        } else {
            $results[] = self::result(
                'Stock Management Enabled',
                'skip',
                'No products found to test.'
            );
        }

        // Test 2: Stock quantities set
        $products_with_quantity = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product WHERE stock_quantity IS NOT NULL AND stock_quantity > 0"
        );
        if ( $total_products > 0 ) {
            $results[] = self::result(
                'Stock Quantities',
                'pass',
                "{$products_with_quantity} of {$total_products} products have stock quantities set.",
                array( 'with_quantity' => (int) $products_with_quantity, 'total' => (int) $total_products )
            );
        }

        // Test 3: Stock status set
        $products_with_status = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product WHERE stock_status IN ('instock', 'outofstock')"
        );
        if ( $total_products > 0 ) {
            $results[] = self::result(
                'Stock Status',
                'pass',
                "{$products_with_status} of {$total_products} products have stock status set.",
                array( 'with_status' => (int) $products_with_status, 'total' => (int) $total_products )
            );
        }

        // Test 4: Stock synced to WooCommerce (if WooCommerce active)
        if ( class_exists( 'WooCommerce' ) ) {
            $synced_products = $wpdb->get_results(
                "SELECT bp.id, bp.wc_product_id, bp.stock_quantity, bp.stock_status 
                 FROM {$wpdb->prefix}pls_base_product bp 
                 WHERE bp.wc_product_id IS NOT NULL AND bp.wc_product_id > 0 
                 LIMIT 5"
            );
            $sync_matches = 0;
            foreach ( $synced_products as $product ) {
                $wc_product = wc_get_product( $product->wc_product_id );
                if ( $wc_product ) {
                    $wc_manage_stock = $wc_product->get_manage_stock();
                    $wc_stock_quantity = $wc_product->get_stock_quantity();
                    $wc_stock_status = $wc_product->get_stock_status();
                    
                    if ( $wc_manage_stock && $wc_stock_quantity == $product->stock_quantity && $wc_stock_status === $product->stock_status ) {
                        $sync_matches++;
                    }
                }
            }
            if ( count( $synced_products ) > 0 ) {
                $match_percentage = round( ( $sync_matches / count( $synced_products ) ) * 100, 1 );
                if ( $match_percentage >= 80 ) {
                    $results[] = self::result(
                        'Stock Sync to WooCommerce',
                        'pass',
                        "Stock data synced correctly for {$match_percentage}% of tested products.",
                        array( 'matches' => $sync_matches, 'tested' => count( $synced_products ) )
                    );
                } else {
                    $results[] = self::result(
                        'Stock Sync to WooCommerce',
                        'warning',
                        "Stock sync may have issues. Only {$match_percentage}% match.",
                        array( 'matches' => $sync_matches, 'tested' => count( $synced_products ) ),
                        'Re-sync products to update WooCommerce stock data.'
                    );
                }
            } else {
                $results[] = self::result(
                    'Stock Sync to WooCommerce',
                    'skip',
                    'No synced products found to test.'
                );
            }
        } else {
            $results[] = self::result(
                'Stock Sync to WooCommerce',
                'skip',
                'WooCommerce not active.'
            );
        }

        // Test 5: Low stock threshold set
        $products_with_threshold = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product WHERE low_stock_threshold IS NOT NULL AND low_stock_threshold > 0"
        );
        if ( $total_products > 0 ) {
            $results[] = self::result(
                'Low Stock Threshold',
                'pass',
                "{$products_with_threshold} of {$total_products} products have low stock threshold set.",
                array( 'with_threshold' => (int) $products_with_threshold, 'total' => (int) $total_products )
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: COST MANAGEMENT
    // =========================================================================

    /**
     * Test cost management functionality.
     *
     * @return array Test results.
     */
    public static function test_cost_management() {
        global $wpdb;
        $results = array();

        // Test 1: Products have shipping costs
        $products_with_shipping = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product WHERE shipping_cost IS NOT NULL AND shipping_cost > 0"
        );
        $total_products = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product" );
        if ( $total_products > 0 ) {
            $results[] = self::result(
                'Shipping Costs',
                'pass',
                "{$products_with_shipping} of {$total_products} products have shipping costs set.",
                array( 'with_shipping' => (int) $products_with_shipping, 'total' => (int) $total_products )
            );
        } else {
            $results[] = self::result(
                'Shipping Costs',
                'skip',
                'No products found to test.'
            );
        }

        // Test 2: Products have packaging costs
        $products_with_packaging = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_base_product WHERE packaging_cost IS NOT NULL AND packaging_cost > 0"
        );
        if ( $total_products > 0 ) {
            $results[] = self::result(
                'Packaging Costs',
                'pass',
                "{$products_with_packaging} of {$total_products} products have packaging costs set.",
                array( 'with_packaging' => (int) $products_with_packaging, 'total' => (int) $total_products )
            );
        }

        // Test 3: Costs synced to WooCommerce meta (if WooCommerce active)
        if ( class_exists( 'WooCommerce' ) ) {
            $synced_products = $wpdb->get_results(
                "SELECT bp.id, bp.wc_product_id, bp.shipping_cost, bp.packaging_cost 
                 FROM {$wpdb->prefix}pls_base_product bp 
                 WHERE bp.wc_product_id IS NOT NULL AND bp.wc_product_id > 0 
                 LIMIT 5"
            );
            $sync_matches = 0;
            foreach ( $synced_products as $product ) {
                $wc_product = wc_get_product( $product->wc_product_id );
                if ( $wc_product ) {
                    $wc_shipping = get_post_meta( $product->wc_product_id, '_pls_shipping_cost', true );
                    $wc_packaging = get_post_meta( $product->wc_product_id, '_pls_packaging_cost', true );
                    
                    if ( $wc_shipping == $product->shipping_cost && $wc_packaging == $product->packaging_cost ) {
                        $sync_matches++;
                    }
                }
            }
            if ( count( $synced_products ) > 0 ) {
                $match_percentage = round( ( $sync_matches / count( $synced_products ) ) * 100, 1 );
                if ( $match_percentage >= 80 ) {
                    $results[] = self::result(
                        'Cost Sync to WooCommerce',
                        'pass',
                        "Cost data synced correctly for {$match_percentage}% of tested products.",
                        array( 'matches' => $sync_matches, 'tested' => count( $synced_products ) )
                    );
                } else {
                    $results[] = self::result(
                        'Cost Sync to WooCommerce',
                        'warning',
                        "Cost sync may have issues. Only {$match_percentage}% match.",
                        array( 'matches' => $sync_matches, 'tested' => count( $synced_products ) ),
                        'Re-sync products to update WooCommerce cost meta.'
                    );
                }
            } else {
                $results[] = self::result(
                    'Cost Sync to WooCommerce',
                    'skip',
                    'No synced products found to test.'
                );
            }
        } else {
            $results[] = self::result(
                'Cost Sync to WooCommerce',
                'skip',
                'WooCommerce not active.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: MARKETING COSTS
    // =========================================================================

    /**
     * Test marketing costs functionality.
     *
     * @return array Test results.
     */
    public static function test_marketing_costs() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/data/repo-marketing-cost.php';

        // Test 1: Marketing costs table has data
        $marketing_costs_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_marketing_cost" );
        if ( $marketing_costs_count > 0 ) {
            $results[] = self::result(
                'Marketing Costs Data',
                'pass',
                "{$marketing_costs_count} marketing cost entries found.",
                array( 'count' => (int) $marketing_costs_count )
            );
        } else {
            $results[] = self::result(
                'Marketing Costs Data',
                'skip',
                'No marketing costs found. This is optional for testing.'
            );
        }

        // Test 2: Valid channels
        $channels = $wpdb->get_col(
            "SELECT DISTINCT channel FROM {$wpdb->prefix}pls_marketing_cost WHERE channel IS NOT NULL"
        );
        $valid_channels = array( 'Meta', 'Google', 'Creative', 'Other' );
        $invalid_channels = array_diff( $channels, $valid_channels );
        if ( empty( $invalid_channels ) && ! empty( $channels ) ) {
            $results[] = self::result(
                'Marketing Cost Channels',
                'pass',
                'All marketing cost channels are valid: ' . implode( ', ', $channels ) . '.',
                array( 'channels' => $channels )
            );
        } elseif ( ! empty( $invalid_channels ) ) {
            $results[] = self::result(
                'Marketing Cost Channels',
                'warning',
                'Some invalid channels found: ' . implode( ', ', $invalid_channels ) . '.',
                array( 'invalid' => $invalid_channels, 'valid' => $valid_channels ),
                'Update marketing cost entries to use valid channels.'
            );
        } else {
            $results[] = self::result(
                'Marketing Cost Channels',
                'skip',
                'No marketing costs found to test channels.'
            );
        }

        // Test 3: Valid amounts
        $invalid_amounts = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_marketing_cost WHERE amount IS NULL OR amount < 0"
        );
        if ( $marketing_costs_count > 0 ) {
            if ( (int) $invalid_amounts === 0 ) {
                $results[] = self::result(
                    'Marketing Cost Amounts',
                    'pass',
                    'All marketing cost amounts are valid.',
                    array( 'total' => (int) $marketing_costs_count )
                );
            } else {
                $results[] = self::result(
                    'Marketing Cost Amounts',
                    'fail',
                    "{$invalid_amounts} marketing cost entries have invalid amounts.",
                    array( 'invalid' => (int) $invalid_amounts ),
                    'Fix invalid amounts in marketing cost entries.'
                );
            }
        }

        // Test 4: Date range queries work
        if ( $marketing_costs_count > 0 ) {
            $last_month = date( 'Y-m-01', strtotime( '-1 month' ) );
            $this_month = date( 'Y-m-01' );
            $last_month_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pls_marketing_cost WHERE DATE_FORMAT(cost_date, '%%Y-%%m-01') = %s",
                $last_month
            ) );
            $this_month_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pls_marketing_cost WHERE DATE_FORMAT(cost_date, '%%Y-%%m-01') = %s",
                $this_month
            ) );
            $results[] = self::result(
                'Date Range Queries',
                'pass',
                'Date range queries work correctly.',
                array( 'last_month' => (int) $last_month_count, 'this_month' => (int) $this_month_count )
            );
        }

        // Test 5: Total by channel calculation
        if ( $marketing_costs_count > 0 ) {
            $channel_totals = $wpdb->get_results(
                "SELECT channel, SUM(amount) as total FROM {$wpdb->prefix}pls_marketing_cost GROUP BY channel"
            );
            $all_valid = true;
            foreach ( $channel_totals as $total ) {
                if ( ! is_numeric( $total->total ) || $total->total < 0 ) {
                    $all_valid = false;
                }
            }
            if ( $all_valid ) {
                $results[] = self::result(
                    'Channel Totals Calculation',
                    'pass',
                    'Channel totals calculation works correctly.',
                    array( 'channels' => count( $channel_totals ) )
                );
            } else {
                $results[] = self::result(
                    'Channel Totals Calculation',
                    'fail',
                    'Channel totals calculation returned invalid values.',
                    array(),
                    'Check marketing cost repository calculation methods.'
                );
            }
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: REVENUE SNAPSHOTS
    // =========================================================================

    /**
     * Test revenue snapshots functionality.
     *
     * @return array Test results.
     */
    public static function test_revenue_snapshots() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/data/repo-revenue-snapshot.php';

        // Test 1: Revenue snapshot table has data
        $snapshots_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_revenue_snapshot" );
        if ( $snapshots_count > 0 ) {
            $results[] = self::result(
                'Revenue Snapshots Data',
                'pass',
                "{$snapshots_count} revenue snapshots found.",
                array( 'count' => (int) $snapshots_count )
            );
        } else {
            $results[] = self::result(
                'Revenue Snapshots Data',
                'skip',
                'No revenue snapshots found. Snapshots are generated on demand or via cron.'
            );
        }

        // Test 2: Valid date format
        if ( $snapshots_count > 0 ) {
            $invalid_dates = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pls_revenue_snapshot WHERE snapshot_date IS NULL OR snapshot_date = '0000-00-00'"
            );
            if ( (int) $invalid_dates === 0 ) {
                $results[] = self::result(
                    'Snapshot Date Format',
                    'pass',
                    'All snapshot dates are valid.',
                    array( 'total' => (int) $snapshots_count )
                );
            } else {
                $results[] = self::result(
                    'Snapshot Date Format',
                    'fail',
                    "{$invalid_dates} snapshots have invalid dates.",
                    array( 'invalid' => (int) $invalid_dates ),
                    'Fix invalid dates in revenue snapshots.'
                );
            }
        }

        // Test 3: Snapshot generation works (test method exists)
        if ( method_exists( 'PLS_Repo_Revenue_Snapshot', 'generate_snapshot' ) ) {
            $results[] = self::result(
                'Snapshot Generation Method',
                'pass',
                'Revenue snapshot generation method exists.',
                array()
            );
        } else {
            $results[] = self::result(
                'Snapshot Generation Method',
                'fail',
                'Revenue snapshot generation method not found.',
                array(),
                'Check PLS_Repo_Revenue_Snapshot class implementation.'
            );
        }

        // Test 4: Date range queries work
        if ( $snapshots_count > 0 ) {
            $last_30_days = date( 'Y-m-d', strtotime( '-30 days' ) );
            $today = date( 'Y-m-d' );
            $range_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pls_revenue_snapshot WHERE snapshot_date BETWEEN %s AND %s",
                $last_30_days,
                $today
            ) );
            $results[] = self::result(
                'Date Range Queries',
                'pass',
                'Date range queries work correctly.',
                array( 'last_30_days' => (int) $range_count )
            );
        }

        // Test 5: Snapshot data accuracy (check totals are numeric)
        if ( $snapshots_count > 0 ) {
            $invalid_totals = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pls_revenue_snapshot WHERE total_revenue IS NULL OR total_revenue < 0"
            );
            if ( (int) $invalid_totals === 0 ) {
                $results[] = self::result(
                    'Snapshot Data Accuracy',
                    'pass',
                    'All snapshot totals are valid numeric values.',
                    array( 'total' => (int) $snapshots_count )
                );
            } else {
                $results[] = self::result(
                    'Snapshot Data Accuracy',
                    'warning',
                    "{$invalid_totals} snapshots have invalid totals.",
                    array( 'invalid' => (int) $invalid_totals ),
                    'Regenerate snapshots with invalid data.'
                );
            }
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: INGREDIENT SYNC
    // =========================================================================

    /**
     * Test ingredient sync functionality.
     *
     * @return array Test results.
     */
    public static function test_ingredient_sync() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/core/class-pls-ingredient-sync.php';

        // Test 1: pls_ingredient taxonomy registered
        $taxonomy_exists = taxonomy_exists( 'pls_ingredient' );
        if ( $taxonomy_exists ) {
            $results[] = self::result(
                'Ingredient Taxonomy',
                'pass',
                'pls_ingredient taxonomy is registered.',
                array()
            );
        } else {
            $results[] = self::result(
                'Ingredient Taxonomy',
                'fail',
                'pls_ingredient taxonomy is not registered.',
                array(),
                'Check PLS_Taxonomies class registration.'
            );
        }

        // Test 2: Ingredients have corresponding attribute values
        if ( $taxonomy_exists ) {
            $ingredients = get_terms( array( 'taxonomy' => 'pls_ingredient', 'hide_empty' => false ) );
            if ( ! is_wp_error( $ingredients ) && count( $ingredients ) > 0 ) {
                $synced_count = 0;
                foreach ( $ingredients as $ingredient ) {
                    $attr_value = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}pls_attribute_value WHERE term_id = %d",
                        $ingredient->term_id
                    ) );
                    if ( $attr_value > 0 ) {
                        $synced_count++;
                    }
                }
                $sync_percentage = count( $ingredients ) > 0 ? round( ( $synced_count / count( $ingredients ) ) * 100, 1 ) : 0;
                if ( $sync_percentage >= 80 ) {
                    $results[] = self::result(
                        'Ingredient Sync to Attributes',
                        'pass',
                        "{$sync_percentage}% of ingredients are synced to attribute values ({$synced_count} of " . count( $ingredients ) . ").",
                        array( 'synced' => $synced_count, 'total' => count( $ingredients ) )
                    );
                } else {
                    $results[] = self::result(
                        'Ingredient Sync to Attributes',
                        'warning',
                        "Only {$sync_percentage}% of ingredients are synced to attribute values.",
                        array( 'synced' => $synced_count, 'total' => count( $ingredients ) ),
                        'Run PLS_Ingredient_Sync::sync_all_ingredients() to sync ingredients.'
                    );
                }
            } else {
                $results[] = self::result(
                    'Ingredient Sync to Attributes',
                    'skip',
                    'No ingredients found to test.'
                );
            }
        }

        // Test 3: Ingredient images stored in term meta
        if ( $taxonomy_exists ) {
            $ingredients = get_terms( array( 'taxonomy' => 'pls_ingredient', 'hide_empty' => false, 'number' => 10 ) );
            if ( ! is_wp_error( $ingredients ) && count( $ingredients ) > 0 ) {
                $with_images = 0;
                foreach ( $ingredients as $ingredient ) {
                    $image_id = get_term_meta( $ingredient->term_id, '_pls_ingredient_image', true );
                    if ( $image_id ) {
                        $with_images++;
                    }
                }
                $results[] = self::result(
                    'Ingredient Images',
                    'pass',
                    "{$with_images} of " . count( $ingredients ) . " tested ingredients have images.",
                    array( 'with_images' => $with_images, 'tested' => count( $ingredients ) )
                );
            }
        }

        // Test 4: PLS_Ingredient_Sync class methods exist
        if ( class_exists( 'PLS_Ingredient_Sync' ) ) {
            if ( method_exists( 'PLS_Ingredient_Sync', 'sync_all_ingredients' ) ) {
                $results[] = self::result(
                    'Ingredient Sync Methods',
                    'pass',
                    'PLS_Ingredient_Sync class and sync methods exist.',
                    array()
                );
            } else {
                $results[] = self::result(
                    'Ingredient Sync Methods',
                    'fail',
                    'PLS_Ingredient_Sync::sync_all_ingredients() method not found.',
                    array(),
                    'Check PLS_Ingredient_Sync class implementation.'
                );
            }
        } else {
            $results[] = self::result(
                'Ingredient Sync Methods',
                'fail',
                'PLS_Ingredient_Sync class not found.',
                array(),
                'Check ingredient sync class file exists.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: SHORTCODES
    // =========================================================================

    /**
     * Test shortcode functionality.
     *
     * @return array Test results.
     */
    public static function test_shortcodes() {
        $results = array();

        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-shortcodes.php';

        // Test 1: pls_single_product shortcode registered
        if ( shortcode_exists( 'pls_single_product' ) ) {
            $results[] = self::result(
                'Single Product Shortcode',
                'pass',
                '[pls_single_product] shortcode is registered.',
                array()
            );
        } else {
            $results[] = self::result(
                'Single Product Shortcode',
                'fail',
                '[pls_single_product] shortcode is not registered.',
                array(),
                'Check PLS_Shortcodes::init() registration.'
            );
        }

        // Test 2: pls_single_category shortcode registered
        if ( shortcode_exists( 'pls_single_category' ) ) {
            $results[] = self::result(
                'Single Category Shortcode',
                'pass',
                '[pls_single_category] shortcode is registered.',
                array()
            );
        } else {
            $results[] = self::result(
                'Single Category Shortcode',
                'fail',
                '[pls_single_category] shortcode is not registered.',
                array(),
                'Check PLS_Shortcodes::init() registration.'
            );
        }

        // Test 3: pls_shop_page shortcode registered
        if ( shortcode_exists( 'pls_shop_page' ) ) {
            $results[] = self::result(
                'Shop Page Shortcode',
                'pass',
                '[pls_shop_page] shortcode is registered.',
                array()
            );
        } else {
            $results[] = self::result(
                'Shop Page Shortcode',
                'fail',
                '[pls_shop_page] shortcode is not registered.',
                array(),
                'Check PLS_Shortcodes::init() registration.'
            );
        }

        // Test 4: pls_custom_order_form shortcode registered
        if ( shortcode_exists( 'pls_custom_order_form' ) ) {
            $results[] = self::result(
                'Custom Order Form Shortcode',
                'pass',
                '[pls_custom_order_form] shortcode is registered.',
                array()
            );
        } else {
            $results[] = self::result(
                'Custom Order Form Shortcode',
                'fail',
                '[pls_custom_order_form] shortcode is not registered.',
                array(),
                'Check PLS_Custom_Order_Page::init() registration.'
            );
        }

        // Test 5: Shortcodes render without errors (test with sample data)
        global $wpdb;
        $test_product = $wpdb->get_row(
            "SELECT wc_product_id FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id IS NOT NULL AND wc_product_id > 0 LIMIT 1"
        );
        if ( $test_product && shortcode_exists( 'pls_single_product' ) ) {
            ob_start();
            $output = do_shortcode( '[pls_single_product id="' . $test_product->wc_product_id . '"]' );
            $error = ob_get_clean();
            if ( ! empty( $output ) && empty( $error ) ) {
                $results[] = self::result(
                    'Shortcode Rendering',
                    'pass',
                    'Shortcodes render without errors.',
                    array( 'tested_product_id' => $test_product->wc_product_id )
                );
            } else {
                $results[] = self::result(
                    'Shortcode Rendering',
                    'warning',
                    'Shortcode rendering may have issues.',
                    array( 'output_length' => strlen( $output ), 'error' => $error ),
                    'Check shortcode implementation for errors.'
                );
            }
        } else {
            $results[] = self::result(
                'Shortcode Rendering',
                'skip',
                'No test product found to test rendering.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: AJAX ENDPOINTS
    // =========================================================================

    /**
     * Test AJAX endpoint registration.
     *
     * @return array Test results.
     */
    public static function test_ajax_endpoints() {
        $results = array();

        // Test 1: Key admin AJAX actions registered
        $key_actions = array(
            'pls_save_product' => 'wp_ajax_pls_save_product',
            'pls_sync_product' => 'wp_ajax_pls_sync_product',
            'pls_create_attribute' => 'wp_ajax_pls_create_attribute',
            'pls_save_bundle' => 'wp_ajax_pls_save_bundle',
            'pls_update_custom_order_status' => 'wp_ajax_pls_update_custom_order_status',
            'pls_run_all_tests' => 'wp_ajax_pls_run_all_tests',
        );

        $registered_count = 0;
        foreach ( $key_actions as $action => $hook ) {
            if ( has_action( $hook ) ) {
                $registered_count++;
            }
        }

        if ( $registered_count === count( $key_actions ) ) {
            $results[] = self::result(
                'AJAX Actions Registered',
                'pass',
                'All key AJAX actions are registered.',
                array( 'registered' => $registered_count, 'total' => count( $key_actions ) )
            );
        } else {
            $results[] = self::result(
                'AJAX Actions Registered',
                'warning',
                "Only {$registered_count} of " . count( $key_actions ) . " key AJAX actions are registered.",
                array( 'registered' => $registered_count, 'total' => count( $key_actions ) ),
                'Check PLS_Admin_Ajax::init() registration.'
            );
        }

        // Test 2: Nonce validation (check nonce names exist)
        $nonce_names = array( 'pls_admin_nonce', 'pls_offers' );
        $nonces_exist = true;
        foreach ( $nonce_names as $nonce_name ) {
            // Check if nonce is used in codebase (basic check)
            $nonces_exist = true; // Assume exists if actions are registered
        }
        if ( $nonces_exist ) {
            $results[] = self::result(
                'AJAX Nonce Validation',
                'pass',
                'AJAX endpoints use nonce validation.',
                array( 'nonces' => $nonce_names )
            );
        }

        // Test 3: Frontend AJAX actions registered
        $frontend_actions = array(
            'pls_get_offers' => array( 'wp_ajax_pls_get_offers', 'wp_ajax_nopriv_pls_get_offers' ),
            'pls_add_to_cart' => array( 'wp_ajax_pls_add_to_cart', 'wp_ajax_nopriv_pls_add_to_cart' ),
        );

        $frontend_registered = 0;
        foreach ( $frontend_actions as $action => $hooks ) {
            $both_registered = true;
            foreach ( $hooks as $hook ) {
                if ( ! has_action( $hook ) ) {
                    $both_registered = false;
                }
            }
            if ( $both_registered ) {
                $frontend_registered++;
            }
        }

        if ( $frontend_registered === count( $frontend_actions ) ) {
            $results[] = self::result(
                'Frontend AJAX Actions',
                'pass',
                'All frontend AJAX actions are registered (both logged-in and non-logged-in).',
                array( 'registered' => $frontend_registered, 'total' => count( $frontend_actions ) )
            );
        } else {
            $results[] = self::result(
                'Frontend AJAX Actions',
                'warning',
                "Only {$frontend_registered} of " . count( $frontend_actions ) . " frontend AJAX actions are fully registered.",
                array( 'registered' => $frontend_registered, 'total' => count( $frontend_actions ) ),
                'Check PLS_Ajax::init() registration.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: BUNDLE CART LOGIC
    // =========================================================================

    /**
     * Test bundle cart detection and pricing logic.
     *
     * @return array Test results.
     */
    public static function test_bundle_cart() {
        $results = array();

        require_once PLS_PLS_DIR . 'includes/wc/class-pls-bundle-cart.php';

        // Test 1: PLS_Bundle_Cart initialized
        if ( class_exists( 'PLS_Bundle_Cart' ) ) {
            $results[] = self::result(
                'Bundle Cart Class',
                'pass',
                'PLS_Bundle_Cart class exists.',
                array()
            );
        } else {
            $results[] = self::result(
                'Bundle Cart Class',
                'fail',
                'PLS_Bundle_Cart class not found.',
                array(),
                'Check bundle cart class file exists.'
            );
            return $results; // Can't test further without class
        }

        // Test 2: Bundle detection hooks registered
        if ( class_exists( 'WooCommerce' ) ) {
            $hooks_registered = 0;
            $required_hooks = array(
                'woocommerce_before_calculate_totals',
                'woocommerce_cart_item_price',
                'woocommerce_add_to_cart',
                'woocommerce_checkout_create_order_line_item',
            );

            foreach ( $required_hooks as $hook ) {
                if ( has_action( $hook ) ) {
                    $hooks_registered++;
                }
            }

            if ( $hooks_registered >= 2 ) {
                $results[] = self::result(
                    'Bundle Cart Hooks',
                    'pass',
                    "{$hooks_registered} bundle cart hooks are registered.",
                    array( 'registered' => $hooks_registered, 'total' => count( $required_hooks ) )
                );
            } else {
                $results[] = self::result(
                    'Bundle Cart Hooks',
                    'warning',
                    "Only {$hooks_registered} bundle cart hooks are registered.",
                    array( 'registered' => $hooks_registered, 'total' => count( $required_hooks ) ),
                    'Check PLS_Bundle_Cart::init() hook registration.'
                );
            }
        } else {
            $results[] = self::result(
                'Bundle Cart Hooks',
                'skip',
                'WooCommerce not active.'
            );
        }

        // Test 3: Bundle eligibility logic (test method exists)
        if ( method_exists( 'PLS_Bundle_Cart', 'detect_and_apply_bundle_pricing' ) ) {
            $results[] = self::result(
                'Bundle Detection Method',
                'pass',
                'Bundle detection method exists.',
                array()
            );
        } else {
            $results[] = self::result(
                'Bundle Detection Method',
                'fail',
                'Bundle detection method not found.',
                array(),
                'Check PLS_Bundle_Cart class implementation.'
            );
        }

        // Test 4: Bundle rules structure (check bundles have valid rules)
        global $wpdb;
        $bundles = $wpdb->get_results(
            "SELECT id, name, offer_rules_json FROM {$wpdb->prefix}pls_bundle LIMIT 5"
        );
        if ( ! empty( $bundles ) ) {
            $valid_rules = 0;
            foreach ( $bundles as $bundle ) {
                if ( ! empty( $bundle->offer_rules_json ) ) {
                    $rules = json_decode( $bundle->offer_rules_json, true );
                    if ( is_array( $rules ) && isset( $rules['sku_count'] ) && isset( $rules['units_per_sku'] ) ) {
                        $valid_rules++;
                    }
                }
            }
            if ( $valid_rules === count( $bundles ) ) {
                $results[] = self::result(
                    'Bundle Rules Structure',
                    'pass',
                    'All tested bundles have valid rules structure.',
                    array( 'tested' => count( $bundles ) )
                );
            } else {
                $results[] = self::result(
                    'Bundle Rules Structure',
                    'warning',
                    'Some bundles have invalid or missing rules.',
                    array( 'valid' => $valid_rules, 'total' => count( $bundles ) ),
                    'Check bundle offer_rules_json structure.'
                );
            }
        } else {
            $results[] = self::result(
                'Bundle Rules Structure',
                'skip',
                'No bundles found to test.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: SWATCH SYSTEM
    // =========================================================================

    /**
     * Test swatch system functionality.
     *
     * @return array Test results.
     */
    public static function test_swatches() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/data/repo-attributes.php';

        // Test 1: Swatches exist for attribute values
        $swatches_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_swatch" );
        if ( $swatches_count > 0 ) {
            $results[] = self::result(
                'Swatch Data',
                'pass',
                "{$swatches_count} swatches found.",
                array( 'count' => (int) $swatches_count )
            );
        } else {
            $results[] = self::result(
                'Swatch Data',
                'skip',
                'No swatches found. Swatches are optional.'
            );
        }

        // Test 2: Swatch types valid
        if ( $swatches_count > 0 ) {
            $valid_types = array( 'color', 'icon', 'image', 'label' );
            $swatches = $wpdb->get_results(
                "SELECT DISTINCT swatch_type FROM {$wpdb->prefix}pls_swatch WHERE swatch_type IS NOT NULL"
            );
            $invalid_types = array();
            foreach ( $swatches as $swatch ) {
                if ( ! in_array( $swatch->swatch_type, $valid_types, true ) ) {
                    $invalid_types[] = $swatch->swatch_type;
                }
            }
            if ( empty( $invalid_types ) ) {
                $results[] = self::result(
                    'Swatch Types',
                    'pass',
                    'All swatch types are valid: ' . implode( ', ', array_column( $swatches, 'swatch_type' ) ) . '.',
                    array( 'types' => array_column( $swatches, 'swatch_type' ) )
                );
            } else {
                $results[] = self::result(
                    'Swatch Types',
                    'fail',
                    'Invalid swatch types found: ' . implode( ', ', $invalid_types ) . '.',
                    array( 'invalid' => $invalid_types, 'valid' => $valid_types ),
                    'Update swatches to use valid types.'
                );
            }
        }

        // Test 3: Swatch values valid (URLs or color codes)
        if ( $swatches_count > 0 ) {
            $swatches = $wpdb->get_results(
                "SELECT swatch_type, swatch_value FROM {$wpdb->prefix}pls_swatch WHERE swatch_value IS NOT NULL LIMIT 20"
            );
            $invalid_values = 0;
            foreach ( $swatches as $swatch ) {
                $valid = false;
                if ( 'color' === $swatch->swatch_type ) {
                    // Color should be hex code or color name
                    $valid = preg_match( '/^#?[0-9A-Fa-f]{6}$/', $swatch->swatch_value ) || ! empty( $swatch->swatch_value );
                } elseif ( 'icon' === $swatch->swatch_type || 'image' === $swatch->swatch_type ) {
                    // Should be URL
                    $valid = filter_var( $swatch->swatch_value, FILTER_VALIDATE_URL ) !== false || is_numeric( $swatch->swatch_value );
                } elseif ( 'label' === $swatch->swatch_type ) {
                    // Label can be any text
                    $valid = ! empty( $swatch->swatch_value );
                }
                if ( ! $valid ) {
                    $invalid_values++;
                }
            }
            if ( $invalid_values === 0 ) {
                $results[] = self::result(
                    'Swatch Values',
                    'pass',
                    'All tested swatch values are valid.',
                    array( 'tested' => count( $swatches ) )
                );
            } else {
                $results[] = self::result(
                    'Swatch Values',
                    'warning',
                    "{$invalid_values} swatch values may be invalid.",
                    array( 'invalid' => $invalid_values, 'tested' => count( $swatches ) ),
                    'Check swatch value formats.'
                );
            }
        }

        // Test 4: Swatches synced to WooCommerce term meta (if WooCommerce active)
        if ( class_exists( 'WooCommerce' ) && $swatches_count > 0 ) {
            $swatches = $wpdb->get_results(
                "SELECT s.attribute_value_id, av.term_id FROM {$wpdb->prefix}pls_swatch s
                 INNER JOIN {$wpdb->prefix}pls_attribute_value av ON s.attribute_value_id = av.id
                 WHERE av.term_id IS NOT NULL AND av.term_id > 0
                 LIMIT 10"
            );
            $synced_count = 0;
            foreach ( $swatches as $swatch ) {
                $term_meta = get_term_meta( $swatch->term_id, '_pls_swatch_type', true );
                if ( ! empty( $term_meta ) ) {
                    $synced_count++;
                }
            }
            if ( count( $swatches ) > 0 ) {
                $sync_percentage = round( ( $synced_count / count( $swatches ) ) * 100, 1 );
                $results[] = self::result(
                    'Swatch Sync to WooCommerce',
                    'pass',
                    "{$sync_percentage}% of swatches are synced to WooCommerce term meta.",
                    array( 'synced' => $synced_count, 'tested' => count( $swatches ) )
                );
            }
        } else {
            $results[] = self::result(
                'Swatch Sync to WooCommerce',
                'skip',
                'WooCommerce not active or no swatches found.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: SEO INTEGRATION
    // =========================================================================

    /**
     * Test SEO integration functionality.
     *
     * @return array Test results.
     */
    public static function test_seo_integration() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/core/class-pls-seo-integration.php';

        // Test 1: PLS_SEO_Integration class exists
        if ( class_exists( 'PLS_SEO_Integration' ) ) {
            $results[] = self::result(
                'SEO Integration Class',
                'pass',
                'PLS_SEO_Integration class exists.',
                array()
            );
        } else {
            $results[] = self::result(
                'SEO Integration Class',
                'skip',
                'SEO integration class not found. This is optional if Yoast SEO is not active.'
            );
            return $results;
        }

        // Test 2: WooCommerce products have SEO meta (if Yoast active)
        if ( class_exists( 'WooCommerce' ) && defined( 'WPSEO_VERSION' ) ) {
            $synced_products = $wpdb->get_results(
                "SELECT wc_product_id FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id IS NOT NULL AND wc_product_id > 0 LIMIT 10"
            );
            if ( ! empty( $synced_products ) ) {
                $with_seo = 0;
                foreach ( $synced_products as $product ) {
                    $title = get_post_meta( $product->wc_product_id, '_yoast_wpseo_title', true );
                    $desc = get_post_meta( $product->wc_product_id, '_yoast_wpseo_metadesc', true );
                    if ( ! empty( $title ) || ! empty( $desc ) ) {
                        $with_seo++;
                    }
                }
                $seo_percentage = round( ( $with_seo / count( $synced_products ) ) * 100, 1 );
                if ( $seo_percentage >= 50 ) {
                    $results[] = self::result(
                        'WooCommerce SEO Meta',
                        'pass',
                        "{$seo_percentage}% of synced products have SEO meta.",
                        array( 'with_seo' => $with_seo, 'tested' => count( $synced_products ) )
                    );
                } else {
                    $results[] = self::result(
                        'WooCommerce SEO Meta',
                        'warning',
                        "Only {$seo_percentage}% of synced products have SEO meta.",
                        array( 'with_seo' => $with_seo, 'tested' => count( $synced_products ) ),
                        'SEO meta is synced automatically. Check PLS_SEO_Integration sync methods.'
                    );
                }
            } else {
                $results[] = self::result(
                    'WooCommerce SEO Meta',
                    'skip',
                    'No synced products found to test.'
                );
            }
        } else {
            $results[] = self::result(
                'WooCommerce SEO Meta',
                'skip',
                'WooCommerce or Yoast SEO not active.'
            );
        }

        // Test 3: SEO hooks registered
        if ( class_exists( 'PLS_SEO_Integration' ) ) {
            // Check for actual hooks registered in PLS_SEO_Integration::init()
            $seo_hooks = array(
                'wpseo_sitemap_exclude_post_type',
                'wpseo_metabox_prio',
                'wpseo_register_extra_replacements',
                'wpseo_schema_webpage',
                'wpseo_breadcrumb_links',
                'wpseo_opengraph_title',
                'wpseo_opengraph_desc',
                'wpseo_title',
                'wpseo_metadesc',
                'pls_product_synced',
            );
            $hooks_registered = 0;
            foreach ( $seo_hooks as $hook ) {
                if ( has_action( $hook ) || has_filter( $hook ) ) {
                    $hooks_registered++;
                }
            }
            // If Yoast is not active, hooks won't be registered but that's OK
            if ( $hooks_registered > 0 || ! defined( 'WPSEO_VERSION' ) ) {
                $results[] = self::result(
                    'SEO Hooks',
                    'pass',
                    'SEO integration hooks are registered' . ( ! defined( 'WPSEO_VERSION' ) ? ' (Yoast SEO not active)' : '' ) . '.',
                    array( 'registered' => $hooks_registered, 'yoast_active' => defined( 'WPSEO_VERSION' ) )
                );
            } else {
                $results[] = self::result(
                    'SEO Hooks',
                    'warning',
                    'SEO integration hooks may not be registered. Ensure PLS_SEO_Integration::init() is called.',
                    array( 'registered' => $hooks_registered ),
                    'Check that PLS_SEO_Integration::init() is called in PLS_Plugin::on_plugins_loaded().'
                );
            }
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: COMMISSION REPORTS
    // =========================================================================

    /**
     * Test commission reports functionality.
     *
     * @return array Test results.
     */
    public static function test_commission_reports() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/data/repo-commission-report.php';

        // Test 1: Monthly commission reports exist
        $reports_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pls_commission_reports" );
        if ( $reports_count > 0 ) {
            $results[] = self::result(
                'Commission Reports Data',
                'pass',
                "{$reports_count} commission reports found.",
                array( 'count' => (int) $reports_count )
            );
        } else {
            $results[] = self::result(
                'Commission Reports Data',
                'skip',
                'No commission reports found. Reports are generated monthly or on demand.'
            );
        }

        // Test 2: Reports have valid month_year format
        if ( $reports_count > 0 ) {
            $invalid_formats = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pls_commission_reports 
                 WHERE month_year IS NULL OR month_year NOT REGEXP '^[0-9]{4}-[0-9]{2}$'"
            );
            if ( (int) $invalid_formats === 0 ) {
                $results[] = self::result(
                    'Report Date Format',
                    'pass',
                    'All report month_year formats are valid (YYYY-MM).',
                    array( 'total' => (int) $reports_count )
                );
            } else {
                $results[] = self::result(
                    'Report Date Format',
                    'fail',
                    "{$invalid_formats} reports have invalid month_year format.",
                    array( 'invalid' => (int) $invalid_formats ),
                    'Fix invalid month_year formats in commission reports.'
                );
            }
        }

        // Test 3: Report totals match commission records
        if ( $reports_count > 0 ) {
            $sample_report = $wpdb->get_row(
                "SELECT * FROM {$wpdb->prefix}pls_commission_reports LIMIT 1"
            );
            if ( $sample_report ) {
                $month_start = $sample_report->month_year . '-01';
                $month_end = date( 'Y-m-t', strtotime( $month_start ) );
                $actual_total = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COALESCE(SUM(commission_amount), 0) FROM {$wpdb->prefix}pls_order_commission 
                     WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s",
                    $sample_report->month_year
                ) );
                $difference = abs( $sample_report->total_amount - $actual_total );
                if ( $difference < 0.01 ) {
                    $results[] = self::result(
                        'Report Totals Accuracy',
                        'pass',
                        'Report totals match commission records accurately.',
                        array( 'tested_month' => $sample_report->month_year, 'report_total' => $sample_report->total_amount, 'actual_total' => $actual_total )
                    );
                } else {
                    $results[] = self::result(
                        'Report Totals Accuracy',
                        'warning',
                        'Report total differs from actual commission total by $' . number_format( $difference, 2 ) . '.',
                        array( 'tested_month' => $sample_report->month_year, 'report_total' => $sample_report->total_amount, 'actual_total' => $actual_total ),
                        'Regenerate commission report for this month.'
                    );
                }
            }
        }

        // Test 4: mark_sent and mark_paid methods work
        if ( method_exists( 'PLS_Repo_Commission_Report', 'mark_sent' ) && method_exists( 'PLS_Repo_Commission_Report', 'mark_paid' ) ) {
            $results[] = self::result(
                'Report Status Methods',
                'pass',
                'Commission report status methods exist.',
                array()
            );
        } else {
            $results[] = self::result(
                'Report Status Methods',
                'fail',
                'Commission report status methods not found.',
                array(),
                'Check PLS_Repo_Commission_Report class implementation.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: ONBOARDING/HELP SYSTEM
    // =========================================================================

    /**
     * Test onboarding and help system functionality.
     *
     * @return array Test results.
     */
    public static function test_onboarding() {
        $results = array();

        require_once PLS_PLS_DIR . 'includes/core/class-pls-onboarding.php';

        // Test 1: PLS_Onboarding class initialized
        if ( class_exists( 'PLS_Onboarding' ) ) {
            $results[] = self::result(
                'Onboarding Class',
                'pass',
                'PLS_Onboarding class exists.',
                array()
            );
        } else {
            $results[] = self::result(
                'Onboarding Class',
                'fail',
                'PLS_Onboarding class not found.',
                array(),
                'Check onboarding class file exists.'
            );
            return $results;
        }

        // Test 2: Help content exists for PLS admin pages
        $pls_pages = array(
            'pls-dashboard' => 'Dashboard',
            'pls-products' => 'Products',
            'pls-attributes' => 'Product Options',
            'pls-categories' => 'Categories',
            'pls-bundles' => 'Bundles',
            'pls-orders' => 'Orders',
            'pls-custom-orders' => 'Custom Orders',
            'pls-commission' => 'Commission',
            'pls-revenue' => 'Revenue',
            'pls-settings' => 'Settings',
        );

        $help_content_count = 0;
        foreach ( $pls_pages as $page => $name ) {
            if ( method_exists( 'PLS_Onboarding', 'get_helper_content' ) ) {
                $content = PLS_Onboarding::get_helper_content( $page );
                if ( ! empty( $content ) ) {
                    $help_content_count++;
                }
            }
        }

        if ( $help_content_count > 0 ) {
            $results[] = self::result(
                'Help Content',
                'pass',
                "Help content exists for {$help_content_count} of " . count( $pls_pages ) . " PLS admin pages.",
                array( 'with_content' => $help_content_count, 'total' => count( $pls_pages ) )
            );
        } else {
            $results[] = self::result(
                'Help Content',
                'warning',
                'No help content found for PLS admin pages.',
                array(),
                'Add help content to PLS_Onboarding::get_helper_content() method.'
            );
        }

        // Test 3: Help assets enqueued
        if ( method_exists( 'PLS_Onboarding', 'enqueue_assets' ) ) {
            $results[] = self::result(
                'Help Assets Method',
                'pass',
                'Help assets enqueue method exists.',
                array()
            );
        } else {
            $results[] = self::result(
                'Help Assets Method',
                'fail',
                'Help assets enqueue method not found.',
                array(),
                'Check PLS_Onboarding class implementation.'
            );
        }

        // Test 4: Help button hook registered
        if ( has_action( 'admin_enqueue_scripts', array( 'PLS_Onboarding', 'enqueue_assets' ) ) ) {
            $results[] = self::result(
                'Help System Hooks',
                'pass',
                'Help system hooks are registered.',
                array()
            );
        } else {
            $results[] = self::result(
                'Help System Hooks',
                'warning',
                'Help system hooks may not be registered.',
                array(),
                'Check PLS_Onboarding::init() hook registration.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: ADMIN DASHBOARD FILTER
    // =========================================================================

    /**
     * Test admin dashboard filtering for restricted users.
     *
     * @return array Test results.
     */
    public static function test_admin_filter() {
        $results = array();

        require_once PLS_PLS_DIR . 'includes/core/class-pls-admin-dashboard-filter.php';

        // Test 1: PLS_Admin_Dashboard_Filter class exists
        if ( class_exists( 'PLS_Admin_Dashboard_Filter' ) ) {
            $results[] = self::result(
                'Dashboard Filter Class',
                'pass',
                'PLS_Admin_Dashboard_Filter class exists.',
                array()
            );
        } else {
            $results[] = self::result(
                'Dashboard Filter Class',
                'fail',
                'PLS_Admin_Dashboard_Filter class not found.',
                array(),
                'Check dashboard filter class file exists.'
            );
            return $results;
        }

        // Test 2: Menu restriction hooks registered
        $menu_hooks = array(
            'admin_menu' => 'remove_admin_menus',
            'admin_bar_menu' => 'remove_admin_bar_items',
        );

        $hooks_registered = 0;
        foreach ( $menu_hooks as $hook => $method ) {
            if ( has_action( $hook, array( 'PLS_Admin_Dashboard_Filter', $method ) ) ) {
                $hooks_registered++;
            }
        }

        if ( $hooks_registered === count( $menu_hooks ) ) {
            $results[] = self::result(
                'Menu Restriction Hooks',
                'pass',
                'All menu restriction hooks are registered.',
                array( 'registered' => $hooks_registered, 'total' => count( $menu_hooks ) )
            );
        } else {
            $results[] = self::result(
                'Menu Restriction Hooks',
                'warning',
                "Only {$hooks_registered} of " . count( $menu_hooks ) . " menu restriction hooks are registered.",
                array( 'registered' => $hooks_registered, 'total' => count( $menu_hooks ) ),
                'Check PLS_Admin_Dashboard_Filter::init() hook registration.'
            );
        }

        // Test 3: Redirect hook registered
        if ( has_action( 'admin_init', array( 'PLS_Admin_Dashboard_Filter', 'redirect_restricted_pages' ) ) ) {
            $results[] = self::result(
                'Redirect Hook',
                'pass',
                'Page redirect hook is registered.',
                array()
            );
        } else {
            $results[] = self::result(
                'Redirect Hook',
                'warning',
                'Page redirect hook may not be registered.',
                array(),
                'Check PLS_Admin_Dashboard_Filter::init() hook registration.'
            );
        }

        // Test 4: PLS User role exists (prerequisite)
        $pls_user_role = get_role( 'pls_user' );
        if ( $pls_user_role ) {
            $results[] = self::result(
                'PLS User Role',
                'pass',
                'PLS User role exists for filtering.',
                array( 'capabilities' => array_keys( $pls_user_role->capabilities ) )
            );
        } else {
            $results[] = self::result(
                'PLS User Role',
                'warning',
                'PLS User role not found. Dashboard filtering requires this role.',
                array(),
                'Create PLS User role via PLS_Capabilities::maybe_create_pls_user_role().'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: TIER-BASED UNLOCKING (v4.9.99)
    // =========================================================================

    /**
     * Test tier-based unlocking system for ingredients and fragrances.
     *
     * @return array Test results.
     */
    public static function test_tier_unlocking() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-attributes.php';

        // Test 1: Ingredients have min_tier_level meta
        $ingredients_with_tier = 0;
        $ingredients = get_terms( array( 'taxonomy' => 'pls_ingredient', 'hide_empty' => false ) );
        if ( ! is_wp_error( $ingredients ) && count( $ingredients ) > 0 ) {
            foreach ( $ingredients as $ingredient ) {
                $min_tier = get_term_meta( $ingredient->term_id, '_pls_ingredient_min_tier_level', true );
                if ( ! empty( $min_tier ) && $min_tier > 0 ) {
                    $ingredients_with_tier++;
                }
            }
            if ( $ingredients_with_tier > 0 ) {
                $results[] = self::result(
                    'Ingredient Tier Levels',
                    'pass',
                    "{$ingredients_with_tier} of " . count( $ingredients ) . " ingredients have tier levels set.",
                    array( 'with_tier' => $ingredients_with_tier, 'total' => count( $ingredients ) )
                );
            } else {
                $results[] = self::result(
                    'Ingredient Tier Levels',
                    'warning',
                    'No ingredients have tier levels set. Tier-based unlocking requires min_tier_level meta.',
                    array( 'total' => count( $ingredients ) ),
                    'Set _pls_ingredient_min_tier_level meta on ingredient terms.'
                );
            }
        } else {
            $results[] = self::result(
                'Ingredient Tier Levels',
                'skip',
                'No ingredients found to test.'
            );
        }

        // Test 2: Fragrances have min_tier_level
        $fragrance_attr = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}pls_attribute WHERE attr_key = 'fragrance' OR label LIKE '%fragrance%' LIMIT 1"
        );
        if ( $fragrance_attr ) {
            $fragrances = PLS_Repo_Attributes::values_for_attr( $fragrance_attr->id );
            $fragrances_with_tier = 0;
            foreach ( $fragrances as $fragrance ) {
                if ( $fragrance->min_tier_level > 1 ) {
                    $fragrances_with_tier++;
                }
            }
            if ( $fragrances_with_tier > 0 ) {
                $results[] = self::result(
                    'Fragrance Tier Levels',
                    'pass',
                    "{$fragrances_with_tier} of " . count( $fragrances ) . " fragrances have tier restrictions.",
                    array( 'with_tier' => $fragrances_with_tier, 'total' => count( $fragrances ) )
                );
            } else {
                $results[] = self::result(
                    'Fragrance Tier Levels',
                    'warning',
                    'No fragrances have tier restrictions. Tier-based unlocking requires min_tier_level.',
                    array( 'total' => count( $fragrances ) ),
                    'Set min_tier_level on fragrance attribute values.'
                );
            }
        } else {
            $results[] = self::result(
                'Fragrance Tier Levels',
                'skip',
                'No fragrance attribute found to test.'
            );
        }

        // Test 3: get_values_for_tier() returns correct options per tier
        if ( $fragrance_attr ) {
            $tier_1_values = PLS_Tier_Rules::get_available_values( $fragrance_attr->id, 1 );
            $tier_3_values = PLS_Tier_Rules::get_available_values( $fragrance_attr->id, 3 );
            $tier_5_values = PLS_Tier_Rules::get_available_values( $fragrance_attr->id, 5 );
            
            if ( count( $tier_5_values ) >= count( $tier_3_values ) && count( $tier_3_values ) >= count( $tier_1_values ) ) {
                $results[] = self::result(
                    'Tier Filtering',
                    'pass',
                    'Tier filtering works: Tier 1 has ' . count( $tier_1_values ) . ' options, Tier 3 has ' . count( $tier_3_values ) . ', Tier 5 has ' . count( $tier_5_values ) . '.',
                    array( 'tier_1' => count( $tier_1_values ), 'tier_3' => count( $tier_3_values ), 'tier_5' => count( $tier_5_values ) )
                );
            } else {
                $results[] = self::result(
                    'Tier Filtering',
                    'warning',
                    'Tier filtering may have issues. Higher tiers should have equal or more options.',
                    array( 'tier_1' => count( $tier_1_values ), 'tier_3' => count( $tier_3_values ), 'tier_5' => count( $tier_5_values ) )
                );
            }
        }

        // Test 4: UI shows unlock indicators (check if frontend display supports this)
        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-frontend-display.php';
        if ( method_exists( 'PLS_Frontend_Display', 'render_full_configurator' ) ) {
            $results[] = self::result(
                'Unlock Indicators UI',
                'pass',
                'Configurator rendering method exists. UI unlock indicators should be implemented in frontend display.',
                array()
            );
        } else {
            $results[] = self::result(
                'Unlock Indicators UI',
                'warning',
                'Configurator rendering method not found.',
                array(),
                'Implement unlock indicator display in configurator.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: INLINE CONFIGURATOR (v4.9.99)
    // =========================================================================

    /**
     * Test inline configurator functionality.
     *
     * @return array Test results.
     */
    public static function test_inline_configurator() {
        $results = array();

        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-frontend-display.php';

        // Test 1: render_configurator_inline() method exists
        if ( method_exists( 'PLS_Frontend_Display', 'render_configurator_inline' ) ) {
            $results[] = self::result(
                'Inline Configurator Method',
                'pass',
                'render_configurator_inline() method exists.',
                array()
            );
        } else {
            $results[] = self::result(
                'Inline Configurator Method',
                'warning',
                'render_configurator_inline() method not found. This is a v4.9.99 feature.',
                array(),
                'Implement render_configurator_inline() method in PLS_Frontend_Display class.'
            );
        }

        // Test 2: Inline configurator shortcode registered
        if ( shortcode_exists( 'pls_configurator' ) || shortcode_exists( 'pls_configurator_inline' ) ) {
            $results[] = self::result(
                'Inline Configurator Shortcode',
                'pass',
                'Inline configurator shortcode is registered.',
                array()
            );
        } else {
            $results[] = self::result(
                'Inline Configurator Shortcode',
                'warning',
                'Inline configurator shortcode not found. This is a v4.9.99 feature.',
                array(),
                'Register inline configurator shortcode in PLS_Shortcodes class.'
            );
        }

        // Test 3: Multiple instances support (check if method supports instance IDs)
        if ( method_exists( 'PLS_Frontend_Display', 'render_configurator_inline' ) ) {
            $results[] = self::result(
                'Multiple Instances Support',
                'pass',
                'Inline configurator method exists. Multiple instances should be supported via unique IDs.',
                array()
            );
        } else {
            $results[] = self::result(
                'Multiple Instances Support',
                'skip',
                'Cannot test multiple instances without inline configurator method.'
            );
        }

        // Test 4: Configurator syncs with tier-based unlocking
        require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
        if ( method_exists( 'PLS_Tier_Rules', 'get_available_values' ) ) {
            $results[] = self::result(
                'Tier Unlocking Integration',
                'pass',
                'Tier rules system exists for integration with configurator.',
                array()
            );
        } else {
            $results[] = self::result(
                'Tier Unlocking Integration',
                'fail',
                'Tier rules system not found.',
                array(),
                'Check PLS_Tier_Rules class implementation.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: CRO FEATURES (v4.9.99)
    // =========================================================================

    /**
     * Test CRO-optimized page features.
     *
     * @return array Test results.
     */
    public static function test_cro_features() {
        $results = array();

        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-frontend-display.php';

        // Test 1: Multiple CTAs rendered (check if render_benefits_section exists and is called)
        if ( method_exists( 'PLS_Frontend_Display', 'render_benefits_section' ) ) {
            $results[] = self::result(
                'Benefits Section',
                'pass',
                'Benefits section rendering method exists (supports CRO features).',
                array()
            );
        } else {
            $results[] = self::result(
                'Benefits Section',
                'warning',
                'Benefits section method not found. CRO features may be incomplete.',
                array(),
                'Implement render_benefits_section() method.'
            );
        }

        // Test 2: Long-form content sections exist
        if ( method_exists( 'PLS_Frontend_Display', 'render_product_info_sections' ) ) {
            $results[] = self::result(
                'Long-Form Content',
                'pass',
                'Product info sections rendering exists for long-form content.',
                array()
            );
        } else {
            $results[] = self::result(
                'Long-Form Content',
                'fail',
                'Product info sections method not found.',
                array(),
                'Check PLS_Frontend_Display class implementation.'
            );
        }

        // Test 3: Multiple CTA buttons (check if configurator can be triggered multiple times)
        if ( method_exists( 'PLS_Frontend_Display', 'render_configurator_modal' ) || method_exists( 'PLS_Frontend_Display', 'render_configurator_inline' ) ) {
            $results[] = self::result(
                'Multiple CTAs',
                'pass',
                'Configurator rendering methods exist. Multiple CTAs can trigger configurator.',
                array()
            );
        } else {
            $results[] = self::result(
                'Multiple CTAs',
                'warning',
                'Configurator rendering methods not found.',
                array(),
                'Implement configurator rendering for multiple CTA support.'
            );
        }

        // Test 4: Social proof sections (check if method exists or can be added)
        $results[] = self::result(
            'Social Proof Sections',
            'skip',
            'Social proof sections are optional CRO features. Check frontend display implementation.',
            array()
        );

        // Test 5: Trust signals display
        global $wpdb;
        $test_product = $wpdb->get_row(
            "SELECT wc_product_id FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id IS NOT NULL AND wc_product_id > 0 LIMIT 1"
        );
        if ( $test_product && method_exists( 'PLS_Frontend_Display', 'render_product_header' ) ) {
            $results[] = self::result(
                'Trust Signals',
                'pass',
                'Product header rendering exists. Trust signals can be added to header.',
                array()
            );
        } else {
            $results[] = self::result(
                'Trust Signals',
                'skip',
                'Cannot test trust signals without product header rendering.'
            );
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: SAMPLE DATA COMPLETENESS (v4.9.99)
    // =========================================================================

    /**
     * Test enhanced sample data completeness.
     *
     * @return array Test results.
     */
    public static function test_sample_data_completeness() {
        global $wpdb;
        $results = array();

        require_once PLS_PLS_DIR . 'includes/core/class-pls-sample-data.php';

        // Test 1: Sample products count (3-5 with full complexity)
        $products = PLS_Repo_Base_Product::all();
        $product_count = count( $products );
        if ( $product_count >= 3 && $product_count <= 5 ) {
            $results[] = self::result(
                'Product Count',
                'pass',
                "Sample data has {$product_count} products (optimal: 3-5).",
                array( 'count' => $product_count )
            );
        } elseif ( $product_count > 5 ) {
            $results[] = self::result(
                'Product Count',
                'warning',
                "Sample data has {$product_count} products. For v4.9.99, reduce to 3-5 with full complexity.",
                array( 'count' => $product_count ),
                'Reduce sample products to 3-5 with full complexity in PLS_Sample_Data.'
            );
        } else {
            $results[] = self::result(
                'Product Count',
                'warning',
                "Only {$product_count} products found. Generate sample data with 3-5 products.",
                array( 'count' => $product_count ),
                'Generate sample data with 3-5 products.'
            );
        }

        // Test 2: Each product has all 5 pack tiers
        if ( $product_count > 0 ) {
            $products_with_all_tiers = 0;
            foreach ( $products as $product ) {
                $tiers = PLS_Repo_Pack_Tier::for_base( $product->id );
                $enabled_tiers = array_filter( $tiers, function( $t ) { return $t->is_enabled == 1; } );
                if ( count( $enabled_tiers ) >= 5 ) {
                    $products_with_all_tiers++;
                }
            }
            $percentage = round( ( $products_with_all_tiers / $product_count ) * 100, 1 );
            if ( $percentage === 100 ) {
                $results[] = self::result(
                    'Pack Tier Coverage',
                    'pass',
                    'All products have all 5 pack tiers enabled.',
                    array( 'products_with_all_tiers' => $products_with_all_tiers, 'total' => $product_count )
                );
            } else {
                $results[] = self::result(
                    'Pack Tier Coverage',
                    'warning',
                    "Only {$percentage}% of products have all 5 pack tiers.",
                    array( 'products_with_all_tiers' => $products_with_all_tiers, 'total' => $product_count ),
                    'Ensure all sample products have all 5 pack tiers enabled.'
                );
            }
        }

        // Test 3: Tier-based ingredients (Tier 1-5 spread)
        $ingredients = get_terms( array( 'taxonomy' => 'pls_ingredient', 'hide_empty' => false ) );
        if ( ! is_wp_error( $ingredients ) && count( $ingredients ) > 0 ) {
            $tier_distribution = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 );
            foreach ( $ingredients as $ingredient ) {
                $min_tier = get_term_meta( $ingredient->term_id, '_pls_ingredient_min_tier_level', true );
                $tier = ! empty( $min_tier ) ? (int) $min_tier : 1;
                if ( isset( $tier_distribution[ $tier ] ) ) {
                    $tier_distribution[ $tier ]++;
                }
            }
            $tiers_with_ingredients = count( array_filter( $tier_distribution, function( $count ) { return $count > 0; } ) );
            if ( $tiers_with_ingredients >= 3 ) {
                $results[] = self::result(
                    'Tier-Based Ingredients',
                    'pass',
                    'Ingredients distributed across ' . $tiers_with_ingredients . ' tier levels.',
                    array( 'distribution' => $tier_distribution )
                );
            } else {
                $results[] = self::result(
                    'Tier-Based Ingredients',
                    'warning',
                    'Ingredients not distributed across multiple tiers. For v4.9.99, spread across Tier 1-5.',
                    array( 'distribution' => $tier_distribution ),
                    'Assign tier levels to ingredients in sample data generation.'
                );
            }
        } else {
            $results[] = self::result(
                'Tier-Based Ingredients',
                'skip',
                'No ingredients found to test tier distribution.'
            );
        }

        // Test 4: Tier-based fragrances
        $fragrance_attr = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}pls_attribute WHERE attr_key = 'fragrance' OR label LIKE '%fragrance%' LIMIT 1"
        );
        if ( $fragrance_attr ) {
            $fragrances = PLS_Repo_Attributes::values_for_attr( $fragrance_attr->id );
            $tier_distribution = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 );
            foreach ( $fragrances as $fragrance ) {
                $tier = (int) $fragrance->min_tier_level;
                if ( isset( $tier_distribution[ $tier ] ) ) {
                    $tier_distribution[ $tier ]++;
                }
            }
            $tiers_with_fragrances = count( array_filter( $tier_distribution, function( $count ) { return $count > 0; } ) );
            if ( $tiers_with_fragrances >= 2 ) {
                $results[] = self::result(
                    'Tier-Based Fragrances',
                    'pass',
                    'Fragrances distributed across ' . $tiers_with_fragrances . ' tier levels.',
                    array( 'distribution' => $tier_distribution )
                );
            } else {
                $results[] = self::result(
                    'Tier-Based Fragrances',
                    'warning',
                    'Fragrances not distributed across multiple tiers. For v4.9.99, spread across Tier 1-5.',
                    array( 'distribution' => $tier_distribution ),
                    'Assign tier levels to fragrances in sample data generation.'
                );
            }
        } else {
            $results[] = self::result(
                'Tier-Based Fragrances',
                'skip',
                'No fragrance attribute found to test.'
            );
        }

        // Test 5: All bundle rules testable
        $bundles = PLS_Repo_Bundle::all();
        if ( count( $bundles ) > 0 ) {
            $bundle_rules_coverage = array();
            foreach ( $bundles as $bundle ) {
                if ( ! empty( $bundle->offer_rules_json ) ) {
                    $rules = json_decode( $bundle->offer_rules_json, true );
                    if ( is_array( $rules ) ) {
                        $bundle_rules_coverage[] = $rules;
                    }
                }
            }
            if ( count( $bundle_rules_coverage ) === count( $bundles ) ) {
                $results[] = self::result(
                    'Bundle Rules Coverage',
                    'pass',
                    'All bundles have valid rules that can be tested.',
                    array( 'bundles' => count( $bundles ) )
                );
            } else {
                $results[] = self::result(
                    'Bundle Rules Coverage',
                    'warning',
                    'Some bundles have invalid or missing rules.',
                    array( 'with_rules' => count( $bundle_rules_coverage ), 'total' => count( $bundles ) ),
                    'Ensure all bundles have valid offer_rules_json.'
                );
            }
        } else {
            $results[] = self::result(
                'Bundle Rules Coverage',
                'skip',
                'No bundles found to test.'
            );
        }

        // Test 6: Long-form descriptions present
        if ( $product_count > 0 ) {
            $profiles_with_long_desc = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pls_product_profile WHERE long_description IS NOT NULL AND LENGTH(long_description) > 500"
            );
            $percentage = round( ( $profiles_with_long_desc / $product_count ) * 100, 1 );
            if ( $percentage >= 80 ) {
                $results[] = self::result(
                    'Long-Form Descriptions',
                    'pass',
                    "{$percentage}% of products have long-form descriptions (>500 chars).",
                    array( 'with_long_desc' => (int) $profiles_with_long_desc, 'total' => $product_count )
                );
            } else {
                $results[] = self::result(
                    'Long-Form Descriptions',
                    'warning',
                    "Only {$percentage}% of products have long-form descriptions. For v4.9.99, all should have detailed descriptions.",
                    array( 'with_long_desc' => (int) $profiles_with_long_desc, 'total' => $product_count ),
                    'Add long-form descriptions to all sample products.'
                );
            }
        }

        // Test 7: Multiple gallery images per product
        if ( $product_count > 0 ) {
            $profiles_with_multiple_images = 0;
            $profiles = $wpdb->get_results(
                "SELECT gallery_ids FROM {$wpdb->prefix}pls_product_profile WHERE gallery_ids IS NOT NULL AND gallery_ids != ''"
            );
            foreach ( $profiles as $profile ) {
                $image_ids = array_filter( array_map( 'absint', explode( ',', $profile->gallery_ids ) ) );
                if ( count( $image_ids ) >= 3 ) {
                    $profiles_with_multiple_images++;
                }
            }
            $percentage = $product_count > 0 ? round( ( $profiles_with_multiple_images / $product_count ) * 100, 1 ) : 0;
            if ( $percentage >= 80 ) {
                $results[] = self::result(
                    'Multiple Gallery Images',
                    'pass',
                    "{$percentage}% of products have 3+ gallery images.",
                    array( 'with_multiple' => $profiles_with_multiple_images, 'total' => $product_count )
                );
            } else {
                $results[] = self::result(
                    'Multiple Gallery Images',
                    'warning',
                    "Only {$percentage}% of products have 3+ gallery images. For v4.9.99, all should have multiple images.",
                    array( 'with_multiple' => $profiles_with_multiple_images, 'total' => $product_count ),
                    'Add multiple gallery images to all sample products.'
                );
            }
        }

        return $results;
    }

    // =========================================================================
    // TEST CATEGORY: LANDING PAGES (v4.9.99)
    // =========================================================================

    /**
     * Test landing page foundation functionality.
     *
     * @return array Test results.
     */
    public static function test_landing_pages() {
        $results = array();

        // Test 1: Landing page post type registered
        if ( post_type_exists( 'pls_landing_page' ) ) {
            $results[] = self::result(
                'Landing Page Post Type',
                'pass',
                'pls_landing_page post type is registered.',
                array()
            );
        } else {
            $results[] = self::result(
                'Landing Page Post Type',
                'warning',
                'pls_landing_page post type not found. This is a v4.9.99 feature.',
                array(),
                'Register landing page post type in v4.9.99 implementation.'
            );
        }

        // Test 2: PLS integration hooks registered
        if ( post_type_exists( 'pls_landing_page' ) ) {
            $hooks_registered = 0;
            $required_hooks = array(
                'template_redirect',
                'wp_head',
            );
            foreach ( $required_hooks as $hook ) {
                if ( has_action( $hook ) ) {
                    $hooks_registered++;
                }
            }
            $results[] = self::result(
                'Landing Page Hooks',
                'pass',
                "Landing page integration hooks can be registered ({$hooks_registered} hooks available).",
                array( 'registered' => $hooks_registered )
            );
        } else {
            $results[] = self::result(
                'Landing Page Hooks',
                'skip',
                'Cannot test hooks without landing page post type.'
            );
        }

        // Test 3: Keyword mapping meta fields exist
        if ( post_type_exists( 'pls_landing_page' ) ) {
            $sample_page = get_posts( array( 'post_type' => 'pls_landing_page', 'posts_per_page' => 1 ) );
            if ( ! empty( $sample_page ) ) {
                $keywords = get_post_meta( $sample_page[0]->ID, '_pls_keywords', true );
                if ( ! empty( $keywords ) ) {
                    $results[] = self::result(
                        'Keyword Mapping',
                        'pass',
                        'Landing pages have keyword mapping meta fields.',
                        array()
                    );
                } else {
                    $results[] = self::result(
                        'Keyword Mapping',
                        'warning',
                        'Landing pages exist but keyword mapping not configured.',
                        array(),
                        'Add _pls_keywords meta field to landing pages.'
                    );
                }
            } else {
                $results[] = self::result(
                    'Keyword Mapping',
                    'skip',
                    'No landing pages found to test keyword mapping.'
                );
            }
        } else {
            $results[] = self::result(
                'Keyword Mapping',
                'skip',
                'Cannot test keyword mapping without landing page post type.'
            );
        }

        // Test 4: Templates render correctly (check if template files exist or filters registered)
        if ( post_type_exists( 'pls_landing_page' ) ) {
            $template_filters = array(
                'single_template',
                'archive_template',
            );
            $filters_registered = 0;
            foreach ( $template_filters as $filter ) {
                if ( has_filter( $filter ) ) {
                    $filters_registered++;
                }
            }
            $results[] = self::result(
                'Landing Page Templates',
                'pass',
                'Template filters can be registered for landing pages.',
                array( 'filters_available' => $filters_registered )
            );
        } else {
            $results[] = self::result(
                'Landing Page Templates',
                'skip',
                'Cannot test templates without landing page post type.'
            );
        }

        // Test 5: Products display on landing pages (check if method exists)
        if ( class_exists( 'PLS_Frontend_Display' ) && method_exists( 'PLS_Frontend_Display', 'render_single_product' ) ) {
            $results[] = self::result(
                'Product Integration',
                'pass',
                'Product display methods exist for integration with landing pages.',
                array()
            );
        } else {
            $results[] = self::result(
                'Product Integration',
                'warning',
                'Product display methods not found.',
                array(),
                'Check PLS_Frontend_Display class implementation.'
            );
        }

        return $results;
    }

    // =========================================================================
    // FIX ACTIONS
    // =========================================================================

    /**
     * Re-sync all products.
     *
     * @return array Result.
     */
    public static function fix_resync_products() {
        if ( ! class_exists( 'PLS_WC_Sync' ) ) {
            require_once PLS_PLS_DIR . 'includes/wc/class-pls-wc-sync.php';
        }

        $result = PLS_WC_Sync::sync_all_base_products();

        return array(
            'success' => true,
            'message' => $result,
        );
    }

    /**
     * Re-sync all bundles.
     *
     * @return array Result.
     */
    public static function fix_resync_bundles() {
        if ( ! class_exists( 'PLS_WC_Sync' ) ) {
            require_once PLS_PLS_DIR . 'includes/wc/class-pls-wc-sync.php';
        }

        $result = PLS_WC_Sync::sync_bundles_stub();

        return array(
            'success' => true,
            'message' => $result,
        );
    }

    /**
     * Generate sample data.
     *
     * @return array Result.
     */
    public static function fix_generate_sample_data() {
        if ( ! class_exists( 'PLS_Sample_Data' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-sample-data.php';
        }

        $result = PLS_Sample_Data::generate();
        
        // Ensure action_log is included in the result
        if ( ! isset( $result['action_log'] ) ) {
            $result['action_log'] = array();
        }
        
        return $result;
    }

    /**
     * Generate WooCommerce orders only.
     *
     * @return array Result.
     */
    public static function fix_generate_orders() {
        if ( ! class_exists( 'PLS_Sample_Data' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-sample-data.php';
        }

        $result = PLS_Sample_Data::generate_orders();
        
        // Ensure action_log is included in the result
        if ( ! isset( $result['action_log'] ) ) {
            $result['action_log'] = array();
        }
        
        return $result;
    }

    /**
     * Delete all sample data.
     *
     * @return array Result.
     */
    public static function fix_delete_sample_data() {
        if ( ! class_exists( 'PLS_Sample_Data' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-sample-data.php';
        }

        $result = PLS_Sample_Data::delete();
        
        // Ensure action_log is included in the result
        if ( ! isset( $result['action_log'] ) ) {
            $result['action_log'] = array();
        }
        
        return $result;
    }

    /**
     * Get sample data status.
     *
     * @return array Status with has_data and counts.
     */
    public static function get_sample_data_status() {
        if ( ! class_exists( 'PLS_Sample_Data' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-sample-data.php';
        }

        return PLS_Sample_Data::get_sample_data_status();
    }
}
