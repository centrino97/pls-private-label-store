<?php
/**
 * Elementor Widget: PLS Bundle Offer (upgrade card).
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

final class PLS_Widget_Bundle_Offer extends Widget_Base {

    public function get_name() {
        return 'pls_bundle_offer';
    }

    public function get_title() {
        return __( 'PLS Bundle Offer', 'pls-private-label-store' );
    }

    public function get_icon() {
        return 'eicon-cart';
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
            'title',
            array(
                'label' => __( 'Title', 'pls-private-label-store' ),
                'type'  => Controls_Manager::TEXT,
                'default' => __( 'Upgrade your order', 'pls-private-label-store' ),
            )
        );

        $this->add_control(
            'popup_selector',
            array(
                'label' => __( 'Elementor Popup selector (optional)', 'pls-private-label-store' ),
                'type'  => Controls_Manager::TEXT,
                'description' => __( 'If set, render a button with this CSS selector so Elementor Pro Popup can be configured to open "On click" by selector.', 'pls-private-label-store' ),
                'default' => '',
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $title = isset($settings['title']) ? $settings['title'] : '';

        $selector = isset($settings['popup_selector']) ? trim((string)$settings['popup_selector']) : '';

        ?>
        <div class="pls-offer" data-pls-offer-widget="1">
            <div class="pls-offer__header">
                <div class="pls-offer__title"><?php echo esc_html( $title ); ?></div>
                <div class="pls-offer__sub"><?php echo esc_html__( 'Offers loaded via AJAX (stub).', 'pls-private-label-store' ); ?></div>
            </div>

            <div class="pls-offer__body">
                <button type="button" class="pls-offer__btn <?php echo $selector ? esc_attr( ltrim($selector, '.') ) : ''; ?>"
                        data-pls-popup-selector="<?php echo esc_attr( $selector ); ?>">
                    <?php echo esc_html__( 'See upgrades', 'pls-private-label-store' ); ?>
                </button>

                <div class="pls-offer__list" data-pls-offer-list>
                    <div class="pls-muted"><?php echo esc_html__( 'This will populate with eligible offers. Implement mapping from cart/product → pls_bundle.', 'pls-private-label-store' ); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
}
