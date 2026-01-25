<?php
/**
 * Migration for v2.7.0
 * 
 * Fixes option_type mismatch (product_option -> product-option) and
 * ensures all product options are properly configured.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_v270 {

    /**
     * Run migration.
     */
    public static function run() {
        global $wpdb;

        error_log( '[PLS Migration v2.7.0] Starting migration...' );

        // Fix 1: Update option_type from 'product_option' (underscore) to 'product-option' (hyphen)
        $attributes_table = $wpdb->prefix . 'pls_attribute';
        
        $updated = $wpdb->query(
            "UPDATE {$attributes_table} 
             SET option_type = 'product-option' 
             WHERE option_type = 'product_option'"
        );
        
        if ( $updated > 0 ) {
            error_log( "[PLS Migration v2.7.0] Fixed option_type for {$updated} attributes (product_option -> product-option)" );
        }

        // Fix 2: Ensure Pack Tier attribute is marked as primary
        $pack_tier_attr = $wpdb->get_row(
            "SELECT * FROM {$attributes_table} 
             WHERE (option_type = 'pack_tier' OR option_type = 'pack-tier' OR attr_key = 'pack-tier') 
             LIMIT 1"
        );
        
        if ( $pack_tier_attr && ! $pack_tier_attr->is_primary ) {
            $wpdb->update(
                $attributes_table,
                array(
                    'is_primary'  => 1,
                    'option_type' => 'pack-tier',
                ),
                array( 'id' => $pack_tier_attr->id ),
                array( '%d', '%s' ),
                array( '%d' )
            );
            error_log( "[PLS Migration v2.7.0] Marked Pack Tier (ID: {$pack_tier_attr->id}) as primary" );
        }

        // Fix 3: Re-sync attributes to WooCommerce if available
        if ( class_exists( 'WooCommerce' ) ) {
            self::sync_attributes_to_woocommerce();
        }

        // Set migration flag
        update_option( 'pls_migration_v270_complete', true );
        update_option( 'pls_db_version', '2.7.0' );

        error_log( '[PLS Migration v2.7.0] Migration completed successfully' );
    }

    /**
     * Sync all PLS attributes to WooCommerce.
     */
    private static function sync_attributes_to_woocommerce() {
        // Load the sync class if not already loaded
        if ( ! class_exists( 'PLS_WC_Sync' ) ) {
            $sync_file = PLS_PLS_DIR . 'includes/wc/class-pls-wc-sync.php';
            if ( file_exists( $sync_file ) ) {
                require_once $sync_file;
            } else {
                error_log( '[PLS Migration v2.7.0] Could not load PLS_WC_Sync class' );
                return;
            }
        }

        // Sync attributes from PLS to WooCommerce
        if ( method_exists( 'PLS_WC_Sync', 'sync_attributes_from_pls' ) ) {
            try {
                PLS_WC_Sync::sync_attributes_from_pls();
                error_log( '[PLS Migration v2.7.0] Synced attributes to WooCommerce' );
            } catch ( Exception $e ) {
                error_log( '[PLS Migration v2.7.0] Error syncing attributes: ' . $e->getMessage() );
            }
        }
    }
}
