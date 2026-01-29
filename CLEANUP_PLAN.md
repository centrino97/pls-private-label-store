# PLS v5.0.0 - Dead Code Cleanup Plan

**Issue:** Widgets are disabled but widget files still exist, causing confusion.

---

## 🎯 CONFIRMED ISSUE

You're generating whole pages via shortcodes:
- ✅ `[pls_single_product]` - Full product page
- ✅ `[pls_single_category]` - Category page  
- ✅ `[pls_shop_page]` - Shop page

Widgets are **completely unnecessary** and are **disabled** in code.

---

## 🗑️ DEAD CODE TO REMOVE

### Widget Files (4 files - never registered):
1. `includes/elementor/widgets/class-pls-widget-configurator.php`
2. `includes/elementor/widgets/class-pls-widget-product-info.php`
3. `includes/elementor/widgets/class-pls-widget-bundle-offer.php`
4. `includes/elementor/widgets/class-pls-widget-product-page.php` (even calls non-existent shortcode `pls_product_page`!)

### Empty Method:
- `PLS_Elementor::register_widgets()` - Empty method, never called

---

## ✅ WHAT TO KEEP

### Dynamic Tags (Still Used):
- `class-pls-dtag-pack-units.php` - ✅ Keep (used in templates)

### Frontend Assets (Still Needed):
- `PLS_Elementor::frontend_assets()` - ✅ Keep (loads CSS/JS for shortcodes)

---

## 📝 PROPOSED ACTIONS

### 1. Delete Widget Files
Remove the 4 widget files - they're never loaded/registered.

### 2. Clean Elementor Class
- Remove `register_widgets()` method
- Update comments to clarify shortcode-only architecture
- Keep dynamic tags registration

### 3. Update Documentation
- Fix `docs/00-overview.md` - Change "widgets" to "shortcodes"
- Fix `docs/30-elementor-integration.md` - Document shortcode usage
- Fix `docs/50-implementation-spec.md` - Update architecture

---

## ⚠️ IMPORTANT NOTE

The widget `class-pls-widget-product-page.php` calls:
```php
echo do_shortcode( '[pls_product_page product_id="..."]' );
```

But this shortcode **doesn't exist**! The actual shortcode is `pls_single_product`.

This confirms widgets are completely broken/unused.

---

**Ready to proceed with cleanup?** This will make the codebase cleaner and match the actual architecture.
