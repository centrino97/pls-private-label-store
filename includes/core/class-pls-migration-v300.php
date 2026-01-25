<?php
/**
 * Migration for v3.0.0 - Custom order enhancements and WC order integration.
 *
 * Adds:
 * - wc_order_id to pls_custom_order table (link to WooCommerce order)
 * - sample_status to pls_custom_order table (tracking sample workflow)
 * - sample_cost to pls_custom_order table (cost of samples)
 * - sample_sent_date to pls_custom_order table (when samples were sent)
 * - sample_tracking to pls_custom_order table (tracking number)
 * - sample_feedback to pls_custom_order table (customer feedback on samples)
 * - converted_at to pls_custom_order table (when converted to WC order)
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_v300 {

    /**
     * Run the migration.
     */
    public static function run() {
        global $wpdb;

        $table = $wpdb->prefix . 'pls_custom_order';

        // Add wc_order_id column
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'wc_order_id'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN wc_order_id BIGINT(20) UNSIGNED NULL AFTER id" );
            error_log( '[PLS Migration v3.0.0] Added wc_order_id column to pls_custom_order table' );
        }

        // Add sample_status column
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'sample_status'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN sample_status VARCHAR(50) DEFAULT 'not_sent' AFTER timeline" );
            error_log( '[PLS Migration v3.0.0] Added sample_status column to pls_custom_order table' );
        }

        // Add sample_cost column
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'sample_cost'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN sample_cost DECIMAL(10,2) DEFAULT 0 AFTER sample_status" );
            error_log( '[PLS Migration v3.0.0] Added sample_cost column to pls_custom_order table' );
        }

        // Add sample_sent_date column
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'sample_sent_date'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN sample_sent_date DATE NULL AFTER sample_cost" );
            error_log( '[PLS Migration v3.0.0] Added sample_sent_date column to pls_custom_order table' );
        }

        // Add sample_tracking column
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'sample_tracking'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN sample_tracking VARCHAR(255) NULL AFTER sample_sent_date" );
            error_log( '[PLS Migration v3.0.0] Added sample_tracking column to pls_custom_order table' );
        }

        // Add sample_feedback column
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'sample_feedback'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN sample_feedback TEXT NULL AFTER sample_tracking" );
            error_log( '[PLS Migration v3.0.0] Added sample_feedback column to pls_custom_order table' );
        }

        // Add converted_at column
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'converted_at'" );
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN converted_at DATETIME NULL AFTER sample_feedback" );
            error_log( '[PLS Migration v3.0.0] Added converted_at column to pls_custom_order table' );
        }

        // Set default sample_status for existing orders
        $wpdb->query( "UPDATE {$table} SET sample_status = 'not_sent' WHERE sample_status IS NULL" );

        error_log( '[PLS Migration v3.0.0] Migration completed successfully' );
    }
}
