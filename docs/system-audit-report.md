# PLS Private Label Store - System Audit Report

**Date:** January 29, 2026  
**Auditor:** Automated System Analysis  
**Version:** 4.9.9+

---

## Executive Summary

This comprehensive audit identified **critical**, **high**, **medium**, and **low** priority issues across security, data integrity, error handling, UX, and functionality. The plugin has a solid foundation but requires attention in several key areas before production deployment.

### Issue Count by Severity

| Severity | Count | Category |
|----------|-------|----------|
| 🔴 Critical | 3 | WooCommerce Setup, Data Integrity |
| 🟠 High | 8 | Security, Error Handling, Data Integrity |
| 🟡 Medium | 12 | UX, Code Quality, Missing Features |
| 🟢 Low | 15+ | Code Improvements, Best Practices |

---

## 1. CRITICAL ISSUES

### 1.1 WooCommerce Essential Pages Missing

**Impact:** Customers CANNOT complete orders

| Page | Status | Required Shortcode |
|------|--------|-------------------|
| Cart | ❌ 404 Error | `[woocommerce_cart]` |
| Checkout | ❌ 404 Error | `[woocommerce_checkout]` |
| My Account | ❌ Not Set | `[woocommerce_my_account]` |

**Fix Required:**
1. Go to WooCommerce > Settings > Advanced > Page Setup
2. Create pages with the required shortcodes
3. Assign pages in WooCommerce settings

### 1.2 Orphan Data on Product Deletion

**Impact:** Data integrity issues, storage bloat

When a product is deleted, the following records are NOT cleaned up:
- Commission records (remain with invalid `product_id`)
- Bundle items referencing the product
- Potential WooCommerce variations

**File:** `includes/admin/class-pls-admin-ajax.php` (lines 1629-1634)

**Fix Required:** Add cascade delete logic for all related tables.

### 1.3 Multi-Operation Transactions Missing

**Impact:** Partial failures leave database in inconsistent state

**Affected Operations:**
- `save_product()` - Multiple DB writes without transaction
- `delete_product()` - Multiple deletes without transaction
- `sync_all_products()` - No rollback on failure

**Fix Required:** Wrap multi-step operations in `$wpdb->query('START TRANSACTION')` / `COMMIT` / `ROLLBACK`.

---

## 2. HIGH PRIORITY ISSUES

### 2.1 Security - CSRF in Add to Cart

**File:** `includes/frontend/class-pls-ajax.php` (line 60)

**Issue:** The `add_to_cart()` AJAX handler lacks nonce verification, allowing Cross-Site Request Forgery attacks.

**Current Code:**
```php
public static function add_to_cart() {
    // Missing: check_ajax_referer()
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
```

**Fix Required:**
```php
public static function add_to_cart() {
    check_ajax_referer( 'pls_add_to_cart', 'nonce' );
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
```

### 2.2 Custom Orders View Button Broken

**File:** `assets/js/custom-orders.js`

**Issue:** Modal display inconsistency - JavaScript uses `.show()` but CSS expects `.is-active` class.

**Status:** ✅ Fixed locally, awaiting deployment

### 2.3 No Error Handling in AJAX Handlers

**File:** `includes/admin/class-pls-admin-ajax.php`

**Issue:** 30+ AJAX handlers lack try/catch blocks. Exceptions cause unhandled errors.

**Affected Handlers:**
- `create_ingredients()`
- `save_product()`
- `delete_product()`
- `sync_product()`
- `create_wc_order_from_custom()`
- And 25+ more

**Fix Required:** Wrap all AJAX handler logic in try/catch blocks.

### 2.4 Database Operations Without Error Checks

**Files:**
- `includes/data/repo-base-product.php`
- `includes/data/repo-pack-tier.php`
- `includes/data/repo-commission.php`

**Issue:** `$wpdb->insert()` and `$wpdb->update()` results are not checked. Silent failures possible.

### 2.5 Email Sending Without Verification

**File:** `includes/admin/class-pls-admin-ajax.php`

**Issue:** 6+ `wp_mail()` calls without checking if email was actually sent.

**Lines:** 2177, 235, 2893, 3018, 3155, 3213

---

## 3. MEDIUM PRIORITY ISSUES

### 3.1 Incomplete Features (TODO/Stubs)

**File:** `includes/frontend/class-pls-ajax.php`

| Feature | Status | Lines |
|---------|--------|-------|
| Offer Eligibility Logic | Stub | 28-43 |
| Apply Offer Behavior | Stub | 46-55 |

