# PLS v5.0.0 Release - Complete Testing Protocol

## 🎯 RELEASE SUMMARY

**Version:** 5.0.0  
**Release Type:** Production-Ready Major Release  
**Date:** January 29, 2026  
**Status:** Ready for UUPD Update Testing

### What Was Released

✅ **Security Fixes:**
- Added CSRF protection to frontend add-to-cart AJAX handler
- Secure nonce verification for all cart operations
- Fixed "View Cart" link to use dynamic WooCommerce cart URL

✅ **Critical Bug Fixes:**
- Fixed Custom Orders "View" button modal display (CSS class toggle)
- Fixed cart URL resolution with proper fallback chain
- All modals now use consistent `.is-active` class pattern

✅ **Documentation:**
- Comprehensive System Audit Report (`docs/system-audit-report.md`)
- Complete Test Plan with 100+ test cases (`docs/comprehensive-test-plan.md`)
- UX Helper Elements verification checklist

---

## 📦 UUPD PROTOCOL SETUP

### What is UUPD?
UUPD (Update Protocol) allows WordPress plugins to update automatically from GitHub releases without WordPress.org.

### How It Works:
1. Plugin checks: `https://raw.githubusercontent.com/centrino97/pls-private-label-store/main/uupd/index.json`
2. Compares local version (4.9.99) vs remote version (5.0.0)
3. Shows "Update Available" in WordPress Plugins page
4. One-click update downloads ZIP from GitHub release

### Files Updated for UUPD:
- ✅ `uupd/index.json` - Version 5.0.0, download URL, changelog
- ✅ `pls-private-label-store.php` - Version constant updated
- ✅ `readme.txt` - Stable tag updated

### Git Tag Created:
- ✅ Tag: `v5.0.0` (annotated with release notes)
- ✅ Commit: `f0f5cf2` - "Release v5.0.0 - Production-ready with security fixes"

---

## 🧪 COMPLETE TESTING PROTOCOL

### PHASE 1: Pre-Update Verification (Current State)

**Test Current Version:**
```
1. Go to WordPress Admin → Plugins
2. Verify current version shows: "4.9.99"
3. Check that plugin is active and working
4. Note any current issues to compare after update
```

**Verify UUPD Endpoint:**
```
1. Open browser: https://raw.githubusercontent.com/centrino97/pls-private-label-store/main/uupd/index.json
2. Verify JSON shows version: "5.0.0"
3. Verify download_url points to: https://github.com/centrino97/pls-private-label-store/releases/download/v5.0.0/pls-private-label-store.zip
4. Verify changelog includes 5.0.0 entry
```

---

### PHASE 2: Update Process Testing

**Step 1: Trigger Update Detection**
```
1. Go to WordPress Admin → Plugins
2. Click "Check for updates" or refresh page
3. EXPECTED: Plugin should show "Update Available" badge
4. EXPECTED: Version should show "5.0.0" available
```

**Step 2: Perform Update**
```
1. Click "Update Now" button
2. Wait for update to complete (should download from GitHub release)
3. EXPECTED: Success message "Plugin updated successfully"
4. EXPECTED: Version now shows "5.0.0" in plugin list
```

**Step 3: Verify Update Success**
```
1. Check plugin header: Should show "Version: 5.0.0"
2. Check PHP constant: PLS_PLS_VERSION should be '5.0.0'
3. Check readme.txt: Stable tag should be "5.0.0"
4. Verify no PHP errors in error log
```

---

### PHASE 3: Critical Feature Testing

#### 3.1 Security Fixes Verification

**Test CSRF Protection:**
```
1. Open browser DevTools → Network tab
2. Go to frontend product page
3. Configure product and click "Add to Cart"
4. Check Network request:
   - EXPECTED: Request includes "nonce" parameter
   - EXPECTED: Request includes "action: pls_add_to_cart"
   - EXPECTED: Server validates nonce (no 403 errors)
```

**Test Cart URL Fix:**
```
1. Add product to cart from frontend
2. Click "View Cart" in success popup
3. EXPECTED: Redirects to correct WooCommerce cart page (/cart/)
4. EXPECTED: No 404 errors
5. EXPECTED: Cart shows the added product
```

#### 3.2 Custom Orders Modal Fix

**Test "View" Button:**
```
1. Go to: WordPress Admin → PLS → Custom Orders
2. Click "View" button on any custom order
3. EXPECTED: Modal opens immediately (no delay)
4. EXPECTED: Modal shows order details correctly
5. EXPECTED: Modal can be closed with X button or backdrop click
```

**Test Modal CSS:**
```
1. Open Custom Orders modal
2. Inspect element: #pls-order-detail-modal
3. EXPECTED: Has class "is-active"
4. EXPECTED: CSS shows "display: flex" (not "display: block")
5. EXPECTED: Body has class "pls-modal-open"
```

#### 3.3 Frontend Customer Journey

