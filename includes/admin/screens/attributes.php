<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$notice = '';
$error  = '';

// Handle ingredient creation
if ( isset( $_POST['pls_ingredient_add'] ) && check_admin_referer( 'pls_ingredient_add' ) ) {
    $name        = isset( $_POST['ingredient_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ingredient_name'] ) ) : '';
    $icon_id     = isset( $_POST['ingredient_icon_id'] ) ? absint( $_POST['ingredient_icon_id'] ) : 0;
    $icon        = $icon_id ? wp_get_attachment_url( $icon_id ) : '';
    $short_descr = isset( $_POST['ingredient_short_desc'] ) ? sanitize_text_field( wp_unslash( $_POST['ingredient_short_desc'] ) ) : '';

    if ( $name ) {
        $slug  = sanitize_title( $name );
        $maybe = term_exists( $slug, 'pls_ingredient' );
        if ( ! $maybe ) {
            $result = wp_insert_term( $name, 'pls_ingredient', array( 'slug' => $slug ) );
            if ( ! is_wp_error( $result ) ) {
                update_term_meta( $result['term_id'], 'pls_ingredient_icon_id', $icon_id );
                update_term_meta( $result['term_id'], 'pls_ingredient_icon', $icon );
                update_term_meta( $result['term_id'], 'pls_ingredient_short_desc', $short_descr );
                $notice = __( 'Ingredient saved.', 'pls-private-label-store' );
            } else {
                $error = $result->get_error_message();
            }
        } else {
            $error = __( 'Ingredient already exists.', 'pls-private-label-store' );
        }
    }
}

// Handle bulk ingredient updates
if ( isset( $_POST['pls_ingredient_edit'] ) && check_admin_referer( 'pls_ingredient_edit' ) ) {
    $edits = isset( $_POST['ingredient_edit'] ) && is_array( $_POST['ingredient_edit'] ) ? $_POST['ingredient_edit'] : array();

    foreach ( $edits as $term_id => $row ) {
        $term_id = absint( $term_id );
        if ( ! $term_id ) {
            continue;
        }

        $new_name    = isset( $row['name'] ) ? sanitize_text_field( wp_unslash( $row['name'] ) ) : '';
        $icon_id     = isset( $row['icon_id'] ) ? absint( $row['icon_id'] ) : 0;
        $icon_url    = $icon_id ? wp_get_attachment_url( $icon_id ) : '';
        $short_descr = isset( $row['short_desc'] ) ? sanitize_text_field( wp_unslash( $row['short_desc'] ) ) : '';

        if ( $new_name ) {
            wp_update_term( $term_id, 'pls_ingredient', array( 'name' => $new_name ) );
        }

        update_term_meta( $term_id, 'pls_ingredient_icon_id', $icon_id );
        update_term_meta( $term_id, 'pls_ingredient_icon', $icon_url );
        update_term_meta( $term_id, 'pls_ingredient_short_desc', $short_descr );
    }

    $notice = __( 'Ingredients updated.', 'pls-private-label-store' );
}

// Handle attribute creation
if ( isset( $_POST['pls_attr_add'] ) && check_admin_referer( 'pls_attr_add' ) ) {
    $label       = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
    $is_var      = isset( $_POST['is_variation'] ) ? 1 : 0;
    $option_type = isset( $_POST['option_type'] ) ? sanitize_text_field( wp_unslash( $_POST['option_type'] ) ) : 'product-option';

    if ( $label ) {
        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'        => $label,
                'is_variation' => $is_var,
                'option_type'  => $option_type,
            )
        );

        $notice = __( 'Option saved.', 'pls-private-label-store' );
    }
}

