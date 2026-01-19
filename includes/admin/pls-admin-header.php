<?php
/**
 * Custom admin header for PLS pages.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'pls-dashboard';
?>
<div class="pls-admin-header">
    <div class="pls-admin-header__brand">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-dashboard' ) ); ?>" class="pls-admin-header__logo">
            <span class="pls-admin-header__logo-text">Bodoci Biophysics</span>
        </a>
    </div>
    <nav class="pls-admin-nav">
        <?php
        $menu_items = array(
            'pls-dashboard'      => __( 'Dashboard', 'pls-private-label-store' ),
            'pls-products'       => __( 'Products', 'pls-private-label-store' ),
            'pls-bundles'        => __( 'Bundles', 'pls-private-label-store' ),
            'pls-custom-orders'  => __( 'Custom Orders', 'pls-private-label-store' ),
            'pls-orders'         => __( 'Orders', 'pls-private-label-store' ),
            'pls-categories'     => __( 'Categories', 'pls-private-label-store' ),
            'pls-attributes'     => __( 'Product Options', 'pls-private-label-store' ),
            'pls-bi'             => __( 'Analytics', 'pls-private-label-store' ),
            'pls-commission'     => __( 'Commission', 'pls-private-label-store' ),
            'pls-revenue'        => __( 'Revenue', 'pls-private-label-store' ),
            // Settings hidden from UI
        );

        // Show all menu items to everyone (WordPress capabilities handle access control)
        foreach ( $menu_items as $page => $label ) {
            $url = admin_url( 'admin.php?page=' . $page );
            $active = ( $current_page === $page ) ? ' is-active' : '';
            echo '<a href="' . esc_url( $url ) . '" class="pls-admin-nav__item' . esc_attr( $active ) . '">' . esc_html( $label ) . '</a>';
        }
        ?>
    </nav>
    <div class="pls-admin-header__user">
        <span class="pls-admin-header__user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
    </div>
</div>
