<?php
/**
 * Lightweight admin AJAX endpoints used by the modal editor.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Admin_Ajax {

    public static function init() {
        add_action( 'wp_ajax_pls_create_ingredients', array( __CLASS__, 'create_ingredients' ) );
        add_action( 'wp_ajax_pls_save_product', array( __CLASS__, 'save_product' ) );
        add_action( 'wp_ajax_pls_delete_product', array( __CLASS__, 'delete_product' ) );
        add_action( 'wp_ajax_pls_sync_product', array( __CLASS__, 'sync_product' ) );
        add_action( 'wp_ajax_pls_sync_all_products', array( __CLASS__, 'sync_all_products' ) );
        add_action( 'wp_ajax_pls_activate_product', array( __CLASS__, 'activate_product' ) );
        add_action( 'wp_ajax_pls_deactivate_product', array( __CLASS__, 'deactivate_product' ) );
        add_action( 'wp_ajax_pls_create_attribute', array( __CLASS__, 'create_attribute' ) );
        add_action( 'wp_ajax_pls_update_attribute', array( __CLASS__, 'update_attribute' ) );
        add_action( 'wp_ajax_pls_create_attribute_value', array( __CLASS__, 'create_attribute_value' ) );
        add_action( 'wp_ajax_pls_update_attribute_values', array( __CLASS__, 'update_attribute_values' ) );
        add_action( 'wp_ajax_pls_update_attribute_tier_rules', array( __CLASS__, 'update_attribute_tier_rules' ) );
        add_action( 'wp_ajax_pls_set_pack_tier_attribute', array( __CLASS__, 'set_pack_tier_attribute' ) );
        add_action( 'wp_ajax_pls_get_tier_pricing', array( __CLASS__, 'get_tier_pricing' ) );
        add_action( 'wp_ajax_pls_delete_attribute', array( __CLASS__, 'delete_attribute' ) );
        add_action( 'wp_ajax_pls_delete_attribute_value', array( __CLASS__, 'delete_attribute_value' ) );
        add_action( 'wp_ajax_pls_update_attribute_value', array( __CLASS__, 'update_attribute_value' ) );
        add_action( 'wp_ajax_pls_update_pack_tier_defaults', array( __CLASS__, 'update_pack_tier_defaults' ) );
        add_action( 'wp_ajax_pls_update_label_pricing', array( __CLASS__, 'update_label_pricing' ) );
        add_action( 'wp_ajax_pls_get_product_options_data', array( __CLASS__, 'get_product_options_data' ) );
        add_action( 'wp_ajax_pls_preview_product', array( __CLASS__, 'preview_product' ) );
        add_action( 'wp_ajax_pls_custom_product_request', array( __CLASS__, 'custom_product_request' ) );
        add_action( 'wp_ajax_pls_update_custom_order_status', array( __CLASS__, 'update_custom_order_status' ) );
        add_action( 'wp_ajax_pls_update_custom_order', array( __CLASS__, 'update_custom_order' ) );
        add_action( 'wp_ajax_pls_get_custom_order_details', array( __CLASS__, 'get_custom_order_details' ) );
        add_action( 'wp_ajax_pls_update_custom_order_financials', array( __CLASS__, 'update_custom_order_financials' ) );
        add_action( 'wp_ajax_pls_mark_custom_order_invoiced', array( __CLASS__, 'mark_custom_order_invoiced' ) );
        add_action( 'wp_ajax_pls_mark_custom_order_paid', array( __CLASS__, 'mark_custom_order_paid' ) );
        add_action( 'wp_ajax_pls_mark_commission_invoiced', array( __CLASS__, 'mark_commission_invoiced' ) );
        add_action( 'wp_ajax_pls_mark_commission_paid', array( __CLASS__, 'mark_commission_paid' ) );
        add_action( 'wp_ajax_pls_mark_commission_invoiced_monthly', array( __CLASS__, 'mark_commission_invoiced_monthly' ) );
        add_action( 'wp_ajax_pls_mark_commission_paid_monthly', array( __CLASS__, 'mark_commission_paid_monthly' ) );
        add_action( 'wp_ajax_pls_bulk_update_commission', array( __CLASS__, 'bulk_update_commission' ) );
        add_action( 'wp_ajax_pls_send_monthly_report', array( __CLASS__, 'send_monthly_report' ) );
        add_action( 'wp_ajax_pls_save_bundle', array( __CLASS__, 'save_bundle' ) );
        add_action( 'wp_ajax_pls_delete_bundle', array( __CLASS__, 'delete_bundle' ) );
        add_action( 'wp_ajax_pls_sync_bundle', array( __CLASS__, 'sync_bundle' ) );
        add_action( 'wp_ajax_pls_get_bundle', array( __CLASS__, 'get_bundle' ) );
        add_action( 'wp_ajax_pls_get_bi_metrics', array( __CLASS__, 'get_bi_metrics' ) );
        add_action( 'wp_ajax_pls_get_bi_chart_data', array( __CLASS__, 'get_bi_chart_data' ) );
        add_action( 'wp_ajax_pls_save_marketing_cost', array( __CLASS__, 'save_marketing_cost' ) );
        add_action( 'wp_ajax_pls_get_product_performance', array( __CLASS__, 'get_product_performance' ) );

        // System Test AJAX handlers
        add_action( 'wp_ajax_pls_run_test_category', array( __CLASS__, 'run_test_category' ) );
        add_action( 'wp_ajax_pls_run_all_tests', array( __CLASS__, 'run_all_tests' ) );
        add_action( 'wp_ajax_pls_fix_issue', array( __CLASS__, 'fix_issue' ) );
    }

    /**
     * Create ingredients immediately from the modal and return a refreshed payload.
     */
    public static function create_ingredients() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $items   = isset( $_POST['ingredients'] ) ? (array) $_POST['ingredients'] : array();
        $created = array();

        foreach ( $items as $item ) {
            $name  = isset( $item['name'] ) ? sanitize_text_field( wp_unslash( $item['name'] ) ) : '';
            $short = isset( $item['short_description'] ) ? sanitize_text_field( wp_unslash( $item['short_description'] ) ) : '';
            $icon_id = isset( $item['icon_id'] ) ? absint( $item['icon_id'] ) : 0;
            $icon    = $icon_id ? wp_get_attachment_url( $icon_id ) : '';

            if ( '' === $name ) {
                continue;
            }

            $slug    = sanitize_title( $name );
            $maybe   = term_exists( $slug, 'pls_ingredient' );
            $term_id = 0;

            if ( ! $maybe ) {
                $result = wp_insert_term( $name, 'pls_ingredient', array( 'slug' => $slug ) );
                if ( ! is_wp_error( $result ) ) {
                    $term_id = isset( $result['term_id'] ) ? absint( $result['term_id'] ) : 0;
                }
            } elseif ( is_array( $maybe ) && isset( $maybe['term_id'] ) ) {
                $term_id = absint( $maybe['term_id'] );
            } elseif ( is_object( $maybe ) && isset( $maybe->term_id ) ) {
                $term_id = absint( $maybe->term_id );
            }

            if ( ! $term_id ) {
                continue;
            }

            if ( '' !== $short || '' === get_term_meta( $term_id, 'pls_ingredient_short_desc', true ) ) {
                update_term_meta( $term_id, 'pls_ingredient_short_desc', $short );
            }

            if ( $icon_id ) {
                update_term_meta( $term_id, 'pls_ingredient_icon_id', $icon_id );
                update_term_meta( $term_id, 'pls_ingredient_icon', $icon );
            }

            $term_obj = get_term( $term_id );
            $created[] = array(
                'id'                 => $term_id,
                'term_id'            => $term_id,
                'name'               => $term_obj ? $term_obj->name : $name,
                'label'              => $term_obj ? $term_obj->name : $name,
                'short_description'  => sanitize_text_field( (string) get_term_meta( $term_id, 'pls_ingredient_short_desc', true ) ),
                'icon'               => PLS_Taxonomies::icon_for_term( $term_id ),
            );
        }

        wp_send_json_success(
            array(
                'created'         => $created,
                'ingredients'     => self::ingredient_payload(),
                'default_icon'    => PLS_Taxonomies::default_icon(),
            )
        );
    }

    /**
     * Provide a consistent ingredient payload for JS consumers.
     *
     * @return array
     */
    public static function ingredient_payload() {
        $terms = get_terms(
            array(
                'taxonomy'   => 'pls_ingredient',
                'hide_empty' => false,
            )
        );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        $payload = array();

        foreach ( $terms as $term ) {
            $payload[] = array(
                'id'                => $term->term_id,
                'term_id'           => $term->term_id,
                'name'              => $term->name,
                'label'             => $term->name,
                'short_description' => sanitize_text_field( (string) get_term_meta( $term->term_id, 'pls_ingredient_short_desc', true ) ),
                'icon'              => PLS_Taxonomies::icon_for_term( $term->term_id ),
            );
        }

        return $payload;
    }

    /**
     * Create attribute via AJAX.
     */
    public static function create_attribute() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $label            = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
        $is_variation     = isset( $_POST['is_variation'] ) ? 1 : 0;
        $option_type      = isset( $_POST['option_type'] ) ? sanitize_text_field( wp_unslash( $_POST['option_type'] ) ) : 'product-option';
        $is_primary       = isset( $_POST['is_primary'] ) ? 1 : 0;
        $default_min_tier = isset( $_POST['default_min_tier'] ) ? absint( $_POST['default_min_tier'] ) : 1;

        // Clamp default_min_tier to 1-5
        $default_min_tier = max( 1, min( 5, $default_min_tier ) );

        if ( '' === $label ) {
            wp_send_json_error( array( 'message' => __( 'Attribute label is required.', 'pls-private-label-store' ) ), 400 );
        }

        // Validate option_type
        $valid_types = array( 'pack-tier', 'product-option', 'ingredient' );
        if ( ! in_array( $option_type, $valid_types, true ) ) {
            $option_type = 'product-option';
        }

        // Validate: only one primary attribute allowed
        if ( $is_primary ) {
            $existing_primary = PLS_Repo_Attributes::get_primary_attribute();
            if ( $existing_primary ) {
                wp_send_json_error( array( 'message' => __( 'Only one primary attribute is allowed. Please unset the existing primary attribute first.', 'pls-private-label-store' ) ), 400 );
            }
        }

        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'            => $label,
                'is_variation'     => $is_variation,
                'option_type'      => $option_type,
                'is_primary'       => $is_primary,
                'default_min_tier' => $default_min_tier,
            )
        );

        wp_send_json_success(
            array(
                'attribute' => array(
                    'id'               => $attr_id,
                    'label'            => $label,
                    'option_type'      => $option_type,
                    'is_primary'       => $is_primary,
                    'default_min_tier' => $default_min_tier,
                    'values'           => array(),
                ),
            )
        );
    }

    /**
     * Update attribute via AJAX.
     */
    public static function update_attribute() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $id               = isset( $_POST['option_id'] ) ? absint( $_POST['option_id'] ) : 0;
        $label            = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
        $is_variation     = isset( $_POST['is_variation'] ) ? 1 : 0;
        $default_min_tier = isset( $_POST['default_min_tier'] ) ? absint( $_POST['default_min_tier'] ) : 1;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Attribute ID is required.', 'pls-private-label-store' ) ), 400 );
        }

        if ( '' === $label ) {
            wp_send_json_error( array( 'message' => __( 'Attribute label is required.', 'pls-private-label-store' ) ), 400 );
        }

        // Clamp default_min_tier to 1-5
        $default_min_tier = max( 1, min( 5, $default_min_tier ) );

        $result = PLS_Repo_Attributes::update_attr(
            $id,
            array(
                'label'            => $label,
                'is_variation'     => $is_variation,
                'default_min_tier' => $default_min_tier,
            )
        );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Attribute updated successfully.', 'pls-private-label-store' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update attribute.', 'pls-private-label-store' ) ), 500 );
        }
    }

    /**
     * Create attribute value via AJAX.
     */
    public static function create_attribute_value() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $attribute_id = isset( $_POST['attribute_id'] ) ? absint( $_POST['attribute_id'] ) : 0;
        $label        = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
        $price_raw    = isset( $_POST['price'] ) ? wp_unslash( $_POST['price'] ) : '';
        $price        = '' !== $price_raw ? round( floatval( $price_raw ), 2 ) : '';
        $min_tier     = isset( $_POST['min_tier_level'] ) ? absint( $_POST['min_tier_level'] ) : 1;

        if ( ! $attribute_id || '' === $label ) {
            wp_send_json_error( array( 'message' => __( 'Attribute and value label are required.', 'pls-private-label-store' ) ), 400 );
        }

        // Check if this is an ingredient attribute - default to Tier 3+
        $attr = PLS_Repo_Attributes::get_primary_attribute();
        $is_ingredient = false;
        if ( $attribute_id ) {
            global $wpdb;
            $table = PLS_Repositories::table( 'attribute' );
            $attr_obj = $wpdb->get_row(
                $wpdb->prepare( "SELECT option_type FROM {$table} WHERE id = %d", $attribute_id )
            );
            if ( $attr_obj && $attr_obj->option_type === 'ingredient' ) {
                $is_ingredient = true;
                $min_tier = 3; // Ingredients are always Tier 3+
            }
        }

        $value_id = PLS_Repo_Attributes::insert_value(
            array(
                'attribute_id' => $attribute_id,
                'label'        => $label,
            )
        );

        // Set tier rules
        if ( $value_id ) {
            PLS_Repo_Attributes::update_value_tier_rules( $value_id, $min_tier, null );
        }

        $value_row = PLS_Repo_Attributes::get_value( $value_id );
        if ( '' !== $price && $value_row && $value_row->term_id ) {
            update_term_meta( $value_row->term_id, '_pls_default_price_impact', $price );
        }

        wp_send_json_success(
            array(
                'value' => array(
                    'id'            => $value_id,
                    'label'         => $label,
                    'attribute_id'  => $attribute_id,
                    'price'         => '' !== $price ? $price : '',
                    'min_tier_level' => $min_tier,
                ),
            )
        );
    }

    /**
     * Update default impacts for attribute values.
     */
    public static function update_attribute_values() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $attribute_id = isset( $_POST['attribute_id'] ) ? absint( $_POST['attribute_id'] ) : 0;
        $values       = isset( $_POST['values'] ) ? (array) $_POST['values'] : array();

        if ( ! $attribute_id ) {
            wp_send_json_error( array( 'message' => __( 'Attribute ID required.', 'pls-private-label-store' ) ), 400 );
        }

        foreach ( $values as $value_row ) {
            $value_id = isset( $value_row['id'] ) ? absint( $value_row['id'] ) : 0;
            $price    = isset( $value_row['price'] ) ? round( floatval( $value_row['price'] ), 2 ) : '';
            if ( ! $value_id ) {
                continue;
            }
            $value_obj = PLS_Repo_Attributes::get_value( $value_id );
            if ( $value_obj && $value_obj->term_id && '' !== $price ) {
                update_term_meta( $value_obj->term_id, '_pls_default_price_impact', $price );
            }
        }

        $attr_payload = self::attribute_payload();
        $attribute    = null;
        foreach ( $attr_payload as $attr ) {
            if ( isset( $attr['id'] ) && (int) $attr['id'] === $attribute_id ) {
                $attribute = $attr;
                break;
            }
        }

        wp_send_json_success(
            array(
                'attribute' => $attribute,
            )
        );
    }

    /**
     * Build attribute payload (attributes + values).
     *
     * @return array
     */
    public static function attribute_payload() {
        $attrs    = PLS_Repo_Attributes::attrs_all();
        $payload  = array();

        foreach ( $attrs as $attr ) {
            $values = array();
            $raw_values = PLS_Repo_Attributes::values_for_attr( $attr->id );
            foreach ( $raw_values as $value ) {
                $price_meta = ( $value->term_id ) ? get_term_meta( $value->term_id, '_pls_default_price_impact', true ) : '';
                $values[]   = array(
                    'id'            => $value->id,
                    'label'         => $value->label,
                    'price'         => '' !== $price_meta ? floatval( $price_meta ) : ( isset( $value->price ) ? floatval( $value->price ) : '' ),
                    'term_id'       => $value->term_id,
                    'min_tier_level' => isset( $value->min_tier_level ) ? absint( $value->min_tier_level ) : 1,
                    'tier_price_overrides' => ! empty( $value->tier_price_overrides ) ? json_decode( $value->tier_price_overrides, true ) : null,
                );
            }
            $payload[] = array(
                'id'          => $attr->id,
                'label'       => $attr->label,
                'attr_key'    => isset( $attr->attr_key ) ? $attr->attr_key : '',
                'option_type' => isset( $attr->option_type ) ? $attr->option_type : 'product-option',
                'is_primary'  => isset( $attr->is_primary ) ? (bool) $attr->is_primary : false,
                'values'      => $values,
            );
        }

        return $payload;
    }

    /**
     * Format a single product payload for JS consumers.
     *
     * @param object $product Base product row.
     * @param string $label_guide_constant Default label guide URL.
     * @return array|null
     */
    public static function format_product_payload( $product, $label_guide_constant ) {
        if ( ! $product ) {
            return null;
        }

        $profile           = PLS_Repo_Product_Profile::get_for_base( $product->id );
        $tiers             = PLS_Repo_Pack_Tier::for_base( $product->id );
        $attr_payload      = self::attribute_payload();
        $profile_skin      = $profile && $profile->skin_types_json ? json_decode( $profile->skin_types_json, true ) : array();
        $profile_benefits  = $profile && $profile->benefits_json ? json_decode( $profile->benefits_json, true ) : array();
        $profile_key_ing   = $profile && $profile->key_ingredients_json ? json_decode( $profile->key_ingredients_json, true ) : array();
        $profile_attrs_raw = $profile && $profile->basics_json ? json_decode( $profile->basics_json, true ) : array();
        $gallery_ids       = $profile && $profile->gallery_ids ? array_filter( array_map( 'absint', explode( ',', $profile->gallery_ids ) ) ) : array();
        $ingredient_ids    = $profile && $profile->ingredients_list ? array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) ) : array();
        $attr_lookup       = array();
        $featured_thumb    = ( $profile && $profile->featured_image_id ) ? wp_get_attachment_image_src( $profile->featured_image_id, 'thumbnail' ) : false;
        $gallery_media     = array();

        foreach ( $gallery_ids as $gid ) {
            $thumb = wp_get_attachment_image_src( $gid, 'thumbnail' );
            $gallery_media[] = array(
                'id'  => $gid,
                'url' => $thumb ? $thumb[0] : '',
            );
        }

        foreach ( $attr_payload as $attr ) {
            if ( isset( $attr['id'], $attr['label'] ) ) {
                $attr_lookup[ $attr['id'] ] = $attr['label'];
            }
        }

        $format_attr_rows = function( $raw_rows ) use ( $attr_lookup ) {
            if ( empty( $raw_rows ) ) {
                return array();
            }

            $normalized = array();

            foreach ( $raw_rows as $row ) {
                if ( isset( $row['values'] ) ) {
                    $attr_id    = isset( $row['attribute_id'] ) ? absint( $row['attribute_id'] ) : 0;
                    $attr_label = isset( $row['attribute_label'] ) ? $row['attribute_label'] : ( $attr_id && isset( $attr_lookup[ $attr_id ] ) ? $attr_lookup[ $attr_id ] : '' );
                    $values     = array();

                    foreach ( $row['values'] as $value ) {
                        $values[] = array(
                            'value_id'    => isset( $value['value_id'] ) ? absint( $value['value_id'] ) : 0,
                            'value_label' => isset( $value['value_label'] ) ? $value['value_label'] : '',
                            'price'       => isset( $value['price'] ) ? floatval( $value['price'] ) : 0,
                        );
                    }

                    if ( $attr_id && $values ) {
                        $normalized[] = array(
                            'attribute_id'    => $attr_id,
                            'attribute_label' => $attr_label,
                            'values'          => $values,
                        );
                    }
                    continue;
                }
            }

            if ( $normalized ) {
                return $normalized;
            }

            $grouped = array();

            foreach ( $raw_rows as $row ) {
                $attr_id     = isset( $row['attribute_id'] ) ? absint( $row['attribute_id'] ) : 0;
                $attr_label  = isset( $row['attribute_label'] ) ? $row['attribute_label'] : ( $attr_id && isset( $attr_lookup[ $attr_id ] ) ? $attr_lookup[ $attr_id ] : '' );
                $value_id    = isset( $row['value_id'] ) ? absint( $row['value_id'] ) : 0;
                $value_label = isset( $row['value_label'] ) ? $row['value_label'] : '';
                $price       = isset( $row['price'] ) ? floatval( $row['price'] ) : 0;

                if ( ! $attr_id && ! $attr_label ) {
                    continue;
                }

                $key = $attr_id ? 'id-' . $attr_id : 'label-' . md5( $attr_label );

                if ( ! isset( $grouped[ $key ] ) ) {
                    $grouped[ $key ] = array(
                        'attribute_id'    => $attr_id,
                        'attribute_label' => $attr_label,
                        'values'          => array(),
                    );
                }

                if ( $value_id || $value_label ) {
                    $grouped[ $key ]['values'][] = array(
                        'value_id'    => $value_id,
                        'value_label' => $value_label,
                        'price'       => $price,
                    );
                }
            }

            return array_values( array_filter( $grouped, fn( $row ) => ! empty( $row['values'] ) ) );
        };

        $profile_attrs = $format_attr_rows( $profile_attrs_raw );

        return array(
            'id'                  => $product->id,
            'wc_product_id'       => $product->wc_product_id ? absint( $product->wc_product_id ) : 0,
            'name'                => $product->name,
            'slug'                => $product->slug,
            'status'              => $product->status,
            'categories'          => $product->category_path ? array_map( 'absint', explode( ',', $product->category_path ) ) : array(),
            'pack_tiers'          => $tiers,
            'short_description'   => $profile ? $profile->short_description : '',
            'long_description'    => $profile ? $profile->long_description : '',
            'directions_text'     => $profile ? $profile->directions_text : '',
            'ingredients_list'    => $ingredient_ids,
            'featured_image_id'   => $profile ? absint( $profile->featured_image_id ) : 0,
            'gallery_ids'         => $gallery_ids,
            'featured_image_thumb'=> $featured_thumb && isset( $featured_thumb[0] ) ? $featured_thumb[0] : '',
            'gallery_media'       => $gallery_media,
            'label_enabled'       => $profile ? absint( $profile->label_enabled ) : 0,
            'label_price_per_unit'=> $profile ? floatval( $profile->label_price_per_unit ) : 0,
            'label_requires_file' => $profile ? absint( $profile->label_requires_file ) : 0,
            'label_helper_text'   => '',
            'label_guide_url'     => $profile && ! empty( $profile->label_guide_url ) ? $profile->label_guide_url : $label_guide_constant,
            'skin_types'          => $profile_skin,
            'benefits'            => $profile_benefits,
            'key_ingredients'     => $profile_key_ing,
            'attributes'          => $profile_attrs,
            'sync_status'         => self::get_sync_status( $product->id ),
            'sync_state'          => self::detect_product_sync_state( $product->id ),
            // Stock management fields
            'manage_stock'        => isset( $product->manage_stock ) ? absint( $product->manage_stock ) : 0,
            'stock_quantity'      => isset( $product->stock_quantity ) ? $product->stock_quantity : '',
            'stock_status'        => isset( $product->stock_status ) ? $product->stock_status : 'instock',
            'backorders_allowed'  => isset( $product->backorders_allowed ) ? absint( $product->backorders_allowed ) : 0,
            'low_stock_threshold' => isset( $product->low_stock_threshold ) ? $product->low_stock_threshold : '',
            // Cost fields
            'shipping_cost'       => isset( $product->shipping_cost ) ? floatval( $product->shipping_cost ) : '',
            'packaging_cost'      => isset( $product->packaging_cost ) ? floatval( $product->packaging_cost ) : '',
        );
    }

    /**
     * Reconcile products pointing at deleted Woo products.
     *
     * @param array $products Base products list.
     * @return array Filtered products.
     */
    /**
     * Reconcile products - reads ALL WooCommerce products directly and ensures PLS records match.
     * WooCommerce is the source of truth (backend sync).
     */
    public static function reconcile_orphaned_products( $products ) {
        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
            return $products; // Return as-is if WooCommerce not available
        }

        // Step 1: Read ALL WooCommerce products with PLS markers directly from WooCommerce
        $all_wc_products = get_posts( array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_key' => '_pls_base_product_id',
            'fields' => 'ids',
        ) );

        // Step 2: Build map of WC product ID => PLS base product ID
        $wc_to_pls_map = array();
        foreach ( $all_wc_products as $wc_product_id ) {
            $pls_base_id = get_post_meta( $wc_product_id, '_pls_base_product_id', true );
            if ( $pls_base_id ) {
                $wc_to_pls_map[ $wc_product_id ] = absint( $pls_base_id );
            }
        }

        // Step 3: Verify each PLS product's WooCommerce product exists
        $clean = array();
        $pls_ids_found = array();

        foreach ( $products as $product ) {
            if ( $product->wc_product_id ) {
                // Verify WooCommerce product exists directly
                $wc_product = wc_get_product( $product->wc_product_id );
                if ( ! $wc_product ) {
                    // WooCommerce product doesn't exist - clear reference
                    error_log( '[PLS Reconcile] WooCommerce product ' . $product->wc_product_id . ' not found, clearing reference for PLS product ' . $product->id );
                    PLS_Repo_Base_Product::set_wc_product_id( $product->id, null );
                    $product->wc_product_id = null;
                } else {
                    // Verify the PLS base_product_id meta matches
                    $meta_pls_id = get_post_meta( $product->wc_product_id, '_pls_base_product_id', true );
                    if ( $meta_pls_id && absint( $meta_pls_id ) !== $product->id ) {
                        error_log( '[PLS Reconcile] Mismatch: WC product ' . $product->wc_product_id . ' has PLS ID ' . $meta_pls_id . ' but PLS record says ' . $product->id );
                        // WooCommerce meta is source of truth - update PLS record
                        PLS_Repo_Base_Product::set_wc_product_id( absint( $meta_pls_id ), $product->wc_product_id );
                        // Clear this product's reference
                        PLS_Repo_Base_Product::set_wc_product_id( $product->id, null );
                        $product->wc_product_id = null;
                    }
                }
            }
            
            if ( $product->wc_product_id ) {
                $pls_ids_found[] = $product->id;
            }
            
            $clean[] = $product;
        }

        // Step 4: Find WooCommerce products with PLS markers that don't have PLS records
        foreach ( $wc_to_pls_map as $wc_product_id => $pls_base_id ) {
            $pls_product = PLS_Repo_Base_Product::get( $pls_base_id );
            if ( ! $pls_product ) {
                // WooCommerce product exists but PLS record doesn't - create PLS record from WooCommerce
                $wc_product = wc_get_product( $wc_product_id );
                if ( $wc_product ) {
                    error_log( '[PLS Reconcile] Creating PLS record for WooCommerce product ' . $wc_product_id . ' (name: ' . $wc_product->get_name() . ')' );
                    // Create PLS record from WooCommerce product
                    $slug = sanitize_title( $wc_product->get_name() );
                    $new_pls_id = PLS_Repo_Base_Product::insert( array(
                        'name' => $wc_product->get_name(),
                        'slug' => $slug,
                        'status' => ( $wc_product->get_status() === 'publish' ) ? 'live' : 'draft',
                        'category_path' => '',
                    ) );
                    if ( $new_pls_id ) {
                        PLS_Repo_Base_Product::set_wc_product_id( $new_pls_id, $wc_product_id );
                        // Add to clean array
                        $pls_product = PLS_Repo_Base_Product::get( $new_pls_id );
                        if ( $pls_product ) {
                            $clean[] = $pls_product;
                        }
                    }
                }
            } elseif ( ! $pls_product->wc_product_id || $pls_product->wc_product_id != $wc_product_id ) {
                // PLS record exists but wc_product_id doesn't match - update it
                error_log( '[PLS Reconcile] Updating PLS product ' . $pls_base_id . ' to point to WooCommerce product ' . $wc_product_id );
                PLS_Repo_Base_Product::set_wc_product_id( $pls_base_id, $wc_product_id );
            }
        }

        return $clean;
    }

    private static function sync_status_key( $base_id ) {
        return 'pls_sync_status_' . absint( $base_id );
    }

    public static function get_sync_status( $base_id ) {
        $status = get_option( self::sync_status_key( $base_id ) );
        if ( ! is_array( $status ) ) {
            return null;
        }

        return array(
            'timestamp' => isset( $status['timestamp'] ) ? absint( $status['timestamp'] ) : 0,
            'message'   => isset( $status['message'] ) ? sanitize_text_field( $status['message'] ) : '',
            'success'   => isset( $status['success'] ) ? (bool) $status['success'] : false,
        );
    }

    private static function record_sync_status( $base_id, $message, $success = true ) {
        update_option(
            self::sync_status_key( $base_id ),
            array(
                'timestamp' => time(),
                'message'   => $message,
                'success'   => $success ? 1 : 0,
            ),
            false
        );
    }

    /**
     * Detect product sync state by comparing PLS product with WooCommerce product.
     *
     * @param int $base_product_id PLS base product ID.
     * @return string Sync state: 'synced_active', 'synced_inactive', 'update_available', or 'not_synced'.
     */
    public static function detect_product_sync_state( $base_product_id ) {
        $base = PLS_Repo_Base_Product::get( $base_product_id );
        if ( ! $base ) {
            return 'not_synced';
        }

        // No WooCommerce product ID means not synced
        if ( ! $base->wc_product_id ) {
            return 'not_synced';
        }

        // Check if WooCommerce product exists
        if ( ! function_exists( 'wc_get_product' ) ) {
            return 'not_synced';
        }

        $wc_product = wc_get_product( $base->wc_product_id );
        if ( ! $wc_product ) {
            return 'not_synced';
        }

        // Get PLS pack tiers (enabled only)
        $pls_tiers = PLS_Repo_Pack_Tier::for_base( $base_product_id );
        $enabled_pls_tiers = array_filter( $pls_tiers, function( $tier ) {
            return (int) $tier->is_enabled === 1;
        } );

        // Get WC variations
        $wc_variations = array();
        if ( $wc_product->is_type( 'variable' ) ) {
            $variation_ids = $wc_product->get_children();
            foreach ( $variation_ids as $variation_id ) {
                $variation = wc_get_product( $variation_id );
                if ( $variation && $variation->get_status() === 'publish' ) {
                    $wc_variations[] = $variation;
                }
            }
        }

        // Check if data matches
        $pls_status = ( 'live' === $base->status ) ? 'publish' : 'draft';
        $wc_status = $wc_product->get_status();

        // Compare basic fields
        $name_matches = $wc_product->get_name() === $base->name;
        $slug_matches = $wc_product->get_slug() === $base->slug;
        $status_matches = $wc_status === $pls_status;

        // Compare categories - clear cache first to ensure fresh data
        clean_object_term_cache( $base->wc_product_id, 'product_cat' );
        wp_cache_delete( $base->wc_product_id, 'product_cat_relationships' );
        
        $pls_categories = ! empty( $base->category_path ) ? array_map( 'absint', explode( ',', $base->category_path ) ) : array();
        $pls_categories = array_filter( $pls_categories );
        sort( $pls_categories );

        $wc_category_ids = $wc_product->get_category_ids();
        sort( $wc_category_ids );

        $categories_match = $pls_categories === $wc_category_ids;

        // Compare pack tier count - be more lenient: allow 1 variation difference
        $tier_count_diff = abs( count( $enabled_pls_tiers ) - count( $wc_variations ) );
        $tier_count_matches = $tier_count_diff <= 1; // Allow 1 variation difference for timing issues

        // Compare pack tier prices and units more accurately
        $tiers_match = true;
        if ( $tier_count_matches && count( $enabled_pls_tiers ) > 0 ) {
            // Build map of WC variations by units
            $wc_variations_by_units = array();
            foreach ( $wc_variations as $variation ) {
                $units = get_post_meta( $variation->get_id(), '_pls_units', true );
                if ( $units ) {
                    $wc_variations_by_units[ (int) $units ] = array(
                        'variation' => $variation,
                        'price' => (float) $variation->get_regular_price(),
                        'units' => (int) $units,
                    );
                }
            }

            // Compare each PLS tier with corresponding WC variation
            foreach ( $enabled_pls_tiers as $pls_tier ) {
                $pls_units = (int) $pls_tier->units;
                $pls_price = (float) $pls_tier->price;

                if ( isset( $wc_variations_by_units[ $pls_units ] ) ) {
                    $wc_tier = $wc_variations_by_units[ $pls_units ];
                    $wc_price = $wc_tier['price'];
                    
                    // Allow small price differences (0.01) due to rounding
                    if ( abs( $pls_price - $wc_price ) > 0.01 ) {
                        $tiers_match = false;
                        break;
                    }
                } else {
                    // PLS tier has no matching WC variation
                    $tiers_match = false;
                    break;
                }
            }
        }

        // Determine if data matches - prioritize name, slug, categories, and tier data matching
        $data_matches = $name_matches && $slug_matches && $categories_match && $tier_count_matches && $tiers_match;

        // Determine sync state
        // v2.7.1: Never return 'update_available' - sync is always automatic
        // Just return synced state based on WooCommerce product status
        return ( 'publish' === $wc_status ) ? 'synced_active' : 'synced_inactive';
    }

    /**
     * AJAX: save product with validation + Woo sync.
     */
    public static function save_product() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        // Debug logging
        if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
            PLS_Debug::log_ajax_request( 'pls_save_product', array( 'product_id' => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0 ) );
        }

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::error( 'Insufficient permissions for save_product', array( 'user_id' => get_current_user_id() ) );
            }
            wp_send_json_error( array( 'ok' => false, 'errors' => array( array( 'field' => 'permission', 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ) ) ), 403 );
        }

        $payload = self::sanitize_product_request( $_POST );
        $errors  = self::validate_product_payload( $payload );

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array( 'ok' => false, 'errors' => $errors ), 400 );
        }

        $persisted = self::persist_product( $payload );
        if ( is_wp_error( $persisted ) ) {
            if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
                PLS_Debug::error( 'Product save failed', array( 'error' => $persisted->get_error_message(), 'payload' => $payload ) );
            }
            wp_send_json_error(
                array(
                    'ok'     => false,
                    'errors' => array(
                        array(
                            'field'   => 'save',
                            'message' => $persisted->get_error_message(),
                        ),
                    ),
                ),
                500
            );
        }

        $sync_result = self::sync_single_product( $persisted['id'] );

        
        // Handle sync result (only if sync was attempted)
        if ( null !== $sync_result && is_wp_error( $sync_result ) ) {
            if ( $persisted['created'] ) {
                self::delete_product_records( $persisted['id'] );
            } else {
                self::record_sync_status( $persisted['id'], $sync_result->get_error_message(), false );
            }

            wp_send_json_error(
                array(
                    'ok'         => false,
                    'needs_sync' => ! $persisted['created'],
                    'errors'     => array(
                        array(
                            'field'   => 'sync',
                            'message' => $sync_result->get_error_message(),
                        ),
                    ),
                ),
                500
            );
        }

        // Record sync status if sync was attempted
        if ( null !== $sync_result ) {
            self::record_sync_status( $persisted['id'], $sync_result, true );
        }
        
        $product_payload = self::format_product_payload( PLS_Repo_Base_Product::get( $persisted['id'] ), 'https://bodocibiophysics.com/label-guide/' );

        $response = array(
            'ok'            => true,
            'product'       => $product_payload,
            'sync_message'  => $sync_result ? $sync_result : __( 'Product saved. Activate to sync to WooCommerce.', 'pls-private-label-store' ),
        );

        // Debug logging
        if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
            PLS_Debug::log_ajax_response( 'pls_save_product', $response, true );
        }

        wp_send_json_success( $response );
    }

    /**
     * AJAX: delete a product + trash Woo counterpart.
     */
    public static function delete_product() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $base  = $id ? PLS_Repo_Base_Product::get( $id ) : null;

        if ( ! $base ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'pls-private-label-store' ) ), 404 );
        }

        if ( $base->wc_product_id ) {
            $wc_product = get_post( $base->wc_product_id );
            if ( $wc_product ) {
                wp_trash_post( $base->wc_product_id );
            }
        }

        self::delete_product_records( $id );

        wp_send_json_success(
            array(
                'deleted' => true,
                'id'      => $id,
            )
        );
    }

    /**
     * AJAX: sync one product to Woo.
     */
    public static function sync_product() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $base = $id ? PLS_Repo_Base_Product::get( $id ) : null;
        if ( ! $base ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'pls-private-label-store' ) ), 404 );
        }

        $result = self::sync_single_product( $id );
        if ( is_wp_error( $result ) ) {
            self::record_sync_status( $id, $result->get_error_message(), false );
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
        }

        // Record successful sync
        self::record_sync_status( $id, $result, true );
        
        // Refresh product data after sync - clear caches
        $base = PLS_Repo_Base_Product::get( $id );
        if ( $base && $base->wc_product_id ) {
            // Clear WooCommerce product cache
            if ( function_exists( 'wc_delete_product_transients' ) ) {
                wc_delete_product_transients( $base->wc_product_id );
            }
            // Clear object cache
            wp_cache_delete( $base->wc_product_id, 'posts' );
            wp_cache_delete( $base->wc_product_id, 'post_meta' );
        }
        
        $product_payload = self::format_product_payload( PLS_Repo_Base_Product::get( $id ), 'https://bodocibiophysics.com/label-guide/' );

        wp_send_json_success(
            array(
                'message' => $result,
                'product' => $product_payload,
            )
        );
    }

    /**
     * AJAX: activate product (set to live and publish in WooCommerce).
     */
    public static function activate_product() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $base = $id ? PLS_Repo_Base_Product::get( $id ) : null;
        if ( ! $base ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'pls-private-label-store' ) ), 404 );
        }

        // Update PLS status to live
        PLS_Repo_Base_Product::update( $id, array(
            'slug'          => $base->slug,
            'name'          => $base->name,
            'category_path' => $base->category_path,
            'status'        => 'live',
        ) );

        // Sync to WooCommerce with publish status
        $result = self::sync_single_product( $id );
        if ( is_wp_error( $result ) ) {
            self::record_sync_status( $id, $result->get_error_message(), false );
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
        }

        self::record_sync_status( $id, __( 'Product activated and synced.', 'pls-private-label-store' ), true );
        $product_payload = self::format_product_payload( PLS_Repo_Base_Product::get( $id ), 'https://bodocibiophysics.com/label-guide/' );

        wp_send_json_success(
            array(
                'message' => __( 'Product activated successfully.', 'pls-private-label-store' ),
                'product' => $product_payload,
            )
        );
    }

    /**
     * AJAX: deactivate product (set to draft in both PLS and WooCommerce).
     */
    public static function deactivate_product() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $base = $id ? PLS_Repo_Base_Product::get( $id ) : null;
        if ( ! $base ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'pls-private-label-store' ) ), 404 );
        }

        // Update PLS status to draft
        PLS_Repo_Base_Product::update( $id, array(
            'slug'          => $base->slug,
            'name'          => $base->name,
            'category_path' => $base->category_path,
            'status'        => 'draft',
        ) );

        // Sync to WooCommerce with draft status
        $result = self::sync_single_product( $id );
        if ( is_wp_error( $result ) ) {
            self::record_sync_status( $id, $result->get_error_message(), false );
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
        }

        self::record_sync_status( $id, __( 'Product deactivated and synced.', 'pls-private-label-store' ), true );
        $product_payload = self::format_product_payload( PLS_Repo_Base_Product::get( $id ), 'https://bodocibiophysics.com/label-guide/' );

        wp_send_json_success(
            array(
                'message' => __( 'Product deactivated successfully.', 'pls-private-label-store' ),
                'product' => $product_payload,
            )
        );
    }

    /**
     * AJAX: bulk sync all PLS products.
     */
    public static function sync_all_products() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $bases    = PLS_Repo_Base_Product::all();
        if ( empty( $bases ) ) {
            wp_send_json_success( array( 'message' => __( 'No base products to sync.', 'pls-private-label-store' ) ) );
        }
        $messages = array();

        foreach ( $bases as $base ) {
            $result = self::sync_single_product( $base->id );
            if ( is_wp_error( $result ) ) {
                self::record_sync_status( $base->id, $result->get_error_message(), false );
                $messages[] = $result->get_error_message();
            } else {
                self::record_sync_status( $base->id, $result, true );
                $messages[] = $result;
            }
        }

        wp_send_json_success( array( 'message' => implode( ' ', $messages ) ) );
    }

    /**
     * Sanitize incoming request into a normalized payload.
     *
     * @param array $data Raw request.
     * @return array
     */
    private static function sanitize_product_request( $data ) {
        $payload                  = array();
        $payload['id']            = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
        $payload['name']          = isset( $data['name'] ) ? sanitize_text_field( wp_unslash( $data['name'] ) ) : '';
        $payload['categories']    = isset( $data['categories'] ) && is_array( $data['categories'] ) ? array_map( 'absint', $data['categories'] ) : array();
        $payload['featured_image_id'] = isset( $data['featured_image_id'] ) ? absint( $data['featured_image_id'] ) : 0;

        $gallery_raw = isset( $data['gallery_ids'] ) ? sanitize_text_field( wp_unslash( $data['gallery_ids'] ) ) : '';
        $payload['gallery_ids'] = $gallery_raw ? array_map( 'absint', explode( ',', $gallery_raw ) ) : array();

        $payload['short_description'] = isset( $data['short_description'] ) ? sanitize_textarea_field( wp_unslash( $data['short_description'] ) ) : '';
        $payload['long_description']  = isset( $data['long_description'] ) ? wp_kses_post( wp_unslash( $data['long_description'] ) ) : '';
        $payload['directions_text']   = isset( $data['directions_text'] ) ? sanitize_textarea_field( wp_unslash( $data['directions_text'] ) ) : '';

        $payload['skin_types'] = isset( $data['skin_types'] ) && is_array( $data['skin_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $data['skin_types'] ) ) : array();

        $benefits_text   = isset( $data['benefits_text'] ) ? wp_unslash( $data['benefits_text'] ) : '';
        $payload['benefits'] = array();
        foreach ( preg_split( '/\r\n|\r|\n/', $benefits_text ) as $benefit_line ) {
            $clean = sanitize_text_field( $benefit_line );
            if ( '' !== $clean ) {
                $payload['benefits'][] = array(
                    'label' => $clean,
                    'icon'  => '',
                );
            }
        }

        $selected_ingredients = isset( $data['ingredient_ids'] ) && is_array( $data['ingredient_ids'] ) ? array_map( 'absint', $data['ingredient_ids'] ) : array();
        $key_ingredients      = isset( $data['key_ingredient_ids'] ) && is_array( $data['key_ingredient_ids'] ) ? array_map( 'absint', $data['key_ingredient_ids'] ) : array();

        $selected_ingredients = array_unique( array_filter( $selected_ingredients ) );
        $key_ingredients      = array_unique( array_filter( $key_ingredients ) );
        $key_ingredients      = array_values( array_intersect( $key_ingredients, $selected_ingredients ) );
        $key_ingredients      = array_slice( $key_ingredients, 0, 5 );

        $payload['ingredients'] = $selected_ingredients;
        $payload['key_ingredients'] = array();

        foreach ( $key_ingredients as $term_id ) {
            $term = get_term( $term_id );
            if ( $term && ! is_wp_error( $term ) ) {
                $payload['key_ingredients'][] = array(
                    'label'   => $term->name,
                    'icon'    => PLS_Taxonomies::icon_for_term( $term_id ),
                    'term_id' => $term_id,
                    'short_description' => sanitize_text_field( (string) get_term_meta( $term_id, 'pls_ingredient_short_desc', true ) ),
                );
            }
        }

        $payload['label_enabled']        = isset( $data['label_enabled'] ) ? 1 : 0;
        $payload['label_price_per_unit'] = isset( $data['label_price_per_unit'] ) ? round( floatval( $data['label_price_per_unit'] ), 2 ) : 0;
        $payload['label_requires_file']  = ( isset( $data['label_enabled'] ) && isset( $data['label_requires_file'] ) ) ? 1 : 0;

        $payload['pack_tiers'] = array();
        $tiers_input           = isset( $data['pack_tiers'] ) && is_array( $data['pack_tiers'] ) ? $data['pack_tiers'] : array();
        foreach ( $tiers_input as $tier_row ) {
            $units   = isset( $tier_row['units'] ) ? absint( $tier_row['units'] ) : 0;
            $price   = isset( $tier_row['price'] ) ? round( floatval( $tier_row['price'] ), 2 ) : 0;
            $enabled = isset( $tier_row['enabled'] ) ? 1 : 0;
            $sort    = isset( $tier_row['sort'] ) ? absint( $tier_row['sort'] ) : 0;

            if ( ! $units ) {
                continue;
            }

            $tier_key = 'u' . $units;
            $payload['pack_tiers'][] = array(
                'tier_key' => $tier_key,
                'units'    => $units,
                'price'    => $price,
                'enabled'  => $enabled,
                'sort'     => $sort,
            );
        }

        $payload['attributes'] = array();

        // Handle Package Type (single selection - value ID is submitted)
        $package_type_value_id = isset( $data['package_type_attr'] ) ? absint( $data['package_type_attr'] ) : 0;
        if ( $package_type_value_id ) {
            $package_type_value = PLS_Repo_Attributes::get_value( $package_type_value_id );
            if ( $package_type_value ) {
                $package_type_attr = PLS_Repo_Attributes::get_attr( $package_type_value->attribute_id );
                if ( $package_type_attr ) {
                    $payload['attributes'][] = array(
                        'attribute_id'    => $package_type_attr->id,
                        'attribute_label' => $package_type_attr->label,
                        'values'          => array(
                            array(
                                'value_id'    => $package_type_value_id,
                                'value_label' => $package_type_value->label,
                                'price'       => 0,
                            ),
                        ),
                    );
                }
            }
        }

        // Handle Package Colors (multiple checkboxes)
        $package_colors = isset( $data['package_colors'] ) && is_array( $data['package_colors'] ) ? array_map( 'absint', $data['package_colors'] ) : array();
        if ( ! empty( $package_colors ) ) {
            // Find Package Color attribute
            $package_color_attr = null;
            $all_attrs = PLS_Repo_Attributes::attrs_all();
            foreach ( $all_attrs as $attr ) {
                if ( isset( $attr->attr_key ) && ( $attr->attr_key === 'package-color' || $attr->attr_key === 'package-colour' ) ) {
                    $package_color_attr = $attr;
                    break;
                }
            }
            if ( $package_color_attr ) {
                $value_payload = array();
                foreach ( $package_colors as $value_id ) {
                    $value_obj = PLS_Repo_Attributes::get_value( $value_id );
                    if ( $value_obj ) {
                        $value_payload[] = array(
                            'value_id'    => $value_id,
                            'value_label' => $value_obj->label,
                            'price'       => 0, // Price calculated from tier_price_overrides
                        );
                    }
                }
                if ( ! empty( $value_payload ) ) {
                    $payload['attributes'][] = array(
                        'attribute_id'    => $package_color_attr->id,
                        'attribute_label' => $package_color_attr->label,
                        'values'          => $value_payload,
                    );
                }
            }
        }

        // Handle Package Caps (multiple checkboxes)
        $package_caps = isset( $data['package_caps'] ) && is_array( $data['package_caps'] ) ? array_map( 'absint', $data['package_caps'] ) : array();
        if ( ! empty( $package_caps ) ) {
            // Find Package Cap attribute
            $package_cap_attr = null;
            $all_attrs = PLS_Repo_Attributes::attrs_all();
            foreach ( $all_attrs as $attr ) {
                if ( isset( $attr->attr_key ) && $attr->attr_key === 'package-cap' ) {
                    $package_cap_attr = $attr;
                    break;
                }
            }
            if ( $package_cap_attr ) {
                $value_payload = array();
                foreach ( $package_caps as $value_id ) {
                    $value_obj = PLS_Repo_Attributes::get_value( $value_id );
                    if ( $value_obj ) {
                        $value_payload[] = array(
                            'value_id'    => $value_id,
                            'value_label' => $value_obj->label,
                            'price'       => 0, // Price calculated from tier_price_overrides
                        );
                    }
                }
                if ( ! empty( $value_payload ) ) {
                    $payload['attributes'][] = array(
                        'attribute_id'    => $package_cap_attr->id,
                        'attribute_label' => $package_cap_attr->label,
                        'values'          => $value_payload,
                    );
                }
            }
        }

        // Handle additional product options (existing logic)
        $attr_rows_input = isset( $data['attr_options'] ) && is_array( $data['attr_options'] ) ? $data['attr_options'] : array();
        foreach ( $attr_rows_input as $attr_row ) {
            $attr_id        = isset( $attr_row['attribute_id'] ) ? absint( $attr_row['attribute_id'] ) : 0;
            $attr_label_raw = isset( $attr_row['attribute_label'] ) ? sanitize_text_field( wp_unslash( $attr_row['attribute_label'] ) ) : '';
            $values_input   = isset( $attr_row['values'] ) && is_array( $attr_row['values'] ) ? $attr_row['values'] : array();

            $attr_label = $attr_label_raw;

            $value_payload = array();

            foreach ( $values_input as $value_row ) {
                $value_id    = isset( $value_row['value_id'] ) ? absint( $value_row['value_id'] ) : 0;
                $value_label = isset( $value_row['value_label'] ) ? sanitize_text_field( wp_unslash( $value_row['value_label'] ) ) : '';
                $price       = isset( $value_row['price'] ) ? round( floatval( $value_row['price'] ), 2 ) : 0;

                if ( ! $value_id && ! $value_label ) {
                    continue;
                }

                $value_payload[] = array(
                    'value_id'    => $value_id,
                    'value_label' => $value_label,
                    'price'       => $price,
                );
            }

            if ( $attr_id && $value_payload ) {
                $payload['attributes'][] = array(
                    'attribute_id'    => $attr_id,
                    'attribute_label' => $attr_label,
                    'values'          => $value_payload,
                );
            }
        }

        // Stock management fields
        $payload['manage_stock']        = isset( $data['manage_stock'] ) ? 1 : 0;
        $payload['stock_quantity']      = isset( $data['stock_quantity'] ) && '' !== $data['stock_quantity'] ? absint( $data['stock_quantity'] ) : '';
        $payload['stock_status']        = isset( $data['stock_status'] ) ? sanitize_text_field( $data['stock_status'] ) : 'instock';
        $payload['backorders_allowed']  = isset( $data['backorders_allowed'] ) ? 1 : 0;
        $payload['low_stock_threshold'] = isset( $data['low_stock_threshold'] ) && '' !== $data['low_stock_threshold'] ? absint( $data['low_stock_threshold'] ) : '';

        // Cost fields
        $payload['shipping_cost']  = isset( $data['shipping_cost'] ) && '' !== $data['shipping_cost'] ? round( floatval( $data['shipping_cost'] ), 2 ) : '';
        $payload['packaging_cost'] = isset( $data['packaging_cost'] ) && '' !== $data['packaging_cost'] ? round( floatval( $data['packaging_cost'] ), 2 ) : '';

        return $payload;
    }

    /**
     * Validate normalized payload.
     *
     * @param array $payload
     * @return array
     */
    private static function validate_product_payload( $payload ) {
        $errors = array();

        if ( '' === $payload['name'] ) {
            $errors[] = array( 'field' => 'name', 'message' => __( 'Name is required.', 'pls-private-label-store' ) );
        }

        if ( empty( $payload['categories'] ) ) {
            $errors[] = array( 'field' => 'categories', 'message' => __( 'Select at least one category.', 'pls-private-label-store' ) );
        }

        if ( ! $payload['featured_image_id'] ) {
            $errors[] = array( 'field' => 'featured_image_id', 'message' => __( 'Featured image is required.', 'pls-private-label-store' ) );
        }

        if ( empty( $payload['gallery_ids'] ) ) {
            $errors[] = array( 'field' => 'gallery_ids', 'message' => __( 'Add at least one gallery image.', 'pls-private-label-store' ) );
        }

        if ( '' === $payload['short_description'] ) {
            $errors[] = array( 'field' => 'short_description', 'message' => __( 'Short description is required.', 'pls-private-label-store' ) );
        }

        if ( '' === $payload['long_description'] ) {
            $errors[] = array( 'field' => 'long_description', 'message' => __( 'Long description is required.', 'pls-private-label-store' ) );
        }

        if ( '' === $payload['directions_text'] ) {
            $errors[] = array( 'field' => 'directions_text', 'message' => __( 'Directions are required.', 'pls-private-label-store' ) );
        }

        if ( empty( $payload['skin_types'] ) ) {
            $errors[] = array( 'field' => 'skin_types', 'message' => __( 'Select at least one skin type.', 'pls-private-label-store' ) );
        }

        if ( empty( $payload['benefits'] ) ) {
            $errors[] = array( 'field' => 'benefits', 'message' => __( 'Provide at least one benefit.', 'pls-private-label-store' ) );
        }

        if ( empty( $payload['ingredients'] ) ) {
            $errors[] = array( 'field' => 'ingredients', 'message' => __( 'Select at least one ingredient.', 'pls-private-label-store' ) );
        }

        if ( empty( $payload['pack_tiers'] ) ) {
            $errors[] = array( 'field' => 'pack_tiers', 'message' => __( 'Add at least one pack tier.', 'pls-private-label-store' ) );
        } else {
            $has_enabled = false;
            foreach ( $payload['pack_tiers'] as $tier ) {
                if ( $tier['enabled'] ) {
                    $has_enabled = true;
                }
                if ( $tier['price'] <= 0 ) {
                    $errors[] = array( 'field' => 'pack_tiers', 'message' => __( 'Pack tier prices must be greater than zero.', 'pls-private-label-store' ) );
                    break;
                }
            }
            if ( ! $has_enabled ) {
                $errors[] = array( 'field' => 'pack_tiers', 'message' => __( 'Enable at least one pack tier.', 'pls-private-label-store' ) );
            }
        }

        // Package Type is now required
        $has_package_type = false;
        foreach ( $payload['attributes'] as $attr ) {
            if ( isset( $attr['attribute_label'] ) && ( $attr['attribute_label'] === 'Package Type' || $attr['attribute_label'] === 'package-type' ) ) {
                $has_package_type = true;
                break;
            }
        }
        if ( ! $has_package_type ) {
            $errors[] = array( 'field' => 'package_type_attr', 'message' => __( 'Package Type is required.', 'pls-private-label-store' ) );
        }

        if ( empty( $payload['attributes'] ) ) {
            $errors[] = array( 'field' => 'attributes', 'message' => __( 'Add at least one attribute with values.', 'pls-private-label-store' ) );
        } else {
            foreach ( $payload['attributes'] as $attr ) {
                if ( empty( $attr['attribute_id'] ) && empty( $attr['attribute_label'] ) ) {
                    $errors[] = array( 'field' => 'attributes', 'message' => __( 'Each attribute row needs a label.', 'pls-private-label-store' ) );
                    break;
                }
                if ( empty( $attr['values'] ) ) {
                    $errors[] = array( 'field' => 'attributes', 'message' => __( 'Each attribute needs at least one value.', 'pls-private-label-store' ) );
                    break;
                }
            }
        }

        if ( $payload['label_enabled'] && $payload['label_price_per_unit'] <= 0 ) {
            $errors[] = array( 'field' => 'label_price_per_unit', 'message' => __( 'Label price per unit must be greater than zero when labels are enabled.', 'pls-private-label-store' ) );
        }

        return $errors;
    }

    /**
     * Persist product rows.
     *
     * @param array $payload
     * @return array|WP_Error
     */
    private static function persist_product( $payload ) {
        $base_id   = $payload['id'];
        $created   = false;
        $slug      = sanitize_title( $payload['name'] );
        $status    = 'draft';
        $categories = array_filter( $payload['categories'] );
        $category_path = $categories ? implode( ',', $categories ) : '';

        $data = array(
            'name'          => $payload['name'],
            'slug'          => $slug,
            'status'        => $status,
            'category_path' => $category_path,
        );

        if ( $base_id ) {
            PLS_Repo_Base_Product::update( $base_id, $data );
        } else {
            $base_id = PLS_Repo_Base_Product::insert( $data );
            $created = true;
        }

        if ( ! $base_id ) {
            return new WP_Error( 'pls_save_failed', __( 'Unable to save product.', 'pls-private-label-store' ) );
        }

        // Pack tiers.
        $seen_keys = array();
        foreach ( $payload['pack_tiers'] as $tier_row ) {
            $tier_key = $tier_row['tier_key'] ? $tier_row['tier_key'] : sanitize_key( wp_generate_uuid4() );
            $seen_keys[] = $tier_key;
            PLS_Repo_Pack_Tier::upsert( $base_id, $tier_key, $tier_row['units'], $tier_row['price'], $tier_row['enabled'], $tier_row['sort'] );
        }

        $attr_rows = array();
        foreach ( $payload['attributes'] as $attr_row ) {
            $attr_id    = isset( $attr_row['attribute_id'] ) ? absint( $attr_row['attribute_id'] ) : 0;
            $attr_label = isset( $attr_row['attribute_label'] ) ? $attr_row['attribute_label'] : '';
            if ( ! $attr_id && $attr_label ) {
                $attr_id = PLS_Repo_Attributes::insert_attr(
                    array(
                        'label'        => $attr_label,
                        'is_variation' => 1,
                    )
                );
            }

            $value_payload = array();
            $values_input  = isset( $attr_row['values'] ) ? $attr_row['values'] : array();
            foreach ( $values_input as $value_row ) {
                $value_id    = isset( $value_row['value_id'] ) ? absint( $value_row['value_id'] ) : 0;
                $value_label = isset( $value_row['value_label'] ) ? $value_row['value_label'] : '';
                $price       = isset( $value_row['price'] ) ? floatval( $value_row['price'] ) : 0;

                if ( ! $value_id && $attr_id && $value_label ) {
                    $value_id = PLS_Repo_Attributes::insert_value(
                        array(
                            'attribute_id' => $attr_id,
                            'label'        => $value_label,
                        )
                    );
                }

                if ( ! $value_id && ! $value_label ) {
                    continue;
                }

                if ( $value_id ) {
                    $value_row = PLS_Repo_Attributes::get_value( $value_id );
                    if ( $value_row && $value_row->term_id ) {
                        update_term_meta( $value_row->term_id, '_pls_default_price_impact', $price );
                    }
                }

                $value_payload[] = array(
                    'value_id'    => $value_id,
                    'value_label' => $value_label,
                    'price'       => $price,
                );
            }

            if ( $attr_id && $value_payload ) {
                $attr_rows[] = array(
                    'attribute_id'    => $attr_id,
                    'attribute_label' => $attr_label,
                    'values'          => $value_payload,
                );
            }
        }

        // Profile data.
        $profile_data = array(
            'short_description'    => $payload['short_description'],
            'long_description'     => $payload['long_description'],
            'featured_image_id'    => $payload['featured_image_id'],
            'gallery_ids'          => $payload['gallery_ids'],
            'directions_text'      => $payload['directions_text'],
            'ingredients_list'     => $payload['ingredients'] ? implode( ',', $payload['ingredients'] ) : '',
            'label_enabled'        => $payload['label_enabled'],
            'label_price_per_unit' => $payload['label_price_per_unit'],
            'label_requires_file'  => $payload['label_requires_file'],
            'label_helper_text'    => '',
            'label_guide_url'      => 'https://bodocibiophysics.com/label-guide/',
            'basics_json'          => $attr_rows,
            'skin_types_json'      => array_map(
                function( $label ) {
                    return array(
                        'label' => $label,
                        'icon'  => '',
                    );
                },
                $payload['skin_types']
            ),
            'benefits_json'        => $payload['benefits'],
            'key_ingredients_json' => $payload['key_ingredients'],
        );

        PLS_Repo_Product_Profile::upsert( $base_id, $profile_data );

        // Stock management fields
        PLS_Repo_Base_Product::update_stock( $base_id, array(
            'manage_stock'        => $payload['manage_stock'],
            'stock_quantity'      => $payload['stock_quantity'],
            'stock_status'        => $payload['stock_status'],
            'backorders_allowed'  => $payload['backorders_allowed'],
            'low_stock_threshold' => $payload['low_stock_threshold'],
        ) );

        // Cost fields
        PLS_Repo_Base_Product::update_costs( $base_id, array(
            'shipping_cost'  => $payload['shipping_cost'],
            'packaging_cost' => $payload['packaging_cost'],
        ) );

        return array(
            'id'      => $base_id,
            'created' => $created,
        );
    }

    /**
     * Run WooCommerce sync for a product.
     *
     * @param int $base_id
     * @return string|WP_Error
     */
    private static function sync_single_product( $base_id ) {
        // Always verify against WooCommerce first (backend sync - WooCommerce is source of truth)
        $base = PLS_Repo_Base_Product::get( $base_id );
        if ( $base && $base->wc_product_id ) {
            $wc_product = wc_get_product( $base->wc_product_id );
            if ( ! $wc_product ) {
                // Product doesn't exist in WooCommerce - clear reference
                error_log( '[PLS Sync] WooCommerce product ' . $base->wc_product_id . ' not found, clearing reference for PLS product ' . $base_id );
                PLS_Repo_Base_Product::set_wc_product_id( $base_id, null );
            }
        }
        
        $result = PLS_WC_Sync::sync_base_product_to_wc( $base_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ( ! is_string( $result ) ) {
            return new WP_Error( 'pls_sync_unknown', __( 'Unexpected sync response.', 'pls-private-label-store' ) );
        }

        return $result;
    }

    /**
     * Delete product + related rows from PLS tables.
     *
     * @param int $base_id
     */
    public static function delete_product_records( $base_id ) {
        PLS_Repo_Product_Profile::delete_for_base( $base_id );
        PLS_Repo_Pack_Tier::delete_for_base( $base_id );
        PLS_Repo_Base_Product::delete( $base_id );
        delete_option( self::sync_status_key( $base_id ) );
    }

    /**
     * AJAX: Update tier rules for an attribute value.
     */
    public static function update_attribute_tier_rules() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $value_id     = isset( $_POST['value_id'] ) ? absint( $_POST['value_id'] ) : 0;
        $min_tier     = isset( $_POST['min_tier_level'] ) ? absint( $_POST['min_tier_level'] ) : 1;
        $price_overrides = isset( $_POST['tier_price_overrides'] ) && is_array( $_POST['tier_price_overrides'] ) ? $_POST['tier_price_overrides'] : null;

        if ( ! $value_id ) {
            wp_send_json_error( array( 'message' => __( 'Value ID required.', 'pls-private-label-store' ) ), 400 );
        }

        // Sanitize price overrides
        $sanitized_overrides = null;
        if ( is_array( $price_overrides ) ) {
            $sanitized_overrides = array();
            foreach ( $price_overrides as $tier => $price ) {
                $tier = absint( $tier );
                if ( $tier >= 1 && $tier <= 5 ) {
                    $sanitized_overrides[ $tier ] = round( floatval( $price ), 2 );
                }
            }
        }

        $result = PLS_Repo_Attributes::update_value_tier_rules( $value_id, $min_tier, $sanitized_overrides );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => __( 'Failed to update tier rules.', 'pls-private-label-store' ) ), 500 );
        }

        $value = PLS_Repo_Attributes::get_value( $value_id );
        wp_send_json_success(
            array(
                'value' => array(
                    'id'            => $value->id,
                    'min_tier_level' => $value->min_tier_level,
                    'tier_price_overrides' => $value->tier_price_overrides ? json_decode( $value->tier_price_overrides, true ) : null,
                ),
            )
        );
    }

    /**
     * AJAX: Set which attribute is designated as Pack Tier.
     */
    public static function set_pack_tier_attribute() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $attribute_id = isset( $_POST['attribute_id'] ) ? absint( $_POST['attribute_id'] ) : 0;

        if ( ! $attribute_id ) {
            wp_send_json_error( array( 'message' => __( 'Attribute ID required.', 'pls-private-label-store' ) ), 400 );
        }

        update_option( 'pls_pack_tier_attribute_id', $attribute_id );

        wp_send_json_success(
            array(
                'attribute_id' => $attribute_id,
                'message'      => __( 'Pack Tier attribute set.', 'pls-private-label-store' ),
            )
        );
    }

    /**
     * AJAX: Get tier-specific pricing for an attribute value.
     */
    public static function get_tier_pricing() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $value_id  = isset( $_POST['value_id'] ) ? absint( $_POST['value_id'] ) : 0;
        $tier_level = isset( $_POST['tier_level'] ) ? absint( $_POST['tier_level'] ) : 1;

        if ( ! $value_id ) {
            wp_send_json_error( array( 'message' => __( 'Value ID required.', 'pls-private-label-store' ) ), 400 );
        }

        require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
        $price = PLS_Tier_Rules::calculate_price( $value_id, $tier_level );

        wp_send_json_success(
            array(
                'value_id'  => $value_id,
                'tier_level' => $tier_level,
                'price'     => $price,
            )
        );
    }

    /**
     * Delete attribute via AJAX.
     */
    public static function delete_attribute() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $attribute_id = isset( $_POST['attribute_id'] ) ? absint( $_POST['attribute_id'] ) : 0;

        if ( ! $attribute_id ) {
            wp_send_json_error( array( 'message' => __( 'Attribute ID required.', 'pls-private-label-store' ) ), 400 );
        }

        // Check if it's primary attribute - don't allow deletion
        $attr = PLS_Repo_Attributes::get_attr( $attribute_id );
        if ( $attr && ! empty( $attr->is_primary ) ) {
            wp_send_json_error( array( 'message' => __( 'Cannot delete primary attribute (Pack Tier).', 'pls-private-label-store' ) ), 400 );
        }

        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );
        
        // Delete all values first
        $values_table = PLS_Repositories::table( 'attribute_value' );
        $values = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$values_table} WHERE attribute_id = %d", $attribute_id ) );
        foreach ( $values as $value_id ) {
            self::delete_attribute_value_internal( $value_id );
        }

        // Delete attribute
        $deleted = $wpdb->delete( $table, array( 'id' => $attribute_id ), array( '%d' ) );

        if ( $deleted ) {
            wp_send_json_success( array( 'message' => __( 'Attribute deleted successfully.', 'pls-private-label-store' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete attribute.', 'pls-private-label-store' ) ), 400 );
        }
    }

    /**
     * Delete attribute value via AJAX.
     */
    public static function delete_attribute_value() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $value_id = isset( $_POST['value_id'] ) ? absint( $_POST['value_id'] ) : 0;

        if ( ! $value_id ) {
            wp_send_json_error( array( 'message' => __( 'Value ID required.', 'pls-private-label-store' ) ), 400 );
        }

        $deleted = self::delete_attribute_value_internal( $value_id );

        if ( $deleted ) {
            wp_send_json_success( array( 'message' => __( 'Value deleted successfully.', 'pls-private-label-store' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete value.', 'pls-private-label-store' ) ), 400 );
        }
    }

    /**
     * Internal method to delete attribute value.
     */
    private static function delete_attribute_value_internal( $value_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );
        
        $value = PLS_Repo_Attributes::get_value( $value_id );
        if ( $value && $value->term_id ) {
            // Delete term meta
            delete_term_meta( $value->term_id, '_pls_default_price_impact' );
            delete_term_meta( $value->term_id, '_pls_tier_level' );
            delete_term_meta( $value->term_id, '_pls_default_units' );
            delete_term_meta( $value->term_id, '_pls_default_price_per_unit' );
            
            // Delete term if it exists - find the taxonomy first
            $term = get_term( $value->term_id );
            if ( $term && ! is_wp_error( $term ) ) {
                // Get attribute to find taxonomy
                $attr = PLS_Repo_Attributes::get_attr( $value->attribute_id );
                if ( $attr && $attr->wc_attribute_id ) {
                    $taxonomy = wc_attribute_taxonomy_name_by_id( $attr->wc_attribute_id );
                    if ( $taxonomy ) {
                        wp_delete_term( $value->term_id, $taxonomy );
                    }
                }
            }
        }

        // Delete swatch
        $swatch_table = PLS_Repositories::table( 'swatch' );
        $wpdb->delete( $swatch_table, array( 'attribute_value_id' => $value_id ), array( '%d' ) );

        // Delete value
        return $wpdb->delete( $table, array( 'id' => $value_id ), array( '%d' ) );
    }

    /**
     * Update attribute value via AJAX.
     */
    public static function update_attribute_value() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $value_id = isset( $_POST['value_id'] ) ? absint( $_POST['value_id'] ) : 0;
        $label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
        $min_tier = isset( $_POST['min_tier_level'] ) ? absint( $_POST['min_tier_level'] ) : 1;
        $price = isset( $_POST['price'] ) ? round( floatval( $_POST['price'] ), 2 ) : 0;

        if ( ! $value_id || '' === $label ) {
            wp_send_json_error( array( 'message' => __( 'Value ID and label are required.', 'pls-private-label-store' ) ), 400 );
        }

        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );
        
        // Update label
        $wpdb->update(
            $table,
            array( 'label' => $label ),
            array( 'id' => $value_id ),
            array( '%s' ),
            array( '%d' )
        );

        // Update tier rules
        PLS_Repo_Attributes::update_value_tier_rules( $value_id, $min_tier, null );

        // Update price
        $value = PLS_Repo_Attributes::get_value( $value_id );
        if ( $value && $value->term_id ) {
            if ( $price > 0 ) {
                update_term_meta( $value->term_id, '_pls_default_price_impact', $price );
            } else {
                delete_term_meta( $value->term_id, '_pls_default_price_impact' );
            }
        }

        wp_send_json_success( array(
            'message' => __( 'Value updated successfully.', 'pls-private-label-store' ),
            'value' => array(
                'id' => $value_id,
                'label' => $label,
                'min_tier_level' => $min_tier,
                'price' => $price,
            ),
        ) );
    }

    /**
     * Update pack tier defaults via AJAX.
     */
    public static function update_pack_tier_defaults() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $updates = isset( $_POST['tier_prices'] ) && is_array( $_POST['tier_prices'] ) ? $_POST['tier_prices'] : array();

        foreach ( $updates as $value_id => $data ) {
            $value_id = absint( $value_id );
            if ( ! $value_id ) {
                continue;
            }

            $price = isset( $data['price'] ) ? round( floatval( $data['price'] ), 2 ) : 0;
            $units = isset( $data['units'] ) ? absint( $data['units'] ) : 0;

            $value = PLS_Repo_Attributes::get_value( $value_id );
            if ( $value && $value->term_id ) {
                if ( $price > 0 ) {
                    update_term_meta( $value->term_id, '_pls_default_price_per_unit', $price );
                }
                if ( $units > 0 ) {
                    update_term_meta( $value->term_id, '_pls_default_units', $units );
                }
            }
        }

        // v2.6.0: Trigger auto-sync for all products when tier defaults change
        do_action( 'pls_pack_tier_updated' );

        wp_send_json_success( array( 'message' => __( 'Pack tier defaults updated and products synced.', 'pls-private-label-store' ) ) );
    }

    /**
     * Update label application pricing via AJAX.
     */
    public static function update_label_pricing() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_ATTRS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $label_price = isset( $_POST['label_price_tier_1_2'] ) ? round( floatval( $_POST['label_price_tier_1_2'] ), 2 ) : 0.50;
        if ( $label_price < 0 ) {
            $label_price = 0;
        }

        update_option( 'pls_label_price_tier_1_2', $label_price );

        wp_send_json_success( array( 'message' => __( 'Label pricing updated successfully.', 'pls-private-label-store' ) ) );
    }

    /**
     * Get product options data for AJAX refresh.
     */
    public static function get_product_options_data() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $primary_attr = PLS_Repo_Attributes::get_primary_attribute();
        $product_options = PLS_Repo_Attributes::get_product_options();
        $ingredient_attrs = PLS_Repo_Attributes::get_ingredient_attributes();
        $ingredients = get_terms( array( 'taxonomy' => 'pls_ingredient', 'hide_empty' => false ) );
        if ( is_wp_error( $ingredients ) ) {
            $ingredients = array();
        }

        require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';

        // Format pack tier data
        $pack_tiers = array();
        if ( $primary_attr ) {
            $tier_values = PLS_Repo_Attributes::values_for_attr( $primary_attr->id );
            foreach ( $tier_values as $tier_value ) {
                $tier_level = PLS_Tier_Rules::get_tier_level_from_value( $tier_value->id );
                $default_units = PLS_Tier_Rules::get_default_units_for_tier( $tier_value->id );
                $default_price = PLS_Tier_Rules::get_default_price_per_unit( $tier_value->id );
                $pack_tiers[] = array(
                    'id' => $tier_value->id,
                    'label' => $tier_value->label,
                    'tier_level' => $tier_level,
                    'units' => $default_units,
                    'price' => $default_price,
                );
            }
        }

        // Format product options
        $options_data = array();
        foreach ( $product_options as $option ) {
            $values = PLS_Repo_Attributes::values_for_attr( $option->id );
            $values_data = array();
            foreach ( $values as $value ) {
                $price_meta = $value->term_id ? get_term_meta( $value->term_id, '_pls_default_price_impact', true ) : '';
                $values_data[] = array(
                    'id' => $value->id,
                    'label' => $value->label,
                    'min_tier_level' => $value->min_tier_level,
                    'price' => '' !== $price_meta ? floatval( $price_meta ) : 0,
                );
            }
            $options_data[] = array(
                'id' => $option->id,
                'label' => $option->label,
                'values' => $values_data,
            );
        }

        wp_send_json_success( array(
            'pack_tiers' => $pack_tiers,
            'product_options' => $options_data,
            'ingredients' => array_map( function( $ing ) {
                return array(
                    'term_id' => $ing->term_id,
                    'name' => $ing->name,
                    'icon' => PLS_Taxonomies::icon_for_term( $ing->term_id ),
                );
            }, $ingredients ),
        ) );
    }

    /**
     * AJAX: Generate live preview HTML for unsaved product data.
     */
    public static function preview_product() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $payload = self::sanitize_product_request( $_POST );

        // Generate preview HTML without saving
        ob_start();
        require_once PLS_PLS_DIR . 'includes/admin/preview-renderer.php';
        PLS_Preview_Renderer::render( $payload );
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * AJAX: Handle custom product request - creates WooCommerce draft order.
     */
    public static function custom_product_request() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        if ( ! function_exists( 'wc_create_order' ) ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'pls-private-label-store' ) ), 400 );
        }

        $product_category = isset( $_POST['product_category'] ) ? absint( $_POST['product_category'] ) : 0;
        $message_text     = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
        $contact_name     = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
        $contact_email    = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';

        if ( empty( $product_category ) || empty( $message_text ) || empty( $contact_name ) || empty( $contact_email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'pls-private-label-store' ) ), 400 );
        }

        // Get category name
        $category_name = __( 'Other', 'pls-private-label-store' );
        if ( $product_category && $product_category !== 'other' ) {
            $category_term = get_term( $product_category, 'product_cat' );
            if ( $category_term && ! is_wp_error( $category_term ) ) {
                $category_name = $category_term->name;
            }
        }

        // Create WooCommerce draft order
        $order = wc_create_order( array( 'status' => 'pending' ) );

        if ( is_wp_error( $order ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to create order.', 'pls-private-label-store' ) ), 500 );
        }

        // Set order notes
        $order_note = sprintf(
            __( 'Custom Product Request - Category: %s\n\nFrom: %s (%s)\n\nMessage:\n%s', 'pls-private-label-store' ),
            $category_name,
            $contact_name,
            $contact_email,
            $message_text
        );
        $order->add_order_note( $order_note );

        // Set customer note
        $order->set_customer_note( $message_text );

        // Add custom meta
        $order->update_meta_data( '_pls_custom_request', 1 );
        $order->update_meta_data( '_pls_product_category', $product_category );
        $order->update_meta_data( '_pls_contact_name', $contact_name );
        $order->update_meta_data( '_pls_contact_email', $contact_email );
        $order->save();

        // Send notification email
        $admin_email = get_option( 'admin_email' );
        $order_url   = admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' );
        $subject     = sprintf( __( '[%s] New Custom Product Request', 'pls-private-label-store' ), get_bloginfo( 'name' ) );
        $email_message = sprintf(
            __( "A new custom product request has been submitted.\n\nCategory: %s\n\nFrom: %s\nEmail: %s\n\nMessage:\n%s\n\nView order: %s", 'pls-private-label-store' ),
            $category_name,
            $contact_name,
            $contact_email,
            $message_text,
            $order_url
        );

        wp_mail( $admin_email, $subject, $email_message );

        wp_send_json_success(
            array(
                'message'  => __( 'Your request has been submitted successfully. We will contact you soon.', 'pls-private-label-store' ),
                'order_id' => $order->get_id(),
                'order_url' => $order_url,
            )
        );
    }

    /**
     * Update custom order status.
     */
    public static function update_custom_order_status() {
        check_ajax_referer( 'pls_custom_orders_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $status   = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

        if ( ! $order_id || ! $status ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data.', 'pls-private-label-store' ) ) );
        }

        $valid_statuses = array( 'new_lead', 'sampling', 'production', 'on_hold', 'done', 'cancelled' );
        if ( ! in_array( $status, $valid_statuses, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid status.', 'pls-private-label-store' ) ) );
        }

        $result = PLS_Repo_Custom_Order::update_status( $order_id, $status );

        // Auto-calculate commission when status changes to 'done'
        if ( $result && 'done' === $status ) {
            $order = PLS_Repo_Custom_Order::get( $order_id );
            if ( $order && $order->total_value && ! $order->nikola_commission_amount ) {
                $final_value = floatval( $order->total_value );
                $commission_rate = get_option( 'pls_commission_rates', array() );
                $custom_order_config = isset( $commission_rate['custom_order'] ) ? $commission_rate['custom_order'] : array();
                
                if ( empty( $custom_order_config ) && isset( $commission_rate['custom_order_percent'] ) ) {
                    // Migrate old single rate
                    $old_rate = floatval( $commission_rate['custom_order_percent'] );
                    $custom_order_config = array(
                        'threshold' => 100000.00,
                        'rate_below' => $old_rate,
                        'rate_above' => $old_rate,
                    );
                }
                
                $threshold = isset( $custom_order_config['threshold'] ) ? floatval( $custom_order_config['threshold'] ) : 100000.00;
                $rate_below = isset( $custom_order_config['rate_below'] ) ? floatval( $custom_order_config['rate_below'] ) : 3.00;
                $rate_above = isset( $custom_order_config['rate_above'] ) ? floatval( $custom_order_config['rate_above'] ) : 5.00;
                
                $rate_to_use = ( $final_value >= $threshold ) ? $rate_above : $rate_below;
                $calculated_commission = $final_value * ( $rate_to_use / 100 );
                
                // Update order with calculated commission
                PLS_Repo_Custom_Order::update_financials(
                    $order_id,
                    $order->production_cost,
                    $order->final_value,
                    $rate_to_use,
                    $calculated_commission
                );
            }
        }

        if ( $result ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update status.', 'pls-private-label-store' ) ) );
        }
    }

    /**
     * Get custom order details for modal.
     */
    public static function get_custom_order_details() {
        check_ajax_referer( 'pls_custom_orders_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'pls-private-label-store' ) ) );
        }

        $order = PLS_Repo_Custom_Order::get( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'pls-private-label-store' ) ) );
        }

        $category_name = '';
        if ( $order->category_id ) {
            $category = get_term( $order->category_id, 'product_cat' );
            if ( $category && ! is_wp_error( $category ) ) {
                $category_name = $category->name;
            }
        }

        $commission_rate = get_option( 'pls_commission_rates', array() );
        
        // Get tiered custom order commission rates
        $custom_order_config = isset( $commission_rate['custom_order'] ) ? $commission_rate['custom_order'] : array();
        if ( empty( $custom_order_config ) && isset( $commission_rate['custom_order_percent'] ) ) {
            // Migrate old single rate
            $old_rate = floatval( $commission_rate['custom_order_percent'] );
            $custom_order_config = array(
                'threshold' => 100000.00,
                'rate_below' => $old_rate,
                'rate_above' => $old_rate,
            );
        }
        $custom_order_threshold = isset( $custom_order_config['threshold'] ) ? floatval( $custom_order_config['threshold'] ) : 100000.00;
        $custom_order_rate_below = isset( $custom_order_config['rate_below'] ) ? floatval( $custom_order_config['rate_below'] ) : 3.00;
        $custom_order_rate_above = isset( $custom_order_config['rate_above'] ) ? floatval( $custom_order_config['rate_above'] ) : 5.00;

        // Define stages for navigation
        $stages = array( 'new_lead', 'sampling', 'production', 'on_hold', 'done' );
        $stage_labels = array(
            'new_lead'   => __( 'New Lead', 'pls-private-label-store' ),
            'sampling'   => __( 'Sampling', 'pls-private-label-store' ),
            'production' => __( 'Production', 'pls-private-label-store' ),
            'on_hold'    => __( 'On-hold', 'pls-private-label-store' ),
            'done'       => __( 'Done', 'pls-private-label-store' ),
        );
        $current_stage_index = array_search( $order->status, $stages, true );
        $prev_stage = ( $current_stage_index > 0 ) ? $stages[ $current_stage_index - 1 ] : null;
        $next_stage = ( $current_stage_index < count( $stages ) - 1 ) ? $stages[ $current_stage_index + 1 ] : null;

        // Get categories for dropdown
        $categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
        if ( is_wp_error( $categories ) ) {
            $categories = array();
        }

        ob_start();
        ?>
        <div class="pls-order-detail" data-order-id="<?php echo esc_attr( $order->id ); ?>">
            <!-- Quick Stage Navigation -->
            <div class="pls-stage-nav" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--pls-gray-100); border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <?php if ( $prev_stage ) : ?>
                        <button type="button" class="button pls-stage-change" data-order-id="<?php echo esc_attr( $order->id ); ?>" data-stage="<?php echo esc_attr( $prev_stage ); ?>">
                            ← <?php echo esc_html( $stage_labels[ $prev_stage ] ); ?>
                        </button>
                    <?php else : ?>
                        <button type="button" class="button" disabled>← <?php esc_html_e( 'Start', 'pls-private-label-store' ); ?></button>
                    <?php endif; ?>
                </div>
                <div style="font-weight: 600; color: var(--pls-accent);">
                    <?php echo esc_html( $stage_labels[ $order->status ] ); ?>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <?php if ( $next_stage ) : ?>
                        <button type="button" class="button button-primary pls-stage-change" data-order-id="<?php echo esc_attr( $order->id ); ?>" data-stage="<?php echo esc_attr( $next_stage ); ?>">
                            <?php echo esc_html( $stage_labels[ $next_stage ] ); ?> →
                        </button>
                    <?php else : ?>
                        <button type="button" class="button button-primary" disabled><?php esc_html_e( 'Complete', 'pls-private-label-store' ); ?> ✓</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pls-order-detail__section">
                <h3><?php esc_html_e( 'Contact Information', 'pls-private-label-store' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="pls-edit-contact-name"><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></label></th>
                        <td><input type="text" id="pls-edit-contact-name" class="regular-text" value="<?php echo esc_attr( $order->contact_name ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="pls-edit-contact-email"><?php esc_html_e( 'Email', 'pls-private-label-store' ); ?></label></th>
                        <td><input type="email" id="pls-edit-contact-email" class="regular-text" value="<?php echo esc_attr( $order->contact_email ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="pls-edit-contact-phone"><?php esc_html_e( 'Phone', 'pls-private-label-store' ); ?></label></th>
                        <td><input type="text" id="pls-edit-contact-phone" class="regular-text" value="<?php echo esc_attr( $order->contact_phone ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="pls-edit-company-name"><?php esc_html_e( 'Company', 'pls-private-label-store' ); ?></label></th>
                        <td><input type="text" id="pls-edit-company-name" class="regular-text" value="<?php echo esc_attr( $order->company_name ); ?>" /></td>
                    </tr>
                </table>
            </div>

            <div class="pls-order-detail__section">
                <h3><?php esc_html_e( 'Order Details', 'pls-private-label-store' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="pls-edit-category"><?php esc_html_e( 'Category', 'pls-private-label-store' ); ?></label></th>
                        <td>
                            <select id="pls-edit-category" class="regular-text">
                                <option value=""><?php esc_html_e( 'Select category...', 'pls-private-label-store' ); ?></option>
                                <?php foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $order->category_id, $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pls-edit-quantity"><?php esc_html_e( 'Quantity Needed', 'pls-private-label-store' ); ?></label></th>
                        <td><input type="number" id="pls-edit-quantity" class="regular-text" min="1" value="<?php echo esc_attr( $order->quantity_needed ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="pls-edit-budget"><?php esc_html_e( 'Budget ($)', 'pls-private-label-store' ); ?></label></th>
                        <td><input type="number" id="pls-edit-budget" class="regular-text" min="0" step="0.01" value="<?php echo esc_attr( $order->budget ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="pls-edit-timeline"><?php esc_html_e( 'Timeline', 'pls-private-label-store' ); ?></label></th>
                        <td><input type="text" id="pls-edit-timeline" class="regular-text" value="<?php echo esc_attr( $order->timeline ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="pls-order-status"><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?></label></th>
                        <td>
                            <select id="pls-order-status" class="regular-text">
                                <?php foreach ( $stage_labels as $stage_key => $stage_label ) : ?>
                                    <option value="<?php echo esc_attr( $stage_key ); ?>" <?php selected( $order->status, $stage_key ); ?>><?php echo esc_html( $stage_label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php if ( 'done' === $order->status ) : ?>
                        <tr>
                            <th><?php esc_html_e( 'Commission Confirmed', 'pls-private-label-store' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="pls-commission-confirmed" value="1" <?php checked( $order->commission_confirmed, 1 ); ?> />
                                    <?php esc_html_e( 'Mark commission as paid (order is complete and payment received)', 'pls-private-label-store' ); ?>
                                </label>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th><label for="pls-edit-message"><?php esc_html_e( 'Message / Notes', 'pls-private-label-store' ); ?></label></th>
                        <td><textarea id="pls-edit-message" rows="4" class="large-text"><?php echo esc_textarea( $order->message ); ?></textarea></td>
                    </tr>
                </table>
            </div>

            <div class="pls-order-detail__section">
                <h3><?php esc_html_e( 'Financial Information', 'pls-private-label-store' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="pls-order-production-cost"><?php esc_html_e( 'Production Cost', 'pls-private-label-store' ); ?></label></th>
                        <td>
                            <input type="number" step="0.01" id="pls-order-production-cost" class="regular-text" 
                                   value="<?php echo esc_attr( $order->production_cost ?: '' ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pls-order-total-value"><?php esc_html_e( 'Final Value', 'pls-private-label-store' ); ?></label></th>
                        <td>
                            <input type="number" step="0.01" id="pls-order-total-value" class="regular-text" 
                                   value="<?php echo esc_attr( $order->total_value ?: '' ); ?>" />
                            <?php
                            // Calculate commission rate based on final value (stored in total_value)
                            $final_value = $order->total_value ? floatval( $order->total_value ) : 0;
                            $commission_rate_to_use = ( $final_value >= $custom_order_threshold ) ? $custom_order_rate_above : $custom_order_rate_below;
                            $commission_amount_calc = $final_value > 0 ? ( $final_value * ( $commission_rate_to_use / 100 ) ) : 0;
                            ?>
                            <p class="description">
                                <?php 
                                if ( $final_value > 0 ) {
                                    printf( 
                                        esc_html__( 'Commission rate: %s%% (%s threshold: %s)', 'pls-private-label-store' ), 
                                        number_format( $commission_rate_to_use, 2 ),
                                        ( $final_value >= $custom_order_threshold ) ? esc_html__( 'above', 'pls-private-label-store' ) : esc_html__( 'below', 'pls-private-label-store' ),
                                        wc_price( $custom_order_threshold )
                                    );
                                } else {
                                    esc_html_e( 'Enter final value to see commission calculation.', 'pls-private-label-store' );
                                }
                                ?>
                            </p>
                            <?php if ( $final_value > 0 ) : ?>
                                <p class="description" style="margin-top: 4px; font-weight: 600; color: var(--pls-accent);">
                                    <?php printf( esc_html__( 'Calculated commission: %s', 'pls-private-label-store' ), wc_price( $commission_amount_calc ) ); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ( $order->nikola_commission_amount ) : ?>
                        <tr>
                            <th><?php esc_html_e( 'Nikola Commission', 'pls-private-label-store' ); ?></th>
                            <td><strong><?php echo wc_price( $order->nikola_commission_amount ); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php esc_html_e( 'Invoiced', 'pls-private-label-store' ); ?></th>
                        <td>
                            <?php if ( $order->invoiced_at ) : ?>
                                <span class="pls-status-badge pls-status-success"><?php esc_html_e( 'Yes', 'pls-private-label-store' ); ?></span>
                                <span class="description"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->invoiced_at ) ) ); ?></span>
                            <?php else : ?>
                                <button type="button" class="button button-small pls-mark-invoiced" data-order-id="<?php echo esc_attr( $order->id ); ?>">
                                    <?php esc_html_e( 'Mark as Invoiced', 'pls-private-label-store' ); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Paid', 'pls-private-label-store' ); ?></th>
                        <td>
                            <?php if ( $order->paid_at ) : ?>
                                <span class="pls-status-badge pls-status-success"><?php esc_html_e( 'Yes', 'pls-private-label-store' ); ?></span>
                                <span class="description"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->paid_at ) ) ); ?></span>
                            <?php else : ?>
                                <button type="button" class="button button-small pls-mark-paid" data-order-id="<?php echo esc_attr( $order->id ); ?>">
                                    <?php esc_html_e( 'Mark as Paid', 'pls-private-label-store' ); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Save All Changes Button -->
            <div class="pls-order-detail__actions" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--pls-gray-200); display: flex; justify-content: space-between; align-items: center;">
                <span class="description"><?php printf( esc_html__( 'Created: %s', 'pls-private-label-store' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->created_at ) ) ); ?></span>
                <button type="button" class="button button-primary button-large" id="pls-save-order-all" data-order-id="<?php echo esc_attr( $order->id ); ?>">
                    <?php esc_html_e( 'Save All Changes', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Update custom order (full edit).
     */
    public static function update_custom_order() {
        check_ajax_referer( 'pls_custom_orders_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'pls-private-label-store' ) ) );
        }

        $order = PLS_Repo_Custom_Order::get( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'pls-private-label-store' ) ) );
        }

        // Build update data
        $data = array();

        // Contact info
        if ( isset( $_POST['contact_name'] ) ) {
            $data['contact_name'] = sanitize_text_field( wp_unslash( $_POST['contact_name'] ) );
        }
        if ( isset( $_POST['contact_email'] ) ) {
            $data['contact_email'] = sanitize_email( wp_unslash( $_POST['contact_email'] ) );
        }
        if ( isset( $_POST['contact_phone'] ) ) {
            $data['contact_phone'] = sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) );
        }
        if ( isset( $_POST['company_name'] ) ) {
            $data['company_name'] = sanitize_text_field( wp_unslash( $_POST['company_name'] ) );
        }

        // Order details
        if ( isset( $_POST['status'] ) ) {
            $data['status'] = sanitize_text_field( wp_unslash( $_POST['status'] ) );
        }
        if ( isset( $_POST['category_id'] ) ) {
            $data['category_id'] = absint( $_POST['category_id'] ) ?: null;
        }
        if ( isset( $_POST['quantity_needed'] ) ) {
            $data['quantity_needed'] = absint( $_POST['quantity_needed'] ) ?: null;
        }
        if ( isset( $_POST['budget'] ) ) {
            $data['budget'] = ! empty( $_POST['budget'] ) ? floatval( $_POST['budget'] ) : null;
        }
        if ( isset( $_POST['timeline'] ) ) {
            $data['timeline'] = sanitize_text_field( wp_unslash( $_POST['timeline'] ) );
        }
        if ( isset( $_POST['message'] ) ) {
            $data['message'] = sanitize_textarea_field( wp_unslash( $_POST['message'] ) );
        }

        // Financial info
        if ( isset( $_POST['production_cost'] ) ) {
            $data['production_cost'] = ! empty( $_POST['production_cost'] ) ? floatval( $_POST['production_cost'] ) : null;
        }
        if ( isset( $_POST['total_value'] ) ) {
            $data['total_value'] = ! empty( $_POST['total_value'] ) ? floatval( $_POST['total_value'] ) : null;
        }

        // Calculate commission if we have total_value
        if ( isset( $data['total_value'] ) && $data['total_value'] > 0 ) {
            $commission_rate = get_option( 'pls_commission_rates', array() );
            $custom_order_config = isset( $commission_rate['custom_order'] ) ? $commission_rate['custom_order'] : array();
            $threshold = isset( $custom_order_config['threshold'] ) ? floatval( $custom_order_config['threshold'] ) : 100000.00;
            $rate_below = isset( $custom_order_config['rate_below'] ) ? floatval( $custom_order_config['rate_below'] ) : 3.00;
            $rate_above = isset( $custom_order_config['rate_above'] ) ? floatval( $custom_order_config['rate_above'] ) : 5.00;

            $rate_to_use = ( $data['total_value'] >= $threshold ) ? $rate_above : $rate_below;
            $data['nikola_commission_rate'] = $rate_to_use;
            $data['nikola_commission_amount'] = $data['total_value'] * ( $rate_to_use / 100 );
        }

        $result = PLS_Repo_Custom_Order::update( $order_id, $data );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Order updated successfully.', 'pls-private-label-store' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update order.', 'pls-private-label-store' ) ) );
        }
    }

    /**
     * Update custom order financials.
     */
    public static function update_custom_order_financials() {
        check_ajax_referer( 'pls_custom_orders_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $production_cost = isset( $_POST['production_cost'] ) ? floatval( $_POST['production_cost'] ) : 0;
        $total_value = isset( $_POST['total_value'] ) ? floatval( $_POST['total_value'] ) : 0;
        $nikola_commission_rate = isset( $_POST['nikola_commission_rate'] ) ? floatval( $_POST['nikola_commission_rate'] ) : 0;
        $nikola_commission_amount = isset( $_POST['nikola_commission_amount'] ) ? floatval( $_POST['nikola_commission_amount'] ) : 0;

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'pls-private-label-store' ) ) );
        }

        $result = PLS_Repo_Custom_Order::update_financials(
            $order_id,
            $production_cost,
            $final_value,
            $nikola_commission_rate,
            $nikola_commission_amount
        );

        // Auto-calculate commission when status changes to 'done'
        $order = PLS_Repo_Custom_Order::get( $order_id );
        if ( $order && 'done' === $order->status ) {
            // Auto-calculate commission if not already set
            if ( ! $order->nikola_commission_amount && $order->total_value ) {
                $final_value = floatval( $order->total_value );
                $commission_rate = get_option( 'pls_commission_rates', array() );
                $custom_order_config = isset( $commission_rate['custom_order'] ) ? $commission_rate['custom_order'] : array();
                
                if ( empty( $custom_order_config ) && isset( $commission_rate['custom_order_percent'] ) ) {
                    // Migrate old single rate
                    $old_rate = floatval( $commission_rate['custom_order_percent'] );
                    $custom_order_config = array(
                        'threshold' => 100000.00,
                        'rate_below' => $old_rate,
                        'rate_above' => $old_rate,
                    );
                }
                
                $threshold = isset( $custom_order_config['threshold'] ) ? floatval( $custom_order_config['threshold'] ) : 100000.00;
                $rate_below = isset( $custom_order_config['rate_below'] ) ? floatval( $custom_order_config['rate_below'] ) : 3.00;
                $rate_above = isset( $custom_order_config['rate_above'] ) ? floatval( $custom_order_config['rate_above'] ) : 5.00;
                
                $rate_to_use = ( $final_value >= $threshold ) ? $rate_above : $rate_below;
                $calculated_commission = $final_value * ( $rate_to_use / 100 );
                
                // Update order with calculated commission
                PLS_Repo_Custom_Order::update_financials(
                    $order_id,
                    $order->production_cost,
                    $order->final_value,
                    $rate_to_use,
                    $calculated_commission
                );
            }
            
            // Handle commission confirmation
            $commission_confirmed = isset( $_POST['commission_confirmed'] ) ? absint( $_POST['commission_confirmed'] ) : 0;
            global $wpdb;
            $table = $wpdb->prefix . 'pls_custom_order';
            $wpdb->update(
                $table,
                array( 'commission_confirmed' => $commission_confirmed ),
                array( 'id' => $order_id ),
                array( '%d' ),
                array( '%d' )
            );

            // If confirmed, mark as paid
            if ( $commission_confirmed && ! $order->paid_at ) {
                PLS_Repo_Custom_Order::mark_paid( $order_id );
                
                // Send notification email
                $recipients = get_option( 'pls_commission_email_recipients', array( 'n.nikolic97@gmail.com' ) );
                $to = is_array( $recipients ) ? $recipients[0] : $recipients;
                $user = wp_get_current_user();
                $subject = __( 'PLS Commission Payment Confirmed', 'pls-private-label-store' );
                $message = sprintf(
                    __( "Custom order commission payment has been confirmed.\n\nOrder #%d\nAmount: %s\nMarked as paid by: %s\n\nView details: %s", 'pls-private-label-store' ),
                    $order_id,
                    wc_price( $order->nikola_commission_amount ),
                    $user->user_email,
                    admin_url( 'admin.php?page=pls-custom-orders' )
                );
                wp_mail( $to, $subject, $message );
            }
        }

        if ( $result ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update financials.', 'pls-private-label-store' ) ) );
        }
    }

    /**
     * Mark custom order as invoiced.
     */
    public static function mark_custom_order_invoiced() {
        check_ajax_referer( 'pls_custom_orders_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'pls-private-label-store' ) ) );
        }

        $result = PLS_Repo_Custom_Order::mark_invoiced( $order_id );
        if ( $result ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to mark as invoiced.', 'pls-private-label-store' ) ) );
        }
    }

    /**
     * Mark custom order as paid.
     */
    public static function mark_custom_order_paid() {
        check_ajax_referer( 'pls_custom_orders_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'pls-private-label-store' ) ) );
        }

        $result = PLS_Repo_Custom_Order::mark_paid( $order_id );
        if ( $result ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to mark as paid.', 'pls-private-label-store' ) ) );
        }
    }

    /**
     * Mark commission item as invoiced.
     */
    public static function mark_commission_invoiced() {
        check_ajax_referer( 'pls_commission_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

        if ( ! $id || ! $type ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data.', 'pls-private-label-store' ) ) );
        }

        if ( 'product' === $type ) {
            $result = PLS_Repo_Commission::mark_invoiced( absint( $id ) );
        } else {
            $result = PLS_Repo_Custom_Order::mark_invoiced( absint( str_replace( 'custom_', '', $id ) ) );
        }

        if ( $result ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to mark as invoiced.', 'pls-private-label-store' ) ) );
        }
    }

    /**
     * Mark commission item as paid.
     */
    public static function mark_commission_paid() {
        check_ajax_referer( 'pls_commission_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

        if ( ! $id || ! $type ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data.', 'pls-private-label-store' ) ) );
        }

        $user = wp_get_current_user();
        $marked_by = $user->user_email;

        if ( 'product' === $type ) {
            $result = PLS_Repo_Commission::mark_paid( absint( $id ) );
        } else {
            $result = PLS_Repo_Custom_Order::mark_paid( absint( str_replace( 'custom_', '', $id ) ) );
        }

        if ( $result ) {
            // Send notification email to Nikola
            $recipients = get_option( 'pls_commission_email_recipients', array( 'n.nikolic97@gmail.com' ) );
            $to = is_array( $recipients ) ? $recipients[0] : $recipients;
            
            $subject = __( 'PLS Commission Payment Confirmed', 'pls-private-label-store' );
            $message = sprintf(
                __( "Commission payment has been confirmed.\n\nMarked as paid by: %s\n\nView details: %s", 'pls-private-label-store' ),
                $marked_by,
                admin_url( 'admin.php?page=pls-commission' )
            );
            
            wp_mail( $to, $subject, $message );

            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to mark as paid.', 'pls-private-label-store' ) ) );
        }
    }

    /**
     * Mark monthly commission as invoiced.
     */
    public static function mark_commission_invoiced_monthly() {
        check_ajax_referer( 'pls_commission_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $month = isset( $_POST['month'] ) ? sanitize_text_field( wp_unslash( $_POST['month'] ) ) : '';
        if ( ! $month ) {
            wp_send_json_error( array( 'message' => __( 'Invalid month.', 'pls-private-label-store' ) ) );
        }

        $month_start = $month . '-01';
        $month_end = date( 'Y-m-t', strtotime( $month_start ) );

        // Mark all commissions for this month as invoiced
        $commissions = PLS_Repo_Commission::query(
            array(
                'date_from' => $month_start,
                'date_to'   => $month_end,
                'invoiced'  => false,
                'limit'     => 1000,
            )
        );

        foreach ( $commissions as $comm ) {
            PLS_Repo_Commission::mark_invoiced( $comm->id );
        }

        // Mark custom orders
        $custom_orders = PLS_Repo_Custom_Order::all();
        foreach ( $custom_orders as $order ) {
            if ( $order->nikola_commission_amount ) {
                $order_month = date( 'Y-m', strtotime( $order->created_at ) );
                if ( $order_month === $month && ! $order->invoiced_at ) {
                    PLS_Repo_Custom_Order::mark_invoiced( $order->id );
                }
            }
        }

        // Update report
        $report = PLS_Repo_Commission_Report::get_by_month( $month );
        if ( ! $report ) {
            // Calculate total
            $product_total = PLS_Repo_Commission::get_total(
                array(
                    'date_from' => $month_start,
                    'date_to'   => $month_end,
                )
            );
            $custom_total = 0;
            foreach ( $custom_orders as $order ) {
                if ( $order->nikola_commission_amount ) {
                    $order_month = date( 'Y-m', strtotime( $order->created_at ) );
                    if ( $order_month === $month ) {
                        $custom_total += floatval( $order->nikola_commission_amount );
                    }
                }
            }
            PLS_Repo_Commission_Report::create_or_update( $month, $product_total + $custom_total );
        }
        PLS_Repo_Commission_Report::mark_sent( $month );

        wp_send_json_success();
    }

    /**
     * Mark monthly commission as paid.
     */
    public static function mark_commission_paid_monthly() {
        check_ajax_referer( 'pls_commission_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $month = isset( $_POST['month'] ) ? sanitize_text_field( wp_unslash( $_POST['month'] ) ) : '';
        if ( ! $month ) {
            wp_send_json_error( array( 'message' => __( 'Invalid month.', 'pls-private-label-store' ) ) );
        }

        $month_start = $month . '-01';
        $month_end = date( 'Y-m-t', strtotime( $month_start ) );

        // Mark all commissions for this month as paid
        $commissions = PLS_Repo_Commission::query(
            array(
                'date_from' => $month_start,
                'date_to'   => $month_end,
                'paid'      => false,
                'limit'     => 1000,
            )
        );

        foreach ( $commissions as $comm ) {
            PLS_Repo_Commission::mark_paid( $comm->id );
        }

        // Mark custom orders
        $custom_orders = PLS_Repo_Custom_Order::all();
        foreach ( $custom_orders as $order ) {
            if ( $order->nikola_commission_amount ) {
                $order_month = date( 'Y-m', strtotime( $order->created_at ) );
                if ( $order_month === $month && ! $order->paid_at ) {
                    PLS_Repo_Custom_Order::mark_paid( $order->id );
                }
            }
        }

        // Update report
        $user_id = get_current_user_id();
        PLS_Repo_Commission_Report::mark_paid( $month, $user_id );

        // Send notification email to Nikola
        $recipients = get_option( 'pls_commission_email_recipients', array( 'n.nikolic97@gmail.com' ) );
        $to = is_array( $recipients ) ? $recipients[0] : $recipients;
        
        $user = wp_get_current_user();
        $subject = __( 'PLS Commission Payment Confirmed', 'pls-private-label-store' );
        $message = sprintf(
            __( "Commission payment for %s has been confirmed.\n\nMarked as paid by: %s\n\nView details: %s", 'pls-private-label-store' ),
            date( 'F Y', strtotime( $month_start ) ),
            $user->user_email,
            admin_url( 'admin.php?page=pls-commission' )
        );
        
        wp_mail( $to, $subject, $message );

        wp_send_json_success();
    }

    /**
     * Bulk update commission status.
     */
    public static function bulk_update_commission() {
        check_ajax_referer( 'pls_commission_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $ids = isset( $_POST['ids'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['ids'] ) ) : array();
        $action = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';

        if ( empty( $ids ) || ! $action ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data.', 'pls-private-label-store' ) ) );
        }

        $product_ids = array();
        $custom_ids = array();

        foreach ( $ids as $id ) {
            if ( strpos( $id, 'custom_' ) === 0 ) {
                $custom_ids[] = absint( str_replace( 'custom_', '', $id ) );
            } else {
                $product_ids[] = absint( $id );
            }
        }

        if ( 'mark_invoiced' === $action ) {
            if ( ! empty( $product_ids ) ) {
                PLS_Repo_Commission::bulk_update_status( $product_ids, 'invoiced' );
            }
            foreach ( $custom_ids as $custom_id ) {
                PLS_Repo_Custom_Order::mark_invoiced( $custom_id );
            }
        } elseif ( 'mark_paid' === $action ) {
            if ( ! empty( $product_ids ) ) {
                PLS_Repo_Commission::bulk_update_status( $product_ids, 'paid' );
            }
            foreach ( $custom_ids as $custom_id ) {
                PLS_Repo_Custom_Order::mark_paid( $custom_id );
            }

            // Send notification email
            $recipients = get_option( 'pls_commission_email_recipients', array( 'n.nikolic97@gmail.com' ) );
            $to = is_array( $recipients ) ? $recipients[0] : $recipients;
            $user = wp_get_current_user();
            $subject = __( 'PLS Commission Payment Confirmed', 'pls-private-label-store' );
            $message = sprintf(
                __( "Commission payments have been confirmed.\n\nMarked as paid by: %s\n\nView details: %s", 'pls-private-label-store' ),
                $user->user_email,
                admin_url( 'admin.php?page=pls-commission' )
            );
            wp_mail( $to, $subject, $message );
        }

        wp_send_json_success();
    }

    /**
     * Send monthly commission report manually.
     */
    public static function send_monthly_report() {
        check_ajax_referer( 'pls_commission_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $month = isset( $_POST['month'] ) ? sanitize_text_field( wp_unslash( $_POST['month'] ) ) : '';
        if ( ! $month ) {
            wp_send_json_error( array( 'message' => __( 'Invalid month.', 'pls-private-label-store' ) ) );
        }

        $sent = PLS_Commission_Email::send_manual_report( $month );

        if ( $sent ) {
            wp_send_json_success( array( 'message' => __( 'Monthly report sent successfully.', 'pls-private-label-store' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to send report.', 'pls-private-label-store' ) ) );
        }
    }

    /**
     * AJAX: save bundle (create or update).
     */
    public static function save_bundle() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $bundle_id = isset( $_POST['bundle_id'] ) ? absint( $_POST['bundle_id'] ) : 0;
        $name = isset( $_POST['bundle_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bundle_name'] ) ) : '';
        $bundle_type = isset( $_POST['bundle_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bundle_type'] ) ) : '';
        $sku_count = isset( $_POST['sku_count'] ) ? absint( $_POST['sku_count'] ) : 0;
        $units_per_sku = isset( $_POST['units_per_sku'] ) ? absint( $_POST['units_per_sku'] ) : 0;
        $price_per_unit = isset( $_POST['price_per_unit'] ) ? floatval( $_POST['price_per_unit'] ) : 0;
        $commission_per_unit = isset( $_POST['commission_per_unit'] ) ? floatval( $_POST['commission_per_unit'] ) : 0;
        $status = isset( $_POST['bundle_status'] ) ? sanitize_text_field( wp_unslash( $_POST['bundle_status'] ) ) : 'draft';

        if ( empty( $name ) || empty( $bundle_type ) || $sku_count < 2 || $units_per_sku < 1 || $price_per_unit <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'pls-private-label-store' ) ), 400 );
        }

        $slug = sanitize_title( $name );
        $bundle_key = $bundle_type . '_' . $sku_count . 'x' . $units_per_sku;

        // Calculate totals
        $total_units = $sku_count * $units_per_sku;
        $total_price = $total_units * $price_per_unit;

        // Store bundle rules in JSON
        $offer_rules = array(
            'bundle_type' => $bundle_type,
            'sku_count' => $sku_count,
            'units_per_sku' => $units_per_sku,
            'price_per_unit' => $price_per_unit,
            'commission_per_unit' => $commission_per_unit,
            'total_units' => $total_units,
            'total_price' => $total_price,
        );

        $data = array(
            'bundle_key' => $bundle_key,
            'slug' => $slug,
            'name' => $name,
            'base_price' => $total_price,
            'pricing_mode' => 'fixed',
            'status' => $status,
            'offer_rules_json' => wp_json_encode( $offer_rules ),
        );

        if ( $bundle_id ) {
            // Update existing bundle
            PLS_Repo_Bundle::update( $bundle_id, $data );
            $bundle = PLS_Repo_Bundle::get( $bundle_id );
        } else {
            // Create new bundle
            $bundle_id = PLS_Repo_Bundle::insert( $data );
            $bundle = PLS_Repo_Bundle::get( $bundle_id );
        }

        if ( ! $bundle ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save bundle.', 'pls-private-label-store' ) ), 500 );
        }

        // Auto-sync to WooCommerce
        $sync_result = PLS_WC_Sync::sync_bundle_to_wc( $bundle_id );
        if ( is_wp_error( $sync_result ) ) {
            // Log error but don't fail the save
            error_log( 'PLS Bundle sync error: ' . $sync_result->get_error_message() );
        }

        wp_send_json_success(
            array(
                'message' => $bundle_id ? __( 'Bundle updated.', 'pls-private-label-store' ) : __( 'Bundle created.', 'pls-private-label-store' ),
                'bundle' => $bundle,
            )
        );
    }

    /**
     * AJAX: get bundle data.
     */
    public static function get_bundle() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $bundle_id = isset( $_POST['bundle_id'] ) ? absint( $_POST['bundle_id'] ) : 0;
        $bundle = $bundle_id ? PLS_Repo_Bundle::get( $bundle_id ) : null;

        if ( ! $bundle ) {
            wp_send_json_error( array( 'message' => __( 'Bundle not found.', 'pls-private-label-store' ) ), 404 );
        }

        // Parse bundle rules
        $bundle_rules = ! empty( $bundle->offer_rules_json ) ? json_decode( $bundle->offer_rules_json, true ) : array();

        wp_send_json_success(
            array(
                'bundle' => array(
                    'id' => $bundle->id,
                    'name' => $bundle->name,
                    'bundle_type' => isset( $bundle_rules['bundle_type'] ) ? $bundle_rules['bundle_type'] : '',
                    'sku_count' => isset( $bundle_rules['sku_count'] ) ? $bundle_rules['sku_count'] : 0,
                    'units_per_sku' => isset( $bundle_rules['units_per_sku'] ) ? $bundle_rules['units_per_sku'] : 0,
                    'price_per_unit' => isset( $bundle_rules['price_per_unit'] ) ? $bundle_rules['price_per_unit'] : 0,
                    'commission_per_unit' => isset( $bundle_rules['commission_per_unit'] ) ? $bundle_rules['commission_per_unit'] : 0,
                    'status' => $bundle->status,
                ),
            )
        );
    }

    /**
     * AJAX: delete bundle.
     */
    public static function delete_bundle() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $bundle_id = isset( $_POST['bundle_id'] ) ? absint( $_POST['bundle_id'] ) : 0;
        $bundle = $bundle_id ? PLS_Repo_Bundle::get( $bundle_id ) : null;

        if ( ! $bundle ) {
            wp_send_json_error( array( 'message' => __( 'Bundle not found.', 'pls-private-label-store' ) ), 404 );
        }

        // Delete WooCommerce product if exists
        if ( $bundle->wc_product_id ) {
            wp_trash_post( $bundle->wc_product_id );
        }

        PLS_Repo_Bundle::delete( $bundle_id );

        wp_send_json_success(
            array(
                'message' => __( 'Bundle deleted.', 'pls-private-label-store' ),
                'bundle_id' => $bundle_id,
            )
        );
    }

    /**
     * AJAX: sync bundle to WooCommerce.
     */
    public static function sync_bundle() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $bundle_id = isset( $_POST['bundle_id'] ) ? absint( $_POST['bundle_id'] ) : 0;
        $bundle = $bundle_id ? PLS_Repo_Bundle::get( $bundle_id ) : null;

        if ( ! $bundle ) {
            wp_send_json_error( array( 'message' => __( 'Bundle not found.', 'pls-private-label-store' ) ), 404 );
        }

        $result = PLS_WC_Sync::sync_bundle_to_wc( $bundle_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Bundle synced successfully.', 'pls-private-label-store' ),
                'bundle' => PLS_Repo_Bundle::get( $bundle_id ),
            )
        );
    }

    /**
     * AJAX: Get BI metrics for date range.
     */
    public static function get_bi_metrics() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : date( 'Y-m-d', strtotime( '-30 days' ) );
        $date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : date( 'Y-m-d' );

        // Calculate revenue
        $revenue = 0.00;
        if ( class_exists( 'WooCommerce' ) ) {
            $orders = wc_get_orders(
                array(
                    'date_created' => $date_from . '...' . $date_to,
                    'status'       => array( 'wc-completed', 'wc-processing' ),
                    'limit'        => -1,
                )
            );
            foreach ( $orders as $order ) {
                $revenue += floatval( $order->get_total() );
            }
        }

        // Add custom orders
        global $wpdb;
        $custom_order_table = $wpdb->prefix . 'pls_custom_order';
        $custom_revenue = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_value) FROM {$custom_order_table} WHERE status = 'done' AND DATE(updated_at) >= %s AND DATE(updated_at) <= %s",
                $date_from,
                $date_to
            )
        );
        $revenue += floatval( $custom_revenue );

        // Calculate commission
        $commission_table = $wpdb->prefix . 'pls_order_commission';
        $commission = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(commission_amount) FROM {$commission_table} WHERE DATE(created_at) >= %s AND DATE(created_at) <= %s",
                $date_from,
                $date_to
            )
        );

        // Get marketing costs
        $marketing_cost = PLS_Repo_Marketing_Cost::get_total( $date_from, $date_to );

        // Calculate profit
        $profit = $revenue - $commission - $marketing_cost;

        wp_send_json_success(
            array(
                'revenue'       => $revenue,
                'commission'   => $commission,
                'marketing_cost' => $marketing_cost,
                'profit'       => $profit,
            )
        );
    }

    /**
     * AJAX: Get BI chart data for date range.
     */
    public static function get_bi_chart_data() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : date( 'Y-m-d', strtotime( '-30 days' ) );
        $date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : date( 'Y-m-d' );

        // Generate revenue trend (daily)
        $revenue_trend = array(
            'labels' => array(),
            'values' => array(),
        );

        $current = strtotime( $date_from );
        $end = strtotime( $date_to );

        while ( $current <= $end ) {
            $date_str = date( 'Y-m-d', $current );
            $revenue_trend['labels'][] = date( 'M j', $current );

            // Calculate revenue for this day
            $day_revenue = 0.00;
            if ( class_exists( 'WooCommerce' ) ) {
                $orders = wc_get_orders(
                    array(
                        'date_created' => $date_str,
                        'status'       => array( 'wc-completed', 'wc-processing' ),
                        'limit'        => -1,
                    )
                );
                foreach ( $orders as $order ) {
                    $day_revenue += floatval( $order->get_total() );
                }
            }

            global $wpdb;
            $custom_order_table = $wpdb->prefix . 'pls_custom_order';
            $custom_revenue = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(total_value) FROM {$custom_order_table} WHERE status = 'done' AND DATE(updated_at) = %s",
                    $date_str
                )
            );
            $day_revenue += floatval( $custom_revenue );

            $revenue_trend['values'][] = $day_revenue;
            $current = strtotime( '+1 day', $current );
        }

        // Get marketing costs by channel
        $marketing_by_channel = PLS_Repo_Marketing_Cost::get_total_by_channel( $date_from, $date_to );

        wp_send_json_success(
            array(
                'revenue_trend'      => $revenue_trend,
                'marketing_by_channel' => $marketing_by_channel,
            )
        );
    }

    /**
     * AJAX: Save marketing cost entry.
     */
    public static function save_marketing_cost() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $cost_date = isset( $_POST['cost_date'] ) ? sanitize_text_field( wp_unslash( $_POST['cost_date'] ) ) : '';
        $channel = isset( $_POST['channel'] ) ? sanitize_text_field( wp_unslash( $_POST['channel'] ) ) : '';
        $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

        if ( empty( $cost_date ) || empty( $channel ) || $amount <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'pls-private-label-store' ) ), 400 );
        }

        $id = PLS_Repo_Marketing_Cost::create(
            array(
                'cost_date'   => $cost_date,
                'channel'     => $channel,
                'amount'      => $amount,
                'description' => $description,
            )
        );

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save marketing cost.', 'pls-private-label-store' ) ), 500 );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Marketing cost saved successfully.', 'pls-private-label-store' ),
                'id'      => $id,
            )
        );
    }

    /**
     * AJAX: Get product performance data.
     */
    public static function get_product_performance() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : date( 'Y-m-d', strtotime( '-30 days' ) );
        $date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : date( 'Y-m-d' );

        global $wpdb;
        $commission_table = $wpdb->prefix . 'pls_order_commission';
        $base_product_table = $wpdb->prefix . 'pls_base_product';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    bp.id,
                    bp.name,
                    SUM(c.commission_amount) as revenue,
                    SUM(c.units) as units
                FROM {$commission_table} c
                INNER JOIN {$base_product_table} bp ON c.product_id = bp.wc_product_id
                WHERE DATE(c.created_at) >= %s AND DATE(c.created_at) <= %s
                GROUP BY bp.id, bp.name
                ORDER BY revenue DESC
                LIMIT 20",
                $date_from,
                $date_to
            ),
            OBJECT
        );

        $performance = array();
        foreach ( $results as $row ) {
            $performance[] = array(
                'id'      => $row->id,
                'name'    => $row->name,
                'revenue' => floatval( $row->revenue ),
                'units'   => intval( $row->units ),
            );
        }

        wp_send_json_success( $performance );
    }

    // =========================================================================
    // SYSTEM TEST AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Run a specific test category.
     */
    public static function run_test_category() {
        check_ajax_referer( 'pls_system_test_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';

        if ( empty( $category ) ) {
            wp_send_json_error( array( 'message' => __( 'No test category specified.', 'pls-private-label-store' ) ), 400 );
        }

        // Ensure test class is loaded
        if ( ! class_exists( 'PLS_System_Test' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-system-test.php';
        }

        $results = PLS_System_Test::run_category( $category );

        wp_send_json_success( $results );
    }

    /**
     * AJAX: Run all tests.
     */
    public static function run_all_tests() {
        check_ajax_referer( 'pls_system_test_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        // Ensure test class is loaded
        if ( ! class_exists( 'PLS_System_Test' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-system-test.php';
        }

        $results = PLS_System_Test::run_all_tests();

        wp_send_json_success( $results );
    }

    /**
     * AJAX: Fix an issue (resync, regenerate, etc.).
     */
    public static function fix_issue() {
        check_ajax_referer( 'pls_system_test_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ), 403 );
        }

        $action = isset( $_POST['fix_action'] ) ? sanitize_text_field( wp_unslash( $_POST['fix_action'] ) ) : '';

        if ( empty( $action ) ) {
            wp_send_json_error( array( 'message' => __( 'No action specified.', 'pls-private-label-store' ) ), 400 );
        }

        // Ensure test class is loaded
        if ( ! class_exists( 'PLS_System_Test' ) ) {
            require_once PLS_PLS_DIR . 'includes/core/class-pls-system-test.php';
        }

        $result = array(
            'success' => false,
            'message' => __( 'Unknown action.', 'pls-private-label-store' ),
        );

        switch ( $action ) {
            case 'resync_products':
                $result = PLS_System_Test::fix_resync_products();
                break;

            case 'resync_bundles':
                $result = PLS_System_Test::fix_resync_bundles();
                break;

            case 'generate_sample_data':
                // Increase timeout for sample data generation (can take 1-3 minutes)
                @set_time_limit( 300 );
                @ini_set( 'max_execution_time', 300 );
                $result = PLS_System_Test::fix_generate_sample_data();
                break;

            default:
                $result = array(
                    'success' => false,
                    'message' => __( 'Unknown action: ', 'pls-private-label-store' ) . $action,
                );
        }

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }
}
