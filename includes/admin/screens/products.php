<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$notice                = '';
$label_guide_constant  = 'https://bodocibiophysics.com/label-guide/';

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

$ingredient_terms = PLS_Admin_Ajax::ingredient_payload();

$attr_payload = PLS_Admin_Ajax::attribute_payload();

// Get pack tier defaults from attribute system
require_once PLS_PLS_DIR . 'includes/core/class-pls-tier-rules.php';
$pack_defaults = array();
$primary_attr = PLS_Repo_Attributes::get_primary_attribute();
if ( $primary_attr ) {
    $tier_values = PLS_Repo_Attributes::values_for_attr( $primary_attr->id );
    foreach ( $tier_values as $tier_value ) {
        $units = PLS_Tier_Rules::get_default_units_for_tier( $tier_value->id );
        $price = PLS_Tier_Rules::get_default_price_per_unit( $tier_value->id );
        if ( $units ) {
            $pack_defaults[] = array(
                'units' => $units,
                'price' => $price ?: 0,
                'label' => $tier_value->label,
            );
        }
    }
}
// Fallback to hardcoded defaults if no tiers found
if ( empty( $pack_defaults ) ) {
    $pack_defaults = array(
        array( 'units' => 50, 'price' => 15.90, 'label' => 'Tier 1' ),
        array( 'units' => 100, 'price' => 14.50, 'label' => 'Tier 2' ),
        array( 'units' => 250, 'price' => 12.50, 'label' => 'Tier 3' ),
        array( 'units' => 500, 'price' => 9.50, 'label' => 'Tier 4' ),
        array( 'units' => 1000, 'price' => 7.90, 'label' => 'Tier 5' ),
    );
}
$skin_options  = array( 'Normal', 'Oily', 'Dry', 'Combination', 'Sensitive' );

$products        = PLS_Admin_Ajax::reconcile_orphaned_products( PLS_Repo_Base_Product::all() );
$product_payload = array();

foreach ( $products as $product ) {
    $formatted = PLS_Admin_Ajax::format_product_payload( $product, $label_guide_constant );
    if ( $formatted ) {
        $product_payload[] = $formatted;
    }
}

