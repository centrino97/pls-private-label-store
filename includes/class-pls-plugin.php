<?php
/**
 * Main plugin bootstrap.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Plugin {

    private static $instance = null;

    /**
     * @return PLS_Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->setup();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Plugin activation.
     */
    public static function activate() {
        require_once PLS_PLS_DIR . 'includes/core/class-pls-activator.php';
        PLS_Activator::activate();
    }

    /**
     * Plugin deactivation.
     */
    public static function deactivate() {
        // Intentionally minimal. Keep data by default.
        // If you want cleanup options, add a setting and implement here.
    }

    /**
     * Bootstraps includes + hooks.
     */
    private function setup() {
        $this->includes();
        $this->hooks();
    }

    private function includes() {
        require_once PLS_PLS_DIR . 'includes/core/class-pls-admin-notices.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-logger.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-capabilities.php';

        require_once PLS_PLS_DIR . 'includes/admin/class-pls-admin-menu.php';

        require_once PLS_PLS_DIR . 'includes/data/class-pls-repositories.php';
        require_once PLS_PLS_DIR . 'includes/wc/class-pls-wc-sync.php';

        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-ajax.php';

        require_once PLS_PLS_DIR . 'includes/elementor/class-pls-elementor.php';
    }

    private function hooks() {
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );

        // HPOS compatibility declaration (safe even if HPOS is off).
        add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compat' ) );
    }

    public function on_plugins_loaded() {
        load_plugin_textdomain( 'pls-private-label-store', false, dirname( plugin_basename( PLS_PLS_FILE ) ) . '/languages' );

        PLS_Admin_Notices::init();
        PLS_Capabilities::init();
        PLS_Admin_Menu::init();
        PLS_Ajax::init();
        PLS_Elementor::init();
    }

    /**
     * Declare HPOS compatibility when WooCommerce is present.
     */
    public function declare_hpos_compat() {
        if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            return;
        }

        // Declare compatibility with custom order tables (HPOS).
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            PLS_PLS_FILE,
            true
        );
    }
}
