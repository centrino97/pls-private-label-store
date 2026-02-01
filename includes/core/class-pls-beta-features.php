<?php
/**
 * Beta Features Manager
 * Controls access to experimental features that can be enabled/disabled.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Beta_Features {

    /**
     * Option name for beta features enabled setting.
     *
     * @var string
     */
    const OPTION_NAME = 'pls_beta_features_enabled';

    /**
     * List of beta feature identifiers.
     *
     * @var array
     */
    private static $beta_features = array(
        'wc_settings_warnings',
        'stock_management_warnings',
        'tier_unlocking',
        'inline_configurator',
        'cro_features',
        'sample_data_completeness',
        'landing_pages',
    );

    /**
     * Initialize beta features system.
     */
    public static function init() {
        // No hooks needed - static methods only
    }

    /**
     * Check if beta features are enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) get_option( self::OPTION_NAME, false );
    }

    /**
     * Get list of all beta feature identifiers.
     *
     * @return array
     */
    public static function get_beta_features() {
        return self::$beta_features;
    }

    /**
     * Check if a specific feature is a beta feature.
     *
     * @param string $feature_id Feature identifier.
     * @return bool
     */
    public static function is_beta_feature( $feature_id ) {
        return in_array( $feature_id, self::$beta_features, true );
    }

    /**
     * Check if a beta feature should be available.
     * Returns true only if beta features are enabled AND the feature is a beta feature.
     *
     * @param string $feature_id Feature identifier.
     * @return bool
     */
    public static function is_feature_available( $feature_id ) {
        if ( ! self::is_beta_feature( $feature_id ) ) {
            return true; // Not a beta feature, always available
        }
        return self::is_enabled();
    }

    /**
     * Enable beta features.
     *
     * @return bool Success status.
     */
    public static function enable() {
        return update_option( self::OPTION_NAME, true );
    }

    /**
     * Disable beta features.
     *
     * @return bool Success status.
     */
    public static function disable() {
        return update_option( self::OPTION_NAME, false );
    }
}
