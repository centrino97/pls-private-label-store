<?php
/**
 * Frontend AJAX endpoints for offers / upgrades.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Ajax {

    public static function init() {
        add_action( 'wp_ajax_pls_get_offers', array( __CLASS__, 'get_offers' ) );
        add_action( 'wp_ajax_nopriv_pls_get_offers', array( __CLASS__, 'get_offers' ) );

        add_action( 'wp_ajax_pls_apply_offer', array( __CLASS__, 'apply_offer' ) );
        add_action( 'wp_ajax_nopriv_pls_apply_offer', array( __CLASS__, 'apply_offer' ) );

        add_action( 'wp_ajax_pls_add_to_cart', array( __CLASS__, 'add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_pls_add_to_cart', array( __CLASS__, 'add_to_cart' ) );
    }

    public static function get_offers() {
        check_ajax_referer( 'pls_offers', 'nonce' );

        // TODO: Replace with real eligibility logic reading PLS bundle tables.
        wp_send_json_success(
            array(
                'offers' => array(
                    array(
                        'id' => 1,
                        'title' => 'Upgrade offer (stub)',
                        'description' => 'This is a placeholder offer. Implement eligibility + bundle mapping.',
                        'action' => array(
                            'type' => 'apply_offer',
                            'offer_id' => 1,
                        ),
                    ),
                ),
            )
        );
    }

    public static function apply_offer() {
        check_ajax_referer( 'pls_offers', 'nonce' );

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( array( 'message' => 'Cart not available.' ), 400 );
        }

        // TODO: Replace with real "upgrade" behavior (add bundle items, remove originals, etc.)
        wp_send_json_success( array( 'message' => 'Offer applied (stub).' ) );
    }

    /**
     * Handle AJAX add to cart request.
     */
    public static function add_to_cart() {
        // Verify nonce for CSRF protection
        if ( ! check_ajax_referer( 'pls_add_to_cart', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'pls-private-label-store' ) ), 403 );
        }

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce cart is not available.', 'pls-private-label-store' ) ) );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
        $quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

        if ( ! $product_id || ! $variation_id ) {
            wp_send_json_error( array( 'message' => __( 'Product ID and variation ID are required.', 'pls-private-label-store' ) ) );
        }

        $variation = wc_get_product( $variation_id );
        if ( ! $variation || $variation->get_parent_id() !== $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid variation.', 'pls-private-label-store' ) ) );
        }

        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );

        if ( $cart_item_key ) {
            // Return success with cart fragments
            ob_start();
            woocommerce_mini_cart();
            $mini_cart = ob_get_clean();

            wp_send_json_success( array(
                'message' => __( 'Product added to cart successfully.', 'pls-private-label-store' ),
                'cart_hash' => WC()->cart->get_cart_hash(),
                'fragments' => apply_filters(
                    'woocommerce_add_to_cart_fragments',
                    array(
                        'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
                    )
                ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to add product to cart.', 'pls-private-label-store' ) ) );
        }
    }
}