**Current Behavior:** Returns placeholder data, doesn't actually apply offers.

### 3.2 Hardcoded Values

| Type | Value | Files |
|------|-------|-------|
| URL | `https://bodocibiophysics.com/label-guide/` | 6 files |
| Email | `n.nikolic97@gmail.com` | 5 files |
| Domain | `bodocibiophysics.com` | 1 file |

**Recommendation:** Move to WordPress options or constants.

### 3.3 Race Conditions

**Affected Operations:**

| Operation | Risk | File |
|-----------|------|------|
| `save_product()` | Duplicate products | class-pls-admin-ajax.php |
| `sync_product()` | Conflicting updates | class-pls-admin-ajax.php |
| `upsert()` pack tiers | Duplicate inserts | repo-pack-tier.php |
| Primary attribute setting | Two requests both set primary | repo-attributes.php |

### 3.4 "View Cart" Link Wrong URL

**File:** `assets/js/offers.js` (line 423)

**Issue:** Link defaults to `/cart/` or `wc_add_to_cart_params.cart_url` - both fail without WooCommerce page setup.

### 3.5 Missing Input Validation

**Repository classes accept data without validation:**
- No check for empty `slug` or `name`
- No validation of `status` against allowed values
- No foreign key validation before insert
- No price/units range validation

---

## 4. UX ISSUES

### 4.1 Missing Loading States

| Location | Issue |
|----------|-------|
| Offers loading | No visual feedback |
| Add to Cart | Button text changes but no spinner |
| Custom Order submit | Text changes but no visual indicator |
| Product sync | No progress indicator |

### 4.2 Excessive use of `alert()`

**Count:** 50+ instances

**Files:**
- `assets/js/custom-orders.js` - 17 instances
- `assets/js/admin.js` - 30+ instances
- `assets/js/onboarding.js` - 2 instances
- `assets/js/offers.js` - 2 instances

**Recommendation:** Replace with toast/notification system.

### 4.3 Accessibility Issues

| Issue | Count |
|-------|-------|
| Missing `aria-label` | 10+ |
| Missing `aria-live` regions | Multiple |
| Missing focus management on modals | 2+ |

---

## 5. CODE QUALITY

### 5.1 XSS - Unescaped Output (Low Risk)

**File:** `includes/admin/screens/products.php`

```php
// Line 708 - Should use esc_html()
echo number_format( $units * $price, 2 );
```

### 5.2 File Operations Without Error Handling

**Files:**
- `includes/core/class-pls-system-test.php` (line 1468)
- `includes/admin/class-pls-admin-ajax.php` (line 4046)
- `includes/core/class-pls-sample-data.php` (line 3157)

---

## 6. ARCHITECTURE RECOMMENDATIONS

### 6.1 Add Transaction Support

```php
// Example implementation
public static function save_product_safe( $data ) {
    global $wpdb;
    $wpdb->query( 'START TRANSACTION' );
    
    try {
        $result = self::persist_product( $data );
        if ( ! $result ) {
            throw new Exception( 'Failed to persist product' );
        }
        
        $sync = self::sync_single_product( $result['base_id'] );
        if ( ! $sync ) {
            throw new Exception( 'Failed to sync product' );
        }
        
        $wpdb->query( 'COMMIT' );
        return $result;
    } catch ( Exception $e ) {
        $wpdb->query( 'ROLLBACK' );
        error_log( '[PLS] Save product failed: ' . $e->getMessage() );
        return false;
    }
}
```

### 6.2 Add Cascade Delete

```php
// In delete_product_records()
public static function delete_product_records( $base_id ) {
    global $wpdb;
    
    // Delete commission records
    $wpdb->delete( $wpdb->prefix . 'pls_commission', array( 'product_id' => $base_id ) );
    
    // Delete bundle items referencing this product
    $wpdb->delete( $wpdb->prefix . 'pls_bundle_item', array( 'base_product_id' => $base_id ) );
    
    // Existing deletions...
    PLS_Repo_Product_Profile::delete_for_base( $base_id );
    PLS_Repo_Pack_Tier::delete_for_base( $base_id );
    PLS_Repo_Base_Product::delete( $base_id );
}
```

### 6.3 Add Nonce to Add to Cart

**JavaScript (offers.js):**
```javascript
$.ajax({
    url: plsOffers.ajaxUrl || '/wp-admin/admin-ajax.php',
    type: 'POST',
    data: {
        action: 'pls_add_to_cart',
        nonce: plsOffers.addToCartNonce, // Add this
        product_id: productId,
        variation_id: variationId,
        quantity: 1
    },
```

