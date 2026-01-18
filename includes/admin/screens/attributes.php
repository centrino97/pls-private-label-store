<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$notice = '';
$error  = '';

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

        // If this is Pack Tier, store the ID
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

            // Set default price impact
            $value = PLS_Repo_Attributes::get_value( $value_id );
            if ( $value && $value->term_id && $default_price > 0 ) {
                update_term_meta( $value->term_id, '_pls_default_price_impact', $default_price );
            }

            $notice = __( 'Attribute value saved.', 'pls-private-label-store' );
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

    $notice = __( 'Attribute values updated.', 'pls-private-label-store' );
}

$attrs = PLS_Repo_Attributes::attrs_all();
$pack_tier_attr_id = get_option( 'pls_pack_tier_attribute_id', 0 );
?>
<div class="wrap pls-wrap">
    <h1><?php esc_html_e( 'PLS – Attributes & Swatches', 'pls-private-label-store' ); ?></h1>
    <p class="description"><?php esc_html_e( 'Manage product attributes with tier-based restrictions and pricing. Attributes work like ingredients - create them here and reuse across products.', 'pls-private-label-store' ); ?></p>

    <?php if ( $notice ) : ?>
        <div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
    <?php endif; ?>
    <?php if ( $error ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

    <div class="pls-card pls-card--panel">
        <h2><?php esc_html_e( 'Add Attribute', 'pls-private-label-store' ); ?></h2>
        <form method="post" class="pls-form">
            <?php wp_nonce_field( 'pls_attr_add' ); ?>
            <input type="hidden" name="pls_attr_add" value="1" />
            <div class="pls-field-row">
                <label><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></label>
                <input type="text" name="label" class="regular-text" placeholder="Package Type" required />
            </div>
            <div class="pls-field-row">
                <label><?php esc_html_e( 'Attribute Type', 'pls-private-label-store' ); ?></label>
                <select name="attr_type" class="regular-text">
                    <option value="regular"><?php esc_html_e( 'Regular Attribute', 'pls-private-label-store' ); ?></option>
                    <option value="pack-tier"><?php esc_html_e( 'Pack Tier', 'pls-private-label-store' ); ?></option>
                    <option value="package-type"><?php esc_html_e( 'Package Type', 'pls-private-label-store' ); ?></option>
                    <option value="package-colour"><?php esc_html_e( 'Package Colour', 'pls-private-label-store' ); ?></option>
                </select>
            </div>
            <div class="pls-field-row">
                <label>
                    <input type="checkbox" name="is_variation" value="1" checked />
                    <?php esc_html_e( 'Use for variations', 'pls-private-label-store' ); ?>
                </label>
            </div>
            <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Attribute', 'pls-private-label-store' ); ?></button></p>
        </form>
    </div>

    <h2><?php esc_html_e( 'Existing Attributes', 'pls-private-label-store' ); ?></h2>
    <div class="pls-card-grid">
        <?php if ( empty( $attrs ) ) : ?>
            <p class="description"><?php esc_html_e( 'No attributes yet. Start adding your attribute library above.', 'pls-private-label-store' ); ?></p>
        <?php else : ?>
            <?php foreach ( $attrs as $attr ) : ?>
                <?php $values = PLS_Repo_Attributes::values_for_attr( $attr->id ); ?>
                <div class="pls-card pls-card--panel">
                    <div class="pls-card__heading">
                        <h3><?php echo esc_html( $attr->label ); ?></h3>
                        <?php if ( (int) $attr->id === (int) $pack_tier_attr_id ) : ?>
                            <span class="pls-chip"><?php esc_html_e( 'Pack Tier', 'pls-private-label-store' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <p><code><?php echo esc_html( $attr->attr_key ); ?></code></p>
                    <p><?php esc_html_e( 'Variation attribute', 'pls-private-label-store' ); ?>: <?php echo $attr->is_variation ? esc_html__( 'Yes', 'pls-private-label-store' ) : esc_html__( 'No', 'pls-private-label-store' ); ?></p>

                    <form method="post" style="margin-top:16px;">
                        <?php wp_nonce_field( 'pls_value_bulk_update' ); ?>
                        <input type="hidden" name="pls_value_bulk_update" value="1" />
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></th>
                                    <th><?php esc_html_e( 'Min Tier', 'pls-private-label-store' ); ?></th>
                                    <th><?php esc_html_e( 'Price', 'pls-private-label-store' ); ?></th>
                                    <th><?php esc_html_e( 'Swatch', 'pls-private-label-store' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( empty( $values ) ) : ?>
                                    <tr><td colspan="4"><?php esc_html_e( 'No values yet.', 'pls-private-label-store' ); ?></td></tr>
                                <?php else : ?>
                                    <?php foreach ( $values as $value ) : ?>
                                        <?php
                                        $swatch = PLS_Repo_Attributes::swatch_for_value( $value->id );
                                        $price_meta = $value->term_id ? get_term_meta( $value->term_id, '_pls_default_price_impact', true ) : '';
                                        $price_display = '' !== $price_meta ? floatval( $price_meta ) : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html( $value->label ); ?></strong>
                                                <br><code style="font-size:11px;"><?php echo esc_html( $value->value_key ); ?></code>
                                            </td>
                                            <td>
                                                <select name="value_updates[<?php echo esc_attr( $value->id ); ?>][min_tier_level]" style="width:80px;">
                                                    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                                        <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $value->min_tier_level, $i ); ?>>
                                                            <?php echo esc_html( $i ); ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="value_updates[<?php echo esc_attr( $value->id ); ?>][price]" 
                                                       value="<?php echo esc_attr( $price_display ); ?>" 
                                                       step="0.01" 
                                                       min="0" 
                                                       style="width:100px;" />
                                            </td>
                                            <td>
                                                <?php if ( $swatch ) : ?>
                                                    <span class="pls-chip">
                                                        <?php echo esc_html( $swatch->swatch_type ); ?>: 
                                                        <?php echo esc_html( $swatch->swatch_value ); ?>
                                                    </span>
                                                <?php else : ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if ( ! empty( $values ) ) : ?>
                            <p class="submit" style="margin-top:12px;">
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'pls-private-label-store' ); ?></button>
                            </p>
                        <?php endif; ?>
                    </form>

                    <form method="post" style="margin-top:16px; padding-top:16px; border-top:1px solid #e2e8f0;">
                        <?php wp_nonce_field( 'pls_value_add' ); ?>
                        <input type="hidden" name="pls_value_add" value="1" />
                        <input type="hidden" name="attribute_id" value="<?php echo esc_attr( $attr->id ); ?>" />
                        <h4><?php esc_html_e( 'Add Value', 'pls-private-label-store' ); ?></h4>
                        <div class="pls-field-row">
                            <label><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></label>
                            <input type="text" name="value_label" required placeholder="Airless Pump" />
                        </div>
                        <div class="pls-field-row">
                            <label><?php esc_html_e( 'Minimum Tier Level', 'pls-private-label-store' ); ?></label>
                            <select name="min_tier_level">
                                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                    <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( sprintf( __( 'Tier %d', 'pls-private-label-store' ), $i ) ); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="pls-field-row">
                            <label><?php esc_html_e( 'Default Price Impact', 'pls-private-label-store' ); ?></label>
                            <input type="number" name="default_price" step="0.01" min="0" value="0" placeholder="0.00" />
                        </div>
                        <div class="pls-field-row">
                            <label><?php esc_html_e( 'Swatch Type', 'pls-private-label-store' ); ?></label>
                            <select name="swatch_type">
                                <option value="label"><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></option>
                                <option value="color"><?php esc_html_e( 'Color', 'pls-private-label-store' ); ?></option>
                                <option value="icon"><?php esc_html_e( 'Icon', 'pls-private-label-store' ); ?></option>
                                <option value="image"><?php esc_html_e( 'Image URL', 'pls-private-label-store' ); ?></option>
                            </select>
                            <input type="text" name="swatch_value" placeholder="#ffffff or icon name" style="margin-top:6px;" />
                        </div>
                        <p class="submit"><button type="submit" class="button"><?php esc_html_e( 'Save Value', 'pls-private-label-store' ); ?></button></p>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
