<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get statistics
$total_products = count( PLS_Repo_Base_Product::all() );

// Get active orders (last 30 days) - only orders with PLS products
$orders_query = new WC_Order_Query(
    array(
        'limit'    => -1,
        'date_created' => '>' . ( time() - 30 * DAY_IN_SECONDS ),
        'status'   => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
    )
);
$all_orders = $orders_query->get_orders();

// Get all PLS product WooCommerce IDs
$pls_products = PLS_Repo_Base_Product::all();
$pls_wc_product_ids = array();
foreach ( $pls_products as $pls_product ) {
    if ( $pls_product->wc_product_id ) {
        $pls_wc_product_ids[] = $pls_product->wc_product_id;
        // Also include variation IDs if product is variable
        $wc_product = wc_get_product( $pls_product->wc_product_id );
        if ( $wc_product && $wc_product->is_type( 'variable' ) ) {
            $variations = $wc_product->get_children();
            $pls_wc_product_ids = array_merge( $pls_wc_product_ids, $variations );
        }
    }
}

// Also include bundle WooCommerce product IDs
require_once PLS_PLS_DIR . 'includes/data/repo-bundle.php';
$bundles = PLS_Repo_Bundle::all();
foreach ( $bundles as $bundle ) {
    if ( $bundle->wc_product_id ) {
        $pls_wc_product_ids[] = $bundle->wc_product_id;
    }
}

// Count only orders containing PLS products
$active_orders = 0;
foreach ( $all_orders as $order ) {
    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        
        // Check if order contains PLS product
        if ( in_array( $product_id, $pls_wc_product_ids, true ) || 
             ( $variation_id && in_array( $variation_id, $pls_wc_product_ids, true ) ) ) {
            $active_orders++;
            break; // Count order only once
        }
    }
}

// Get pending custom orders
$pending_custom_orders = PLS_Repo_Custom_Order::count_by_status( 'new_lead' ) + 
                         PLS_Repo_Custom_Order::count_by_status( 'sampling' );

// Get monthly revenue (current month)
$date_from = date( 'Y-m-01' );
$date_to   = date( 'Y-m-d' );
$monthly_revenue = PLS_Repo_Commission::get_total(
    array(
        'date_from' => $date_from,
        'date_to'   => $date_to,
    )
);

// Get pending commission
$pending_commission = PLS_Repo_Commission::get_total(
    array(
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'invoiced'  => false,
    )
);
?>
<div class="wrap pls-wrap pls-page-dashboard">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Dashboard', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'PLS Overview', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Quick overview of your PLS operations. Click the help button (?) for detailed guides on any page.', 'pls-private-label-store' ); ?></p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="pls-dashboard-summary">
        <div class="pls-summary-card">
            <div class="pls-summary-card__icon">
                <span class="dashicons dashicons-products"></span>
            </div>
            <div class="pls-summary-card__content">
                <h3><?php esc_html_e( 'Total Products', 'pls-private-label-store' ); ?></h3>
                <div class="pls-summary-card__value"><?php echo esc_html( $total_products ); ?></div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-products' ) ); ?>" class="pls-summary-card__link">
                    <?php esc_html_e( 'View Products', 'pls-private-label-store' ); ?>
                </a>
            </div>
        </div>

        <div class="pls-summary-card">
            <div class="pls-summary-card__icon">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="pls-summary-card__content">
                <h3><?php esc_html_e( 'Active Orders', 'pls-private-label-store' ); ?></h3>
                <div class="pls-summary-card__value"><?php echo esc_html( $active_orders ); ?></div>
                <p class="pls-summary-card__description"><?php esc_html_e( 'Last 30 days', 'pls-private-label-store' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-orders' ) ); ?>" class="pls-summary-card__link">
                    <?php esc_html_e( 'View Orders', 'pls-private-label-store' ); ?>
                </a>
            </div>
        </div>

        <div class="pls-summary-card">
            <div class="pls-summary-card__icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="pls-summary-card__content">
                <h3><?php esc_html_e( 'Pending Custom Orders', 'pls-private-label-store' ); ?></h3>
                <div class="pls-summary-card__value"><?php echo esc_html( $pending_custom_orders ); ?></div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-custom-orders' ) ); ?>" class="pls-summary-card__link">
                    <?php esc_html_e( 'Manage Custom Orders', 'pls-private-label-store' ); ?>
                </a>
            </div>
        </div>

        <div class="pls-summary-card">
            <div class="pls-summary-card__icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="pls-summary-card__content">
                <h3><?php esc_html_e( 'Monthly Revenue', 'pls-private-label-store' ); ?></h3>
                <div class="pls-summary-card__value"><?php echo wc_price( $monthly_revenue ); ?></div>
                <p class="pls-summary-card__description"><?php esc_html_e( 'Current month', 'pls-private-label-store' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-revenue' ) ); ?>" class="pls-summary-card__link">
                    <?php esc_html_e( 'View Revenue', 'pls-private-label-store' ); ?>
                </a>
            </div>
        </div>

        <div class="pls-summary-card">
            <div class="pls-summary-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="pls-summary-card__content">
                <h3><?php esc_html_e( 'Pending Commission', 'pls-private-label-store' ); ?></h3>
                <div class="pls-summary-card__value"><?php echo wc_price( $pending_commission ); ?></div>
                <p class="pls-summary-card__description"><?php esc_html_e( 'Not yet invoiced', 'pls-private-label-store' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-revenue' ) ); ?>" class="pls-summary-card__link">
                    <?php esc_html_e( 'View Details', 'pls-private-label-store' ); ?>
                </a>
            </div>
        </div>
    </div>


    <!-- Quick Links -->
    <div class="pls-dashboard-links">
        <h2><?php esc_html_e( 'Quick Links', 'pls-private-label-store' ); ?></h2>
        <div class="pls-dashboard-links__grid">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-products' ) ); ?>" class="pls-dashboard-link">
                <span class="dashicons dashicons-products"></span>
                <?php esc_html_e( 'Products', 'pls-private-label-store' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-orders' ) ); ?>" class="pls-dashboard-link">
                <span class="dashicons dashicons-cart"></span>
                <?php esc_html_e( 'Orders', 'pls-private-label-store' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-custom-orders' ) ); ?>" class="pls-dashboard-link">
                <span class="dashicons dashicons-email-alt"></span>
                <?php esc_html_e( 'Custom Orders', 'pls-private-label-store' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-revenue' ) ); ?>" class="pls-dashboard-link">
                <span class="dashicons dashicons-money-alt"></span>
                <?php esc_html_e( 'Revenue', 'pls-private-label-store' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-categories' ) ); ?>" class="pls-dashboard-link">
                <span class="dashicons dashicons-category"></span>
                <?php esc_html_e( 'Categories', 'pls-private-label-store' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-attributes' ) ); ?>" class="pls-dashboard-link">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e( 'Product Options', 'pls-private-label-store' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-bundles' ) ); ?>" class="pls-dashboard-link">
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e( 'Bundles', 'pls-private-label-store' ); ?>
            </a>
        </div>
    </div>
</div>
