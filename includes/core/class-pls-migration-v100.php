<?php
/**
 * Migration for v0.10.0: Custom orders and commission tracking.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Migration_V100 {

    /**
     * Run migration if needed.
     */
    public static function maybe_migrate() {
        $migration_key = 'pls_migration_v100_completed';
        
        if ( get_option( $migration_key, false ) ) {
            return;
        }

        global $wpdb;
        $p = $wpdb->prefix;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Create custom_order table
        $sql_custom_order = "CREATE TABLE {$p}pls_custom_order (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            status VARCHAR(20) NOT NULL DEFAULT 'new_lead',
            contact_name VARCHAR(255) NOT NULL,
            contact_email VARCHAR(255) NOT NULL,
            contact_phone VARCHAR(50) NULL,
            company_name VARCHAR(255) NULL,
            category_id BIGINT(20) UNSIGNED NULL,
            message LONGTEXT NULL,
            quantity_needed INT(11) NULL,
            budget DECIMAL(18,2) NULL,
            timeline VARCHAR(255) NULL,
            production_cost DECIMAL(18,2) NULL,
            total_value DECIMAL(18,2) NULL,
            nikola_commission_rate DECIMAL(5,2) NULL DEFAULT 3.00,
            nikola_commission_amount DECIMAL(18,2) NULL,
            invoiced_at DATETIME NULL,
            paid_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY category_id (category_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta( $sql_custom_order );

        // Create order_commission table
        $sql_commission = "CREATE TABLE {$p}pls_order_commission (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            wc_order_id BIGINT(20) UNSIGNED NOT NULL,
            wc_order_item_id BIGINT(20) UNSIGNED NULL,
            product_id BIGINT(20) UNSIGNED NULL,
            tier_key VARCHAR(50) NULL,
            bundle_key VARCHAR(50) NULL,
            units INT(11) NOT NULL DEFAULT 0,
            commission_rate_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            commission_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            invoiced_at DATETIME NULL,
            paid_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY wc_order_id (wc_order_id),
            KEY wc_order_item_id (wc_order_item_id),
            KEY product_id (product_id),
            KEY tier_key (tier_key),
            KEY bundle_key (bundle_key),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta( $sql_commission );

        // Set default commission rates
        $default_rates = array(
            'tiers' => array(
                'tier_1' => 0.80,
                'tier_2' => 0.75,
                'tier_3' => 0.65,
                'tier_4' => 0.40,
                'tier_5' => 0.29,
            ),
            'bundles' => array(
                'mini_line' => 0.59,
                'starter_line' => 0.49,
                'growth_line' => 0.32,
                'premium_line' => 0.25,
            ),
            'custom_order_percent' => 3.00,
        );

        if ( ! get_option( 'pls_commission_rates' ) ) {
            update_option( 'pls_commission_rates', $default_rates );
        }

        // Create custom-order page if it doesn't exist
        self::create_custom_order_page();

        update_option( $migration_key, true );
    }

    /**
     * Create the /custom-order page on activation.
     */
    private static function create_custom_order_page() {
        $page_slug = 'custom-order';
        
        // Check if page already exists
        $existing = get_page_by_path( $page_slug );
        if ( $existing ) {
            return;
        }

        $page_data = array(
            'post_title'   => __( 'Custom Order Request', 'pls-private-label-store' ),
            'post_content' => '[pls_custom_order_form]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => $page_slug,
        );

        wp_insert_post( $page_data );
    }
}
