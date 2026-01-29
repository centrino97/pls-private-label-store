# Implementation Spec (Phase 1.5)

## Dependencies & target stack
- WordPress 6.5+
- PHP 7.4+
- WooCommerce (current stable, HPOS on – plugin already declares compatibility)
- Elementor + Elementor Pro (Theme Builder + Woo widgets)
- Theme: Hello Elementor

Notes:
- Dependency notices: `includes/core/class-pls-admin-notices.php`.
- HPOS compatibility: `includes/class-pls-plugin.php`.
- Core/admin/frontend/Elementor classes load from `pls-private-label-store.php`.

## What exists today
- Tables (created by `includes/core/class-pls-activator.php`):
  - `pls_base_product`, `pls_pack_tier`, `pls_bundle`, `pls_bundle_item`, `pls_attribute`, `pls_attribute_value`, `pls_swatch`.
- Admin screens (`includes/admin/screens/`): dashboard + stub pages for products, attributes, bundles.
- Data helper: `includes/data/class-pls-repositories.php` (table resolver, no repos yet).
- Woo sync scaffold: `includes/wc/class-pls-wc-sync.php` (stub methods).
- Elementor layer: `includes/elementor/class-pls-elementor.php` registers Pack Units dynamic tag and loads frontend assets for shortcodes.
- AJAX: `includes/frontend/class-pls-ajax.php` exposes placeholder offer/apply endpoints.
- Docs: `docs/00-40` cover overview, data model, Woo mapping, Elementor integration, sync/idempotency.

## Build scope (verticals + layers)
- Products & Packs
- Attributes & Swatches
- Bundles & Deals
- WooCommerce sync layer (connects all tables to native Woo objects)
- Elementor layer (shortcodes render complete pages, dynamic tags provide additional data)

## Data layer – repository classes (add under `includes/data/`)
- `repo-base-product.php` (`PLS_Repo_Base_Product`): `all()`, `get()`, `insert()`, `update()`, `set_wc_product_id()`.
- `repo-pack-tier.php` (`PLS_Repo_Pack_Tier`): `for_base()`, `upsert()`, `set_wc_variation_id()`.
- `repo-attributes.php` (`PLS_Repo_Attributes`):
  - Attributes: `attrs_all()`, `insert_attr()`, `set_wc_attribute_id()`.
  - Values: `values_for_attr()`, `insert_value()`, `set_term_id_for_value()`.
  - Swatches: `swatch_for_value()`, `upsert_swatch_for_value()`.
- (Later) bundle repos for bundles/bundle_items.
- Register new repos in `includes/class-pls-plugin.php::includes()`.

## Module 1 – Products & Packs
### Admin UI (`includes/admin/screens/products.php`)
- List view from `PLS_Repo_Base_Product::all()` with columns: ID, Name, Slug, Status (draft/live), Woo product ID (link when present).
- Actions: Add Base Product, Sync All; row actions: Edit, Sync.
- Forms routed via `?page=pls-products&pls_action=add|edit&id=...`.
- Fields: Name (required), Slug (optional → auto), Status (Draft/Live), Categories (product_cat IDs stored comma-separated in `category_path`).
- Pack tier editor (fixed keys trial, starter, brand_entry, growth, wholesale): enabled flag, units, price, sort order. Save via `PLS_Repo_Pack_Tier::upsert()` per tier.
- POST: sanitize/validate, insert/update base via repos, redirect with notice.
- Sync buttons: single product → `PLS_WC_Sync::sync_base_product_to_wc( $id )`; bulk → `PLS_WC_Sync::sync_all_base_products()` (nonce-protected).

### WooCommerce sync (`includes/wc/class-pls-wc-sync.php`)
- Helper `pack_tier_definitions()` returns tier keys => labels.
- `ensure_pack_tier_attribute()`:
  - Ensure global attribute slug `pack-tier` exists via `wc_create_attribute()` if missing; invalidate `wc_attribute_taxonomies` transient.
  - Taxonomy = `wc_attribute_taxonomy_name( 'pack-tier' )` (→ `pa_pack-tier`).
  - Ensure terms Trial/Starter/Brand Entry/Growth/Wholesale exist with matching slugs.
  - Return attribute_id + taxonomy.
