<?php
/**
 * Plugin Name: PLS – Private Label Store Manager (Woo + Elementor)
 * Description: Internal data model + WooCommerce sync + Elementor widgets for pack tiers, swatches, and bundles (Hello Elementor ready).
 * Version: 0.1.0
 * Author: (Your team)
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Text Domain: pls-private-label-store
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PLS_PLS_VERSION', '0.1.0' );
define( 'PLS_PLS_FILE', __FILE__ );
define( 'PLS_PLS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PLS_PLS_URL', plugin_dir_url( __FILE__ ) );

require_once PLS_PLS_DIR . 'includes/class-pls-plugin.php';

register_activation_hook( __FILE__, array( 'PLS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PLS_Plugin', 'deactivate' ) );

PLS_Plugin::instance();
