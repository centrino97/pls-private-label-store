<?php
/**
 * Elementor Widget: PLS Configurator (Pack tiers + swatches).
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

final class PLS_Widget_Configurator extends Widget_Base {

    public function get_name() {
        return 'pls_configurator';
    }

    public function get_title() {
        return __( 'PLS Configurator', 'pls-private-label-store' );
    }

    public function get_icon() {
        return 'eicon-products';
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
            'show_pack_tiers',
            array(
                'label' => __( 'Show pack tiers', 'pls-private-label-store' ),
                'type'  => Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );

        $this->add_control(
            'show_swatches',
            array(
                'label' => __( 'Show swatches', 'pls-private-label-store' ),
                'type'  => Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        if ( ! function_exists( 'is_product' ) || ! is_product() ) {
            echo '<div class="pls-note">' . esc_html__( 'PLS Configurator is intended for Single Product templates.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        global $product;
        if ( ! $product instanceof \WC_Product ) {
            echo '<div class="pls-note">' . esc_html__( 'WooCommerce product not available.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        // In Phase 1, we rely on WooCommerce variable-product mechanism.
        // Your team will replace the UI below with:
        // - pack tier selector wired to variation attribute pa_pack-tier
        // - attribute swatches wired to variation attributes
        ?>
        <div class="pls-configurator" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
            <div class="pls-configurator__title"><?php echo esc_html__( 'Configure your pack', 'pls-private-label-store' ); ?></div>

            <div class="pls-configurator__block">
                <strong><?php echo esc_html__( 'Pack tiers (stub UI)', 'pls-private-label-store' ); ?></strong>
                <div class="pls-chips">
                    <button type="button" class="pls-chip" data-tier="trial">Trial</button>
                    <button type="button" class="pls-chip" data-tier="starter">Starter</button>
                    <button type="button" class="pls-chip" data-tier="brand_entry">Brand Entry</button>
                    <button type="button" class="pls-chip" data-tier="growth">Growth</button>
                    <button type="button" class="pls-chip" data-tier="wholesale">Wholesale</button>
                </div>
                <p class="pls-muted"><?php echo esc_html__( 'Wire these buttons to the Woo variation form (attribute pa_pack-tier) so Woo price/stock updates work natively.', 'pls-private-label-store' ); ?></p>
            </div>

            <div class="pls-configurator__block">
                <strong><?php echo esc_html__( 'Swatches (stub)', 'pls-private-label-store' ); ?></strong>
                <div class="pls-chips">
                    <span class="pls-swatch pls-swatch--color" style="background:#111;" title="Example"></span>
                    <span class="pls-swatch pls-swatch--color" style="background:#999;" title="Example"></span>
                    <span class="pls-swatch pls-swatch--label">Label</span>
                </div>
                <p class="pls-muted"><?php echo esc_html__( 'Implement swatch rendering using PLS attribute tables + Woo term mapping.', 'pls-private-label-store' ); ?></p>
            </div>
        </div>
        <?php
    }
}
