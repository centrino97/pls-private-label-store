<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Debug logging
if ( class_exists( 'PLS_Debug' ) && PLS_Debug::is_enabled() ) {
    PLS_Debug::log_page_load( 'products' );
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

// Add preview modal CSS - Fullscreen product editor
wp_add_inline_style( 'pls-admin', '
/* ============================================
   Product Modal - FULLSCREEN Editor
   ============================================ */
/* Override all base modal styles for fullscreen product modal */
.pls-modal#pls-product-modal,
#pls-product-modal.pls-modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    max-height: 100vh !important;
    z-index: 100000 !important;
    background: rgba(0,0,0,0.85) !important;
    padding: 0 !important;
    margin: 0 !important;
    display: flex !important;
    justify-content: stretch !important;
    align-items: stretch !important;
    border-radius: 0 !important;
    overflow: hidden !important;
}

.pls-modal#pls-product-modal .pls-modal__dialog,
#pls-product-modal.pls-modal .pls-modal__dialog,
#pls-product-modal .pls-modal__dialog {
    position: relative !important;
    max-width: 100vw !important;
    width: 100vw !important;
    height: 100vh !important;
    max-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    border-radius: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    box-shadow: none !important;
    background: #fff !important;
    overflow: hidden !important;
    transform: none !important;
}

/* Modal Header */
#pls-product-modal .pls-modal__head {
    flex-shrink: 0;
    padding: 20px 32px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: 64px;
}

#pls-product-modal .pls-modal__head h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
}

/* Mode Toggle (Builder/Preview) - Now inline in header */
#pls-product-modal .pls-modal__head .pls-mode-toggle {
    display: flex;
    gap: 4px;
    align-items: center;
    margin-right: 8px;
}

#pls-product-modal .pls-mode-btn {
    padding: 6px 14px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.15s ease;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

#pls-product-modal .pls-mode-btn:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

#pls-product-modal .pls-mode-btn.is-active {
    background: #007AFF;
    color: #fff;
    border-color: #007AFF;
}

/* Form Container */
#pls-product-modal #pls-product-form {
    flex: 1;
    overflow-y: auto;
    display: flex !important;
    flex-direction: column !important;
    padding: 0 32px 32px 32px;
    min-height: 0;
}

/* Preview Panel */
#pls-product-modal #pls-preview-panel {
    display: none;
    flex-direction: column;
    background: #fff;
    overflow: hidden;
}

#pls-product-modal #pls-preview-panel.is-visible,
#pls-product-modal #pls-preview-panel:not([style*="display: none"]):not(.hidden) {
    display: flex !important;
}

/* ============================================
   Split Screen Mode (Builder + Preview)
   ============================================ */
#pls-product-modal.pls-modal-split .pls-modal__dialog {
    flex-direction: row !important;
}

#pls-product-modal.pls-modal-split #pls-product-form {
    width: 55% !important;
    flex-shrink: 0 !important;
    overflow-y: auto !important;
    border-right: 1px solid #e2e8f0 !important;
}

#pls-product-modal.pls-modal-split #pls-preview-panel {
    width: 45% !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}

/* ============================================
   Preview Only Mode
   ============================================ */
#pls-product-modal:not(.pls-modal-split) #pls-preview-panel:not(.hidden) {
    flex: 1;
    display: flex !important;
    flex-direction: column !important;
}

/* Modal Footer - Sticky at bottom */
#pls-product-modal .pls-modal__footer {
    position: sticky;
    bottom: 0;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    z-index: 10;
    flex-shrink: 0;
    padding: 16px 32px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

/* Preview Panel Styling */
#pls-preview-panel .pls-preview-header {
    flex-shrink: 0;
}

#pls-preview-panel #pls-preview-content {
    flex: 1;
    overflow-y: auto;
    min-height: 0;
}
' );

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
    // Sort by units ascending (Tier 1 → Tier 5)
    usort( $pack_defaults, function( $a, $b ) {
        return $a['units'] <=> $b['units'];
    } );
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

