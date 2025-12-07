# Sync & Idempotency

All sync operations should support:
- dry-run preview ("what will change")
- idempotency (no duplicates)
- diff-based updates
- backreferences into PLS tables (`wc_product_id`, `wc_variation_id`)

Current sync methods are stubs:
- `PLS_WC_Sync::sync_all_stub()`
- `PLS_WC_Sync::sync_attributes_stub()`
- `PLS_WC_Sync::sync_bundles_stub()`
