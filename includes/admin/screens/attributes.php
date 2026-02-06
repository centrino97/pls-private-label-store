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

// v5.7.0: Option category definitions for standardized grouping
$option_categories = array(
    'package-config' => array(
        'label'       => __( 'Package Configuration', 'pls-private-label-store' ),
        'icon'        => 'dashicons-archive',
        'color'       => '#2271b1',
        'description' => __( 'Container type, glass finish, and cap/applicator options. These define the physical package.', 'pls-private-label-store' ),
        'keywords'    => array( 'package type', 'package color', 'package colour', 'package cap', 'container', 'applicator', 'bottle', 'jar' ),
    ),
    'premium'        => array(
        'label'       => __( 'Premium Options', 'pls-private-label-store' ),
        'icon'        => 'dashicons-star-filled',
        'color'       => '#6366f1',
        'description' => __( 'Value-add options like fragrance, custom printing, and luxury packaging. Often tier-restricted.', 'pls-private-label-store' ),
        'keywords'    => array( 'fragrance', 'custom printed', 'external box', 'premium', 'luxury' ),
    ),
    'label'          => array(
        'label'       => __( 'Label Application', 'pls-private-label-store' ),
        'icon'        => 'dashicons-tag',
        'color'       => '#0d9488',
        'description' => __( 'Label options: No Labels, Professional Application, or DIY. Pricing controlled globally.', 'pls-private-label-store' ),
        'keywords'    => array( 'label application', 'label' ),
    ),
    'other'          => array(
        'label'       => __( 'Other', 'pls-private-label-store' ),
        'icon'        => 'dashicons-admin-generic',
        'color'       => '#64748b',
        'description' => __( 'Additional product options that don\'t fit other categories.', 'pls-private-label-store' ),
        'keywords'    => array(),
    ),
);

/**
 * Detect the category for a product option based on its label/key.
 */
function pls_detect_option_category( $option, $categories ) {
    $label_lower = strtolower( $option->label );
    $attr_key    = isset( $option->attr_key ) ? strtolower( $option->attr_key ) : '';

    foreach ( $categories as $cat_key => $cat ) {
        if ( 'other' === $cat_key ) {
            continue;
        }
        foreach ( $cat['keywords'] as $keyword ) {
            if ( false !== strpos( $label_lower, $keyword ) || false !== strpos( $attr_key, sanitize_title( $keyword ) ) ) {
                return $cat_key;
            }
        }
    }
    return 'other';
}

