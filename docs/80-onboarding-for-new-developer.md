# PLS Plugin - Developer Onboarding Guide

## 🎯 Purpose of This Document

This guide is designed to bring a new developer up to speed on the **PLS (Private Label Store) WordPress Plugin** without prior context. Read this document thoroughly before making any changes.

---

## 📋 Executive Summary

**Plugin Name:** PLS – Private Label Store Manager (Woo + Elementor)  
**Current Version:** 0.7.1  
**Target Version:** 0.8.0  
**Purpose:** A WordPress plugin that manages a private-label WooCommerce store with Elementor integration. It provides a custom data model stored in database tables and syncs to native WooCommerce products/variations/attributes so Elementor templates can render them without hacks.

**Key Principle:** Elementor should render **native WooCommerce objects** (variable products, variations, global attributes + terms) so Theme Builder templates work seamlessly.

---

## 🏗️ Architecture Overview

### Technology Stack
- **WordPress:** 6.5+ (HPOS compatible)
- **PHP:** 7.4+
- **WooCommerce:** Current stable (HPOS enabled)
- **Elementor + Elementor Pro:** Theme Builder + Woo widgets
- **Theme:** Hello Elementor
- **Update System:** Self-hosted via UUPD (GitHub-based)

### Core Design Pattern
1. **Custom Database Tables** → Store structured product data (base products, pack tiers, bundles, attributes)
2. **Repository Classes** → Abstract database operations
3. **WooCommerce Sync Layer** → Converts PLS data to native WooCommerce objects
4. **Elementor Integration** → Widgets and dynamic tags for frontend display
5. **Admin Interface** → Modal-based UI for managing products, attributes, bundles

---

## 📁 File Structure

```
pls-private-label-store/
├── pls-private-label-store.php          # Main plugin file (bootstrap)
├── readme.txt                            # WordPress plugin readme
├── uupd/
│   └── index.json                        # Update metadata (version info)
├── assets/
│   ├── css/                              # Admin & frontend styles
│   ├── js/                               # Admin & frontend scripts
│   └── img/                              # Images/icons
├── includes/
│   ├── class-pls-plugin.php             # Main plugin class (singleton)
│   ├── updater.php                       # UUPD update system
│   ├── core/
│   │   ├── class-pls-activator.php       # Database table creation
│   │   ├── class-pls-admin-notices.php   # Admin notices
│   │   ├── class-pls-capabilities.php    # User capabilities
│   │   ├── class-pls-logger.php          # Logging utility
│   │   └── class-pls-taxonomies.php      # Custom taxonomies (ingredients)
│   ├── admin/
│   │   ├── class-pls-admin-menu.php      # Admin menu registration
│   │   ├── class-pls-admin-ajax.php      # Admin AJAX handlers
│   │   └── screens/                      # Admin screen templates
│   │       ├── dashboard.php
│   │       ├── products.php              # Product management UI
│   │       ├── attributes.php            # Attribute/swatch management
│   │       ├── bundles.php               # Bundle management
│   │       ├── categories.php            # Category management
│   │       └── ingredients.php           # Ingredient management
│   ├── data/                              # Repository classes (data access layer)
│   │   ├── class-pls-repositories.php    # Table name resolver
│   │   ├── repo-base-product.php         # Base product CRUD
│   │   ├── repo-pack-tier.php            # Pack tier CRUD
│   │   ├── repo-product-profile.php      # Product profile CRUD
│   │   └── repo-attributes.php           # Attributes/values/swatches CRUD
│   ├── wc/
│   │   └── class-pls-wc-sync.php         # WooCommerce sync logic
│   ├── frontend/
│   │   └── class-pls-ajax.php           # Frontend AJAX endpoints
│   └── elementor/
│       ├── class-pls-elementor.php       # Elementor integration
│       ├── widgets/
│       │   ├── class-pls-widget-configurator.php    # Pack tier/swatch selector
│       │   └── class-pls-widget-bundle-offer.php    # Upgrade offer widget
│       └── dynamic-tags/
│           └── class-pls-dtag-pack-units.php        # Pack units dynamic tag
└── docs/                                  # Documentation
    ├── 00-overview.md
    ├── 10-data-model.md
    ├── 20-wc-mapping.md
    ├── 30-elementor-integration.md
    ├── 40-sync-and-migrations.md
    ├── 50-implementation-spec.md
    └── 80-onboarding-for-new-developer.md (this file)
```

