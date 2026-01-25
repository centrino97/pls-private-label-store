<?php
/**
 * Revenue tracking screen.
 * 
 * Since WooCommerce is ONLY used for PLS products, ALL orders are PLS orders.
 * No filtering needed - this is a simplified v2.6.0 approach.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get date range filter
$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : date( 'Y-m-01' );
$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : date( 'Y-m-d' );
$product_filter = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

// Get PLS products for filter dropdown
$pls_products = PLS_Repo_Base_Product::all();

// Get ALL WooCommerce orders - ALL orders are PLS orders (v2.6.0 simplification)
$orders_query_args = array(
    'limit'    => -1,
    'orderby'  => 'date',
    'order'    => 'DESC',
    'date_created' => $date_from . '...' . $date_to,
);

if ( $status_filter ) {
    $orders_query_args['status'] = 'wc-' . $status_filter;
} else {
    $orders_query_args['status'] = array( 'wc-completed', 'wc-processing', 'wc-on-hold' );
}

$orders_query = new WC_Order_Query( $orders_query_args );
$all_orders = $orders_query->get_orders();

// Process ALL orders - no PLS filtering needed (all WC = PLS)
$total_revenue = 0;
$orders_count = 0;
$revenue_by_product = array();
$revenue_by_tier = array();
$filtered_orders = array();

foreach ( $all_orders as $order ) {
    $items = $order->get_items();
    
    // Apply product filter if set
    if ( $product_filter ) {
        $has_product = false;
        foreach ( $items as $item ) {
            if ( $item->get_product_id() == $product_filter ) {
                $has_product = true;
                break;
            }
        }
        if ( ! $has_product ) {
            continue;
        }
    }
    
    // Track revenue by product and tier
    foreach ( $items as $item ) {
        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        $item_total = $item->get_total();
        
        // Track by product
        if ( ! isset( $revenue_by_product[ $product_id ] ) ) {
            $revenue_by_product[ $product_id ] = 0;
        }
        $revenue_by_product[ $product_id ] += $item_total;
        
        // Track by tier (from variation attributes)
        if ( $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( $variation ) {
                $attributes = $variation->get_attributes();
                if ( isset( $attributes['pa_pack-tier'] ) ) {
                    $tier = $attributes['pa_pack-tier'];
                    if ( ! isset( $revenue_by_tier[ $tier ] ) ) {
                        $revenue_by_tier[ $tier ] = 0;
                    }
                    $revenue_by_tier[ $tier ] += $item_total;
                }
            }
        }
    }
    
    $filtered_orders[] = $order;
    $total_revenue += $order->get_total();
    $orders_count++;
}

// Calculate average order value
$average_order_value = $orders_count > 0 ? $total_revenue / $orders_count : 0;

// Get top tier
$top_tier = '';
$top_tier_revenue = 0;
foreach ( $revenue_by_tier as $tier => $revenue ) {
    if ( $revenue > $top_tier_revenue ) {
        $top_tier_revenue = $revenue;
        $top_tier = $tier;
    }
}

// Get monthly revenue trend (last 6 months) - ALL orders
$monthly_trend = array();
for ( $i = 5; $i >= 0; $i-- ) {
    $month_start = date( 'Y-m-01', strtotime( "-{$i} months" ) );
    $month_end = date( 'Y-m-t', strtotime( "-{$i} months" ) );
    $month_label = date( 'M Y', strtotime( "-{$i} months" ) );
    
    $month_query = new WC_Order_Query(
        array(
            'limit'    => -1,
            'date_created' => $month_start . '...' . $month_end,
            'status'   => array( 'wc-completed', 'wc-processing' ),
        )
    );
    $month_orders = $month_query->get_orders();
    $month_revenue = 0;
    
    // Sum ALL order totals - no PLS filtering needed
    foreach ( $month_orders as $order ) {
        $month_revenue += $order->get_total();
    }
    
    $monthly_trend[] = array(
        'month' => $month_label,
        'revenue' => $month_revenue,
    );
}

// Get top 5 products
arsort( $revenue_by_product );
$top_products = array_slice( $revenue_by_product, 0, 5, true );
?>
<div class="wrap pls-wrap pls-page-revenue">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Revenue', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Sales Revenue', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Track total sales revenue from all orders.', 'pls-private-label-store' ); ?></p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="pls-revenue-summary">
        <div class="pls-revenue-card">
            <h3><?php esc_html_e( 'Total Revenue', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount"><?php echo wc_price( $total_revenue ); ?></div>
            <p class="description"><?php printf( esc_html__( '%d orders', 'pls-private-label-store' ), $orders_count ); ?></p>
        </div>
        <div class="pls-revenue-card">
            <h3><?php esc_html_e( 'Orders Count', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount"><?php echo esc_html( $orders_count ); ?></div>
        </div>
        <div class="pls-revenue-card">
            <h3><?php esc_html_e( 'Average Order Value', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount"><?php echo wc_price( $average_order_value ); ?></div>
        </div>
        <div class="pls-revenue-card">
            <h3><?php esc_html_e( 'Top Tier', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount" style="font-size: 18px;"><?php echo esc_html( ucfirst( str_replace( '-', ' ', $top_tier ) ) ); ?></div>
            <p class="description"><?php echo wc_price( $top_tier_revenue ); ?></p>
        </div>
    </div>

    <!-- Charts -->
    <div class="pls-revenue-charts">
        <div class="pls-chart-card">
            <h3><?php esc_html_e( 'Monthly Revenue Trend', 'pls-private-label-store' ); ?></h3>
            <div class="pls-chart-bars">
                <?php
                $max_revenue = ! empty( $monthly_trend ) ? max( array_column( $monthly_trend, 'revenue' ) ) : 0;
                foreach ( $monthly_trend as $month_data ) :
                    $height = $max_revenue > 0 ? ( $month_data['revenue'] / $max_revenue ) * 100 : 0;
                    ?>
                    <div class="pls-chart-bar">
                        <div class="pls-chart-bar__fill" style="height: <?php echo esc_attr( $height ); ?>%;"></div>
                        <div class="pls-chart-bar__label"><?php echo esc_html( $month_data['month'] ); ?></div>
                        <div class="pls-chart-bar__value"><?php echo wc_price( $month_data['revenue'] ); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pls-chart-card">
            <h3><?php esc_html_e( 'Top Products', 'pls-private-label-store' ); ?></h3>
            <div class="pls-top-products">
                <?php if ( empty( $top_products ) ) : ?>
                    <p><?php esc_html_e( 'No product sales yet.', 'pls-private-label-store' ); ?></p>
                <?php else : ?>
                    <?php
                    $max_product_revenue = max( $top_products );
                    foreach ( $top_products as $product_id => $revenue ) :
                        $product = wc_get_product( $product_id );
                        if ( ! $product ) {
                            continue;
                        }
                        $width = $max_product_revenue > 0 ? ( $revenue / $max_product_revenue ) * 100 : 0;
                        ?>
                        <div class="pls-top-product">
                            <div class="pls-top-product__name"><?php echo esc_html( $product->get_name() ); ?></div>
                            <div class="pls-top-product__bar">
                                <div class="pls-top-product__fill" style="width: <?php echo esc_attr( $width ); ?>%;"></div>
                            </div>
                            <div class="pls-top-product__value"><?php echo wc_price( $revenue ); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="pls-chart-card">
            <h3><?php esc_html_e( 'Revenue by Tier', 'pls-private-label-store' ); ?></h3>
            <div class="pls-tier-revenue">
                <?php if ( empty( $revenue_by_tier ) ) : ?>
                    <p><?php esc_html_e( 'No tier sales yet.', 'pls-private-label-store' ); ?></p>
                <?php else : ?>
                    <?php
                    $max_tier_revenue = max( $revenue_by_tier );
                    foreach ( $revenue_by_tier as $tier => $revenue ) :
                        $width = $max_tier_revenue > 0 ? ( $revenue / $max_tier_revenue ) * 100 : 0;
                        ?>
                        <div class="pls-tier-revenue-item">
                            <div class="pls-tier-revenue__name"><?php echo esc_html( ucfirst( str_replace( '-', ' ', $tier ) ) ); ?></div>
                            <div class="pls-tier-revenue__bar">
                                <div class="pls-tier-revenue__fill" style="width: <?php echo esc_attr( $width ); ?>%;"></div>
                            </div>
                            <div class="pls-tier-revenue__value"><?php echo wc_price( $revenue ); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="pls-revenue-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="pls-revenue" />
            <label>
                <?php esc_html_e( 'From:', 'pls-private-label-store' ); ?>
                <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
            </label>
            <label>
                <?php esc_html_e( 'To:', 'pls-private-label-store' ); ?>
                <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
            </label>
            <label>
                <?php esc_html_e( 'Product:', 'pls-private-label-store' ); ?>
                <select name="product_id">
                    <option value=""><?php esc_html_e( 'All Products', 'pls-private-label-store' ); ?></option>
                    <?php foreach ( $pls_products as $product ) : ?>
                        <option value="<?php echo esc_attr( $product->wc_product_id ); ?>" <?php selected( $product_filter, $product->wc_product_id ); ?>>
                            <?php echo esc_html( $product->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <?php esc_html_e( 'Status:', 'pls-private-label-store' ); ?>
                <select name="status">
                    <option value=""><?php esc_html_e( 'All Statuses', 'pls-private-label-store' ); ?></option>
                    <option value="completed" <?php selected( $status_filter, 'completed' ); ?>><?php esc_html_e( 'Completed', 'pls-private-label-store' ); ?></option>
                    <option value="processing" <?php selected( $status_filter, 'processing' ); ?>><?php esc_html_e( 'Processing', 'pls-private-label-store' ); ?></option>
                    <option value="on-hold" <?php selected( $status_filter, 'on-hold' ); ?>><?php esc_html_e( 'On Hold', 'pls-private-label-store' ); ?></option>
                </select>
            </label>
            <button type="submit" class="button"><?php esc_html_e( 'Filter', 'pls-private-label-store' ); ?></button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="pls-revenue-table-wrapper">
        <div class="pls-table-modern pls-table-modern--compact">
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Order #', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Customer', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Products', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Tier', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Total', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $filtered_orders ) ) : ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e( 'No orders found for this period.', 'pls-private-label-store' ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $filtered_orders as $order ) : ?>
                        <?php
                        $order_id = $order->get_id();
                        $order_date = $order->get_date_created()->date_i18n( get_option( 'date_format' ) );
                        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        $order_total = $order->get_total();
                        $order_status = $order->get_status();
                        $order_items = $order->get_items();
                        $product_names = array();
                        $tier_info = array();
                        
                        foreach ( $order_items as $item ) {
                            $product = $item->get_product();
                            if ( $product ) {
                                $product_names[] = $product->get_name();
                                
                                // Get tier info from variation
                                if ( $item->get_variation_id() ) {
                                    $variation = wc_get_product( $item->get_variation_id() );
                                    if ( $variation ) {
                                        $attributes = $variation->get_attributes();
                                        if ( isset( $attributes['pa_pack-tier'] ) ) {
                                            $tier_info[] = ucfirst( str_replace( '-', ' ', $attributes['pa_pack-tier'] ) );
                                        }
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
                            <td><?php echo esc_html( implode( ', ', array_slice( array_unique( $product_names ), 0, 2 ) ) ); ?></td>
                            <td><?php echo esc_html( implode( ', ', array_unique( $tier_info ) ) ); ?></td>
                            <td><strong><?php echo wc_price( $order_total ); ?></strong></td>
                            <td>
                                <span class="pls-status-badge pls-status-<?php echo esc_attr( $order_status ); ?>">
                                    <?php echo esc_html( wc_get_order_status_name( $order_status ) ); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
