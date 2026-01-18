<?php
/**
 * Migration for v0.9.0: Tier-Variable Pricing & Enhanced Attributes
 * Adds tier_price_overrides and ingredient_category columns to pls_attribute_value table.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_V090 {

    /**
     * Run migration if not already completed.
     */
    public static function maybe_migrate() {
        $migration_complete = get_option( 'pls_migration_v090_complete', false );
        if ( $migration_complete ) {
            return;
        }

        self::migrate();
        update_option( 'pls_migration_v090_complete', true );
    }

    /**
     * Execute migration steps.
     */
    private static function migrate() {
        global $wpdb;

        // Step 1: Add tier_price_overrides column to attribute_value table
        self::add_tier_price_overrides_column();

        // Step 2: Add ingredient_category column to attribute_value table
        self::add_ingredient_category_column();
    }

    /**
     * Add tier_price_overrides JSON column to pls_attribute_value table.
     */
    private static function add_tier_price_overrides_column() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        // Check if column already exists
        $columns = $wpdb->get_col( "DESCRIBE {$table}" );

        if ( ! in_array( 'tier_price_overrides', $columns, true ) ) {
            $wpdb->query(
                "ALTER TABLE {$table} 
                ADD COLUMN tier_price_overrides TEXT NULL AFTER min_tier_level"
            );
        }
    }

    /**
     * Add ingredient_category column to pls_attribute_value table.
     */
    private static function add_ingredient_category_column() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        // Check if column already exists
        $columns = $wpdb->get_col( "DESCRIBE {$table}" );

        if ( ! in_array( 'ingredient_category', $columns, true ) ) {
            $wpdb->query(
                "ALTER TABLE {$table} 
                ADD COLUMN ingredient_category VARCHAR(50) NULL AFTER tier_price_overrides"
            );
        }
    }
}
