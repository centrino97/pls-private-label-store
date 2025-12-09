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
}
