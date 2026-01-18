# PLS Plugin Technical Documentation v1.1.0

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Business Domain](#business-domain)
3. [Data Flow & Sync Logic](#data-flow--sync-logic)
4. [Database Schema](#database-schema)
5. [API Endpoints & AJAX Handlers](#api-endpoints--ajax-handlers)
6. [Developer Guide](#developer-guide)
7. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

### Plugin Structure

The PLS (Private Label Store) plugin follows WordPress plugin architecture standards:

```
pls-private-label-store/
├── pls-private-label-store.php    # Main plugin file
├── includes/
│   ├── class-pls-plugin.php       # Bootstrap and initialization
│   ├── admin/                      # Admin interface
│   │   ├── class-pls-admin-menu.php
│   │   ├── class-pls-admin-ajax.php
│   │   └── screens/                # Admin page templates
│   ├── core/                       # Core functionality
│   │   ├── class-pls-activator.php
│   │   ├── class-pls-onboarding.php
│   │   └── class-pls-tier-rules.php
│   ├── data/                       # Data access layer
│   │   ├── class-pls-repositories.php
│   │   ├── repo-base-product.php
│   │   ├── repo-pack-tier.php
│   │   ├── repo-bundle.php
│   │   └── repo-commission.php
│   ├── wc/                         # WooCommerce integration
│   │   ├── class-pls-wc-sync.php
│   │   └── class-pls-bundle-cart.php
│   └── elementor/                  # Elementor widgets
├── assets/
│   ├── css/
│   │   ├── admin.css               # Admin styles (v1.0.0 design system)
│   │   └── onboarding.css           # Onboarding/helper styles
│   └── js/
│       ├── admin.js                # Admin JavaScript
│       └── onboarding.js           # Onboarding/helper JavaScript
└── docs/                           # Documentation
```

### WordPress Integration Points

**Hooks:**
- `plugins_loaded` - Initialize plugin components
- `admin_enqueue_scripts` - Load admin assets
- `woocommerce_order_status_changed` - Commission calculation trigger
- `woocommerce_before_calculate_totals` - Bundle cart detection

**Capabilities:**
- `manage_pls_products` - Product management
- `manage_pls_orders` - Order management
- `manage_pls_commission` - Commission management

### WooCommerce Sync Architecture

PLS products sync to WooCommerce as:
- **Base Products** → Variable Products
- **Pack Tiers** → Product Variations (with `pa_pack-tier` attribute)
- **Bundles** → Grouped Products

Sync is unidirectional: PLS → WooCommerce (PLS is source of truth).

---

## Business Domain

### Pack Tier Structure

Pack tiers represent quantity-based pricing levels for single products:

| Tier | Units | Price/Unit | Commission/Unit |
|------|-------|-----------|-----------------|
| Trial Pack | 50 | $15.90 | $0.80 |
| Starter Pack | 100 | $14.50 | $0.75 |
| Brand Entry | 250 | $12.50 | $0.65 |
| Growth Brand | 500 | $9.50 | $0.40 |
| Wholesale Launch | 1000 | $7.90 | $0.29 |

**Customisation Levels:**
- Trial/Starter: Standard formula only
- Brand Entry+: Access to customisation (peptides, fragrances, actives)
- Growth Brand+: Full customisation included

### Bundle Structure

Bundles combine multiple SKUs (different products) with special pricing:

| Bundle | SKUs × Units | Total Units | Price/Unit | Commission/Unit |
|--------|-------------|-------------|-----------|-----------------|
| Mini Line | 2 × 250 | 500 | $10.90 | $0.59 |
| Starter Line | 3 × 300 | 900 | $9.90 | $0.49 |
| Growth Line | 4 × 400 | 1600 | $8.20 | $0.32 |
| Premium Line | 6 × 500 | 3000 | $7.50 | $0.25 |

**Bundle Behavior:**
- Customer picks which products to include
- Cart automatically detects bundle qualification
- Bundle pricing applied when cart matches bundle criteria

### Commission Calculation Model

**Per-Unit Commission:**
- Each pack tier has a fixed commission per unit
- Each bundle type has a fixed commission per unit
- Commission = `units_sold × commission_rate_per_unit`

**Commission Workflow:**
1. Order status changes to `completed`
2. System checks if order contains PLS products
3. For each item, determines tier or bundle
4. Calculates commission using rates from Settings
5. Creates commission record with `pending` status
6. Admin reviews and approves/adjusts
7. Admin marks as invoiced/paid

### Custom Order Sales Pipeline

Custom orders (quote-based inquiries) flow through stages:

1. **New Lead** - Initial inquiry received
2. **Contacted** - Customer has been reached
3. **Sampling** - Sample products sent
4. **Negotiating** - Price/terms discussion
5. **Won** - Order confirmed
6. **Lost** - Opportunity closed without sale

---

## Data Flow & Sync Logic

### Product Sync Flow (PLS → WooCommerce)

```
PLS Base Product
    ↓
Check if wc_product_id exists
    ↓
[No] Create new WC Variable Product
[Yes] Load existing WC Product
    ↓
Update product name, slug, status
    ↓
Sync categories (product_cat taxonomy)
    ↓
Ensure pack-tier attribute exists
    ↓
For each enabled Pack Tier:
    ↓
    Check if variation exists (wc_variation_id)
    ↓
    [No] Create new WC Variation
    [Yes] Update existing Variation
    ↓
    Set variation attributes (pa_pack-tier)
    Set variation price
    Set variation status (publish)
    Store _pls_units meta
    ↓
    Update backreference (wc_variation_id in PLS)
    ↓
Save WC Product
```

### Sync State Detection Algorithm

The `detect_product_sync_state()` method compares PLS product with WooCommerce product:

**Comparison Checks:**
1. Product name/title match
2. Product slug match
3. Categories match (term IDs)
4. Product status match (publish/draft)
5. Pack tier count matches variation count
6. Pack tier prices match variation prices

**States:**
- `synced_active` - Data matches, WC status = publish, PLS status = live
- `synced_inactive` - Data matches, WC status = draft, PLS status = draft
- `update_available` - Data differs between PLS and WC
- `not_synced` - No WC product exists

### Pack Tier → WC Variation Mapping

**Attribute System:**
- Pack Tier is stored as primary attribute (`is_primary = 1`)
- Attribute slug: `pa_pack-tier`
- Each tier value has:
  - `value_key` (e.g., `tier_1`, `tier_2`)
  - `label` (e.g., "Trial Pack (50 units)")
  - Term meta: `_pls_default_units` (units count)
  - Term meta: `_pls_tier_level` (tier level)

**Variation Creation:**
- Variation attribute: `pa_pack-tier` = tier value_key
- Variation price: From PLS pack tier price
- Variation meta: `_pls_units` = pack tier units
- Backreference: Store `wc_variation_id` in `pls_pack_tier` table

### Bundle → WC Grouped Product Mapping

**Bundle Sync:**
1. Create/update WC Grouped Product
2. Set product type to `grouped`
3. Link child products (if bundle items defined)
4. Store bundle metadata:
   - `_pls_bundle_id` - PLS bundle ID
   - `_pls_bundle_key` - Bundle key identifier
   - `_pls_bundle_rules` - Bundle rules JSON

**Cart Detection:**
- Hook: `woocommerce_before_calculate_totals`
- Count distinct PLS products in cart
- Check quantities match bundle criteria
- Apply bundle pricing automatically
- Store bundle info in cart item data

---

## Database Schema

### Core Tables

#### `wp_pls_base_product`
Stores base product definitions.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) | Primary key |
| wc_product_id | BIGINT(20) | Linked WooCommerce product ID |
| slug | VARCHAR(200) | URL-friendly slug (unique) |
| name | VARCHAR(255) | Product name |
| category_path | TEXT | Comma-separated category IDs |
| status | VARCHAR(20) | `live` or `draft` |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY slug
- KEY wc_product_id
- KEY status

#### `wp_pls_pack_tier`
Stores pack tier definitions for each product.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) | Primary key |
| base_product_id | BIGINT(20) | Foreign key to base_product |
| tier_key | VARCHAR(50) | Tier identifier (e.g., `tier_1`) |
| units | INT(11) | Number of units |
| price | DECIMAL(18,2) | Price per unit |
| is_enabled | TINYINT(1) | Whether tier is active |
| wc_variation_id | BIGINT(20) | Linked WooCommerce variation ID |
| sort_order | INT(11) | Display order |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY base_tier (base_product_id, tier_key)
- KEY base_product_id

#### `wp_pls_bundle`
Stores bundle definitions.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) | Primary key |
| wc_product_id | BIGINT(20) | Linked WooCommerce Grouped Product ID |
| bundle_key | VARCHAR(50) | Bundle identifier (unique) |
| slug | VARCHAR(200) | URL-friendly slug (unique) |
| name | VARCHAR(255) | Bundle name |
| base_price | DECIMAL(18,2) | Total bundle price |
| pricing_mode | VARCHAR(30) | `fixed` or `sum_discount` |
| discount_amount | DECIMAL(18,2) | Discount amount (if applicable) |
| status | VARCHAR(20) | `live` or `draft` |
| offer_rules_json | LONGTEXT | JSON with bundle rules (SKU count, units, pricing) |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY bundle_key
- UNIQUE KEY slug
- KEY wc_product_id
- KEY status

#### `wp_pls_bundle_item`
Stores products included in bundles (optional - for pre-defined bundles).

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) | Primary key |
| bundle_id | BIGINT(20) | Foreign key to bundle |
| base_product_id | BIGINT(20) | Foreign key to base_product |
| tier_key | VARCHAR(50) | Pack tier to include |
| units_override | INT(11) | Override units (if different from tier default) |
| qty | INT(11) | Quantity of this product in bundle |
| sort_order | INT(11) | Display order |

**Indexes:**
- PRIMARY KEY (id)
- KEY bundle_id
- KEY base_product_id
- KEY tier_key

#### `wp_pls_product_profile`
Stores detailed product information.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) | Primary key |
| base_product_id | BIGINT(20) | Foreign key to base_product (unique) |
| short_description | TEXT | Brief product description |
| long_description | LONGTEXT | Full product description |
| directions_text | TEXT | Usage directions |
| featured_image_id | BIGINT(20) | Featured image attachment ID |
| gallery_ids | TEXT | Comma-separated gallery image IDs |
| label_enabled | TINYINT(1) | Whether custom label is enabled |
| label_price_per_unit | DECIMAL(18,2) | Label application price |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