- `sync_base_product_to_wc( $base_product_id )`:
  - Load base row; create or reuse Woo product. New product uses title/slug, status publish when base status=live else draft; set product type `variable`; store `wc_product_id` back.
  - Apply categories from `category_path` IDs.
  - Attach attribute `pa_pack-tier` as visible/for-variation with all relevant term IDs.
  - Fetch enabled tiers via repo; for each, create/update variation with attribute `pa_pack-tier` = tier key, price from PLS, status publish; store `_pls_units` meta and backreference `wc_variation_id` in repo.
- `sync_all_base_products()` loops all bases and calls single sync; collect messages.

## Module 2 – Attributes & Swatches
### Admin UI (`includes/admin/screens/attributes.php`)
- Add attribute form: fields attr_key, label, is_variation (checkbox), sort_order → `insert_attr()`.
- Attribute list shows label/key/is_variation/wc_attribute_id and nested values table.
- Add value form per attribute: attribute_id (hidden), value_key, label, optional SEO fields, swatch_type (label|color|icon|image), swatch_value → `insert_value()` + `upsert_swatch_for_value()`.
- “Sync Attributes to Woo” button → `PLS_WC_Sync::sync_attributes_from_pls()` with admin notice.

### Woo sync (`PLS_WC_Sync::sync_attributes_from_pls()`)
- For each attribute: slug = sanitize_title( attr_key ); find/create Woo attribute via `wc_create_attribute()`; save `wc_attribute_id` in PLS; taxonomy = `wc_attribute_taxonomy_name( $slug )`.
- For each value: ensure term exists (`wp_insert_term`) with slug = value_key; save term_id; apply swatch meta `_pls_swatch_type` / `_pls_swatch_value` when present.

## Module 3 – Bundles & Deals (later in phase)
- Data already in tables (`pls_bundle`, `pls_bundle_item`).
- Admin UI (`includes/admin/screens/bundles.php`): list bundles; add/edit general fields (name/key/slug/status/base_price/pricing mode); items repeater (base product, tier, qty, units override, sort); offer rules JSON/UI; per-bundle sync button (to Woo product or logical bundle).
- Cart logic: applying a bundle resolves base product + tier → Woo variation, adds line items, tags cart items with `_pls_bundle_id` for grouping.
- Offers: `PLS_Ajax::get_offers()` derives upsell bundles from current product/cart; `pls_apply_offer` injects bundle items.

## Elementor integration (frontend layer)
- **Shortcodes**: Use `[pls_single_product]`, `[pls_single_category]`, or `[pls_shop_page]` in Elementor templates via Shortcode widget. These render complete pages with configurator, product info, and bundle offers.
- **Configurator**: Built into `[pls_single_product]` shortcode - detects current Woo product, reads variations/pack tiers; JS sets `select[name="attribute_pa_pack-tier"]` on Woo add-to-cart form.
- **Bundle Offers**: Built into `[pls_single_product]` shortcode - uses `assets/js/offers.js` to call `pls_get_offers` with product ID + cart hash, render cards, and call `pls_apply_offer` to add bundles.
- **Dynamic tag `PLS_DTag_Pack_Units`**: Render `_pls_units` for selected/default variation so templates can show "Pack of X units" in custom displays.

## Cross-cutting behaviours
- Sync should be idempotent; re-running updates without duplicates and respects backreferences (`wc_product_id`, `wc_variation_id`, `wc_attribute_id`, term IDs).
- Add logging via `PLS_Logger::info()` inside sync flows (product/variation counts, attribute term creation, etc.).
- Future: optional dry-run mode for syncs; migrations handled via activator updates or dedicated scripts.
