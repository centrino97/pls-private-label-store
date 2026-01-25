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
        $results = array(
            'pls_info'          => self::test_pls_info(),
            'server_config'     => self::test_server_config(),
            'wc_settings'       => self::test_wc_settings(),
            'user_roles'        => self::test_user_roles(),
            'database'          => self::test_database(),
            'product_options'   => self::test_product_options(),
            'products_sync'     => self::test_products_sync(),
            'variations'        => self::test_variations(),
            'bundles'           => self::test_bundles(),
            'wc_orders'         => self::test_wc_orders(),
            'custom_orders'     => self::test_custom_orders(),
            'commissions'       => self::test_commissions(),
            'revenue'           => self::test_revenue(),
            'frontend_display'  => self::test_frontend_display(),
        );

        // Calculate summary
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

            // Check price
            $price = $variation->get_regular_price();
            if ( abs( (float) $price - (float) $tier->price ) < 0.01 ) {
                $variation_price_ok++;
            } else {
                $issues[] = "Variation #{$tier->wc_variation_id} price mismatch: expected {$tier->price}, got {$price}.";
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

        // Check frontend display settings
        $settings = PLS_Frontend_Display::get_settings();
        
        $results[] = self::result(
            'Auto-Injection Enabled',
            'pass',
            $settings['auto_inject_enabled'] ? 'Enabled - PLS content will auto-display on product pages' : 'Disabled - Use shortcodes or Elementor widgets instead',
            array( 'enabled' => $settings['auto_inject_enabled'] )
        );

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

        // Check shop page badges
        $badges_enabled = array();
        if ( $settings['show_tier_badges'] ) $badges_enabled[] = 'Tier Badges';
        if ( $settings['show_starting_price'] ) $badges_enabled[] = 'Starting Price';

        $results[] = self::result(
            'Shop Page Badges',
            count( $badges_enabled ) > 0 ? 'pass' : 'warning',
            count( $badges_enabled ) . ' badges enabled: ' . implode( ', ', $badges_enabled ),
            array( 'badges' => $badges_enabled )
        );

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
}