#### `wp_pls_commission`
Stores commission records.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) | Primary key |
| wc_order_id | BIGINT(20) | WooCommerce order ID |
| wc_order_item_id | BIGINT(20) | WooCommerce order item ID |
| product_id | BIGINT(20) | WooCommerce product ID |
| tier_key | VARCHAR(50) | Pack tier key (if applicable) |
| bundle_key | VARCHAR(50) | Bundle key (if applicable) |
| units | INT(11) | Units sold |
| commission_rate_per_unit | DECIMAL(18,2) | Commission rate used |
| commission_amount | DECIMAL(18,2) | Total commission |
| status | VARCHAR(20) | `pending`, `approved`, `invoiced`, `paid` |
| invoiced_at | DATETIME | When marked as invoiced |
| paid_at | DATETIME | When marked as paid |
| created_at | DATETIME | Creation timestamp |

**Indexes:**
- PRIMARY KEY (id)
- KEY wc_order_id
- KEY status
- KEY created_at

### Relationships

```
base_product (1) ──→ (many) pack_tier
base_product (1) ──→ (1) product_profile
base_product (1) ──→ (many) bundle_item
bundle (1) ──→ (many) bundle_item
```

---

## API Endpoints & AJAX Handlers

### Admin AJAX Endpoints

All admin AJAX endpoints require:
- Nonce: `pls_admin_nonce`
- Capability: `manage_pls_products` (or specific capability)

