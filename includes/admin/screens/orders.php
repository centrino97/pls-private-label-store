<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_orders' ) ) {
    $woocommerce_active = false;
} else {
    $woocommerce_active = true;
}

// Get PLS products to identify which WooCommerce orders contain them
$pls_products = PLS_Repo_Base_Product::all();
$pls_wc_ids   = array();
foreach ( $pls_products as $product ) {
    if ( $product->wc_product_id ) {
        $pls_wc_ids[] = $product->wc_product_id;
        
        // Also include variation IDs if product is variable (for proper order detection)
        $wc_product = wc_get_product( $product->wc_product_id );
        if ( $wc_product && $wc_product->is_type( 'variable' ) ) {
            $variations = $wc_product->get_children();
            $pls_wc_ids = array_merge( $pls_wc_ids, $variations );
        }
    }
}

// Also include bundle WooCommerce product IDs
require_once PLS_PLS_DIR . 'includes/data/repo-bundle.php';
$bundles = PLS_Repo_Bundle::all();
foreach ( $bundles as $bundle ) {
    if ( $bundle->wc_product_id ) {
        $pls_wc_ids[] = $bundle->wc_product_id;
    }
}

// Get ALL WooCommerce orders - Since WooCommerce is ONLY used for PLS products, ALL orders are PLS orders
$pls_orders = array();
if ( $woocommerce_active ) {
    $orders_query = new WC_Order_Query(
        array(
            'limit'    => 100,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'status'   => array( 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed' ),
        )
    );
    $pls_orders = $orders_query->get_orders();
}

// Get commission rates
$commission_rates = get_option( 'pls_commission_rates', array() );
$tier_rates       = isset( $commission_rates['tiers'] ) ? $commission_rates['tiers'] : array();
$bundle_rates     = isset( $commission_rates['bundles'] ) ? $commission_rates['bundles'] : array();
?>
<div class="wrap pls-wrap pls-page-orders">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Orders', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Orders', 'pls-private-label-store' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'All WooCommerce orders with commission tracking. Commission is automatically calculated for PLS products based on pack tier or bundle type.', 'pls-private-label-store' ); ?>
                <span class="pls-help-icon" title="<?php esc_attr_e( 'Commission rates are configured in Settings. Pack tier commissions are per unit, bundle commissions are per bundle unit.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px;">ⓘ</span>
            </p>
        </div>
    </div>

    <?php if ( ! $woocommerce_active ) : ?>
        <div class="pls-card">
            <p><?php esc_html_e( 'WooCommerce is not active. Please activate WooCommerce to view orders.', 'pls-private-label-store' ); ?></p>
        </div>
    <?php elseif ( empty( $pls_orders ) ) : ?>
        <div class="pls-card">
            <p><?php esc_html_e( 'No WooCommerce orders found. Orders will appear here once customers make purchases.', 'pls-private-label-store' ); ?></p>
            <p style="margin-top: 16px;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-orders' ) ); ?>" class="button button-secondary">
                    <?php esc_html_e( 'View All WooCommerce Orders', 'pls-private-label-store' ); ?>
                </a>
            </p>
        </div>
    <?php else : ?>
        <div class="pls-table-modern pls-table-modern--compact">
            <table>
                <thead>
                    <tr>
                        <th title="<?php esc_attr_e( 'Click order number to view full details', 'pls-private-label-store' ); ?>"><?php esc_html_e( 'Order #', 'pls-private-label-store' ); ?></th>
                        <th title="<?php esc_attr_e( 'Date the order was placed', 'pls-private-label-store' ); ?>"><?php esc_html_e( 'Date', 'pls-private-label-store' ); ?></th>
                        <th title="<?php esc_attr_e( 'Customer billing name', 'pls-private-label-store' ); ?>"><?php esc_html_e( 'Customer', 'pls-private-label-store' ); ?></th>
                        <th title="<?php esc_attr_e( 'Products in this order (shows first 3)', 'pls-private-label-store' ); ?>"><?php esc_html_e( 'Products', 'pls-private-label-store' ); ?></th>
                        <th title="<?php esc_attr_e( 'Total order value including tax and shipping', 'pls-private-label-store' ); ?>"><?php esc_html_e( 'Total', 'pls-private-label-store' ); ?></th>
                        <th title="<?php esc_attr_e( 'Commission calculated for PLS products in this order. Based on pack tier or bundle commission rates.', 'pls-private-label-store' ); ?>">
                            <?php esc_html_e( 'Commission', 'pls-private-label-store' ); ?>
                            <span class="pls-help-icon" title="<?php esc_attr_e( 'Commission rates are set in Settings → Commission Rates', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px; font-size: 12px;">ⓘ</span>
                        </th>
                        <th title="<?php esc_attr_e( 'Current order status: Completed, Processing, On Hold, Pending, Cancelled, Refunded, or Failed', 'pls-private-label-store' ); ?>"><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $pls_orders as $order ) : ?>
                    <?php
                    $order_id        = $order->get_id();
                    $order_date      = $order->get_date_created()->date_i18n( get_option( 'date_format' ) );
                    $customer_name   = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                    $order_total     = $order->get_total();
                    $order_status    = $order->get_status();
                    $order_items     = $order->get_items();
                    $total_commission = 0;
                    $product_names   = array();

                    // Calculate commission for each item
                    foreach ( $order_items as $item_id => $item ) {
                        $product_id = $item->get_product_id();
                        $variation_id = $item->get_variation_id();
                        $product = $item->get_product();
                        
                        if ( $product ) {
                            $product_names[] = $product->get_name();
                        } else {
                            $product_names[] = sprintf( __( 'Product #%d (deleted)', 'pls-private-label-store' ), $product_id );
                        }
                        
                        $quantity = $item->get_quantity();
                        
                        // Check if this is a PLS product (for commission calculation)
                        $is_pls_product = false;
                        if ( ! empty( $pls_wc_ids ) ) {
                            if ( in_array( $product_id, $pls_wc_ids, true ) ) {
                                $is_pls_product = true;
                            } elseif ( $variation_id && in_array( $variation_id, $pls_wc_ids, true ) ) {
                                $is_pls_product = true;
                            }
                        }
                        
                        // Only calculate commission for PLS products
                        if ( ! $is_pls_product ) {
                            continue;
                        }

                        // Check if it's a variation (pack tier)
                        if ( $variation_id ) {
                            $variation = wc_get_product( $variation_id );
                            $attributes = $variation->get_attributes();
                            
                            // Find tier key from attributes
                            $tier_key = null;
                            if ( isset( $attributes['pa_pack-tier'] ) ) {
                                $tier_term = get_term_by( 'slug', $attributes['pa_pack-tier'], 'pa_pack-tier' );
                                if ( $tier_term ) {
                                    // Map tier term to tier key (tier_1, tier_2, etc.)
                                    $tier_key = pls_get_tier_key_from_term( $tier_term->name );
                                }
                            }

                            if ( $tier_key && isset( $tier_rates[ $tier_key ] ) ) {
                                $rate_per_unit = $tier_rates[ $tier_key ];
                                $commission    = $rate_per_unit * $quantity;
                                $total_commission += $commission;

                                // Store commission if not already stored
                                $existing = PLS_Repo_Commission::get_by_order( $order_id );
                                $found = false;
                                foreach ( $existing as $comm ) {
                                    if ( $comm->wc_order_item_id == $item_id ) {
                                        $found = true;
                                        break;
                                    }
                                }
                                if ( ! $found ) {
                                    PLS_Repo_Commission::create(
                                        array(
                                            'wc_order_id'            => $order_id,
                                            'wc_order_item_id'       => $item_id,
                                            'product_id'              => $product_id,
                                            'tier_key'                => $tier_key,
                                            'units'                   => $quantity,
                                            'commission_rate_per_unit' => $rate_per_unit,
                                            'commission_amount'      => $commission,
                                        )
                                    );
                                }
                            }
                        } else {
                            // Check if it's a bundle
                            $bundle_key = pls_get_bundle_key_from_product( $product_id );
                            if ( $bundle_key && isset( $bundle_rates[ $bundle_key ] ) ) {
                                $rate_per_unit = $bundle_rates[ $bundle_key ];
                                $commission    = $rate_per_unit * $quantity;
                                $total_commission += $commission;

                                // Store commission if not already stored
                                $existing = PLS_Repo_Commission::get_by_order( $order_id );
                                $found = false;
                                foreach ( $existing as $comm ) {
                                    if ( $comm->wc_order_item_id == $item_id ) {
                                        $found = true;
                                        break;
                                    }
                                }
                                if ( ! $found ) {
                                    PLS_Repo_Commission::create(
                                        array(
                                            'wc_order_id'            => $order_id,
                                            'wc_order_item_id'       => $item_id,
                                            'product_id'              => $product_id,
                                            'bundle_key'              => $bundle_key,
                                            'units'                   => $quantity,
                                            'commission_rate_per_unit' => $rate_per_unit,
                                            'commission_amount'      => $commission,
                                        )
                                    );
                                }
                            }
                        }
                    }
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-order-detail&order_id=' . $order_id ) ); ?>">
                                #<?php echo esc_html( $order_id ); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html( $order_date ); ?></td>
                        <td><?php echo esc_html( $customer_name ); ?></td>
                        <td><?php echo esc_html( implode( ', ', array_slice( $product_names, 0, 3 ) ) ); ?></td>
                        <td><?php echo wc_price( $order_total ); ?></td>
                        <td><strong><?php echo wc_price( $total_commission ); ?></strong></td>
                        <td><span class="pls-status-badge pls-status-<?php echo esc_attr( $order_status ); ?>">
                            <?php echo esc_html( wc_get_order_status_name( $order_status ) ); ?>
                        </span></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-order-detail&order_id=' . $order_id ) ); ?>" 
                               class="button button-small pls-btn--ghost"
                               title="<?php esc_attr_e( 'View full order details including items, commission breakdown, and customer information', 'pls-private-label-store' ); ?>">
                                <?php esc_html_e( 'View', 'pls-private-label-store' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
/**
 * Helper functions — delegated to PLS_Helpers.
 */
if ( ! function_exists( 'pls_get_tier_key_from_term' ) ) {
    function pls_get_tier_key_from_term( $term_name ) {
        return PLS_Helpers::get_tier_key_from_term( $term_name );
    }
}

if ( ! function_exists( 'pls_get_bundle_key_from_product' ) ) {
    function pls_get_bundle_key_from_product( $product_id ) {
        return PLS_Helpers::get_bundle_key_from_product( $product_id );
    }
}
?>
