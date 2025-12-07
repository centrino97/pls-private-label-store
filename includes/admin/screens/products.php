<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$action = isset( $_GET['pls_action'] ) ? sanitize_text_field( wp_unslash( $_GET['pls_action'] ) ) : '';
$notice = '';

if ( 'sync' === $action && isset( $_GET['id'] ) && check_admin_referer( 'pls_sync_base_product_' . absint( $_GET['id'] ) ) ) {
    $notice = PLS_WC_Sync::sync_base_product_to_wc( absint( $_GET['id'] ) );
    $action = '';
}

if ( isset( $_POST['pls_sync_all'] ) && check_admin_referer( 'pls_sync_all_base_products' ) ) {
    $notice = PLS_WC_Sync::sync_all_base_products();
    $action = '';
}

if ( isset( $_POST['pls_product_save'] ) && check_admin_referer( 'pls_product_save' ) ) {
    $base_id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $slug_raw = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
    $status   = isset( $_POST['status'] ) && in_array( $_POST['status'], array( 'draft', 'live' ), true ) ? $_POST['status'] : 'draft';
    $slug     = $slug_raw ? sanitize_title( $slug_raw ) : sanitize_title( $name );

    $categories = isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ? array_map( 'absint', $_POST['categories'] ) : array();
    $categories = array_filter( $categories );
    $category_path = $categories ? implode( ',', $categories ) : '';

    $data = array(
        'name'          => $name,
        'slug'          => $slug,
        'status'        => $status,
        'category_path' => $category_path,
    );

    if ( $base_id ) {
        PLS_Repo_Base_Product::update( $base_id, $data );
    } else {
        $base_id = PLS_Repo_Base_Product::insert( $data );
    }

    $tiers_input = isset( $_POST['tier'] ) && is_array( $_POST['tier'] ) ? $_POST['tier'] : array();
    $definitions = PLS_WC_Sync::pack_tier_definitions();

    foreach ( $definitions as $tier_key => $label ) {
        $tier_row = isset( $tiers_input[ $tier_key ] ) ? $tiers_input[ $tier_key ] : array();
        $enabled  = isset( $tier_row['enabled'] ) ? 1 : 0;
        $units    = isset( $tier_row['units'] ) ? absint( $tier_row['units'] ) : 0;
        $price    = isset( $tier_row['price'] ) ? floatval( $tier_row['price'] ) : 0;
        $sort     = isset( $tier_row['sort'] ) ? absint( $tier_row['sort'] ) : 0;

        PLS_Repo_Pack_Tier::upsert( $base_id, $tier_key, $units, $price, $enabled, $sort );
    }

    wp_safe_redirect( admin_url( 'admin.php?page=pls-products&message=saved' ) );
    exit;
}

$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

$categories = get_terms(
    array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    )
);
if ( is_wp_error( $categories ) ) {
    $categories = array();
}