// Handle value creation
if ( isset( $_POST['pls_value_add'] ) && check_admin_referer( 'pls_value_add' ) ) {
    $attr_id      = isset( $_POST['attribute_id'] ) ? absint( $_POST['attribute_id'] ) : 0;
    $label        = isset( $_POST['value_label'] ) ? sanitize_text_field( wp_unslash( $_POST['value_label'] ) ) : '';
    $swatch_type  = isset( $_POST['swatch_type'] ) ? sanitize_text_field( wp_unslash( $_POST['swatch_type'] ) ) : 'label';
    $swatch_value = isset( $_POST['swatch_value'] ) ? sanitize_text_field( wp_unslash( $_POST['swatch_value'] ) ) : '';
    $min_tier     = isset( $_POST['min_tier_level'] ) ? absint( $_POST['min_tier_level'] ) : 1;
    $default_price = isset( $_POST['default_price'] ) ? round( floatval( $_POST['default_price'] ), 2 ) : 0;

    if ( $attr_id && $label ) {
        $value_id = PLS_Repo_Attributes::insert_value(
            array(
                'attribute_id' => $attr_id,
                'label'        => $label,
            )
        );

        if ( $value_id ) {
            PLS_Repo_Attributes::upsert_swatch_for_value( $value_id, $swatch_type, $swatch_value );
            PLS_Repo_Attributes::update_value_tier_rules( $value_id, $min_tier, null );

            $value = PLS_Repo_Attributes::get_value( $value_id );
            if ( $value && $value->term_id && $default_price > 0 ) {
                update_term_meta( $value->term_id, '_pls_default_price_impact', $default_price );
            }

            $notice = __( 'Option saved.', 'pls-private-label-store' );
        }
    }
}

// Handle bulk value updates
if ( isset( $_POST['pls_value_bulk_update'] ) && check_admin_referer( 'pls_value_bulk_update' ) ) {
    $updates = isset( $_POST['value_updates'] ) && is_array( $_POST['value_updates'] ) ? $_POST['value_updates'] : array();

    foreach ( $updates as $value_id => $data ) {
        $value_id = absint( $value_id );
        if ( ! $value_id ) {
            continue;
        }

        $min_tier = isset( $data['min_tier_level'] ) ? absint( $data['min_tier_level'] ) : 1;
        $price    = isset( $data['price'] ) ? round( floatval( $data['price'] ), 2 ) : 0;

        PLS_Repo_Attributes::update_value_tier_rules( $value_id, $min_tier, null );

        $value = PLS_Repo_Attributes::get_value( $value_id );
        if ( $value && $value->term_id ) {
            if ( $price > 0 ) {
                update_term_meta( $value->term_id, '_pls_default_price_impact', $price );
            } else {
                delete_term_meta( $value->term_id, '_pls_default_price_impact' );
            }
        }
    }

    $notice = __( 'Options updated.', 'pls-private-label-store' );
}

