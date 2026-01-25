<?php
/**
 * Plugin Name: PLS – Private Label Store Manager (Woo + Elementor)
 * Description: Internal data model + WooCommerce sync + Elementor widgets for pack tiers, swatches, and bundles (Hello Elementor ready).
 * Version: 4.4.0
 * Author: Z2HB team
 * Author URI: https://zerotoherobusiness.com
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Text Domain: pls-private-label-store
 *
 * @package PLS_Private_Label_Store
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PLS_PLS_VERSION', '4.4.0' );
define( 'PLS_PLS_FILE', __FILE__ );
define( 'PLS_PLS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PLS_PLS_URL', plugin_dir_url( __FILE__ ) );

// UUPD: Self-hosted updates from GitHub.
if ( ! class_exists( '\UUPD\V1\UUPD_Updater_V1' ) ) {
    require_once PLS_PLS_DIR . 'includes/updater.php';
}

add_action( 'plugins_loaded', function() {
    \UUPD\V1\UUPD_Updater_V1::register( [
        'plugin_file' => plugin_basename( PLS_PLS_FILE ),
        'slug'        => 'pls-private-label-store',
        'name'        => 'PLS – Private Label Store Manager (Woo + Elementor)',
        'version'     => PLS_PLS_VERSION,
        'server'      => 'https://raw.githubusercontent.com/centrino97/pls-private-label-store/main/uupd/index.json',
    ] );
    
    // Override remote URL to fetch JSON directly (bypass GitHub API detection)
    add_filter( 'uupd/remote_url/pls-private-label-store', function( $url, $slug ) {
        return 'https://raw.githubusercontent.com/centrino97/pls-private-label-store/main/uupd/index.json';
    }, 10, 2 );
}, 20 );

require_once PLS_PLS_DIR . 'includes/class-pls-plugin.php';

register_activation_hook( __FILE__, array( 'PLS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PLS_Plugin', 'deactivate' ) );

PLS_Plugin::instance();