---

## 🗄️ Database Schema

The plugin uses **8 custom tables** (prefixed `wp_pls_`):

### Core Tables
1. **`pls_base_product`** - Base products (name, slug, status, categories, Woo product ID reference)
2. **`pls_pack_tier`** - Pack tiers (units, price, enabled flag, Woo variation ID reference)
3. **`pls_product_profile`** - Extended product data (descriptions, images, ingredients, attributes, label settings)
4. **`pls_bundle`** - Bundle definitions (name, pricing mode, offer rules)
5. **`pls_bundle_item`** - Bundle composition (which products/tiers are in each bundle)

### Attribute System
6. **`pls_attribute`** - Custom attributes (label, is_variation flag, Woo attribute ID reference)
7. **`pls_attribute_value`** - Attribute values (label, SEO fields, Woo term ID reference)
8. **`pls_swatch`** - Visual swatches (color/icon/image) for attribute values

### Custom Taxonomy
- **`pls_ingredient`** - Ingredient taxonomy (terms with icons and descriptions)

**Why Custom Tables?** Pack tiers and bundle compositions are structured and benefit from validation, predictable queries, reliable bulk editing, and safe re-sync without postmeta sprawl.

---

## 🔄 Data Flow

### Product Creation Flow
1. **Admin creates product** → `PLS_Admin_Ajax::save_product()`
2. **Data validated** → Server-side validation (required fields)
3. **Saved to PLS tables** → `PLS_Repo_Base_Product`, `PLS_Repo_Pack_Tier`, `PLS_Repo_Product_Profile`
4. **Auto-sync to WooCommerce** → `PLS_WC_Sync::sync_base_product_to_wc()`
   - Creates/updates WooCommerce variable product
   - Creates/updates variations for each enabled pack tier
   - Stores backreferences (`wc_product_id`, `wc_variation_id`)
5. **Elementor renders** → Uses native WooCommerce objects

### Sync Process
- **Idempotent:** Can be run multiple times without creating duplicates
- **Backreferences:** PLS tables store WooCommerce IDs for reconciliation
- **Status tracking:** Sync status stored per product (`pls_sync_status_{id}` option)

---

## 🎨 Admin Interface

### Main Menu: "PLS" (under WooCommerce)
- **Dashboard** - Overview and quick links
- **Products** - Full product management (modal-based editor)
- **Categories** - Category management
- **Attributes** - Attribute/value/swatch management
- **Ingredients** - Ingredient taxonomy management
- **Bundles** - Bundle management (stub/partial)

### Product Editor Features
- **General Tab:** Name, categories, featured image, gallery
- **Content Tab:** Short description, long description, directions
- **Packs Tab:** Pack tier management (50, 100, 250, 500, 1000 units)
- **Attributes Tab:** Attribute selection with price impacts
- **Ingredients Tab:** Ingredient selection (up to 5 key ingredients)
- **Label Tab:** Custom label options (price per unit, file upload requirements)

### Validation
- **Server-side validation** enforced before save
- **Required fields:** Name, categories, featured image, gallery, descriptions, directions, skin types, benefits, ingredients, pack tiers, attributes
- **Auto-sync** on save (can be disabled if sync fails)

---

## 🔌 WooCommerce Integration

### Sync Mapping
- **Base Product** → WooCommerce Variable Product
- **Pack Tier** → WooCommerce Variation (using `pa_pack-tier` attribute)
- **Attributes** → WooCommerce Global Attributes + Terms
- **Swatches** → Term meta (`_pls_swatch_type`, `_pls_swatch_value`)

### Pack Tier Attribute
- **Global attribute:** `pack-tier` (taxonomy: `pa_pack-tier`)
- **Terms:** `u50`, `u100`, `u250`, `u500`, `u1000` (auto-created)
- **Variation meta:** `_pls_units` stores unit count

