<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get initial data
$primary_attr = PLS_Repo_Attributes::get_primary_attribute();
$product_options = PLS_Repo_Attributes::get_product_options();
$ingredients = get_terms( array( 'taxonomy' => 'pls_ingredient', 'hide_empty' => false ) );
if ( is_wp_error( $ingredients ) ) {
    $ingredients = array();
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : ( ! empty( $product_options ) ? 'option-' . $product_options[0]->id : 'ingredients' );

require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
?>
<div class="wrap pls-wrap pls-product-options" id="pls-product-options-page">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <h1 style="margin: 0;"><?php esc_html_e( 'Product Options', 'pls-private-label-store' ); ?></h1>
        <button type="button" class="button button-primary pls-open-pack-tier-modal" style="display: flex; align-items: center; gap: 6px;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 18px; width: 18px; height: 18px;"></span>
            <?php esc_html_e( 'Pack Tier Defaults', 'pls-private-label-store' ); ?>
        </button>
    </div>

    <div id="pls-notice-container"></div>

    <!-- Tabs Navigation -->
    <nav class="nav-tab-wrapper pls-options-tabs" style="margin: 20px 0 0; border-bottom: 2px solid var(--pls-accent);">
        <?php foreach ( $product_options as $option ) : ?>
            <a href="<?php echo esc_url( add_query_arg( 'tab', 'option-' . $option->id, admin_url( 'admin.php?page=pls-attributes' ) ) ); ?>" 
               class="nav-tab pls-option-tab <?php echo $active_tab === 'option-' . $option->id ? 'nav-tab-active' : ''; ?>"
               data-option-id="<?php echo esc_attr( $option->id ); ?>"
               style="border-bottom: 2px solid transparent; margin-bottom: -2px; transition: none;">
                <?php echo esc_html( $option->label ); ?>
            </a>
        <?php endforeach; ?>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'ingredients', admin_url( 'admin.php?page=pls-attributes' ) ) ); ?>" 
           class="nav-tab <?php echo $active_tab === 'ingredients' ? 'nav-tab-active' : ''; ?>"
           style="border-bottom: 2px solid transparent; margin-bottom: -2px; transition: none;">
            <span class="pls-tier-badge" style="background: #6366f1; color: #fff; padding: 2px 6px; border-radius: 2px; font-size: 9px; margin-right: 6px;">T3+</span>
            <?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?>
        </a>
    </nav>

    <!-- Pack Tier Modal -->
    <div id="pls-pack-tier-modal" class="pls-modal" style="display: none;">
        <div class="pls-modal__dialog" style="max-width: 900px;">
            <div class="pls-modal__head">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <span class="pls-primary-badge" style="background: #2271b1; color: #fff; padding: 4px 10px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'PRIMARY OPTION', 'pls-private-label-store' ); ?></span>
                    <?php esc_html_e( 'Pack Tier Defaults', 'pls-private-label-store' ); ?>
                </h2>
                <button type="button" class="pls-modal__close pls-close-pack-tier-modal" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 999px; width: 36px; height: 36px; cursor: pointer; font-size: 20px; line-height: 1;">&times;</button>
            </div>
            <div class="pls-modal__content" style="padding: 20px 0;">
                <?php if ( $primary_attr ) : ?>
                    <?php
                    $tier_values = PLS_Repo_Attributes::values_for_attr( $primary_attr->id );
                    ?>
                    <p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'These are the default values used when creating products. You can override them per product.', 'pls-private-label-store' ); ?></p>
                    
                    <form id="pls-pack-tier-form">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 20%;"><?php esc_html_e( 'Tier', 'pls-private-label-store' ); ?></th>
                                    <th style="width: 20%;"><?php esc_html_e( 'Default Units', 'pls-private-label-store' ); ?></th>
                                    <th style="width: 20%;"><?php esc_html_e( 'Default Price/Unit', 'pls-private-label-store' ); ?></th>
                                    <th style="width: 20%;"><?php esc_html_e( 'Total Price', 'pls-private-label-store' ); ?></th>
                                    <th style="width: 20%;"><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Fallback defaults matching product creation modal
                                $fallback_defaults = array(
                                    1 => array( 'units' => 50, 'price' => 15.90 ),
                                    2 => array( 'units' => 100, 'price' => 14.50 ),
                                    3 => array( 'units' => 250, 'price' => 12.50 ),
                                    4 => array( 'units' => 500, 'price' => 9.50 ),
                                    5 => array( 'units' => 1000, 'price' => 7.90 ),
                                );
                                foreach ( $tier_values as $tier_value ) : ?>
                                    <?php
                                    $tier_level = PLS_Tier_Rules::get_tier_level_from_value( $tier_value->id );
                                    $default_units = PLS_Tier_Rules::get_default_units_for_tier( $tier_value->id );
                                    $default_price = PLS_Tier_Rules::get_default_price_per_unit( $tier_value->id );
                                    
                                    // Use fallback defaults if values not found in database
                                    if ( false === $default_units && $tier_level && isset( $fallback_defaults[ $tier_level ] ) ) {
                                        $default_units = $fallback_defaults[ $tier_level ]['units'];
                                    }
                                    if ( false === $default_price && $tier_level && isset( $fallback_defaults[ $tier_level ] ) ) {
                                        $default_price = $fallback_defaults[ $tier_level ]['price'];
                                    }
                                    
                                    $total_price = ( $default_units && $default_price ) ? $default_units * $default_price : 0;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_html( $tier_value->label ); ?></strong></td>
                                        <td>
                                            <input type="number" 
                                                   name="tier_prices[<?php echo esc_attr( $tier_value->id ); ?>][units]" 
                                                   value="<?php echo esc_attr( $default_units ?: '' ); ?>" 
                                                   min="1" 
                                                   class="pls-tier-units"
                                                   style="width: 100px;" />
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="tier_prices[<?php echo esc_attr( $tier_value->id ); ?>][price]" 
                                                   value="<?php echo esc_attr( $default_price ?: '' ); ?>" 
                                                   step="0.01" 
                                                   min="0" 
                                                   class="pls-tier-price"
                                                   style="width: 100px;" />
                                        </td>
                                        <td>
                                            <strong style="color: #2271b1;">$<span class="pls-tier-total-calc"><?php echo number_format( $total_price, 2 ); ?></span></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $descriptions = array(
                                                1 => __( 'Standard formula only', 'pls-private-label-store' ),
                                                2 => __( 'Standard formula only', 'pls-private-label-store' ),
                                                3 => __( 'Access to customisation (peptides, fragrances, actives)', 'pls-private-label-store' ),
                                                4 => __( 'Full Customisation Included', 'pls-private-label-store' ),
                                                5 => __( 'Full Customisation + bulk discount', 'pls-private-label-store' ),
                                            );
                                            echo esc_html( isset( $descriptions[ $tier_level ] ) ? $descriptions[ $tier_level ] : '' );
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p style="margin-top: 20px; text-align: right;">
                            <button type="button" class="button pls-close-pack-tier-modal" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Default Values', 'pls-private-label-store' ); ?></button>
                        </p>
                    </form>
                <?php else : ?>
                    <p><?php esc_html_e( 'Pack Tier attribute not found. Please activate the plugin to create default attributes.', 'pls-private-label-store' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        </div>

    <!-- Label Application Pricing Section -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-top: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <div>
                <h2 style="margin: 0; font-size: 18px; font-weight: 600;"><?php esc_html_e( 'Label Application Pricing', 'pls-private-label-store' ); ?></h2>
                <p class="description" style="margin: 4px 0 0;"><?php esc_html_e( 'Set automatic pricing for label application based on tier. Tier 3-5 are automatically FREE.', 'pls-private-label-store' ); ?></p>
            </div>
        </div>
        
        <?php
        $label_price = get_option( 'pls_label_price_tier_1_2', '0.50' );
        ?>
        
        <form id="pls-label-pricing-form" style="max-width: 500px;">
            <div class="pls-input-group" style="margin-bottom: 16px;">
                <label for="label_price_tier_1_2" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php esc_html_e( 'Tier 1-2 Price:', 'pls-private-label-store' ); ?></label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--pls-gray-600); font-size: 16px;">$</span>
                    <input type="number" step="0.01" id="label_price_tier_1_2" name="label_price_tier_1_2" 
                           value="<?php echo esc_attr( $label_price ); ?>" 
                           class="pls-input" style="width: 150px;" min="0" />
                    <span class="description" style="font-size: 13px; color: var(--pls-gray-500);"><?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?></span>
                </div>
                <p class="description" style="margin-top: 8px;"><?php esc_html_e( 'This price will be multiplied by the number of units in the pack tier for Tier 1 and Tier 2 orders.', 'pls-private-label-store' ); ?></p>
            </div>
            
            <div style="padding: 12px; background: var(--pls-success-light); border-radius: 8px; margin-bottom: 16px;">
                <strong style="color: var(--pls-success);"><?php esc_html_e( 'Tier 3-5: FREE', 'pls-private-label-store' ); ?></strong>
                <span style="margin-left: 8px; color: var(--pls-gray-500); font-size: 13px;">
                    <?php esc_html_e( '(automatically applied)', 'pls-private-label-store' ); ?>
                </span>
            </div>
            
            <div style="text-align: right;">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Label Pricing', 'pls-private-label-store' ); ?></button>
            </div>
        </form>
    </div>

    <!-- Add/Edit Option Modal -->
    <div id="pls-option-modal" class="pls-modal" style="display: none;">
        <div class="pls-modal__dialog" style="max-width: 600px;">
            <div class="pls-modal__head">
                <h2 id="pls-option-modal-title" style="margin: 0;"><?php esc_html_e( 'Add Product Option', 'pls-private-label-store' ); ?></h2>
                <button type="button" class="pls-modal__close pls-close-option-modal">&times;</button>
            </div>
            <div class="pls-modal__content" style="padding: 20px;">
                <form id="pls-option-form">
                    <input type="hidden" id="pls-option-id" name="option_id" value="" />
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Option Name', 'pls-private-label-store' ); ?></label>
                        <input type="text" id="pls-option-label" name="label" class="regular-text" required style="width: 100%;" />
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label><input type="checkbox" id="pls-option-variation" name="is_variation" value="1" checked /> <?php esc_html_e( 'For variations', 'pls-private-label-store' ); ?></label>
                    </div>
                    <p style="text-align: right; margin-top: 20px;">
                        <button type="button" class="button pls-close-option-modal" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'pls-private-label-store' ); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit Value Modal -->
    <div id="pls-value-modal" class="pls-modal" style="display: none;">
        <div class="pls-modal__dialog" style="max-width: 600px;">
            <div class="pls-modal__head">
                <h2 id="pls-value-modal-title" style="margin: 0;"><?php esc_html_e( 'Add Value', 'pls-private-label-store' ); ?></h2>
                <button type="button" class="pls-modal__close pls-close-value-modal">&times;</button>
            </div>
            <div class="pls-modal__content" style="padding: 20px;">
                <form id="pls-value-form">
                    <input type="hidden" id="pls-value-id" name="value_id" value="" />
                    <input type="hidden" id="pls-value-attribute-id" name="attribute_id" value="" />
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Value Label', 'pls-private-label-store' ); ?></label>
                        <input type="text" id="pls-value-label" name="label" class="regular-text" required style="width: 100%;" />
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Minimum Tier', 'pls-private-label-store' ); ?></label>
                        <select id="pls-value-min-tier" name="min_tier_level" style="width: 100%;">
                            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Price Impact', 'pls-private-label-store' ); ?></label>
                        <input type="number" id="pls-value-price" name="price" step="0.01" min="0" value="0" style="width: 100%;" />
                    </div>
                    <p style="text-align: right; margin-top: 20px;">
                        <button type="button" class="button pls-close-value-modal" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'pls-private-label-store' ); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- Product Options Tabs Content -->
    <div id="pls-tab-content-container" style="margin-top: 20px;">
        <?php if ( strpos( $active_tab, 'option-' ) === 0 ) : ?>
            <?php
            $option_id = absint( str_replace( 'option-', '', $active_tab ) );
            $current_option = null;
            foreach ( $product_options as $opt ) {
                if ( (int) $opt->id === $option_id ) {
                    $current_option = $opt;
                    break;
                }
            }
            ?>
            <?php if ( $current_option ) : ?>
                <div class="tab-content pls-tab-content" data-option-id="<?php echo esc_attr( $current_option->id ); ?>">
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                            <h2 style="margin: 0;"><?php echo esc_html( $current_option->label ); ?></h2>
                            <div>
                                <button type="button" class="button pls-edit-option" data-option-id="<?php echo esc_attr( $current_option->id ); ?>" data-option-label="<?php echo esc_attr( $current_option->label ); ?>"><?php esc_html_e( 'Edit', 'pls-private-label-store' ); ?></button>
                                <button type="button" class="button button-link-delete pls-delete-option" data-option-id="<?php echo esc_attr( $current_option->id ); ?>" style="color: #b32d2e;"><?php esc_html_e( 'Delete', 'pls-private-label-store' ); ?></button>
                            </div>
                        </div>
                        
                        <?php $values = PLS_Repo_Attributes::values_for_attr( $current_option->id ); ?>
                        
                        <div class="pls-table-modern pls-table-modern--compact">
                            <table>
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Value', 'pls-private-label-store' ); ?></th>
                                        <th style="width: 120px;"><?php esc_html_e( 'Min Tier', 'pls-private-label-store' ); ?></th>
                                        <th style="width: 120px;"><?php esc_html_e( 'Price Impact', 'pls-private-label-store' ); ?></th>
                                        <th style="width: 150px;"><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="pls-values-list-<?php echo esc_attr( $current_option->id ); ?>">
                                <?php foreach ( $values as $value ) : ?>
                                    <?php
                                    $price_meta = $value->term_id ? get_term_meta( $value->term_id, '_pls_default_price_impact', true ) : '';
                                    $price_display = '' !== $price_meta ? floatval( $price_meta ) : 0;
                                    ?>
                                    <tr data-value-id="<?php echo esc_attr( $value->id ); ?>">
                                        <td><strong><?php echo esc_html( $value->label ); ?></strong></td>
                                        <td>T<?php echo esc_html( $value->min_tier_level ); ?></td>
                                        <td>$<?php echo number_format( $price_display, 2 ); ?></td>
                                        <td>
                                            <button type="button" class="button button-small pls-edit-value" 
                                                    data-value-id="<?php echo esc_attr( $value->id ); ?>"
                                                    data-value-label="<?php echo esc_attr( $value->label ); ?>"
                                                    data-value-min-tier="<?php echo esc_attr( $value->min_tier_level ); ?>"
                                                    data-value-price="<?php echo esc_attr( $price_display ); ?>"
                                                    data-attribute-id="<?php echo esc_attr( $current_option->id ); ?>">
                                                <?php esc_html_e( 'Edit', 'pls-private-label-store' ); ?>
                                            </button>
                                            <button type="button" class="button button-small button-link-delete pls-delete-value" 
                                                    data-value-id="<?php echo esc_attr( $value->id ); ?>"
                                                    style="color: #b32d2e;">
                                                <?php esc_html_e( 'Delete', 'pls-private-label-store' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p style="margin-top: 15px;">
                            <button type="button" class="button button-primary pls-add-value" data-attribute-id="<?php echo esc_attr( $current_option->id ); ?>">
                                <?php esc_html_e( 'Add New Value', 'pls-private-label-store' ); ?>
                            </button>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

        <!-- Ingredients Tab -->
        <?php elseif ( $active_tab === 'ingredients' ) : ?>
            <div class="tab-content pls-tab-content">
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <h2 style="margin: 0; font-size: 18px;"><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></h2>
                        <span class="pls-tier-badge" style="background: #6366f1; color: #fff; padding: 4px 10px; border-radius: 3px; font-size: 11px; font-weight: 600;"><?php esc_html_e( 'Tier 3+', 'pls-private-label-store' ); ?></span>
                        <span class="pls-badge" style="background: #f0f0f1; color: #50575e; padding: 4px 10px; border-radius: 3px; font-size: 11px; font-weight: 600;"><?php echo count( $ingredients ); ?> <?php esc_html_e( 'ingredients', 'pls-private-label-store' ); ?></span>
                    </div>

                    <?php if ( empty( $ingredients ) ) : ?>
                        <p><?php esc_html_e( 'No ingredients yet.', 'pls-private-label-store' ); ?></p>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 50px;"><?php esc_html_e( 'Icon', 'pls-private-label-store' ); ?></th>
                                    <th style="width: 25%;"><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></th>
                                    <th><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $ingredients as $ingredient ) : ?>
                                    <?php
                                    $icon    = PLS_Taxonomies::icon_for_term( $ingredient->term_id );
                                    $short   = get_term_meta( $ingredient->term_id, 'pls_ingredient_short_desc', true );
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="width: 32px; height: 32px; border-radius: 4px; background: #f0f0f1; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                                <?php if ( $icon ) : ?>
                                                    <img src="<?php echo esc_url( $icon ); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;" />
                                                <?php else : ?>
                                                    <span style="color: #999;">📦</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><strong><?php echo esc_html( $ingredient->name ); ?></strong></td>
                                        <td><?php echo esc_html( $short ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Add New Option -->
    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin-top: 20px;">
        <h3 style="margin-top: 0;"><?php esc_html_e( 'Add New Product Option', 'pls-private-label-store' ); ?></h3>
        <button type="button" class="button button-primary pls-add-option"><?php esc_html_e( 'Add New Option', 'pls-private-label-store' ); ?></button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php';
    var nonce = (window.PLS_Admin && PLS_Admin.nonce) ? PLS_Admin.nonce : '';

    // Show notice
    function showNotice(message, type) {
        type = type || 'success';
        var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('#pls-notice-container').html(notice);
        setTimeout(function() { notice.fadeOut(); }, 5000);
    }

    // Refresh page data
    function refreshPageData() {
        $.post(ajaxurl, {
            action: 'pls_get_product_options_data',
            nonce: nonce
        }, function(resp) {
            if (resp.success) {
                // Reload page to show updated data
                window.location.reload();
            }
        });
    }

    // Pack Tier Modal handlers
    $('.pls-open-pack-tier-modal').on('click', function() {
        $('#pls-pack-tier-modal').fadeIn(200);
        $('body').addClass('pls-modal-open');
    });
    
    $('.pls-close-pack-tier-modal, #pls-pack-tier-modal').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('pls-close-pack-tier-modal')) {
            $('#pls-pack-tier-modal').fadeOut(200);
            $('body').removeClass('pls-modal-open');
        }
    });
    
    $('#pls-pack-tier-modal .pls-modal__dialog').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Pack Tier form submit
    $('#pls-pack-tier-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serializeArray();
        var tierPrices = {};
        formData.forEach(function(item) {
            var match = item.name.match(/tier_prices\[(\d+)\]\[(\w+)\]/);
            if (match) {
                var valueId = match[1];
                var field = match[2];
                if (!tierPrices[valueId]) tierPrices[valueId] = {};
                tierPrices[valueId][field] = item.value;
            }
        });

        $.post(ajaxurl, {
            action: 'pls_update_pack_tier_defaults',
            nonce: nonce,
            tier_prices: tierPrices
        }, function(resp) {
            if (resp.success) {
                showNotice(resp.data.message);
                $('#pls-pack-tier-modal').fadeOut(200);
                $('body').removeClass('pls-modal-open');
                refreshPageData();
            } else {
                showNotice(resp.data.message || 'Error updating pack tier defaults', 'error');
            }
        });
    });
    
    // Auto-calculate total price
    $('#pls-pack-tier-modal').on('input', '.pls-tier-units, .pls-tier-price', function() {
        var row = $(this).closest('tr');
        var units = parseFloat(row.find('.pls-tier-units').val()) || 0;
        var price = parseFloat(row.find('.pls-tier-price').val()) || 0;
        var total = units * price;
        row.find('.pls-tier-total-calc').text(total.toFixed(2));
    });

    // Option Modal handlers
    $('.pls-add-option').on('click', function() {
        $('#pls-option-modal-title').text('Add Product Option');
        $('#pls-option-id').val('');
        $('#pls-option-label').val('');
        $('#pls-option-variation').prop('checked', true);
        $('#pls-option-modal').fadeIn(200);
        $('body').addClass('pls-modal-open');
    });

    $('.pls-edit-option').on('click', function() {
        var optionId = $(this).data('option-id');
        var optionLabel = $(this).data('option-label');
        $('#pls-option-modal-title').text('Edit Product Option');
        $('#pls-option-id').val(optionId);
        $('#pls-option-label').val(optionLabel);
        $('#pls-option-modal').fadeIn(200);
        $('body').addClass('pls-modal-open');
    });

    $('.pls-close-option-modal, #pls-option-modal').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('pls-close-option-modal')) {
            $('#pls-option-modal').fadeOut(200);
            $('body').removeClass('pls-modal-open');
        }
    });

    $('#pls-option-modal .pls-modal__dialog').on('click', function(e) {
        e.stopPropagation();
    });

    // Option form submit
    $('#pls-option-form').on('submit', function(e) {
        e.preventDefault();
        var optionId = $('#pls-option-id').val();
        var isEdit = optionId !== '';

        if (isEdit) {
            // For edit, we'd need an update endpoint - for now, show message
            showNotice('Edit functionality coming soon. Please delete and recreate.', 'info');
            $('#pls-option-modal').fadeOut(200);
            $('body').removeClass('pls-modal-open');
            return;
        }

        $.post(ajaxurl, {
            action: 'pls_create_attribute',
            nonce: nonce,
            label: $('#pls-option-label').val(),
            is_variation: $('#pls-option-variation').is(':checked') ? 1 : 0,
            option_type: 'product-option'
        }, function(resp) {
            if (resp.success) {
                showNotice('Option created successfully');
                $('#pls-option-modal').fadeOut(200);
                $('body').removeClass('pls-modal-open');
                refreshPageData();
            } else {
                showNotice(resp.data.message || 'Error creating option', 'error');
            }
        });
    });

    // Delete option
    $(document).on('click', '.pls-delete-option', function() {
        if (!confirm('Are you sure you want to delete this option? All values will be deleted too.')) {
            return;
        }
        var optionId = $(this).data('option-id');
        $.post(ajaxurl, {
            action: 'pls_delete_attribute',
            nonce: nonce,
            attribute_id: optionId
        }, function(resp) {
            if (resp.success) {
                showNotice('Option deleted successfully');
                refreshPageData();
            } else {
                showNotice(resp.data.message || 'Error deleting option', 'error');
            }
        });
    });

    // Value Modal handlers
    $(document).on('click', '.pls-add-value', function() {
        var attributeId = $(this).data('attribute-id');
        $('#pls-value-modal-title').text('Add Value');
        $('#pls-value-id').val('');
        $('#pls-value-attribute-id').val(attributeId);
        $('#pls-value-label').val('');
        $('#pls-value-min-tier').val('1');
        $('#pls-value-price').val('0');
        $('#pls-value-modal').fadeIn(200);
        $('body').addClass('pls-modal-open');
    });

    $(document).on('click', '.pls-edit-value', function() {
        var valueId = $(this).data('value-id');
        var valueLabel = $(this).data('value-label');
        var valueMinTier = $(this).data('value-min-tier');
        var valuePrice = $(this).data('value-price');
        var attributeId = $(this).data('attribute-id');
        $('#pls-value-modal-title').text('Edit Value');
        $('#pls-value-id').val(valueId);
        $('#pls-value-attribute-id').val(attributeId);
        $('#pls-value-label').val(valueLabel);
        $('#pls-value-min-tier').val(valueMinTier);
        $('#pls-value-price').val(valuePrice);
        $('#pls-value-modal').fadeIn(200);
        $('body').addClass('pls-modal-open');
    });

    $('.pls-close-value-modal, #pls-value-modal').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('pls-close-value-modal')) {
            $('#pls-value-modal').fadeOut(200);
            $('body').removeClass('pls-modal-open');
        }
    });

    $('#pls-value-modal .pls-modal__dialog').on('click', function(e) {
        e.stopPropagation();
    });

    // Value form submit
    $('#pls-value-form').on('submit', function(e) {
        e.preventDefault();
        var valueId = $('#pls-value-id').val();
        var isEdit = valueId !== '';

        if (isEdit) {
            $.post(ajaxurl, {
                action: 'pls_update_attribute_value',
                nonce: nonce,
                value_id: valueId,
                label: $('#pls-value-label').val(),
                min_tier_level: $('#pls-value-min-tier').val(),
                price: $('#pls-value-price').val()
            }, function(resp) {
                if (resp.success) {
                    showNotice('Value updated successfully');
                    $('#pls-value-modal').fadeOut(200);
                    $('body').removeClass('pls-modal-open');
                    refreshPageData();
                } else {
                    showNotice(resp.data.message || 'Error updating value', 'error');
                }
            });
        } else {
            $.post(ajaxurl, {
                action: 'pls_create_attribute_value',
                nonce: nonce,
                attribute_id: $('#pls-value-attribute-id').val(),
                label: $('#pls-value-label').val(),
                min_tier_level: $('#pls-value-min-tier').val(),
                price: $('#pls-value-price').val()
            }, function(resp) {
                if (resp.success) {
                    showNotice('Value created successfully');
                    $('#pls-value-modal').fadeOut(200);
                    $('body').removeClass('pls-modal-open');
                    refreshPageData();
                } else {
                    showNotice(resp.data.message || 'Error creating value', 'error');
                }
            });
        }
    });

    // Delete value
    $(document).on('click', '.pls-delete-value', function() {
        if (!confirm('Are you sure you want to delete this value?')) {
            return;
        }
        var valueId = $(this).data('value-id');
        $.post(ajaxurl, {
            action: 'pls_delete_attribute_value',
            nonce: nonce,
            value_id: valueId
        }, function(resp) {
            if (resp.success) {
                showNotice('Value deleted successfully');
                refreshPageData();
            } else {
                showNotice(resp.data.message || 'Error deleting value', 'error');
            }
        });
    });
    
    // Label Pricing form submit
    $('#pls-label-pricing-form').on('submit', function(e) {
        e.preventDefault();
        var labelPrice = parseFloat($('#label_price_tier_1_2').val()) || 0;
        if (labelPrice < 0) {
            labelPrice = 0;
        }
        
        $.post(ajaxurl, {
            action: 'pls_update_label_pricing',
            nonce: nonce,
            label_price_tier_1_2: labelPrice
        }, function(resp) {
            if (resp.success) {
                showNotice(resp.data.message || 'Label pricing saved successfully');
            } else {
                showNotice(resp.data.message || 'Error saving label pricing', 'error');
            }
        });
    });
});
</script>

<style>
.pls-product-options .pls-primary-section { border-left: 4px solid var(--pls-accent) !important; }
.pls-product-options .pls-primary-badge { text-transform: uppercase; font-weight: 600; background: var(--pls-accent); color: #fff; padding: 4px 10px; border-radius: 3px; font-size: 11px; }
.pls-product-options .pls-options-tabs { border-bottom: 2px solid var(--pls-accent); }
.pls-product-options .pls-options-tabs .nav-tab { margin-bottom: -2px; border-bottom: 2px solid transparent; transition: none; }
.pls-product-options .pls-options-tabs .nav-tab-active { border-bottom-color: var(--pls-accent); border-bottom-width: 2px; }
.pls-product-options .pls-tab-content { min-height: 400px; }
.pls-product-options table { margin-top: 0; }
@media (max-width: 782px) {
    .pls-product-options form[style*="grid"] { grid-template-columns: 1fr !important; }
    .pls-product-options .pls-options-tabs .nav-tab { font-size: 12px; padding: 8px 10px; }
}
</style>
