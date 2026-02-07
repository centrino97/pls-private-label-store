<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_order' ) ) {
    wp_die( __( 'WooCommerce is not active.', 'pls-private-label-store' ) );
}

// Get order ID from query string
$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

if ( ! $order_id ) {
    wp_die( __( 'Invalid order ID.', 'pls-private-label-store' ) );
}

// Get WooCommerce order
$order = wc_get_order( $order_id );

if ( ! $order ) {
    wp_die( __( 'Order not found.', 'pls-private-label-store' ) );
}

// Get PLS products to check if this order contains PLS products
$pls_products = PLS_Repo_Base_Product::all();
$pls_wc_ids   = array();
foreach ( $pls_products as $product ) {
    if ( $product->wc_product_id ) {
        $pls_wc_ids[] = $product->wc_product_id;
        $wc_product = wc_get_product( $product->wc_product_id );
        if ( $wc_product && $wc_product->is_type( 'variable' ) ) {
            $variations = $wc_product->get_children();
            $pls_wc_ids = array_merge( $pls_wc_ids, $variations );
        }
    }
}

require_once PLS_PLS_DIR . 'includes/data/repo-bundle.php';
$bundles = PLS_Repo_Bundle::all();
foreach ( $bundles as $bundle ) {
    if ( $bundle->wc_product_id ) {
        $pls_wc_ids[] = $bundle->wc_product_id;
    }
}

// Check if order contains PLS products
$contains_pls = false;
$order_items = $order->get_items();
foreach ( $order_items as $item ) {
    $product_id = $item->get_product_id();
    $variation_id = $item->get_variation_id();
    if ( in_array( $product_id, $pls_wc_ids, true ) || ( $variation_id && in_array( $variation_id, $pls_wc_ids, true ) ) ) {
        $contains_pls = true;
        break;
    }
}

if ( ! $contains_pls ) {
    wp_die( __( 'This order does not contain PLS products.', 'pls-private-label-store' ) );
}

// Get commission rates
$commission_rates = get_option( 'pls_commission_rates', array() );
$tier_rates       = isset( $commission_rates['tiers'] ) ? $commission_rates['tiers'] : array();
$bundle_rates     = isset( $commission_rates['bundles'] ) ? $commission_rates['bundles'] : array();

// Calculate commission
$total_commission = 0;
$commission_items = array();

foreach ( $order_items as $item_id => $item ) {
    $product_id = $item->get_product_id();
    if ( ! in_array( $product_id, $pls_wc_ids, true ) ) {
        continue;
    }

    $product = $item->get_product();
    $quantity = $item->get_quantity();
    $line_total = $item->get_total();

    // Check if it's a variation (pack tier)
    if ( $item->get_variation_id() ) {
        $variation = wc_get_product( $item->get_variation_id() );
        $attributes = $variation->get_attributes();
        
        $tier_key = null;
        if ( isset( $attributes['pa_pack-tier'] ) ) {
            $tier_term = get_term_by( 'slug', $attributes['pa_pack-tier'], 'pa_pack-tier' );
            if ( $tier_term ) {
                $tier_key = pls_get_tier_key_from_term( $tier_term->name );
            }
        }

        if ( $tier_key && isset( $tier_rates[ $tier_key ] ) ) {
            $rate_per_unit = $tier_rates[ $tier_key ];
            $commission = $rate_per_unit * $quantity;
            $total_commission += $commission;
            
            $commission_items[] = array(
                'product' => $product->get_name(),
                'variation' => $variation->get_name(),
                'quantity' => $quantity,
                'rate' => $rate_per_unit,
                'commission' => $commission,
                'type' => 'tier',
            );
        }
    } else {
        // Check if it's a bundle
        $bundle_key = pls_get_bundle_key_from_product( $product_id );
        if ( $bundle_key && isset( $bundle_rates[ $bundle_key ] ) ) {
            $rate_per_unit = $bundle_rates[ $bundle_key ];
            $commission = $rate_per_unit * $quantity;
            $total_commission += $commission;
            
            $commission_items[] = array(
                'product' => $product->get_name(),
                'quantity' => $quantity,
                'rate' => $rate_per_unit,
                'commission' => $commission,
                'type' => 'bundle',
            );
        }
    }
}

