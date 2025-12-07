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

## Why custom tables?
Pack tiers and bundle compositions are structured and benefit from:
- validation
- predictable queries
- reliable bulk editing
- safe re-sync without postmeta sprawl