#### Product Endpoints

**`pls_save_product`**
- **Purpose**: Create or update product
- **Method**: POST
- **Parameters**:
  - `id` (optional) - Product ID for updates
  - `name` - Product name
  - `slug` - Product slug
  - `categories` - Array of category IDs
  - `status` - `live` or `draft`
  - `pack_tiers` - Array of tier definitions
  - `attributes` - Array of attribute assignments
  - Additional product profile fields
- **Response**: `{ success: true, data: { product: {...}, sync_message: "..." } }`

**`pls_sync_product`**
- **Purpose**: Sync product to WooCommerce
- **Method**: POST
- **Parameters**: `id` - Product ID
- **Response**: `{ success: true, data: { product: {...}, message: "..." } }`

**`pls_activate_product`**
- **Purpose**: Activate product (set to live and publish in WC)
- **Method**: POST
- **Parameters**: `id` - Product ID
- **Response**: `{ success: true, data: { product: {...}, message: "..." } }`

**`pls_deactivate_product`**
- **Purpose**: Deactivate product (set to draft in PLS and WC)
- **Method**: POST
- **Parameters**: `id` - Product ID
- **Response**: `{ success: true, data: { product: {...}, message: "..." } }`

**`pls_delete_product`**
- **Purpose**: Delete product and trash WC product
- **Method**: POST
- **Parameters**: `id` - Product ID
- **Response**: `{ success: true, data: { deleted: true, id: ... } }`

