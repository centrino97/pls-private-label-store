<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$notice = '';
$error  = '';
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'attributes';

// Handle ingredient creation (now as attribute type)
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
    $attr_type   = isset( $_POST['attr_type'] ) ? sanitize_text_field( wp_unslash( $_POST['attr_type'] ) ) : 'regular';

    if ( $label ) {
        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'        => $label,
                'is_variation' => $is_var,
            )
        );

        if ( $attr_type === 'pack-tier' && $attr_id ) {
            update_option( 'pls_pack_tier_attribute_id', $attr_id );
        }

        $notice = __( 'Attribute saved.', 'pls-private-label-store' );
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

$attrs = PLS_Repo_Attributes::attrs_all();
$pack_tier_attr_id = get_option( 'pls_pack_tier_attribute_id', 0 );
$ingredients = get_terms( array( 'taxonomy' => 'pls_ingredient', 'hide_empty' => false ) );
if ( is_wp_error( $ingredients ) ) {
    $ingredients = array();
}
?>
<div class="wrap pls-wrap pls-product-options">
    <div class="pls-page-header">
        <div>
            <h1><?php esc_html_e( 'Product Options', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Configure all product options, attributes, ingredients, and pricing impacts in one place.', 'pls-private-label-store' ); ?></p>
        </div>
    </div>

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
    <?php endif; ?>
    <?php if ( $error ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

    <nav class="pls-tabs-nav" style="margin: 20px 0; border-bottom: 2px solid #e1e4e8;">
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'attributes', admin_url( 'admin.php?page=pls-attributes' ) ) ); ?>" 
           class="nav-tab <?php echo $active_tab === 'attributes' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Attributes', 'pls-private-label-store' ); ?>
            <span class="pls-badge"><?php echo count( $attrs ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'ingredients', admin_url( 'admin.php?page=pls-attributes' ) ) ); ?>" 
           class="nav-tab <?php echo $active_tab === 'ingredients' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?>
            <span class="pls-badge"><?php echo count( $ingredients ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'pricing', admin_url( 'admin.php?page=pls-attributes' ) ) ); ?>" 
           class="nav-tab <?php echo $active_tab === 'pricing' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Pricing Overview', 'pls-private-label-store' ); ?>
        </a>
    </nav>

    <?php if ( $active_tab === 'attributes' ) : ?>
        <!-- Attributes Tab -->
        <div class="pls-tab-content">
            <div class="pls-options-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Add New Attribute -->
                <div class="pls-card-modern" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e1e4e8;">
                    <h2 style="margin-top: 0; font-size: 18px; font-weight: 600; color: #1f2937;"><?php esc_html_e( 'Add New Option', 'pls-private-label-store' ); ?></h2>
                    <form method="post" class="pls-form-modern">
                        <?php wp_nonce_field( 'pls_attr_add' ); ?>
                        <input type="hidden" name="pls_attr_add" value="1" />
                        <div class="pls-form-group">
                            <label><?php esc_html_e( 'Option Name', 'pls-private-label-store' ); ?></label>
                            <input type="text" name="label" class="pls-input-modern" placeholder="e.g., Package Type" required />
                        </div>
                        <div class="pls-form-group">
                            <label><?php esc_html_e( 'Type', 'pls-private-label-store' ); ?></label>
                            <select name="attr_type" class="pls-input-modern">
                                <option value="regular"><?php esc_html_e( 'Regular Option', 'pls-private-label-store' ); ?></option>
                                <option value="pack-tier"><?php esc_html_e( 'Pack Tier', 'pls-private-label-store' ); ?></option>
                                <option value="package-type"><?php esc_html_e( 'Package Type', 'pls-private-label-store' ); ?></option>
                                <option value="package-colour"><?php esc_html_e( 'Package Colour', 'pls-private-label-store' ); ?></option>
                            </select>
                        </div>
                        <div class="pls-form-group">
                            <label class="pls-checkbox-label">
                                <input type="checkbox" name="is_variation" value="1" checked />
                                <?php esc_html_e( 'Use for product variations', 'pls-private-label-store' ); ?>
                            </label>
                        </div>
                        <button type="submit" class="button button-primary button-large" style="width: 100%; margin-top: 12px;">
                            <?php esc_html_e( 'Create Option', 'pls-private-label-store' ); ?>
                        </button>
                    </form>
                </div>

                <!-- Quick Stats -->
                <div class="pls-card-modern" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <h3 style="margin-top: 0; color: #fff; font-size: 16px;"><?php esc_html_e( 'Quick Stats', 'pls-private-label-store' ); ?></h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                        <div>
                            <div style="font-size: 32px; font-weight: 700;"><?php echo count( $attrs ); ?></div>
                            <div style="font-size: 13px; opacity: 0.9;"><?php esc_html_e( 'Options', 'pls-private-label-store' ); ?></div>
                        </div>
                        <div>
                            <div style="font-size: 32px; font-weight: 700;"><?php echo count( $ingredients ); ?></div>
                            <div style="font-size: 13px; opacity: 0.9;"><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attributes List -->
            <div style="margin-top: 24px;">
                <?php if ( empty( $attrs ) ) : ?>
                    <div class="pls-empty-state" style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 12px; border: 2px dashed #e1e4e8;">
                        <p style="font-size: 16px; color: #6b7280;"><?php esc_html_e( 'No options yet. Create your first option above.', 'pls-private-label-store' ); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ( $attrs as $attr ) : ?>
                        <?php $values = PLS_Repo_Attributes::values_for_attr( $attr->id ); ?>
                        <div class="pls-option-card" style="background: #fff; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e1e4e8;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                                <div>
                                    <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 600; color: #1f2937;">
                                        <?php echo esc_html( $attr->label ); ?>
                                        <?php if ( (int) $attr->id === (int) $pack_tier_attr_id ) : ?>
                                            <span class="pls-chip" style="background: #dbeafe; color: #1e40af; font-size: 11px; padding: 4px 8px; border-radius: 4px; margin-left: 8px;"><?php esc_html_e( 'Pack Tier', 'pls-private-label-store' ); ?></span>
                                        <?php endif; ?>
                                    </h3>
                                    <code style="font-size: 12px; color: #6b7280;"><?php echo esc_html( $attr->attr_key ); ?></code>
                                </div>
                                <div style="text-align: right;">
                                    <span style="display: inline-block; background: #f3f4f6; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; color: #374151;">
                                        <?php echo count( $values ); ?> <?php echo count( $values ) === 1 ? esc_html__( 'value', 'pls-private-label-store' ) : esc_html__( 'values', 'pls-private-label-store' ); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ( ! empty( $values ) ) : ?>
                                <form method="post" style="margin-bottom: 20px;">
                                    <?php wp_nonce_field( 'pls_value_bulk_update' ); ?>
                                    <input type="hidden" name="pls_value_bulk_update" value="1" />
                                    <div class="pls-values-table" style="overflow-x: auto;">
                                        <table class="wp-list-table widefat fixed striped" style="margin: 0;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 30%;"><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></th>
                                                    <th style="width: 15%;"><?php esc_html_e( 'Min Tier', 'pls-private-label-store' ); ?></th>
                                                    <th style="width: 15%;"><?php esc_html_e( 'Price Impact', 'pls-private-label-store' ); ?></th>
                                                    <th style="width: 20%;"><?php esc_html_e( 'Swatch', 'pls-private-label-store' ); ?></th>
                                                    <th style="width: 20%;"><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ( $values as $value ) : ?>
                                                    <?php
                                                    $swatch = PLS_Repo_Attributes::swatch_for_value( $value->id );
                                                    $price_meta = $value->term_id ? get_term_meta( $value->term_id, '_pls_default_price_impact', true ) : '';
                                                    $price_display = '' !== $price_meta ? floatval( $price_meta ) : 0;
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo esc_html( $value->label ); ?></strong>
                                                            <br><code style="font-size: 11px; color: #9ca3af;"><?php echo esc_html( $value->value_key ); ?></code>
                                                        </td>
                                                        <td>
                                                            <select name="value_updates[<?php echo esc_attr( $value->id ); ?>][min_tier_level]" class="pls-input-modern" style="width: 100%;">
                                                                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                                                    <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $value->min_tier_level, $i ); ?>>
                                                                        <?php echo esc_html( $i ); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <div style="display: flex; align-items: center; gap: 4px;">
                                                                <span style="color: #6b7280;">$</span>
                                                                <input type="number" 
                                                                       name="value_updates[<?php echo esc_attr( $value->id ); ?>][price]" 
                                                                       value="<?php echo esc_attr( $price_display ); ?>" 
                                                                       step="0.01" 
                                                                       min="0" 
                                                                       class="pls-input-modern" 
                                                                       style="width: 100px;" />
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if ( $swatch ) : ?>
                                                                <span class="pls-chip" style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                                                    <?php echo esc_html( $swatch->swatch_type ); ?>
                                                                </span>
                                                            <?php else : ?>
                                                                <span style="color: #9ca3af;">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ( $value->min_tier_level > 1 ) : ?>
                                                                <span class="pls-tier-badge pls-tier-badge--tier<?php echo esc_attr( $value->min_tier_level ); ?>" style="font-size: 11px;">
                                                                    Tier <?php echo esc_html( $value->min_tier_level ); ?>+
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div style="margin-top: 16px;">
                                        <button type="submit" class="button button-primary">
                                            <?php esc_html_e( 'Save Changes', 'pls-private-label-store' ); ?>
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <!-- Add Value Form -->
                            <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 20px;">
                                <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #374151;"><?php esc_html_e( 'Add New Value', 'pls-private-label-store' ); ?></h4>
                                <form method="post" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 12px; align-items: end;">
                                    <?php wp_nonce_field( 'pls_value_add' ); ?>
                                    <input type="hidden" name="pls_value_add" value="1" />
                                    <input type="hidden" name="attribute_id" value="<?php echo esc_attr( $attr->id ); ?>" />
                                    <div>
                                        <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 500; color: #374151;"><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></label>
                                        <input type="text" name="value_label" class="pls-input-modern" placeholder="e.g., Airless Pump" required />
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 500; color: #374151;"><?php esc_html_e( 'Min Tier', 'pls-private-label-store' ); ?></label>
                                        <select name="min_tier_level" class="pls-input-modern">
                                            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                                <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( sprintf( __( 'Tier %d', 'pls-private-label-store' ), $i ) ); ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 500; color: #374151;"><?php esc_html_e( 'Price', 'pls-private-label-store' ); ?></label>
                                        <input type="number" name="default_price" step="0.01" min="0" value="0" class="pls-input-modern" placeholder="0.00" />
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 4px; font-size: 12px; font-weight: 500; color: #374151;"><?php esc_html_e( 'Swatch', 'pls-private-label-store' ); ?></label>
                                        <select name="swatch_type" class="pls-input-modern">
                                            <option value="label"><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></option>
                                            <option value="color"><?php esc_html_e( 'Color', 'pls-private-label-store' ); ?></option>
                                            <option value="icon"><?php esc_html_e( 'Icon', 'pls-private-label-store' ); ?></option>
                                        </select>
                                    </div>
                                    <div>
                                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Add', 'pls-private-label-store' ); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ( $active_tab === 'ingredients' ) : ?>
        <!-- Ingredients Tab -->
        <div class="pls-tab-content">
            <div class="pls-options-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Add Ingredient -->
                <div class="pls-card-modern" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e1e4e8;">
                    <h2 style="margin-top: 0; font-size: 18px; font-weight: 600; color: #1f2937;"><?php esc_html_e( 'Add Ingredient', 'pls-private-label-store' ); ?></h2>
                    <form method="post" class="pls-form-modern">
                        <?php wp_nonce_field( 'pls_ingredient_add' ); ?>
                        <input type="hidden" name="pls_ingredient_add" value="1" />
                        <div class="pls-form-group">
                            <label><?php esc_html_e( 'Ingredient Name', 'pls-private-label-store' ); ?></label>
                            <input type="text" name="ingredient_name" class="pls-input-modern" placeholder="e.g., Hyaluronic Acid" required />
                        </div>
                        <div class="pls-form-group">
                            <label><?php esc_html_e( 'Short Description', 'pls-private-label-store' ); ?></label>
                            <input type="text" name="ingredient_short_desc" class="pls-input-modern" placeholder="Brief description of benefits" />
                        </div>
                        <div class="pls-form-group">
                            <label><?php esc_html_e( 'Icon (optional)', 'pls-private-label-store' ); ?></label>
                            <div class="pls-icon-picker" data-target="ingredient_icon_id">
                                <div class="pls-icon-preview" id="ingredient_icon_preview"></div>
                                <input type="hidden" name="ingredient_icon_id" id="ingredient_icon_id" />
                                <button type="button" class="button pls-icon-pick"><?php esc_html_e( 'Upload/Select icon', 'pls-private-label-store' ); ?></button>
                            </div>
                        </div>
                        <button type="submit" class="button button-primary button-large" style="width: 100%; margin-top: 12px;">
                            <?php esc_html_e( 'Add Ingredient', 'pls-private-label-store' ); ?>
                        </button>
                    </form>
                </div>

                <!-- Info Card -->
                <div class="pls-card-modern" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <h3 style="margin-top: 0; color: #fff; font-size: 16px;"><?php esc_html_e( 'About Ingredients', 'pls-private-label-store' ); ?></h3>
                    <p style="color: rgba(255,255,255,0.9); font-size: 14px; line-height: 1.6;">
                        <?php esc_html_e( 'Ingredients are reusable components that can be added to any product. They appear in the product editor and can be selected as key ingredients (up to 5 per product).', 'pls-private-label-store' ); ?>
                    </p>
                </div>
            </div>

            <!-- Ingredients List -->
            <?php if ( empty( $ingredients ) ) : ?>
                <div class="pls-empty-state" style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 12px; border: 2px dashed #e1e4e8; margin-top: 24px;">
                    <p style="font-size: 16px; color: #6b7280;"><?php esc_html_e( 'No ingredients yet. Add your first ingredient above.', 'pls-private-label-store' ); ?></p>
                </div>
            <?php else : ?>
                <form method="post" style="margin-top: 24px;">
                    <?php wp_nonce_field( 'pls_ingredient_edit' ); ?>
                    <input type="hidden" name="pls_ingredient_edit" value="1" />
                    <div class="pls-card-modern" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e1e4e8;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;"><?php esc_html_e( 'All Ingredients', 'pls-private-label-store' ); ?></h3>
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save All Changes', 'pls-private-label-store' ); ?></button>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                            <?php foreach ( $ingredients as $ingredient ) : ?>
                                <?php
                                $icon    = PLS_Taxonomies::icon_for_term( $ingredient->term_id );
                                $icon_id = absint( get_term_meta( $ingredient->term_id, 'pls_ingredient_icon_id', true ) );
                                $short   = get_term_meta( $ingredient->term_id, 'pls_ingredient_short_desc', true );
                                ?>
                                <div style="background: #f9fafb; border-radius: 8px; padding: 16px; border: 1px solid #e5e7eb;">
                                    <div style="display: flex; gap: 12px; align-items: start;">
                                        <div class="pls-icon-preview" style="width: 48px; height: 48px; border-radius: 8px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                            <?php if ( $icon ) : ?>
                                                <img src="<?php echo esc_url( $icon ); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;" />
                                            <?php else : ?>
                                                <span style="color: #9ca3af;">📦</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <input type="text" 
                                                   name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][name]" 
                                                   value="<?php echo esc_attr( $ingredient->name ); ?>" 
                                                   class="pls-input-modern" 
                                                   style="width: 100%; margin-bottom: 8px; font-weight: 600;" />
                                            <input type="text" 
                                                   name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][short_desc]" 
                                                   value="<?php echo esc_attr( $short ); ?>" 
                                                   class="pls-input-modern" 
                                                   style="width: 100%; font-size: 13px;" 
                                                   placeholder="<?php esc_attr_e( 'Description...', 'pls-private-label-store' ); ?>" />
                                            <div class="pls-icon-picker" data-target="ingredient_edit_<?php echo esc_attr( $ingredient->term_id ); ?>" style="margin-top: 8px;">
                                                <input type="hidden" name="ingredient_edit[<?php echo esc_attr( $ingredient->term_id ); ?>][icon_id]" id="ingredient_edit_<?php echo esc_attr( $ingredient->term_id ); ?>" value="<?php echo esc_attr( $icon_id ); ?>" />
                                                <button type="button" class="button button-small pls-icon-pick"><?php esc_html_e( 'Change Icon', 'pls-private-label-store' ); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    <?php elseif ( $active_tab === 'pricing' ) : ?>
        <!-- Pricing Overview Tab -->
        <div class="pls-tab-content">
            <div class="pls-card-modern" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e1e4e8; margin-top: 20px;">
                <h2 style="margin-top: 0; font-size: 18px; font-weight: 600; color: #1f2937;"><?php esc_html_e( 'Pricing Impact Overview', 'pls-private-label-store' ); ?></h2>
                <p style="color: #6b7280; margin-bottom: 24px;"><?php esc_html_e( 'See all pricing impacts across all options at a glance. Edit directly from this view.', 'pls-private-label-store' ); ?></p>
                
                <?php
                $all_values_with_pricing = array();
                foreach ( $attrs as $attr ) {
                    $values = PLS_Repo_Attributes::values_for_attr( $attr->id );
                    foreach ( $values as $value ) {
                        $price = $value->term_id ? get_term_meta( $value->term_id, '_pls_default_price_impact', true ) : '';
                        if ( '' !== $price || $value->min_tier_level > 1 ) {
                            $all_values_with_pricing[] = array(
                                'attr_label' => $attr->label,
                                'value_label' => $value->label,
                                'value_id' => $value->id,
                                'price' => '' !== $price ? floatval( $price ) : 0,
                                'min_tier' => $value->min_tier_level,
                            );
                        }
                    }
                }
                ?>
                
                <?php if ( empty( $all_values_with_pricing ) ) : ?>
                    <div class="pls-empty-state" style="text-align: center; padding: 40px; background: #f9fafb; border-radius: 8px;">
                        <p style="color: #6b7280;"><?php esc_html_e( 'No pricing impacts configured yet.', 'pls-private-label-store' ); ?></p>
                    </div>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Option', 'pls-private-label-store' ); ?></th>
                                <th><?php esc_html_e( 'Value', 'pls-private-label-store' ); ?></th>
                                <th><?php esc_html_e( 'Price Impact', 'pls-private-label-store' ); ?></th>
                                <th><?php esc_html_e( 'Min Tier', 'pls-private-label-store' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $all_values_with_pricing as $item ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $item['attr_label'] ); ?></strong></td>
                                    <td><?php echo esc_html( $item['value_label'] ); ?></td>
                                    <td>
                                        <?php if ( $item['price'] > 0 ) : ?>
                                            <span style="color: #059669; font-weight: 600;">+$<?php echo number_format( $item['price'], 2 ); ?></span>
                                        <?php else : ?>
                                            <span style="color: #9ca3af;">$0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( $item['min_tier'] > 1 ) : ?>
                                            <span class="pls-tier-badge pls-tier-badge--tier<?php echo esc_attr( $item['min_tier'] ); ?>">
                                                Tier <?php echo esc_html( $item['min_tier'] ); ?>+
                                            </span>
                                        <?php else : ?>
                                            <span style="color: #9ca3af;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.pls-input-modern {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}
.pls-input-modern:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.pls-form-group {
    margin-bottom: 16px;
}
.pls-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 13px;
    color: #374151;
}
.pls-badge {
    display: inline-block;
    background: #e5e7eb;
    color: #374151;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}
.pls-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal !important;
}
</style>
