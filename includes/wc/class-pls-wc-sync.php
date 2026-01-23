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

    /**
     * Get pack tier attribute ID.
     *
     * @return int|false Attribute ID or false if not set.
     */
    public static function get_pack_tier_attribute_id() {
        // Try to get primary attribute first (v0.8.3+)
        $primary = PLS_Repo_Attributes::get_primary_attribute();
        if ( $primary ) {
            update_option( 'pls_pack_tier_attribute_id', $primary->id );
            return $primary->id;
        }
        
        // Fallback to option for backward compatibility
        return get_option( 'pls_pack_tier_attribute_id', false );
    }

    /**
     * Get pack tier values dynamically from attribute system.
     *
     * @return array Array of attribute value objects.
     */
    public static function get_pack_tier_values() {
        $attr_id = self::get_pack_tier_attribute_id();
        if ( ! $attr_id ) {
            return array();
        }

        return PLS_Repo_Attributes::values_for_attr( $attr_id );
    }

    /**
     * Get pack tier definitions for backwards compatibility.
     * Maps tier values to old format.
     *
     * @return array Associative array of tier_key => label.
     */
    public static function pack_tier_definitions() {
        $tiers = self::get_pack_tier_values();
        $definitions = array();

        foreach ( $tiers as $tier ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
            $units = PLS_Tier_Rules::get_default_units_for_tier( $tier->id );
            if ( $units ) {
                $key = 'u' . $units;
                $definitions[ $key ] = $units . ' units';
            }
        }

        return $definitions;
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

        // Get pack tier values dynamically
        $pack_tier_values = self::get_pack_tier_values();
        require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';

        foreach ( $pack_tier_values as $tier_value ) {
            $units = PLS_Tier_Rules::get_default_units_for_tier( $tier_value->id );
            if ( ! $units ) {
                continue;
            }

            $key = 'u' . $units;
            $label = $tier_value->label . ' (' . $units . ' units)';

            $term = get_term_by( 'slug', $tier_value->value_key, $taxonomy );
            if ( ! $term ) {
                $inserted = wp_insert_term( $label, $taxonomy, array( 'slug' => $tier_value->value_key ) );
                if ( ! is_wp_error( $inserted ) ) {
                    $term_ids[ $key ] = $inserted['term_id'];
                    // Store tier level and units in term meta
                    update_term_meta( $inserted['term_id'], '_pls_tier_level', PLS_Tier_Rules::get_tier_level_from_value( $tier_value->id ) );
                    update_term_meta( $inserted['term_id'], '_pls_default_units', $units );
                }
            } else {
                $term_ids[ $key ] = $term->term_id;
                // Ensure metadata is set
                $tier_level = PLS_Tier_Rules::get_tier_level_from_value( $tier_value->id );
                if ( $tier_level ) {
                    update_term_meta( $term->term_id, '_pls_tier_level', $tier_level );
                }
                update_term_meta( $term->term_id, '_pls_default_units', $units );
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
        // Debug logging
        if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
            PLS_Debug::log_sync( 'sync_base_product_to_wc', array( 'base_product_id' => $base_product_id ) );
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::error( 'WooCommerce not active for sync', array( 'base_product_id' => $base_product_id ) );
            }
            return new WP_Error( 'pls_wc_missing', __( 'WooCommerce not active; sync skipped.', 'pls-private-label-store' ) );
        }

        if ( ! function_exists( 'wc_get_product' ) ) {
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::error( 'WooCommerce product functions unavailable', array( 'base_product_id' => $base_product_id ) );
            }
            return new WP_Error( 'pls_wc_missing', __( 'WooCommerce product functions unavailable; sync skipped.', 'pls-private-label-store' ) );
        }

        $base = PLS_Repo_Base_Product::get( $base_product_id );
        if ( ! $base ) {
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::error( 'Base product not found for sync', array( 'base_product_id' => $base_product_id ) );
            }
            return new WP_Error( 'pls_base_missing', __( 'Base product not found.', 'pls-private-label-store' ) );
        }

        $status  = ( 'live' === $base->status ) ? 'publish' : 'draft';
        $product = null;
        $created = false;

        // Debug: Log product status
        if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
            PLS_Debug::log_sync( 'sync_product_status', array(
                'base_product_id' => $base_product_id,
                'pls_status' => $base->status,
                'wc_status' => $status,
                'existing_wc_id' => $base->wc_product_id,
            ) );
        }

        if ( $base->wc_product_id ) {
            $product = wc_get_product( $base->wc_product_id );
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::log_sync( 'sync_product_found', array(
                    'base_product_id' => $base_product_id,
                    'wc_product_id' => $base->wc_product_id,
                    'product_exists' => ! ! $product,
                ) );
            }
        }

        if ( ! $product ) {
            // Debug: Log product creation
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::log_sync( 'sync_product_create', array(
                    'base_product_id' => $base_product_id,
                    'name' => $base->name,
                    'slug' => $base->slug,
                    'status' => $status,
                ) );
            }

            $post_id = wp_insert_post(
                array(
                    'post_title'  => $base->name,
                    'post_name'   => $base->slug,
                    'post_type'   => 'product',
                    'post_status' => $status,
                )
            );

            if ( is_wp_error( $post_id ) ) {
                if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                    PLS_Debug::error( 'Failed to create WooCommerce product', array(
                        'base_product_id' => $base_product_id,
                        'error' => $post_id->get_error_message(),
                    ) );
                }
                return new WP_Error( 'pls_wc_create_failed', __( 'Failed to create WooCommerce product.', 'pls-private-label-store' ) );
            }

            wp_set_object_terms( $post_id, 'variable', 'product_type' );
            PLS_Repo_Base_Product::set_wc_product_id( $base_product_id, $post_id );
            $product = wc_get_product( $post_id );
            $created = true;

            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::log_sync( 'sync_product_created', array(
                    'base_product_id' => $base_product_id,
                    'wc_product_id' => $post_id,
                ) );
            }
        }

        if ( ! $product ) {
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::error( 'WooCommerce product not available after creation', array(
                    'base_product_id' => $base_product_id,
                ) );
            }
            return new WP_Error( 'pls_wc_product_missing', __( 'WooCommerce product not available.', 'pls-private-label-store' ) );
        }

        // Update product status if needed
        if ( $product->get_status() !== $status ) {
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::log_sync( 'sync_product_status_update', array(
                    'base_product_id' => $base_product_id,
                    'wc_product_id' => $product->get_id(),
                    'old_status' => $product->get_status(),
                    'new_status' => $status,
                ) );
            }
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
                if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                    PLS_Debug::log_sync( 'sync_product_categories', array(
                        'base_product_id' => $base_product_id,
                        'wc_product_id' => $product->get_id(),
                        'category_ids' => $ids,
                    ) );
                }
                wp_set_object_terms( $product->get_id(), $ids, 'product_cat' );
            }
        }

        $pack_attr = self::ensure_pack_tier_attribute();
        if ( ! $pack_attr ) {
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::error( 'Unable to ensure pack tier attribute', array(
                    'base_product_id' => $base_product_id,
                ) );
            }
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

        if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
            PLS_Debug::log_sync( 'sync_product_attributes_set', array(
                'base_product_id' => $base_product_id,
                'wc_product_id' => $product->get_id(),
                'pack_attr_taxonomy' => $pack_attr['taxonomy'],
            ) );
        }

        // Variations from PLS pack tiers.
        // First, try to use new attribute-based system
        $pack_tier_attr_id = self::get_pack_tier_attribute_id();
        $created_variations = 0;
        $updated_variations = 0;
        $variation_errors = array();

        if ( $pack_tier_attr_id ) {
            // New system: Use pack tier attribute values
            require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
            $pack_tier_values = self::get_pack_tier_values();
            $tiers = PLS_Repo_Pack_Tier::for_base( $base_product_id );

            // Map old tier_key to new attribute values
            $tier_map = array();
            foreach ( $tiers as $tier ) {
                if ( (int) $tier->is_enabled !== 1 ) {
                    continue;
                }

                // Find corresponding attribute value by units
                foreach ( $pack_tier_values as $tier_value ) {
                    $units = PLS_Tier_Rules::get_default_units_for_tier( $tier_value->id );
                    if ( $units && $units === (int) $tier->units ) {
                        $tier_map[ $tier->tier_key ] = array(
                            'value' => $tier_value,
                            'price' => $tier->price,
                            'units' => $tier->units,
                            'wc_variation_id' => $tier->wc_variation_id,
                        );
                        break;
                    }
                }
            }

            // Create variations from mapped tiers
            foreach ( $tier_map as $tier_key => $tier_data ) {
                $tier_value = $tier_data['value'];
                $variation = null;

                if ( $tier_data['wc_variation_id'] ) {
                    $variation = wc_get_product( $tier_data['wc_variation_id'] );
                }

                if ( ! $variation || ! $variation instanceof WC_Product_Variation ) {
                    $variation = new WC_Product_Variation();
                    $variation->set_parent_id( $product->get_id() );
                }

                // Use value_key as the term slug
                $variation->set_attributes( array( $pack_attr['taxonomy'] => $tier_value->value_key ) );
                $variation->set_regular_price( $tier_data['price'] );
                $variation->set_status( 'publish' );
                $variation->save();

                // Store tier metadata
                $tier_level = PLS_Tier_Rules::get_tier_level_from_value( $tier_value->id );
                if ( $tier_level ) {
                    update_post_meta( $variation->get_id(), '_pls_tier_level', $tier_level );
                }
                update_post_meta( $variation->get_id(), '_pls_units', $tier_data['units'] );

                // Update backreference
                PLS_Repo_Pack_Tier::set_wc_variation_id( $base_product_id, $tier_key, $variation->get_id() );
                $created_variations++;
            }

            // Debug logging for sync completion
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::log_sync( 'sync_base_product_to_wc_complete', array(
                    'base_product_id' => $base_product_id,
                    'wc_product_id' => $product->get_id(),
                    'created' => $created,
                    'variations_created' => $created_variations,
                ) );
            }
        } else {
            // Fallback to old system for backwards compatibility
            $tiers = PLS_Repo_Pack_Tier::for_base( $base_product_id );

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
        }

        // Validate sync
        $validation_result = self::validate_product_sync( $base_product_id, $product->get_id() );
        
        // Debug logging for sync completion
        if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
            PLS_Debug::log_sync( 'sync_base_product_to_wc_complete', array(
                'base_product_id' => $base_product_id,
                'wc_product_id' => $product->get_id(),
                'created' => $created,
                'variations_created' => $created_variations,
                'validation' => $validation_result,
            ) );
            
            if ( ! empty( $validation_result['errors'] ) ) {
                PLS_Debug::warn( 'Product sync validation found errors', $validation_result );
            }
            if ( ! empty( $validation_result['warnings'] ) ) {
                PLS_Debug::warn( 'Product sync validation found warnings', $validation_result );
            }
        }
        
        // Return validation result if invalid, otherwise return success message
        if ( ! $validation_result['valid'] ) {
            return new WP_Error( 'pls_sync_validation_failed', 'Sync validation failed', $validation_result );
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

    /**
     * Sync bundle to WooCommerce as Grouped Product.
     *
     * @param int $bundle_id Bundle ID.
     * @return string|WP_Error Success message or error.
     */
    public static function sync_bundle_to_wc( $bundle_id ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return new WP_Error( 'pls_wc_missing', __( 'WooCommerce not active; sync skipped.', 'pls-private-label-store' ) );
        }

        if ( ! function_exists( 'wc_get_product' ) ) {
            return new WP_Error( 'pls_wc_missing', __( 'WooCommerce product functions unavailable; sync skipped.', 'pls-private-label-store' ) );
        }

        $bundle = PLS_Repo_Bundle::get( $bundle_id );
        if ( ! $bundle ) {
            return new WP_Error( 'pls_bundle_missing', __( 'Bundle not found.', 'pls-private-label-store' ) );
        }

        $status = ( 'live' === $bundle->status ) ? 'publish' : 'draft';
        $product = null;
        $created = false;

        if ( $bundle->wc_product_id ) {
            $product = wc_get_product( $bundle->wc_product_id );
        }

        if ( ! $product ) {
            $post_id = wp_insert_post(
                array(
                    'post_title'  => $bundle->name,
                    'post_name'   => $bundle->slug,
                    'post_type'   => 'product',
                    'post_status' => $status,
                )
            );

            if ( is_wp_error( $post_id ) ) {
                return new WP_Error( 'pls_wc_create_failed', __( 'Failed to create WooCommerce product.', 'pls-private-label-store' ) );
            }

            wp_set_object_terms( $post_id, 'grouped', 'product_type' );
            PLS_Repo_Bundle::set_wc_product_id( $bundle_id, $post_id );
            $product = wc_get_product( $post_id );
            $created = true;
        }

        if ( ! $product ) {
            return new WP_Error( 'pls_wc_product_missing', __( 'WooCommerce product not available.', 'pls-private-label-store' ) );
        }

        // Ensure product type is grouped
        if ( method_exists( $product, 'set_type' ) ) {
            $product->set_type( 'grouped' );
        }

        // Update status
        if ( $product->get_status() !== $status ) {
            $product->set_status( $status );
        }

        // Get bundle items (products in bundle)
        $bundle_items = PLS_Repo_Bundle_Item::for_bundle( $bundle_id );
        $child_product_ids = array();

        // If bundle items exist, use them; otherwise, bundle is customer-choice (no specific products)
        if ( ! empty( $bundle_items ) ) {
            foreach ( $bundle_items as $item ) {
                $base_product = PLS_Repo_Base_Product::get( $item->base_product_id );
                if ( $base_product && $base_product->wc_product_id ) {
                    $child_product_ids[] = $base_product->wc_product_id;
                }
            }
        }

        // Set grouped product children
        if ( method_exists( $product, 'set_children' ) ) {
            $product->set_children( $child_product_ids );
        } else {
            // Fallback: use post meta
            update_post_meta( $product->get_id(), '_children', $child_product_ids );
        }

        // Set price
        if ( $bundle->base_price ) {
            $product->set_regular_price( $bundle->base_price );
        }

        $product->save();

        // Store bundle metadata
        update_post_meta( $product->get_id(), '_pls_bundle_id', $bundle_id );
        update_post_meta( $product->get_id(), '_pls_bundle_key', $bundle->bundle_key );

        // Parse and store bundle rules
        $bundle_rules = ! empty( $bundle->offer_rules_json ) ? json_decode( $bundle->offer_rules_json, true ) : array();
        if ( ! empty( $bundle_rules ) ) {
            update_post_meta( $product->get_id(), '_pls_bundle_rules', $bundle_rules );
        }

        return __( 'Bundle synced to WooCommerce as Grouped Product.', 'pls-private-label-store' );
    }

    /**
     * Sync bundles stub (for backward compatibility).
     *
     * @return string Message.
     */
    public static function sync_bundles_stub() {
        $bundles = PLS_Repo_Bundle::all();
        $synced = 0;
        $errors = 0;

        foreach ( $bundles as $bundle ) {
            $result = self::sync_bundle_to_wc( $bundle->id );
            if ( is_wp_error( $result ) ) {
                $errors++;
            } else {
                $synced++;
            }
        }

        if ( $errors > 0 ) {
            return sprintf( __( 'Synced %d bundles. %d errors.', 'pls-private-label-store' ), $synced, $errors );
        }

        return sprintf( __( 'Synced %d bundles successfully.', 'pls-private-label-store' ), $synced );
    }
}
