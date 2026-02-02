<?php
/**
 * Beta Features Manager
 * Controls access to experimental features that can be enabled/disabled individually.
 *
 * @package PLS_Private_Label_Store
 * @since 5.5.1 - Added individual feature toggles
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Beta_Features {

    /**
     * Option name for beta features enabled setting (master toggle).
     *
     * @var string
     */
    const OPTION_NAME = 'pls_beta_features_enabled';

    /**
     * Option name for individual beta feature settings.
     *
     * @var string
     */
    const FEATURES_OPTION_NAME = 'pls_beta_features_individual';

    /**
     * List of beta feature identifiers with their metadata.
     * 
     * @since 5.5.1 - Added individual feature configuration
     * @var array
     */
    private static $beta_features = array(
        'wc_settings_warnings' => array(
            'label' => 'WooCommerce Settings Warnings',
            'description' => 'Show warnings for WooCommerce configuration issues',
            'category' => 'system',
        ),
        'stock_management_warnings' => array(
            'label' => 'Stock Management Warnings',
            'description' => 'Show warnings for stock management configuration',
            'category' => 'system',
        ),
        'tier_unlocking' => array(
            'label' => 'Tier Unlocking',
            'description' => 'Allow customers to unlock higher tiers based on purchase history',
            'category' => 'features',
        ),
        'inline_configurator' => array(
            'label' => 'Inline Configurator',
            'description' => 'Show product configurator inline instead of modal',
            'category' => 'features',
        ),
        'cro_features' => array(
            'label' => 'CRO Features',
            'description' => 'Conversion rate optimization features',
            'category' => 'features',
        ),
        'sample_data_completeness' => array(
            'label' => 'Sample Data Completeness',
            'description' => 'Extended sample data generation for testing',
            'category' => 'system',
        ),
        'landing_pages' => array(
            'label' => 'Landing Pages',
            'description' => 'Create custom landing pages for products and categories',
            'category' => 'features',
        ),
        'custom_printed_bottles' => array(
            'label' => 'Custom Printed Bottles',
            'description' => 'Allow customers to order custom printed bottles (Tier 4+)',
            'category' => 'product_options',
            'default' => false,
        ),
        'external_box_packaging' => array(
            'label' => 'External Box Packaging',
            'description' => 'Allow customers to add external box packaging (Tier 4+)',
            'category' => 'product_options',
            'default' => false,
        ),
    );

    /**
     * Initialize beta features system.
     */
    public static function init() {
        // No hooks needed - static methods only
    }

    /**
     * Check if master beta features toggle is enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) get_option( self::OPTION_NAME, false );
    }

    /**
     * Get list of all beta feature identifiers (keys only for backwards compatibility).
     *
     * @return array
     */
    public static function get_beta_features() {
        return array_keys( self::$beta_features );
    }

    /**
     * Get all beta features with their metadata.
     *
     * @since 5.5.1
     * @return array
     */
    public static function get_beta_features_with_meta() {
        return self::$beta_features;
    }

    /**
     * Get beta features grouped by category.
     *
     * @since 5.5.1
     * @return array
     */
    public static function get_beta_features_by_category() {
        $grouped = array();
        foreach ( self::$beta_features as $id => $meta ) {
            $category = isset( $meta['category'] ) ? $meta['category'] : 'other';
            if ( ! isset( $grouped[ $category ] ) ) {
                $grouped[ $category ] = array();
            }
            $grouped[ $category ][ $id ] = $meta;
        }
        return $grouped;
    }

    /**
     * Get individual feature settings.
     *
     * @since 5.5.1
     * @return array
     */
    public static function get_individual_settings() {
        $settings = get_option( self::FEATURES_OPTION_NAME, array() );
        return is_array( $settings ) ? $settings : array();
    }

    /**
     * Check if a specific feature is a beta feature.
     *
     * @param string $feature_id Feature identifier.
     * @return bool
     */
    public static function is_beta_feature( $feature_id ) {
        return isset( self::$beta_features[ $feature_id ] );
    }

    /**
     * Check if a specific beta feature is individually enabled.
     *
     * @since 5.5.1
     * @param string $feature_id Feature identifier.
     * @return bool
     */
    public static function is_feature_individually_enabled( $feature_id ) {
        if ( ! self::is_beta_feature( $feature_id ) ) {
            return true; // Not a beta feature, always available
        }
        
        $settings = self::get_individual_settings();
        
        // Check if individually set
        if ( isset( $settings[ $feature_id ] ) ) {
            return (bool) $settings[ $feature_id ];
        }
        
        // Check for default value
        if ( isset( self::$beta_features[ $feature_id ]['default'] ) ) {
            return (bool) self::$beta_features[ $feature_id ]['default'];
        }
        
        // Fallback to master toggle for backwards compatibility
        return self::is_enabled();
    }

    /**
     * Check if a beta feature should be available.
     * Returns true only if beta features are enabled AND the specific feature is enabled.
     *
     * @param string $feature_id Feature identifier.
     * @return bool
     */
    public static function is_feature_available( $feature_id ) {
        if ( ! self::is_beta_feature( $feature_id ) ) {
            return true; // Not a beta feature, always available
        }
        
        // For product option beta features, check individual setting only
        $meta = self::$beta_features[ $feature_id ];
        if ( isset( $meta['category'] ) && $meta['category'] === 'product_options' ) {
            return self::is_feature_individually_enabled( $feature_id );
        }
        
        // For other beta features, require master toggle AND individual setting
        return self::is_enabled() && self::is_feature_individually_enabled( $feature_id );
    }

    /**
     * Enable a specific beta feature.
     *
     * @since 5.5.1
     * @param string $feature_id Feature identifier.
     * @return bool Success status.
     */
    public static function enable_feature( $feature_id ) {
        $settings = self::get_individual_settings();
        $settings[ $feature_id ] = true;
        return update_option( self::FEATURES_OPTION_NAME, $settings );
    }

    /**
     * Disable a specific beta feature.
     *
     * @since 5.5.1
     * @param string $feature_id Feature identifier.
     * @return bool Success status.
     */
    public static function disable_feature( $feature_id ) {
        $settings = self::get_individual_settings();
        $settings[ $feature_id ] = false;
        return update_option( self::FEATURES_OPTION_NAME, $settings );
    }

    /**
     * Save multiple feature settings at once.
     *
     * @since 5.5.1
     * @param array $features Array of feature_id => enabled pairs.
     * @return bool Success status.
     */
    public static function save_feature_settings( $features ) {
        $settings = self::get_individual_settings();
        foreach ( $features as $feature_id => $enabled ) {
            if ( self::is_beta_feature( $feature_id ) ) {
                $settings[ $feature_id ] = (bool) $enabled;
            }
        }
        return update_option( self::FEATURES_OPTION_NAME, $settings );
    }

    /**
     * Enable master beta features toggle.
     *
     * @return bool Success status.
     */
    public static function enable() {
        return update_option( self::OPTION_NAME, true );
    }

    /**
     * Disable master beta features toggle.
     *
     * @return bool Success status.
     */
    public static function disable() {
        return update_option( self::OPTION_NAME, false );
    }
}
