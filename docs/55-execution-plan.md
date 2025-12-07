# Execution Plan (Dependencies-Aware)

This playbook converts the Phase 1.5 spec into an actionable, error-minimized sequence for the team. It assumes all work happens on this repo’s main branch with WordPress 6.5+, PHP 7.4+, WooCommerce (stable, HPOS on), Elementor + Pro, and the Hello Elementor theme.

## Pre-flight (one-time per environment)
- **Verify stack**: WP 6.5+, PHP 7.4+, WooCommerce current (HPOS enabled), Elementor + Pro active, Hello Elementor theme. Keep Woo/Elementor checks from `includes/core/class-pls-admin-notices.php` intact.
- **Permalinks**: Set WP permalinks to “Post name” to avoid AJAX/REST routing surprises.
- **Woo HPOS**: Confirm HPOS enabled; plugin already declares compatibility in `includes/class-pls-plugin.php`.
- **Test data**: Prepare a small set of base products, attributes, and pack tiers for iterative sync testing.

## Coding order (short-circuit failures early)
1. **Data layer first**: Add repositories so all higher layers call stable APIs.
2. **Attributes/Swatches admin + sync**: Land attribute CRUD and Woo term sync to unblock pack-tier attachment later.
3. **Products/Packs admin + sync**: Build UI and Woo sync that depends on global pack-tier attribute.
4. **Bundles (data + minimal UI)**: Optional for this pass but scaffold data access to support AJAX/cart work.
5. **Elementor wiring**: Hook Configurator to Woo variation form; ensure dynamic tag surfaces `_pls_units`.
6. **Offers/cart injection**: Implement bundle composition + AJAX apply path once Woo objects exist.

## Repositories (add under `includes/data/`)
- **Base products** (`repo-base-product.php` – `PLS_Repo_Base_Product`): `all`, `get`, `insert`, `update`, `set_wc_product_id`.
- **Pack tiers** (`repo-pack-tier.php` – `PLS_Repo_Pack_Tier`): `for_base`, `upsert`, `set_wc_variation_id`.
- **Attributes** (`repo-attributes.php` – `PLS_Repo_Attributes`):
  - Attributes: `attrs_all`, `insert_attr`, `set_wc_attribute_id`.
  - Values: `values_for_attr`, `insert_value`, `set_term_id_for_value`.
  - Swatches: `swatch_for_value`, `upsert_swatch_for_value`.
- **Bundle repos** (optional now, needed for offers/cart): bundle + bundle_item CRUD helpers.
- **Wire-up**: Register all repo files in `PLS_Plugin::includes()` to avoid autoload gaps.

### Safety/quality gates for repos
- Use `$wpdb->prefix` when resolving table names via `PLS_Repositories::table()`; never hardcode `wp_`.
- Sanitize all inputs (`sanitize_text_field`, `absint`, `floatval`) before insert/update.
- Return associative arrays (not raw DB objects) for consistency; treat missing rows as `null`.
- Wrap DB writes with `prepare` to prevent SQL injection; let WordPress handle errors via return codes.

## Attributes & Swatches (admin + sync)
- **Admin screen (`includes/admin/screens/attributes.php`)**:
  - Top form: attr_key, label, is_variation checkbox, sort_order → calls `insert_attr`.
  - List: shows label/key/is_variation/wc_attribute_id and nested values.
  - Per-attribute value form: attribute_id (hidden), value_key, label, optional SEO fields (may skip in UI v1), swatch_type (label|color|icon|image), swatch_value → calls `insert_value` then `upsert_swatch_for_value`.
  - CTA: “Sync Attributes to Woo” → nonce → `PLS_WC_Sync::sync_attributes_from_pls()`; surface success/fail notices.
- **Sync implementation** (`PLS_WC_Sync::sync_attributes_from_pls`):
  - For each attribute: `slug = sanitize_title( attr_key )`; find/create Woo attribute (`wc_create_attribute`); persist `wc_attribute_id`; taxonomy via `wc_attribute_taxonomy_name( $slug )`.
  - For each value: `wp_insert_term( label, $tax, [ 'slug' => value_key ] )` if missing; store `term_id`; apply `_pls_swatch_type` / `_pls_swatch_value` term meta when swatch exists.
- **Validation/UX**: Prevent duplicate attr_key/value_key; show inline admin notices for required fields.
- **Idempotency**: Re-running sync must update labels/swatch meta without creating duplicate terms.

