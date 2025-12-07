<?php
/**
 * Elementor Dynamic Tag: Pack Units (stub).
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Core\DynamicTags\Tag;

final class PLS_DTag_Pack_Units extends Tag {

    public function get_name() {
        return 'pls_pack_units';
    }

    public function get_title() {
        return __( 'PLS Pack Units', 'pls-private-label-store' );
    }

    public function get_group() {
        return 'pls';
    }

    public function get_categories() {
        return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
    }

    public function render() {
        if ( ! function_exists( 'wc_get_product' ) ) {
            echo esc_html__( '—', 'pls-private-label-store' );
            return;
        }

        global $product;
        $variation_id = 0;

        if ( $product instanceof \WC_Product_Variation ) {
            $variation_id = $product->get_id();
        } elseif ( $product instanceof \WC_Product && $product->is_type( 'variable' ) ) {
            $defaults = $product->get_default_attributes();
            if ( ! empty( $defaults ) ) {
                $variation_id = wc_get_product_variation_id_by_attributes( $product, $defaults );
            }

            if ( ! $variation_id ) {
                $children = $product->get_children();
                if ( ! empty( $children ) ) {
                    $variation_id = (int) current( $children );
                }
            }
        }

        $units = $variation_id ? get_post_meta( $variation_id, '_pls_units', true ) : '';
        if ( '' === $units ) {
            echo esc_html__( '—', 'pls-private-label-store' );
            return;
        }

        echo esc_html( $units );
    }
}
