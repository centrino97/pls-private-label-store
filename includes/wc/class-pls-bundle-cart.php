<?php
/**
 * Bundle cart detection and pricing logic.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Bundle_Cart {

    /**
     * Initialize bundle cart hooks.
     */
    public static function init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'detect_and_apply_bundle_pricing' ), 10, 1 );
        add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'display_bundle_price' ), 10, 3 );
    }

    /**
     * Detect bundle qualification and apply pricing.
     *
     * @param WC_Cart $cart Cart object.
     */
    public static function detect_and_apply_bundle_pricing( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        // Get all bundles
        $bundles = PLS_Repo_Bundle::all();
        if ( empty( $bundles ) ) {
            return;
        }

        // Group cart items by PLS product
        $pls_items = array();
        $pls_products = PLS_Repo_Base_Product::all();
        $pls_wc_ids = array();
        foreach ( $pls_products as $product ) {
            if ( $product->wc_product_id ) {
                $pls_wc_ids[ $product->wc_product_id ] = $product->id;
            }
        }

        // Count distinct PLS products and their quantities
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            $variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;

            // Skip if not a PLS product
            if ( ! isset( $pls_wc_ids[ $product_id ] ) ) {
                continue;
            }

            // Skip if bundle pricing already applied (prevent double application)
            if ( isset( $cart_item['pls_bundle_price'] ) ) {
                continue;
            }

            // Get variation units if it's a variation
            $units_per_item = 0;
            if ( $variation_id ) {
                $variation = wc_get_product( $variation_id );
                if ( $variation ) {
                    // Try _pls_units meta first
                    $units_per_item = (int) get_post_meta( $variation_id, '_pls_units', true );
                    
                    // If not found, try pack tier term meta
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

            // Use quantity if units not found (fallback)
            if ( ! $units_per_item ) {
                $units_per_item = 1; // Default to 1 unit per item
            }

            $base_product_id = $pls_wc_ids[ $product_id ];
            if ( ! isset( $pls_items[ $base_product_id ] ) ) {
                $pls_items[ $base_product_id ] = array(
                    'product_id' => $product_id,
                    'base_product_id' => $base_product_id,
                    'total_units' => 0,
                    'cart_items' => array(),
                );
            }

            // Calculate total units: units_per_item × quantity
            $item_total_units = $units_per_item * $cart_item['quantity'];
            $pls_items[ $base_product_id ]['total_units'] += $item_total_units;
            $pls_items[ $base_product_id ]['cart_items'][] = array(
                'key' => $cart_item_key,
                'quantity' => $cart_item['quantity'],
                'units_per_item' => $units_per_item,
                'total_units' => $item_total_units,
            );
        }

        // Check each bundle for qualification
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

            // Count distinct products with matching units
            $matching_products = 0;
            foreach ( $pls_items as $item ) {
                // Check if this product has the required units per SKU
                if ( $item['total_units'] >= $required_units_per_sku ) {
                    $matching_products++;
                }
            }

            // Check if cart qualifies for this bundle
            // Qualification: At least N distinct products, each with at least M units
            $qualified_products = 0;
            foreach ( $pls_items as $item ) {
                if ( $item['total_units'] >= $required_units_per_sku ) {
                    $qualified_products++;
                }
            }

            if ( $qualified_products >= $required_sku_count ) {
                // Apply bundle pricing to all qualifying items
                foreach ( $pls_items as $item ) {
                    if ( $item['total_units'] >= $required_units_per_sku ) {
                        foreach ( $item['cart_items'] as $cart_item_data ) {
                            $cart_item = $cart->get_cart_item( $cart_item_data['key'] );
                            if ( $cart_item && ! isset( $cart_item['pls_bundle_price'] ) ) {
                                // Set bundle price per unit
                                $cart_item['data']->set_price( $bundle_price_per_unit );
                                
                                // Store bundle info in cart item data for commission calculation
                                $cart_item['pls_bundle_id'] = $bundle->id;
                                $cart_item['pls_bundle_key'] = $bundle->bundle_key;
                                $cart_item['pls_bundle_price'] = $bundle_price_per_unit;
                                $cart_item['pls_bundle_type'] = $bundle_rules['bundle_type'];
                                
                                $cart->cart_contents[ $cart_item_data['key'] ] = $cart_item;
                            }
                        }
                    }
                }

                // Add bundle notice (only once)
                if ( ! wc_has_notice( sprintf( __( 'Bundle pricing applied: %s', 'pls-private-label-store' ), $bundle->name ), 'success' ) ) {
                    wc_add_notice(
                        sprintf(
                            __( 'Bundle pricing applied: %s', 'pls-private-label-store' ),
                            $bundle->name
                        ),
                        'success'
                    );
                }

                // Only apply one bundle at a time (best match - highest priority)
                break;
            }
        }
    }

    /**
     * Display bundle price in cart.
     *
     * @param string $price Price HTML.
     * @param array  $cart_item Cart item data.
     * @param string $cart_item_key Cart item key.
     * @return string Modified price HTML.
     */
    public static function display_bundle_price( $price, $cart_item, $cart_item_key ) {
        if ( isset( $cart_item['pls_bundle_price'] ) ) {
            $bundle_price = floatval( $cart_item['pls_bundle_price'] );
            $quantity = isset( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1;
            $total = $bundle_price * $quantity;
            
            return '<span class="pls-bundle-price">' . wc_price( $total ) . ' <small>(' . wc_price( $bundle_price ) . ' per unit)</small></span>';
        }

        return $price;
    }

    /**
     * Save bundle info to order item meta when order is created.
     *
     * @param WC_Order_Item_Product $item Order item.
     * @param string                 $cart_item_key Cart item key.
     * @param array                  $values Cart item values.
     * @param WC_Order               $order Order object.
     */
    public static function save_bundle_info_to_order_item( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['pls_bundle_key'] ) ) {
            $item->add_meta_data( 'pls_bundle_key', $values['pls_bundle_key'] );
        }
        if ( isset( $values['pls_bundle_id'] ) ) {
            $item->add_meta_data( 'pls_bundle_id', $values['pls_bundle_id'] );
        }
        if ( isset( $values['pls_bundle_type'] ) ) {
            $item->add_meta_data( 'pls_bundle_type', $values['pls_bundle_type'] );
        }
    }
}
