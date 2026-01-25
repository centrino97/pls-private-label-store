<?php
/**
 * Migration for v2.3.0
 * 
 * Adds stock management and cost fields to pls_base_product table.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_v230 {

    /**
     * Run migration.
     */
    public static function run() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pls_base_product';

        // Add stock management columns
        self::add_column_if_not_exists( $table_name, 'stock_quantity', 'INT DEFAULT NULL' );
        self::add_column_if_not_exists( $table_name, 'manage_stock', 'TINYINT(1) DEFAULT 0' );
        self::add_column_if_not_exists( $table_name, 'stock_status', "VARCHAR(20) DEFAULT 'instock'" );
        self::add_column_if_not_exists( $table_name, 'backorders_allowed', 'TINYINT(1) DEFAULT 0' );
        self::add_column_if_not_exists( $table_name, 'low_stock_threshold', 'INT DEFAULT NULL' );

        // Add cost fields
        self::add_column_if_not_exists( $table_name, 'shipping_cost', 'DECIMAL(10,2) DEFAULT NULL' );
        self::add_column_if_not_exists( $table_name, 'packaging_cost', 'DECIMAL(10,2) DEFAULT NULL' );

        // Set migration flag
        update_option( 'pls_migration_v230_complete', true );
        update_option( 'pls_db_version', '2.3.0' );

        error_log( '[PLS Migration v2.3.0] Migration completed - added stock and cost columns to pls_base_product' );
    }

    /**
     * Add column to table if it doesn't exist.
     *
     * @param string $table_name Full table name.
     * @param string $column_name Column name to add.
     * @param string $column_definition Column definition (type, default, etc.).
     */
    private static function add_column_if_not_exists( $table_name, $column_name, $column_definition ) {
        global $wpdb;

        $column_exists = $wpdb->get_results(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table_name} LIKE %s", $column_name )
        );

        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN {$column_name} {$column_definition}" );
            error_log( "[PLS Migration v2.3.0] Added column {$column_name} to {$table_name}" );
        }
    }
}
