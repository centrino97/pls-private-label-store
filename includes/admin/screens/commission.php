<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get view mode
$view_mode = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'monthly';
$selected_month = isset( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : date( 'Y-m' );

// Get date range filter for detailed view
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

// Calculate monthly summary
$monthly_summary = array();
$months_to_show = array();

// Get last 12 months
for ( $i = 11; $i >= 0; $i-- ) {
    $month_key = date( 'Y-m', strtotime( "-{$i} months" ) );
    $months_to_show[] = $month_key;
    $month_start = $month_key . '-01';
    $month_end = date( 'Y-m-t', strtotime( $month_start ) );
    
    // Product commissions for this month
    $month_product_commissions = PLS_Repo_Commission::query(
        array(
            'date_from' => $month_start,
            'date_to'   => $month_end,
            'limit'     => 1000,
        )
    );
    
    $product_total = 0;
    foreach ( $month_product_commissions as $comm ) {
        $product_total += floatval( $comm->commission_amount );
    }
    
    // Custom order commissions for this month
    $custom_total = 0;
    foreach ( $custom_orders as $order ) {
        if ( $order->nikola_commission_amount ) {
            $order_month = date( 'Y-m', strtotime( $order->created_at ) );
            if ( $order_month === $month_key ) {
                $custom_total += floatval( $order->nikola_commission_amount );
            }
        }
    }
    
    $total = $product_total + $custom_total;
    
    // Get status from commission_reports table
    global $wpdb;
    $reports_table = $wpdb->prefix . 'pls_commission_reports';
    $report = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$reports_table} WHERE month_year = %s", $month_key ),
        OBJECT
    );
    
    $status = 'pending';
    if ( $report ) {
        if ( $report->marked_paid_at ) {
            $status = 'paid';
        } elseif ( $report->sent_at ) {
            $status = 'invoiced';
        }
    }
    
    if ( $total > 0 || $report ) {
        $monthly_summary[ $month_key ] = array(
            'month' => date( 'F Y', strtotime( $month_start ) ),
            'product_commission' => $product_total,
            'custom_commission' => $custom_total,
            'total' => $total,
            'status' => $status,
            'report_id' => $report ? $report->id : null,
        );
    }
}

// Calculate totals for current period
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

// Pending (not invoiced)
$pending_product = PLS_Repo_Commission::get_total(
    array(
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'invoiced'  => false,
    )
);

$pending_custom = 0;
foreach ( $custom_commissions as $order ) {
    if ( ! $order->invoiced_at ) {
        $pending_custom += floatval( $order->nikola_commission_amount );
    }
}
$pending_total = $pending_product + $pending_custom;

// Invoiced (not paid)
$invoiced_product = PLS_Repo_Commission::get_total(
    array(
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'invoiced'  => true,
        'paid'      => false,
    )
);

