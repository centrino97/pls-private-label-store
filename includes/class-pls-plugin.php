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
        require_once PLS_PLS_DIR . 'includes/core/class-pls-taxonomies.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-default-attributes.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-migration-v080.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-migration-v083.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-migration-v090.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-migration-v100.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-migration-v110.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-ingredient-sync.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-admin-dashboard-filter.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-commission-email.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-onboarding.php';

        require_once PLS_PLS_DIR . 'includes/admin/class-pls-admin-menu.php';
        require_once PLS_PLS_DIR . 'includes/admin/class-pls-admin-ajax.php';

        require_once PLS_PLS_DIR . 'includes/data/class-pls-repositories.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-base-product.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-pack-tier.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-attributes.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-product-profile.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-custom-order.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-commission.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-commission-report.php';
        require_once PLS_PLS_DIR . 'includes/wc/class-pls-wc-sync.php';

        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-ajax.php';
        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-custom-order-page.php';

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
        PLS_Taxonomies::init();
        PLS_Admin_Ajax::init();
        PLS_Admin_Menu::init();
        PLS_Ajax::init();
        PLS_Custom_Order_Page::init();
        PLS_Elementor::init();
        PLS_Admin_Dashboard_Filter::init();
        PLS_Commission_Email::init();
        PLS_Onboarding::init();

        // Hook ingredient sync
        add_action( 'created_pls_ingredient', array( 'PLS_Ingredient_Sync', 'on_ingredient_created' ) );
        add_action( 'edited_pls_ingredient', array( 'PLS_Ingredient_Sync', 'on_ingredient_updated' ) );
        add_action( 'delete_pls_ingredient', array( 'PLS_Ingredient_Sync', 'on_ingredient_deleted' ) );

        // Hook WooCommerce order status changes for commission tracking
        if ( class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_order_status_changed', array( $this, 'check_order_payment' ), 10, 4 );
        }

        $this->maybe_upgrade();
    }

    /**
     * Check WooCommerce order payment status and create/update commission.
     *
     * @param int    $order_id Order ID.
     * @param string $from     Previous status.
     * @param string $to       New status.
     * @param object $order    Order object.
     */
    public function check_order_payment( $order_id, $from, $to, $order ) {
        if ( ! $order ) {
            return;
        }

        // Paid statuses: processing, completed (any status that indicates payment received)
        $paid_statuses = array( 'processing', 'completed' );
        
        if ( ! in_array( $to, $paid_statuses, true ) ) {
            return;
        }

        // Get PLS products
        $pls_products = PLS_Repo_Base_Product::all();
        $pls_wc_ids = array();
        foreach ( $pls_products as $product ) {
            if ( $product->wc_product_id ) {
                $pls_wc_ids[] = $product->wc_product_id;
            }
        }

        // Check if order contains PLS products
        $items = $order->get_items();
        $order_contains_pls = false;
        
        foreach ( $items as $item ) {
            if ( in_array( $item->get_product_id(), $pls_wc_ids, true ) ) {
                $order_contains_pls = true;
                break;
            }
        }

        if ( ! $order_contains_pls ) {
            return;
        }

        // Get commission rates
        $commission_rates = get_option( 'pls_commission_rates', array() );
        $tier_rates = isset( $commission_rates['tiers'] ) ? $commission_rates['tiers'] : array();
        $bundle_rates = isset( $commission_rates['bundles'] ) ? $commission_rates['bundles'] : array();

        // Calculate and store commission for each item
        foreach ( $items as $item_id => $item ) {
            $product_id = $item->get_product_id();
            if ( ! in_array( $product_id, $pls_wc_ids, true ) ) {
                continue;
            }

            // Check if commission already exists
            $existing = PLS_Repo_Commission::get_by_order( $order_id );
            $found = false;
            foreach ( $existing as $comm ) {
                if ( $comm->wc_order_item_id == $item_id ) {
                    $found = true;
                    break;
                }
            }

            if ( $found ) {
                continue; // Already has commission record
            }

            $quantity = $item->get_quantity();
            $commission_rate_per_unit = 0;
            $commission_amount = 0;
            $tier_key = null;
            $bundle_key = null;

            // Check if it's a variation (pack tier)
            if ( $item->get_variation_id() ) {
                $variation = wc_get_product( $item->get_variation_id() );
                $attributes = $variation->get_attributes();
                
                if ( isset( $attributes['pa_pack-tier'] ) ) {
                    $tier_term = get_term_by( 'slug', $attributes['pa_pack-tier'], 'pa_pack-tier' );
                    if ( $tier_term ) {
                        $tier_key = pls_get_tier_key_from_term( $tier_term->name );
                        if ( $tier_key && isset( $tier_rates[ $tier_key ] ) ) {
                            $commission_rate_per_unit = $tier_rates[ $tier_key ];
                            $commission_amount = $commission_rate_per_unit * $quantity;
                        }
                    }
                }
            } else {
                // Check if it's a bundle
                $bundle_key = pls_get_bundle_key_from_product( $product_id );
                if ( $bundle_key && isset( $bundle_rates[ $bundle_key ] ) ) {
                    $commission_rate_per_unit = $bundle_rates[ $bundle_key ];
                    $commission_amount = $commission_rate_per_unit * $quantity;
                }
            }

            if ( $commission_amount > 0 ) {
                PLS_Repo_Commission::create(
                    array(
                        'wc_order_id'            => $order_id,
                        'wc_order_item_id'      => $item_id,
                        'product_id'             => $product_id,
                        'tier_key'               => $tier_key,
                        'bundle_key'             => $bundle_key,
                        'units'                  => $quantity,
                        'commission_rate_per_unit' => $commission_rate_per_unit,
                        'commission_amount'     => $commission_amount,
                        'status'                 => 'pending',
                    )
                );
            }
        }
    }

    private function maybe_upgrade() {
        $stored = get_option( 'pls_pls_version' );
        if ( $stored !== PLS_PLS_VERSION ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-activator.php';
            PLS_Activator::activate();
        }
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
