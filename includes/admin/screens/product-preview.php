<?php
/**
 * Product Preview - Shows how Elementor widgets would render on frontend
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get product IDs from query params
$pls_product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
$wc_product_id  = isset( $_GET['wc_id'] ) ? absint( $_GET['wc_id'] ) : 0;

if ( ! $pls_product_id || ! $wc_product_id ) {
    wp_die( esc_html__( 'Invalid product ID.', 'pls-private-label-store' ) );
}

// Get PLS product
$pls_product = PLS_Repo_Base_Product::get( $pls_product_id );
if ( ! $pls_product ) {
    wp_die( esc_html__( 'Product not found.', 'pls-private-label-store' ) );
}

// Get WooCommerce product
if ( ! function_exists( 'wc_get_product' ) ) {
    wp_die( esc_html__( 'WooCommerce is not active.', 'pls-private-label-store' ) );
}

$wc_product = wc_get_product( $wc_product_id );
if ( ! $wc_product ) {
    wp_die( esc_html__( 'WooCommerce product not found. Please sync the product first.', 'pls-private-label-store' ) );
}

// Enqueue frontend assets to simulate frontend environment
wp_enqueue_style( 'pls-offers', PLS_PLS_URL . 'assets/css/offers.css', array(), PLS_PLS_VERSION );
wp_enqueue_script( 'pls-offers', PLS_PLS_URL . 'assets/js/offers.js', array( 'jquery' ), PLS_PLS_VERSION, true );
wp_localize_script(
    'pls-offers',
    'PLS_Offers',
    array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'pls_offers' ),
    )
);

// Enqueue WooCommerce assets
if ( function_exists( 'WC' ) ) {
    wp_enqueue_script( 'wc-add-to-cart-variation' );
}

// Set up global $product for widgets
global $product;
$product = $wc_product;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( sprintf( __( 'Preview: %s', 'pls-private-label-store' ), $pls_product->name ) ); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f0f0f1;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .pls-preview-header {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pls-preview-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .pls-preview-content {
            background: #fff;
            padding: 40px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
        }
        .pls-preview-note {
            background: #fff3cd;
            border-left: 4px solid #ffb900;
            padding: 12px 16px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .pls-preview-note strong {
            display: block;
            margin-bottom: 8px;
        }
        .pls-widget-section {
            margin-bottom: 40px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .pls-widget-section h2 {
            margin-top: 0;
            font-size: 20px;
            color: #2271b1;
        }
        .pls-note {
            padding: 12px;
            background: #f0f0f1;
            border-left: 4px solid #dba617;
            margin: 10px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="pls-preview-header">
        <div>
            <h1><?php echo esc_html( sprintf( __( 'Frontend Preview: %s', 'pls-private-label-store' ), $pls_product->name ) ); ?></h1>
            <p style="margin: 8px 0 0; color: #646970;">
                <?php esc_html_e( 'This preview shows how Elementor widgets will render on the frontend.', 'pls-private-label-store' ); ?>
            </p>
        </div>
        <div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-products' ) ); ?>" class="button"><?php esc_html_e( 'Back to Products', 'pls-private-label-store' ); ?></a>
            <?php if ( $wc_product->get_permalink() ) : ?>
                <a href="<?php echo esc_url( $wc_product->get_permalink() ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'View Live Product', 'pls-private-label-store' ); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="pls-preview-content">
        <div class="pls-preview-note">
            <strong><?php esc_html_e( '📋 Preview Mode', 'pls-private-label-store' ); ?></strong>
            <p style="margin: 0;">
                <?php esc_html_e( 'This preview shows how the product will render using the [pls_single_product] shortcode in your Elementor template.', 'pls-private-label-store' ); ?>
            </p>
        </div>

        <?php
        // Simulate is_product() for shortcode
        global $wp_query;
        $original_query = $wp_query;
        $wp_query->is_product = true;
        $wp_query->is_singular = true;
        ?>

        <!-- Render using PLS Single Product Shortcode -->
        <?php
        // Render using the shortcode - this is what users will use in Elementor templates
        echo do_shortcode( '[pls_single_product product_id="' . esc_attr( $wc_product_id ) . '"]' );
        ?>

        <!-- Shortcode Usage Info -->
        <div class="pls-widget-section" style="background: #e7f5e7; border-left: 4px solid #00a32a; margin-top: 40px;">
            <h2><?php esc_html_e( '📝 Shortcode Usage', 'pls-private-label-store' ); ?></h2>
            <p style="margin: 0 0 12px 0;">
                <strong><?php esc_html_e( 'In your Elementor template, use:', 'pls-private-label-store' ); ?></strong>
            </p>
            <code style="display: block; padding: 12px; background: #fff; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 12px;">
                [pls_single_product]
            </code>
            <p style="margin: 0 0 12px 0;">
                <strong><?php esc_html_e( 'Or with options:', 'pls-private-label-store' ); ?></strong>
            </p>
            <code style="display: block; padding: 12px; background: #fff; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 12px;">
                [pls_single_product show_configurator="yes" show_description="yes" show_ingredients="yes" show_bundles="yes"]
            </code>
            <p style="margin: 0; font-size: 13px; color: #666;">
                <?php esc_html_e( 'The shortcode automatically detects the current product when used in Elementor Theme Builder Single Product template.', 'pls-private-label-store' ); ?>
            </p>
        </div>

        <?php
        // Restore original query
        $wp_query = $original_query;
        ?>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
