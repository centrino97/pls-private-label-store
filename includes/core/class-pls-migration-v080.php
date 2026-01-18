<?php
/**
 * Migration for v0.8.0: Pack Tiers as Attributes + Tier Rules
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_V080 {

    /**
     * Run migration if not already completed.
     */
    public static function maybe_migrate() {
        $migration_complete = get_option( 'pls_migration_v080_complete', false );
        if ( $migration_complete ) {
            return;
        }

        self::migrate();
        update_option( 'pls_migration_v080_complete', true );
    }

    /**
     * Execute migration steps.
     */
    private static function migrate() {
        global $wpdb;

        // Step 1: Alter pls_attribute_value table
        self::alter_attribute_value_table();

        // Step 2: Create default attributes
        require_once PLS_PLS_DIR . 'includes/core/class-pls-default-attributes.php';
        PLS_Default_Attributes::create_defaults();

        // Step 3: Migrate existing pack tier data (if any exists)
        self::migrate_existing_pack_tiers();
    }

    /**
     * Add tier-related columns to pls_attribute_value table.
     */
    private static function alter_attribute_value_table() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        // Check if columns already exist
        $columns = $wpdb->get_col( "DESCRIBE {$table}" );
        
        if ( ! in_array( 'min_tier_level', $columns, true ) ) {
            $wpdb->query(
                "ALTER TABLE {$table} 
                ADD COLUMN min_tier_level INT(11) DEFAULT 1 AFTER sort_order"
            );
        }

        if ( ! in_array( 'tier_price_overrides', $columns, true ) ) {
            $wpdb->query(
                "ALTER TABLE {$table} 
                ADD COLUMN tier_price_overrides LONGTEXT NULL AFTER min_tier_level"
            );
        }
    }

    /**
     * Migrate existing pls_pack_tier records to reference new Pack Tier attribute.
     * This preserves existing product configurations.
     */
    private static function migrate_existing_pack_tiers() {
        global $wpdb;
        
        $pack_tier_attr_id = get_option( 'pls_pack_tier_attribute_id' );
        if ( ! $pack_tier_attr_id ) {
            return; // No pack tier attribute created yet
        }

        // Get existing pack tiers
        $pack_tier_table = PLS_Repositories::table( 'pack_tier' );
        $existing_tiers = $wpdb->get_results( "SELECT DISTINCT tier_key, units FROM {$pack_tier_table} ORDER BY units ASC" );

        if ( empty( $existing_tiers ) ) {
            return;
        }

        // Map old tier keys to new tier levels
        $tier_mapping = array(
            'u50'   => 1,
            'u100'  => 2,
            'u250'  => 3,
            'u500'  => 4,
            'u1000' => 5,
        );

        // Find corresponding attribute values and update them
        foreach ( $existing_tiers as $tier ) {
            $tier_level = isset( $tier_mapping[ $tier->tier_key ] ) ? $tier_mapping[ $tier->tier_key ] : null;
            if ( ! $tier_level ) {
                continue;
            }

            // Find attribute value by tier level (assuming they're named "Tier 1", "Tier 2", etc.)
            $value_label = 'Tier ' . $tier_level;
            $value = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}pls_attribute_value 
                    WHERE attribute_id = %d AND label = %s",
                    $pack_tier_attr_id,
                    $value_label
                )
            );

            if ( $value ) {
                // Store units in term meta for reference
                $term = PLS_Repo_Attributes::get_value( $value->id );
                if ( $term && $term->term_id ) {
                    update_term_meta( $term->term_id, '_pls_tier_level', $tier_level );
                    update_term_meta( $term->term_id, '_pls_default_units', $tier->units );
                }
            }
        }
    }
}