#### Bundle Endpoints

**`pls_save_bundle`**
- **Purpose**: Create or update bundle
- **Method**: POST
- **Parameters**:
  - `bundle_id` (optional) - Bundle ID for updates
  - `bundle_name` - Bundle name
  - `bundle_type` - `mini_line`, `starter_line`, `growth_line`, `premium_line`
  - `sku_count` - Number of SKUs
  - `units_per_sku` - Units per SKU
  - `price_per_unit` - Price per unit
  - `commission_per_unit` - Commission per unit
  - `bundle_status` - `live` or `draft`
- **Response**: `{ success: true, data: { bundle: {...}, message: "..." } }`

**`pls_sync_bundle`**
- **Purpose**: Sync bundle to WooCommerce as Grouped Product
- **Method**: POST
- **Parameters**: `bundle_id` - Bundle ID
- **Response**: `{ success: true, data: { bundle: {...}, message: "..." } }`

**`pls_get_bundle`**
- **Purpose**: Get bundle data for editing
- **Method**: POST
- **Parameters**: `bundle_id` - Bundle ID
- **Response**: `{ success: true, data: { bundle: {...} } }`

**`pls_delete_bundle`**
- **Purpose**: Delete bundle and trash WC product
- **Method**: POST
- **Parameters**: `bundle_id` - Bundle ID
- **Response**: `{ success: true, data: { message: "...", bundle_id: ... } }`

#### Commission Endpoints

**`pls_mark_commission_invoiced`**
- **Purpose**: Mark commission as invoiced
- **Method**: POST
- **Parameters**: `commission_id` - Commission ID
- **Response**: `{ success: true }`

**`pls_mark_commission_paid`**
- **Purpose**: Mark commission as paid
- **Method**: POST
- **Parameters**: `commission_id` - Commission ID
- **Response**: `{ success: true }`

**`pls_send_monthly_report`**
- **Purpose**: Send monthly commission report email
- **Method**: POST
- **Parameters**: `month` - Month in YYYY-MM format
- **Response**: `{ success: true, data: { message: "..." } }`

### Frontend AJAX Endpoints

**`pls_get_offers`**
- **Purpose**: Get available bundle offers for current cart
- **Method**: POST
- **Nonce**: `pls_offers`
- **Response**: `{ success: true, data: { offers: [...] } }`

