<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get date range filter
$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : date( 'Y-m-01' );
$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : date( 'Y-m-d' );

// Get commissions from synced product orders
$product_commissions = PLS_Repo_Commission::query(
    array(
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'limit'     => 1000,
    )
);

// Get custom order commissions
$custom_orders = PLS_Repo_Custom_Order::all();
$custom_commissions = array();
foreach ( $custom_orders as $order ) {
    if ( $order->nikola_commission_amount && $order->nikola_commission_amount > 0 ) {
        $order_date = date( 'Y-m-d', strtotime( $order->created_at ) );
        if ( $order_date >= $date_from && $order_date <= $date_to ) {
            $custom_commissions[] = $order;
        }
    }
}

// Calculate totals
$total_product_commission = PLS_Repo_Commission::get_total(
    array(
        'date_from' => $date_from,
        'date_to'   => $date_to,
    )
);

$total_custom_commission = 0;
foreach ( $custom_commissions as $order ) {
    $total_custom_commission += floatval( $order->nikola_commission_amount );
}

$total_commission = $total_product_commission + $total_custom_commission;

// Pending invoice (not invoiced)
$pending_invoice_product = PLS_Repo_Commission::get_total(
    array(
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'invoiced'  => false,
    )
);

$pending_invoice_custom = 0;
foreach ( $custom_commissions as $order ) {
    if ( ! $order->invoiced_at ) {
        $pending_invoice_custom += floatval( $order->nikola_commission_amount );
    }
}
$pending_invoice_total = $pending_invoice_product + $pending_invoice_custom;

// Paid
$paid_product = PLS_Repo_Commission::get_total(
    array(
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'paid'      => true,
    )
);

$paid_custom = 0;
foreach ( $custom_commissions as $order ) {
    if ( $order->paid_at ) {
        $paid_custom += floatval( $order->nikola_commission_amount );
    }
}
$paid_total = $paid_product + $paid_custom;

$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'products';
?>
<div class="wrap pls-wrap pls-page-revenue">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Revenue', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Commission Tracking', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Track commissions from PLS product orders and custom orders.', 'pls-private-label-store' ); ?></p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="pls-revenue-summary">
        <div class="pls-revenue-card">
            <h3><?php esc_html_e( 'Total Commission', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount"><?php echo wc_price( $total_commission ); ?></div>
            <p class="description">
                <?php echo wc_price( $total_product_commission ); ?> <?php esc_html_e( 'from products', 'pls-private-label-store' ); ?> + 
                <?php echo wc_price( $total_custom_commission ); ?> <?php esc_html_e( 'from custom orders', 'pls-private-label-store' ); ?>
            </p>
        </div>
        <div class="pls-revenue-card">
            <h3><?php esc_html_e( 'Pending Invoice', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount"><?php echo wc_price( $pending_invoice_total ); ?></div>
        </div>
        <div class="pls-revenue-card">
            <h3><?php esc_html_e( 'Paid', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount"><?php echo wc_price( $paid_total ); ?></div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="pls-revenue-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="pls-revenue" />
            <input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>" />
            <label>
                <?php esc_html_e( 'From:', 'pls-private-label-store' ); ?>
                <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
            </label>
            <label>
                <?php esc_html_e( 'To:', 'pls-private-label-store' ); ?>
                <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
            </label>
            <button type="submit" class="button"><?php esc_html_e( 'Filter', 'pls-private-label-store' ); ?></button>
        </form>
    </div>

    <!-- Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'products', admin_url( 'admin.php?page=pls-revenue' ) ) ); ?>" 
           class="nav-tab <?php echo 'products' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Synced Product Orders', 'pls-private-label-store' ); ?>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'custom', admin_url( 'admin.php?page=pls-revenue' ) ) ); ?>" 
           class="nav-tab <?php echo 'custom' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Custom Orders', 'pls-private-label-store' ); ?>
        </a>
    </nav>

    <!-- Product Orders Tab -->
    <?php if ( 'products' === $active_tab ) : ?>
        <div class="pls-revenue-table-wrapper">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Order #', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Units', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Rate/Unit', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Commission', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Invoiced', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Paid', 'pls-private-label-store' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $product_commissions ) ) : ?>
                        <tr>
                            <td colspan="8"><?php esc_html_e( 'No commissions found for this period.', 'pls-private-label-store' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $product_commissions as $commission ) : ?>
                            <?php
                            $order = wc_get_order( $commission->wc_order_id );
                            $order_date = $order ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '';
                            $type = $commission->bundle_key ? __( 'Bundle', 'pls-private-label-store' ) : __( 'Tier', 'pls-private-label-store' );
                            ?>
                            <tr>
                                <td>
                                    <?php if ( $order ) : ?>
                                        <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $commission->wc_order_id . '&action=edit' ) ); ?>">
                                            #<?php echo esc_html( $commission->wc_order_id ); ?>
                                        </a>
                                    <?php else : ?>
                                        #<?php echo esc_html( $commission->wc_order_id ); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $order_date ); ?></td>
                                <td><?php echo esc_html( $type ); ?></td>
                                <td><?php echo esc_html( $commission->units ); ?></td>
                                <td><?php echo wc_price( $commission->commission_rate_per_unit ); ?></td>
                                <td><strong><?php echo wc_price( $commission->commission_amount ); ?></strong></td>
                                <td>
                                    <?php if ( $commission->invoiced_at ) : ?>
                                        <span class="pls-status-badge pls-status-success"><?php esc_html_e( 'Yes', 'pls-private-label-store' ); ?></span>
                                    <?php else : ?>
                                        <span class="pls-status-badge"><?php esc_html_e( 'No', 'pls-private-label-store' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $commission->paid_at ) : ?>
                                        <span class="pls-status-badge pls-status-success"><?php esc_html_e( 'Yes', 'pls-private-label-store' ); ?></span>
                                    <?php else : ?>
                                        <span class="pls-status-badge"><?php esc_html_e( 'No', 'pls-private-label-store' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Custom Orders Tab -->
    <?php if ( 'custom' === $active_tab ) : ?>
        <div class="pls-revenue-table-wrapper">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Lead #', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Client', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Total Value', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Rate', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Commission', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Invoiced', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Paid', 'pls-private-label-store' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $custom_commissions ) ) : ?>
                        <tr>
                            <td colspan="8"><?php esc_html_e( 'No custom order commissions found for this period.', 'pls-private-label-store' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $custom_commissions as $order ) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-custom-orders' ) ); ?>">
                                        #<?php echo esc_html( $order->id ); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->created_at ) ) ); ?></td>
                                <td><?php echo esc_html( $order->contact_name ); ?></td>
                                <td><?php echo $order->total_value ? wc_price( $order->total_value ) : '-'; ?></td>
                                <td><?php echo esc_html( number_format( $order->nikola_commission_rate, 2 ) ); ?>%</td>
                                <td><strong><?php echo wc_price( $order->nikola_commission_amount ); ?></strong></td>
                                <td>
                                    <?php if ( $order->invoiced_at ) : ?>
                                        <span class="pls-status-badge pls-status-success"><?php esc_html_e( 'Yes', 'pls-private-label-store' ); ?></span>
                                    <?php else : ?>
                                        <span class="pls-status-badge"><?php esc_html_e( 'No', 'pls-private-label-store' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $order->paid_at ) : ?>
                                        <span class="pls-status-badge pls-status-success"><?php esc_html_e( 'Yes', 'pls-private-label-store' ); ?></span>
                                    <?php else : ?>
                                        <span class="pls-status-badge"><?php esc_html_e( 'No', 'pls-private-label-store' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
