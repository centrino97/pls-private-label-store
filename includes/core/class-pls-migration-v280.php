<?php
/**
 * Migration for v2.8.0 - Schema changes for variation matrix and tier defaults.
 *
 * Adds:
 * - default_min_tier to pls_attribute table (option-level tier requirement)
 * - package_type_value_id to pls_pack_tier table (for Tier x Type matrix)
 * - calculated_price to pls_pack_tier table (cached variation price)
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_v280 {

    /**
     * Run the migration.
     */
    public static function run() {
        global $wpdb;

        // Add default_min_tier to pls_attribute table
        $attribute_table = $wpdb->prefix . 'pls_attribute';
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$attribute_table} LIKE 'default_min_tier'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$attribute_table} ADD COLUMN default_min_tier INT(11) DEFAULT 1 AFTER is_variation" );
            error_log( '[PLS Migration v2.8.0] Added default_min_tier column to pls_attribute table' );
        }

        // Add package_type_value_id to pls_pack_tier table
        $pack_tier_table = $wpdb->prefix . 'pls_pack_tier';
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$pack_tier_table} LIKE 'package_type_value_id'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$pack_tier_table} ADD COLUMN package_type_value_id BIGINT(20) UNSIGNED NULL AFTER wc_variation_id" );
            error_log( '[PLS Migration v2.8.0] Added package_type_value_id column to pls_pack_tier table' );
        }

        // Add calculated_price to pls_pack_tier table
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$pack_tier_table} LIKE 'calculated_price'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$pack_tier_table} ADD COLUMN calculated_price DECIMAL(18,2) NULL AFTER package_type_value_id" );
            error_log( '[PLS Migration v2.8.0] Added calculated_price column to pls_pack_tier table' );
        }

        // Set default tier levels for existing attributes that don't have one
        $wpdb->query( "UPDATE {$attribute_table} SET default_min_tier = 1 WHERE default_min_tier IS NULL" );

        // Set specific default tiers for known option types
        // Fragrance typically requires Tier 3+
        $wpdb->query( "UPDATE {$attribute_table} SET default_min_tier = 3 WHERE label LIKE '%Fragrance%' AND default_min_tier = 1" );
        
        // Custom Printed Bottles typically requires Tier 4+
        $wpdb->query( "UPDATE {$attribute_table} SET default_min_tier = 4 WHERE label LIKE '%Custom Printed%' AND default_min_tier = 1" );
        
        // External Box Packaging typically requires Tier 4+
        $wpdb->query( "UPDATE {$attribute_table} SET default_min_tier = 4 WHERE label LIKE '%External Box%' AND default_min_tier = 1" );

        error_log( '[PLS Migration v2.8.0] Migration completed successfully' );
    }
}