## Products & Packs (admin + sync)
- **Admin screen (`includes/admin/screens/products.php`)**:
  - List view via `PLS_Repo_Base_Product::all()`; columns: ID, Name, Slug, Status (draft/live), Woo product (link if exists).
  - Actions: Add Base Product, Sync All (nonce). Row actions: Edit, Sync (nonce per row).
  - Form routing: `?page=pls-products&pls_action=add|edit&id=...`.
  - Fields: Name (required), Slug (auto from name if empty), Status (Draft/Live), Categories (multi-select product_cat; store comma-separated IDs in `category_path`).
  - Pack tiers editor (fixed keys trial, starter, brand_entry, growth, wholesale): enabled checkbox, units (int), price (decimal), sort (int). Persist via `PLS_Repo_Pack_Tier::upsert` per tier.
  - POST flow: sanitize → insert/update base product → save tiers → redirect with notice to avoid resubmission.
- **Woo sync (`includes/wc/class-pls-wc-sync.php`)**:
  - `pack_tier_definitions()` returns tier key => label map.
  - `ensure_pack_tier_attribute()` ensures global attribute `pack-tier` exists (create with `wc_create_attribute` if missing; clear `wc_attribute_taxonomies` transient) and terms Trial/Starter/Brand Entry/Growth/Wholesale exist; return attribute_id + taxonomy.
  - `sync_base_product_to_wc( $base_product_id )`:
    - Load base; create/reuse variable product (status publish if PLS status=live else draft); store `wc_product_id` back.
    - Apply categories from `category_path` to `product_cat`.
    - Attach attribute `pa_pack-tier` as visible + variation attribute with all relevant term IDs.
    - For enabled tiers: create/update variations with attribute `pa_pack-tier` = tier key, price from PLS, status publish; save `_pls_units` meta; backfill `wc_variation_id`.
  - `sync_all_base_products()` loops all bases, calling single sync; collect messages for admin notices.
- **Idempotency/defensive**: Skip disabled tiers; handle deleted Woo products by recreating; log via `PLS_Logger::info()`.

## Bundles & Deals (when ready)
- **Data**: Tables `pls_bundle`, `pls_bundle_item` already exist; add repos for CRUD and joins.
- **Admin UI (`includes/admin/screens/bundles.php`)**: list bundles; add/edit general fields (name/key/slug/status/base_price/pricing_mode); items repeater (base product, tier, qty, units override, sort); offer rules JSON/UI; per-bundle sync or “logical bundle” mode.
- **Cart logic**: `PLS_Ajax::get_offers()` derives offers from product/cart; `pls_apply_offer` resolves bundle items to Woo variations and adds to cart, tagging items with `_pls_bundle_id`.

## Elementor integration
- **Configurator widget**: On render, detect current Woo product/variations; frontend JS selects `attribute_pa_pack-tier` on Woo add-to-cart form when user picks a tier; optionally fetch `_pls_units` for UI badges.
- **Dynamic tag `PLS_DTag_Pack_Units`**: Output `_pls_units` for selected/default variation so templates can show “Pack of X units.”
- **Bundle Offer widget**: `assets/js/offers.js` should call `pls_get_offers` (product ID + cart hash), render cards, and trigger `pls_apply_offer` to inject bundle items.

## Testing matrix (per module)
- **Repos**: Unit smoke via WP shell or small harness—insert, update, fetch, foreign key alignment (base ↔ tiers, attr ↔ values ↔ swatches).
- **Attribute sync**: Re-run sync twice to confirm idempotency; verify Woo global attributes and term meta reflect PLS data.
- **Product sync**: Create/edit base product, toggle tiers, sync twice; confirm Woo variable product + variations reuse IDs and prices/units update.
- **Elementor**: In Hello Elementor, place Configurator on a product template; ensure tier selection changes variation dropdown; dynamic tag shows `_pls_units` value.
- **Bundles**: When implemented, apply offer from widget/AJAX and verify cart items carry `_pls_bundle_id` and correct variations.

## Deployment/operability notes
- Keep WP_DEBUG/LOG on in staging; use `PLS_Logger::info()` inside sync flows for traceability.
- After schema changes, bump activator/migration steps and test activation on a fresh site.
- HPOS + variation sync: ensure no direct `wp_posts` SQL writes—always use Woo CRUD classes to stay HPOS-safe.
- Nonce all admin actions (sync buttons, add/edit forms) and escape all output (`esc_html`, `esc_attr`).