$pack_definitions = PLS_WC_Sync::pack_tier_definitions();
?>
<div class="wrap pls-wrap">
  <h1>PLS – Products & Packs</h1>
  <?php if ( $notice ) : ?>
      <div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
  <?php endif; ?>
  <?php if ( 'saved' === $message ) : ?>
      <div class="notice notice-success"><p><?php esc_html_e( 'Base product saved.', 'pls-private-label-store' ); ?></p></div>
  <?php endif; ?>

  <?php if ( 'add' === $action || 'edit' === $action ) : ?>
      <?php
      $editing     = 'edit' === $action;
      $base_id     = $editing && isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
      $base_record = $editing ? PLS_Repo_Base_Product::get( $base_id ) : null;
      $selected_cats = $base_record && $base_record->category_path ? array_map( 'absint', explode( ',', $base_record->category_path ) ) : array();

      $tiers_existing = array();
      if ( $base_record ) {
          $existing_rows = PLS_Repo_Pack_Tier::for_base( $base_record->id );
          foreach ( $existing_rows as $row ) {
              $tiers_existing[ $row->tier_key ] = $row;
          }
      }
      ?>
      <form method="post" class="pls-form">
        <?php wp_nonce_field( 'pls_product_save' ); ?>
        <input type="hidden" name="pls_product_save" value="1" />
        <?php if ( $editing ) : ?>
            <input type="hidden" name="id" value="<?php echo esc_attr( $base_record->id ); ?>" />
        <?php endif; ?>

        <table class="form-table">
          <tr>
            <th scope="row"><label for="pls_name"><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></label></th>
            <td><input name="name" id="pls_name" type="text" class="regular-text" value="<?php echo esc_attr( $base_record->name ?? '' ); ?>" required /></td>
          </tr>
          <tr>
            <th scope="row"><label for="pls_slug"><?php esc_html_e( 'Slug', 'pls-private-label-store' ); ?></label></th>
            <td><input name="slug" id="pls_slug" type="text" class="regular-text" value="<?php echo esc_attr( $base_record->slug ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Auto from name', 'pls-private-label-store' ); ?>" /></td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?></th>
            <td>
              <?php
              $is_draft = ( $base_record && 'draft' === $base_record->status ) || ! $base_record;
              ?>
              <label><input type="radio" name="status" value="draft" <?php checked( $is_draft ); ?> /> <?php esc_html_e( 'Draft', 'pls-private-label-store' ); ?></label>
              <label style="margin-left:15px;"><input type="radio" name="status" value="live" <?php checked( $base_record && 'live' === $base_record->status ); ?> /> <?php esc_html_e( 'Live (publish to Woo)', 'pls-private-label-store' ); ?></label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e( 'Categories', 'pls-private-label-store' ); ?></th>
            <td>
              <select name="categories[]" multiple style="min-width:260px;" size="6">
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( in_array( $cat->term_id, $selected_cats, true ) ); ?>><?php echo esc_html( $cat->name ); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="description"><?php esc_html_e( 'Select Woo product categories for the variable product.', 'pls-private-label-store' ); ?></p>
            </td>
          </tr>
        </table>

        <h2><?php esc_html_e( 'Pack tiers', 'pls-private-label-store' ); ?></h2>
        <table class="widefat fixed striped">
          <thead>
            <tr>
              <th><?php esc_html_e( 'Tier', 'pls-private-label-store' ); ?></th>
              <th><?php esc_html_e( 'Enabled', 'pls-private-label-store' ); ?></th>
              <th><?php esc_html_e( 'Units', 'pls-private-label-store' ); ?></th>
              <th><?php esc_html_e( 'Price', 'pls-private-label-store' ); ?></th>
              <th><?php esc_html_e( 'Sort', 'pls-private-label-store' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ( $pack_definitions as $tier_key => $label ) :
                $row = $tiers_existing[ $tier_key ] ?? null;
                ?>
                <tr>
                  <td><?php echo esc_html( $label ); ?> <code><?php echo esc_html( $tier_key ); ?></code></td>
                  <td><input type="checkbox" name="tier[<?php echo esc_attr( $tier_key ); ?>][enabled]" <?php checked( ! $row || ( $row && (int) $row->is_enabled === 1 ) ); ?> /></td>
                  <td><input type="number" name="tier[<?php echo esc_attr( $tier_key ); ?>][units]" value="<?php echo esc_attr( $row->units ?? 0 ); ?>" min="0" /></td>
                  <td><input type="text" name="tier[<?php echo esc_attr( $tier_key ); ?>][price]" value="<?php echo esc_attr( $row->price ?? '' ); ?>" /></td>
                  <td><input type="number" name="tier[<?php echo esc_attr( $tier_key ); ?>][sort]" value="<?php echo esc_attr( $row->sort_order ?? 0 ); ?>" /></td>
                </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Base Product', 'pls-private-label-store' ); ?></button></p>
      </form>
  <?php else : ?>
      <div style="margin: 10px 0;">
        <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pls-products&pls_action=add' ) ); ?>"><?php esc_html_e( 'Add Base Product', 'pls-private-label-store' ); ?></a>
        <form method="post" style="display:inline-block;margin-left:10px;">
            <?php wp_nonce_field( 'pls_sync_all_base_products' ); ?>
            <input type="hidden" name="pls_sync_all" value="1" />
            <button type="submit" class="button"><?php esc_html_e( 'Sync All', 'pls-private-label-store' ); ?></button>
        </form>
      </div>

      <table class="widefat fixed striped">
        <thead>
          <tr>
            <th><?php esc_html_e( 'ID', 'pls-private-label-store' ); ?></th>
            <th><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?></th>
            <th><?php esc_html_e( 'Slug', 'pls-private-label-store' ); ?></th>
            <th><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?></th>
            <th><?php esc_html_e( 'Woo Product', 'pls-private-label-store' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'pls-private-label-store' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $bases = PLS_Repo_Base_Product::all(); ?>
          <?php if ( empty( $bases ) ) : ?>
              <tr><td colspan="6"><?php esc_html_e( 'No base products yet.', 'pls-private-label-store' ); ?></td></tr>
          <?php else : ?>
              <?php foreach ( $bases as $base ) :
                  $sync_url = wp_nonce_url( admin_url( 'admin.php?page=pls-products&pls_action=sync&id=' . $base->id ), 'pls_sync_base_product_' . $base->id );
                  $edit_url = admin_url( 'admin.php?page=pls-products&pls_action=edit&id=' . $base->id );
                  ?>
                  <tr>
                    <td><?php echo esc_html( $base->id ); ?></td>
                    <td><?php echo esc_html( $base->name ); ?></td>
                    <td><?php echo esc_html( $base->slug ); ?></td>
                    <td><?php echo esc_html( $base->status ); ?></td>
                    <td>
                      <?php if ( $base->wc_product_id && get_post_status( $base->wc_product_id ) ) : ?>
                          <a href="<?php echo esc_url( get_edit_post_link( $base->wc_product_id ) ); ?>"><?php echo esc_html( $base->wc_product_id ); ?></a>
                      <?php else : ?>
                          —
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'pls-private-label-store' ); ?></a> |
                      <a href="<?php echo esc_url( $sync_url ); ?>"><?php esc_html_e( 'Sync', 'pls-private-label-store' ); ?></a>
                    </td>
                  </tr>
              <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
  <?php endif; ?>
</div>
