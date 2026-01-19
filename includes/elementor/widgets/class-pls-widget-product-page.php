<?php
/**
 * Elementor Widget: PLS Product Page (Comprehensive - Configurator + Info + Bundles).
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

final class PLS_Widget_Product_Page extends Widget_Base {

    public function get_name() {
        return 'pls_product_page';
    }

    public function get_title() {
        return __( 'PLS Product Page', 'pls-private-label-store' );
    }

    public function get_icon() {
        return 'eicon-product-pages';
    }

    public function get_categories() {
        return array( 'woocommerce-elements' );
    }

    public function get_style_depends() {
        return array( 'pls-offers' );
    }

    public function get_script_depends() {
        return array( 'pls-offers' );
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            array(
                'label' => __( 'Content', 'pls-private-label-store' ),
            )
        );

        $this->add_control(
            'product_id',
            array(
                'label'       => __( 'Product ID', 'pls-private-label-store' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 0,
                'description' => __( 'Leave 0 to auto-detect from current product page.', 'pls-private-label-store' ),
            )
        );

        $this->add_control(
            'show_configurator',
            array(
                'label'   => __( 'Show Configurator', 'pls-private-label-store' ),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );

        $this->add_control(
            'show_product_info',
            array(
                'label'   => __( 'Show Product Info', 'pls-private-label-store' ),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );

        $this->add_control(
            'show_bundles',
            array(
                'label'   => __( 'Show Bundle Offers', 'pls-private-label-store' ),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $product_id = ! empty( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;

        // Auto-detect product ID if not provided
        if ( ! $product_id && function_exists( 'is_product' ) && is_product() ) {
            global $product;
            if ( $product instanceof \WC_Product ) {
                $product_id = $product->get_id();
            }
        }

        if ( ! $product_id ) {
            echo '<div class="pls-note">' . esc_html__( 'Product ID required. Please set a product ID or use this widget on a product page.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        // Build shortcode attributes
        $shortcode_atts = array( 'product_id' => $product_id );

        // Render using the shortcode
        echo do_shortcode( '[pls_product_page product_id="' . esc_attr( $product_id ) . '"]' );
    }
}
