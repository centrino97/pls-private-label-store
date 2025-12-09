<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$notice = '';

if ( isset( $_POST['pls_attr_add'] ) && check_admin_referer( 'pls_attr_add' ) ) {
    $label  = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
    $is_var = isset( $_POST['is_variation'] ) ? 1 : 0;

    if ( $label ) {
        PLS_Repo_Attributes::insert_attr(
            array(
                'label'        => $label,
                'is_variation' => $is_var,
            )
        );
        $notice = __( 'Attribute saved.', 'pls-private-label-store' );
    }
}

if ( isset( $_POST['pls_value_add'] ) && check_admin_referer( 'pls_value_add' ) ) {
    $attr_id      = isset( $_POST['attribute_id'] ) ? absint( $_POST['attribute_id'] ) : 0;
    $label        = isset( $_POST['value_label'] ) ? sanitize_text_field( wp_unslash( $_POST['value_label'] ) ) : '';
    $swatch_type  = isset( $_POST['swatch_type'] ) ? sanitize_text_field( wp_unslash( $_POST['swatch_type'] ) ) : 'label';
    $swatch_value = isset( $_POST['swatch_value'] ) ? sanitize_text_field( wp_unslash( $_POST['swatch_value'] ) ) : '';

    if ( $attr_id && $label ) {
        $value_id = PLS_Repo_Attributes::insert_value(
            array(
                'attribute_id'     => $attr_id,
                'label'            => $label,
            )
        );

        PLS_Repo_Attributes::upsert_swatch_for_value( $value_id, $swatch_type, $swatch_value );
        $notice = __( 'Attribute value saved.', 'pls-private-label-store' );
    }
}

$attrs = PLS_Repo_Attributes::attrs_all();
?>
<div class="wrap pls-wrap">
  <h1>PLS – Attributes & Swatches</h1>
  <?php if ( $notice ) : ?>
      <div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
  <?php endif; ?>

  <h2><?php esc_html_e( 'Add Attribute', 'pls-private-label-store' ); ?></h2>
  <form method="post" class="pls-form">
    <?php wp_nonce_field( 'pls_attr_add' ); ?>
    <input type="hidden" name="pls_attr_add" value="1" />
    <table class="form-table">
      <tr>
        <th scope="row"><label for="attr_label"><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></label></th>
        <td><input type="text" name="label" id="attr_label" class="regular-text" required placeholder="Packaging Type" /></td>
      </tr>
      <tr>
        <th scope="row"><?php esc_html_e( 'Variation attribute?', 'pls-private-label-store' ); ?></th>
        <td><label><input type="checkbox" name="is_variation" value="1" checked /> <?php esc_html_e( 'Expose on variation form', 'pls-private-label-store' ); ?></label></td>
      </tr>
    </table>
    <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Attribute', 'pls-private-label-store' ); ?></button></p>
  </form>

  <h2><?php esc_html_e( 'Attributes', 'pls-private-label-store' ); ?></h2>
  <?php if ( empty( $attrs ) ) : ?>
      <p><?php esc_html_e( 'No attributes yet.', 'pls-private-label-store' ); ?></p>
  <?php else : ?>
      <?php foreach ( $attrs as $attr ) : ?>
          <?php $values = PLS_Repo_Attributes::values_for_attr( $attr->id ); ?>
          <div class="card" style="padding:15px; margin-bottom:20px;">
            <h3><?php echo esc_html( $attr->label ); ?> <code><?php echo esc_html( $attr->attr_key ); ?></code></h3>
            <p><?php esc_html_e( 'Variation attribute', 'pls-private-label-store' ); ?>: <?php echo $attr->is_variation ? esc_html__( 'Yes', 'pls-private-label-store' ) : esc_html__( 'No', 'pls-private-label-store' ); ?></p>

            <table class="widefat striped" style="margin-top:10px;">
              <thead>
                <tr>
                  <th><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></th>
                  <th><?php esc_html_e( 'Key', 'pls-private-label-store' ); ?></th>
                  <th><?php esc_html_e( 'Term ID', 'pls-private-label-store' ); ?></th>
                  <th><?php esc_html_e( 'Swatch', 'pls-private-label-store' ); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if ( empty( $values ) ) : ?>
                    <tr><td colspan="4"><?php esc_html_e( 'No values yet.', 'pls-private-label-store' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $values as $value ) : $swatch = PLS_Repo_Attributes::swatch_for_value( $value->id ); ?>
                        <tr>
                          <td><?php echo esc_html( $value->label ); ?></td>
                          <td><code><?php echo esc_html( $value->value_key ); ?></code></td>
                          <td><?php echo $value->term_id ? esc_html( $value->term_id ) : '—'; ?></td>
                          <td><?php echo $swatch ? esc_html( $swatch->swatch_type . ': ' . $swatch->swatch_value ) : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>

            <form method="post" style="margin-top:10px;">
              <?php wp_nonce_field( 'pls_value_add' ); ?>
              <input type="hidden" name="pls_value_add" value="1" />
              <input type="hidden" name="attribute_id" value="<?php echo esc_attr( $attr->id ); ?>" />
              <h4><?php esc_html_e( 'Add Value', 'pls-private-label-store' ); ?></h4>
              <table class="form-table">
                <tr>
                  <th scope="row"><label><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></label></th>
                  <td><input type="text" name="value_label" required placeholder="Airless Pump" /></td>
                </tr>
                <tr>
                  <th scope="row"><label><?php esc_html_e( 'Swatch Type', 'pls-private-label-store' ); ?></label></th>
                  <td>
                    <select name="swatch_type">
                      <option value="label"><?php esc_html_e( 'Label', 'pls-private-label-store' ); ?></option>
                      <option value="color"><?php esc_html_e( 'Color', 'pls-private-label-store' ); ?></option>
                      <option value="icon"><?php esc_html_e( 'Icon', 'pls-private-label-store' ); ?></option>
                      <option value="image"><?php esc_html_e( 'Image URL', 'pls-private-label-store' ); ?></option>
                    </select>
                    <input type="text" name="swatch_value" placeholder="#ffffff or icon name" />
                  </td>
                </tr>
              </table>
              <p class="submit"><button type="submit" class="button"><?php esc_html_e( 'Save Value', 'pls-private-label-store' ); ?></button></p>
            </form>
          </div>
      <?php endforeach; ?>
  <?php endif; ?>
</div>
