<?php
/**
 * Migration for v0.11.0: Revenue/Commission separation, onboarding, and commission tracking.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_V110 {

    /**
     * Run migration if needed.
     */
    public static function maybe_migrate() {
        $migration_key = 'pls_migration_v110_completed';
        
        if ( get_option( $migration_key, false ) ) {
            return;
        }

        global $wpdb;
        $p = $wpdb->prefix;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Create onboarding_progress table
        $sql_onboarding = "CREATE TABLE {$p}pls_onboarding_progress (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            current_step VARCHAR(100) NULL,
            completed_steps LONGTEXT NULL,
            test_product_id BIGINT(20) UNSIGNED NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY current_step (current_step)
        ) $charset_collate;";

        dbDelta( $sql_onboarding );

        // Add status column to pls_order_commission if it doesn't exist
        $commission_table = $p . 'pls_order_commission';
        $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$commission_table} LIKE 'status'" );
        
        if ( empty( $column_exists ) ) {
            $wpdb->query( "ALTER TABLE {$commission_table} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER commission_amount" );
        }

        // Ensure paid_at exists
        $paid_at_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$commission_table} LIKE 'paid_at'" );
        if ( empty( $paid_at_exists ) ) {
            $wpdb->query( "ALTER TABLE {$commission_table} ADD COLUMN paid_at DATETIME NULL AFTER invoiced_at" );
        }

        // Add commission_confirmed to pls_custom_order
        $custom_order_table = $p . 'pls_custom_order';
        $confirmed_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$custom_order_table} LIKE 'commission_confirmed'" );
        
        if ( empty( $confirmed_exists ) ) {
            $wpdb->query( "ALTER TABLE {$custom_order_table} ADD COLUMN commission_confirmed TINYINT(1) NOT NULL DEFAULT 0 AFTER paid_at" );
        }

        // Create commission_reports table
        $sql_reports = "CREATE TABLE {$p}pls_commission_reports (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            month_year VARCHAR(7) NOT NULL,
            total_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            sent_at DATETIME NULL,
            marked_paid_at DATETIME NULL,
            marked_paid_by BIGINT(20) UNSIGNED NULL,
            PRIMARY KEY (id),
            UNIQUE KEY month_year (month_year),
            KEY sent_at (sent_at)
        ) $charset_collate;";

        dbDelta( $sql_reports );

        // Set default commission email recipients
        if ( ! get_option( 'pls_commission_email_recipients' ) ) {
            update_option( 'pls_commission_email_recipients', array( 'n.nikolic97@gmail.com' ) );
        }

        // Schedule monthly email cron
        if ( ! wp_next_scheduled( 'pls_monthly_commission_email' ) ) {
            // Schedule for 2nd of next month at 9:00 AM
            $next_month = date( 'Y-m-02 09:00:00', strtotime( '+1 month' ) );
            wp_schedule_event( strtotime( $next_month ), 'monthly', 'pls_monthly_commission_email' );
        }

        update_option( $migration_key, true );
    }
}
