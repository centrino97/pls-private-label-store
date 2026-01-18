# Data Model (Phase 1)

Tables are created on activation using `dbDelta()` and prefixed with `wp_pls_...`.

- `pls_base_product`
- `pls_pack_tier`
- `pls_bundle`
- `pls_bundle_item`
- `pls_attribute`
- `pls_attribute_value`
- `pls_swatch`

See: `includes/core/class-pls-activator.php`

## pls_attribute Table (v0.8.3+)

The `pls_attribute` table supports hierarchical product options with the following columns:

- `id`: Primary key
- `parent_attribute_id`: NULL for root options, attribute ID for children (future use)
- `wc_attribute_id`: Linked WooCommerce attribute ID
- `attr_key`: Unique key identifier
- `label`: Display label
- `option_type`: 'pack-tier' | 'product-option' | 'ingredient'
- `is_primary`: 1 for Pack Tier (only one can be primary), 0 for others
- `is_variation`: Whether this attribute is used for variations
- `sort_order`: Display order

The Pack Tier attribute is marked with `is_primary = 1` and `option_type = 'pack-tier'`. All other product options use `option_type = 'product-option'`. Ingredients are synced with `option_type = 'ingredient'`.

## Why custom tables?
Pack tiers and bundle compositions are structured and benefit from:
- validation
- predictable queries
- reliable bulk editing
- safe re-sync without postmeta sprawl