// Helper functions — delegated to PLS_Helpers.
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
<div class="wrap pls-wrap pls-page-order-detail">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Orders', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Order #', 'pls-private-label-store' ); ?><?php echo esc_html( $order_id ); ?></h1>
            <p class="description"><?php esc_html_e( 'View order details and commission information.', 'pls-private-label-store' ); ?></p>
        </div>
        <div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-orders' ) ); ?>" class="button">
                <?php esc_html_e( '← Back to Orders', 'pls-private-label-store' ); ?>
            </a>
        </div>
    </div>

    <div class="pls-card-grid" style="grid-template-columns: 2fr 1fr;">
        <!-- Order Details -->
        <div class="pls-card">
            <div class="pls-card__header">
                <h2><?php esc_html_e( 'Order Details', 'pls-private-label-store' ); ?></h2>
            </div>
            <div class="pls-card__body">
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Order Number', 'pls-private-label-store' ); ?></th>
                        <td>#<?php echo esc_html( $order_id ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'pls-private-label-store' ); ?></th>
                        <td><?php echo esc_html( $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?></th>
                        <td>
                            <span class="pls-status-badge pls-status-<?php echo esc_attr( $order->get_status() ); ?>">
                                <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Customer', 'pls-private-label-store' ); ?></th>
                        <td>
                            <?php
                            $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
                            if ( empty( $customer_name ) ) {
                                $customer_name = __( 'Guest', 'pls-private-label-store' );
                            }
                            echo esc_html( $customer_name );
                            ?>
                            <?php if ( $order->get_billing_email() ) : ?>
                                <br><a href="mailto:<?php echo esc_attr( $order->get_billing_email() ); ?>"><?php echo esc_html( $order->get_billing_email() ); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <h3 style="margin-top: 24px;"><?php esc_html_e( 'Order Items', 'pls-private-label-store' ); ?></h3>
                <table class="pls-table-modern" style="margin-top: 12px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Product', 'pls-private-label-store' ); ?></th>
                            <th><?php esc_html_e( 'Quantity', 'pls-private-label-store' ); ?></th>
                            <th><?php esc_html_e( 'Price', 'pls-private-label-store' ); ?></th>
                            <th><?php esc_html_e( 'Total', 'pls-private-label-store' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $order_items as $item_id => $item ) : ?>
                            <tr>
                                <td>
                                    <?php echo esc_html( $item->get_name() ); ?>
                                    <?php
                                    $product_id = $item->get_product_id();
                                    $variation_id = $item->get_variation_id();
                                    if ( in_array( $product_id, $pls_wc_ids, true ) || ( $variation_id && in_array( $variation_id, $pls_wc_ids, true ) ) ) {
                                        echo ' <span class="pls-badge pls-badge--success">PLS</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html( $item->get_quantity() ); ?></td>
                                <td><?php echo wc_price( $item->get_subtotal() / $item->get_quantity() ); ?></td>
                                <td><strong><?php echo wc_price( $item->get_total() ); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--pls-gray-200);">
                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th style="text-align: right; padding-right: 16px;"><?php esc_html_e( 'Subtotal', 'pls-private-label-store' ); ?></th>
                            <td style="text-align: right;"><?php echo wc_price( $order->get_subtotal() ); ?></td>
                        </tr>
                        <?php if ( $order->get_total_tax() > 0 ) : ?>
                            <tr>
                                <th style="text-align: right; padding-right: 16px;"><?php esc_html_e( 'Tax', 'pls-private-label-store' ); ?></th>
                                <td style="text-align: right;"><?php echo wc_price( $order->get_total_tax() ); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ( $order->get_total_shipping() > 0 ) : ?>
                            <tr>
                                <th style="text-align: right; padding-right: 16px;"><?php esc_html_e( 'Shipping', 'pls-private-label-store' ); ?></th>
                                <td style="text-align: right;"><?php echo wc_price( $order->get_total_shipping() ); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th style="text-align: right; padding-right: 16px; font-size: 18px; font-weight: 600;"><?php esc_html_e( 'Total', 'pls-private-label-store' ); ?></th>
                            <td style="text-align: right; font-size: 18px; font-weight: 600;"><?php echo wc_price( $order->get_total() ); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Commission Information -->
        <div class="pls-card">
            <div class="pls-card__header">
                <h2><?php esc_html_e( 'Commission', 'pls-private-label-store' ); ?></h2>
            </div>
            <div class="pls-card__body">
                <?php if ( ! empty( $commission_items ) ) : ?>
                    <table class="form-table">
                        <?php foreach ( $commission_items as $comm_item ) : ?>
                            <tr>
                                <th><?php echo esc_html( $comm_item['product'] ); ?></th>
                                <td>
                                    <?php if ( isset( $comm_item['variation'] ) ) : ?>
                                        <small><?php echo esc_html( $comm_item['variation'] ); ?></small><br>
                                    <?php endif; ?>
                                    <?php echo esc_html( $comm_item['quantity'] ); ?> × <?php echo wc_price( $comm_item['rate'] ); ?> = 
                                    <strong><?php echo wc_price( $comm_item['commission'] ); ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--pls-gray-200);">
                        <table class="form-table" style="margin: 0;">
                            <tr>
                                <th style="text-align: right; padding-right: 16px; font-size: 16px; font-weight: 600;"><?php esc_html_e( 'Total Commission', 'pls-private-label-store' ); ?></th>
                                <td style="text-align: right; font-size: 16px; font-weight: 600; color: var(--pls-success);">
                                    <?php echo wc_price( $total_commission ); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php else : ?>
                    <p class="description"><?php esc_html_e( 'No commission calculated for this order.', 'pls-private-label-store' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