**Complete Order Flow:**
```
1. Go to frontend shop page
2. Click on a product
3. Click "Configure & Order" button
4. Select pack tier (e.g., Tier 1 - 50 units)
5. Select product options (color, cap, etc.)
6. Verify price calculator updates correctly
7. Click "Add to Cart"
8. EXPECTED: Success popup appears
9. Click "View Cart" in popup
10. EXPECTED: Redirects to cart page
11. Verify product in cart with correct options
12. Proceed to checkout
13. EXPECTED: Checkout page loads (no 404)
14. Complete test order
```

**Test Product Configurator:**
```
1. Open product page
2. Click "Configure & Order"
3. EXPECTED: Modal opens with configurator
4. Select different pack tiers
5. EXPECTED: Price updates in real-time
6. Select product options
7. EXPECTED: Price impact shows correctly
8. EXPECTED: Total price calculates accurately
```

---

### PHASE 4: Admin Interface Testing

#### 4.1 Product Management

**Create New Product:**
```
1. Go to: PLS → Products
2. Click "Add Product"
3. Fill in all required fields:
   - Name, Description, SKU
   - Ingredients, Pack Tiers
   - Product Options
4. Click "Save Product"
5. EXPECTED: Product saves successfully
6. EXPECTED: Product appears in list
7. EXPECTED: Syncs to WooCommerce automatically
```

**Edit Existing Product:**
```
1. Click "Edit" on any product
2. Modify product details
3. Click "Save"
4. EXPECTED: Changes save correctly
5. EXPECTED: WooCommerce product updates
```

**View Product Details:**
```
1. Click "View" on any product
2. EXPECTED: Modal opens with all product info
3. EXPECTED: All sections display correctly
```

#### 4.2 Custom Orders Management

**Create Custom Order:**
```
1. Go to: PLS → Custom Orders
2. Click "Add Custom Order"
3. Fill in all fields
4. Click "Save"
5. EXPECTED: Order appears in Kanban board
6. EXPECTED: Order is in "New Leads" stage
```

**View Custom Order:**
```
1. Click "View" on custom order
2. EXPECTED: Modal opens immediately
3. EXPECTED: All order details display
4. EXPECTED: Can edit and save changes
```

**Move Order Between Stages:**
```
1. Drag order card to different stage
2. OR click "Next Stage" / "Prev Stage"
3. EXPECTED: Order moves to new stage
4. EXPECTED: Status updates in database
```

**Convert to WooCommerce Order:**
```
1. Open custom order modal
2. Click "Create WooCommerce Order"
3. Select order status
4. Click "Create Order"
5. EXPECTED: WooCommerce order created
6. EXPECTED: Custom order links to WC order
```

#### 4.3 System Test Page

**Run All Tests:**
```
1. Go to: PLS → System Test
2. Click "Run All Tests"
3. EXPECTED: All tests pass (green checkmarks)
4. EXPECTED: No critical failures
5. Review test results for any warnings
```

**Test Categories to Verify:**
- ✅ Core Functionality
- ✅ WooCommerce Sync
- ✅ Data Management
- ✅ Orders & Commissions
- ✅ Admin Interface
- ✅ Frontend Display
- ✅ AJAX Endpoints

---

### PHASE 5: WooCommerce Integration

**Verify WooCommerce Pages:**
```
CRITICAL: These pages MUST exist:
1. Cart: /cart/ (should have [woocommerce_cart] shortcode)
2. Checkout: /checkout/ (should have [woocommerce_checkout] shortcode)
3. My Account: /my-account/ (should have [woocommerce_my_account] shortcode)

If missing:
1. Go to: WooCommerce → Settings → Advanced → Page Setup
2. Click "Create pages" button
3. Verify pages created and assigned
```

**Test Product Sync:**
```
1. Create/edit product in PLS admin
2. EXPECTED: Product automatically syncs to WooCommerce
3. Go to: Products → All Products (WooCommerce)
4. EXPECTED: Product appears with correct variations
5. EXPECTED: Variations have correct prices
6. EXPECTED: Product options sync as attributes
```

**Test Order Processing:**
```
1. Create test order from frontend
2. Complete checkout
3. Go to: WooCommerce → Orders
4. EXPECTED: Order appears with PLS product
5. EXPECTED: Order item meta includes PLS data
6. EXPECTED: Commission created automatically (if order completed)
```

---

### PHASE 6: Error Handling & Edge Cases

**Test Error Scenarios:**
```
1. Try to add product to cart without selecting pack tier
   EXPECTED: Validation error message

2. Try to save product without required fields
   EXPECTED: Validation errors shown

3. Try to delete product that has orders
   EXPECTED: Warning or prevention

4. Test with slow network (throttle in DevTools)
   EXPECTED: Loading states show, no broken UI

5. Test with JavaScript disabled
   EXPECTED: Graceful degradation (forms still work)
```

**Test Empty States:**
```
1. Delete all products, then view Products page
   EXPECTED: Shows "No products" message with "Add Product" button

2. Delete all custom orders, then view Custom Orders
   EXPECTED: Shows empty Kanban board with "Add Custom Order" button

3. View Revenue page with no orders
   EXPECTED: Shows $0 totals, empty charts
```

