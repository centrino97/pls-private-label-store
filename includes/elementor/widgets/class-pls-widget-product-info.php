<?php
/**
 * Elementor Widget: PLS Product Info (Description, Ingredients, Directions, etc.).
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

final class PLS_Widget_Product_Info extends Widget_Base {

    public function get_name() {
        return 'pls_product_info';
    }

    public function get_title() {
        return __( 'PLS Product Info', 'pls-private-label-store' );
    }

    public function get_icon() {
        return 'eicon-info-circle';
    }

    public function get_categories() {
        return array( 'woocommerce-elements' );
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            array(
                'label' => __( 'Content', 'pls-private-label-store' ),
            )
        );

        $this->add_control(
            'show_description',
            array(
                'label'   => __( 'Show Description', 'pls-private-label-store' ),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );

        $this->add_control(
            'show_directions',
            array(
                'label'   => __( 'Show Directions', 'pls-private-label-store' ),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );

        $this->add_control(
            'show_ingredients',
            array(
                'label'   => __( 'Show Key Ingredients', 'pls-private-label-store' ),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );

        $this->add_control(
            'show_skin_types',
            array(
                'label'   => __( 'Show Skin Types', 'pls-private-label-store' ),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        global $product;
        
        if ( ! $product instanceof \WC_Product ) {
            echo '<div class="pls-note">' . esc_html__( 'WooCommerce product not available.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        $settings = $this->get_settings_for_display();
        $product_id = $product->get_id();

        // Get PLS product data
        global $wpdb;
        $pls_product_table = $wpdb->prefix . 'pls_base_product';
        $pls_product = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$pls_product_table} WHERE wc_product_id = %d LIMIT 1",
            $product_id
        ) );

        if ( ! $pls_product ) {
            // Fallback to WooCommerce description
            if ( $settings['show_description'] === 'yes' ) {
                echo '<div class="pls-product-info">';
                echo '<div class="pls-product-description">' . wp_kses_post( $product->get_description() ) . '</div>';
                echo '</div>';
            }
            return;
        }

        // Get product profile
        $profile_table = $wpdb->prefix . 'pls_product_profile';
        $profile = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$profile_table} WHERE product_id = %d LIMIT 1",
            $pls_product->id
        ) );

        // Get key ingredients
        $ingredients = wp_get_object_terms( $pls_product->id, 'pls_ingredient' );

        ?>
        <div class="pls-product-info">
            <?php if ( $settings['show_description'] === 'yes' && ! empty( $profile->description ) ) : ?>
                <div class="pls-product-description">
                    <?php echo wp_kses_post( wpautop( $profile->description ) ); ?>
                </div>
            <?php elseif ( $settings['show_description'] === 'yes' && $product->get_description() ) : ?>
                <div class="pls-product-description">
                    <?php echo wp_kses_post( wpautop( $product->get_description() ) ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $settings['show_directions'] === 'yes' && ! empty( $profile->directions ) ) : ?>
                <div class="pls-product-directions">
                    <h3><?php esc_html_e( 'Directions for Use', 'pls-private-label-store' ); ?></h3>
                    <?php echo wp_kses_post( wpautop( $profile->directions ) ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $settings['show_skin_types'] === 'yes' && ! empty( $profile->skin_types ) ) : ?>
                <div class="pls-product-skin-types">
                    <h3><?php esc_html_e( 'Skin Type', 'pls-private-label-store' ); ?></h3>
                    <p><?php echo esc_html( $profile->skin_types ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $settings['show_ingredients'] === 'yes' && ! empty( $ingredients ) && ! is_wp_error( $ingredients ) ) : ?>
                <div class="pls-product-ingredients">
                    <h3><?php esc_html_e( 'Key Ingredients', 'pls-private-label-store' ); ?></h3>
                    <ul>
                        <?php foreach ( $ingredients as $ingredient ) : ?>
                            <li>
                                <strong><?php echo esc_html( $ingredient->name ); ?></strong>
                                <?php if ( ! empty( $ingredient->description ) ) : ?>
                                    <span class="pls-ingredient-desc"><?php echo esc_html( $ingredient->description ); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
