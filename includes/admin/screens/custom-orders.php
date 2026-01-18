<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$stages = array(
    'new_lead'   => __( 'New Leads', 'pls-private-label-store' ),
    'sampling'   => __( 'Sampling', 'pls-private-label-store' ),
    'production' => __( 'Production', 'pls-private-label-store' ),
    'on_hold'    => __( 'On-hold', 'pls-private-label-store' ),
    'done'       => __( 'Done', 'pls-private-label-store' ),
);

$all_orders = PLS_Repo_Custom_Order::all();
$orders_by_stage = array();

foreach ( $stages as $stage_key => $stage_label ) {
    $orders_by_stage[ $stage_key ] = array();
}

foreach ( $all_orders as $order ) {
    $status = $order->status;
    if ( isset( $orders_by_stage[ $status ] ) ) {
        $orders_by_stage[ $status ][] = $order;
    }
}

$commission_rate = get_option( 'pls_commission_rates', array() );
$custom_order_percent = isset( $commission_rate['custom_order_percent'] ) ? $commission_rate['custom_order_percent'] : 3.00;
?>
<div class="wrap pls-wrap pls-page-custom-orders">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Custom Orders', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Custom Order Management', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Manage custom order leads through their lifecycle stages.', 'pls-private-label-store' ); ?></p>
        </div>
    </div>

    <div class="pls-kanban-board" id="pls-custom-orders-kanban">
        <?php foreach ( $stages as $stage_key => $stage_label ) : ?>
            <div class="pls-kanban-column" data-stage="<?php echo esc_attr( $stage_key ); ?>">
                <div class="pls-kanban-column__header">
                    <h3><?php echo esc_html( $stage_label ); ?></h3>
                    <span class="pls-kanban-count"><?php echo count( $orders_by_stage[ $stage_key ] ); ?></span>
                </div>
                <div class="pls-kanban-column__cards" data-stage="<?php echo esc_attr( $stage_key ); ?>">
                    <?php foreach ( $orders_by_stage[ $stage_key ] as $order ) : ?>
                        <?php
                        $category_name = '';
                        if ( $order->category_id ) {
                            $category = get_term( $order->category_id, 'product_cat' );
                            if ( $category && ! is_wp_error( $category ) ) {
                                $category_name = $category->name;
                            }
                        }
                        $date_range = date_i18n( 'M j, Y', strtotime( $order->created_at ) );
                        if ( $order->timeline ) {
                            $date_range .= ' - ' . esc_html( $order->timeline );
                        }
                        ?>
                        <div class="pls-kanban-card" data-order-id="<?php echo esc_attr( $order->id ); ?>">
                            <div class="pls-kanban-card__header">
                                <strong><?php echo esc_html( $order->contact_name ); ?></strong>
                                <?php if ( $order->company_name ) : ?>
                                    <span class="pls-kanban-card__company"><?php echo esc_html( $order->company_name ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="pls-kanban-card__meta">
                                <span class="pls-kanban-card__date"><?php echo esc_html( $date_range ); ?></span>
                                <?php if ( $category_name ) : ?>
                                    <span class="pls-kanban-card__category"><?php echo esc_html( $category_name ); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ( $order->production_cost || $order->total_value ) : ?>
                                <div class="pls-kanban-card__financials">
                                    <?php if ( $order->production_cost ) : ?>
                                        <div class="pls-kanban-card__cost">
                                            <?php esc_html_e( 'Production cost:', 'pls-private-label-store' ); ?>
                                            <strong><?php echo wc_price( $order->production_cost ); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( $order->nikola_commission_amount ) : ?>
                                        <div class="pls-kanban-card__commission">
                                            <?php esc_html_e( 'Nikola %:', 'pls-private-label-store' ); ?>
                                            <strong><?php echo wc_price( $order->nikola_commission_amount ); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="pls-kanban-card__actions">
                                <button type="button" class="button button-small pls-view-order" data-order-id="<?php echo esc_attr( $order->id ); ?>">
                                    <?php esc_html_e( 'View', 'pls-private-label-store' ); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="pls-modal" id="pls-order-detail-modal">
    <div class="pls-modal__dialog">
        <div class="pls-modal__head">
            <div>
                <h2><?php esc_html_e( 'Custom Order Details', 'pls-private-label-store' ); ?></h2>
            </div>
            <button type="button" class="pls-modal__close" aria-label="<?php esc_attr_e( 'Close', 'pls-private-label-store' ); ?>">×</button>
        </div>
        <div class="pls-modal__body" id="pls-order-detail-content">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
var PLS_CustomOrders = {
    nonce: '<?php echo wp_create_nonce( 'pls_custom_orders_nonce' ); ?>',
    ajax_url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
    commission_percent: <?php echo floatval( $custom_order_percent ); ?>
};
</script>
