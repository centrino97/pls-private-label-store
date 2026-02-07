<?php
/**
 * Migration for v0.8.3: Hierarchical Product Options
 * Adds option_type, is_primary, and parent_attribute_id columns to pls_attribute table.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_V083 {

    /**
     * Run migration if not already completed.
     */
    public static function maybe_migrate() {
        $migration_complete = get_option( 'pls_migration_v083_complete', false );
        if ( $migration_complete ) {
            return;
        }

        self::migrate();
        update_option( 'pls_migration_v083_complete', true );
    }

    /**
     * Execute migration steps.
     */
    private static function migrate() {
        global $wpdb;

        // Step 1: Alter pls_attribute table to add new columns
        self::alter_attribute_table();

        // Step 2: Identify and mark Pack Tier as PRIMARY
        self::mark_pack_tier_as_primary();

        // Step 3: Set option_type for all existing attributes
        self::set_option_types();

        // Step 4: Sync existing ingredients to attribute system
        require_once PLS_PLS_DIR . 'includes/core/class-pls-ingredient-sync.php';
        PLS_Ingredient_Sync::sync_all_ingredients();
    }

    /**
     * Add hierarchy support columns to pls_attribute table.
     */
    private static function alter_attribute_table() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        // Check if columns already exist
        $columns = $wpdb->get_col( "DESCRIBE {$table}" );

        // Add parent_attribute_id column
        if ( ! in_array( 'parent_attribute_id', $columns, true ) ) {
            $wpdb->query(
                "ALTER TABLE {$table} 
                ADD COLUMN parent_attribute_id BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER id,
                ADD KEY parent_attribute_id (parent_attribute_id)"
            );
        }

        // Add option_type column
        if ( ! in_array( 'option_type', $columns, true ) ) {
            $wpdb->query(
                "ALTER TABLE {$table} 
                ADD COLUMN option_type VARCHAR(50) NOT NULL DEFAULT 'product-option' AFTER label,
                ADD KEY option_type (option_type)"
            );
        }

        // Add is_primary column
        if ( ! in_array( 'is_primary', $columns, true ) ) {
            $wpdb->query(
                "ALTER TABLE {$table} 
                ADD COLUMN is_primary TINYINT(1) NOT NULL DEFAULT 0 AFTER option_type,
                ADD KEY is_primary (is_primary)"
            );
        }
    }

    /**
     * Identify Pack Tier attribute and mark it as PRIMARY.
     */
    private static function mark_pack_tier_as_primary() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        // Find Pack Tier attribute by attr_key or option
        $pack_tier_attr_id = get_option( 'pls_pack_tier_attribute_id', false );
        
        if ( ! $pack_tier_attr_id ) {
            // Try to find by attr_key
            $pack_tier = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE attr_key = %s",
                    'pack-tier'
                )
            );
            
            if ( $pack_tier ) {
                $pack_tier_attr_id = $pack_tier->id;
                update_option( 'pls_pack_tier_attribute_id', $pack_tier_attr_id );
            }
        }

        if ( $pack_tier_attr_id ) {
            // Unset any existing primary attribute
            $wpdb->update(
                $table,
                array( 'is_primary' => 0 ),
                array( 'is_primary' => 1 ),
                array( '%d' ),
                array( '%d' )
            );

            // Set Pack Tier as primary
            $wpdb->update(
                $table,
                array(
                    'is_primary'  => 1,
                    'option_type' => 'pack-tier',
                ),
                array( 'id' => $pack_tier_attr_id ),
                array( '%d', '%s' ),
                array( '%d' )
            );
        }
    }

    /**
     * Set option_type for all existing attributes.
     */
    private static function set_option_types() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        // Get Pack Tier ID
        $pack_tier_attr_id = get_option( 'pls_pack_tier_attribute_id', false );

        // Set all non-primary attributes to 'product-option' (if not already set)
        if ( $pack_tier_attr_id ) {
            $where['id'] = $pack_tier_attr_id;
            $where_format[] = '%d';
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} 
                    SET option_type = 'product-option' 
                    WHERE id != %d AND (option_type = '' OR option_type IS NULL)",
                    $pack_tier_attr_id
                )
            );
        } else {
            // If no pack tier found, set all to product-option
            $wpdb->query(
                "UPDATE {$table} 
                SET option_type = 'product-option' 
                WHERE option_type = '' OR option_type IS NULL"
            );
        }
    }
}
