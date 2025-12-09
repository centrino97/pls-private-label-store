<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$notice                = '';
$label_guide_constant  = 'https://bodocibiophysics.com/label-guide/';

if ( isset( $_POST['pls_product_modal_save'] ) && check_admin_referer( 'pls_product_modal_save' ) ) {
    $base_id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $slug_raw = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
    $status   = isset( $_POST['status'] ) && in_array( $_POST['status'], array( 'draft', 'live' ), true ) ? $_POST['status'] : 'draft';
    $slug     = $slug_raw ? sanitize_title( $slug_raw ) : sanitize_title( $name );

    $categories     = isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ? array_map( 'absint', $_POST['categories'] ) : array();
    $categories     = array_filter( $categories );
    $category_path  = $categories ? implode( ',', $categories ) : '';
    $has_base_name  = ( '' !== $name );

    if ( $has_base_name ) {
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
    }

    if ( $base_id ) {
        $tiers_input = isset( $_POST['pack_tiers'] ) && is_array( $_POST['pack_tiers'] ) ? $_POST['pack_tiers'] : array();

        foreach ( $tiers_input as $tier_row ) {
            $units    = isset( $tier_row['units'] ) ? absint( $tier_row['units'] ) : 0;
            $tier_key = $units ? 'u' . $units : sanitize_key( wp_generate_uuid4() );
            $enabled  = isset( $tier_row['enabled'] ) ? 1 : 0;
            $price    = isset( $tier_row['price'] ) ? floatval( $tier_row['price'] ) : 0;
            $sort     = isset( $tier_row['sort'] ) ? absint( $tier_row['sort'] ) : 0;

            if ( ! $units ) {
                continue;
            }

            PLS_Repo_Pack_Tier::upsert( $base_id, $tier_key, $units, $price, $enabled, $sort );
        }
    }

    $gallery_raw  = isset( $_POST['gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['gallery_ids'] ) ) : '';
    $gallery_ids  = $gallery_raw ? array_map( 'absint', explode( ',', $gallery_raw ) ) : array();
    $featured_id  = isset( $_POST['featured_image_id'] ) ? absint( $_POST['featured_image_id'] ) : 0;

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
                'label'  => $term->name,
                'icon'   => PLS_Taxonomies::icon_for_term( $term_id ),
                'term_id'=> $term_id,
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

    $attr_rows_input = isset( $_POST['attr_options'] ) && is_array( $_POST['attr_options'] ) ? $_POST['attr_options'] : array();
    $attr_rows       = array();

    foreach ( $attr_rows_input as $attr_row ) {
        $attr_id     = isset( $attr_row['attribute_id'] ) ? absint( $attr_row['attribute_id'] ) : 0;
        $value_id    = isset( $attr_row['value_id'] ) ? absint( $attr_row['value_id'] ) : 0;
        $attr_label  = isset( $attr_row['attribute_label'] ) ? sanitize_text_field( wp_unslash( $attr_row['attribute_label'] ) ) : '';
        $value_label = isset( $attr_row['value_label'] ) ? sanitize_text_field( wp_unslash( $attr_row['value_label'] ) ) : '';
        $price       = isset( $attr_row['price'] ) ? floatval( $attr_row['price'] ) : 0;

        if ( ! $attr_id && $attr_label ) {
            $attr_id = PLS_Repo_Attributes::insert_attr(
                array(
                    'label'        => $attr_label,
                    'is_variation' => 1,
                )
            );
        }

        if ( ! $value_id && $attr_id && $value_label ) {
            $value_id = PLS_Repo_Attributes::insert_value(
                array(
                    'attribute_id' => $attr_id,
                    'label'        => $value_label,
                )
            );
        }

        if ( $attr_id && $value_id ) {
            $attr_rows[] = array(
                'attribute_id'    => $attr_id,
                'attribute_label' => $attr_label,
                'value_id'        => $value_id,
                'value_label'     => $value_label,
                'price'           => $price,
            );
        }
    }

    if ( $base_id ) {
        $profile_data = array(
            'short_description'     => isset( $_POST['short_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['short_description'] ) ) : '',
            'long_description'      => isset( $_POST['long_description'] ) ? wp_kses_post( wp_unslash( $_POST['long_description'] ) ) : '',
            'featured_image_id'     => $featured_id,
            'gallery_ids'           => $gallery_ids,
            'directions_text'       => isset( $_POST['directions_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['directions_text'] ) ) : '',
            'ingredients_list'      => $selected_ingredients ? implode( ',', $selected_ingredients ) : '',
            'label_enabled'         => isset( $_POST['label_enabled'] ) ? 1 : 0,
            'label_price_per_unit'  => isset( $_POST['label_price_per_unit'] ) ? floatval( $_POST['label_price_per_unit'] ) : 0,
            'label_requires_file'   => isset( $_POST['label_requires_file'] ) ? 1 : 0,
            'label_helper_text'     => '',
            'label_guide_url'       => $label_guide_constant,
            'basics_json'           => array(),
            'skin_types_json'       => $skin_rows,
            'benefits_json'         => $benefit_rows,
            'key_ingredients_json'  => $key_ingredients_json,
        );

        PLS_Repo_Product_Profile::upsert( $base_id, $profile_data );
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
        'label_helper_text'    => '',
        'label_guide_url'      => $label_guide_constant,
        'basics_json'          => $attr_rows,
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

$attr_terms   = PLS_Repo_Attributes::attrs_all();
$attr_payload = array();

foreach ( $attr_terms as $attr ) {
    $values = PLS_Repo_Attributes::values_for_attr( $attr->id );
    $attr_payload[] = array(
        'id'     => $attr->id,
        'label'  => $attr->label,
        'values' => $values,
    );
}

$pack_defaults = array( 50, 100, 250, 500, 1000 );
$skin_options  = array( 'Normal', 'Oily', 'Dry', 'Combination', 'Sensitive' );

$products        = PLS_Repo_Base_Product::all();
$product_payload = array();

foreach ( $products as $product ) {
    $profile           = PLS_Repo_Product_Profile::get_for_base( $product->id );
    $tiers             = PLS_Repo_Pack_Tier::for_base( $product->id );
    $profile_skin      = $profile && $profile->skin_types_json ? json_decode( $profile->skin_types_json, true ) : array();
    $profile_benefits  = $profile && $profile->benefits_json ? json_decode( $profile->benefits_json, true ) : array();
    $profile_key_ing   = $profile && $profile->key_ingredients_json ? json_decode( $profile->key_ingredients_json, true ) : array();
    $profile_attrs     = $profile && $profile->basics_json ? json_decode( $profile->basics_json, true ) : array();
    $gallery_ids       = $profile && $profile->gallery_ids ? array_filter( array_map( 'absint', explode( ',', $profile->gallery_ids ) ) ) : array();
    $ingredient_ids    = $profile && $profile->ingredients_list ? array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) ) : array();

    $product_payload[] = array(
        'id'                  => $product->id,
        'name'                => $product->name,
        'slug'                => $product->slug,
        'status'              => $product->status,
        'categories'          => $product->category_path ? array_map( 'absint', explode( ',', $product->category_path ) ) : array(),
        'pack_tiers'          => $tiers,
        'short_description'   => $profile ? $profile->short_description : '',
        'long_description'    => $profile ? $profile->long_description : '',
        'directions_text'     => $profile ? $profile->directions_text : '',
        'ingredients_list'    => $ingredient_ids,
        'featured_image_id'   => $profile ? absint( $profile->featured_image_id ) : 0,
        'gallery_ids'         => $gallery_ids,
        'label_enabled'       => $profile ? absint( $profile->label_enabled ) : 0,
        'label_price_per_unit'=> $profile ? floatval( $profile->label_price_per_unit ) : 0,
        'label_requires_file' => $profile ? absint( $profile->label_requires_file ) : 0,
        'label_helper_text'   => '',
        'label_guide_url'     => $profile && ! empty( $profile->label_guide_url ) ? $profile->label_guide_url : $label_guide_constant,
        'skin_types'          => $profile_skin,
        'benefits'            => $profile_benefits,
        'key_ingredients'     => $profile_key_ing,
        'attributes'          => $profile_attrs,
    );
}

wp_localize_script(
    'pls-admin',
    'PLS_ProductAdmin',
    array(
        'products'     => $product_payload,
        'packDefaults' => $pack_defaults,
        'skinOptions'  => $skin_options,
        'ingredients'  => $ingredient_terms,
        'attributes'   => $attr_payload,
    )
);
?>
<div class="wrap pls-wrap pls-page-products">
  <div class="pls-page-head">
    <div>
      <p class="pls-label"><?php esc_html_e( 'Catalog', 'pls-private-label-store' ); ?></p>
      <h1><?php esc_html_e( 'PLS products', 'pls-private-label-store' ); ?></h1>
      <p class="description"><?php esc_html_e( 'Create and manage every SKU inside PLS. WooCommerce only receives the final variable product.', 'pls-private-label-store' ); ?></p>
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
    <?php if ( empty( $product_payload ) ) : ?>
        <p class="description"><?php esc_html_e( 'No products yet. Add your first private label item.', 'pls-private-label-store' ); ?></p>
    <?php else : ?>
        <?php foreach ( $product_payload as $product ) : ?>
            <?php
            $cat_labels = array();
            if ( ! empty( $product['categories'] ) ) {
                foreach ( $product['categories'] as $cat_id ) {
                    $term = get_term( $cat_id, 'product_cat' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $cat_labels[] = $term->name;
                    }
                }
            }
            ?>
            <div class="pls-card" data-product-id="<?php echo esc_attr( $product['id'] ); ?>">
              <div class="pls-card__heading">
                <strong><?php echo esc_html( $product['name'] ); ?></strong>
                <span class="pls-pill"><?php echo esc_html( ucfirst( $product['status'] ) ); ?></span>
              </div>
              <?php if ( $cat_labels ) : ?>
                  <div class="pls-chip"><?php esc_html_e( 'Categories', 'pls-private-label-store' ); ?>: <?php echo esc_html( implode( ', ', $cat_labels ) ); ?></div>
              <?php endif; ?>
              <?php if ( ! empty( $product['short_description'] ) ) : ?>
                  <p class="description"><?php echo esc_html( $product['short_description'] ); ?></p>
              <?php endif; ?>
              <button class="button pls-edit-product" data-product-id="<?php echo esc_attr( $product['id'] ); ?>"><?php esc_html_e( 'Open editor', 'pls-private-label-store' ); ?></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="pls-modal" id="pls-product-modal">
    <div class="pls-modal__dialog">
      <div class="pls-modal__head">
        <div>
          <h2 id="pls-modal-title"><?php esc_html_e( 'Create product', 'pls-private-label-store' ); ?></h2>
          <p class="description"><?php esc_html_e( 'Minimal, responsive editor with instant saves.', 'pls-private-label-store' ); ?></p>
        </div>
        <button type="button" class="pls-modal__close" aria-label="Close">×</button>
      </div>
      <form method="post" class="pls-modern-form" id="pls-product-form">
        <?php wp_nonce_field( 'pls_product_modal_save' ); ?>
        <input type="hidden" name="pls_product_modal_save" value="1" />
        <input type="hidden" name="id" id="pls-product-id" />
        <input type="hidden" name="gallery_ids" id="pls-gallery-ids" />
        <input type="hidden" name="new_ingredients_tokens" id="pls-new-ingredients" />

        <div class="pls-stepper">
          <div class="pls-stepper__nav" id="pls-stepper-nav">
            <button type="button" class="pls-stepper__item is-active" data-step="general"><?php esc_html_e( 'General', 'pls-private-label-store' ); ?></button>
            <button type="button" class="pls-stepper__item" data-step="data"><?php esc_html_e( 'Data', 'pls-private-label-store' ); ?></button>
            <button type="button" class="pls-stepper__item" data-step="ingredients"><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></button>
            <button type="button" class="pls-stepper__item" data-step="attributes"><?php esc_html_e( 'Attribute options', 'pls-private-label-store' ); ?></button>
            <button type="button" class="pls-stepper__item" data-step="packs"><?php esc_html_e( 'Pack tiers', 'pls-private-label-store' ); ?></button>
            <button type="button" class="pls-stepper__item" data-step="label"><?php esc_html_e( 'Label application', 'pls-private-label-store' ); ?></button>
          </div>

          <div class="pls-stepper__panels">
            <div class="pls-stepper__panel is-active" data-step="general">
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
                </div>
              </div>
            </div>

            <div class="pls-stepper__panel" data-step="data">
              <div class="pls-modal__grid pls-bento-grid">
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <p class="pls-label"><?php esc_html_e( 'Story', 'pls-private-label-store' ); ?></p>
                    <h3><?php esc_html_e( 'Descriptions', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle"><?php esc_html_e( 'Set the tone with a crisp overview and a thoughtful long-form note.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <label><?php esc_html_e( 'Short description', 'pls-private-label-store' ); ?>
                    <textarea name="short_description" id="pls-short-description" rows="2"></textarea>
                  </label>
                  <label><?php esc_html_e( 'Long description', 'pls-private-label-store' ); ?>
                    <textarea name="long_description" id="pls-long-description" rows="4"></textarea>
                  </label>
                </div>
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <p class="pls-label"><?php esc_html_e( 'How to use', 'pls-private-label-store' ); ?></p>
                    <h3><?php esc_html_e( 'Directions for use', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle"><?php esc_html_e( 'Keep directions standalone so customers can spot them instantly.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <textarea name="directions_text" id="pls-directions" rows="5" placeholder="Apply to cleansed skin and press gently until absorbed."></textarea>
                </div>
              </div>
              <div class="pls-modal__grid pls-bento-grid">
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <p class="pls-label"><?php esc_html_e( 'Skin match', 'pls-private-label-store' ); ?></p>
                    <h3><?php esc_html_e( 'Skin types', 'pls-private-label-store' ); ?></h3>
                  </div>
                  <div class="pls-chip-group">
                    <?php foreach ( $skin_options as $skin ) : ?>
                        <label class="pls-chip-select"><input type="checkbox" name="skin_types[]" value="<?php echo esc_attr( $skin ); ?>" /> <?php echo esc_html( $skin ); ?></label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <p class="pls-label"><?php esc_html_e( 'Results', 'pls-private-label-store' ); ?></p>
                    <h3><?php esc_html_e( 'Benefits', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle"><?php esc_html_e( 'One per line keeps bullets sharp and readable.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <textarea name="benefits_text" id="pls-benefits" rows="5" placeholder="Hydrates instantly&#10;Boosts elasticity"></textarea>
                </div>
              </div>
            </div>

            <div class="pls-stepper__panel" data-step="ingredients">
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
                </div>
                <div class="pls-modal__section">
                  <h3><?php esc_html_e( 'Key ingredients (show with icons)', 'pls-private-label-store' ); ?></h3>
                  <div class="pls-chip-group" id="pls-key-ingredients">
                    <?php foreach ( $ingredient_terms as $term ) : ?>
                        <label class="pls-chip-select"><input type="checkbox" name="key_ingredient_ids[]" value="<?php echo esc_attr( $term->term_id ); ?>" /> <?php echo esc_html( $term->name ); ?></label>
                    <?php endforeach; ?>
                  </div>
                  <p class="description"><?php esc_html_e( 'Upload ingredient icons from the Ingredients base screen for better previews.', 'pls-private-label-store' ); ?></p>
                </div>
              </div>
            </div>

            <div class="pls-stepper__panel" data-step="attributes">
              <div class="pls-modal__section">
                <div class="pls-section-heading">
                  <p class="pls-label"><?php esc_html_e( 'Attributes', 'pls-private-label-store' ); ?></p>
                  <h3><?php esc_html_e( 'Attribute options', 'pls-private-label-store' ); ?></h3>
                  <p class="pls-subtle"><?php esc_html_e( 'Pick an existing attribute or craft a new one, then pair it with a value and price impact.', 'pls-private-label-store' ); ?></p>
                </div>
                <div id="pls-attribute-rows" class="pls-attribute-rows"></div>
                <button type="button" class="button" id="pls-add-attribute-row"><?php esc_html_e( 'Add attribute option', 'pls-private-label-store' ); ?></button>
                <div id="pls-attribute-template" class="hidden">
                  <div class="pls-attribute-row">
                    <div class="pls-attribute-row__grid">
                      <div class="pls-attribute-card">
                        <p class="pls-micro"><?php esc_html_e( 'Attribute', 'pls-private-label-store' ); ?></p>
                        <select class="pls-attr-select" name="">
                          <option value=""><?php esc_html_e( 'Select attribute', 'pls-private-label-store' ); ?></option>
                          <option value="__new__"><?php esc_html_e( 'Create new attribute', 'pls-private-label-store' ); ?></option>
                          <?php foreach ( $attr_payload as $attr ) : ?>
                              <option value="<?php echo esc_attr( $attr['id'] ); ?>"><?php echo esc_html( $attr['label'] ); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <div class="pls-attr-new-wrap">
                          <label><?php esc_html_e( 'New attribute label', 'pls-private-label-store' ); ?>
                            <input type="text" class="pls-attr-new" placeholder="Add a clean label" />
                          </label>
                        </div>
                      </div>
                      <div class="pls-attribute-card">
                        <div class="pls-attr-value-stack">
                          <label><?php esc_html_e( 'Value', 'pls-private-label-store' ); ?>
                            <select class="pls-attr-value" data-placeholder="<?php esc_attr_e( 'Select value', 'pls-private-label-store' ); ?>" name=""></select>
                          </label>
                          <div class="pls-attr-value-new-wrap">
                            <label><?php esc_html_e( 'New value label', 'pls-private-label-store' ); ?>
                              <input type="text" class="pls-attr-value-new" placeholder="Ex: Frosted 50ml" />
                            </label>
                          </div>
                        </div>
                        <div class="pls-price-inline">
                          <label><?php esc_html_e( 'Price impact for this value', 'pls-private-label-store' ); ?>
                            <input type="number" step="0.01" class="pls-attr-price" />
                          </label>
                        </div>
                      </div>
                    </div>
                    <button type="button" class="button-link-delete pls-attribute-remove"><?php esc_html_e( 'Remove', 'pls-private-label-store' ); ?></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="pls-stepper__panel" data-step="packs">
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
            </div>

            <div class="pls-stepper__panel" data-step="label">
              <div class="pls-modal__section">
                <div class="pls-section-heading">
                  <p class="pls-label"><?php esc_html_e( 'Label customization', 'pls-private-label-store' ); ?></p>
                  <h3><?php esc_html_e( 'Application & pricing', 'pls-private-label-store' ); ?></h3>
                  <p class="pls-subtle"><?php esc_html_e( 'Decide how labels are applied, priced, and when artwork is collected.', 'pls-private-label-store' ); ?></p>
                </div>
                <div class="pls-label-flex">
                  <label class="pls-toggle-card"><input type="checkbox" name="label_enabled" id="pls-label-enabled" />
                    <div>
                      <strong><?php esc_html_e( 'Offer label application', 'pls-private-label-store' ); ?></strong>
                      <p class="pls-subtle"><?php esc_html_e( 'Apply customer artwork to every unit for a polished finish.', 'pls-private-label-store' ); ?></p>
                    </div>
                  </label>
                  <div class="pls-label-price">
                    <label><?php esc_html_e( 'Price per unit for application', 'pls-private-label-store' ); ?>
                      <input type="number" step="0.01" name="label_price_per_unit" id="pls-label-price" placeholder="0.00" />
                    </label>
                  </div>
                  <label class="pls-toggle-card"><input type="checkbox" name="label_requires_file" id="pls-label-file" />
                    <div>
                      <strong><?php esc_html_e( 'Require label upload now', 'pls-private-label-store' ); ?></strong>
                      <p class="pls-subtle"><?php esc_html_e( 'If customers want their own design applied, collect the file right away.', 'pls-private-label-store' ); ?></p>
                    </div>
                  </label>
                </div>
                <div class="pls-design-callout">
                  <div>
                    <p class="pls-label"><?php esc_html_e( 'Label design', 'pls-private-label-store' ); ?></p>
                    <h4><?php esc_html_e( 'Offer label design support', 'pls-private-label-store' ); ?></h4>
                    <p class="pls-subtle"><?php esc_html_e( 'We will reach out to customers after they select this to craft their artwork.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <div class="pls-guide-link">
                    <p class="pls-subtle"><?php esc_html_e( 'Label guide', 'pls-private-label-store' ); ?>: <a href="<?php echo esc_url( $label_guide_constant ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $label_guide_constant ); ?></a></p>
                  </div>
                </div>
                <input type="hidden" name="label_helper_text" value="" />
                <input type="hidden" name="label_guide_url" id="pls-label-guide" value="<?php echo esc_attr( $label_guide_constant ); ?>" />
              </div>
            </div>
          </div>
        </div>

        <div class="pls-modal__footer">
          <div class="pls-stepper__controls">
            <button type="button" class="button" id="pls-step-prev"><?php esc_html_e( 'Back', 'pls-private-label-store' ); ?></button>
            <button type="button" class="button button-primary" id="pls-step-next"><?php esc_html_e( 'Next', 'pls-private-label-store' ); ?></button>
          </div>
          <div class="pls-stepper__actions">
            <button type="button" class="button" id="pls-modal-cancel"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
            <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save product', 'pls-private-label-store' ); ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
