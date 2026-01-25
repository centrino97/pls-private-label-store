<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$notice = '';
$error = '';

// Handle Create Custom Order
if ( isset( $_POST['pls_create_custom_order'] ) && check_admin_referer( 'pls_create_custom_order' ) ) {
    $data = array(
        'status'          => 'new_lead',
        'contact_name'    => isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '',
        'contact_email'   => isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '',
        'contact_phone'   => isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '',
        'company_name'    => isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '',
        'category_id'     => isset( $_POST['category_id'] ) && ! empty( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : null,
        'message'         => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
        'quantity_needed' => isset( $_POST['quantity_needed'] ) && ! empty( $_POST['quantity_needed'] ) ? absint( $_POST['quantity_needed'] ) : null,
        'budget'          => isset( $_POST['budget'] ) && ! empty( $_POST['budget'] ) ? floatval( $_POST['budget'] ) : null,
        'timeline'        => isset( $_POST['timeline'] ) ? sanitize_text_field( wp_unslash( $_POST['timeline'] ) ) : '',
    );

    if ( ! empty( $data['contact_name'] ) && ! empty( $data['contact_email'] ) ) {
        $order_id = PLS_Repo_Custom_Order::create( $data );
        if ( $order_id ) {
            $notice = __( 'Custom order created successfully.', 'pls-private-label-store' );
        } else {
            $error = __( 'Failed to create custom order.', 'pls-private-label-store' );
        }
    } else {
        $error = __( 'Contact name and email are required.', 'pls-private-label-store' );
    }
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
$custom_order_config = isset( $commission_rate['custom_order'] ) ? $commission_rate['custom_order'] : array();
if ( empty( $custom_order_config ) && isset( $commission_rate['custom_order_percent'] ) ) {
    $old_rate = floatval( $commission_rate['custom_order_percent'] );
    $custom_order_config = array(
        'threshold' => 100000.00,
        'rate_below' => $old_rate,
        'rate_above' => $old_rate,
    );
}
$custom_order_threshold = isset( $custom_order_config['threshold'] ) ? floatval( $custom_order_config['threshold'] ) : 100000.00;
$custom_order_rate_below = isset( $custom_order_config['rate_below'] ) ? floatval( $custom_order_config['rate_below'] ) : 3.00;
$custom_order_rate_above = isset( $custom_order_config['rate_above'] ) ? floatval( $custom_order_config['rate_above'] ) : 5.00;

// Get categories for dropdown
$categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
if ( is_wp_error( $categories ) ) {
    $categories = array();
}
?>
<div class="wrap pls-wrap pls-page-custom-orders">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Custom Orders', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Custom Order Management', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Manage custom order leads through their lifecycle stages.', 'pls-private-label-store' ); ?></p>
        </div>
        <div>
            <button type="button" class="button button-primary pls-create-custom-order"><?php esc_html_e( 'Add Custom Order', 'pls-private-label-store' ); ?></button>
        </div>
    </div>

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
    <?php endif; ?>
    <?php if ( $error ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

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
                                <?php if ( ! isset( $order->wc_order_id ) || ! $order->wc_order_id ) : ?>
                                    <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                        <button type="button" class="button button-small button-primary pls-create-wc-order-card" data-order-id="<?php echo esc_attr( $order->id ); ?>" style="margin-left: 8px;">
                                            <?php esc_html_e( 'Create WC Order', 'pls-private-label-store' ); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <?php
                                    $wc_order = wc_get_order( $order->wc_order_id );
                                    if ( $wc_order ) {
                                        ?>
                                        <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->wc_order_id . '&action=edit' ) ); ?>" class="button button-small" style="margin-left: 8px;" target="_blank">
                                            <?php esc_html_e( 'View WC Order', 'pls-private-label-store' ); ?>
                                        </a>
                                        <?php
                                    }
                                    ?>
                                <?php endif; ?>
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

<!-- Create Custom Order Modal -->
<div class="pls-modal" id="pls-create-order-modal">
    <div class="pls-modal__dialog" style="max-width: 600px;">
        <div class="pls-modal__head">
            <h2 style="margin: 0;"><?php esc_html_e( 'Create Custom Order', 'pls-private-label-store' ); ?></h2>
            <button type="button" class="pls-modal__close pls-close-create-modal">&times;</button>
        </div>
        <div class="pls-modal__body" style="padding: 20px;">
            <form method="post" id="pls-create-order-form">
                <?php wp_nonce_field( 'pls_create_custom_order' ); ?>
                <input type="hidden" name="pls_create_custom_order" value="1" />
                
                <div class="pls-field-grid" style="gap: 16px;">
                    <div class="pls-input-group">
                        <label for="contact_name"><?php esc_html_e( 'Contact Name', 'pls-private-label-store' ); ?> <span style="color: #b32d2e;">*</span></label>
                        <input type="text" id="contact_name" name="contact_name" class="pls-input" required style="width: 100%;" />
                    </div>
                    <div class="pls-input-group">
                        <label for="contact_email"><?php esc_html_e( 'Contact Email', 'pls-private-label-store' ); ?> <span style="color: #b32d2e;">*</span></label>
                        <input type="email" id="contact_email" name="contact_email" class="pls-input" required style="width: 100%;" />
                    </div>
                </div>
                
                <div class="pls-field-grid" style="gap: 16px; margin-top: 16px;">
                    <div class="pls-input-group">
                        <label for="contact_phone"><?php esc_html_e( 'Phone', 'pls-private-label-store' ); ?></label>
                        <input type="text" id="contact_phone" name="contact_phone" class="pls-input" style="width: 100%;" />
                    </div>
                    <div class="pls-input-group">
                        <label for="company_name"><?php esc_html_e( 'Company Name', 'pls-private-label-store' ); ?></label>
                        <input type="text" id="company_name" name="company_name" class="pls-input" style="width: 100%;" />
                    </div>
                </div>
                
                <div class="pls-input-group" style="margin-top: 16px;">
                    <label for="category_id"><?php esc_html_e( 'Product Category', 'pls-private-label-store' ); ?></label>
                    <select id="category_id" name="category_id" class="pls-input" style="width: 100%;">
                        <option value=""><?php esc_html_e( 'Select category...', 'pls-private-label-store' ); ?></option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="pls-field-grid" style="gap: 16px; margin-top: 16px;">
                    <div class="pls-input-group">
                        <label for="quantity_needed"><?php esc_html_e( 'Quantity Needed', 'pls-private-label-store' ); ?></label>
                        <input type="number" id="quantity_needed" name="quantity_needed" class="pls-input" min="1" style="width: 100%;" />
                    </div>
                    <div class="pls-input-group">
                        <label for="budget"><?php esc_html_e( 'Budget ($)', 'pls-private-label-store' ); ?></label>
                        <input type="number" id="budget" name="budget" class="pls-input" min="0" step="0.01" style="width: 100%;" />
                    </div>
                </div>
                
                <div class="pls-input-group" style="margin-top: 16px;">
                    <label for="timeline"><?php esc_html_e( 'Timeline', 'pls-private-label-store' ); ?></label>
                    <input type="text" id="timeline" name="timeline" class="pls-input" placeholder="e.g., 4-6 weeks" style="width: 100%;" />
                </div>
                
                <div class="pls-input-group" style="margin-top: 16px;">
                    <label for="message"><?php esc_html_e( 'Message / Notes', 'pls-private-label-store' ); ?></label>
                    <textarea id="message" name="message" rows="4" class="pls-input" style="width: 100%;"></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--pls-gray-200);">
                    <button type="button" class="button pls-close-create-modal" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Create Order', 'pls-private-label-store' ); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create WooCommerce Order Modal -->
