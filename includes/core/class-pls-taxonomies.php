<?php
/**
 * Registers lightweight taxonomies used by the PLS admin.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Taxonomies {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register' ) );
    }

    public static function register() {
        register_taxonomy(
            'pls_ingredient',
            array(),
            array(
                'label'             => __( 'Ingredients', 'pls-private-label-store' ),
                'public'            => false,
                'show_ui'           => false,
                'show_in_rest'      => false,
                'hierarchical'      => false,
                'rewrite'           => false,
                'show_admin_column' => false,
            )
        );
    }

    public static function icon_for_term( $term_id ) {
        $icon_id = absint( get_term_meta( $term_id, 'pls_ingredient_icon_id', true ) );
        $icon    = '';

        if ( $icon_id ) {
            $icon = wp_get_attachment_url( $icon_id );
        }

        if ( ! $icon ) {
            $icon = get_term_meta( $term_id, 'pls_ingredient_icon', true );
        }

        if ( ! $icon ) {
            $icon = self::default_icon();
        }

        return $icon ? esc_url_raw( $icon ) : '';
    }

    /**
     * Default fallback icon for ingredients when no custom media is set.
     *
     * @return string
     */
    public static function default_icon() {
        return PLS_PLS_URL . 'assets/img/ingredient-placeholder.svg';
    }
}
