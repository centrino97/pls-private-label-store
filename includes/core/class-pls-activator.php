<?php
/**
 * Activation handler: creates DB tables via dbDelta.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Activator {

    public static function activate() {
        self::maybe_create_tables();
        self::maybe_run_migrations();
        update_option( 'pls_pls_version', PLS_PLS_VERSION );
    }

    /**
     * Run version-specific migrations.
     */
    private static function maybe_run_migrations() {
        $stored_version = get_option( 'pls_pls_version', '0.0.0' );
        
        // Run v0.8.0 migration if upgrading from earlier version
        if ( version_compare( $stored_version, '0.8.0', '<' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-migration-v080.php';
            PLS_Migration_V080::maybe_migrate();
        }
        
        // Run v0.8.3 migration if upgrading from earlier version
        if ( version_compare( $stored_version, '0.8.3', '<' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-migration-v083.php';
            PLS_Migration_V083::maybe_migrate();
        }
        
        // Run v0.9.0 migration if upgrading from earlier version
        if ( version_compare( $stored_version, '0.9.0', '<' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-migration-v090.php';
            PLS_Migration_V090::maybe_migrate();
        }
    }

    private static function maybe_create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;

        $tables = [];

        $tables[] = "CREATE TABLE {$p}pls_base_product (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            wc_product_id BIGINT(20) UNSIGNED NULL,
            slug VARCHAR(200) NOT NULL,
            name VARCHAR(255) NOT NULL,
            category_path TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY wc_product_id (wc_product_id),
            KEY status (status)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}pls_pack_tier (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            base_product_id BIGINT(20) UNSIGNED NOT NULL,
            tier_key VARCHAR(50) NOT NULL,
            units INT(11) NOT NULL DEFAULT 1,
            price DECIMAL(18,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NULL,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            wc_variation_id BIGINT(20) UNSIGNED NULL,
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY base_tier (base_product_id, tier_key),
            KEY base_product_id (base_product_id),
            KEY wc_variation_id (wc_variation_id),
            KEY tier_key (tier_key)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}pls_product_profile (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            base_product_id BIGINT(20) UNSIGNED NOT NULL,
            short_description TEXT NULL,
            long_description LONGTEXT NULL,
            featured_image_id BIGINT(20) UNSIGNED NULL,
            gallery_ids TEXT NULL,
            basics_json LONGTEXT NULL,
            skin_types_json LONGTEXT NULL,
            benefits_json LONGTEXT NULL,
            key_ingredients_json LONGTEXT NULL,
            directions_text LONGTEXT NULL,
            ingredients_list LONGTEXT NULL,
            label_enabled TINYINT(1) NOT NULL DEFAULT 0,
            label_price_per_unit DECIMAL(18,2) NULL,
            label_requires_file TINYINT(1) NOT NULL DEFAULT 0,
            label_helper_text TEXT NULL,
            label_guide_url VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY base_product_id (base_product_id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}pls_bundle (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            wc_product_id BIGINT(20) UNSIGNED NULL,
            bundle_key VARCHAR(50) NOT NULL,
            slug VARCHAR(200) NOT NULL,
            name VARCHAR(255) NOT NULL,
            base_price DECIMAL(18,2) NULL,
            pricing_mode VARCHAR(30) NOT NULL DEFAULT 'fixed', /* fixed|sum_discount */
            discount_amount DECIMAL(18,2) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            offer_rules_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY bundle_key (bundle_key),
            UNIQUE KEY slug (slug),
            KEY wc_product_id (wc_product_id),
            KEY status (status)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}pls_bundle_item (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bundle_id BIGINT(20) UNSIGNED NOT NULL,
            base_product_id BIGINT(20) UNSIGNED NOT NULL,
            tier_key VARCHAR(50) NOT NULL,
            units_override INT(11) NULL,
            qty INT(11) NOT NULL DEFAULT 1,
            sort_order INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY bundle_id (bundle_id),
            KEY base_product_id (base_product_id),
            KEY tier_key (tier_key)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}pls_attribute (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parent_attribute_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
            wc_attribute_id BIGINT(20) UNSIGNED NULL,
            attr_key VARCHAR(100) NOT NULL,
            label VARCHAR(255) NOT NULL,
            option_type VARCHAR(50) NOT NULL DEFAULT 'product-option',
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            is_variation TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY attr_key (attr_key),
            KEY parent_attribute_id (parent_attribute_id),
            KEY wc_attribute_id (wc_attribute_id),
            KEY option_type (option_type),
            KEY is_primary (is_primary),
            KEY is_variation (is_variation)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}pls_attribute_value (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            attribute_id BIGINT(20) UNSIGNED NOT NULL,
            term_id BIGINT(20) UNSIGNED NULL,
            value_key VARCHAR(100) NOT NULL,
            label VARCHAR(255) NOT NULL,
            seo_slug VARCHAR(200) NULL,
            seo_title VARCHAR(255) NULL,
            seo_description TEXT NULL,
            sort_order INT(11) NOT NULL DEFAULT 0,
            min_tier_level INT(11) DEFAULT 1,
            tier_price_overrides LONGTEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY attr_value (attribute_id, value_key),
            KEY attribute_id (attribute_id),
            KEY term_id (term_id),
            KEY min_tier_level (min_tier_level)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$p}pls_swatch (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            attribute_value_id BIGINT(20) UNSIGNED NOT NULL,
            swatch_type VARCHAR(20) NOT NULL DEFAULT 'label', /* label|color|icon|image */
            swatch_value VARCHAR(255) NULL,
            display_hint_json TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY attr_value (attribute_value_id),
            KEY swatch_type (swatch_type)
        ) $charset_collate;";

        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    }
}
