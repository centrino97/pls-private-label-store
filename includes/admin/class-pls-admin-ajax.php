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
        add_action( 'wp_ajax_pls_create_attribute', array( __CLASS__, 'create_attribute' ) );
        add_action( 'wp_ajax_pls_create_attribute_value', array( __CLASS__, 'create_attribute_value' ) );
        add_action( 'wp_ajax_pls_update_attribute_values', array( __CLASS__, 'update_attribute_values' ) );
        add_action( 'wp_ajax_pls_update_attribute_tier_rules', array( __CLASS__, 'update_attribute_tier_rules' ) );
        add_action( 'wp_ajax_pls_set_pack_tier_attribute', array( __CLASS__, 'set_pack_tier_attribute' ) );
        add_action( 'wp_ajax_pls_get_tier_pricing', array( __CLASS__, 'get_tier_pricing' ) );
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

        $label       = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
        $is_variation = isset( $_POST['is_variation'] ) ? 1 : 0;

        if ( '' === $label ) {
            wp_send_json_error( array( 'message' => __( 'Attribute label is required.', 'pls-private-label-store' ) ), 400 );
        }

        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'        => $label,
                'is_variation' => $is_variation,
            )
        );

        wp_send_json_success(
            array(
                'attribute' => array(
                    'id'     => $attr_id,
                    'label'  => $label,
                    'values' => array(),
                ),
            )
        );
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
                'id'     => $attr->id,
                'label'  => $attr->label,
                'values' => $values,
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
        );
    }

    /**
     * Reconcile products pointing at deleted Woo products.
     *
     * @param array $products Base products list.
     * @return array Filtered products.
     */
    public static function reconcile_orphaned_products( $products ) {
        $clean = array();

        foreach ( $products as $product ) {
            if ( $product->wc_product_id && ! get_post( $product->wc_product_id ) ) {
                self::delete_product_records( $product->id );
                continue;
            }
            $clean[] = $product;
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
     * AJAX: save product with validation + Woo sync.
     */
    public static function save_product() {
        check_ajax_referer( 'pls_admin_nonce', 'nonce' );

        if ( ! current_user_can( PLS_Capabilities::CAP_PRODUCTS ) ) {
            wp_send_json_error( array( 'ok' => false, 'errors' => array( array( 'field' => 'permission', 'message' => __( 'Insufficient permissions.', 'pls-private-label-store' ) ) ) ), 403 );
        }

        $payload = self::sanitize_product_request( $_POST );
        $errors  = self::validate_product_payload( $payload );

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array( 'ok' => false, 'errors' => $errors ), 400 );
        }

        $persisted = self::persist_product( $payload );
        if ( is_wp_error( $persisted ) ) {
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
        if ( is_wp_error( $sync_result ) ) {
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

        self::record_sync_status( $persisted['id'], $sync_result, true );
        $product_payload = self::format_product_payload( PLS_Repo_Base_Product::get( $persisted['id'] ), 'https://bodocibiophysics.com/label-guide/' );

        wp_send_json_success(
            array(
                'ok'            => true,
                'product'       => $product_payload,
                'sync_message'  => $sync_result,
            )
        );
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

        self::record_sync_status( $id, $result, true );
        $product_payload = self::format_product_payload( PLS_Repo_Base_Product::get( $id ), 'https://bodocibiophysics.com/label-guide/' );

        wp_send_json_success(
            array(
                'message' => $result,
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
}