$invoiced_custom = 0;
foreach ( $custom_commissions as $order ) {
    if ( $order->invoiced_at && ! $order->paid_at ) {
        $invoiced_custom += floatval( $order->nikola_commission_amount );
    }
}
$invoiced_total = $invoiced_product + $invoiced_custom;

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
?>
<div class="wrap pls-wrap pls-page-commission">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Commission', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Commission Tracking', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Track and manage commissions from PLS product orders and custom orders.', 'pls-private-label-store' ); ?></p>
        </div>
        <div>
            <button type="button" class="button" id="pls-send-monthly-report">
                <?php esc_html_e( 'Send Monthly Report', 'pls-private-label-store' ); ?>
            </button>
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
            <h3><?php esc_html_e( 'Pending', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount"><?php echo wc_price( $pending_total ); ?></div>
            <p class="description"><?php esc_html_e( 'Not yet invoiced', 'pls-private-label-store' ); ?></p>
        </div>
        <div class="pls-revenue-card">
            <h3><?php esc_html_e( 'Invoiced', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount"><?php echo wc_price( $invoiced_total ); ?></div>
            <p class="description"><?php esc_html_e( 'Awaiting payment', 'pls-private-label-store' ); ?></p>
        </div>
        <div class="pls-revenue-card">
            <h3><?php esc_html_e( 'Paid', 'pls-private-label-store' ); ?></h3>
            <div class="pls-revenue-amount"><?php echo wc_price( $paid_total ); ?></div>
        </div>
    </div>

    <!-- View Toggle -->
    <div class="pls-commission-view-toggle">
        <a href="<?php echo esc_url( add_query_arg( 'view', 'monthly', admin_url( 'admin.php?page=pls-commission' ) ) ); ?>" 
           class="button <?php echo 'monthly' === $view_mode ? 'button-primary' : ''; ?>">
            <?php esc_html_e( 'Monthly Summary', 'pls-private-label-store' ); ?>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'view', 'detailed', admin_url( 'admin.php?page=pls-commission' ) ) ); ?>" 
           class="button <?php echo 'detailed' === $view_mode ? 'button-primary' : ''; ?>">
            <?php esc_html_e( 'Detailed List', 'pls-private-label-store' ); ?>
        </a>
    </div>

    <!-- Monthly Summary View -->
    <?php if ( 'monthly' === $view_mode ) : ?>
        <div class="pls-commission-monthly">
            <div class="pls-revenue-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="pls-commission" />
                    <input type="hidden" name="view" value="monthly" />
                    <label>
                        <?php esc_html_e( 'Select Month:', 'pls-private-label-store' ); ?>
                        <input type="month" name="month" value="<?php echo esc_attr( $selected_month ); ?>" />
                    </label>
                    <button type="submit" class="button"><?php esc_html_e( 'View', 'pls-private-label-store' ); ?></button>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Month', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Product Orders', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Custom Orders', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Total', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $monthly_summary ) ) : ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e( 'No commission data available.', 'pls-private-label-store' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $monthly_summary as $month_key => $summary ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $summary['month'] ); ?></strong></td>
                                <td><?php echo wc_price( $summary['product_commission'] ); ?></td>
                                <td><?php echo wc_price( $summary['custom_commission'] ); ?></td>
                                <td><strong><?php echo wc_price( $summary['total'] ); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = 'pls-status-badge';
                                    if ( 'paid' === $summary['status'] ) {
                                        $status_class .= ' pls-status-success';
                                    } elseif ( 'invoiced' === $summary['status'] ) {
                                        $status_class .= ' pls-status-processing';
                                    }
                                    ?>
                                    <span class="<?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( ucfirst( $summary['status'] ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ( 'pending' === $summary['status'] ) : ?>
                                        <button type="button" class="button button-small pls-mark-invoiced-monthly" data-month="<?php echo esc_attr( $month_key ); ?>">
                                            <?php esc_html_e( 'Mark Invoiced', 'pls-private-label-store' ); ?>
                                        </button>
                                    <?php elseif ( 'invoiced' === $summary['status'] ) : ?>
                                        <button type="button" class="button button-small pls-mark-paid-monthly" data-month="<?php echo esc_attr( $month_key ); ?>">
                                            <?php esc_html_e( 'Mark Paid', 'pls-private-label-store' ); ?>
                                        </button>
                                    <?php else : ?>
                                        <span class="description"><?php esc_html_e( 'Paid', 'pls-private-label-store' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Detailed List View -->
    <?php if ( 'detailed' === $view_mode ) : ?>
        <div class="pls-commission-detailed">
            <div class="pls-revenue-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="pls-commission" />
                    <input type="hidden" name="view" value="detailed" />
                    <label>
                        <?php esc_html_e( 'From:', 'pls-private-label-store' ); ?>
                        <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
                    </label>
                    <label>
                        <?php esc_html_e( 'To:', 'pls-private-label-store' ); ?></label>
                        <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
                    </label>
                    <button type="submit" class="button"><?php esc_html_e( 'Filter', 'pls-private-label-store' ); ?></button>
                </form>
            </div>

            <div class="pls-bulk-actions" style="margin-bottom: 16px;">
                <select id="pls-commission-bulk-action">
                    <option value=""><?php esc_html_e( 'Bulk Actions', 'pls-private-label-store' ); ?></option>
                    <option value="mark_invoiced"><?php esc_html_e( 'Mark as Invoiced', 'pls-private-label-store' ); ?></option>
                    <option value="mark_paid"><?php esc_html_e( 'Mark as Paid', 'pls-private-label-store' ); ?></option>
                </select>
                <button type="button" class="button" id="pls-apply-bulk-action"><?php esc_html_e( 'Apply', 'pls-private-label-store' ); ?></button>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="check-column"><input type="checkbox" id="pls-select-all-commissions" /></th>
                        <th><?php esc_html_e( 'Source', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $all_commissions = array();
                    
                    // Add product commissions
                    foreach ( $product_commissions as $comm ) {
                        $order = wc_get_order( $comm->wc_order_id );
                        $all_commissions[] = array(
                            'id' => $comm->id,
                            'type' => 'product',
                            'source' => 'Order #' . $comm->wc_order_id,
                            'date' => $order ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
                            'amount' => $comm->commission_amount,
                            'status' => $comm->status ?: 'pending',
                            'invoiced_at' => $comm->invoiced_at,
                            'paid_at' => $comm->paid_at,
                            'order_id' => $comm->wc_order_id,
                        );
                    }
                    
                    // Add custom order commissions
                    foreach ( $custom_commissions as $order ) {
                        $all_commissions[] = array(
                            'id' => 'custom_' . $order->id,
                            'type' => 'custom',
                            'source' => 'Custom Order #' . $order->id,
                            'date' => date_i18n( get_option( 'date_format' ), strtotime( $order->created_at ) ),
                            'amount' => $order->nikola_commission_amount,
                            'status' => $order->paid_at ? 'paid' : ( $order->invoiced_at ? 'invoiced' : 'pending' ),
                            'invoiced_at' => $order->invoiced_at,
                            'paid_at' => $order->paid_at,
                            'order_id' => $order->id,
                        );
                    }
                    
                    // Sort by date descending
                    usort( $all_commissions, function( $a, $b ) {
                        return strtotime( $b['date'] ) - strtotime( $a['date'] );
                    } );
                    ?>
                    <?php if ( empty( $all_commissions ) ) : ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e( 'No commissions found for this period.', 'pls-private-label-store' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $all_commissions as $comm ) : ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" class="pls-commission-checkbox" value="<?php echo esc_attr( $comm['id'] ); ?>" data-type="<?php echo esc_attr( $comm['type'] ); ?>" />
                                </th>
                                <td>
                                    <?php if ( 'product' === $comm['type'] ) : ?>
                                        <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $comm['order_id'] . '&action=edit' ) ); ?>">
                                            <?php echo esc_html( $comm['source'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-custom-orders' ) ); ?>">
                                            <?php echo esc_html( $comm['source'] ); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $comm['date'] ); ?></td>
                                <td><?php echo esc_html( ucfirst( $comm['type'] ) ); ?></td>
                                <td><strong><?php echo wc_price( $comm['amount'] ); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = 'pls-status-badge';
                                    if ( 'paid' === $comm['status'] ) {
                                        $status_class .= ' pls-status-success';
                                    } elseif ( 'invoiced' === $comm['status'] ) {
                                        $status_class .= ' pls-status-processing';
                                    }
                                    ?>
                                    <span class="<?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( ucfirst( $comm['status'] ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ( 'pending' === $comm['status'] ) : ?>
                                        <button type="button" class="button button-small pls-mark-invoiced-item" 
                                                data-id="<?php echo esc_attr( $comm['id'] ); ?>" 
                                                data-type="<?php echo esc_attr( $comm['type'] ); ?>">
                                            <?php esc_html_e( 'Mark Invoiced', 'pls-private-label-store' ); ?>
                                        </button>
                                    <?php elseif ( 'invoiced' === $comm['status'] ) : ?>
                                        <button type="button" class="button button-small pls-mark-paid-item" 
                                                data-id="<?php echo esc_attr( $comm['id'] ); ?>" 
                                                data-type="<?php echo esc_attr( $comm['type'] ); ?>">
                                            <?php esc_html_e( 'Mark Paid', 'pls-private-label-store' ); ?>
                                        </button>
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

<!-- Send Monthly Report Modal -->
<div class="pls-modal" id="pls-send-report-modal">
    <div class="pls-modal__dialog">
        <div class="pls-modal__head">
            <div>
                <h2><?php esc_html_e( 'Send Monthly Commission Report', 'pls-private-label-store' ); ?></h2>
            </div>
            <button type="button" class="pls-modal__close" aria-label="<?php esc_attr_e( 'Close', 'pls-private-label-store' ); ?>">×</button>
        </div>
        <div class="pls-modal__body">
            <form id="pls-send-report-form">
                <label>
                    <?php esc_html_e( 'Select Month:', 'pls-private-label-store' ); ?>
                    <input type="month" id="pls-report-month" value="<?php echo esc_attr( date( 'Y-m' ) ); ?>" required />
                </label>
                <p class="description"><?php esc_html_e( 'This will send an email with the total commission amount for the selected month.', 'pls-private-label-store' ); ?></p>
                <div class="pls-modal__footer">
                    <button type="button" class="button" id="pls-cancel-report"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Send Report', 'pls-private-label-store' ); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var PLS_Commission = {
    nonce: '<?php echo wp_create_nonce( 'pls_commission_nonce' ); ?>',
    ajax_url: '<?php echo admin_url( 'admin-ajax.php' ); ?>'
};
</script>
