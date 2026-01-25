<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get bundles
$bundles = PLS_Repo_Bundle::all();
?>
<div class="wrap pls-wrap pls-page-bundles">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Bundles', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Bundles & Deals', 'pls-private-label-store' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Create product bundles and special offers that combine multiple products or pack tiers. Bundles automatically appear in cart when customers qualify.', 'pls-private-label-store' ); ?>
                <span class="pls-help-icon" title="<?php esc_attr_e( 'Bundle types: Mini Line (2 SKUs), Starter Line (3 SKUs), Growth Line (4 SKUs), Premium Line (6 SKUs). Customers qualify when cart matches bundle requirements.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px;">ⓘ</span>
            </p>
        </div>
        <div>
            <button type="button" class="button button-primary button-hero" id="pls-create-bundle">
                <?php esc_html_e( 'Create Bundle', 'pls-private-label-store' ); ?>
            </button>
        </div>
    </div>

    <?php if ( empty( $bundles ) ) : ?>
        <div class="pls-card" style="text-align: center; padding: 48px 24px;">
            <div style="font-size: 48px; color: var(--pls-gray-300); margin-bottom: 16px;">📦</div>
            <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 600; color: var(--pls-gray-900);"><?php esc_html_e( 'No bundles yet', 'pls-private-label-store' ); ?></h2>
            <p style="margin: 0 0 24px; color: var(--pls-gray-500);"><?php esc_html_e( 'Create your first bundle to offer special deals and product combinations.', 'pls-private-label-store' ); ?></p>
            <button type="button" class="button button-primary" id="pls-create-bundle-empty">
                <?php esc_html_e( 'Create Bundle', 'pls-private-label-store' ); ?>
            </button>
        </div>
    <?php else : ?>
        <div class="pls-card-grid">
            <?php foreach ( $bundles as $bundle ) : ?>
                <?php
                // Parse bundle rules from JSON
                $bundle_rules = ! empty( $bundle->offer_rules_json ) ? json_decode( $bundle->offer_rules_json, true ) : array();
                $bundle_type = isset( $bundle_rules['bundle_type'] ) ? $bundle_rules['bundle_type'] : '';
                $sku_count = isset( $bundle_rules['sku_count'] ) ? $bundle_rules['sku_count'] : 0;
                $units_per_sku = isset( $bundle_rules['units_per_sku'] ) ? $bundle_rules['units_per_sku'] : 0;
                $price_per_unit = isset( $bundle_rules['price_per_unit'] ) ? $bundle_rules['price_per_unit'] : 0;
                $commission_per_unit = isset( $bundle_rules['commission_per_unit'] ) ? $bundle_rules['commission_per_unit'] : 0;
                $total_units = $sku_count * $units_per_sku;
                $total_price = $total_units * $price_per_unit;
                ?>
                <div class="pls-card pls-card--interactive" data-bundle-id="<?php echo esc_attr( $bundle->id ); ?>">
                    <div class="pls-card__heading">
                        <strong style="font-size: 16px; font-weight: 600;"><?php echo esc_html( $bundle->name ); ?></strong>
                        <span class="pls-badge pls-badge--<?php echo esc_attr( $bundle->status === 'live' ? 'success' : 'info' ); ?>">
                            <?php echo esc_html( ucfirst( $bundle->status ) ); ?>
                        </span>
                    </div>
                    <?php if ( $bundle_type ) : ?>
                        <div class="pls-chip" style="margin-top: 8px; margin-bottom: 8px;">
                            <?php echo esc_html( ucfirst( str_replace( '_', ' ', $bundle_type ) ) ); ?>
                        </div>
                    <?php endif; ?>
                    <div style="margin: 8px 0; color: var(--pls-gray-600); font-size: 13px;">
                        <?php if ( $sku_count && $units_per_sku ) : ?>
                            <p style="margin: 4px 0;"><strong><?php echo esc_html( $sku_count ); ?> SKUs</strong> × <strong><?php echo esc_html( $units_per_sku ); ?> units</strong> = <?php echo esc_html( $total_units ); ?> total units</p>
                        <?php endif; ?>
                        <?php if ( $price_per_unit > 0 ) : ?>
                            <p style="margin: 4px 0;">Price: <strong>$<?php echo esc_html( number_format( $price_per_unit, 2 ) ); ?></strong> per unit</p>
                            <?php if ( $total_price > 0 ) : ?>
                                <p style="margin: 4px 0;">Total: <strong>$<?php echo esc_html( number_format( $total_price, 2 ) ); ?></strong></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ( $commission_per_unit > 0 ) : ?>
                            <p style="margin: 4px 0; color: var(--pls-gray-500); font-size: 12px;">Commission: $<?php echo esc_html( number_format( $commission_per_unit, 2 ) ); ?> per unit</p>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--pls-gray-200);">
                        <button type="button" 
                                class="button button-small pls-btn--primary pls-edit-bundle" 
                                data-bundle-id="<?php echo esc_attr( $bundle->id ); ?>"
                                title="<?php esc_attr_e( 'Edit bundle details, pricing, and commission rates', 'pls-private-label-store' ); ?>">
                            <?php esc_html_e( 'Edit', 'pls-private-label-store' ); ?>
                        </button>
                        <?php if ( ! empty( $bundle->wc_product_id ) ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $bundle->wc_product_id . '&action=edit' ) ); ?>" 
                               class="button button-small pls-btn--ghost" 
                               target="_blank"
                               title="<?php esc_attr_e( 'Open bundle product in WooCommerce admin', 'pls-private-label-store' ); ?>">
                                <?php esc_html_e( 'View in WC', 'pls-private-label-store' ); ?>
                            </a>
                        <?php endif; ?>
                        <button type="button" 
                                class="button button-small pls-btn--ghost pls-sync-bundle" 
                                data-bundle-id="<?php echo esc_attr( $bundle->id ); ?>"
                                title="<?php esc_attr_e( 'Sync bundle to WooCommerce. Creates or updates the grouped product.', 'pls-private-label-store' ); ?>">
                            <?php esc_html_e( 'Sync', 'pls-private-label-store' ); ?>
                        </button>
                        <button type="button" 
                                class="button button-small pls-btn--ghost pls-btn--danger pls-delete-bundle" 
                                data-bundle-id="<?php echo esc_attr( $bundle->id ); ?>"
                                title="<?php esc_attr_e( 'Delete bundle permanently. This will also delete the WooCommerce product if synced.', 'pls-private-label-store' ); ?>">
                            <?php esc_html_e( 'Delete', 'pls-private-label-store' ); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Bundle Creation/Edit Modal -->
    <div class="pls-modal" id="pls-bundle-modal">
        <div class="pls-modal__dialog">
            <div class="pls-modal__head">
                <div>
                    <h2 id="pls-bundle-modal-title"><?php esc_html_e( 'Create Bundle', 'pls-private-label-store' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Define bundle type, SKU count, units, and pricing.', 'pls-private-label-store' ); ?></p>
                </div>
                <button type="button" class="pls-modal__close" aria-label="Close">×</button>
            </div>
            <form method="post" class="pls-modern-form" id="pls-bundle-form">
                <?php wp_nonce_field( 'pls_bundle_save', 'pls_bundle_nonce' ); ?>
                <input type="hidden" name="pls_bundle_save" value="1" />
                <input type="hidden" name="bundle_id" id="pls-bundle-id" />
                <div class="notice notice-error pls-form-errors" id="pls-bundle-errors" style="display:none;">
                    <p><?php esc_html_e( 'Please review the highlighted issues before saving.', 'pls-private-label-store' ); ?></p>
                    <ul></ul>
                </div>
                <div class="pls-modal__body">
                    <div class="pls-field-grid">
                        <div class="pls-input-group">
                            <label for="bundle_name"><?php esc_html_e( 'Bundle Name', 'pls-private-label-store' ); ?> <span class="required">*</span></label>
                            <input type="text" id="bundle_name" name="bundle_name" class="pls-input" placeholder="Business Pack 1 - Mini Line" required />
                        </div>
                        <div class="pls-input-group">
                            <label for="bundle_type"><?php esc_html_e( 'Bundle Type', 'pls-private-label-store' ); ?> <span class="required">*</span></label>
                            <select id="bundle_type" name="bundle_type" class="pls-select" required>
                                <option value=""><?php esc_html_e( 'Select type...', 'pls-private-label-store' ); ?></option>
                                <option value="mini_line"><?php esc_html_e( 'Mini Line (2 SKUs)', 'pls-private-label-store' ); ?></option>
                                <option value="starter_line"><?php esc_html_e( 'Starter Line (3 SKUs)', 'pls-private-label-store' ); ?></option>
                                <option value="growth_line"><?php esc_html_e( 'Growth Line (4 SKUs)', 'pls-private-label-store' ); ?></option>
                                <option value="premium_line"><?php esc_html_e( 'Premium Line (6 SKUs)', 'pls-private-label-store' ); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="pls-field-grid">
                        <div class="pls-input-group">
                            <label for="sku_count">
                                <?php esc_html_e( 'SKU Count', 'pls-private-label-store' ); ?> <span class="required">*</span>
                                <span class="pls-help-icon" title="<?php esc_attr_e( 'Number of different products (SKUs) included in this bundle. Example: Mini Line = 2 different products.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px; font-size: 12px;">ⓘ</span>
                            </label>
                            <input type="number" id="sku_count" name="sku_count" class="pls-input" min="2" max="10" placeholder="2" required />
                            <p class="description"><?php esc_html_e( 'Number of different products in bundle. Example: Mini Line = 2 SKUs, Starter Line = 3 SKUs.', 'pls-private-label-store' ); ?></p>
                        </div>
                        <div class="pls-input-group">
                            <label for="units_per_sku">
                                <?php esc_html_e( 'Units per SKU', 'pls-private-label-store' ); ?> <span class="required">*</span>
                                <span class="pls-help-icon" title="<?php esc_attr_e( 'How many units of each product are included. Example: If SKU Count = 2 and Units per SKU = 250, customer gets 2 products × 250 units each = 500 total units.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px; font-size: 12px;">ⓘ</span>
                            </label>
                            <input type="number" id="units_per_sku" name="units_per_sku" class="pls-input" min="1" placeholder="250" required />
                            <p class="description"><?php esc_html_e( 'Units for each product in bundle. Total units = SKU Count × Units per SKU.', 'pls-private-label-store' ); ?></p>
                        </div>
                    </div>
                    <div class="pls-field-grid">
                        <div class="pls-input-group">
                            <label for="price_per_unit"><?php esc_html_e( 'Price per Unit', 'pls-private-label-store' ); ?> <span class="required">*</span></label>
                            <input type="number" id="price_per_unit" name="price_per_unit" class="pls-input" step="0.01" min="0" placeholder="10.90" required />
                        </div>
                        <div class="pls-input-group">
                            <label for="commission_per_unit"><?php esc_html_e( 'Commission per Unit', 'pls-private-label-store' ); ?> <span class="required">*</span></label>
                            <input type="number" id="commission_per_unit" name="commission_per_unit" class="pls-input" step="0.01" min="0" placeholder="0.59" required />
                        </div>
                    </div>
                    <div class="pls-input-group">
                        <label for="bundle_status"><?php esc_html_e( 'Status', 'pls-private-label-store' ); ?></label>
                        <select id="bundle_status" name="bundle_status" class="pls-select">
                            <option value="draft"><?php esc_html_e( 'Draft', 'pls-private-label-store' ); ?></option>
                            <option value="live"><?php esc_html_e( 'Live', 'pls-private-label-store' ); ?></option>
                        </select>
                    </div>
                </div>
                <div class="pls-modal__footer">
                    <button type="button" class="button pls-modal-cancel"><?php esc_html_e( 'Cancel', 'pls-private-label-store' ); ?></button>
                    <button type="submit" class="button button-primary pls-btn--primary"><?php esc_html_e( 'Save Bundle', 'pls-private-label-store' ); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