**`pls_apply_offer`**
- **Purpose**: Apply bundle offer to cart
- **Method**: POST
- **Nonce**: `pls_offers`
- **Parameters**: `offer_id` - Offer ID
- **Response**: `{ success: true, data: { message: "..." } }`

---

## Developer Guide

### Extending Product Sync

To add custom sync logic, hook into the sync process:

```php
// Before sync
add_action('pls_before_sync_product', function($base_product_id) {
    // Custom logic before sync
});

// After sync
add_action('pls_after_sync_product', function($base_product_id, $wc_product_id) {
    // Custom logic after sync
});
```

### Adding New Product Attributes

1. Create attribute via `PLS_Repo_Attributes::insert()`
2. Add values via `PLS_Repo_Attributes::insert_value()`
3. Sync to WooCommerce via `PLS_WC_Sync::sync_attributes()`
4. Assign to products in product modal

### Customizing Commission Calculation

Commission calculation happens in `PLS_Plugin::check_order_payment()`.

To customize:
1. Hook into `woocommerce_order_status_changed`
2. Check order items
3. Calculate commission using custom logic
4. Create commission records via `PLS_Repo_Commission::create()`

### Available Hooks and Filters

**Actions:**
- `pls_before_sync_product` - Before product sync
- `pls_after_sync_product` - After product sync
- `pls_product_saved` - After product save
- `pls_bundle_synced` - After bundle sync

**Filters:**
- `pls_product_sync_data` - Modify product data before sync
- `pls_commission_rate` - Modify commission rate calculation
- `pls_bundle_cart_qualification` - Modify bundle qualification logic

---

## Troubleshooting

### Common Sync Issues

**Issue**: Product sync fails with "WooCommerce product functions unavailable"
- **Solution**: Ensure WooCommerce is active and up to date

**Issue**: Variations not created
- **Solution**: Check that pack-tier attribute exists and has terms

**Issue**: Sync state shows "Update Available" but data looks the same
- **Solution**: Check pack tier count matches variation count, verify prices match

### Commission Calculation Debugging

**Issue**: Commission not calculated on order completion
- **Check**: Order contains PLS products (check `wc_product_id` matches)
- **Check**: Commission rates are set in Settings
- **Check**: Order status is `completed` or `processing`

**Issue**: Wrong commission rate applied
- **Check**: Tier key mapping in `pls_get_tier_key_from_term()`
- **Check**: Bundle key stored in product meta (`_pls_bundle_key`)

### Bundle Cart Detection Issues

**Issue**: Bundle pricing not applied
- **Check**: Bundle status is `live`
- **Check**: Cart has correct number of distinct PLS products
- **Check**: Quantities match bundle requirements
- **Check**: `PLS_Bundle_Cart::init()` is called

**Issue**: Bundle pricing applied incorrectly
- **Check**: Bundle rules JSON structure
- **Check**: Units calculation logic
- **Check**: Cart item metadata storage

### Performance Optimization

**Database Queries:**
- Use `PLS_Repo_*` classes for all database access
- Avoid direct `$wpdb` queries outside repos
- Cache frequently accessed data (e.g., commission rates)

**Sync Performance:**
- Sync products individually, not in bulk
- Use background processing for large syncs
- Cache WooCommerce product objects

**Frontend Performance:**
- Minimize AJAX calls
- Use debouncing for search/filter inputs
- Lazy load modal content

---

## Version History

### v1.1.0
- Product sync state detection (4 states)
- Activate/Deactivate buttons
- Full bundle functionality
- Bundle cart auto-detection
- Semi-automated commission calculation
- Per-section help buttons with tooltips
- Comprehensive technical documentation

### v1.0.0
- Complete UI/UX modernization
- Apple-inspired design system
- Modern component library
- Responsive design

---

## Additional Resources

- [Data Model Documentation](10-data-model.md)
- [WooCommerce Mapping](20-wc-mapping.md)
- [Elementor Integration](30-elementor-integration.md)
- [Sync & Migrations](40-sync-and-migrations.md)
