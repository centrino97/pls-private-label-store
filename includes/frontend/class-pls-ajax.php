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

    /**
     * Get available bundle offers based on current cart contents.
     * Returns bundles that customer qualifies for or is close to qualifying for.
     */
    public static function get_offers() {
        check_ajax_referer( 'pls_offers', 'nonce' );

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_success( array( 'offers' => array() ) );
        }

        require_once PLS_PLS_DIR . 'includes/data/repo-bundle.php';
        require_once PLS_PLS_DIR . 'includes/data/repo-base-product.php';

        // Get all live bundles
        $bundles = PLS_Repo_Bundle::all();
        $available_offers = array();

        if ( empty( $bundles ) ) {
            wp_send_json_success( array( 'offers' => array() ) );
        }

        // Get PLS products mapping
        $pls_products = PLS_Repo_Base_Product::all();
        $pls_wc_ids = array();
        foreach ( $pls_products as $product ) {
            if ( $product->wc_product_id ) {
                $pls_wc_ids[ $product->wc_product_id ] = $product->id;
            }
        }

        // Analyze current cart
        $cart = WC()->cart;
        $pls_items = array();
        
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            $variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;

            // Skip if not a PLS product
            if ( ! isset( $pls_wc_ids[ $product_id ] ) ) {
                continue;
            }

            // Get variation units
            $units_per_item = 0;
            if ( $variation_id ) {
                $variation = wc_get_product( $variation_id );
                if ( $variation ) {
                    $units_per_item = (int) get_post_meta( $variation_id, '_pls_units', true );
                    if ( ! $units_per_item ) {
                        $attributes = $variation->get_attributes();
                        if ( isset( $attributes['pa_pack-tier'] ) ) {
                            $tier_term = get_term_by( 'slug', $attributes['pa_pack-tier'], 'pa_pack-tier' );
                            if ( $tier_term ) {
                                $units_per_item = (int) get_term_meta( $tier_term->term_id, '_pls_default_units', true );
                            }
                        }
                    }
                }
            }

            if ( ! $units_per_item ) {
                $units_per_item = 1;
            }

            $base_product_id = $pls_wc_ids[ $product_id ];
            if ( ! isset( $pls_items[ $base_product_id ] ) ) {
                $pls_items[ $base_product_id ] = array(
                    'product_id' => $product_id,
                    'total_units' => 0,
                );
            }

            $pls_items[ $base_product_id ]['total_units'] += $units_per_item * $cart_item['quantity'];
        }

        // Check each bundle for qualification or near-qualification
        foreach ( $bundles as $bundle ) {
            if ( 'live' !== $bundle->status ) {
                continue;
            }

            $bundle_rules = ! empty( $bundle->offer_rules_json ) ? json_decode( $bundle->offer_rules_json, true ) : array();
            if ( empty( $bundle_rules ) ) {
                continue;
            }

            $required_sku_count = isset( $bundle_rules['sku_count'] ) ? (int) $bundle_rules['sku_count'] : 0;
            $required_units_per_sku = isset( $bundle_rules['units_per_sku'] ) ? (int) $bundle_rules['units_per_sku'] : 0;
            $bundle_price_per_unit = isset( $bundle_rules['price_per_unit'] ) ? floatval( $bundle_rules['price_per_unit'] ) : 0;

            if ( $required_sku_count < 2 || $required_units_per_sku < 1 || $bundle_price_per_unit <= 0 ) {
                continue;
            }

            // Count qualifying products
            $qualified_products = 0;
            foreach ( $pls_items as $item ) {
                if ( $item['total_units'] >= $required_units_per_sku ) {
                    $qualified_products++;
                }
            }

            // Check if already qualified
            $is_qualified = ( $qualified_products >= $required_sku_count );
            
            // Check if close to qualifying (within 1 product)
            $is_close = ( $qualified_products >= ( $required_sku_count - 1 ) && $qualified_products < $required_sku_count );

            if ( $is_qualified || $is_close ) {
                $savings_per_unit = 0;
                // Calculate potential savings (compare bundle price to regular tier prices)
                if ( ! empty( $pls_items ) ) {
                    $avg_regular_price = 0;
                    $price_count = 0;
                    foreach ( $pls_items as $item ) {
                        $product = wc_get_product( $item['product_id'] );
                        if ( $product && $product->is_type( 'variable' ) ) {
                            $variations = $product->get_children();
                            foreach ( $variations as $var_id ) {
                                $var = wc_get_product( $var_id );
                                if ( $var ) {
                                    $var_units = (int) get_post_meta( $var_id, '_pls_units', true );
                                    if ( $var_units === $required_units_per_sku ) {
                                        $var_price = (float) $var->get_price();
                                        if ( $var_price > 0 && $var_units > 0 ) {
                                            $avg_regular_price += ( $var_price / $var_units );
                                            $price_count++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ( $price_count > 0 ) {
                        $avg_regular_price = $avg_regular_price / $price_count;
                        $savings_per_unit = max( 0, $avg_regular_price - $bundle_price_per_unit );
                    }
                }

                $available_offers[] = array(
                    'id' => $bundle->id,
                    'title' => $bundle->name,
                    'description' => $is_qualified 
                        ? sprintf( __( 'You qualify for %s bundle pricing! Save %s per unit.', 'pls-private-label-store' ), $bundle->name, wc_price( $savings_per_unit ) )
                        : sprintf( __( 'Add %d more product(s) with %d+ units each to qualify for %s bundle pricing.', 'pls-private-label-store' ), ( $required_sku_count - $qualified_products ), $required_units_per_sku, $bundle->name ),
                    'is_qualified' => $is_qualified,
                    'is_close' => $is_close,
                    'required_sku_count' => $required_sku_count,
                    'current_sku_count' => $qualified_products,
                    'required_units' => $required_units_per_sku,
                    'bundle_price_per_unit' => $bundle_price_per_unit,
                    'savings_per_unit' => $savings_per_unit,
                    'action' => array(
                        'type' => 'info', // Bundles are auto-applied, this is informational
                        'bundle_id' => $bundle->id,
                    ),
                );
            }
        }

        // Sort by qualification status (qualified first, then close)
        usort( $available_offers, function( $a, $b ) {
            if ( $a['is_qualified'] && ! $b['is_qualified'] ) {
                return -1;
            }
            if ( ! $a['is_qualified'] && $b['is_qualified'] ) {
                return 1;
            }
            return 0;
        } );

        wp_send_json_success( array( 'offers' => $available_offers ) );
    }

    /**
     * Apply bundle offer (informational - bundles are automatically applied by PLS_Bundle_Cart).
     * This endpoint provides feedback to users about bundle status.
     */
    public static function apply_offer() {
        check_ajax_referer( 'pls_offers', 'nonce' );

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( array( 'message' => __( 'Cart not available.', 'pls-private-label-store' ) ), 400 );
        }

        $offer_id = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
        $bundle_id = isset( $_POST['bundle_id'] ) ? absint( $_POST['bundle_id'] ) : $offer_id;

        if ( ! $bundle_id ) {
            wp_send_json_error( array( 'message' => __( 'Bundle ID required.', 'pls-private-label-store' ) ), 400 );
        }

        require_once PLS_PLS_DIR . 'includes/data/repo-bundle.php';
        $bundle = PLS_Repo_Bundle::get( $bundle_id );

        if ( ! $bundle ) {
            wp_send_json_error( array( 'message' => __( 'Bundle not found.', 'pls-private-label-store' ) ), 404 );
        }

        // Bundles are automatically applied by PLS_Bundle_Cart when cart qualifies
        // This endpoint just provides informational feedback
        $message = sprintf( 
            __( 'Bundle pricing for "%s" is automatically applied when your cart qualifies. Continue shopping to unlock bundle savings!', 'pls-private-label-store' ),
            $bundle->name
        );

        wp_send_json_success( array( 
            'message' => $message,
            'note' => __( 'Bundle pricing is applied automatically - no action needed!', 'pls-private-label-store' ),
        ) );
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

        // Validate required fields
        if ( ! $product_id || ! $variation_id ) {
            wp_send_json_error( array( 'message' => __( 'Product ID and variation ID are required.', 'pls-private-label-store' ) ) );
        }

        // Validate quantity (tiers are fixed packs, quantity should be 1)
        if ( $quantity < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid quantity.', 'pls-private-label-store' ) ) );
        }

        $variation = wc_get_product( $variation_id );
        if ( ! $variation || $variation->get_parent_id() !== $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid variation.', 'pls-private-label-store' ) ) );
        }

        // Check if variation is purchasable
        if ( ! $variation->is_purchasable() ) {
            wp_send_json_error( array( 'message' => __( 'This variation is not available for purchase.', 'pls-private-label-store' ) ) );
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
            // Check for WooCommerce notices
            $notices = wc_get_notices( 'error' );
            $error_message = __( 'Failed to add product to cart.', 'pls-private-label-store' );
            
            if ( ! empty( $notices ) ) {
                $error_message = wp_strip_all_tags( $notices[0]['notice'] );
            }
            
            wp_send_json_error( array( 'message' => $error_message ) );
        }
    }
}