<div class="pls-modal" id="pls-create-wc-order-modal" style="display: none;">
    <div class="pls-modal__dialog" style="max-width: 700px;">
        <div class="pls-modal__head">
            <h2 style="margin: 0;"><?php esc_html_e( 'Create WooCommerce Order', 'pls-private-label-store' ); ?></h2>
            <button type="button" class="pls-modal__close pls-close-wc-order-modal">&times;</button>
        </div>
        <div class="pls-modal__body" style="padding: 20px;">
            <input type="hidden" id="pls-create-wc-order-order-id" />
            
            <div class="pls-input-group" style="margin-bottom: 20px;">
                <label for="pls-wc-order-status"><?php esc_html_e( 'Order Status', 'pls-private-label-store' ); ?></label>
                <select id="pls-wc-order-status" class="pls-input" style="width: 100%;">
                    <option value="pending"><?php esc_html_e( 'Pending Payment', 'pls-private-label-store' ); ?></option>
                    <option value="on-hold"><?php esc_html_e( 'On Hold', 'pls-private-label-store' ); ?></option>
                    <option value="draft"><?php esc_html_e( 'Draft', 'pls-private-label-store' ); ?></option>
                </select>
                <p class="description"><?php esc_html_e( 'Choose the initial status for the WooCommerce order.', 'pls-private-label-store' ); ?></p>
            </div>

            <div class="pls-input-group" style="margin-bottom: 20px;">
                <label><?php esc_html_e( 'Products', 'pls-private-label-store' ); ?></label>
                <div id="pls-wc-order-products">
                    <div class="pls-product-row" style="display: flex; gap: 8px; margin-bottom: 8px; align-items: center;">
                        <input type="number" class="pls-product-id" placeholder="Product ID" style="width: 120px;" />
                        <input type="number" class="pls-product-qty" placeholder="Qty" min="1" value="1" style="width: 80px;" />
                        <button type="button" class="button pls-remove-product-row"><?php esc_html_e( 'Remove', 'pls-private-label-store' ); ?></button>
                    </div>
                </div>
                <button type="button" class="button button-small" id="pls-add-product-row" style="margin-top: 8px;">
                    <?php esc_html_e( '+ Add Product', 'pls-private-label-store' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'Add products by entering WooCommerce product IDs and quantities.', 'pls-private-label-store' ); ?></p>
            </div>

            <div class="pls-input-group" style="margin-bottom: 20px;">
                <label><?php esc_html_e( 'Custom Line Items', 'pls-private-label-store' ); ?></label>
                <div id="pls-wc-order-custom-lines">
                    <!-- Custom lines will be added here -->
                </div>
                <button type="button" class="button button-small" id="pls-add-custom-line" style="margin-top: 8px;">
                    <?php esc_html_e( '+ Add Custom Line Item', 'pls-private-label-store' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'Add custom line items (e.g., setup fees, custom charges).', 'pls-private-label-store' ); ?></p>
            </div>

            <div class="pls-input-group" style="margin-bottom: 20px;">
                <label>
                    <input type="checkbox" id="pls-include-sampling" checked />
                    <?php esc_html_e( 'Include Sample Cost', 'pls-private-label-store' ); ?>
                </label>
                <p class="description" id="pls-sample-cost-display" style="margin-top: 4px; display: none;">
                    <?php esc_html_e( 'Sample cost will be added as a line item.', 'pls-private-label-store' ); ?>
                </p>
            </div>

            <div class="pls-input-group" style="margin-bottom: 20px;">
                <label for="pls-wc-order-notes"><?php esc_html_e( 'Order Notes', 'pls-private-label-store' ); ?></label>
                <textarea id="pls-wc-order-notes" rows="3" class="pls-input" style="width: 100%;" placeholder="<?php esc_attr_e( 'Optional notes for the order...', 'pls-private-label-store' ); ?>"></textarea>
            </div>

            <div style="text-align: right; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--pls-gray-200);">
                <button type="button" class="button pls-close-wc-order-modal" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                <button type="button" class="button button-primary" id="pls-submit-create-wc-order">
                    <?php esc_html_e( 'Create Order', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var PLS_CustomOrders = {
    nonce: '<?php echo wp_create_nonce( 'pls_custom_orders_nonce' ); ?>',
    ajax_url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
    commission_threshold: <?php echo floatval( $custom_order_threshold ); ?>,
    commission_rate_below: <?php echo floatval( $custom_order_rate_below ); ?>,
    commission_rate_above: <?php echo floatval( $custom_order_rate_above ); ?>
};

jQuery(document).ready(function($) {
    // Open create modal
    $('.pls-create-custom-order').on('click', function() {
        $('#pls-create-order-modal').show();
    });
    
    // Close modal
    $('.pls-close-create-modal, #pls-create-order-modal').on('click', function(e) {
        if (e.target === this) {
            $('#pls-create-order-modal').hide();
        }
    });
    
    // Prevent modal content click from closing
    $('#pls-create-order-modal .pls-modal__dialog').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>