**PHP (class-pls-frontend-display.php):**
```php
wp_localize_script( 'pls-offers', 'plsOffers', array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'pls_offers' ),
    'addToCartNonce' => wp_create_nonce( 'pls_add_to_cart' ), // Add this
) );
```

---

## 7. TESTING RESULTS

### 7.1 Frontend Testing

| Test | Result |
|------|--------|
| Product page loads | ✅ Pass |
| Configurator modal opens | ✅ Pass |
| Pack tier selection | ✅ Pass |
| Price calculations | ✅ Pass |
| Add to Cart AJAX | ✅ Pass |
| View Cart link | ❌ Fail (404) |
| Checkout flow | ❌ Fail (404) |

### 7.2 Admin Testing

| Test | Result |
|------|--------|
| Dashboard loads | ✅ Pass |
| Products page | ✅ Pass |
| Bundles page | ✅ Pass |
| Categories page | ✅ Pass |
| Product Options | ✅ Pass |
| Custom Orders (Add) | ✅ Pass |
| Custom Orders (View) | ❌ Fail (modal doesn't open) |
| Settings page | ✅ Pass |
| Analytics | ✅ Pass |

---

## 8. PRIORITY FIX ORDER

### Immediate (Before Production)

1. ⬜ Create WooCommerce Cart/Checkout/My Account pages
2. ⬜ Deploy Custom Orders View button fix
3. ⬜ Add CSRF protection to `add_to_cart()`

### Short Term (Next Sprint)

4. ⬜ Add transaction support to `save_product()` and `delete_product()`
5. ⬜ Add cascade delete for commission and bundle items
6. ⬜ Add try/catch to AJAX handlers
7. ⬜ Check `wp_mail()` return values

### Medium Term

8. ⬜ Implement offers/eligibility logic (currently stub)
9. ⬜ Replace `alert()` with toast notifications
10. ⬜ Add loading states to async operations
11. ⬜ Move hardcoded values to settings

### Long Term

12. ⬜ Add accessibility improvements (ARIA labels, focus management)
13. ⬜ Add input validation to repository classes
14. ⬜ Add race condition prevention (row locking)
15. ⬜ Add comprehensive error logging

---

## 9. FILES REQUIRING CHANGES

| File | Priority | Issues |
|------|----------|--------|
| `includes/frontend/class-pls-ajax.php` | High | CSRF, stubs |
| `includes/admin/class-pls-admin-ajax.php` | High | Transactions, try/catch, cascade delete |
| `assets/js/custom-orders.js` | High | Modal fix (done), alerts |
| `includes/data/repo-*.php` | Medium | Validation, error checking |
| `assets/js/offers.js` | Medium | Cart URL, loading states |
| `assets/js/admin.js` | Low | Alerts, hardcoded values |

---

## Appendix A: Security Checklist

- [x] Nonce verification on most AJAX handlers
- [ ] Nonce verification on `add_to_cart()`
- [x] Capability checks on admin AJAX handlers
- [x] SQL queries use `$wpdb->prepare()` (mostly)
- [x] Output escaping with `esc_html()` / `esc_attr()` (mostly)
- [ ] Complete XSS protection

## Appendix B: Data Flow Diagram

```
Frontend Product Page
        ↓
    [Configure & Order]
        ↓
    Select Pack Tier
        ↓
    Select Options
        ↓
    [Add to Cart] ──→ AJAX: pls_add_to_cart ──→ WC()->cart->add_to_cart()
        ↓
    Success Modal
        ↓
    [View Cart] ──→ /cart/ ──→ 404 ERROR (page missing)
```

## Appendix C: Database Tables

| Table | Purpose | Integrity Issues |
|-------|---------|------------------|
| `pls_base_product` | Core products | None |
| `pls_pack_tier` | Pricing tiers | FK not enforced |
| `pls_product_profile` | Product details | FK not enforced |
| `pls_attribute` | Product options | None |
| `pls_attribute_value` | Option values | None |
| `pls_bundle` | Bundle definitions | None |
| `pls_bundle_item` | Bundle products | Orphan risk |
| `pls_custom_order` | Lead tracking | None |
| `pls_commission` | Sales commissions | Orphan risk |
| `pls_category` | Product categories | None |

---

*Report generated by automated analysis. Manual verification recommended for critical fixes.*