// Always reconcile by reading from WooCommerce directly (backend sync - WooCommerce is source of truth)
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
      <p class="description">
        <?php esc_html_e( 'Create and manage every SKU inside PLS. WooCommerce only receives the final variable product.', 'pls-private-label-store' ); ?>
      </p>
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
            
            // Get sync state
            $sync_state = isset( $product['sync_state'] ) ? $product['sync_state'] : 'not_synced';
            
            // Determine sync state badge class and label
            $sync_badge_class = 'pls-badge--not-synced';
            $sync_badge_label = __( 'Not Synced', 'pls-private-label-store' );
            
            // v2.7.1: Simplified sync states - no 'update_available', sync is automatic
            switch ( $sync_state ) {
                case 'synced_active':
                    $sync_badge_class = 'pls-badge--success';
                    $sync_badge_label = __( 'Live', 'pls-private-label-store' );
                    break;
                case 'synced_inactive':
                    $sync_badge_class = 'pls-badge--info';
                    $sync_badge_label = __( 'Inactive', 'pls-private-label-store' );
                    break;
                case 'not_synced':
                default:
                    $sync_badge_class = 'pls-badge--warning';
                    $sync_badge_label = __( 'Not Synced', 'pls-private-label-store' );
                    break;
            }
            ?>
            <div class="pls-card pls-card--interactive" data-product-id="<?php echo esc_attr( $product['id'] ); ?>">
              <div class="pls-card__heading">
                <strong style="font-size: 16px; font-weight: 600;"><?php echo esc_html( $product['name'] ); ?></strong>
                <div style="display: flex; gap: 8px; align-items: center;">
                  <span class="pls-badge pls-badge--<?php echo esc_attr( $product['status'] === 'live' ? 'success' : 'info' ); ?>" 
                        title="<?php echo esc_attr( $product['status'] === 'live' ? __( 'Product is published and visible in shop', 'pls-private-label-store' ) : __( 'Product is draft and hidden from shop', 'pls-private-label-store' ) ); ?>">
                      <?php echo esc_html( $product['status'] === 'live' ? __( 'Live', 'pls-private-label-store' ) : __( 'Draft', 'pls-private-label-store' ) ); ?>
                  </span>
                  <?php if ( 'not_synced' === $sync_state ) : ?>
                    <span class="pls-badge pls-badge--warning" 
                          title="<?php esc_attr_e( 'Product not yet synced to WooCommerce. Click Activate to sync.', 'pls-private-label-store' ); ?>">
                        <?php esc_html_e( 'Not Synced', 'pls-private-label-store' ); ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
              <?php if ( $cat_labels ) : ?>
                  <div class="pls-chip" style="margin-top: 8px; margin-bottom: 8px;"><?php echo esc_html( implode( ', ', $cat_labels ) ); ?></div>
              <?php endif; ?>
              <?php if ( ! empty( $product['short_description'] ) ) : ?>
                  <p class="description" style="margin: 8px 0; color: var(--pls-gray-600);"><?php echo esc_html( $product['short_description'] ); ?></p>
              <?php endif; ?>
              <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--pls-gray-200);">
                <button class="button button-small pls-btn--primary pls-edit-product" 
                        data-product-id="<?php echo esc_attr( $product['id'] ); ?>"
                        title="<?php esc_attr_e( 'Edit product details, pack tiers, options, and descriptions', 'pls-private-label-store' ); ?>">
                    <?php esc_html_e( 'Edit', 'pls-private-label-store' ); ?>
                </button>
                <?php if ( ! empty( $product['wc_product_id'] ) ) : ?>
                  <button class="button button-small pls-btn--ghost pls-preview-product-btn" 
                          data-product-id="<?php echo esc_attr( $product['id'] ); ?>"
                          data-wc-id="<?php echo esc_attr( $product['wc_product_id'] ); ?>"
                          data-product-name="<?php echo esc_attr( $product['name'] ); ?>"
                          title="<?php esc_attr_e( 'Preview how this product appears on the frontend', 'pls-private-label-store' ); ?>">
                      <?php esc_html_e( 'Preview', 'pls-private-label-store' ); ?>
                  </button>
                <?php else : ?>
                  <button class="button button-small pls-btn--ghost" 
                          disabled 
                          title="<?php esc_attr_e( 'Sync product first to preview. Click Activate to sync.', 'pls-private-label-store' ); ?>">
                      <?php esc_html_e( 'Preview', 'pls-private-label-store' ); ?>
                  </button>
                <?php endif; ?>
                
                <?php
                // v2.7.1: Simplified buttons - only Activate/Deactivate, sync is automatic
                if ( 'not_synced' === $sync_state ) :
                    // Not synced: Show Activate button (will sync and publish)
                    ?>
                    <button class="button button-small pls-btn--primary pls-activate-product" 
                            data-product-id="<?php echo esc_attr( $product['id'] ); ?>"
                            title="<?php esc_attr_e( 'Sync to WooCommerce and publish. Creates variable product with pack tier variations.', 'pls-private-label-store' ); ?>">
                        <?php esc_html_e( 'Activate', 'pls-private-label-store' ); ?>
                    </button>
                <?php elseif ( 'synced_active' === $sync_state ) : ?>
                    <!-- Synced & Active: Show Deactivate button -->
                    <button class="button button-small pls-btn--ghost pls-deactivate-product" 
                            data-product-id="<?php echo esc_attr( $product['id'] ); ?>"
                            title="<?php esc_attr_e( 'Set WooCommerce product to draft. Product stays synced but hidden from shop.', 'pls-private-label-store' ); ?>">
                        <?php esc_html_e( 'Deactivate', 'pls-private-label-store' ); ?>
                    </button>
                <?php elseif ( 'synced_inactive' === $sync_state ) : ?>
                    <!-- Synced & Inactive: Show Activate button -->
                    <button class="button button-small pls-btn--primary pls-activate-product" 
                            data-product-id="<?php echo esc_attr( $product['id'] ); ?>"
                            title="<?php esc_attr_e( 'Publish WooCommerce product. Product is already synced.', 'pls-private-label-store' ); ?>">
                        <?php esc_html_e( 'Activate', 'pls-private-label-store' ); ?>
                    </button>
                <?php endif; ?>
                
                <button class="button button-small pls-btn--ghost pls-btn--danger pls-delete-product" 
                        data-product-id="<?php echo esc_attr( $product['id'] ); ?>"
                        title="<?php esc_attr_e( 'Delete product permanently. This will also delete the WooCommerce product if synced.', 'pls-private-label-store' ); ?>">
                    <?php esc_html_e( 'Delete', 'pls-private-label-store' ); ?>
                </button>
              </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="pls-modal" id="pls-product-modal">
    <div class="pls-modal__dialog">
      <div class="pls-modal__head">
        <div style="flex: 1;">
          <h2 id="pls-modal-title"><?php esc_html_e( 'Create product', 'pls-private-label-store' ); ?></h2>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
          <div class="pls-mode-toggle" style="display: flex; gap: 4px; margin-right: 8px;">
            <button type="button" class="pls-mode-btn is-active" data-mode="builder"><?php esc_html_e( 'Builder', 'pls-private-label-store' ); ?></button>
            <button type="button" class="pls-mode-btn" data-mode="preview"><?php esc_html_e( 'Preview', 'pls-private-label-store' ); ?></button>
          </div>
          <button type="button" class="button pls-help-button pls-modal-help-button" id="pls-product-modal-help" title="<?php esc_attr_e( 'View Help Guide', 'pls-private-label-store' ); ?>" style="width: 32px; height: 32px; padding: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; background: var(--pls-accent-light, #E5F2FF); color: var(--pls-accent, #007AFF); border: 1px solid var(--pls-accent, #007AFF); cursor: pointer;">?</button>
          <button type="button" class="pls-modal__close" aria-label="Close">×</button>
        </div>
      </div>
      <form method="post" class="pls-modern-form" id="pls-product-form">
        <?php wp_nonce_field( 'pls_product_modal_save' ); ?>
        <input type="hidden" name="pls_product_modal_save" value="1" />
        <input type="hidden" name="id" id="pls-product-id" />
        <input type="hidden" name="gallery_ids" id="pls-gallery-ids" />
        <!-- Success Message -->
        <div class="notice notice-success pls-form-success" id="pls-product-success" style="display:none; position: sticky; top: 0; z-index: 1000; margin: 0 0 16px 0;">
          <p style="margin: 0; font-weight: 500;">
            <span style="display: inline-block; margin-right: 8px;">✓</span>
            <span id="pls-success-message"><?php esc_html_e( 'Product saved successfully!', 'pls-private-label-store' ); ?></span>
          </p>
        </div>
        
        <!-- Error Messages - Sticky at Top -->
        <div class="notice notice-error pls-form-errors" id="pls-product-errors" style="display:none; position: sticky; top: 0; z-index: 1000; margin: 0 0 16px 0; background: #d63638; color: #fff; border-left-color: #d63638;">
          <p style="margin: 0 0 8px 0; font-weight: 600;">
            <span style="display: inline-block; margin-right: 8px;">⚠️</span>
            <span id="pls-error-count">0</span> <?php esc_html_e( 'error(s) found. Please fix before saving.', 'pls-private-label-store' ); ?>
          </p>
          <ul style="margin: 0; padding-left: 20px;"></ul>
        </div>

        <div class="pls-stepper">
          <div class="pls-stepper__header">
            <div class="pls-stepper__progress">
              <span class="pls-stepper__progress-text" id="pls-stepper-progress">Step 1 of 5</span>
              <span class="pls-stepper__progress-bar">
                <span class="pls-stepper__progress-fill" id="pls-stepper-progress-fill" style="width: 20%;"></span>
              </span>
              <span class="pls-stepper__progress-percent" id="pls-stepper-percent">20% Complete</span>
            </div>
          </div>
          <div class="pls-stepper__nav" id="pls-stepper-nav">
            <button type="button" class="pls-stepper__item is-active" data-step="general">
              <span class="pls-stepper__item-number">1</span>
              <span class="pls-stepper__item-label"><?php esc_html_e( 'General', 'pls-private-label-store' ); ?></span>
              <span class="pls-stepper__item-check" style="display: none;">✓</span>
            </button>
            <button type="button" class="pls-stepper__item" data-step="data">
              <span class="pls-stepper__item-number">2</span>
              <span class="pls-stepper__item-label"><?php esc_html_e( 'Data', 'pls-private-label-store' ); ?></span>
              <span class="pls-stepper__item-check" style="display: none;">✓</span>
            </button>
            <button type="button" class="pls-stepper__item" data-step="ingredients">
              <span class="pls-stepper__item-number">3</span>
              <span class="pls-stepper__item-label"><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></span>
              <span class="pls-stepper__item-check" style="display: none;">✓</span>
            </button>
            <button type="button" class="pls-stepper__item" data-step="packs">
              <span class="pls-stepper__item-number">4</span>
              <span class="pls-primary-badge" style="background: #2271b1; color: #fff; padding: 2px 6px; border-radius: 2px; font-size: 9px; margin-right: 6px;">PRIMARY</span>
              <span class="pls-stepper__item-label"><?php esc_html_e( 'Pack tiers', 'pls-private-label-store' ); ?></span>
              <span class="pls-stepper__item-check" style="display: none;">✓</span>
            </button>
            <button type="button" class="pls-stepper__item" data-step="attributes">
              <span class="pls-stepper__item-number">5</span>
              <span class="pls-stepper__item-label"><?php esc_html_e( 'Product options', 'pls-private-label-store' ); ?></span>
              <span class="pls-stepper__item-check" style="display: none;">✓</span>
            </button>
          </div>

          <div class="pls-stepper__panels">
            <div class="pls-stepper__panel is-active" data-step="general">
              <div class="pls-modal__grid">
                <div class="pls-modal__section">
                  <h3><?php esc_html_e( 'General', 'pls-private-label-store' ); ?></h3>
                  <label><?php esc_html_e( 'Name', 'pls-private-label-store' ); ?> <span class="pls-required-indicator" style="color: #d63638;">*</span>
                    <input type="text" name="name" id="pls-name" required placeholder="Collagen Serum" />
                  </label>
                  <p class="pls-subtle"><?php esc_html_e( 'Products are automatically synced to WooCommerce on every save. Drafts are hidden from the shop. Activate when ready to publish.', 'pls-private-label-store' ); ?></p>
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

              <!-- Stock & Inventory Management -->
              <div class="pls-modal__grid pls-bento-grid">
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <p class="pls-label"><?php esc_html_e( 'Inventory', 'pls-private-label-store' ); ?></p>
                    <h3><?php esc_html_e( 'Stock Management', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle"><?php esc_html_e( 'Control product availability and stock tracking.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <div class="pls-field-stack">
                    <label class="pls-toggle-field">
                      <input type="checkbox" name="manage_stock" id="pls-manage-stock" value="1" />
                      <span><?php esc_html_e( 'Track stock quantity', 'pls-private-label-store' ); ?></span>
                    </label>
                    <div class="pls-stock-fields" id="pls-stock-fields" style="display: none;">
                      <label><?php esc_html_e( 'Stock quantity', 'pls-private-label-store' ); ?>
                        <input type="number" name="stock_quantity" id="pls-stock-quantity" min="0" step="1" placeholder="0" />
                      </label>
                      <label><?php esc_html_e( 'Low stock threshold', 'pls-private-label-store' ); ?>
                        <input type="number" name="low_stock_threshold" id="pls-low-stock-threshold" min="0" step="1" placeholder="5" />
                        <span class="pls-field-hint"><?php esc_html_e( 'Alert when stock falls below this level.', 'pls-private-label-store' ); ?></span>
                      </label>
                    </div>
                    <label><?php esc_html_e( 'Stock status', 'pls-private-label-store' ); ?>
                      <select name="stock_status" id="pls-stock-status">
                        <option value="instock"><?php esc_html_e( 'In stock', 'pls-private-label-store' ); ?></option>
                        <option value="outofstock"><?php esc_html_e( 'Out of stock', 'pls-private-label-store' ); ?></option>
                        <option value="onbackorder"><?php esc_html_e( 'On backorder', 'pls-private-label-store' ); ?></option>
                      </select>
                    </label>
                    <label class="pls-toggle-field">
                      <input type="checkbox" name="backorders_allowed" id="pls-backorders-allowed" value="1" />
                      <span><?php esc_html_e( 'Allow backorders', 'pls-private-label-store' ); ?></span>
                    </label>
                    <span class="pls-field-hint"><?php esc_html_e( 'Allow customers to order when product is out of stock.', 'pls-private-label-store' ); ?></span>
                  </div>
                </div>
                <div class="pls-modal__section">
                  <div class="pls-section-heading">
                    <p class="pls-label"><?php esc_html_e( 'Costs', 'pls-private-label-store' ); ?></p>
                    <h3><?php esc_html_e( 'Shipping & Packaging', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle"><?php esc_html_e( 'Manual cost inputs for accurate profit calculations.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <label><?php esc_html_e( 'Shipping cost (per unit)', 'pls-private-label-store' ); ?>
                    <div class="pls-input-with-prefix">
                      <span class="pls-input-prefix">$</span>
                      <input type="number" name="shipping_cost" id="pls-shipping-cost" min="0" step="0.01" placeholder="0.00" />
                    </div>
                    <span class="pls-field-hint"><?php esc_html_e( 'Average shipping cost per unit for Australian delivery.', 'pls-private-label-store' ); ?></span>
                  </label>
                  <label><?php esc_html_e( 'Packaging cost (per unit)', 'pls-private-label-store' ); ?>
                    <div class="pls-input-with-prefix">
                      <span class="pls-input-prefix">$</span>
                      <input type="number" name="packaging_cost" id="pls-packaging-cost" min="0" step="0.01" placeholder="0.00" />
                    </div>
                    <span class="pls-field-hint"><?php esc_html_e( 'Cost of packaging materials per unit.', 'pls-private-label-store' ); ?></span>
                  </label>
                </div>
              </div>
            </div>

            <div class="pls-stepper__panel" data-step="ingredients">
              <!-- v4.9.99: Redesigned Ingredients UI - Table-like layout for 100+ ingredients -->
              <div class="pls-ingredients-container">
                <div class="pls-ingredients-header">
                  <div class="pls-ingredients-header__left">
                    <h3 style="margin: 0;"><?php esc_html_e( 'Ingredients', 'pls-private-label-store' ); ?></h3>
                    <p class="pls-subtle" style="margin: 4px 0 0 0;"><?php esc_html_e( 'Select ingredients from the list. Use search to find specific items.', 'pls-private-label-store' ); ?></p>
                  </div>
                  <div class="pls-ingredients-header__right">
                    <span class="pls-badge pls-badge--info" id="pls-ingredient-stats">
                      <span id="pls-selected-count">0</span> <?php esc_html_e( 'selected', 'pls-private-label-store' ); ?>
                    </span>
                    <button type="button" class="button button-small" id="pls-open-ingredient-create">
                      <span class="dashicons dashicons-plus-alt2" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
                      <?php esc_html_e( 'Create', 'pls-private-label-store' ); ?>
                    </button>
                  </div>
                </div>
                
                <!-- Ingredients Tabs -->
                <div class="pls-ingredients-tabs">
                  <button type="button" class="pls-tab is-active" data-tab="all">
                    <?php esc_html_e( 'All Ingredients', 'pls-private-label-store' ); ?>
                  </button>
                  <button type="button" class="pls-tab" data-tab="base">
                    <?php esc_html_e( 'Base Ingredients (INCI)', 'pls-private-label-store' ); ?>
                  </button>
                  <button type="button" class="pls-tab" data-tab="unlockable">
                    <?php esc_html_e( 'Unlockable (T3+)', 'pls-private-label-store' ); ?>
                  </button>
                </div>
                
                <!-- Search Bar -->
                <div class="pls-ingredients-toolbar">
                  <div class="pls-search-box">
                    <span class="dashicons dashicons-search" style="color: #94a3b8;"></span>
                    <input type="search" id="pls-ingredient-search" placeholder="<?php esc_attr_e( 'Search ingredients...', 'pls-private-label-store' ); ?>" />
                    <span class="pls-search-count" id="pls-search-count"></span>
                  </div>
                </div>
                
                <!-- Tab Description -->
                <div class="pls-tab-description" id="pls-tab-description">
                  <p class="pls-subtle" style="margin: 0 0 12px 0; font-size: 13px; color: #64748b;">
                    <span id="pls-tab-description-text"><?php esc_html_e( 'All ingredients are shown. Base ingredients (INCI) are included in all products. Tier 3+ customers can unlock additional key ingredients.', 'pls-private-label-store' ); ?></span>
                  </p>
                  <div id="pls-tab-counts" style="display: flex; gap: 16px; font-size: 12px; color: #64748b; margin-top: 4px;">
                    <span id="pls-tab-count-all">All: <strong>0</strong></span>
                    <span id="pls-tab-count-base">Base: <strong>0</strong></span>
                    <span id="pls-tab-count-unlockable">Unlockable: <strong>0</strong></span>
                  </div>
                </div>
                
                <!-- Ingredients Table -->
                <div class="pls-ingredients-table-wrapper">
                  <table class="pls-ingredients-table" id="pls-ingredients-table">
                    <thead>
                      <tr>
                        <th class="pls-col-select" style="width: 40px;">
                          <input type="checkbox" id="pls-select-all-ingredients" title="<?php esc_attr_e( 'Select/Deselect All Visible', 'pls-private-label-store' ); ?>" />
                        </th>
                        <th class="pls-col-icon" style="width: 40px;"></th>
                        <th class="pls-col-name"><?php esc_html_e( 'Ingredient', 'pls-private-label-store' ); ?></th>
                        <th class="pls-col-key" style="width: 80px;" title="<?php esc_attr_e( 'Mark as Key Ingredient (max 5)', 'pls-private-label-store' ); ?>">
                          <?php esc_html_e( 'Key', 'pls-private-label-store' ); ?>
                          <span class="pls-tier-badge" style="background: #6366f1; color: #fff; padding: 1px 4px; border-radius: 2px; font-size: 8px; margin-left: 4px;">T3+</span>
                        </th>
                        <th class="pls-col-price" style="width: 120px;" title="<?php esc_attr_e( 'Price Impact (Tier 3+ only)', 'pls-private-label-store' ); ?>">
                          <?php esc_html_e( 'Price Impact', 'pls-private-label-store' ); ?>
                        </th>
                      </tr>
                    </thead>
                    <tbody id="pls-ingredient-chips" data-default-icon="<?php echo esc_attr( PLS_Taxonomies::default_icon() ); ?>">
                      <!-- Populated by JavaScript -->
                    </tbody>
                  </table>
                  <div class="pls-table-empty" id="pls-ingredients-empty" style="display: none;">
                    <span class="dashicons dashicons-warning" style="font-size: 32px; color: #cbd5e1;"></span>
                    <p><?php esc_html_e( 'No ingredients found matching your search.', 'pls-private-label-store' ); ?></p>
                  </div>
                </div>
                
                <!-- Pagination -->
                <div class="pls-ingredients-pagination" id="pls-ingredients-pagination">
                  <div class="pls-pagination-info">
                    <span id="pls-pagination-showing"><?php esc_html_e( 'Showing 0-0 of 0', 'pls-private-label-store' ); ?></span>
                  </div>
                  <div class="pls-pagination-controls">
                    <select id="pls-ingredients-per-page" style="padding: 4px 8px;">
                      <option value="25">25</option>
                      <option value="50" selected>50</option>
                      <option value="100">100</option>
                      <option value="all"><?php esc_html_e( 'All', 'pls-private-label-store' ); ?></option>
                    </select>
                    <button type="button" class="button button-small" id="pls-page-prev" disabled>
                      <span class="dashicons dashicons-arrow-left-alt2" style="font-size: 14px; width: 14px; height: 14px;"></span>
                    </button>
                    <span id="pls-page-info" style="padding: 0 8px;">1 / 1</span>
                    <button type="button" class="button button-small" id="pls-page-next" disabled>
                      <span class="dashicons dashicons-arrow-right-alt2" style="font-size: 14px; width: 14px; height: 14px;"></span>
                    </button>
                  </div>
                </div>
                
                <!-- Key Ingredients Summary -->
                <div class="pls-key-ingredients-summary" id="pls-key-ingredients-summary">
                  <div class="pls-key-ingredients-header">
                    <strong id="pls-key-counter"><?php esc_html_e( 'Key ingredients: 0 / 5', 'pls-private-label-store' ); ?></strong>
                    <span class="pls-key-limit-message" id="pls-key-limit-message" aria-live="polite"></span>
                  </div>
                  <p class="pls-subtle"><?php esc_html_e( 'Key ingredients are highlighted on the product page and available as options for Tier 3+ customers.', 'pls-private-label-store' ); ?></p>
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
                        <span class="pls-help-icon" title="<?php esc_attr_e( 'This is the price customers pay per unit. Total price = price per unit × units.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px; display: inline-block; width: 14px; height: 14px; line-height: 14px; text-align: center; border-radius: 50%; background: #E5F2FF; color: #007AFF; font-size: 11px; font-weight: 600;">ⓘ</span>
                        <input type="number" step="0.01" class="pls-price-input pls-tier-price-input" name="pack_tiers[<?php echo esc_attr( $i ); ?>][price]" value="<?php echo esc_attr( $price ); ?>" min="0" data-units="<?php echo esc_attr( $units ); ?>" />
                      </label>
                      <div style="margin-top: 8px; padding: 10px 12px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 8px; border-left: 3px solid #007AFF;">
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;"><?php esc_html_e( 'Price Breakdown', 'pls-private-label-store' ); ?></div>
                        <div style="font-size: 13px; color: #334155; margin-bottom: 2px;">
                          $<span class="pls-tier-price-display"><?php echo number_format( $price, 2 ); ?></span> <?php esc_html_e( 'per unit', 'pls-private-label-store' ); ?> × <span class="pls-tier-units-display"><?php echo esc_html( $units ); ?></span> <?php esc_html_e( 'units', 'pls-private-label-store' ); ?>
                        </div>
                        <div style="font-size: 16px; font-weight: 700; color: #007AFF; margin-top: 4px;">
                          <?php esc_html_e( 'Total:', 'pls-private-label-store' ); ?> $<span class="pls-tier-total"><?php echo number_format( $units * $price, 2 ); ?></span>
                        </div>
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
                        // Find Package Type attribute - try by attr_key first, then by label
                        $package_type_attr = null;
                        foreach ( $attr_payload as $attr ) {
                            $attr_key = isset( $attr['attr_key'] ) ? $attr['attr_key'] : '';
                            $attr_label = isset( $attr['label'] ) ? strtolower( $attr['label'] ) : '';
                            
                            if ( $attr_key === 'package-type' || 
                                 stripos( $attr_label, 'package type' ) !== false ||
                                 stripos( $attr_label, 'container size' ) !== false ) {
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
                        } else {
                            ?>
                            <option value="" disabled><?php esc_html_e( 'No package types available. Please create package type options first.', 'pls-private-label-store' ); ?></option>
                            <?php
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
                    // Find Package Cap attribute - try by attr_key first, then by label
                    $package_cap_attr = null;
                    foreach ( $attr_payload as $attr ) {
                        $attr_key = isset( $attr['attr_key'] ) ? $attr['attr_key'] : '';
                        $attr_label = isset( $attr['label'] ) ? strtolower( $attr['label'] ) : '';
                        
                        if ( $attr_key === 'package-cap' ||
                             stripos( $attr_label, 'package cap' ) !== false ||
                             stripos( $attr_label, 'cap' ) !== false && stripos( $attr_label, 'package' ) !== false ||
                             stripos( $attr_label, 'applicator' ) !== false ) {
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
                    } else {
                        ?>
                        <p class="pls-muted" style="padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px dashed #e2e8f0;">
                            <?php esc_html_e( 'No package caps available. Please create package cap options in Product Options settings first.', 'pls-private-label-store' ); ?>
                        </p>
                        <?php
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
                  <p class="pls-subtle"><?php esc_html_e( 'Select from existing product options like Custom Printed Bottles, External Box Packaging, etc. To create or edit options, use the Product Options menu.', 'pls-private-label-store' ); ?></p>
                </div>
                <div class="pls-field-stack" style="margin-bottom: 16px;">
                  <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-attributes' ) ); ?>" target="_blank" class="button" style="text-decoration: none;">
                    <?php esc_html_e( 'Manage product options & values', 'pls-private-label-store' ); ?>
                    <span class="dashicons dashicons-external" style="font-size: 16px; vertical-align: middle; margin-left: 4px;"></span>
                  </a>
                  <p class="description" style="margin-top: 8px; font-size: 12px; color: #666;">
                    <?php esc_html_e( 'Opens Product Options page where you can create and edit options and their values.', 'pls-private-label-store' ); ?>
                  </p>
                </div>
                <div id="pls-attribute-rows" class="pls-attribute-rows"></div>
                <div id="pls-attribute-template" class="hidden">
                  <div class="pls-attribute-row">
                    <div class="pls-attribute-row__grid">
                      <div class="pls-attribute-card">
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                          <div style="flex: 1;">
                            <p class="pls-micro"><?php esc_html_e( 'Product Option', 'pls-private-label-store' ); ?></p>
                            <select class="pls-attr-select" name="">
                              <option value=""><?php esc_html_e( 'Select option', 'pls-private-label-store' ); ?></option>
                              <?php 
                              // Filter out Pack Tier (primary), Package Type, Package Color, Package Cap, Label Application, and ingredient attributes
                              $primary_attr_id = $primary_attr ? $primary_attr->id : 0;
                              $excluded_keys = array( 'pack-tier', 'package-type', 'package-color', 'package-colour', 'package-cap', 'label-application' );
                              foreach ( $attr_payload as $attr ) : 
                                // Skip primary attribute (Pack Tier) and ingredient attributes
                                if ( isset( $attr['is_primary'] ) && $attr['is_primary'] ) continue;
                                if ( isset( $attr['option_type'] ) && $attr['option_type'] === 'ingredient' ) continue;
                                if ( isset( $attr['id'] ) && (int) $attr['id'] === $primary_attr_id ) continue;
                                if ( isset( $attr['attr_key'] ) && in_array( $attr['attr_key'], $excluded_keys, true ) ) continue;
                                // Also skip Label Application by label name (in case attr_key differs)
                                if ( isset( $attr['label'] ) && stripos( $attr['label'], 'label application' ) !== false ) continue;
                              ?>
                                  <option value="<?php echo esc_attr( $attr['id'] ); ?>"><?php echo esc_html( $attr['label'] ); ?></option>
                              <?php endforeach; ?>
                            </select>
                            <span class="pls-field-hint"><?php esc_html_e( 'Pick an additional product option.', 'pls-private-label-store' ); ?></span>
                          </div>
                          <button type="button" class="button button-small pls-quick-add-option" style="margin-top: 20px; white-space: nowrap;" title="<?php esc_attr_e( 'Quick add new option', 'pls-private-label-store' ); ?>">
                            <?php esc_html_e( '+ Option', 'pls-private-label-store' ); ?>
                          </button>
                        </div>
                      </div>
                      <div class="pls-attribute-card">
                        <div class="pls-attr-value-stack">
                          <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <div style="flex: 1;">
                              <p class="pls-micro"><?php esc_html_e( 'Values & price impact', 'pls-private-label-store' ); ?></p>
                              <label class="pls-field-stack">
                                <span class="screen-reader-text"><?php esc_html_e( 'Select values', 'pls-private-label-store' ); ?></span>
                                <select class="pls-attr-value-multi" multiple data-placeholder="<?php esc_attr_e( 'Select values', 'pls-private-label-store' ); ?>"></select>
                              </label>
                            </div>
                            <button type="button" class="button button-small pls-quick-add-value" style="margin-top: 20px; white-space: nowrap;" title="<?php esc_attr_e( 'Quick add new value', 'pls-private-label-store' ); ?>">
                              <?php esc_html_e( '+ Value', 'pls-private-label-store' ); ?>
                            </button>
                          </div>
                          <div class="pls-attribute-value-details"></div>
                          <p class="pls-field-hint" style="margin-top: 8px; font-size: 11px; color: #666;">
                            <?php esc_html_e( 'Select from existing values or use Quick Add to create new ones.', 'pls-private-label-store' ); ?>
                          </p>
                        </div>
                      </div>
                    </div>
                    <button type="button" class="button-link-delete pls-attribute-remove"><?php esc_html_e( 'Remove', 'pls-private-label-store' ); ?></button>
                  </div>
                </div>
              </div>

              <!-- Label Application Section (moved into Product Options) -->
              <div class="pls-modal__section" style="margin-top: 32px; padding-top: 32px; border-top: 2px solid var(--pls-gray-200);">
                <div class="pls-section-heading">
                  <p class="pls-label"><?php esc_html_e( 'LABEL CUSTOMIZATION', 'pls-private-label-store' ); ?></p>
                  <h3><?php esc_html_e( 'Label Application & Pricing', 'pls-private-label-store' ); ?></h3>
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
                <div class="pls-design-callout" style="margin-top: 16px;">
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
            <button type="button" class="button pls-btn--secondary" id="pls-step-prev">
              <span style="display: inline-block; margin-right: 6px;">←</span>
              <?php esc_html_e( 'Back', 'pls-private-label-store' ); ?>
            </button>
            <button type="button" class="button button-primary" id="pls-step-next">
              <?php esc_html_e( 'Next', 'pls-private-label-store' ); ?>
              <span style="display: inline-block; margin-left: 6px;">→</span>
            </button>
          </div>
          <div class="pls-stepper__actions">
            <button type="button" class="button pls-btn--secondary" id="pls-modal-cancel">
              <span style="display: inline-block; margin-right: 6px;">×</span>
              <?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?>
            </button>
            <button type="submit" class="button button-primary button-hero" id="pls-save-product-btn">
              <span class="pls-save-icon" style="display: inline-block; margin-right: 6px;">✓</span>
              <span class="pls-save-text"><?php esc_html_e( 'Save product', 'pls-private-label-store' ); ?></span>
              <span class="pls-save-spinner" style="display: none; margin-left: 8px;">
                <span class="spinner is-active" style="float: none; margin: 0; visibility: visible;"></span>
              </span>
            </button>
          </div>
        </div>
      </form>
      
      <!-- Preview Panel -->
      <div id="pls-preview-panel" class="pls-modal__preview-panel" style="display: none;">
        <!-- Preview Header -->
        <div class="pls-preview-header" style="padding: 16px 20px; border-bottom: 1px solid #ddd; background: #f9f9f9; flex-shrink: 0;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h3 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 600;"><?php esc_html_e( 'Live Preview', 'pls-private-label-store' ); ?></h3>
              <p class="description" style="margin: 0; font-size: 12px; color: #666;">
                <?php esc_html_e( 'Updates automatically as you edit', 'pls-private-label-store' ); ?>
              </p>
            </div>
            <button type="button" class="button button-small pls-preview-refresh-btn" title="<?php esc_attr_e( 'Refresh Preview', 'pls-private-label-store' ); ?>">
              <span class="dashicons dashicons-update" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
            </button>
          </div>
        </div>
        
        <!-- Preview Content -->
        <div id="pls-preview-content" style="flex: 1; overflow-y: auto; padding: 20px; background: #fff;">
          <div id="pls-preview-loading" style="display: none; text-align: center; padding: 60px 20px;">
            <div class="pls-spinner" style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #2271b1; border-radius: 50%; animation: pls-spin 1s linear infinite; margin-bottom: 16px;"></div>
            <p class="description" style="margin: 0; color: #666;"><?php esc_html_e( 'Generating preview...', 'pls-private-label-store' ); ?></p>
          </div>
          <div id="pls-preview-placeholder" style="text-align: center; padding: 60px 20px;">
            <div style="margin-bottom: 16px;">
              <span class="dashicons dashicons-visibility" style="font-size: 48px; width: 48px; height: 48px; color: #ddd;"></span>
            </div>
            <p style="margin: 0 0 8px 0; font-size: 14px; color: #666; font-weight: 500;">
              <?php esc_html_e( 'Preview will appear here', 'pls-private-label-store' ); ?>
            </p>
            <p class="description" style="margin: 0; color: #999; font-size: 13px;">
              <?php esc_html_e( 'Product must be saved and synced to WooCommerce to see preview.', 'pls-private-label-store' ); ?>
            </p>
          </div>
        </div>
      </div>
      
      <style>
        @keyframes pls-spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      </style>
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

  <!-- Quick Add Option Modal -->
  <div id="pls-quick-add-option-modal" class="pls-modal" style="display: none;">
    <div class="pls-modal__backdrop"></div>
    <div class="pls-modal__dialog" style="max-width: 500px;">
      <div class="pls-modal__header">
        <h2><?php esc_html_e( 'Quick Add Option', 'pls-private-label-store' ); ?></h2>
        <button type="button" class="pls-modal__close" aria-label="<?php esc_attr_e( 'Close', 'pls-private-label-store' ); ?>">×</button>
      </div>
      <div class="pls-modal__body">
        <form id="pls-quick-add-option-form">
          <div class="pls-field-row">
            <label><?php esc_html_e( 'Option Name', 'pls-private-label-store' ); ?> *</label>
            <input type="text" id="pls-quick-option-name" class="regular-text" required placeholder="<?php esc_attr_e( 'e.g., Custom Bottle Design', 'pls-private-label-store' ); ?>" />
          </div>
          <div class="pls-field-row">
            <label>
              <input type="checkbox" id="pls-quick-option-variation" />
              <?php esc_html_e( 'Use for variations', 'pls-private-label-store' ); ?>
            </label>
          </div>
          <div class="pls-modal__footer">
            <button type="button" class="button pls-modal-cancel"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Create Option', 'pls-private-label-store' ); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Quick Add Value Modal -->
  <div id="pls-quick-add-value-modal" class="pls-modal" style="display: none;">
    <div class="pls-modal__backdrop"></div>
    <div class="pls-modal__dialog" style="max-width: 500px;">
      <div class="pls-modal__header">
        <h2><?php esc_html_e( 'Quick Add Value', 'pls-private-label-store' ); ?></h2>
        <button type="button" class="pls-modal__close" aria-label="<?php esc_attr_e( 'Close', 'pls-private-label-store' ); ?>">×</button>
      </div>
      <div class="pls-modal__body">
        <form id="pls-quick-add-value-form">
          <input type="hidden" id="pls-quick-value-attr-id" />
          <div class="pls-field-row">
            <label><?php esc_html_e( 'Value Name', 'pls-private-label-store' ); ?> *</label>
            <input type="text" id="pls-quick-value-name" class="regular-text" required placeholder="<?php esc_attr_e( 'e.g., Premium Gold', 'pls-private-label-store' ); ?>" />
          </div>
          <div class="pls-field-row">
            <label><?php esc_html_e( 'Price Impact', 'pls-private-label-store' ); ?></label>
            <input type="number" step="0.01" id="pls-quick-value-price" class="regular-text" value="0.00" placeholder="0.00" />
            <p class="description"><?php esc_html_e( 'Additional cost when this value is selected.', 'pls-private-label-store' ); ?></p>
          </div>
          <div class="pls-modal__footer">
            <button type="button" class="button pls-modal-cancel"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Create Value', 'pls-private-label-store' ); ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

