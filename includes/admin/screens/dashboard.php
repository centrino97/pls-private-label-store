<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get statistics
$total_products = count( PLS_Repo_Base_Product::all() );

// Get active orders (last 30 days)
$orders_query = new WC_Order_Query(
    array(
        'limit'    => -1,
        'date_created' => '>' . ( time() - 30 * DAY_IN_SECONDS ),
        'status'   => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
    )
);
$active_orders = count( $orders_query->get_orders() );

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
    <?php
    $current_user_id = get_current_user_id();
    $onboarding_progress = PLS_Onboarding::get_progress( $current_user_id );
    $has_completed_onboarding = $onboarding_progress && $onboarding_progress->completed_at;
    ?>
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Dashboard', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'PLS Overview', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Quick overview of your PLS operations.', 'pls-private-label-store' ); ?></p>
        </div>
        <?php if ( ! $has_completed_onboarding ) : ?>
            <div>
                <button type="button" class="button button-primary" id="pls-start-tutorial">
                    <?php esc_html_e( 'Start Tutorial', 'pls-private-label-store' ); ?>
                </button>
            </div>
        <?php endif; ?>
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
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-settings' ) ); ?>" class="pls-dashboard-link">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e( 'Settings', 'pls-private-label-store' ); ?>
            </a>
        </div>
    </div>
</div>