// Group options by category
$options_by_category = array();
foreach ( $option_categories as $cat_key => $cat_def ) {
    $options_by_category[ $cat_key ] = array();
}
foreach ( $product_options as $option ) {
    $cat = pls_detect_option_category( $option, $option_categories );
    $options_by_category[ $cat ][] = $option;
}
?>
<div class="wrap pls-wrap pls-product-options" id="pls-product-options-page">
    <!-- v5.7.0: Enhanced Header with description and stats -->
    <div class="pls-page-head" style="margin-bottom: 16px;">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Configuration', 'pls-private-label-store' ); ?></p>
            <h1 style="margin: 4px 0;"><?php esc_html_e( 'Product Options', 'pls-private-label-store' ); ?></h1>
            <p class="description" style="margin-top: 4px;">
                <?php esc_html_e( 'Manage all product options from this central page. Options are grouped by category and referenced by products.', 'pls-private-label-store' ); ?>
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <?php
            // Stats badges
            $total_options = count( $product_options );
            $total_values  = 0;
            foreach ( $product_options as $_opt ) {
                $total_values += count( PLS_Repo_Attributes::values_for_attr( $_opt->id ) );
            }
            ?>
            <span class="pls-badge pls-badge--info" style="font-size: 11px;"><?php echo esc_html( $total_options ); ?> <?php esc_html_e( 'options', 'pls-private-label-store' ); ?></span>
            <span class="pls-badge" style="font-size: 11px;"><?php echo esc_html( $total_values ); ?> <?php esc_html_e( 'values', 'pls-private-label-store' ); ?></span>
            <span class="pls-help-icon" title="<?php esc_attr_e( 'Product options define the attributes customers can select when ordering. Pack Tier is the primary option that controls pricing tiers. Options created here are available to all products.', 'pls-private-label-store' ); ?>" style="cursor: help;">ⓘ</span>
            <button type="button" class="button pls-open-pack-tier-modal" style="display: flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-admin-settings" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <?php esc_html_e( 'Pack Tier Defaults', 'pls-private-label-store' ); ?>
            </button>
            <button type="button" class="button button-primary pls-add-option" style="display: flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <?php esc_html_e( 'Add New Option', 'pls-private-label-store' ); ?>
            </button>
        </div>
    </div>

    <div id="pls-notice-container"></div>

    <!-- v5.7.0: Category-grouped Tabs Navigation -->
    <nav class="nav-tab-wrapper pls-options-tabs" style="margin: 20px 0 0; border-bottom: 2px solid var(--pls-accent); display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0;">
        <?php
        $first_tab = true;
        foreach ( $options_by_category as $cat_key => $cat_options ) :
            if ( empty( $cat_options ) ) {
                continue;
            }
            $cat_def = $option_categories[ $cat_key ];
            if ( ! $first_tab ) : ?>
                <span class="pls-tab-separator" style="display: inline-block; width: 1px; height: 20px; background: #e2e8f0; margin: 0 4px 8px;"></span>
            <?php endif;
            $first_tab = false;
            foreach ( $cat_options as $option ) :
                $option_min_tier = isset( $option->default_min_tier ) ? intval( $option->default_min_tier ) : 1;
            ?>
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'option-' . $option->id, admin_url( 'admin.php?page=pls-attributes' ) ) ); ?>" 
                   class="nav-tab pls-option-tab <?php echo $active_tab === 'option-' . $option->id ? 'nav-tab-active' : ''; ?>"
                   data-option-id="<?php echo esc_attr( $option->id ); ?>"
                   data-category="<?php echo esc_attr( $cat_key ); ?>"
                   style="border-bottom: 2px solid transparent; margin-bottom: -2px; transition: none;">
                    <?php if ( $option_min_tier > 1 ) : ?>
                        <span class="pls-tier-badge" style="background: <?php echo $option_min_tier >= 4 ? '#ef4444' : '#6366f1'; ?>; color: #fff; padding: 2px 6px; border-radius: 2px; font-size: 9px; margin-right: 6px;">T<?php echo esc_html( $option_min_tier ); ?>+</span>
                    <?php endif; ?>
                    <?php echo esc_html( $option->label ); ?>
                </a>
            <?php endforeach;
        endforeach; ?>
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
                            <strong style="color: #2271b1;">A$<span class="pls-tier-total-calc"><?php echo number_format( $total_price, 2 ); ?></span></strong>
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

    <?php
    // v5.5.2: Simplified Label Application Pricing - only show on Label Application tab
    $label_app_option_id = null;
    foreach ( $product_options as $opt ) {
        if ( stripos( $opt->label, 'label application' ) !== false ) {
            $label_app_option_id = $opt->id;
            break;
        }
    }
    $is_label_app_tab = $label_app_option_id && $active_tab === 'option-' . $label_app_option_id;
    
    if ( $is_label_app_tab ) :
    $label_fee_tier_1_2 = get_option( 'pls_label_fee_tier_1_2', '0.30' );
    $label_application_fee_tier_1_2 = get_option( 'pls_label_application_fee_tier_1_2', '0.25' );
    ?>
    <!-- Label Application Pricing - v5.5.2 Simplified Table -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-top: 24px;">
        <h2 style="margin: 0 0 16px; font-size: 16px; font-weight: 600;"><?php esc_html_e( 'Label Pricing (Tier 1-2 Only)', 'pls-private-label-store' ); ?></h2>
        
        <form id="pls-label-pricing-form">
            <table class="wp-list-table widefat fixed striped" style="max-width: 500px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Fee Type', 'pls-private-label-store' ); ?></th>
                        <th style="width: 150px;"><?php esc_html_e( 'Price per Unit', 'pls-private-label-store' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong><?php esc_html_e( 'Label Fee', 'pls-private-label-store' ); ?></strong>
                            <p class="description" style="margin: 2px 0 0;"><?php esc_html_e( 'Cost for providing custom labels', 'pls-private-label-store' ); ?></p>
                        </td>
                        <td>
                            <div class="pls-input-with-prefix" style="max-width: 120px;">
                                <span class="pls-input-prefix">A$</span>
                                <input type="number" step="0.01" id="label_fee_tier_1_2" name="label_fee_tier_1_2" 
                                       value="<?php echo esc_attr( $label_fee_tier_1_2 ); ?>" class="pls-input" min="0" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong><?php esc_html_e( 'Professional Application Fee', 'pls-private-label-store' ); ?></strong>
                            <p class="description" style="margin: 2px 0 0;"><?php esc_html_e( 'Additional cost for applying labels', 'pls-private-label-store' ); ?></p>
                        </td>
                        <td>
                            <div class="pls-input-with-prefix" style="max-width: 120px;">
                                <span class="pls-input-prefix">A$</span>
                                <input type="number" step="0.01" id="label_application_fee_tier_1_2" name="label_application_fee_tier_1_2" 
                                       value="<?php echo esc_attr( $label_application_fee_tier_1_2 ); ?>" class="pls-input" min="0" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 16px; padding: 10px 14px; background: var(--pls-success-light); border-radius: 6px; display: inline-block;">
                <strong style="color: var(--pls-success);"><?php esc_html_e( 'Tier 3-5: FREE', 'pls-private-label-store' ); ?></strong>
                <span style="margin-left: 8px; color: var(--pls-gray-500); font-size: 12px;"><?php esc_html_e( '(labels + application)', 'pls-private-label-store' ); ?></span>
            </div>
            
            <p style="margin-top: 16px;">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Pricing', 'pls-private-label-store' ); ?></button>
            </p>
        </form>
    </div>
    <?php endif; ?>

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
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Option Name', 'pls-private-label-store' ); ?> <span class="pls-required-indicator">*</span></label>
                        <input type="text" id="pls-option-label" name="label" class="regular-text pls-input" required style="width: 100%;" placeholder="<?php esc_attr_e( 'e.g. Package Color, Cap Style, Fragrance', 'pls-private-label-store' ); ?>" />
                        <span class="pls-field-hint"><?php esc_html_e( 'Name displayed to customers during product configuration.', 'pls-private-label-store' ); ?></span>
                    </div>
                    <!-- v5.7.0: Option Category -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Category', 'pls-private-label-store' ); ?></label>
                        <select id="pls-option-category" name="option_category" class="pls-select" style="width: 100%;">
                            <?php foreach ( $option_categories as $cat_key => $cat_def ) : ?>
                                <option value="<?php echo esc_attr( $cat_key ); ?>">
                                    <?php echo esc_html( $cat_def['label'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="pls-field-hint"><?php esc_html_e( 'Helps organize options into logical groups. Auto-detected from name if left as "Other".', 'pls-private-label-store' ); ?></span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Default Minimum Tier', 'pls-private-label-store' ); ?></label>
                        <select id="pls-option-default-tier" name="default_min_tier" class="pls-select" style="width: 100%;">
                            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                <option value="<?php echo esc_attr( $i ); ?>">
                                    <?php 
                                    echo esc_html( sprintf( 
                                        __( 'Tier %d%s', 'pls-private-label-store' ), 
                                        $i, 
                                        $i === 1 ? ' (Available to all)' : '' 
                                    ) ); 
                                    ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <span class="pls-field-hint"><?php esc_html_e( 'Values inherit this tier by default (can be overridden per value).', 'pls-private-label-store' ); ?></span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label class="pls-toggle-switch">
                            <input type="checkbox" id="pls-option-variation" name="is_variation" value="1" checked />
                            <span class="pls-toggle-switch__slider"></span>
                            <span class="pls-toggle-switch__label"><?php esc_html_e( 'Use for WooCommerce variations', 'pls-private-label-store' ); ?></span>
                        </label>
                    </div>
                    <div class="pls-modal__footer" style="text-align: right; margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--pls-gray-200);">
                        <button type="button" class="button pls-close-option-modal" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Option', 'pls-private-label-store' ); ?></button>
                    </div>
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
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Value Label', 'pls-private-label-store' ); ?> <span class="pls-required-indicator">*</span></label>
                        <input type="text" id="pls-value-label" name="label" class="regular-text pls-input" required style="width: 100%;" placeholder="<?php esc_attr_e( 'e.g. White, Matte Black, Rose Gold', 'pls-private-label-store' ); ?>" />
                        <span class="pls-field-hint"><?php esc_html_e( 'The value name shown to customers.', 'pls-private-label-store' ); ?></span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Minimum Tier', 'pls-private-label-store' ); ?></label>
                        <select id="pls-value-min-tier" name="min_tier_level" class="pls-select" style="width: 100%;">
                            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                <option value="<?php echo esc_attr( $i ); ?>">
                                    <?php 
                                    $tier_desc = '';
                                    switch ( $i ) {
                                        case 1: $tier_desc = ' (50 units)'; break;
                                        case 2: $tier_desc = ' (100 units)'; break;
                                        case 3: $tier_desc = ' (250 units)'; break;
                                        case 4: $tier_desc = ' (500 units)'; break;
                                        case 5: $tier_desc = ' (1000 units)'; break;
                                    }
                                    echo esc_html( sprintf( __( 'Tier %d%s', 'pls-private-label-store' ), $i, $tier_desc ) );
                                    ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <span class="pls-field-hint"><?php esc_html_e( 'Customers need at least this tier to select this value.', 'pls-private-label-store' ); ?></span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Price Impact (AUD per unit)', 'pls-private-label-store' ); ?></label>
                        <div class="pls-input-with-prefix">
                            <span class="pls-input-prefix">A$</span>
                            <input type="number" id="pls-value-price" name="price" step="0.01" min="0" value="0.00" class="pls-input" placeholder="0.00" />
                        </div>
                        <span class="pls-field-hint"><?php esc_html_e( 'Additional cost per unit when this value is selected. Set to 0 for no extra charge.', 'pls-private-label-store' ); ?></span>
                    </div>
                    <div class="pls-modal__footer" style="text-align: right; margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--pls-gray-200);">
                        <button type="button" class="button pls-close-value-modal" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Value', 'pls-private-label-store' ); ?></button>
                    </div>
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
                <?php 
                // Get default_min_tier (with fallback to 1)
                $default_min_tier = isset( $current_option->default_min_tier ) ? intval( $current_option->default_min_tier ) : 1;
                // v5.7.0: Detect category for the current option
                $current_cat_key = pls_detect_option_category( $current_option, $option_categories );
                $current_cat_def = $option_categories[ $current_cat_key ];
                ?>
                <!-- v5.7.0: Category Info Banner -->
                <div class="pls-category-banner" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: <?php echo esc_attr( $current_cat_def['color'] ); ?>0D; border-left: 3px solid <?php echo esc_attr( $current_cat_def['color'] ); ?>; border-radius: 4px; margin-bottom: 16px;">
                    <span class="dashicons <?php echo esc_attr( $current_cat_def['icon'] ); ?>" style="color: <?php echo esc_attr( $current_cat_def['color'] ); ?>; font-size: 20px; width: 20px; height: 20px;"></span>
                    <div>
                        <strong style="color: <?php echo esc_attr( $current_cat_def['color'] ); ?>; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo esc_html( $current_cat_def['label'] ); ?></strong>
                        <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;"><?php echo esc_html( $current_cat_def['description'] ); ?></p>
                    </div>
                </div>

                <div class="tab-content pls-tab-content" data-option-id="<?php echo esc_attr( $current_option->id ); ?>">
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <h2 style="margin: 0;"><?php echo esc_html( $current_option->label ); ?></h2>
                                <span class="pls-badge" style="background: <?php echo esc_attr( $current_cat_def['color'] ); ?>1A; color: <?php echo esc_attr( $current_cat_def['color'] ); ?>; font-size: 10px; padding: 2px 8px; border-radius: 10px;"><?php echo esc_html( $current_cat_def['label'] ); ?></span>
                                <?php if ( $default_min_tier > 1 ) : ?>
                                    <span class="pls-tier-badge pls-tier-badge--<?php echo esc_attr( $default_min_tier ); ?>">T<?php echo esc_html( $default_min_tier ); ?>+</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button type="button" class="button pls-edit-option" 
                                        data-option-id="<?php echo esc_attr( $current_option->id ); ?>" 
                                        data-option-label="<?php echo esc_attr( $current_option->label ); ?>"
                                        data-option-default-tier="<?php echo esc_attr( $default_min_tier ); ?>">
                                    <?php esc_html_e( 'Edit', 'pls-private-label-store' ); ?>
                                </button>
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
                                        <td>A$<?php echo number_format( $price_display, 2 ); ?></td>
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

        <!-- v5.5.2: Ingredients moved to separate menu -->
        <?php elseif ( $active_tab === 'ingredients' ) : ?>
            <?php wp_safe_redirect( admin_url( 'admin.php?page=pls-ingredients' ) ); exit; ?>
        <?php endif; ?>
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
        $('#pls-option-default-tier').val('1'); // Reset to Tier 1
        $('#pls-option-variation').prop('checked', true);
        $('#pls-option-modal').fadeIn(200);
        $('body').addClass('pls-modal-open');
    });

    $('.pls-edit-option').on('click', function() {
        var optionId = $(this).data('option-id');
        var optionLabel = $(this).data('option-label');
        var defaultTier = $(this).data('option-default-tier') || 1;
        $('#pls-option-modal-title').text('Edit Product Option');
        $('#pls-option-id').val(optionId);
        $('#pls-option-label').val(optionLabel);
        $('#pls-option-default-tier').val(defaultTier);
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
            // Update existing option
            $.post(ajaxurl, {
                action: 'pls_update_attribute',
                nonce: nonce,
                option_id: optionId,
                label: $('#pls-option-label').val(),
                is_variation: $('#pls-option-variation').is(':checked') ? 1 : 0,
                default_min_tier: $('#pls-option-default-tier').val()
            }, function(resp) {
                if (resp.success) {
                    showNotice('Option updated successfully');
                    $('#pls-option-modal').fadeOut(200);
                    $('body').removeClass('pls-modal-open');
                    refreshPageData();
                } else {
                    showNotice(resp.data.message || 'Error updating option', 'error');
                }
            });
            return;
        }

        // Create new option
        $.post(ajaxurl, {
            action: 'pls_create_attribute',
            nonce: nonce,
            label: $('#pls-option-label').val(),
            is_variation: $('#pls-option-variation').is(':checked') ? 1 : 0,
            option_type: 'product-option',
            default_min_tier: $('#pls-option-default-tier').val()
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
    
    // Label Pricing form submit - v5.5.2 fixed
    $('#pls-label-pricing-form').on('submit', function(e) {
        e.preventDefault();
        var labelFee = parseFloat($('#label_fee_tier_1_2').val()) || 0;
        var applicationFee = parseFloat($('#label_application_fee_tier_1_2').val()) || 0;
        
        $.post(ajaxurl, {
            action: 'pls_update_label_pricing',
            nonce: nonce,
            label_fee_tier_1_2: labelFee,
            label_application_fee_tier_1_2: applicationFee
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