---

### PHASE 7: Performance & UX

**Test Loading States:**
```
1. Open product modal
   EXPECTED: Shows loading spinner while data loads

2. Save product
   EXPECTED: Shows "Saving..." feedback

3. Run system tests
   EXPECTED: Shows progress indicator

4. Generate sample data
   EXPECTED: Shows progress modal with steps
```

**Test Modal Behavior:**
```
1. Open any modal
   EXPECTED: Body scroll locked
   EXPECTED: ESC key closes modal
   EXPECTED: Backdrop click closes modal
   EXPECTED: Focus trapped inside modal

2. Close modal
   EXPECTED: Body scroll restored
   EXPECTED: Focus returns to trigger element
```

**Test Responsive Design:**
```
1. Test on mobile viewport (375px width)
   EXPECTED: All modals fullscreen
   EXPECTED: Tables scroll horizontally
   EXPECTED: Buttons touch-friendly size

2. Test on tablet viewport (768px width)
   EXPECTED: Layout adapts correctly
   EXPECTED: No horizontal scroll

3. Test on desktop (1920px width)
   EXPECTED: Optimal layout with proper spacing
```

---

## ✅ SUCCESS CRITERIA CHECKLIST

### Critical (Must Pass):
- [ ] Update from 4.9.99 to 5.0.0 works via UUPD
- [ ] Version shows 5.0.0 after update
- [ ] Custom Orders "View" button opens modal correctly
- [ ] Frontend "Add to Cart" works with CSRF protection
- [ ] "View Cart" link redirects to correct cart page
- [ ] No PHP errors in error log
- [ ] No JavaScript console errors
- [ ] WooCommerce pages exist (Cart, Checkout, My Account)

### High Priority (Should Pass):
- [ ] All system tests pass
- [ ] Product creation/editing works
- [ ] Custom order management works
- [ ] Product sync to WooCommerce works
- [ ] Frontend configurator works
- [ ] Order flow completes successfully

### Medium Priority (Nice to Have):
- [ ] All modals work correctly
- [ ] Loading states show properly
- [ ] Error messages are clear
- [ ] Responsive design works on mobile
- [ ] Performance is acceptable (< 2s page load)

---

## 🐛 KNOWN ISSUES & WORKAROUNDS

### Issue 1: WooCommerce Pages Missing
**Symptom:** 404 errors on /cart/, /checkout/, /my-account/  
**Fix:** Create pages in WooCommerce → Settings → Advanced → Page Setup  
**Status:** User action required (not a plugin bug)

### Issue 2: Product Save Silent Failure (If Still Exists)
**Symptom:** Product doesn't appear after saving  
**Workaround:** Check browser console for errors, verify all required fields filled  
**Status:** Under investigation

---

## 📝 TESTING LOG TEMPLATE

```
Date: ___________
Tester: ___________
Environment: WordPress ___, WooCommerce ___, PHP ___

[ ] Pre-Update: Current version verified (4.9.99)
[ ] UUPD Endpoint: JSON verified (5.0.0)
[ ] Update Process: Update completed successfully
[ ] Version Check: Plugin shows 5.0.0
[ ] Security: CSRF protection working
[ ] Cart URL: View Cart link works
[ ] Custom Orders: View button works
[ ] Product Creation: Works correctly
[ ] Product Sync: WooCommerce sync works
[ ] Frontend Flow: Complete order flow works
[ ] System Tests: All tests pass
[ ] Error Handling: Errors handled gracefully
[ ] Performance: Acceptable load times
[ ] Mobile: Responsive design works

Issues Found:
1. _________________________________
2. _________________________________
3. _________________________________

Overall Status: [ ] PASS [ ] FAIL [ ] NEEDS FIXES
```

---

## 🚀 NEXT STEPS AFTER TESTING

1. **If All Tests Pass:**
   - Mark release as production-ready
   - Update documentation with test results
   - Deploy to production sites

2. **If Issues Found:**
   - Document all issues in detail
   - Prioritize fixes (Critical → High → Medium)
   - Create fix commits and new release

3. **If Update Fails:**
   - Check GitHub release exists: https://github.com/centrino97/pls-private-label-store/releases/tag/v5.0.0
   - Verify ZIP file is downloadable
   - Check UUPD JSON is accessible
   - Review WordPress error logs

---

## 📞 SUPPORT & TROUBLESHOOTING

**If update doesn't appear:**
1. Clear WordPress cache
2. Clear browser cache
3. Check UUPD JSON manually: https://raw.githubusercontent.com/centrino97/pls-private-label-store/main/uupd/index.json
4. Verify GitHub release exists

**If update fails:**
1. Check WordPress error log
2. Check PHP error log
3. Verify file permissions
4. Try manual ZIP upload

**If features don't work:**
1. Check browser console for JavaScript errors
2. Check Network tab for failed AJAX requests
3. Verify WooCommerce is active and configured
4. Run System Test page for diagnostics

---

**END OF TESTING PROTOCOL**

Please test thoroughly and report all findings. We want 100% success rate! 🎯
