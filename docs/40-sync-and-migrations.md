# Sync & Migrations

## Product Sync States

1. **Synced & Active**: Product synced, WooCommerce product published
2. **Synced & Inactive**: Product synced, WooCommerce product draft
3. **Update Available**: PLS product changed, needs re-sync
4. **Not Synced**: Product not yet synced to WooCommerce

## Sync Actions

- **Sync**: Create/update WooCommerce product
- **Activate**: Set WooCommerce product to published
- **Deactivate**: Set WooCommerce product to draft
- **Update**: Re-sync when changes detected

## Migrations

- `v0.8.0`: Pack tier attribute system
- `v0.9.0`: Tier-variable pricing
- `v1.0.0`: UI modernization
- `v1.1.0`: Bundle system
- `v1.2.0`: Tutorial overhaul, tiered commission

Migrations run automatically on plugin activation/update.