### Sync Methods
- `PLS_WC_Sync::sync_base_product_to_wc($base_id)` - Sync single product
- `PLS_WC_Sync::sync_all_base_products()` - Bulk sync
- `PLS_WC_Sync::sync_attributes_from_pls()` - Sync attributes/values

---

## 🎭 Elementor Integration

### Widgets
1. **PLS Configurator** (`pls_configurator`)
   - Shows pack tier selector and swatches on Single Product templates
   - Interacts with WooCommerce add-to-cart form
   - Reads variations and pack tier data

2. **PLS Bundle Offer** (`pls_bundle_offer`)
   - Displays upgrade offers on PDP or Cart templates
   - Uses AJAX to fetch offers (`pls_get_offers`)
   - Can trigger Elementor Pro Popups

### Dynamic Tags
- **PLS Pack Units** (`pls_dtag_pack_units`) - Displays unit count for selected variation

### Frontend Assets
- `assets/css/offers.css` - Bundle offer styling
- `assets/js/offers.js` - AJAX handlers for offers

---

## 📊 Current Implementation Status (v0.7.1)

### ✅ Fully Implemented
- Database schema and table creation
- Repository classes (base products, pack tiers, attributes, product profiles)
- Product management UI (full modal-based editor)
- WooCommerce sync (products → variable products, tiers → variations)
- Attribute/value management with swatches
- Ingredient taxonomy system
- Admin AJAX handlers
- Elementor widget registration
- Self-hosted update system (UUPD via GitHub)

### 🚧 Partially Implemented / Stubs
- **Bundle management:** Tables exist, admin UI is stub, cart logic not implemented
- **Frontend offers:** AJAX endpoints are placeholders (`pls_get_offers`, `pls_apply_offer`)
- **Bundle cart logic:** Not implemented (should add bundle items to cart)

### ❌ Not Implemented
- Bundle sync to WooCommerce
- Bundle cart item grouping
- Offer eligibility logic
- Bundle pricing calculations

---

## 🎯 Version 0.8.0 Goals

### Primary Objectives
1. **Complete Bundle System**
   - Full admin UI for bundle management
   - Bundle sync to WooCommerce (or logical bundle handling)
   - Cart logic for applying bundles
   - Offer eligibility system

2. **Client-Specific Requirements**
   - [TO BE FILLED BY CLIENT SPECIFICATIONS]
   - Review client requirements document
   - Implement custom features as specified

3. **Code Quality & Testing**
   - Improve error handling
   - Add logging for debugging
   - Test sync operations thoroughly
   - Verify Elementor widget functionality

### Development Checklist
- [ ] Review client specifications document
- [ ] Implement bundle management UI
- [ ] Implement bundle cart logic
- [ ] Implement offer system
- [ ] Test WooCommerce sync thoroughly
- [ ] Test Elementor widgets on frontend
- [ ] Update version numbers to 0.8.0
- [ ] Update changelog
- [ ] Test update system
- [ ] Create release

---

## 🛠️ Development Workflow

### Branch Strategy
- **Main branch:** Production-ready code
- **Feature branches:** `feature/feature-name`
- **Current branch:** `feature/v0.8.0-upgrade`

### Version Bump Process
1. Update version in `pls-private-label-store.php` (header + constant)
2. Update version in `uupd/index.json`
3. Update "Stable tag" in `readme.txt`
4. Add changelog entry
5. Commit changes
6. Create and push tag: `git tag v0.8.0 && git push origin v0.8.0`
7. GitHub Actions workflow builds ZIP and creates release automatically

### Testing Checklist
- [ ] Activate plugin (check tables created)
- [ ] Create a product (test validation)
- [ ] Sync to WooCommerce (verify product/variations created)
- [ ] Edit product (verify sync updates)
- [ ] Create attributes/values (verify sync)
- [ ] Test Elementor widgets on frontend
- [ ] Test bundle creation (if implemented)
- [ ] Test update system (if applicable)

---

## 🔍 Key Classes & Methods

