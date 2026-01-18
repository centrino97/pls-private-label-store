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
                <?php esc_html_e( 'This preview simulates the frontend environment. Use this to see how PLS Elementor widgets will render before building your Elementor Theme Builder template.', 'pls-private-label-store' ); ?>
            </p>
        </div>

        <?php
        // Simulate is_product() for widgets
        global $wp_query;
        $original_query = $wp_query;
        $wp_query->is_product = true;
        $wp_query->is_singular = true;
        ?>

        <!-- Product Title -->
        <div class="pls-widget-section">
            <h2><?php esc_html_e( 'Product Title', 'pls-private-label-store' ); ?></h2>
            <h1><?php echo esc_html( $wc_product->get_name() ); ?></h1>
        </div>

        <!-- Product Description -->
        <?php if ( $wc_product->get_description() ) : ?>
        <div class="pls-widget-section">
            <h2><?php esc_html_e( 'Product Description', 'pls-private-label-store' ); ?></h2>
            <div><?php echo wp_kses_post( $wc_product->get_description() ); ?></div>
        </div>
        <?php endif; ?>

        <!-- PLS Configurator Widget Preview -->
        <div class="pls-widget-section">
            <h2><?php esc_html_e( 'PLS Configurator Widget', 'pls-private-label-store' ); ?></h2>
            <p class="description"><?php esc_html_e( 'This is how the PLS Configurator widget will render:', 'pls-private-label-store' ); ?></p>
            <?php
            // Render the configurator widget
            if ( $wc_product->is_type( 'variable' ) ) {
                require_once PLS_PLS_DIR . 'includes/elementor/widgets/class-pls-widget-configurator.php';
                $widget = new \PLS_Widget_Configurator();
                $widget->render();
            } else {
                echo '<div class="pls-note">' . esc_html__( 'Product must be synced as a variable product to show configurator.', 'pls-private-label-store' ) . '</div>';
            }
            ?>
        </div>

        <!-- Product Attributes Preview -->
        <?php if ( $wc_product->is_type( 'variable' ) ) : ?>
        <div class="pls-widget-section">
            <h2><?php esc_html_e( 'Product Attributes (Variations)', 'pls-private-label-store' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Available attributes for variations:', 'pls-private-label-store' ); ?></p>
            <?php
            $variation_attributes = $wc_product->get_variation_attributes();
            if ( ! empty( $variation_attributes ) ) {
                echo '<ul>';
                foreach ( $variation_attributes as $attribute_name => $options ) {
                    $attribute_label = wc_attribute_label( $attribute_name );
                    echo '<li><strong>' . esc_html( $attribute_label ) . ':</strong> ' . esc_html( implode( ', ', $options ) ) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<div class="pls-note">' . esc_html__( 'No variation attributes found.', 'pls-private-label-store' ) . '</div>';
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- Next Steps -->
        <div class="pls-widget-section" style="background: #e7f5e7; border-left: 4px solid #00a32a;">
            <h2><?php esc_html_e( '🎨 Next Steps: Build Elementor Template', 'pls-private-label-store' ); ?></h2>
            <ol style="margin: 0; padding-left: 20px;">
                <li><?php esc_html_e( 'Go to Elementor → Theme Builder → Single Product', 'pls-private-label-store' ); ?></li>
                <li><?php esc_html_e( 'Add the "PLS Configurator" widget to your template', 'pls-private-label-store' ); ?></li>
                <li><?php esc_html_e( 'Add other WooCommerce widgets (Product Title, Price, Images, etc.)', 'pls-private-label-store' ); ?></li>
                <li><?php esc_html_e( 'Use Elementor Dynamic Tags for PLS-specific data (Pack Units, etc.)', 'pls-private-label-store' ); ?></li>
                <li><?php esc_html_e( 'Publish the template and it will apply to all variable products', 'pls-private-label-store' ); ?></li>
            </ol>
        </div>

        <?php
        // Restore original query
        $wp_query = $original_query;
        ?>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
