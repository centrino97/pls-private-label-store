# PLS Private Label Store - Comprehensive Test Plan

**Version:** 4.9.9+  
**Last Updated:** January 29, 2026  
**Purpose:** Ensure 100% functionality and perfect UX across all features

---

## Table of Contents

1. [Pre-Test Setup](#1-pre-test-setup)
2. [WooCommerce Integration Tests](#2-woocommerce-integration-tests)
3. [Admin Interface Tests](#3-admin-interface-tests)
4. [Frontend Customer Journey Tests](#4-frontend-customer-journey-tests)
5. [AJAX & API Tests](#5-ajax--api-tests)
6. [UX Helper Elements Tests](#6-ux-helper-elements-tests)
7. [Security Tests](#7-security-tests)
8. [Performance Tests](#8-performance-tests)
9. [Error Handling Tests](#9-error-handling-tests)
10. [Mobile Responsiveness Tests](#10-mobile-responsiveness-tests)

---

## 1. Pre-Test Setup

### 1.1 Environment Checklist

| Item | Expected | How to Verify |
|------|----------|---------------|
| WordPress Version | 6.0+ | Dashboard → Updates |
| WooCommerce Version | 8.0+ | Plugins → Installed |
| PLS Plugin Active | Yes | Plugins → Installed |
| PHP Version | 8.0+ | Tools → Site Health |
| Memory Limit | 256M+ | Tools → Site Health |

### 1.2 WooCommerce Pages Setup

**CRITICAL: These pages MUST exist for checkout to work**

| Page | Required Shortcode | URL | Test |
|------|-------------------|-----|------|
| Cart | `[woocommerce_cart]` | /cart/ | Navigate and verify no 404 |
| Checkout | `[woocommerce_checkout]` | /checkout/ | Navigate and verify no 404 |
| My Account | `[woocommerce_my_account]` | /my-account/ | Navigate and verify no 404 |
| Shop | `[woocommerce_shop]` | /shop/ | Navigate and verify products show |

**How to create missing pages:**
1. Go to WooCommerce → Settings → Advanced → Page Setup
2. Click "Create pages" or manually create with shortcodes
3. Assign pages in the dropdown menus

### 1.3 PLS System Test

1. Navigate to: `wp-admin/admin.php?page=pls-system-test`
2. Click "Run All Tests"
3. Verify all tests pass (green checkmarks)
4. Note any failed tests for investigation

---

## 2. WooCommerce Integration Tests

### 2.1 Product Sync Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| WC-01 | Product creates WC product | Create new PLS product with tiers → Save | WC variable product created with variations |  |
| WC-02 | Product updates sync | Edit PLS product name → Save | WC product name updates |  |
| WC-03 | Price sync | Change tier price → Save | WC variation price updates |  |
| WC-04 | Stock status sync | Change stock status → Save | WC product stock updates |  |
| WC-05 | Category sync | Assign category → Save | WC product in correct category |  |
| WC-06 | Image sync | Add featured image → Save | WC product has image |  |
| WC-07 | Deactivate hides product | Deactivate product | WC product set to draft/hidden |  |
| WC-08 | Activate shows product | Activate product | WC product set to publish |  |

### 2.2 Cart & Checkout Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| CC-01 | Add to cart | Select tier → Add to Cart | Item added, success message |  |
| CC-02 | Cart displays item | Go to cart page | Product, tier, price shown correctly |  |
| CC-03 | Cart quantity change | Change quantity | Price recalculates |  |
| CC-04 | Remove from cart | Click remove | Item removed |  |
| CC-05 | Proceed to checkout | Click checkout | Checkout page loads |  |
| CC-06 | Complete order | Fill details → Place order | Order created, confirmation shown |  |
| CC-07 | Order in admin | Check WC Orders | Order appears with correct details |  |

---

## 3. Admin Interface Tests

### 3.1 Dashboard Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| AD-01 | Dashboard loads | Navigate to PLS Dashboard | Stats display correctly |  |
| AD-02 | Quick stats accurate | Compare with actual data | Numbers match reality |  |
| AD-03 | Recent activity shows | Check activity feed | Recent actions listed |  |

### 3.2 Products Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| PR-01 | Products list loads | Navigate to Products | All products displayed |  |
| PR-02 | Add product button | Click "Add Product" | Create modal opens |  |
| PR-03 | Step 1: General | Enter name, select category | Fields accept input, Next works |  |
| PR-04 | Step 2: Data | Enter descriptions, skin types | All fields work |  |
| PR-05 | Step 3: Ingredients | Add/remove ingredients | Repeater works |  |
| PR-06 | Step 4: Pack Tiers | Enter units and prices | Totals calculate correctly |  |
| PR-07 | Step 5: Options | View product options | Options displayed |  |
| PR-08 | Save product | Click Save | Product saves, appears in list |  |
| PR-09 | Edit product | Click Edit on existing | Modal opens with data populated |  |
| PR-10 | Edit and save | Modify data → Save | Changes persist |  |
| PR-11 | Preview product | Click Preview | Frontend preview opens |  |
| PR-12 | Deactivate product | Click Deactivate | Product marked as draft |  |
| PR-13 | Activate product | Click Activate | Product marked as live |  |
| PR-14 | Delete product | Click Delete → Confirm | Product removed |  |
| PR-15 | Help button | Click "?" button | Help content displays |  |

### 3.3 Categories Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| CT-01 | Categories list loads | Navigate to Categories | All categories shown |  |
| CT-02 | Create category | Fill form → Save | Category created |  |
| CT-03 | Edit category | Click Edit → Modify → Save | Changes persist |  |
| CT-04 | Delete category | Click Delete → Confirm | Category removed |  |
| CT-05 | SEO fields work | Enter meta title/description | Fields save correctly |  |
| CT-06 | Category preview | Click Preview | Category page shows |  |

### 3.4 Bundles Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| BN-01 | Bundles list loads | Navigate to Bundles | All bundles shown |  |
| BN-02 | Create bundle | Click Add → Fill form → Save | Bundle created |  |
| BN-03 | Add products to bundle | Select products | Products added |  |
| BN-04 | Set bundle pricing | Enter discount/price | Pricing saves |  |
| BN-05 | Edit bundle | Click Edit → Modify → Save | Changes persist |  |
| BN-06 | Delete bundle | Click Delete → Confirm | Bundle removed |  |

### 3.5 Product Options Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| PO-01 | Options list loads | Navigate to Product Options | All options shown |  |
| PO-02 | Create attribute | Click Add → Enter name → Save | Attribute created |  |
| PO-03 | Add attribute values | Add values with prices | Values save with pricing |  |
| PO-04 | Edit attribute | Click Edit → Modify → Save | Changes persist |  |
| PO-05 | Delete attribute value | Click Delete on value | Value removed |  |
| PO-06 | Tier rules work | Set min tier for value | Rule enforced in frontend |  |
| PO-07 | Swatches display | Add color swatch | Swatch shows in frontend |  |

### 3.6 Custom Orders Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| CO-01 | Kanban loads | Navigate to Custom Orders | 5 columns display |  |
| CO-02 | Add custom order | Click Add → Fill form → Save | Order created in New Leads |  |
| CO-03 | **View order details** | Click "View" button | **Modal opens with details** |  |
| CO-04 | Edit order | Modify fields → Save | Changes persist |  |
| CO-05 | Drag to new stage | Drag card to Sampling | Stage updates via AJAX |  |
| CO-06 | Stage buttons | Click stage change button | Stage updates |  |
| CO-07 | Create WC order | Click "Create WC Order" | WC order modal opens |  |
| CO-08 | WC order creation | Fill WC order form → Submit | WC order created and linked |  |
| CO-09 | Financial fields | Enter production cost, total value | Commission calculates |  |
| CO-10 | Mark as invoiced | Click "Mark Invoiced" | Status updates |  |

### 3.7 Orders Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| OR-01 | Orders list loads | Navigate to Orders | WC orders displayed |  |
| OR-02 | Order details | Click on order | Details modal/page opens |  |
| OR-03 | Filter by status | Select status filter | Orders filter correctly |  |
| OR-04 | Search orders | Enter search term | Results filter |  |

### 3.8 Analytics Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| AN-01 | Analytics loads | Navigate to Analytics | Charts and data display |  |
| AN-02 | Date range filter | Change date range | Data updates |  |
| AN-03 | Export data | Click export | CSV downloads |  |

### 3.9 Commission Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| CM-01 | Commission list loads | Navigate to Commission | Records displayed |  |
| CM-02 | Filter by status | Select status filter | Records filter |  |
| CM-03 | Mark as paid | Click "Mark Paid" | Status updates |  |
| CM-04 | Bulk actions | Select multiple → Apply action | Bulk update works |  |

### 3.10 Settings Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| ST-01 | Settings loads | Navigate to Settings | All tabs display |  |
| ST-02 | General settings | Modify settings → Save | Settings persist |  |
| ST-03 | Tier settings | Modify default tiers → Save | Changes persist |  |
| ST-04 | Commission settings | Set commission rate → Save | Rate saves |  |
| ST-05 | Email settings | Configure emails → Save | Settings persist |  |
| ST-06 | API settings | View/regenerate API keys | Keys display/regenerate |  |

---

## 4. Frontend Customer Journey Tests

### 4.1 Shop/Category Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| FE-01 | Shop page loads | Navigate to /shop/ | Products display |  |
| FE-02 | Category filtering | Click category | Products filter |  |
| FE-03 | Product cards display | View product grid | Name, price, image show |  |
| FE-04 | Starting price shown | Check price display | "Starting from $X.XX" |  |

### 4.2 Product Page Tests

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| PP-01 | Product page loads | Click on product | Page loads, no errors |  |
| PP-02 | Product info displays | Check content | Name, description, price shown |  |
| PP-03 | Configure button | Click "Configure & Order" | Configurator modal opens |  |
| PP-04 | Pack tier selection | Click on tier | Tier selected, price updates |  |
| PP-05 | Option selection | Select options | Options selected, price updates |  |
| PP-06 | Price calculation | Select tier + options | Total calculates correctly |  |
| PP-07 | Add to cart enabled | After selecting tier | Button becomes clickable |  |
| PP-08 | Add to cart | Click Add to Cart | "Adding..." then success |  |
| PP-09 | Success popup | After add to cart | "Added to Cart!" popup shows |  |
| PP-10 | View Cart link | Click "View Cart" | Navigates to cart page |  |
| PP-11 | Continue shopping | Click "Continue Shopping" | Popup closes |  |
| PP-12 | Tab navigation | Click Description/Directions/Info | Content switches |  |
| PP-13 | Bundle suggestions | Check Bundle & Save section | Bundles display if applicable |  |

### 4.3 Custom Order Form Tests (Frontend)

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| CF-01 | Form page loads | Navigate to custom order page | Form displays |  |
| CF-02 | Required validation | Submit empty form | Validation errors show |  |
| CF-03 | Fill form | Enter all fields | Fields accept input |  |
| CF-04 | Submit form | Click Submit | "Submitting..." then success |  |
| CF-05 | Success message | After submit | Thank you message/redirect |  |
| CF-06 | Order in admin | Check Custom Orders | New lead appears |  |

### 4.4 Complete Purchase Flow Test

| Test ID | Test Case | Steps | Expected Result | Pass/Fail |
|---------|-----------|-------|-----------------|-----------|
| PF-01 | Browse products | Go to shop, view products | Products display |  |
| PF-02 | Select product | Click product | Product page loads |  |
| PF-03 | Configure order | Open configurator, select tier | Configuration works |  |
| PF-04 | Add to cart | Click Add to Cart | Success popup shows |  |
| PF-05 | View cart | Go to cart | Item displays correctly |  |
| PF-06 | Proceed to checkout | Click Checkout | Checkout page loads |  |
| PF-07 | Enter details | Fill billing/shipping | Fields work |  |
| PF-08 | Place order | Click Place Order | Order confirmation |  |
| PF-09 | Verify in admin | Check WC Orders | Order exists with correct data |  |

---

## 5. AJAX & API Tests

### 5.1 Admin AJAX Endpoints

| Test ID | Endpoint | Test Method | Expected | Pass/Fail |
|---------|----------|-------------|----------|-----------|
| AJ-01 | `pls_save_product` | Save new product | Returns success with base_id |  |
| AJ-02 | `pls_delete_product` | Delete product | Returns success |  |
| AJ-03 | `pls_sync_product` | Sync single product | Returns sync status |  |
| AJ-04 | `pls_get_custom_order_details` | View order modal | Returns HTML |  |
| AJ-05 | `pls_update_custom_order_status` | Drag card | Returns success |  |
| AJ-06 | `pls_create_wc_order_from_custom` | Create WC order | Returns order_id |  |

### 5.2 Frontend AJAX Endpoints

| Test ID | Endpoint | Test Method | Expected | Pass/Fail |
|---------|----------|-------------|----------|-----------|
| FA-01 | `pls_add_to_cart` | Add product to cart | Returns success + fragments |  |
| FA-02 | `pls_get_offers` | Load offers | Returns offers array |  |
| FA-03 | Custom order submit | Submit form | Returns success |  |

---

## 6. UX Helper Elements Tests

### 6.1 Tooltips & Info Icons

| Test ID | Location | Test | Expected | Pass/Fail |
|---------|----------|------|----------|-----------|
| UX-01 | Products page header | Click "?" icon | Help panel opens |  |
| UX-02 | Custom Orders header | Hover info icon | Tooltip shows workflow |  |
| UX-03 | Categories SEO fields | Hover "ⓘ" | Tooltip explains field |  |
| UX-04 | Pack tiers section | Read helper text | Text explains defaults |  |
| UX-05 | Stock management | Read checkbox label | Clear explanation |  |

### 6.2 Form Validation & Feedback

| Test ID | Location | Test | Expected | Pass/Fail |
|---------|----------|------|----------|-----------|
| UX-06 | Product name required | Leave empty → Save | Error message shows |  |
| UX-07 | Category name required | Leave empty → Save | Error message shows |  |
| UX-08 | Email validation | Enter invalid email | Validation error |  |
| UX-09 | Price validation | Enter negative price | Validation error |  |
| UX-10 | Success messages | Save successfully | Green success message |  |
| UX-11 | Error messages | Action fails | Red error message |  |

### 6.3 Loading States

| Test ID | Location | Test | Expected | Pass/Fail |
|---------|----------|------|----------|-----------|
| UX-12 | Product save | Click Save | Button shows "Saving..." |  |
| UX-13 | Add to cart | Click Add | Button shows "Adding..." |  |
| UX-14 | Custom order submit | Click Submit | Button shows "Submitting..." |  |
| UX-15 | Modal loading | Open view modal | Loading indicator shows |  |
| UX-16 | Page transitions | Navigate pages | Smooth transitions |  |

### 6.4 Modal Behaviors

| Test ID | Location | Test | Expected | Pass/Fail |
|---------|----------|------|----------|-----------|
| UX-17 | Product create modal | Click Add Product | Modal opens smoothly |  |
| UX-18 | Modal close X | Click X button | Modal closes |  |
| UX-19 | Modal backdrop click | Click outside modal | Modal closes |  |
| UX-20 | Modal escape key | Press ESC | Modal closes |  |
| UX-21 | Body scroll lock | Open modal | Background doesn't scroll |  |
| UX-22 | Custom Order view | Click View button | **Modal opens with data** |  |
| UX-23 | Configurator modal | Click Configure | Modal opens |  |

### 6.5 Navigation & Breadcrumbs

| Test ID | Location | Test | Expected | Pass/Fail |
|---------|----------|------|----------|-----------|
| UX-24 | Admin nav | Click each menu item | Correct page loads |  |
| UX-25 | Active state | View current page | Menu item highlighted |  |
| UX-26 | Stepper navigation | Click step numbers | Steps navigate |  |
| UX-27 | Back/Next buttons | Click in wizard | Navigation works |  |

### 6.6 Empty States

| Test ID | Location | Test | Expected | Pass/Fail |
|---------|----------|------|----------|-----------|
| UX-28 | Empty products | Delete all products | Helpful empty state message |  |
| UX-29 | Empty orders | No orders exist | "No orders yet" message |  |
| UX-30 | Empty search | Search with no results | "No results" message |  |

### 6.7 Confirmation Dialogs

| Test ID | Location | Test | Expected | Pass/Fail |
|---------|----------|------|----------|-----------|
| UX-31 | Delete product | Click Delete | Confirmation dialog |  |
| UX-32 | Delete category | Click Delete | Confirmation dialog |  |
| UX-33 | Cancel unsaved | Close modal with changes | Warning dialog |  |

---

## 7. Security Tests

### 7.1 Authentication & Authorization

| Test ID | Test Case | Steps | Expected | Pass/Fail |
|---------|-----------|-------|----------|-----------|
| SC-01 | Admin pages require login | Access admin page logged out | Redirect to login |  |
| SC-02 | Capability check | Non-admin tries admin action | Permission denied |  |
| SC-03 | Nonce verification | Submit form without nonce | Request rejected |  |

### 7.2 CSRF Protection

| Test ID | Test Case | Steps | Expected | Pass/Fail |
|---------|-----------|-------|----------|-----------|
| SC-04 | Admin AJAX nonce | Inspect AJAX requests | Nonce included |  |
| SC-05 | Frontend add to cart | Inspect add to cart | Nonce included |  |
| SC-06 | Invalid nonce | Send request with bad nonce | Request rejected |  |

### 7.3 Input Sanitization

| Test ID | Test Case | Steps | Expected | Pass/Fail |
|---------|-----------|-------|----------|-----------|
| SC-07 | XSS in product name | Enter `<script>alert(1)</script>` | Script escaped |  |
| SC-08 | SQL injection attempt | Enter `'; DROP TABLE--` | Query fails safely |  |
| SC-09 | HTML in description | Enter HTML tags | Tags escaped or allowed safely |  |

---

## 8. Performance Tests

### 8.1 Page Load Times

| Test ID | Page | Target | How to Measure | Pass/Fail |
|---------|------|--------|----------------|-----------|
| PF-01 | Admin Dashboard | < 3s | Browser DevTools |  |
| PF-02 | Products page | < 3s | Browser DevTools |  |
| PF-03 | Frontend product page | < 2s | Browser DevTools |  |
| PF-04 | Configurator modal | < 1s | Stopwatch |  |

### 8.2 AJAX Response Times

| Test ID | Action | Target | How to Measure | Pass/Fail |
|---------|--------|--------|----------------|-----------|
| PF-05 | Save product | < 2s | Network tab |  |
| PF-06 | Add to cart | < 1s | Network tab |  |
| PF-07 | Load order details | < 1s | Network tab |  |

---

## 9. Error Handling Tests

### 9.1 Network Errors

| Test ID | Test Case | Steps | Expected | Pass/Fail |
|---------|-----------|-------|----------|-----------|
| EH-01 | AJAX timeout | Disable network during save | Error message shown |  |
| EH-02 | Server error | Return 500 from endpoint | Error message shown |  |
| EH-03 | Retry mechanism | Error → Try again | Retry works |  |

### 9.2 Data Validation Errors

| Test ID | Test Case | Steps | Expected | Pass/Fail |
|---------|-----------|-------|----------|-----------|
| EH-04 | Invalid product ID | Request non-existent product | 404 or error message |  |
| EH-05 | Missing required field | Submit without name | Field highlighted |  |
| EH-06 | Invalid price format | Enter "abc" in price | Validation error |  |

---

## 10. Mobile Responsiveness Tests

### 10.1 Admin Interface (Tablet)

| Test ID | Page | Test | Expected | Pass/Fail |
|---------|------|------|----------|-----------|
| MO-01 | Products | View on tablet | Responsive layout |  |
| MO-02 | Kanban | View Custom Orders | Scrollable columns |  |
| MO-03 | Modals | Open modal | Full-width modal |  |

### 10.2 Frontend (Mobile)

| Test ID | Page | Test | Expected | Pass/Fail |
|---------|------|------|----------|-----------|
| MO-04 | Shop page | View on mobile | Products stack |  |
| MO-05 | Product page | View on mobile | Content stacks |  |
| MO-06 | Configurator | Open on mobile | Touch-friendly UI |  |
| MO-07 | Cart | View cart | Readable, usable |  |
| MO-08 | Checkout | Complete checkout | All fields accessible |  |

---

## Test Execution Checklist

### Pre-Deployment Verification

- [ ] All WooCommerce pages exist and work (Cart, Checkout, My Account)
- [ ] All PLS System Tests pass
- [ ] All critical path tests pass (product creation, add to cart, checkout)
- [ ] Custom Orders View button opens modal
- [ ] Add to Cart includes security nonce
- [ ] No console errors on any page
- [ ] Mobile experience is acceptable

### Sign-Off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Developer | | | |
| QA Tester | | | |
| Product Owner | | | |

---

## Known Issues Tracker

| Issue ID | Description | Severity | Status | Fix Date |
|----------|-------------|----------|--------|----------|
| ISS-001 | WooCommerce pages missing | Critical | Fixed | 2026-01-29 |
| ISS-002 | Custom Orders View modal broken | High | Fixed | 2026-01-29 |
| ISS-003 | Add to cart missing CSRF nonce | High | Fixed | 2026-01-29 |
| ISS-004 | View Cart link wrong URL | Medium | Fixed | 2026-01-29 |
| ISS-005 | Product save silent failure | Medium | Investigating | |

---

## Appendix: Browser Testing Matrix

| Browser | Version | Desktop | Mobile | Status |
|---------|---------|---------|--------|--------|
| Chrome | Latest | Test | Test | |
| Firefox | Latest | Test | Test | |
| Safari | Latest | Test | Test | |
| Edge | Latest | Test | N/A | |
| iOS Safari | Latest | N/A | Test | |
| Android Chrome | Latest | N/A | Test | |

---

*Document maintained by development team. Update after each release.*
