<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$notice = '';
$error  = '';
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'attributes';

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

$attrs = PLS_Repo_Attributes::attrs_all();
$pack_tier_attr_id = get_option( 'pls_pack_tier_attribute_id', 0 );
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

    <nav class="nav-tab-wrapper" style="margin: 20px 0 0;">
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'attributes', admin_url( 'admin.php?page=pls-attributes' ) ) ); ?>" 
           class="nav-tab <?php echo $active_tab === 'attributes' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Attributes', 'pls-private-label-store' ); ?>
            <span class="count">(<?php echo count( $attrs ); ?>)</span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'ingredients', admin_url( 'admin.php?page=pls-attributes' ) ) ); ?>" 
           class="nav-tab <?php echo $active_tab === 'ingredients' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?>
            <span class="count">(<?php echo count( $ingredients ); ?>)</span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'pricing', admin_url( 'admin.php?page=pls-attributes' ) ) ); ?>" 
           class="nav-tab <?php echo $active_tab === 'pricing' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Pricing Overview', 'pls-private-label-store' ); ?>
        </a>
    </nav>

    <?php if ( $active_tab === 'attributes' ) : ?>
        <!-- Attributes Tab -->
        <div class="tab-content" style="margin-top: 20px;">
            <!-- Quick Add Form -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 12px; margin-bottom: 20px; border-radius: 4px;">
                <form method="post" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 8px; align-items: end;">
                    <?php wp_nonce_field( 'pls_attr_add' ); ?>
                    <input type="hidden" name="pls_attr_add" value="1" />
                    <div>
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;"><?php esc_html_e( 'Option Name', 'pls-private-label-store' ); ?></label>
                        <input type="text" name="label" class="regular-text" placeholder="e.g., Package Type" required style="width: 100%;" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px;"><?php esc_html_e( 'Type', 'pls-private-label-store' ); ?></label>
                        <select name="attr_type" class="regular-text" style="width: 100%;">
                            <option value="regular"><?php esc_html_e( 'Regular', 'pls-private-label-store' ); ?></option>
                            <option value="pack-tier"><?php esc_html_e( 'Pack Tier', 'pls-private-label-store' ); ?></option>
                        </select>
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

            <!-- Attributes Table -->
            <?php if ( empty( $attrs ) ) : ?>
                <p><?php esc_html_e( 'No options yet. Add one above.', 'pls-private-label-store' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 25%;"><?php esc_html_e( 'Option', 'pls-private-label-store' ); ?></th>
                            <th style="width: 50%;"><?php esc_html_e( 'Values', 'pls-private-label-store' ); ?></th>
                            <th style="width: 15%;"><?php esc_html_e( 'Tier', 'pls-private-label-store' ); ?></th>
                            <th style="width: 10%;"><?php esc_html_e( 'Price', 'pls-private-label-store' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $attrs as $attr ) : ?>
                            <?php $values = PLS_Repo_Attributes::values_for_attr( $attr->id ); ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $attr->label ); ?></strong>
                                    <?php if ( (int) $attr->id === (int) $pack_tier_attr_id ) : ?>
                                        <span class="pls-badge-small" style="background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 6px;"><?php esc_html_e( 'Pack Tier', 'pls-private-label-store' ); ?></span>
                                    <?php endif; ?>
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
                                                        <span class="pls-tier-badge-small" style="background: #6366f1; color: #fff; padding: 1px 4px; border-radius: 2px; font-size: 9px; margin-left: 4px;">T<?php echo esc_html( $value->min_tier_level ); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $tier_counts = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 );
                                    foreach ( $values as $value ) {
                                        $tier_counts[ $value->min_tier_level ]++;
                                    }
                                    $tier_info = array();
                                    foreach ( $tier_counts as $tier => $count ) {
                                        if ( $count > 0 ) {
                                            $tier_info[] = 'T' . $tier . ': ' . $count;
                                        }
                                    }
                                    echo esc_html( implode( ', ', $tier_info ) ?: '—' );
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $has_pricing = false;
                                    foreach ( $values as $value ) {
                                        $price = $value->term_id ? get_term_meta( $value->term_id, '_pls_default_price_impact', true ) : '';
                                        if ( '' !== $price && floatval( $price ) > 0 ) {
                                            $has_pricing = true;
                                            break;
                                        }
                                    }
                                    echo $has_pricing ? '<span style="color: #059669;">✓</span>' : '—';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Expandable Edit Forms -->
                <?php foreach ( $attrs as $attr ) : ?>
                    <?php $values = PLS_Repo_Attributes::values_for_attr( $attr->id ); ?>
                    <div class="pls-attr-edit-panel" data-attr-id="<?php echo esc_attr( $attr->id ); ?>" style="display: none; background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-top: 10px; border-radius: 4px;">
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
                                            $swatch = PLS_Repo_Attributes::swatch_for_value( $value->id );
                                            $price_meta = $value->term_id ? get_term_meta( $value->term_id, '_pls_default_price_impact', true ) : '';
                                            $price_display = '' !== $price_meta ? floatval( $price_meta ) : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo esc_html( $value->label ); ?></strong>
                                                    <br><code style="font-size: 10px; color: #999;"><?php echo esc_html( $value->value_key ); ?></code>
                                                </td>
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
                            <?php if ( isset( $_POST['swatch_type'] ) && $_POST['swatch_type'] === 'color' ) : ?>
                                <div style="margin-top: 8px;">
                                    <input type="text" name="swatch_value" placeholder="#ffffff" style="width: 120px;" />
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php elseif ( $active_tab === 'ingredients' ) : ?>
        <!-- Ingredients Tab -->
        <div class="tab-content" style="margin-top: 20px;">
            <!-- Quick Add Form -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 12px; margin-bottom: 20px; border-radius: 4px;">
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

    <?php elseif ( $active_tab === 'pricing' ) : ?>
        <!-- Pricing Overview Tab -->
        <div class="tab-content" style="margin-top: 20px;">
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
                <p><?php esc_html_e( 'No pricing impacts configured yet.', 'pls-private-label-store' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 25%;"><?php esc_html_e( 'Option', 'pls-private-label-store' ); ?></th>
                            <th style="width: 35%;"><?php esc_html_e( 'Value', 'pls-private-label-store' ); ?></th>
                            <th style="width: 20%;"><?php esc_html_e( 'Price Impact', 'pls-private-label-store' ); ?></th>
                            <th style="width: 20%;"><?php esc_html_e( 'Min Tier', 'pls-private-label-store' ); ?></th>
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
                                        <span style="color: #999;">$0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $item['min_tier'] > 1 ) : ?>
                                        <span class="pls-tier-badge-small" style="background: #6366f1; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 10px;">Tier <?php echo esc_html( $item['min_tier'] ); ?>+</span>
                                    <?php else : ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle edit panels
    $('.wp-list-table tbody tr').on('click', function() {
        if ($(this).next('.pls-attr-edit-panel').length) {
            $(this).next('.pls-attr-edit-panel').slideToggle();
        }
    });
    
    // Make rows clickable
    $('.wp-list-table tbody tr').css('cursor', 'pointer');
});
</script>

<style>
.pls-product-options .tab-content { margin-top: 20px; }
.pls-product-options table { margin-top: 0; }
.pls-product-options .wp-list-table tbody tr { cursor: pointer; }
.pls-product-options .wp-list-table tbody tr:hover { background: #f6f7f7; }
.pls-badge-small { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; }
.pls-tier-badge-small { display: inline-block; padding: 1px 4px; border-radius: 2px; font-size: 9px; font-weight: 600; }
.pls-attr-edit-panel { display: none; }
@media (max-width: 782px) {
    .pls-product-options form[style*="grid"] { grid-template-columns: 1fr !important; }
    .pls-product-options .wp-list-table { font-size: 13px; }
}
</style>
