<?php
/**
 * Main plugin bootstrap.
 *
 * v2.6.0: Simplified architecture - ALL WooCommerce products are PLS products.
 * Auto-sync is built into the AJAX handlers, with hooks for programmatic access.
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
        require_once PLS_PLS_DIR . 'includes/core/class-pls-helpers.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-debug.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-admin-notices.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-logger.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-capabilities.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-taxonomies.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-default-attributes.php';
        // Migrations are loaded on-demand by PLS_Activator when needed.
        require_once PLS_PLS_DIR . 'includes/core/class-pls-ingredient-sync.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-admin-dashboard-filter.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-commission-email.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-onboarding.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-seo-integration.php';
        require_once PLS_PLS_DIR . 'includes/core/class-pls-beta-features.php';

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
        require_once PLS_PLS_DIR . 'includes/data/repo-bundle.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-marketing-cost.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-revenue-snapshot.php';
        require_once PLS_PLS_DIR . 'includes/wc/class-pls-wc-sync.php';
        require_once PLS_PLS_DIR . 'includes/wc/class-pls-bundle-cart.php';

        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-ajax.php';
        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-custom-order-page.php';
        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-shortcodes.php';
        require_once PLS_PLS_DIR . 'includes/frontend/class-pls-frontend-display.php';

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
        PLS_Shortcodes::init();
        PLS_Elementor::init();
        PLS_Frontend_Display::init();
        PLS_Admin_Dashboard_Filter::init();
        PLS_Commission_Email::init();
        PLS_Onboarding::init();
        PLS_Bundle_Cart::init();
        PLS_SEO_Integration::init();

        // Hook ingredient sync
        add_action( 'created_pls_ingredient', array( 'PLS_Ingredient_Sync', 'on_ingredient_created' ) );
        add_action( 'edited_pls_ingredient', array( 'PLS_Ingredient_Sync', 'on_ingredient_updated' ) );
        add_action( 'delete_pls_ingredient', array( 'PLS_Ingredient_Sync', 'on_ingredient_deleted' ) );

        // Auto-sync hooks for programmatic extensibility (v2.6.0)
        // These allow external code to trigger syncs when products are changed programmatically
        add_action( 'pls_product_saved', array( 'PLS_WC_Sync', 'sync_base_product_to_wc' ) );
        add_action( 'pls_pack_tier_updated', array( $this, 'sync_all_products_on_tier_change' ) );

        // Hook WooCommerce order status changes for commission tracking
        // v2.6.0: Simplified - ALL WooCommerce orders are PLS orders
        if ( class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_order_status_changed', array( $this, 'check_order_payment' ), 10, 4 );
        }

        $this->maybe_upgrade();
    }

    /**
     * Sync all products when pack tier defaults change.
     * Triggered by the pls_pack_tier_updated action.
     */
    public function sync_all_products_on_tier_change() {
        if ( class_exists( 'PLS_WC_Sync' ) ) {
            PLS_WC_Sync::sync_all_base_products();
        }
    }

    /**
     * Get tier key from term name (delegates to PLS_Helpers).
     *
     * @param string $term_name Term name.
     * @return string|null Tier key like "tier_1" or null.
     */
    private function get_tier_key_from_term( $term_name ) {
        return PLS_Helpers::get_tier_key_from_term( $term_name );
    }

    /**
     * Get bundle key from product ID (delegates to PLS_Helpers).
     *
     * @param int $product_id WooCommerce product ID.
     * @return string|null Bundle key or null.
     */
    private function get_bundle_key_from_product( $product_id ) {
        return PLS_Helpers::get_bundle_key_from_product( $product_id );
    }

    /**
     * Check WooCommerce order payment status and create/update commission.
     * 
     * v2.6.0 Simplification: ALL WooCommerce products are PLS products,
     * so we process ALL orders without filtering.
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

        // Get commission rates
        $commission_rates = get_option( 'pls_commission_rates', array() );
        $tier_rates = isset( $commission_rates['tiers'] ) ? $commission_rates['tiers'] : array();
        $bundle_rates = isset( $commission_rates['bundles'] ) ? $commission_rates['bundles'] : array();
        $default_rate = isset( $commission_rates['default'] ) ? floatval( $commission_rates['default'] ) : 0.05;

        // Calculate and store commission for each item
        // v2.6.0: Process ALL items since ALL WooCommerce = PLS
        $items = $order->get_items();
        foreach ( $items as $item_id => $item ) {
            $product_id = $item->get_product_id();

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
            $units_sold = $quantity;

            // Check order item meta for bundle info (from cart detection)
            $bundle_key_meta = $item->get_meta( 'pls_bundle_key' );
            
            // If bundle item from cart detection, use bundle commission rate
            if ( $bundle_key_meta && isset( $bundle_rates[ $bundle_key_meta ] ) ) {
                $bundle_key = $bundle_key_meta;
                $commission_rate_per_unit = $bundle_rates[ $bundle_key ];
                
                // Calculate units: get from variation or use quantity
                $units = $quantity;
                if ( $item->get_variation_id() ) {
                    $variation = wc_get_product( $item->get_variation_id() );
                    if ( $variation ) {
                        $variation_units = (int) get_post_meta( $variation->get_id(), '_pls_units', true );
                        if ( ! $variation_units ) {
                            // Try pack tier term meta
                            $attributes = $variation->get_attributes();
                            if ( isset( $attributes['pa_pack-tier'] ) ) {
                                $tier_term = get_term_by( 'slug', $attributes['pa_pack-tier'], 'pa_pack-tier' );
                                if ( $tier_term ) {
                                    $variation_units = (int) get_term_meta( $tier_term->term_id, '_pls_default_units', true );
                                }
                            }
                        }
                        if ( $variation_units ) {
                            $units = $variation_units * $quantity;
                        }
                    }
                }
                $units_sold = $units;
                $commission_amount = $commission_rate_per_unit * $units;
            }
            
            // If no bundle commission found, check for pack tier
            if ( ! $commission_amount && $item->get_variation_id() ) {
                // Check if it's a variation (pack tier)
                $variation = wc_get_product( $item->get_variation_id() );
                if ( $variation ) {
                    $attributes = $variation->get_attributes();
                    
                    if ( isset( $attributes['pa_pack-tier'] ) ) {
                        $tier_term = get_term_by( 'slug', $attributes['pa_pack-tier'], 'pa_pack-tier' );
                        if ( $tier_term ) {
                            $tier_key = $this->get_tier_key_from_term( $tier_term->name );
                            if ( $tier_key && isset( $tier_rates[ $tier_key ] ) ) {
                                $commission_rate_per_unit = $tier_rates[ $tier_key ];
                                // Get units from variation meta or term meta
                                $units = (int) get_post_meta( $variation->get_id(), '_pls_units', true );
                                if ( ! $units ) {
                                    $units = (int) get_term_meta( $tier_term->term_id, '_pls_default_units', true );
                                }
                                if ( ! $units ) {
                                    $units = $quantity; // Fallback
                                }
                                $units_sold = $units * $quantity;
                                $commission_amount = $commission_rate_per_unit * $units_sold;
                            }
                        }
                    }
                }
            }
            
            // Check if it's a bundle product (Grouped Product)
            if ( ! $commission_amount ) {
                $bundle_key = $this->get_bundle_key_from_product( $product_id );
                if ( $bundle_key && isset( $bundle_rates[ $bundle_key ] ) ) {
                    $commission_rate_per_unit = $bundle_rates[ $bundle_key ];
                    $commission_amount = $commission_rate_per_unit * $quantity;
                }
            }

            // Fallback: use default commission rate based on item total
            if ( ! $commission_amount && $default_rate > 0 ) {
                $item_total = $item->get_total();
                $commission_amount = $item_total * $default_rate;
                $commission_rate_per_unit = $default_rate;
            }

            if ( $commission_amount > 0 ) {
                PLS_Repo_Commission::create(
                    array(
                        'wc_order_id'            => $order_id,
                        'wc_order_item_id'       => $item_id,
                        'product_id'             => $product_id,
                        'tier_key'               => $tier_key,
                        'bundle_key'             => $bundle_key,
                        'units'                  => $units_sold,
                        'commission_rate_per_unit' => $commission_rate_per_unit,
                        'commission_amount'      => $commission_amount,
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
