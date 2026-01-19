<?php
/**
 * Migration for v1.3.0
 * 
 * Adds BI dashboard tables and sync tracking columns.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_v130 {

    /**
     * Run migration.
     */
    public static function run() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Add sync tracking columns to pls_base_product
        $table_name = $wpdb->prefix . 'pls_base_product';
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE 'last_synced_at'" );
        
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} 
                ADD COLUMN last_synced_at datetime DEFAULT NULL,
                ADD COLUMN sync_error text DEFAULT NULL" );
        }

        // Create marketing_cost table
        self::create_marketing_cost_table( $charset_collate );

        // Create revenue_snapshot table
        self::create_revenue_snapshot_table( $charset_collate );

        // Set migration flag
        update_option( 'pls_migration_v130_complete', true );
        update_option( 'pls_db_version', '1.3.0' );
    }

    /**
     * Create marketing_cost table.
     */
    private static function create_marketing_cost_table( $charset_collate ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pls_marketing_cost';

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cost_date date NOT NULL,
            channel varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            description text,
            created_at datetime NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY cost_date (cost_date),
            KEY channel (channel)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Create revenue_snapshot table.
     */
    private static function create_revenue_snapshot_table( $charset_collate ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pls_revenue_snapshot';

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            snapshot_date date NOT NULL,
            total_revenue decimal(10,2) NOT NULL DEFAULT 0.00,
            total_commission decimal(10,2) NOT NULL DEFAULT 0.00,
            total_marketing_cost decimal(10,2) NOT NULL DEFAULT 0.00,
            net_profit decimal(10,2) NOT NULL DEFAULT 0.00,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY snapshot_date (snapshot_date)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}