wp_localize_script(
    'pls-admin',
    'PLS_ProductAdmin',
    array(
        'products'     => $product_payload,
        'packDefaults' => $pack_defaults, // Now array of objects with units, price, label
        'skinOptions'  => $skin_options,
        'ingredients'  => $ingredient_terms,
        'attributes'   => $attr_payload,
        'defaultIngredientIcon' => PLS_Taxonomies::default_icon(),
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
      <button class="button" id="pls-sync-all"><?php esc_html_e( 'Sync all to Woo', 'pls-private-label-store' ); ?></button>
      <button class="button" id="pls-open-custom-request-modal"><?php esc_html_e( 'Request Custom Product', 'pls-private-label-store' ); ?></button>
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
            $sync_status = isset( $product['sync_status'] ) ? $product['sync_status'] : null;
            $sync_label  = __( 'Not synced yet.', 'pls-private-label-store' );
            if ( $sync_status ) {
                $status_prefix = ! empty( $sync_status['success'] ) ? __( 'Synced', 'pls-private-label-store' ) : __( 'Sync failed', 'pls-private-label-store' );
                $ts            = ! empty( $sync_status['timestamp'] ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $sync_status['timestamp'] ) : '';
                $msg           = ! empty( $sync_status['message'] ) ? $sync_status['message'] : '';
                $sync_label    = trim( $status_prefix . ( $ts ? ' – ' . $ts : '' ) . ( $msg ? ' – ' . $msg : '' ) );
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
              <p class="description pls-sync-meta" data-product-id="<?php echo esc_attr( $product['id'] ); ?>"><?php echo esc_html( $sync_label ); ?></p>
              <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button class="button pls-edit-product" data-product-id="<?php echo esc_attr( $product['id'] ); ?>"><?php esc_html_e( 'Open editor', 'pls-private-label-store' ); ?></button>
                <?php if ( ! empty( $product['wc_product_id'] ) ) : ?>
                  <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-product-preview&product_id=' . $product['id'] . '&wc_id=' . $product['wc_product_id'] ) ); ?>" class="button" target="_blank"><?php esc_html_e( 'Preview Frontend', 'pls-private-label-store' ); ?></a>
                <?php else : ?>
                  <button class="button" disabled title="<?php esc_attr_e( 'Sync product first to preview', 'pls-private-label-store' ); ?>"><?php esc_html_e( 'Preview Frontend', 'pls-private-label-store' ); ?></button>
                <?php endif; ?>
                <button class="button pls-sync-product" data-product-id="<?php echo esc_attr( $product['id'] ); ?>" data-wc-product-id="<?php echo esc_attr( isset( $product['wc_product_id'] ) ? $product['wc_product_id'] : 0 ); ?>"><?php esc_html_e( 'Sync', 'pls-private-label-store' ); ?></button>
                <button class="button-link-delete pls-delete-product" data-product-id="<?php echo esc_attr( $product['id'] ); ?>"><?php esc_html_e( 'Delete', 'pls-private-label-store' ); ?></button>
              </div>
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
      <div class="pls-mode-toggle">
        <button type="button" class="pls-mode-btn is-active" data-mode="builder"><?php esc_html_e( 'Builder', 'pls-private-label-store' ); ?></button>
        <button type="button" class="pls-mode-btn" data-mode="preview"><?php esc_html_e( 'Preview', 'pls-private-label-store' ); ?></button>
      </div>
      <form method="post" class="pls-modern-form" id="pls-product-form">
        <?php wp_nonce_field( 'pls_product_modal_save' ); ?>
        <input type="hidden" name="pls_product_modal_save" value="1" />
        <input type="hidden" name="id" id="pls-product-id" />
        <input type="hidden" name="gallery_ids" id="pls-gallery-ids" />
        <div class="notice notice-error pls-form-errors" id="pls-product-errors" style="display:none;">
          <p><?php esc_html_e( 'Please review the highlighted issues before saving.', 'pls-private-label-store' ); ?></p>
          <ul></ul>
        </div>

        <div class="pls-stepper">
          <div class="pls-stepper__nav" id="pls-stepper-nav">
            <button type="button" class="pls-stepper__item is-active" data-step="general"><?php esc_html_e( 'General', 'pls-private-label-store' ); ?></button>
            <button type="button" class="pls-stepper__item" data-step="data"><?php esc_html_e( 'Data', 'pls-private-label-store' ); ?></button>
            <button type="button" class="pls-stepper__item" data-step="ingredients"><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></button>
            <button type="button" class="pls-stepper__item" data-step="packs">
              <span class="pls-primary-badge" style="background: #2271b1; color: #fff; padding: 2px 6px; border-radius: 2px; font-size: 9px; margin-right: 6px;">PRIMARY</span>
              <?php esc_html_e( 'Pack tiers', 'pls-private-label-store' ); ?>
            </button>
            <button type="button" class="pls-stepper__item" data-step="attributes"><?php esc_html_e( 'Product options', 'pls-private-label-store' ); ?></button>
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
                  <p class="pls-subtle"><?php esc_html_e( 'Status is saved as Draft. Activate products from the PLS products table when ready.', 'pls-private-label-store' ); ?></p>
                  <div class="pls-field-stack">
                    <p class="pls-micro"><?php esc_html_e( 'Categories', 'pls-private-label-store' ); ?></p>
                    <div class="pls-chip-group" id="pls-category-pills">
                      <?php foreach ( $categories as $cat ) : ?>
                          <label class="pls-chip-select"><input type="checkbox" name="categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" /> <?php echo esc_html( $cat->name ); ?></label>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <div class="pls-modal__section">
                  <h3><?php esc_html_e( 'Media', 'pls-private-label-store' ); ?></h3>
                  <div class="pls-media-picker">
                    <p class="pls-micro"><?php esc_html_e( 'Featured image', 'pls-private-label-store' ); ?></p>
                    <input type="hidden" name="featured_image_id" id="pls-featured-id" />
                    <div class="pls-media-preview pls-media-preview--single" id="pls-featured-preview"></div>
                    <button type="button" class="button" id="pls-pick-featured"><?php esc_html_e( 'Pick featured image', 'pls-private-label-store' ); ?></button>
                  </div>
                  <div class="pls-media-picker">
                    <p class="pls-micro"><?php esc_html_e( 'Gallery images', 'pls-private-label-store' ); ?></p>
                    <div class="pls-media-preview pls-media-preview--grid" id="pls-gallery-preview"></div>
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
                    <textarea name="short_description" id="pls-short-description" class="pls-rich-textarea" rows="3" placeholder="<?php esc_attr_e( 'E.g., A brightening daily serum that firms, hydrates, and boosts glow.', 'pls-private-label-store' ); ?>"></textarea>
                    <span class="pls-field-hint"><?php esc_html_e( 'Keep this to 1-2 punchy sentences for cards and list views.', 'pls-private-label-store' ); ?></span>
                  </label>
                  <label><?php esc_html_e( 'Long description', 'pls-private-label-store' ); ?>
                    <textarea name="long_description" id="pls-long-description" class="pls-rich-textarea" rows="8" placeholder="<?php esc_attr_e( "Tell the full story: formula philosophy, skin feel, routine placement, and standout results.", 'pls-private-label-store' ); ?>"></textarea>
                    <span class="pls-field-hint"><?php esc_html_e( 'Use paragraphs, bullet points, or line breaks for richer storytelling.', 'pls-private-label-store' ); ?></span>
                  </label>
                </div>
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <p class="pls-label"><?php esc_html_e( 'How to use', 'pls-private-label-store' ); ?></p>
                    <h3><?php esc_html_e( 'Directions for use', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle"><?php esc_html_e( 'Keep directions standalone so customers can spot them instantly.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <textarea name="directions_text" id="pls-directions" class="pls-rich-textarea" rows="5" placeholder="Apply to cleansed skin and press gently until absorbed."></textarea>
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
                  <textarea name="benefits_text" id="pls-benefits" class="pls-rich-textarea" rows="5" placeholder="Hydrates instantly&#10;Boosts elasticity"></textarea>
                </div>
              </div>
            </div>

            <div class="pls-stepper__panel" data-step="ingredients">
              <div class="pls-modal__grid">
                <div class="pls-modal__section">
                  <h3><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></h3>
                  <div class="pls-field-stack">
                    <label class="pls-field-stack">
                      <span class="pls-micro"><?php esc_html_e( 'Search ingredients', 'pls-private-label-store' ); ?></span>
                      <input type="search" id="pls-ingredient-search" placeholder="<?php esc_attr_e( 'Search ingredients', 'pls-private-label-store' ); ?>" />
                    </label>
                    <button type="button" class="button" id="pls-open-ingredient-create"><?php esc_html_e( 'Create ingredient', 'pls-private-label-store' ); ?></button>
                  </div>
                  <div class="pls-ingredient-columns">
                    <div class="pls-ingredient-panel">
                      <p class="pls-micro"><?php esc_html_e( 'All ingredients', 'pls-private-label-store' ); ?></p>
                      <div class="pls-chip-group pls-ingredient-list" id="pls-ingredient-chips" data-default-icon="<?php echo esc_attr( PLS_Taxonomies::default_icon() ); ?>"></div>
                    </div>
                    <div class="pls-ingredient-panel">
                      <p class="pls-micro"><?php esc_html_e( 'Selected', 'pls-private-label-store' ); ?> (<span id="pls-selected-count">0</span>)</p>
                      <div class="pls-chip-group" id="pls-selected-ingredients"></div>
                    </div>
                  </div>
                </div>
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <p class="pls-label"><?php esc_html_e( 'Spotlight picks', 'pls-private-label-store' ); ?></p>
                    <h3><?php esc_html_e( 'Key ingredients', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle" id="pls-key-ingredients-hint" data-ready-text="<?php esc_attr_e( 'Choose which ingredients to spotlight with icons (up to 5).', 'pls-private-label-store' ); ?>"><?php esc_html_e( 'Select ingredients on the left to spotlight them here.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <div class="pls-key-ingredients-header">
                    <strong id="pls-key-counter"><?php esc_html_e( 'Key ingredients: 0 / 5', 'pls-private-label-store' ); ?></strong>
                    <span class="pls-key-limit-message" id="pls-key-limit-message" aria-live="polite"></span>
                  </div>
                  <p class="pls-subtle"><?php esc_html_e( 'Pick your hero ingredients and keep their icons aligned with the base list.', 'pls-private-label-store' ); ?></p>
                  <div class="pls-chip-group pls-key-ingredients-list" id="pls-key-ingredients"></div>
                </div>
              </div>
            </div>

            <div class="pls-stepper__panel" data-step="packs">
              <div class="pls-modal__section" style="border-left: 4px solid #2271b1; padding-left: 20px;">
                <div class="pls-section-heading">
                  <p class="pls-label"><?php esc_html_e( 'PRIMARY OPTION', 'pls-private-label-store' ); ?></p>
                  <h3><?php esc_html_e( 'Pack tiers', 'pls-private-label-store' ); ?></h3>
                  <p class="pls-subtle"><?php esc_html_e( 'Default values are pre-filled from your settings. You can adjust units and prices per product.', 'pls-private-label-store' ); ?></p>
                </div>
                <div class="pls-pack-grid" id="pls-pack-grid">
                  <?php foreach ( $pack_defaults as $i => $tier ) : ?>
                    <?php
                    $units = isset( $tier['units'] ) ? $tier['units'] : $tier;
                    $price = isset( $tier['price'] ) ? $tier['price'] : 0;
                    $label = isset( $tier['label'] ) ? $tier['label'] : '';
                    ?>
                    <div class="pls-pack-row">
                      <input type="hidden" name="pack_tiers[<?php echo esc_attr( $i ); ?>][sort]" value="<?php echo esc_attr( $i ); ?>" />
                      <?php if ( $label ) : ?>
                        <div style="font-weight: 600; margin-bottom: 8px; color: #2271b1;"><?php echo esc_html( $label ); ?></div>
                      <?php endif; ?>
                      <label><?php esc_html_e( 'Units', 'pls-private-label-store' ); ?>
                        <input type="number" name="pack_tiers[<?php echo esc_attr( $i ); ?>][units]" value="<?php echo esc_attr( $units ); ?>" min="1" />
                      </label>
                      <label><?php esc_html_e( 'Price per unit', 'pls-private-label-store' ); ?>
                        <input type="number" step="0.01" class="pls-price-input" name="pack_tiers[<?php echo esc_attr( $i ); ?>][price]" value="<?php echo esc_attr( $price ); ?>" min="0" />
                      </label>
                      <div style="padding: 8px 0;">
                        <strong style="color: #2271b1;"><?php esc_html_e( 'Total:', 'pls-private-label-store' ); ?> $<span class="pls-tier-total"><?php echo number_format( $units * $price, 2 ); ?></span></strong>
                      </div>
                      <label class="pls-inline-checkbox"><input type="checkbox" name="pack_tiers[<?php echo esc_attr( $i ); ?>][enabled]" checked /> <?php esc_html_e( 'Enabled', 'pls-private-label-store' ); ?></label>
                    </div>
                  <?php endforeach; ?>
                </div>
                
                <!-- Live Price Calculator -->
                <div class="pls-price-calculator" id="pls-live-calculator" style="margin-top: 24px; padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                  <h4 style="margin-top: 0; font-size: 16px;"><?php esc_html_e( 'Estimated Price Calculator', 'pls-private-label-store' ); ?></h4>
                  <div class="pls-calc-tier-selector" style="margin-bottom: 12px;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                      <span><?php esc_html_e( 'Calculate for:', 'pls-private-label-store' ); ?></span>
                      <select id="pls-calc-tier-select" style="padding: 4px 8px;">
                        <option value="1"><?php esc_html_e( 'Tier 1 (50 units)', 'pls-private-label-store' ); ?></option>
                        <option value="2"><?php esc_html_e( 'Tier 2 (100 units)', 'pls-private-label-store' ); ?></option>
                        <option value="3"><?php esc_html_e( 'Tier 3 (250 units)', 'pls-private-label-store' ); ?></option>
                        <option value="4"><?php esc_html_e( 'Tier 4 (500 units)', 'pls-private-label-store' ); ?></option>
                        <option value="5"><?php esc_html_e( 'Tier 5 (1000 units)', 'pls-private-label-store' ); ?></option>
                      </select>
                    </label>
                  </div>
                  <div class="pls-calc-breakdown">
                    <div class="pls-calc-row" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                      <span id="pls-calc-base-label"><?php esc_html_e( 'Base Price', 'pls-private-label-store' ); ?></span>
                      <strong id="pls-calc-base-price">$0.00</strong>
                    </div>
                    <div id="pls-calc-addons" style="margin-top: 8px;">
                      <!-- Addons will be populated by JavaScript -->
                    </div>
                    <div class="pls-calc-total" style="display: flex; justify-content: space-between; padding: 12px 0; margin-top: 12px; border-top: 2px solid #2271b1; font-size: 18px; font-weight: 600;">
                      <span><?php esc_html_e( 'Total Price', 'pls-private-label-store' ); ?></span>
                      <strong id="pls-calc-total-price" style="color: #2271b1;">$0.00</strong>
                    </div>
                  </div>
                  <p class="pls-subtle" style="margin-top: 12px; font-size: 12px;"><?php esc_html_e( 'Prices shown are estimates. Select different pack tiers and options to see pricing variations.', 'pls-private-label-store' ); ?></p>
                </div>
              </div>
            </div>

            <div class="pls-stepper__panel" data-step="attributes">
              <div class="pls-modal__grid">
                <!-- Package Type Section -->
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <p class="pls-label"><?php esc_html_e( 'PACKAGE CONFIGURATION', 'pls-private-label-store' ); ?></p>
                    <h3><?php esc_html_e( 'Package Type', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle"><?php esc_html_e( 'Choose the container size for this product.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <div class="pls-attribute-group" id="pls-package-type-group">
                    <label><?php esc_html_e( 'Container Size', 'pls-private-label-store' ); ?>
                      <select name="package_type_attr" id="pls-package-type-select" class="pls-package-type-select">
                        <option value=""><?php esc_html_e( 'Select package type', 'pls-private-label-store' ); ?></option>
                        <?php
                        // Find Package Type attribute
                        $package_type_attr = null;
                        foreach ( $attr_payload as $attr ) {
                            if ( isset( $attr['attr_key'] ) && $attr['attr_key'] === 'package-type' ) {
                                $package_type_attr = $attr;
                                break;
                            }
                        }
                        if ( $package_type_attr && ! empty( $package_type_attr['values'] ) ) {
                            foreach ( $package_type_attr['values'] as $value ) {
                                ?>
                                <option value="<?php echo esc_attr( $value['id'] ); ?>" data-label="<?php echo esc_attr( $value['label'] ); ?>">
                                    <?php echo esc_html( $value['label'] ); ?>
                                </option>
                                <?php
                            }
                        }
                        ?>
                      </select>
                    </label>
                    <p class="pls-field-hint"><?php esc_html_e( 'Select the container size (30ml, 50ml, 120ml bottle or 50gr jar).', 'pls-private-label-store' ); ?></p>
                  </div>
                </div>

                <!-- Package Color Section -->
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <h3><?php esc_html_e( 'Package Color/Finish', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle"><?php esc_html_e( 'Choose the glass finish. Prices vary by pack tier.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <div class="pls-color-options" id="pls-package-color-options">
                    <?php
                    // Find Package Color attribute
                    $package_color_attr = null;
                    foreach ( $attr_payload as $attr ) {
                        if ( isset( $attr['attr_key'] ) && ( $attr['attr_key'] === 'package-color' || $attr['attr_key'] === 'package-colour' ) ) {
                            $package_color_attr = $attr;
                            break;
                        }
                    }
                    if ( $package_color_attr && ! empty( $package_color_attr['values'] ) ) {
                        foreach ( $package_color_attr['values'] as $value ) {
                            $tier_prices = isset( $value['tier_price_overrides'] ) && is_array( $value['tier_price_overrides'] ) ? $value['tier_price_overrides'] : null;
                            $default_price = isset( $value['price'] ) ? floatval( $value['price'] ) : 0;
                            $is_standard = ( strpos( strtolower( $value['label'] ), 'standard' ) !== false || strpos( strtolower( $value['label'] ), 'clear' ) !== false );
                            ?>
                            <label class="pls-option-card">
                                <input type="checkbox" name="package_colors[]" value="<?php echo esc_attr( $value['id'] ); ?>" 
                                       data-value-id="<?php echo esc_attr( $value['id'] ); ?>"
                                       <?php echo $is_standard ? 'checked disabled' : ''; ?> />
                                <div>
                                    <strong><?php echo esc_html( $value['label'] ); ?></strong>
                                    <?php if ( $is_standard ) : ?>
                                        <span class="pls-price-badge"><?php esc_html_e( 'Included', 'pls-private-label-store' ); ?></span>
                                    <?php else : ?>
                                        <span class="pls-price-badge" data-tier-prices="<?php echo esc_attr( $tier_prices ? wp_json_encode( $tier_prices ) : '' ); ?>" 
                                              data-default-price="<?php echo esc_attr( $default_price ); ?>">
                                            <?php
                                            if ( $tier_prices && isset( $tier_prices[1] ) ) {
                                                echo '+$' . number_format( floatval( $tier_prices[1] ), 2 ) . ' ' . esc_html__( '(Tier 1)', 'pls-private-label-store' );
                                            } elseif ( $default_price > 0 ) {
                                                echo '+$' . number_format( $default_price, 2 );
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </label>
                            <?php
                        }
                    }
                    ?>
                  </div>
                </div>

                <!-- Package Cap Section -->
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <h3><?php esc_html_e( 'Package Cap/Applicator', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle"><?php esc_html_e( 'Select compatible caps for your chosen package type. Silver options have tier-variable pricing.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <div class="pls-cap-matrix" id="pls-cap-compatibility">
                    <?php
                    // Find Package Cap attribute
                    $package_cap_attr = null;
                    foreach ( $attr_payload as $attr ) {
                        if ( isset( $attr['attr_key'] ) && $attr['attr_key'] === 'package-cap' ) {
                            $package_cap_attr = $attr;
                            break;
                        }
                    }
                    if ( $package_cap_attr && ! empty( $package_cap_attr['values'] ) ) {
                        foreach ( $package_cap_attr['values'] as $value ) {
                            $tier_prices = isset( $value['tier_price_overrides'] ) && is_array( $value['tier_price_overrides'] ) ? $value['tier_price_overrides'] : null;
                            $default_price = isset( $value['price'] ) ? floatval( $value['price'] ) : 0;
                            $is_silver = ( strpos( strtolower( $value['label'] ), 'silver' ) !== false );
                            ?>
                            <label class="pls-option-card">
                                <input type="checkbox" name="package_caps[]" value="<?php echo esc_attr( $value['id'] ); ?>" 
                                       data-value-id="<?php echo esc_attr( $value['id'] ); ?>" />
                                <div>
                                    <strong><?php echo esc_html( $value['label'] ); ?></strong>
                                    <?php if ( $is_silver && $tier_prices ) : ?>
                                        <span class="pls-price-badge" data-tier-prices="<?php echo esc_attr( wp_json_encode( $tier_prices ) ); ?>">
                                            <?php echo '+$' . number_format( floatval( $tier_prices[1] ), 2 ) . ' ' . esc_html__( '(Tier 1)', 'pls-private-label-store' ); ?>
                                        </span>
                                    <?php elseif ( $default_price > 0 ) : ?>
                                        <span class="pls-price-badge">+$<?php echo number_format( $default_price, 2 ); ?></span>
                                    <?php else : ?>
                                        <span class="pls-price-badge"><?php esc_html_e( 'Included', 'pls-private-label-store' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </label>
                            <?php
                        }
                    }
                    ?>
                  </div>
                </div>
              </div>

              <!-- Additional Product Options (for custom attributes) -->
              <div class="pls-modal__section" style="margin-top: 24px;">
                <div class="pls-section-heading">
                  <p class="pls-label"><?php esc_html_e( 'ADDITIONAL OPTIONS', 'pls-private-label-store' ); ?></p>
                  <h3><?php esc_html_e( 'Other Product Options', 'pls-private-label-store' ); ?></h3>
                  <p class="pls-subtle"><?php esc_html_e( 'Add custom product options like Custom Printed Bottles, External Box Packaging, etc.', 'pls-private-label-store' ); ?></p>
                </div>
                <div class="pls-field-stack">
                  <button type="button" class="button" id="pls-open-attribute-manage"><?php esc_html_e( 'Manage product options & values', 'pls-private-label-store' ); ?></button>
                </div>
                <div id="pls-attribute-rows" class="pls-attribute-rows"></div>
                <button type="button" class="button" id="pls-add-attribute-row"><?php esc_html_e( 'Add product option', 'pls-private-label-store' ); ?></button>
                <div id="pls-attribute-template" class="hidden">
                  <div class="pls-attribute-row">
                    <div class="pls-attribute-row__grid">
                      <div class="pls-attribute-card">
                        <p class="pls-micro"><?php esc_html_e( 'Product Option', 'pls-private-label-store' ); ?></p>
                        <select class="pls-attr-select" name="">
                          <option value=""><?php esc_html_e( 'Select option', 'pls-private-label-store' ); ?></option>
                          <?php 
                          // Filter out Pack Tier (primary), Package Type, Package Color, Package Cap, and ingredient attributes
                          $primary_attr_id = $primary_attr ? $primary_attr->id : 0;
                          $excluded_keys = array( 'pack-tier', 'package-type', 'package-color', 'package-colour', 'package-cap' );
                          foreach ( $attr_payload as $attr ) : 
                            // Skip primary attribute (Pack Tier) and ingredient attributes
                            if ( isset( $attr['is_primary'] ) && $attr['is_primary'] ) continue;
                            if ( isset( $attr['option_type'] ) && $attr['option_type'] === 'ingredient' ) continue;
                            if ( isset( $attr['id'] ) && (int) $attr['id'] === $primary_attr_id ) continue;
                            if ( isset( $attr['attr_key'] ) && in_array( $attr['attr_key'], $excluded_keys, true ) ) continue;
                          ?>
                              <option value="<?php echo esc_attr( $attr['id'] ); ?>"><?php echo esc_html( $attr['label'] ); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <span class="pls-field-hint"><?php esc_html_e( 'Pick an additional product option.', 'pls-private-label-store' ); ?></span>
                      </div>
                      <div class="pls-attribute-card">
                        <div class="pls-attr-value-stack">
                          <p class="pls-micro"><?php esc_html_e( 'Values & price impact', 'pls-private-label-store' ); ?></p>
                          <label class="pls-field-stack">
                            <span class="screen-reader-text"><?php esc_html_e( 'Select values', 'pls-private-label-store' ); ?></span>
                            <select class="pls-attr-value-multi" multiple data-placeholder="<?php esc_attr_e( 'Select values', 'pls-private-label-store' ); ?>"></select>
                          </label>
                          <div class="pls-attribute-value-details"></div>
                          <div class="pls-attribute-custom-values"></div>
                          <button type="button" class="button button-small pls-attribute-value-add-custom"><?php esc_html_e( 'Add custom value', 'pls-private-label-store' ); ?></button>
                        </div>
                      </div>
                    </div>
                    <button type="button" class="button-link-delete pls-attribute-remove"><?php esc_html_e( 'Remove', 'pls-private-label-store' ); ?></button>
                  </div>
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
                  <label class="pls-toggle-card pls-toggle-card--switch">
                    <input type="checkbox" name="label_enabled" id="pls-label-enabled" value="1" checked />
                    <div>
                      <strong><?php esc_html_e( 'Offer label application for this product', 'pls-private-label-store' ); ?></strong>
                      <p class="pls-subtle"><?php esc_html_e( 'Turn this off when labels are not available so the option stays hidden.', 'pls-private-label-store' ); ?></p>
                    </div>
                  </label>
                  <div class="pls-label-price">
                    <label><?php esc_html_e( 'Price per unit for application', 'pls-private-label-store' ); ?>
                      <input type="number" step="0.01" class="pls-price-input" name="label_price_per_unit" id="pls-label-price" placeholder="0.00" />
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

        <div class="pls-modal__preview-panel" id="pls-preview-panel" style="display:none;">
          <div class="pls-preview-loading"><?php esc_html_e( 'Generating preview...', 'pls-private-label-store' ); ?></div>
          <iframe id="pls-preview-iframe" style="width:100%; height:600px; border:none; background:#fff;"></iframe>
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

<div class="pls-modal" id="pls-ingredient-create-modal">
  <div class="pls-modal__dialog">
    <div class="pls-modal__head">
      <div>
        <h2><?php esc_html_e( 'Create ingredient', 'pls-private-label-store' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Add a new ingredient with an optional icon and description.', 'pls-private-label-store' ); ?></p>
      </div>
      <button type="button" class="pls-modal__close" aria-label="<?php esc_attr_e( 'Close', 'pls-private-label-store' ); ?>">×</button>
    </div>
    <form class="pls-modern-form" id="pls-create-ingredient-form">
      <div class="pls-modal__section pls-form-grid">
        <div class="pls-field-stack">
          <label><?php esc_html_e( 'Ingredient name', 'pls-private-label-store' ); ?>
            <input type="text" id="pls-new-ingredient-name" required placeholder="<?php esc_attr_e( 'e.g., Vitamin C', 'pls-private-label-store' ); ?>" />
          </label>
          <label><?php esc_html_e( 'Short description (optional)', 'pls-private-label-store' ); ?>
            <input type="text" id="pls-new-ingredient-short" placeholder="<?php esc_attr_e( 'Antioxidant brightener', 'pls-private-label-store' ); ?>" />
          </label>
        </div>
        <div class="pls-field-stack">
          <span class="pls-micro"><?php esc_html_e( 'Icon (optional)', 'pls-private-label-store' ); ?></span>
          <div class="pls-icon-picker" data-target="pls-new-ingredient-icon">
            <div class="pls-icon-preview" id="pls-new-ingredient-icon-preview"></div>
            <input type="hidden" id="pls-new-ingredient-icon" />
            <div class="pls-chip-row">
              <button type="button" class="button pls-icon-pick"><?php esc_html_e( 'Upload/Select icon', 'pls-private-label-store' ); ?></button>
              <button type="button" class="button-link-delete pls-icon-clear"><?php esc_html_e( 'Remove', 'pls-private-label-store' ); ?></button>
            </div>
          </div>
        </div>
      </div>
      <div class="pls-modal__footer">
        <button type="button" class="button" id="pls-cancel-ingredient-create"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
        <button type="submit" class="button button-primary" id="pls-save-ingredient-create"><?php esc_html_e( 'Save ingredient', 'pls-private-label-store' ); ?></button>
      </div>
    </form>
  </div>
</div>
<div class="pls-modal" id="pls-attribute-manage-modal">
  <div class="pls-modal__dialog">
    <div class="pls-modal__head">
      <div>
        <h2><?php esc_html_e( 'Manage attributes & values', 'pls-private-label-store' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Create attributes and manage reusable values with their default price impacts.', 'pls-private-label-store' ); ?></p>
      </div>
      <button type="button" class="pls-modal__close" aria-label="<?php esc_attr_e( 'Close', 'pls-private-label-store' ); ?>">×</button>
    </div>
    <div class="pls-modal__section pls-attribute-manage">
      <div class="pls-attribute-manage__grid">
        <div class="pls-attribute-manage__panel">
          <div class="pls-section-heading">
            <p class="pls-label"><?php esc_html_e( 'Attributes', 'pls-private-label-store' ); ?></p>
            <h3><?php esc_html_e( 'Pick or create', 'pls-private-label-store' ); ?></h3>
          </div>
          <div id="pls-manage-attr-list" class="pls-manage-attr-list"></div>
          <form class="pls-field-stack" id="pls-manage-attr-create">
            <label><?php esc_html_e( 'New attribute label', 'pls-private-label-store' ); ?>
              <input type="text" id="pls-manage-attr-label" placeholder="<?php esc_attr_e( 'Packaging Type', 'pls-private-label-store' ); ?>" />
            </label>
            <label class="pls-inline-checkbox"><input type="checkbox" id="pls-manage-attr-variation" checked /> <?php esc_html_e( 'Use for variations', 'pls-private-label-store' ); ?></label>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Create attribute', 'pls-private-label-store' ); ?></button>
          </form>
        </div>
        <div class="pls-attribute-manage__panel">
          <div class="pls-section-heading">
            <p class="pls-label"><?php esc_html_e( 'Values', 'pls-private-label-store' ); ?></p>
            <h3 id="pls-manage-attr-current">&nbsp;</h3>
            <p class="pls-subtle" id="pls-manage-attr-hint"><?php esc_html_e( 'Select an attribute on the left to view its values.', 'pls-private-label-store' ); ?></p>
          </div>
          <div id="pls-manage-value-list" class="pls-manage-value-list"></div>
          <form class="pls-manage-value-form" id="pls-manage-value-create">
            <div class="pls-field-grid">
              <label><?php esc_html_e( 'Value label', 'pls-private-label-store' ); ?>
                <input type="text" id="pls-manage-value-label" placeholder="<?php esc_attr_e( 'Airless Pump', 'pls-private-label-store' ); ?>" />
              </label>
              <label><?php esc_html_e( 'Default price impact', 'pls-private-label-store' ); ?>
                <input type="number" step="0.01" class="pls-price-input" id="pls-manage-value-price" placeholder="0.00" />
              </label>
            </div>
            <div class="pls-chip-row">
              <button type="submit" class="button button-small"><?php esc_html_e( 'Add value', 'pls-private-label-store' ); ?></button>
              <span class="pls-key-limit-message" id="pls-manage-value-status"></span>
            </div>
          </form>
          <div class="pls-chip-row">
            <button type="button" class="button button-primary" id="pls-manage-save-values"><?php esc_html_e( 'Save defaults', 'pls-private-label-store' ); ?></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Custom Product Request Modal -->
<div class="pls-modal" id="pls-custom-request-modal">
  <div class="pls-modal__dialog">
    <div class="pls-modal__head">
      <div>
        <h2><?php esc_html_e( 'Request Custom Product', 'pls-private-label-store' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Submit a custom product inquiry. We will contact you to discuss your requirements.', 'pls-private-label-store' ); ?></p>
      </div>
      <button type="button" class="pls-modal__close" aria-label="<?php esc_attr_e( 'Close', 'pls-private-label-store' ); ?>">×</button>
    </div>
    <form class="pls-modern-form" id="pls-custom-request-form">
      <div class="pls-modal__section">
        <label><?php esc_html_e( 'Product Category', 'pls-private-label-store' ); ?>
          <select name="product_category" id="pls-custom-product-category" required>
            <option value=""><?php esc_html_e( 'Select category', 'pls-private-label-store' ); ?></option>
            <?php foreach ( $categories as $category ) : ?>
              <option value="<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></option>
            <?php endforeach; ?>
            <option value="other"><?php esc_html_e( 'Other', 'pls-private-label-store' ); ?></option>
          </select>
        </label>
        
        <label><?php esc_html_e( 'Your Message', 'pls-private-label-store' ); ?>
          <textarea name="message" id="pls-custom-message" rows="6" required 
                    placeholder="<?php esc_attr_e( 'Tell us about your custom product needs, quantities, timeline, special requirements, etc.', 'pls-private-label-store' ); ?>"></textarea>
        </label>
        
        <label><?php esc_html_e( 'Your Name', 'pls-private-label-store' ); ?>
          <input type="text" name="contact_name" id="pls-custom-contact-name" required 
                 value="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>" />
        </label>
        
        <label><?php esc_html_e( 'Your Email', 'pls-private-label-store' ); ?>
          <input type="email" name="contact_email" id="pls-custom-contact-email" required 
                 value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" />
        </label>
      </div>
      
      <div class="pls-modal__footer">
        <button type="button" class="button" id="pls-cancel-custom-request"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
        <button type="submit" class="button button-primary"><?php esc_html_e( 'Send Request', 'pls-private-label-store' ); ?></button>
      </div>
    </form>
  </div>
</div>