// Get data by type
$primary_attr = PLS_Repo_Attributes::get_primary_attribute();
$product_options = PLS_Repo_Attributes::get_product_options();
$ingredient_attrs = PLS_Repo_Attributes::get_ingredient_attributes();
$ingredients = get_terms( array( 'taxonomy' => 'pls_ingredient', 'hide_empty' => false ) );
if ( is_wp_error( $ingredients ) ) {
    $ingredients = array();
}
?>
<div class="wrap pls-wrap pls-product-options">
    <h1><?php esc_html_e( 'Product Options', 'pls-private-label-store' ); ?></h1>

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
    <?php endif; ?>
    <?php if ( $error ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

    <!-- PRIMARY: Pack Tier Section -->
    <div class="pls-primary-section" style="background: #fff; border: 2px solid #2271b1; border-radius: 4px; padding: 15px; margin: 20px 0;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <h2 style="margin: 0; font-size: 18px;"><?php esc_html_e( 'Pack Tier', 'pls-private-label-store' ); ?></h2>
            <span class="pls-primary-badge" style="background: #2271b1; color: #fff; padding: 4px 10px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'PRIMARY OPTION', 'pls-private-label-store' ); ?></span>
        </div>

        <?php if ( $primary_attr ) : ?>
            <?php
            $tier_values = PLS_Repo_Attributes::values_for_attr( $primary_attr->id );
            require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?php esc_html_e( 'Tier', 'pls-private-label-store' ); ?></th>
                        <th style="width: 20%;"><?php esc_html_e( 'Units', 'pls-private-label-store' ); ?></th>
                        <th style="width: 20%;"><?php esc_html_e( 'Price', 'pls-private-label-store' ); ?></th>
                        <th style="width: 40%;"><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $tier_values as $tier_value ) : ?>
                        <?php
                        $tier_level = PLS_Tier_Rules::get_tier_level_from_value( $tier_value->id );
                        $units = PLS_Tier_Rules::get_default_units_for_tier( $tier_value->id );
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $tier_value->label ); ?></strong></td>
                            <td><?php echo esc_html( $units ?: '—' ); ?></td>
                            <td><?php esc_html_e( 'Set per product', 'pls-private-label-store' ); ?></td>
                            <td>
                                <button type="button" class="button button-small pls-edit-tier" data-attr-id="<?php echo esc_attr( $primary_attr->id ); ?>"><?php esc_html_e( 'Edit', 'pls-private-label-store' ); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'Pack Tier attribute not found. Please activate the plugin to create default attributes.', 'pls-private-label-store' ); ?></p>
        <?php endif; ?>
    </div>

    <!-- PRODUCT OPTIONS Section -->
    <div class="pls-product-options-section" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin: 20px 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <h2 style="margin: 0; font-size: 18px;"><?php esc_html_e( 'Product Options', 'pls-private-label-store' ); ?></h2>
                <span class="pls-badge" style="background: #f0f0f1; color: #50575e; padding: 4px 10px; border-radius: 3px; font-size: 11px; font-weight: 600;"><?php echo count( $product_options ); ?> <?php esc_html_e( 'options', 'pls-private-label-store' ); ?></span>
            </div>
            <button type="button" class="button button-small pls-toggle-section" data-target="product-options-content"><?php esc_html_e( 'Toggle', 'pls-private-label-store' ); ?></button>
        </div>

        <div id="product-options-content">
            <!-- Quick Add Form -->
            <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
                <form method="post" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 8px; align-items: end;">
                    <?php wp_nonce_field( 'pls_attr_add' ); ?>
                    <input type="hidden" name="pls_attr_add" value="1" />
                    <input type="hidden" name="option_type" value="product-option" />
                    <div>
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;"><?php esc_html_e( 'Option Name', 'pls-private-label-store' ); ?></label>
                        <input type="text" name="label" class="regular-text" placeholder="e.g., Package Type" required style="width: 100%;" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">&nbsp;</label>
                        <label><input type="checkbox" name="is_variation" value="1" checked /> <?php esc_html_e( 'For variations', 'pls-private-label-store' ); ?></label>
                    </div>
                    <div>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Add', 'pls-private-label-store' ); ?></button>
                    </div>
                </form>
            </div>

            <!-- Product Options Table -->
            <?php if ( empty( $product_options ) ) : ?>
                <p><?php esc_html_e( 'No product options yet. Add one above.', 'pls-private-label-store' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 25%;"><?php esc_html_e( 'Option', 'pls-private-label-store' ); ?></th>
                            <th style="width: 45%;"><?php esc_html_e( 'Values', 'pls-private-label-store' ); ?></th>
                            <th style="width: 15%;"><?php esc_html_e( 'Tier', 'pls-private-label-store' ); ?></th>
                            <th style="width: 15%;"><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $product_options as $attr ) : ?>
                            <?php $values = PLS_Repo_Attributes::values_for_attr( $attr->id ); ?>
                            <tr class="pls-clickable-row" data-attr-id="<?php echo esc_attr( $attr->id ); ?>">
                                <td>
                                    <strong><?php echo esc_html( $attr->label ); ?></strong>
                                    <br><code style="font-size: 11px; color: #999;"><?php echo esc_html( $attr->attr_key ); ?></code>
                                </td>
                                <td>
                                    <?php if ( empty( $values ) ) : ?>
                                        <span style="color: #999;"><?php esc_html_e( 'No values', 'pls-private-label-store' ); ?></span>
                                    <?php else : ?>
                                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                            <?php foreach ( $values as $value ) : ?>
                                                <span style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px; font-size: 12px;">
                                                    <?php echo esc_html( $value->label ); ?>
                                                    <?php if ( $value->min_tier_level > 1 ) : ?>
                                                        <span class="pls-tier-badge" style="background: #6366f1; color: #fff; padding: 1px 4px; border-radius: 2px; font-size: 9px; margin-left: 4px;">T<?php echo esc_html( $value->min_tier_level ); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $tier_info = array();
                                    foreach ( $values as $value ) {
                                        if ( $value->min_tier_level > 1 ) {
                                            $tier_info[] = 'T' . $value->min_tier_level;
                                        }
                                    }
                                    echo esc_html( ! empty( $tier_info ) ? implode( ', ', array_unique( $tier_info ) ) : 'T1+' );
                                    ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small pls-edit-option" data-attr-id="<?php echo esc_attr( $attr->id ); ?>"><?php esc_html_e( 'Edit', 'pls-private-label-store' ); ?></button>
                                </td>
                            </tr>
                            <!-- Edit Panel -->
                            <tr class="pls-edit-panel-row" data-attr-id="<?php echo esc_attr( $attr->id ); ?>" style="display: none;">
                                <td colspan="4" style="background: #f9f9f9; padding: 15px;">
                                    <div class="pls-attr-edit-panel">
                                        <h3 style="margin-top: 0;"><?php echo esc_html( $attr->label ); ?> - <?php esc_html_e( 'Edit Values', 'pls-private-label-store' ); ?></h3>
                                        
                                        <?php if ( ! empty( $values ) ) : ?>
                                            <form method="post" style="margin-bottom: 15px;">
                                                <?php wp_nonce_field( 'pls_value_bulk_update' ); ?>
                                                <input type="hidden" name="pls_value_bulk_update" value="1" />
                                                <table class="wp-list-table widefat fixed">
                                                    <thead>
                                                        <tr>
                                                            <th><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></th>
                                                            <th style="width: 100px;"><?php esc_html_e( 'Min Tier', 'pls-private-label-store' ); ?></th>
                                                            <th style="width: 120px;"><?php esc_html_e( 'Price', 'pls-private-label-store' ); ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ( $values as $value ) : ?>
                                                            <?php
                                                            $price_meta = $value->term_id ? get_term_meta( $value->term_id, '_pls_default_price_impact', true ) : '';
                                                            $price_display = '' !== $price_meta ? floatval( $price_meta ) : 0;
                                                            ?>
                                                            <tr>
                                                                <td><strong><?php echo esc_html( $value->label ); ?></strong></td>
                                                                <td>
                                                                    <select name="value_updates[<?php echo esc_attr( $value->id ); ?>][min_tier_level]" style="width: 100%;">
                                                                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                                                            <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $value->min_tier_level, $i ); ?>><?php echo esc_html( $i ); ?></option>
                                                                        <?php endfor; ?>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="number" 
                                                                           name="value_updates[<?php echo esc_attr( $value->id ); ?>][price]" 
                                                                           value="<?php echo esc_attr( $price_display ); ?>" 
                                                                           step="0.01" 
                                                                           min="0" 
                                                                           style="width: 100%;" />
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                                <p style="margin-top: 10px;">
                                                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'pls-private-label-store' ); ?></button>
                                                </p>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Add New Value -->
                                        <form method="post" style="background: #fff; padding: 12px; border: 1px solid #ddd; border-radius: 4px;">
                                            <?php wp_nonce_field( 'pls_value_add' ); ?>
                                            <input type="hidden" name="pls_value_add" value="1" />
                                            <input type="hidden" name="attribute_id" value="<?php echo esc_attr( $attr->id ); ?>" />
                                            <h4 style="margin-top: 0;"><?php esc_html_e( 'Add Value', 'pls-private-label-store' ); ?></h4>
                                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 8px; align-items: end;">
                                                <div>
                                                    <label style="display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></label>
                                                    <input type="text" name="value_label" class="regular-text" placeholder="e.g., Airless Pump" required style="width: 100%;" />
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( 'Min Tier', 'pls-private-label-store' ); ?></label>
                                                    <select name="min_tier_level" style="width: 100%;">
                                                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                                            <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( 'Price', 'pls-private-label-store' ); ?></label>
                                                    <input type="number" name="default_price" step="0.01" min="0" value="0" style="width: 100%;" />
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 4px; font-size: 12px;"><?php esc_html_e( 'Swatch', 'pls-private-label-store' ); ?></label>
                                                    <select name="swatch_type" style="width: 100%;">
                                                        <option value="label"><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></option>
                                                        <option value="color"><?php esc_html_e( 'Color', 'pls-private-label-store' ); ?></option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <button type="submit" class="button"><?php esc_html_e( 'Add', 'pls-private-label-store' ); ?></button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- INGREDIENTS Section -->
    <div class="pls-ingredient-section" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin: 20px 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <h2 style="margin: 0; font-size: 18px;"><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></h2>
                <span class="pls-tier-badge" style="background: #6366f1; color: #fff; padding: 4px 10px; border-radius: 3px; font-size: 11px; font-weight: 600;"><?php esc_html_e( 'Tier 3+', 'pls-private-label-store' ); ?></span>
                <span class="pls-badge" style="background: #f0f0f1; color: #50575e; padding: 4px 10px; border-radius: 3px; font-size: 11px; font-weight: 600;"><?php echo count( $ingredients ); ?> <?php esc_html_e( 'ingredients', 'pls-private-label-store' ); ?></span>
            </div>
            <button type="button" class="button button-small pls-toggle-section" data-target="ingredients-content"><?php esc_html_e( 'Toggle', 'pls-private-label-store' ); ?></button>
        </div>

        <div id="ingredients-content">
            <!-- Quick Add Form -->
            <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
                <form method="post" style="display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 8px; align-items: end;">
                    <?php wp_nonce_field( 'pls_ingredient_add' ); ?>
                    <input type="hidden" name="pls_ingredient_add" value="1" />
                    <div>
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;"><?php esc_html_e( 'Ingredient Name', 'pls-private-label-store' ); ?></label>
                        <input type="text" name="ingredient_name" class="regular-text" placeholder="e.g., Hyaluronic Acid" required style="width: 100%;" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;"><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></label>
                        <input type="text" name="ingredient_short_desc" class="regular-text" placeholder="Brief description" style="width: 100%;" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;">&nbsp;</label>
                        <div class="pls-icon-picker" data-target="ingredient_icon_id">
                            <input type="hidden" name="ingredient_icon_id" id="ingredient_icon_id" />
                            <button type="button" class="button pls-icon-pick"><?php esc_html_e( 'Icon', 'pls-private-label-store' ); ?></button>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Add', 'pls-private-label-store' ); ?></button>
                    </div>
                </form>
            </div>

            <!-- Ingredients Table -->
            <?php if ( empty( $ingredients ) ) : ?>
                <p><?php esc_html_e( 'No ingredients yet. Add one above.', 'pls-private-label-store' ); ?></p>
            <?php else : ?>
                <form method="post">
                    <?php wp_nonce_field( 'pls_ingredient_edit' ); ?>
                    <input type="hidden" name="pls_ingredient_edit" value="1" />
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php esc_html_e( 'Icon', 'pls-private-label-store' ); ?></th>
                                <th style="width: 25%;"><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></th>
                                <th><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></th>
                                <th style="width: 150px;"><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $ingredients as $ingredient ) : ?>
                                <?php
                                $icon    = PLS_Taxonomies::icon_for_term( $ingredient->term_id );
                                $icon_id = absint( get_term_meta( $ingredient->term_id, 'pls_ingredient_icon_id', true ) );
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
                                    <td>
                                        <input type="text" 
                                               name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][name]" 
                                               value="<?php echo esc_attr( $ingredient->name ); ?>" 
                                               class="regular-text" 
                                               style="width: 100%;" />
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][short_desc]" 
                                               value="<?php echo esc_attr( $short ); ?>" 
                                               class="regular-text" 
                                               style="width: 100%;" 
                                               placeholder="<?php esc_attr_e( 'Description...', 'pls-private-label-store' ); ?>" />
                                    </td>
                                    <td>
                                        <div class="pls-icon-picker" data-target="ingredient_edit_<?php echo esc_attr( $ingredient->term_id ); ?>">
                                            <input type="hidden" name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][icon_id]" id="ingredient_edit_<?php echo esc_attr( $ingredient->term_id ); ?>" value="<?php echo esc_attr( $icon_id ); ?>" />
                                            <button type="button" class="button button-small pls-icon-pick"><?php esc_html_e( 'Change', 'pls-private-label-store' ); ?></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="margin-top: 10px;">
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save All Changes', 'pls-private-label-store' ); ?></button>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle sections
    $('.pls-toggle-section').on('click', function() {
        var target = $(this).data('target');
        $('#' + target).slideToggle();
    });

    // Toggle edit panels for product options
    $('.pls-clickable-row').on('click', function() {
        var attrId = $(this).data('attr-id');
        $('.pls-edit-panel-row[data-attr-id="' + attrId + '"]').slideToggle();
    });

    $('.pls-clickable-row').css('cursor', 'pointer');
});
</script>

<style>
.pls-product-options .pls-primary-section { border-left: 4px solid #2271b1 !important; }
.pls-product-options .pls-primary-badge { text-transform: uppercase; font-weight: 600; }
.pls-product-options .pls-clickable-row:hover { background: #f6f7f7; }
.pls-product-options table { margin-top: 0; }
.pls-product-options .pls-edit-panel-row { display: none; }
@media (max-width: 782px) {
    .pls-product-options form[style*="grid"] { grid-template-columns: 1fr !important; }
    .pls-product-options .wp-list-table { font-size: 13px; }
}
</style>
