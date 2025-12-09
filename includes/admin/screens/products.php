<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$action = isset( $_GET['pls_action'] ) ? sanitize_text_field( wp_unslash( $_GET['pls_action'] ) ) : '';
$notice = '';

if ( isset( $_POST['pls_product_modal_save'] ) && check_admin_referer( 'pls_product_modal_save' ) ) {
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

    $tiers_input = isset( $_POST['pack_tiers'] ) && is_array( $_POST['pack_tiers'] ) ? $_POST['pack_tiers'] : array();

    foreach ( $tiers_input as $tier_row ) {
        $units   = isset( $tier_row['units'] ) ? absint( $tier_row['units'] ) : 0;
        $tier_key = $units ? 'u' . $units : sanitize_key( wp_generate_uuid4() );
        $enabled = isset( $tier_row['enabled'] ) ? 1 : 0;
        $price   = isset( $tier_row['price'] ) ? floatval( $tier_row['price'] ) : 0;
        $sort    = isset( $tier_row['sort'] ) ? absint( $tier_row['sort'] ) : 0;

        if ( ! $units ) {
            continue;
        }

        PLS_Repo_Pack_Tier::upsert( $base_id, $tier_key, $units, $price, $enabled, $sort );
    }

    $gallery_raw = isset( $_POST['gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['gallery_ids'] ) ) : '';
    $gallery_ids = $gallery_raw ? array_map( 'absint', explode( ',', $gallery_raw ) ) : array();

    $selected_ingredients = isset( $_POST['ingredient_ids'] ) && is_array( $_POST['ingredient_ids'] ) ? array_map( 'absint', $_POST['ingredient_ids'] ) : array();
    $key_ingredients      = isset( $_POST['key_ingredient_ids'] ) && is_array( $_POST['key_ingredient_ids'] ) ? array_map( 'absint', $_POST['key_ingredient_ids'] ) : array();
    $new_ingredients_raw  = isset( $_POST['new_ingredients_tokens'] ) ? sanitize_text_field( wp_unslash( $_POST['new_ingredients_tokens'] ) ) : '';

    if ( $new_ingredients_raw ) {
        $tokens = array_filter( array_map( 'trim', explode( ',', $new_ingredients_raw ) ) );
        foreach ( $tokens as $token ) {
            $slug  = sanitize_title( $token );
            $maybe = term_exists( $slug, 'pls_ingredient' );
            if ( ! $maybe ) {
                $created = wp_insert_term( $token, 'pls_ingredient', array( 'slug' => $slug ) );
                if ( ! is_wp_error( $created ) ) {
                    $selected_ingredients[] = $created['term_id'];
                }
            } elseif ( is_array( $maybe ) && isset( $maybe['term_id'] ) ) {
                $selected_ingredients[] = absint( $maybe['term_id'] );
            } elseif ( is_object( $maybe ) && isset( $maybe->term_id ) ) {
                $selected_ingredients[] = absint( $maybe->term_id );
            }
        }
    }

    $selected_ingredients = array_unique( array_filter( $selected_ingredients ) );
    $key_ingredients      = array_unique( array_filter( $key_ingredients ) );

    $key_ingredients_json = array();
    $ingredient_names     = array();

    foreach ( $selected_ingredients as $term_id ) {
        $term = get_term( $term_id );
        if ( $term && ! is_wp_error( $term ) ) {
            $ingredient_names[] = $term->name;
        }
    }

    foreach ( $key_ingredients as $term_id ) {
        $term = get_term( $term_id );
        if ( $term && ! is_wp_error( $term ) ) {
            $key_ingredients_json[] = array(
                'label' => $term->name,
                'icon'  => PLS_Taxonomies::icon_for_term( $term_id ),
                'term_id' => $term_id,
            );
        }
    }

    $benefits_text = isset( $_POST['benefits_text'] ) ? wp_unslash( $_POST['benefits_text'] ) : '';
    $benefit_rows  = array();
    foreach ( preg_split( '/\r\n|\r|\n/', $benefits_text ) as $benefit_line ) {
        $clean = sanitize_text_field( $benefit_line );
        if ( '' !== $clean ) {
            $benefit_rows[] = array(
                'label' => $clean,
                'icon'  => '',
            );
        }
    }

    $skin_selected = isset( $_POST['skin_types'] ) && is_array( $_POST['skin_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['skin_types'] ) ) : array();
    $skin_rows     = array();
    foreach ( $skin_selected as $skin_label ) {
        $skin_rows[] = array(
            'label' => $skin_label,
            'icon'  => '',
        );
    }

    $profile_data = array(
        'short_description'    => isset( $_POST['short_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['short_description'] ) ) : '',
        'long_description'     => isset( $_POST['long_description'] ) ? wp_kses_post( wp_unslash( $_POST['long_description'] ) ) : '',
        'featured_image_id'    => isset( $_POST['featured_image_id'] ) ? absint( $_POST['featured_image_id'] ) : 0,
        'gallery_ids'          => $gallery_ids,
        'directions_text'      => isset( $_POST['directions_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['directions_text'] ) ) : '',
        'ingredients_list'     => $selected_ingredients ? implode( ',', $selected_ingredients ) : '',
        'label_enabled'        => isset( $_POST['label_enabled'] ) ? 1 : 0,
        'label_price_per_unit' => isset( $_POST['label_price_per_unit'] ) ? floatval( $_POST['label_price_per_unit'] ) : 0,
        'label_requires_file'  => isset( $_POST['label_requires_file'] ) ? 1 : 0,
        'label_helper_text'    => isset( $_POST['label_helper_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['label_helper_text'] ) ) : '',
        'label_guide_url'      => isset( $_POST['label_guide_url'] ) ? esc_url_raw( wp_unslash( $_POST['label_guide_url'] ) ) : '',
        'basics_json'          => array(),
        'skin_types_json'      => $skin_rows,
        'benefits_json'        => $benefit_rows,
        'key_ingredients_json' => $key_ingredients_json,
    );

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

$categories = array_filter(
    $categories,
    function( $term ) {
        return 'uncategorized' !== $term->slug;
    }
);

$ingredient_terms = get_terms(
    array(
        'taxonomy'   => 'pls_ingredient',
        'hide_empty' => false,
    )
);
if ( is_wp_error( $ingredient_terms ) ) {
    $ingredient_terms = array();
}

$pack_defaults = array( 50, 100, 250, 500, 1000 );
$skin_options  = array( 'Normal', 'Oily', 'Dry', 'Combination', 'Sensitive' );

$products = PLS_Repo_Base_Product::all();
$product_payload = array();

foreach ( $products as $product ) {
    $profile = PLS_Repo_Product_Profile::get_for_base( $product->id );
    $tiers   = PLS_Repo_Pack_Tier::for_base( $product->id );
    $profile_skin      = $profile && $profile->skin_types_json ? json_decode( $profile->skin_types_json, true ) : array();
    $profile_benefits  = $profile && $profile->benefits_json ? json_decode( $profile->benefits_json, true ) : array();
    $profile_key_ing   = $profile && $profile->key_ingredients_json ? json_decode( $profile->key_ingredients_json, true ) : array();
    $gallery_ids       = $profile && $profile->gallery_ids ? array_filter( array_map( 'absint', explode( ',', $profile->gallery_ids ) ) ) : array();
    $ingredient_ids    = $profile && $profile->ingredients_list ? array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) ) : array();

    $product_payload[] = array(
        'id'                 => $product->id,
        'name'               => $product->name,
        'slug'               => $product->slug,
        'status'             => $product->status,
        'categories'         => $product->category_path ? array_map( 'absint', explode( ',', $product->category_path ) ) : array(),
        'pack_tiers'         => $tiers,
        'short_description'  => $profile ? $profile->short_description : '',
        'long_description'   => $profile ? $profile->long_description : '',
        'directions_text'    => $profile ? $profile->directions_text : '',
        'ingredients_list'   => $ingredient_ids,
        'featured_image_id'  => $profile ? absint( $profile->featured_image_id ) : 0,
        'gallery_ids'        => $gallery_ids,
        'label_enabled'      => $profile ? absint( $profile->label_enabled ) : 0,
        'label_price_per_unit'=> $profile ? floatval( $profile->label_price_per_unit ) : 0,
        'label_requires_file'=> $profile ? absint( $profile->label_requires_file ) : 0,
        'label_helper_text'  => $profile ? $profile->label_helper_text : '',
        'label_guide_url'    => $profile ? $profile->label_guide_url : '',
        'skin_types'         => $profile_skin,
        'benefits'           => $profile_benefits,
        'key_ingredients'    => $profile_key_ing,
    );
}

wp_localize_script(
    'pls-admin',
    'PLS_ProductAdmin',
    array(
        'products'      => $product_payload,
        'packDefaults'  => $pack_defaults,
        'skinOptions'   => $skin_options,
        'ingredients'   => $ingredient_terms,
    )
);
?>
<div class="wrap pls-wrap pls-page-products">
  <div class="pls-page-head">
    <div>
      <p class="pls-label">Catalog</p>
      <h1><?php esc_html_e( 'PLS products', 'pls-private-label-store' ); ?></h1>
      <p class="description"><?php esc_html_e( 'Everything lives in PLS. Create items instantly with prefilled pack tiers and ingredient tags.', 'pls-private-label-store' ); ?></p>
    </div>
    <div>
      <button class="button button-primary button-hero" id="pls-open-product-modal"><?php esc_html_e( 'Add product', 'pls-private-label-store' ); ?></button>
    </div>
  </div>

  <?php if ( $notice ) : ?>
      <div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
  <?php endif; ?>
  <?php if ( 'saved' === $message ) : ?>
      <div class="notice notice-success"><p><?php esc_html_e( 'Product saved.', 'pls-private-label-store' ); ?></p></div>
  <?php endif; ?>

  <div class="pls-card-grid">
    <?php if ( empty( $products ) ) : ?>
        <p class="description"><?php esc_html_e( 'No products yet. Add your first private label item.', 'pls-private-label-store' ); ?></p>
    <?php else : ?>
        <?php foreach ( $products as $product ) : ?>
            <?php $profile = PLS_Repo_Product_Profile::get_for_base( $product->id ); ?>
            <?php
            $cat_labels = array();
            if ( $product->category_path ) {
                foreach ( array_map( 'absint', explode( ',', $product->category_path ) ) as $cat_id ) {
                    $term = get_term( $cat_id, 'product_cat' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $cat_labels[] = $term->name;
                    }
                }
            }
            ?>
            <div class="pls-card" data-product-id="<?php echo esc_attr( $product->id ); ?>">
              <div class="pls-card__heading">
                <strong><?php echo esc_html( $product->name ); ?></strong>
                <span class="pls-pill"><?php echo esc_html( ucfirst( $product->status ) ); ?></span>
              </div>
              <?php if ( $cat_labels ) : ?>
                  <div class="pls-chip"><?php esc_html_e( 'Categories', 'pls-private-label-store' ); ?>: <?php echo esc_html( implode( ', ', $cat_labels ) ); ?></div>
              <?php endif; ?>
              <?php if ( $profile && $profile->short_description ) : ?>
                  <p class="description"><?php echo esc_html( $profile->short_description ); ?></p>
              <?php endif; ?>
              <button class="button pls-edit-product" data-product-id="<?php echo esc_attr( $product->id ); ?>"><?php esc_html_e( 'Open editor', 'pls-private-label-store' ); ?></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="pls-modal" id="pls-product-modal">
    <div class="pls-modal__dialog">
      <div class="pls-modal__head">
        <div>
          <p class="pls-label"><?php esc_html_e( 'Apple-like modal', 'pls-private-label-store' ); ?></p>
          <h2 id="pls-modal-title"><?php esc_html_e( 'Create product', 'pls-private-label-store' ); ?></h2>
        </div>
        <button type="button" class="pls-modal__close" aria-label="Close">×</button>
      </div>
      <form method="post" class="pls-modern-form" id="pls-product-form">
        <?php wp_nonce_field( 'pls_product_modal_save' ); ?>
        <input type="hidden" name="pls_product_modal_save" value="1" />
        <input type="hidden" name="id" id="pls-product-id" />
        <input type="hidden" name="gallery_ids" id="pls-gallery-ids" />
        <input type="hidden" name="new_ingredients_tokens" id="pls-new-ingredients" />

        <div class="pls-modal__grid">
          <div class="pls-modal__section">
            <h3><?php esc_html_e( 'General', 'pls-private-label-store' ); ?></h3>
            <label><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?>
              <input type="text" name="name" id="pls-name" required placeholder="Collagen Serum" />
            </label>
            <label><?php esc_html_e( 'Slug', 'pls-private-label-store' ); ?>
              <input type="text" name="slug" id="pls-slug" placeholder="collagen-serum" />
            </label>
            <label><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?>
              <select name="status" id="pls-status">
                <option value="draft"><?php esc_html_e( 'Draft', 'pls-private-label-store' ); ?></option>
                <option value="live"><?php esc_html_e( 'Live', 'pls-private-label-store' ); ?></option>
              </select>
            </label>
            <label><?php esc_html_e( 'Categories', 'pls-private-label-store' ); ?>
              <select name="categories[]" id="pls-categories" multiple>
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label><?php esc_html_e( 'Short description', 'pls-private-label-store' ); ?>
              <textarea name="short_description" id="pls-short-description" rows="2"></textarea>
            </label>
            <label><?php esc_html_e( 'Long description', 'pls-private-label-store' ); ?>
              <textarea name="long_description" id="pls-long-description" rows="4"></textarea>
            </label>
          </div>

          <div class="pls-modal__section">
            <h3><?php esc_html_e( 'Media', 'pls-private-label-store' ); ?></h3>
            <div class="pls-media-picker">
              <input type="hidden" name="featured_image_id" id="pls-featured-id" />
              <div class="pls-media-preview" id="pls-featured-preview"></div>
              <button type="button" class="button" id="pls-pick-featured"><?php esc_html_e( 'Pick featured image', 'pls-private-label-store' ); ?></button>
            </div>
            <div class="pls-media-picker">
              <div class="pls-media-preview" id="pls-gallery-preview"></div>
              <button type="button" class="button" id="pls-pick-gallery"><?php esc_html_e( 'Select gallery images', 'pls-private-label-store' ); ?></button>
            </div>
            <label><?php esc_html_e( 'Directions for use', 'pls-private-label-store' ); ?>
              <textarea name="directions_text" id="pls-directions" rows="3"></textarea>
            </label>
          </div>
        </div>

        <div class="pls-modal__section">
          <h3><?php esc_html_e( 'Pack tiers', 'pls-private-label-store' ); ?></h3>
          <p class="description"><?php esc_html_e( 'Defaults are 50/100/250/500/1000 units. Adjust on the fly.', 'pls-private-label-store' ); ?></p>
          <div class="pls-pack-grid" id="pls-pack-grid">
            <?php foreach ( $pack_defaults as $i => $units ) : ?>
              <div class="pls-pack-row">
                <input type="hidden" name="pack_tiers[<?php echo esc_attr( $i ); ?>][sort]" value="<?php echo esc_attr( $i ); ?>" />
                <label><?php esc_html_e( 'Units', 'pls-private-label-store' ); ?>
                  <input type="number" name="pack_tiers[<?php echo esc_attr( $i ); ?>][units]" value="<?php echo esc_attr( $units ); ?>" />
                </label>
                <label><?php esc_html_e( 'Price per unit', 'pls-private-label-store' ); ?>
                  <input type="number" step="0.01" name="pack_tiers[<?php echo esc_attr( $i ); ?>][price]" />
                </label>
                <label class="pls-inline-checkbox"><input type="checkbox" name="pack_tiers[<?php echo esc_attr( $i ); ?>][enabled]" checked /> <?php esc_html_e( 'Enabled', 'pls-private-label-store' ); ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="pls-modal__grid">
          <div class="pls-modal__section">
            <h3><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></h3>
            <div class="pls-chip-group" id="pls-ingredient-chips">
              <?php foreach ( $ingredient_terms as $term ) : ?>
                  <label class="pls-chip-select"><input type="checkbox" name="ingredient_ids[]" value="<?php echo esc_attr( $term->term_id ); ?>" /> <?php echo esc_html( $term->name ); ?></label>
              <?php endforeach; ?>
            </div>
            <label><?php esc_html_e( 'Add new ingredients (comma to separate)', 'pls-private-label-store' ); ?>
              <input type="text" id="pls-ingredients-input" placeholder="Vitamin C, Retinol" />
            </label>
            <p><button type="button" class="button" id="pls-push-new-ingredients"><?php esc_html_e( 'Create missing in Ingredients base', 'pls-private-label-store' ); ?></button></p>
            <h4><?php esc_html_e( 'Key ingredients (show with icons)', 'pls-private-label-store' ); ?></h4>
            <div class="pls-chip-group" id="pls-key-ingredients">
              <?php foreach ( $ingredient_terms as $term ) : ?>
                  <label class="pls-chip-select"><input type="checkbox" name="key_ingredient_ids[]" value="<?php echo esc_attr( $term->term_id ); ?>" /> <?php echo esc_html( $term->name ); ?></label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="pls-modal__section">
            <h3><?php esc_html_e( 'Skin types & benefits', 'pls-private-label-store' ); ?></h3>
            <div class="pls-chip-group">
              <?php foreach ( $skin_options as $skin ) : ?>
                  <label class="pls-chip-select"><input type="checkbox" name="skin_types[]" value="<?php echo esc_attr( $skin ); ?>" /> <?php echo esc_html( $skin ); ?></label>
              <?php endforeach; ?>
            </div>
            <label><?php esc_html_e( 'Benefits (one per line)', 'pls-private-label-store' ); ?>
              <textarea name="benefits_text" id="pls-benefits" rows="4" placeholder="Hydrates instantly&#10;Boosts elasticity"></textarea>
            </label>
          </div>
        </div>

        <div class="pls-modal__section">
          <h3><?php esc_html_e( 'Label application', 'pls-private-label-store' ); ?></h3>
          <label class="pls-inline-checkbox"><input type="checkbox" name="label_enabled" id="pls-label-enabled" /> <?php esc_html_e( 'Offer label application', 'pls-private-label-store' ); ?></label>
          <label><?php esc_html_e( 'Additional price per unit', 'pls-private-label-store' ); ?>
            <input type="number" step="0.01" name="label_price_per_unit" id="pls-label-price" />
          </label>
          <label class="pls-inline-checkbox"><input type="checkbox" name="label_requires_file" id="pls-label-file" /> <?php esc_html_e( 'Require upload', 'pls-private-label-store' ); ?></label>
          <label><?php esc_html_e( 'Helper text', 'pls-private-label-store' ); ?>
            <textarea name="label_helper_text" id="pls-label-helper" rows="2"></textarea>
          </label>
          <label><?php esc_html_e( 'Label guide URL', 'pls-private-label-store' ); ?>
            <input type="url" name="label_guide_url" id="pls-label-guide" placeholder="https://..." />
          </label>
        </div>

        <div class="pls-modal__footer">
          <button type="button" class="button" id="pls-modal-cancel"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
          <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save product', 'pls-private-label-store' ); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

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
