# Sync & Idempotency

All sync operations should support:
- dry-run preview ("what will change")
- idempotency (no duplicates)
- diff-based updates
- backreferences into PLS tables (`wc_product_id`, `wc_variation_id`)

## Sync State Detection (v1.1.0+)

Products have 4 sync states detected by `PLS_Admin_Ajax::detect_product_sync_state()`:

1. **synced_active** - Product matches WooCommerce, both are published/active
2. **synced_inactive** - Product matches WooCommerce, both are draft/inactive
3. **update_available** - Product exists in WooCommerce but data differs
4. **not_synced** - No WooCommerce product exists

**Comparison Checks:**
- Product name, slug, categories
- Product status (publish vs draft)
- Pack tier count and prices
- Variation count matches enabled pack tier count

## Sync Methods

**Product Sync:**
- `PLS_WC_Sync::sync_base_product_to_wc()` - Sync single product
- `PLS_Admin_Ajax::sync_product()` - AJAX endpoint
- `PLS_Admin_Ajax::sync_all_products()` - Bulk sync

**Bundle Sync (v1.1.0+):**
- `PLS_WC_Sync::sync_bundle_to_wc()` - Sync bundle as Grouped Product
- `PLS_Admin_Ajax::sync_bundle()` - AJAX endpoint
- `PLS_WC_Sync::sync_bundles_stub()` - Bulk sync all bundles

**Attribute Sync:**
- `PLS_WC_Sync::sync_attributes_stub()` - Sync attributes to WooCommerce
