<?php
/**
 * Admin notices for missing dependencies (WooCommerce, Elementor).
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Admin_Notices {

    public static function init() {
        add_action( 'admin_notices', array( __CLASS__, 'render' ) );
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $issues = array();

        if ( ! class_exists( 'WooCommerce' ) ) {
            $issues[] = 'WooCommerce is not active. PLS requires WooCommerce.';
        }

        if ( ! did_action( 'elementor/loaded' ) ) {
            $issues[] = 'Elementor is not active. PLS requires Elementor (and is designed for Elementor Pro + Hello Elementor).';
        }

        // Elementor baseline requirements (keep aligned with Elementor).
        global $wp_version;
        if ( version_compare( $wp_version, '6.5', '<' ) ) {
            $issues[] = 'WordPress 6.5+ is recommended/required for this stack.';
        }
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            $issues[] = 'PHP 7.4+ is required for this stack.';
        }

        if ( empty( $issues ) ) {
            return;
        }

        echo '<div class="notice notice-warning"><p><strong>PLS – Private Label Store Manager</strong></p><ul>';
        foreach ( $issues as $msg ) {
            echo '<li>' . esc_html( $msg ) . '</li>';
        }
        echo '</ul></div>';
    }
}