### Repository Classes
```php
PLS_Repo_Base_Product::all()                    // Get all products
PLS_Repo_Base_Product::get($id)                 // Get single product
PLS_Repo_Base_Product::insert($data)            // Create product
PLS_Repo_Base_Product::update($id, $data)       // Update product
PLS_Repo_Base_Product::set_wc_product_id()     // Store WooCommerce product ID

PLS_Repo_Pack_Tier::for_base($base_id)          // Get tiers for product
PLS_Repo_Pack_Tier::upsert()                    // Create/update tier
PLS_Repo_Pack_Tier::set_wc_variation_id()       // Store WooCommerce variation ID

PLS_Repo_Attributes::attrs_all()                // Get all attributes
PLS_Repo_Attributes::insert_attr()               // Create attribute
PLS_Repo_Attributes::values_for_attr()          // Get values for attribute
PLS_Repo_Attributes::insert_value()              // Create attribute value
PLS_Repo_Attributes::swatch_for_value()          // Get swatch for value
```

### Sync Classes
```php
PLS_WC_Sync::sync_base_product_to_wc($id)       // Sync product to WooCommerce
PLS_WC_Sync::sync_all_base_products()           // Bulk sync
PLS_WC_Sync::sync_attributes_from_pls()         // Sync attributes
PLS_WC_Sync::pack_tier_definitions()            // Get pack tier definitions
```

### Admin Classes
```php
PLS_Admin_Ajax::save_product()                  // Save product (AJAX)
PLS_Admin_Ajax::delete_product()                // Delete product
PLS_Admin_Ajax::sync_product()                  // Sync single product
PLS_Admin_Ajax::sync_all_products()              // Bulk sync
PLS_Admin_Ajax::create_ingredients()             // Create ingredients
PLS_Admin_Ajax::create_attribute()               // Create attribute
PLS_Admin_Ajax::create_attribute_value()         // Create attribute value
```

---

## 🐛 Common Issues & Solutions

### Sync Issues
- **Problem:** Products not syncing to WooCommerce
- **Solution:** Check WooCommerce is active, verify `wc_product_id` is stored, check sync status option

### Modal Issues
- **Problem:** Modals not opening/closing correctly
- **Solution:** Check JavaScript console, verify modal targeting in `assets/js/admin.js`

### Elementor Widget Issues
- **Problem:** Widgets not displaying on frontend
- **Solution:** Verify Elementor Pro is active, check widget registration, verify WooCommerce product context

### Update Issues
- **Problem:** Updates not showing in WordPress
- **Solution:** Check `uupd/index.json` version, verify GitHub release exists, clear transients

---

## 📚 Additional Resources

### Documentation Files
- `docs/00-overview.md` - High-level overview
- `docs/10-data-model.md` - Database schema details
- `docs/20-wc-mapping.md` - WooCommerce mapping rules
- `docs/30-elementor-integration.md` - Elementor integration details
- `docs/40-sync-and-migrations.md` - Sync patterns
- `docs/50-implementation-spec.md` - Detailed implementation spec

### Code References
- WordPress Plugin Handbook: https://developer.wordpress.org/plugins/
- WooCommerce Developer Docs: https://woocommerce.com/document/woocommerce-developer-resources/
- Elementor Developer Docs: https://developers.elementor.com/

---

## 🚀 Getting Started

1. **Read this document completely**
2. **Review the codebase structure** (use file structure above)
3. **Read existing documentation** (`docs/` folder)
4. **Review client specifications** (if provided)
5. **Set up local development environment**
6. **Activate plugin and explore admin interface**
7. **Review existing code** (start with `includes/class-pls-plugin.php`)
8. **Make changes incrementally** (test after each change)

---

## ⚠️ Important Notes

- **Never modify WooCommerce core files** - Always use hooks/filters
- **Always validate and sanitize user input** - Security is critical
- **Test sync operations** - They affect production data
- **Follow WordPress coding standards** - Use WPCS
- **Keep sync idempotent** - Can be run multiple times safely
- **Maintain backreferences** - Store WooCommerce IDs in PLS tables
- **Log important operations** - Use `PLS_Logger::info()`

---

## 📝 Next Steps

1. **Review client specifications** (if available)
2. **Identify specific requirements for v0.8.0**
3. **Create task breakdown**
4. **Start implementation**
5. **Test thoroughly**
6. **Update version and release**

---

**Last Updated:** 2026-02-21  
**Version:** 0.7.1 → 0.8.0 (in progress)  
**Branch:** `feature/v0.8.0-upgrade`
