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

        if ( ! $product->is_type( 'variable' ) ) {
            echo '<div class="pls-note">' . esc_html__( 'PLS Configurator requires a variable product with pack tiers.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        $variation_attributes = $product->get_variation_attributes();
        $pack_tiers           = isset( $variation_attributes['pa_pack-tier'] ) ? (array) $variation_attributes['pa_pack-tier'] : array();

        if ( empty( $pack_tiers ) ) {
            echo '<div class="pls-note">' . esc_html__( 'No pack tiers found on this product.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        ?>
        <div class="pls-configurator" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
            <div class="pls-configurator__title"><?php echo esc_html__( 'Configure your pack', 'pls-private-label-store' ); ?></div>

            <div class="pls-configurator__block">
                <strong><?php echo esc_html__( 'Pack tiers', 'pls-private-label-store' ); ?></strong>
                <div class="pls-chips">
                    <?php foreach ( $pack_tiers as $slug ) :
                        $term = get_term_by( 'slug', $slug, 'pa_pack-tier' );
                        $label = $term ? $term->name : $slug;
                        ?>
                        <button type="button" class="pls-chip pls-tier-button" data-term="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></button>
                    <?php endforeach; ?>
                </div>
                <p class="pls-muted"><?php echo esc_html__( 'Selecting a tier updates the WooCommerce variation form.', 'pls-private-label-store' ); ?></p>
            </div>
        </div>
        <?php
        $script = <<<'JS'
jQuery(function($){
  $('.pls-configurator').on('click', '.pls-tier-button', function(){
    var btn = $(this);
    var slug = btn.data('term');
    var wrap = btn.closest('.product, .pls-configurator');
    var form = wrap.find('form.variations_form, form.cart').first();
    var select = form.find('select[name="attribute_pa_pack-tier"]');
    if(!select.length){return;}
    select.val(slug).trigger('change');
    form.find('input.variation_id').trigger('change');
    form.trigger('woocommerce_variation_has_changed');
    btn.closest('.pls-chips').find('.pls-tier-button').removeClass('is-active');
    btn.addClass('is-active');
  });
});
JS;

        wc_enqueue_js( $script );
    }
}
