<?php
/**
 * Cleanup and Re-sync Script
 * 
 * This script will:
 * 1. Clean up incorrectly synced ingredient WooCommerce attributes
 * 2. Re-sync all ingredients with correct tier levels
 * 3. Verify ingredient sync status
 * 
 * Usage: Run this from WordPress admin or via WP-CLI
 * 
 * @package PLS_Private_Label_Store
 */

// Load WordPress
require_once __DIR__ . '/../wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access not allowed.' );
}

// Check permissions
if ( ! current_user_can( 'manage_options' ) && php_sapi_name() !== 'cli' ) {
    die( 'Insufficient permissions.' );
}

echo "=== PLS Cleanup and Re-sync Script ===\n\n";

// Load required files
require_once PLS_PLS_DIR . 'includes/wc/class-pls-wc-sync-cleanup.php';
require_once PLS_PLS_DIR . 'includes/core/class-pls-ingredient-sync.php';

// Step 1: Cleanup incorrectly synced WooCommerce attributes
echo "Step 1: Cleaning up incorrectly synced ingredient WooCommerce attributes...\n";
$cleanup_result = PLS_WC_Sync_Cleanup::cleanup_ingredient_attributes();

if ( $cleanup_result['success'] ) {
    echo "✓ " . $cleanup_result['message'] . "\n";
    if ( $cleanup_result['deleted_count'] > 0 ) {
        echo "  Deleted " . $cleanup_result['deleted_count'] . " WooCommerce attributes:\n";
        foreach ( $cleanup_result['deleted_attributes'] as $attr ) {
            echo "    - {$attr['label']} (WC ID: {$attr['wc_id']}, PLS ID: {$attr['pls_id']})\n";
        }
    }
} else {
    echo "✗ " . $cleanup_result['message'] . "\n";
}

if ( ! empty( $cleanup_result['errors'] ) ) {
    echo "  Errors:\n";
    foreach ( $cleanup_result['errors'] as $error ) {
        echo "    - {$error}\n";
    }
}

echo "\n";

// Step 2: Re-sync ingredients with correct tier levels
echo "Step 2: Re-syncing ingredients with correct tier levels...\n";
$resync_result = PLS_WC_Sync_Cleanup::resync_ingredients();

if ( $resync_result['success'] ) {
    echo "✓ " . $resync_result['message'] . "\n";
    echo "  Synced {$resync_result['synced_count']} of {$resync_result['total_count']} ingredients.\n";
} else {
    echo "✗ " . $resync_result['message'] . "\n";
}

if ( ! empty( $resync_result['errors'] ) ) {
    echo "  Errors:\n";
    foreach ( $resync_result['errors'] as $error ) {
        echo "    - {$error}\n";
    }
}

echo "\n";

// Step 3: Verify ingredient sync status
echo "Step 3: Verifying ingredient sync status...\n";
global $wpdb;

$ingredients = get_terms( array(
    'taxonomy' => 'pls_ingredient',
    'hide_empty' => false,
) );

if ( ! is_wp_error( $ingredients ) && count( $ingredients ) > 0 ) {
    $synced_count = 0;
    $tier_1_count = 0;
    $tier_3_count = 0;
    
    foreach ( $ingredients as $ingredient ) {
        // Check if synced to PLS attributes
        $attr_value = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pls_attribute_value WHERE term_id = %d",
            $ingredient->term_id
        ) );
        
        if ( $attr_value > 0 ) {
            $synced_count++;
            
            // Check tier level
            $value_obj = $wpdb->get_row( $wpdb->prepare(
                "SELECT min_tier_level FROM {$wpdb->prefix}pls_attribute_value WHERE term_id = %d LIMIT 1",
                $ingredient->term_id
            ) );
            
            if ( $value_obj ) {
                if ( $value_obj->min_tier_level <= 2 ) {
                    $tier_1_count++;
                } elseif ( $value_obj->min_tier_level >= 3 ) {
                    $tier_3_count++;
                }
            }
        }
    }
    
    $sync_percentage = count( $ingredients ) > 0 ? round( ( $synced_count / count( $ingredients ) ) * 100, 1 ) : 0;
    
    echo "  Total ingredients: " . count( $ingredients ) . "\n";
    echo "  Synced to PLS attributes: {$synced_count} ({$sync_percentage}%)\n";
    echo "  Base ingredients (Tier 1-2): {$tier_1_count}\n";
    echo "  Key ingredients (Tier 3+): {$tier_3_count}\n";
    
    if ( $sync_percentage >= 80 ) {
        echo "✓ Ingredient sync status: PASS (80%+ threshold met)\n";
    } else {
        echo "⚠ Ingredient sync status: WARNING (below 80% threshold)\n";
    }
} else {
    echo "  No ingredients found.\n";
}

echo "\n";

// Step 4: Check for incorrectly synced WooCommerce attributes
echo "Step 4: Checking for remaining ingredient WooCommerce attributes...\n";
if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
    $taxonomies = wc_get_attribute_taxonomies();
    $ingredient_wc_attrs = 0;
    
    foreach ( $taxonomies as $tax ) {
        // Check if this is an ingredient attribute (starts with ingredient-)
        if ( strpos( $tax->attribute_name, 'ingredient-' ) === 0 ) {
            $ingredient_wc_attrs++;
        }
    }
    
    if ( $ingredient_wc_attrs === 0 ) {
        echo "✓ No ingredient WooCommerce attributes found. Cleanup successful!\n";
    } else {
        echo "⚠ Found {$ingredient_wc_attrs} ingredient WooCommerce attributes still remaining.\n";
        echo "  Run cleanup again or manually delete them from WooCommerce > Products > Attributes.\n";
    }
} else {
    echo "  WooCommerce not available.\n";
}

echo "\n=== Cleanup and Re-sync Complete ===\n";
echo "\nNext steps:\n";
echo "1. Run system tests to verify everything works: PLS > System Test\n";
echo "2. Sync attributes to WooCommerce (if needed): PLS > Attributes > Sync Attributes to Woo\n";
echo "3. Verify products are synced correctly: PLS > Products\n";
