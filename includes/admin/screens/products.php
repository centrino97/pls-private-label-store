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

    $gallery_raw = isset( $_POST['gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['gallery_ids'] ) ) : '';
    $gallery_ids = $gallery_raw ? array_map( 'absint', explode( ',', $gallery_raw ) ) : array();

    $profile_data = array(
        'short_description'   => isset( $_POST['short_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['short_description'] ) ) : '',
        'long_description'    => isset( $_POST['long_description'] ) ? wp_kses_post( wp_unslash( $_POST['long_description'] ) ) : '',
        'featured_image_id'   => isset( $_POST['featured_image_id'] ) ? absint( $_POST['featured_image_id'] ) : 0,
        'gallery_ids'         => $gallery_ids,
        'directions_text'     => isset( $_POST['directions_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['directions_text'] ) ) : '',
        'ingredients_list'    => isset( $_POST['ingredients_list'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ingredients_list'] ) ) : '',
        'label_enabled'       => isset( $_POST['label_enabled'] ) ? 1 : 0,
        'label_price_per_unit'=> isset( $_POST['label_price_per_unit'] ) ? floatval( $_POST['label_price_per_unit'] ) : 0,
        'label_requires_file' => isset( $_POST['label_requires_file'] ) ? 1 : 0,
        'label_helper_text'   => isset( $_POST['label_helper_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['label_helper_text'] ) ) : '',
        'label_guide_url'     => isset( $_POST['label_guide_url'] ) ? esc_url_raw( wp_unslash( $_POST['label_guide_url'] ) ) : '',
    );

    $basics = isset( $_POST['basics'] ) && is_array( $_POST['basics'] ) ? $_POST['basics'] : array();
    $skin   = isset( $_POST['skin'] ) && is_array( $_POST['skin'] ) ? $_POST['skin'] : array();
    $benefits = isset( $_POST['benefits'] ) && is_array( $_POST['benefits'] ) ? $_POST['benefits'] : array();
    $ingredients = isset( $_POST['key_ingredients'] ) && is_array( $_POST['key_ingredients'] ) ? $_POST['key_ingredients'] : array();

    $profile_data['basics_json'] = array();
    $profile_data['skin_types_json'] = array();
    $profile_data['benefits_json'] = array();
    $profile_data['key_ingredients_json'] = array();

    if ( isset( $basics['label'] ) && is_array( $basics['label'] ) ) {
        foreach ( $basics['label'] as $i => $label ) {
            $label_val = sanitize_text_field( wp_unslash( $label ) );
            $icon_val  = isset( $basics['icon'][ $i ] ) ? esc_url_raw( wp_unslash( $basics['icon'][ $i ] ) ) : '';
            if ( '' === $label_val && '' === $icon_val ) {
                continue;
            }
            $profile_data['basics_json'][] = array(
                'label' => $label_val,
                'icon'  => $icon_val,
            );
        }
    }

    if ( isset( $skin['label'] ) && is_array( $skin['label'] ) ) {
        foreach ( $skin['label'] as $i => $label ) {
            $label_val = sanitize_text_field( wp_unslash( $label ) );
            $icon_val  = isset( $skin['icon'][ $i ] ) ? esc_url_raw( wp_unslash( $skin['icon'][ $i ] ) ) : '';
            if ( '' === $label_val && '' === $icon_val ) {
                continue;
            }
            $profile_data['skin_types_json'][] = array(
                'label' => $label_val,
                'icon'  => $icon_val,
            );
        }
    }

    if ( isset( $benefits['label'] ) && is_array( $benefits['label'] ) ) {
        foreach ( $benefits['label'] as $i => $label ) {
            $label_val = sanitize_text_field( wp_unslash( $label ) );
            $icon_val  = isset( $benefits['icon'][ $i ] ) ? esc_url_raw( wp_unslash( $benefits['icon'][ $i ] ) ) : '';
            if ( '' === $label_val && '' === $icon_val ) {
                continue;
            }
            $profile_data['benefits_json'][] = array(
                'label' => $label_val,
                'icon'  => $icon_val,
            );
        }
    }

    if ( isset( $ingredients['label'] ) && is_array( $ingredients['label'] ) ) {
        foreach ( $ingredients['label'] as $i => $label ) {
            $label_val = sanitize_text_field( wp_unslash( $label ) );
            $icon_val  = isset( $ingredients['icon'][ $i ] ) ? esc_url_raw( wp_unslash( $ingredients['icon'][ $i ] ) ) : '';
            if ( '' === $label_val && '' === $icon_val ) {
                continue;
            }
            $profile_data['key_ingredients_json'][] = array(
                'label' => $label_val,
                'icon'  => $icon_val,
            );
        }
    }

    PLS_Repo_Product_Profile::upsert( $base_id, $profile_data );

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

      $profile_record = $base_record ? PLS_Repo_Product_Profile::get_for_base( $base_record->id ) : null;
      $profile_basics = $profile_record && $profile_record->basics_json ? json_decode( $profile_record->basics_json, true ) : array();
      $profile_skin   = $profile_record && $profile_record->skin_types_json ? json_decode( $profile_record->skin_types_json, true ) : array();
      $profile_benefits = $profile_record && $profile_record->benefits_json ? json_decode( $profile_record->benefits_json, true ) : array();
      $profile_ingredients = $profile_record && $profile_record->key_ingredients_json ? json_decode( $profile_record->key_ingredients_json, true ) : array();
      $profile_gallery = $profile_record && $profile_record->gallery_ids ? array_filter( array_map( 'absint', explode( ',', $profile_record->gallery_ids ) ) ) : array();

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

        <h2 class="nav-tab-wrapper pls-tabs-nav">
          <a href="#" class="nav-tab nav-tab-active" data-pls-tab="general"><?php esc_html_e( 'General', 'pls-private-label-store' ); ?></a>
          <a href="#" class="nav-tab" data-pls-tab="content"><?php esc_html_e( 'Content', 'pls-private-label-store' ); ?></a>
          <a href="#" class="nav-tab" data-pls-tab="icons"><?php esc_html_e( 'Basics & Benefits', 'pls-private-label-store' ); ?></a>
          <a href="#" class="nav-tab" data-pls-tab="label"><?php esc_html_e( 'Label Application', 'pls-private-label-store' ); ?></a>
        </h2>

        <div class="pls-tab-panel is-active" data-pls-tab="general">
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
        </div>

        <div class="pls-tab-panel" data-pls-tab="content">
          <table class="form-table">
            <tr>
              <th scope="row"><label for="pls_short_desc"><?php esc_html_e( 'Short description', 'pls-private-label-store' ); ?></label></th>
              <td><textarea name="short_description" id="pls_short_desc" rows="3" class="large-text"><?php echo esc_textarea( $profile_record->short_description ?? '' ); ?></textarea></td>
            </tr>
            <tr>
              <th scope="row"><label for="pls_long_desc"><?php esc_html_e( 'Long description', 'pls-private-label-store' ); ?></label></th>
              <td><textarea name="long_description" id="pls_long_desc" rows="6" class="large-text"><?php echo esc_textarea( $profile_record->long_description ?? '' ); ?></textarea></td>
            </tr>
            <tr>
              <th scope="row"><label for="pls_featured_id"><?php esc_html_e( 'Featured image ID', 'pls-private-label-store' ); ?></label></th>
              <td><input name="featured_image_id" id="pls_featured_id" type="number" value="<?php echo esc_attr( $profile_record->featured_image_id ?? '' ); ?>" class="small-text" /> <span class="description"><?php esc_html_e( 'Media attachment ID from the library.', 'pls-private-label-store' ); ?></span></td>
            </tr>
            <tr>
              <th scope="row"><label for="pls_gallery_ids"><?php esc_html_e( 'Gallery image IDs', 'pls-private-label-store' ); ?></label></th>
              <td><input name="gallery_ids" id="pls_gallery_ids" type="text" value="<?php echo esc_attr( implode( ',', $profile_gallery ) ); ?>" class="regular-text" placeholder="12,45,99" /></td>
            </tr>
            <tr>
              <th scope="row"><label for="pls_directions"><?php esc_html_e( 'Directions for use', 'pls-private-label-store' ); ?></label></th>
              <td><textarea name="directions_text" id="pls_directions" rows="4" class="large-text"><?php echo esc_textarea( $profile_record->directions_text ?? '' ); ?></textarea></td>
            </tr>
            <tr>
              <th scope="row"><label for="pls_ingredients"><?php esc_html_e( 'Ingredients list', 'pls-private-label-store' ); ?></label></th>
              <td><textarea name="ingredients_list" id="pls_ingredients" rows="4" class="large-text"><?php echo esc_textarea( $profile_record->ingredients_list ?? '' ); ?></textarea></td>
            </tr>
          </table>
        </div>

        <div class="pls-tab-panel" data-pls-tab="icons">
          <div class="pls-repeater" data-group="basics">
            <h3><?php esc_html_e( 'Product basics (icons + labels)', 'pls-private-label-store' ); ?></h3>
            <div class="pls-repeater__rows">
              <?php if ( ! empty( $profile_basics ) ) : ?>
                <?php foreach ( $profile_basics as $row ) : ?>
                  <div class="pls-repeater__row">
                    <input type="text" name="basics[label][]" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Label', 'pls-private-label-store' ); ?>" />
                    <input type="text" name="basics[icon][]" value="<?php echo esc_attr( $row['icon'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Icon URL', 'pls-private-label-store' ); ?>" />
                    <button type="button" class="button-link-delete pls-repeater-remove">&times;</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <button type="button" class="button pls-repeater-add" data-template="basics-template"><?php esc_html_e( 'Add basic', 'pls-private-label-store' ); ?></button>
            <div class="pls-repeater__template" id="basics-template">
              <div class="pls-repeater__row">
                <input type="text" name="basics[label][]" placeholder="<?php esc_attr_e( 'Label', 'pls-private-label-store' ); ?>" />
                <input type="text" name="basics[icon][]" placeholder="<?php esc_attr_e( 'Icon URL', 'pls-private-label-store' ); ?>" />
                <button type="button" class="button-link-delete pls-repeater-remove">&times;</button>
              </div>
            </div>
          </div>

          <div class="pls-repeater" data-group="skin">
            <h3><?php esc_html_e( 'Skin types', 'pls-private-label-store' ); ?></h3>
            <div class="pls-repeater__rows">
              <?php if ( ! empty( $profile_skin ) ) : ?>
                <?php foreach ( $profile_skin as $row ) : ?>
                  <div class="pls-repeater__row">
                    <input type="text" name="skin[label][]" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Skin type', 'pls-private-label-store' ); ?>" />
                    <input type="text" name="skin[icon][]" value="<?php echo esc_attr( $row['icon'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Icon URL', 'pls-private-label-store' ); ?>" />
                    <button type="button" class="button-link-delete pls-repeater-remove">&times;</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <button type="button" class="button pls-repeater-add" data-template="skin-template"><?php esc_html_e( 'Add skin type', 'pls-private-label-store' ); ?></button>
            <div class="pls-repeater__template" id="skin-template">
              <div class="pls-repeater__row">
                <input type="text" name="skin[label][]" placeholder="<?php esc_attr_e( 'Skin type', 'pls-private-label-store' ); ?>" />
                <input type="text" name="skin[icon][]" placeholder="<?php esc_attr_e( 'Icon URL', 'pls-private-label-store' ); ?>" />
                <button type="button" class="button-link-delete pls-repeater-remove">&times;</button>
              </div>
            </div>
          </div>

          <div class="pls-repeater" data-group="benefits">
            <h3><?php esc_html_e( 'Benefits', 'pls-private-label-store' ); ?></h3>
            <div class="pls-repeater__rows">
              <?php if ( ! empty( $profile_benefits ) ) : ?>
                <?php foreach ( $profile_benefits as $row ) : ?>
                  <div class="pls-repeater__row">
                    <input type="text" name="benefits[label][]" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Benefit label', 'pls-private-label-store' ); ?>" />
                    <input type="text" name="benefits[icon][]" value="<?php echo esc_attr( $row['icon'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Icon URL', 'pls-private-label-store' ); ?>" />
                    <button type="button" class="button-link-delete pls-repeater-remove">&times;</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <button type="button" class="button pls-repeater-add" data-template="benefits-template"><?php esc_html_e( 'Add benefit', 'pls-private-label-store' ); ?></button>
            <div class="pls-repeater__template" id="benefits-template">
              <div class="pls-repeater__row">
                <input type="text" name="benefits[label][]" placeholder="<?php esc_attr_e( 'Benefit label', 'pls-private-label-store' ); ?>" />
                <input type="text" name="benefits[icon][]" placeholder="<?php esc_attr_e( 'Icon URL', 'pls-private-label-store' ); ?>" />
                <button type="button" class="button-link-delete pls-repeater-remove">&times;</button>
              </div>
            </div>
          </div>

          <div class="pls-repeater" data-group="ingredients">
            <h3><?php esc_html_e( 'Key ingredients', 'pls-private-label-store' ); ?></h3>
            <div class="pls-repeater__rows">
              <?php if ( ! empty( $profile_ingredients ) ) : ?>
                <?php foreach ( $profile_ingredients as $row ) : ?>
                  <div class="pls-repeater__row">
                    <input type="text" name="key_ingredients[label][]" value="<?php echo esc_attr( $row['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Ingredient label', 'pls-private-label-store' ); ?>" />
                    <input type="text" name="key_ingredients[icon][]" value="<?php echo esc_attr( $row['icon'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Icon URL', 'pls-private-label-store' ); ?>" />
                    <button type="button" class="button-link-delete pls-repeater-remove">&times;</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <button type="button" class="button pls-repeater-add" data-template="ingredients-template"><?php esc_html_e( 'Add ingredient', 'pls-private-label-store' ); ?></button>
            <div class="pls-repeater__template" id="ingredients-template">
              <div class="pls-repeater__row">
                <input type="text" name="key_ingredients[label][]" placeholder="<?php esc_attr_e( 'Ingredient label', 'pls-private-label-store' ); ?>" />
                <input type="text" name="key_ingredients[icon][]" placeholder="<?php esc_attr_e( 'Icon URL', 'pls-private-label-store' ); ?>" />
                <button type="button" class="button-link-delete pls-repeater-remove">&times;</button>
              </div>
            </div>
          </div>
        </div>

        <div class="pls-tab-panel" data-pls-tab="label">
          <table class="form-table">
            <tr>
              <th scope="row"><?php esc_html_e( 'Enable label application', 'pls-private-label-store' ); ?></th>
              <td>
                <label><input type="checkbox" name="label_enabled" <?php checked( $profile_record && (int) $profile_record->label_enabled === 1 ); ?> /> <?php esc_html_e( 'Allow customers to request label application', 'pls-private-label-store' ); ?></label>
              </td>
            </tr>
            <tr>
              <th scope="row"><label for="pls_label_price"><?php esc_html_e( 'Additional price per unit', 'pls-private-label-store' ); ?></label></th>
              <td><input type="text" name="label_price_per_unit" id="pls_label_price" value="<?php echo esc_attr( $profile_record->label_price_per_unit ?? '' ); ?>" class="small-text" /></td>
            </tr>
            <tr>
              <th scope="row"><?php esc_html_e( 'Require file upload', 'pls-private-label-store' ); ?></th>
              <td><label><input type="checkbox" name="label_requires_file" <?php checked( $profile_record && (int) $profile_record->label_requires_file === 1 ); ?> /> <?php esc_html_e( 'Require clients to upload a label file', 'pls-private-label-store' ); ?></label></td>
            </tr>
            <tr>
              <th scope="row"><label for="pls_label_helper"><?php esc_html_e( 'Helper text', 'pls-private-label-store' ); ?></label></th>
              <td><textarea name="label_helper_text" id="pls_label_helper" rows="3" class="large-text"><?php echo esc_textarea( $profile_record->label_helper_text ?? '' ); ?></textarea></td>
            </tr>
            <tr>
              <th scope="row"><label for="pls_label_guide"><?php esc_html_e( 'Link to label guide', 'pls-private-label-store' ); ?></label></th>
              <td><input type="url" name="label_guide_url" id="pls_label_guide" class="regular-text" value="<?php echo esc_attr( $profile_record->label_guide_url ?? '' ); ?>" /></td>
            </tr>
          </table>
        </div>

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
