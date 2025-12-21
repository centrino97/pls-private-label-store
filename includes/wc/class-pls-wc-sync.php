<?php
/**
 * WooCommerce sync layer.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_WC_Sync {

    public static function pack_tier_definitions() {
        return array(
            'u50'   => '50 units',
            'u100'  => '100 units',
            'u250'  => '250 units',
            'u500'  => '500 units',
            'u1000' => '1000 units',
        );
    }

    /**
     * Ensure global pack-tier attribute + terms exist.
     *
     * @return array|null { attribute_id, taxonomy, term_ids } or null on failure.
     */
    private static function ensure_pack_tier_attribute() {
        if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
            return null;
        }

        if ( ! function_exists( 'wc_create_attribute' ) ) {
            return null;
        }

        $slug       = 'pack-tier';
        $attribute  = null;
        $taxonomies = wc_get_attribute_taxonomies();
        if ( ! is_array( $taxonomies ) ) {
            $taxonomies = array();
        }

        foreach ( $taxonomies as $tax ) {
            if ( $slug === $tax->attribute_name ) {
                $attribute = $tax;
                break;
            }
        }

        if ( ! $attribute ) {
            $attr_id = wc_create_attribute(
                array(
                    'name'         => 'Pack Tier',
                    'slug'         => $slug,
                    'type'         => 'select',
                    'order_by'     => 'menu_order',
                    'has_archives' => false,
                )
            );

            if ( is_wp_error( $attr_id ) ) {
                return null;
            }

            delete_transient( 'wc_attribute_taxonomies' );
            $taxonomies = wc_get_attribute_taxonomies();
            if ( ! is_array( $taxonomies ) ) {
                $taxonomies = array();
            }

            foreach ( $taxonomies as $tax ) {
                if ( $slug === $tax->attribute_name ) {
                    $attribute = $tax;
                    break;
                }
            }
        }

        if ( ! $attribute ) {
            return null;
        }

        $taxonomy = wc_attribute_taxonomy_name( $slug );
        $term_ids = array();

        foreach ( self::pack_tier_definitions() as $key => $label ) {
            $term = get_term_by( 'slug', $key, $taxonomy );
            if ( ! $term ) {
                $inserted = wp_insert_term( $label, $taxonomy, array( 'slug' => $key ) );
                if ( ! is_wp_error( $inserted ) ) {
                    $term_ids[ $key ] = $inserted['term_id'];
                }
            } else {
                $term_ids[ $key ] = $term->term_id;
            }
        }

        return array(
            'attribute_id' => (int) $attribute->attribute_id,
            'taxonomy'     => $taxonomy,
            'term_ids'     => $term_ids,
        );
    }

    /**
     * Sync one base product into Woo (variable + variations).
     */
    public static function sync_base_product_to_wc( $base_product_id ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return new WP_Error( 'pls_wc_missing', __( 'WooCommerce not active; sync skipped.', 'pls-private-label-store' ) );
        }

        if ( ! function_exists( 'wc_get_product' ) ) {
            return new WP_Error( 'pls_wc_missing', __( 'WooCommerce product functions unavailable; sync skipped.', 'pls-private-label-store' ) );
        }

        $base = PLS_Repo_Base_Product::get( $base_product_id );
        if ( ! $base ) {
            return new WP_Error( 'pls_base_missing', __( 'Base product not found.', 'pls-private-label-store' ) );
        }

        $status  = ( 'live' === $base->status ) ? 'publish' : 'draft';
        $product = null;
        $created = false;

        if ( $base->wc_product_id ) {
            $product = wc_get_product( $base->wc_product_id );
        }

        if ( ! $product ) {
            $post_id = wp_insert_post(
                array(
                    'post_title'  => $base->name,
                    'post_name'   => $base->slug,
                    'post_type'   => 'product',
                    'post_status' => $status,
                )
            );

            if ( is_wp_error( $post_id ) ) {
                return __( 'Failed to create WooCommerce product.', 'pls-private-label-store' );
            }

            wp_set_object_terms( $post_id, 'variable', 'product_type' );
            PLS_Repo_Base_Product::set_wc_product_id( $base_product_id, $post_id );
            $product = wc_get_product( $post_id );
            $created = true;
        }

        if ( ! $product ) {
            return new WP_Error( 'pls_wc_product_missing', __( 'WooCommerce product not available.', 'pls-private-label-store' ) );
        }

        if ( $product->get_status() !== $status ) {
            $product->set_status( $status );
        }

        if ( method_exists( $product, 'set_type' ) ) {
            $product->set_type( 'variable' );
        }

        // Categories from PLS (comma-separated IDs in category_path).
        if ( ! empty( $base->category_path ) ) {
            $ids = array_map( 'absint', explode( ',', $base->category_path ) );
            $ids = array_filter( $ids );
            if ( $ids ) {
                wp_set_object_terms( $product->get_id(), $ids, 'product_cat' );
            }
        }

        $pack_attr = self::ensure_pack_tier_attribute();
        if ( ! $pack_attr ) {
            return new WP_Error( 'pls_pack_attribute_missing', __( 'Unable to ensure pack tier attribute.', 'pls-private-label-store' ) );
        }

        $wc_attribute = new WC_Product_Attribute();
        $wc_attribute->set_id( $pack_attr['attribute_id'] );
        $wc_attribute->set_name( $pack_attr['taxonomy'] );
        $wc_attribute->set_options( array_values( $pack_attr['term_ids'] ) );
        $wc_attribute->set_visible( true );
        $wc_attribute->set_variation( true );

        $product->set_attributes( array( $pack_attr['taxonomy'] => $wc_attribute ) );
        $product->save();

        // Variations from PLS pack tiers.
        $tiers               = PLS_Repo_Pack_Tier::for_base( $base_product_id );
        $created_variations  = 0;

        foreach ( $tiers as $tier ) {
            if ( (int) $tier->is_enabled !== 1 ) {
                continue;
            }

            $variation = null;
            if ( $tier->wc_variation_id ) {
                $variation = wc_get_product( $tier->wc_variation_id );
            }

            if ( ! $variation || ! $variation instanceof WC_Product_Variation ) {
                $variation = new WC_Product_Variation();
                $variation->set_parent_id( $product->get_id() );
            }

            $variation->set_attributes( array( $pack_attr['taxonomy'] => $tier->tier_key ) );
            $variation->set_regular_price( $tier->price );
            $variation->set_status( 'publish' );
            $variation->save();

            update_post_meta( $variation->get_id(), '_pls_units', $tier->units );
            PLS_Repo_Pack_Tier::set_wc_variation_id( $base_product_id, $tier->tier_key, $variation->get_id() );
            $created_variations++;
        }

        if ( $created ) {
            return sprintf(
                __( 'Created WooCommerce product #%d with %d variations.', 'pls-private-label-store' ),
                $product->get_id(),
                $created_variations
            );
        }

        return sprintf(
            __( 'Synced WooCommerce product #%d with %d variations.', 'pls-private-label-store' ),
            $product->get_id(),
            $created_variations
        );
    }

    /**
     * Sync all base products.
     */
    public static function sync_all_base_products() {
        $bases = PLS_Repo_Base_Product::all();
        if ( empty( $bases ) ) {
            return __( 'No base products to sync.', 'pls-private-label-store' );
        }

        $messages = array();
        foreach ( $bases as $base ) {
            $result = self::sync_base_product_to_wc( $base->id );
            if ( is_wp_error( $result ) ) {
                $messages[] = $result->get_error_message();
            } else {
                $messages[] = $result;
            }
        }

        return implode( ' ', $messages );
    }

    /**
     * Sync attributes/values/swatches from PLS tables into Woo global attributes + terms.
     */
    public static function sync_attributes_from_pls() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return __( 'WooCommerce not active; attribute sync skipped.', 'pls-private-label-store' );
        }

        if ( ! function_exists( 'wc_get_attribute_taxonomies' ) || ! function_exists( 'wc_create_attribute' ) ) {
            return __( 'WooCommerce attribute functions unavailable; sync skipped.', 'pls-private-label-store' );
        }

        $attributes = PLS_Repo_Attributes::attrs_all();
        $taxonomies = wc_get_attribute_taxonomies();
        if ( ! is_array( $taxonomies ) ) {
            $taxonomies = array();
        }

        foreach ( $attributes as $attr ) {
            $slug  = sanitize_title( $attr->attr_key );
            $match = null;

            foreach ( $taxonomies as $tax ) {
                if ( $slug === $tax->attribute_name ) {
                    $match = $tax;
                    break;
                }
            }

            if ( ! $match ) {
                $attr_id = wc_create_attribute(
                    array(
                        'name'         => $attr->label,
                        'slug'         => $slug,
                        'type'         => 'select',
                        'order_by'     => 'menu_order',
                        'has_archives' => false,
                    )
                );

                if ( is_wp_error( $attr_id ) ) {
                    continue;
                }

                delete_transient( 'wc_attribute_taxonomies' );
                $taxonomies = wc_get_attribute_taxonomies();
                if ( ! is_array( $taxonomies ) ) {
                    $taxonomies = array();
                }

                foreach ( $taxonomies as $tax ) {
                    if ( $slug === $tax->attribute_name ) {
                        $match = $tax;
                        break;
                    }
                }
            }

            if ( $match ) {
                PLS_Repo_Attributes::set_wc_attribute_id( $attr->id, $match->attribute_id );
            }

            $taxonomy = wc_attribute_taxonomy_name( $slug );
            $values   = PLS_Repo_Attributes::values_for_attr( $attr->id );

            foreach ( $values as $value ) {
                $term = get_term_by( 'slug', $value->value_key, $taxonomy );
                if ( ! $term ) {
                    $inserted = wp_insert_term(
                        $value->label,
                        $taxonomy,
                        array(
                            'slug' => $value->value_key,
                        )
                    );
                    if ( is_wp_error( $inserted ) ) {
                        continue;
                    }
                    $term_id = $inserted['term_id'];
                } else {
                    $term_id = $term->term_id;
                }

                PLS_Repo_Attributes::set_term_id_for_value( $value->id, $term_id );
                $swatch = PLS_Repo_Attributes::swatch_for_value( $value->id );
                if ( $swatch ) {
                    update_term_meta( $term_id, '_pls_swatch_type', $swatch->swatch_type );
                    update_term_meta( $term_id, '_pls_swatch_value', $swatch->swatch_value );
                }
            }
        }

        return __( 'Attributes synced to WooCommerce.', 'pls-private-label-store' );
    }
}
