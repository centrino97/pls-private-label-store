# PLS v5.0.0 - Testing Execution Summary

**Date:** January 29, 2026  
**Purpose:** Complete manual testing execution guide

---

## 🎯 Complete Testing Workflow

This document provides the **exact steps** to test the entire production workflow from scratch.

---

## 📋 PRE-FLIGHT CHECKLIST

Before starting, ensure:
- [ ] WordPress 6.5+ installed and running
- [ ] WooCommerce active and configured
- [ ] PLS plugin active (version 5.0.0)
- [ ] WooCommerce pages created:
  - [ ] Cart page exists (`/cart/`)
  - [ ] Checkout page exists (`/checkout/`)
  - [ ] My Account page exists (`/my-account/`)
- [ ] Browser DevTools ready (F12)
- [ ] WordPress error log accessible

**To create WooCommerce pages:**
1. Go to: WooCommerce → Settings → Advanced → Page Setup
2. Click "Create pages" button
3. Verify pages created

---

## 🚀 COMPLETE TESTING WORKFLOW

### PART 1: Setup Production Options (15 minutes)

#### 1.1 Create Categories
```
Location: PLS → Categories
Action: Create "Test Serums" category
Expected: Category appears in list
```

#### 1.2 Create Ingredients  
```
Location: PLS → Ingredients
Action: Create 5 ingredients:
  - Test Vitamin C (Tier 1)
  - Test Hyaluronic Acid (Tier 1)
  - Test Retinol (Tier 3+)
  - Test Niacinamide (Tier 1)
  - Test Peptides (Tier 2+)
Expected: All ingredients appear in table
```

#### 1.3 Create Product Options
```
Location: PLS → Product Options
Action: Create 3 options with values:

Option 1: Package Type
  Values:
    - Airless Pump (Tier 1, $0.00)
    - Dropper Bottle (Tier 1, $0.00)
    - Custom Bottle (Tier 4+, $2.50)

Option 2: Package Color
  Values:
    - Clear (Tier 1, $0.00)
    - Amber (Tier 1, $0.00)
    - Custom Color (Tier 3+, $1.00)

Option 3: Package Cap
  Values:
    - Standard Cap (Tier 1, $0.00)
    - Premium Cap (Tier 2+, $0.50)

Expected: All options and values created
```

---

### PART 2: Create Complete Product (20 minutes)

#### 2.1 Product Basics
```
Location: PLS → Products → Add Product
Fill in:
  Name: Test Serum - Complete Workflow
  Status: Live
  Category: Test Serums
  Short Description: Complete test product for manual testing
  Long Description: [Full description text]
  Directions: Apply to cleansed skin morning and evening
  Benefits: 
    - Hydrates instantly
    - Boosts elasticity
    - Brightens skin tone
Click: Save Product (draft)
Expected: Product saves, modal stays open
```

#### 2.2 Add Ingredients
```
In product modal:
1. Scroll to Ingredients section
2. Search and select:
   - Test Vitamin C
   - Test Hyaluronic Acid
   - Test Niacinamide
3. Mark Test Vitamin C as "Key Ingredient" (click star)
Expected: Ingredients appear as chips
Expected: Key ingredient shows in separate section
```

#### 2.3 Configure Pack Tiers
```
In product modal, Pack Tiers section:
Enable and configure:

Tier 1 - Starter:
  Enabled: ✓
  Units: 50
  Price: $15.00
  Sort: 1

Tier 2 - Growth:
  Enabled: ✓
  Units: 100
  Price: $25.00
  Sort: 2

Tier 3 - Brand Entry:
  Enabled: ✓
  Units: 250
  Price: $55.00
  Sort: 3

Expected: All tiers save correctly
Expected: Price calculator shows correct totals
```

#### 2.4 Add Product Options
```
In product modal, Product Options section:
1. Click "Select product option"
2. Select "Package Type"
3. Select values: Airless Pump, Dropper Bottle
4. Click "Select product option" again
5. Select "Package Color"
6. Select values: Clear, Amber
7. Click "Select product option" again
8. Select "Package Cap"
9. Select value: Standard Cap

Expected: All options appear in list
Expected: Tier badges show correctly
```

#### 2.5 Set Images
```
In product modal, Product Images section:
1. Click "Set Featured Image"
2. Upload/select test image
3. Click "Add to Gallery"
4. Add 2-3 more images
Expected: Featured image shows
Expected: Gallery images appear
```

#### 2.6 Final Save and Sync
```
1. Click "Save Product" button
2. EXPECTED: Success message appears
3. EXPECTED: Product appears in product list
4. EXPECTED: Sync status shows "Syncing..." then "Synced & Active"
5. Wait for sync to complete (watch status)
6. Click "View" on product
7. EXPECTED: All data displays correctly
```

#### 2.7 Verify WooCommerce Sync
```
1. Go to: Products → All Products (WooCommerce)
2. Find "Test Serum - Complete Workflow"
3. Click to edit
4. Verify:
   - Product type: Variable product ✓
   - Status: Published ✓
   - Variations: 3 variations exist ✓
   - Check Variation 1:
     * Price: $15.00 ✓
     * Meta: _pls_units = 50 ✓
     * Attributes: Package Type, Color, Cap set ✓
   - Check Variation 2:
     * Price: $25.00 ✓
     * Meta: _pls_units = 100 ✓
   - Check Variation 3:
     * Price: $55.00 ✓
     * Meta: _pls_units = 250 ✓
```

---

### PART 3: Frontend Product Testing (15 minutes)

#### 3.1 View Product Page
```
1. Go to frontend shop/product archive
2. Find "Test Serum - Complete Workflow"
3. Click to view product
4. EXPECTED: Product page loads without errors
5. EXPECTED: Images display correctly
6. EXPECTED: Pack tier cards show (3 tiers)
7. EXPECTED: Product options visible
8. EXPECTED: Price calculator visible
```

#### 3.2 Test Configurator
```
1. Click "Configure & Order" button
2. EXPECTED: Configurator modal opens
3. Select Tier 1 - Starter (50 units)
4. EXPECTED: Price updates in real-time
5. EXPECTED: "Add to Cart" button becomes enabled
6. Select Package Type: Airless Pump
7. Select Package Color: Clear
8. Select Package Cap: Standard Cap
9. EXPECTED: Total price calculates correctly
10. EXPECTED: Price per unit shows correctly
```

#### 3.3 Test Add-to-Cart (CSRF Protection) ⭐ v5.0.0 FIX
```
1. Open Browser DevTools → Network tab
2. With product configured, click "Add to Cart"
3. Check Network request:
   - Request URL: admin-ajax.php ✓
   - Request Method: POST ✓
   - Form Data includes:
     * action: pls_add_to_cart ✓
     * product_id: [number] ✓
     * variation_id: [number] ✓
     * quantity: 1 ✓
     * nonce: [string] ✓ ⭐ CRITICAL
   - Status: 200 ✓
4. Check Console tab:
   - No JavaScript errors ✓
   - No 403 CSRF errors ✓
5. EXPECTED: Success message: "✓ Added to cart successfully!"
6. EXPECTED: Bundle popup appears after 500ms
7. EXPECTED: Configurator modal closes
```

#### 3.4 Test View Cart Link ⭐ v5.0.0 FIX
```
1. In bundle popup, inspect "View Cart" link
2. Check link href:
   - EXPECTED: Points to WooCommerce cart URL (e.g., /cart/)
   - EXPECTED: NOT hardcoded /cart/ (should be dynamic)
3. Click "View Cart" button
4. EXPECTED: Redirects to cart page
5. EXPECTED: No 404 errors
6. EXPECTED: Cart shows added product
7. EXPECTED: Product shows correct:
   - Variation (pack tier)
   - Options selected
   - Price
   - Quantity: 1
```

#### 3.5 Test Cart and Checkout
```
1. On cart page, verify product details
2. Click "Proceed to Checkout"
3. EXPECTED: Checkout page loads
4. EXPECTED: Product appears in checkout
5. EXPECTED: Order total calculates correctly
6. Fill in test checkout details
7. Place test order
8. EXPECTED: Order completes successfully
```

---

### PART 4: Create WooCommerce Order (10 minutes)

#### 4.1 Complete Order
```
1. On checkout, fill in:
   - Email: test@example.com
   - Name: Test Customer
   - Address: 123 Test St
   - City: Test City
   - Postcode: 12345
2. Select payment method
3. Click "Place Order"
4. EXPECTED: Order completes
5. EXPECTED: Order number displayed
```

#### 4.2 Verify Order in Admin
```
1. Go to: PLS → Orders
2. Find test order
3. Click to view
4. EXPECTED: Order shows PLS product
5. EXPECTED: Order item meta includes PLS data
6. EXPECTED: Variation ID matches
7. EXPECTED: Pack tier info present
```

#### 4.3 Verify Commission
```
1. Go to: PLS → Commissions
2. Find commission for test order
3. EXPECTED: Commission created automatically
4. EXPECTED: Commission amount calculated correctly
```

---

### PART 5: Custom Order Management (15 minutes)

#### 5.1 Create Custom Order (Admin)
```
1. Go to: PLS → Custom Orders
2. Click "Add Custom Order"
3. Fill form:
   - Contact Name: Test Custom Customer
   - Contact Email: custom@example.com
   - Phone: 1234567890
   - Company: Test Custom Company
   - Category: Test Serums
   - Quantity: 500
   - Budget: $5000
   - Timeline: 4-6 weeks
   - Message: Test custom order for workflow verification
4. Click "Create Order"
5. EXPECTED: Order appears in "New Leads" column
```

#### 5.2 Test View Button ⭐ v5.0.0 FIX
```
1. Click "View" button on custom order
2. EXPECTED: Modal opens IMMEDIATELY (no delay) ⭐
3. Open DevTools → Elements tab
4. Inspect #pls-order-detail-modal:
   - EXPECTED: Has class "is-active" ⭐
   - EXPECTED: CSS shows "display: flex" (not "display: block") ⭐
   - EXPECTED: Body has class "pls-modal-open"
5. Test close:
   - Click X button → EXPECTED: Modal closes
   - Click backdrop → EXPECTED: Modal closes
```

#### 5.3 Edit Custom Order
```
1. In modal, edit fields:
   - Change Contact Name
   - Change Budget
   - Change Status to "Sampling"
2. Click "Save All Changes"
3. EXPECTED: Changes save
4. EXPECTED: Modal closes
5. EXPECTED: Page reloads with updates
```

#### 5.4 Move Order Between Stages
```
1. Drag order card from "New Leads" to "Sampling"
2. EXPECTED: Order moves smoothly
3. EXPECTED: Order appears in "Sampling" column
4. EXPECTED: Column counts update
```

#### 5.5 Add Sampling Info
```
1. Open order modal
2. Scroll to Sampling section
3. Fill in:
   - Sample Status: Sent
   - Sample Cost: $25.00
   - Sample Sent Date: Today
   - Sample Tracking: TEST123456
   - Sample Feedback: Approved
4. Click "Save All Changes"
5. EXPECTED: Sampling data saves
```

#### 5.6 Convert to WooCommerce Order
```
1. In modal, click "Create WooCommerce Order"
2. EXPECTED: Create WC Order modal opens
3. Select Status: "Pending Payment"
4. Add product:
   - Product ID: [Test Serum product ID]
   - Quantity: 1
5. Check "Include Sample Cost"
6. Add custom line: Setup Fee, $100.00
7. Add notes: "Test conversion"
8. Click "Create Order"
9. EXPECTED: WooCommerce order created
10. EXPECTED: Custom order links to WC order
11. EXPECTED: "View WC Order" button appears
```

---

### PART 6: Frontend Custom Order Form (10 minutes)

#### 6.1 Setup Custom Order Page
```
OPTION A - Shortcode Method:
1. Pages → Add New
2. Title: "Custom Order Request"
3. Content: [pls_custom_order_form]
4. Publish
5. Visit page on frontend

OPTION B - Page Slug Method:
1. Create page with slug "custom-order"
2. Add shortcode: [pls_custom_order_form]
3. Publish
4. Visit /custom-order/
```

#### 6.2 Submit Custom Order
```
1. On custom order form page, fill in:
   - Contact Name: Frontend Test Customer
   - Contact Email: frontend@example.com
   - Phone: 9876543210
   - Company: Frontend Test Company
   - Category: Test Serums
   - Quantity: 1000
   - Budget: $10000
   - Timeline: 6-8 weeks
   - Message: Test from frontend form
2. Click "Submit Request"
3. EXPECTED: Form submits successfully
4. EXPECTED: Success message appears
5. EXPECTED: Form resets OR redirects
```

#### 6.3 Verify Custom Order Created
```
1. Go to: PLS → Custom Orders
2. Find "Frontend Test Customer" order
3. EXPECTED: Order in "New Leads" column
4. EXPECTED: All information matches submission
```

---

## ✅ v5.0.0 CRITICAL FEATURES VERIFICATION

### Feature 1: CSRF Protection ✅
```
Test: Add product to cart
Check: Network tab → Request includes nonce parameter
Result: [ ] PASS [ ] FAIL
```

### Feature 2: View Cart Link ✅
```
Test: Click "View Cart" after adding product
Check: Redirects to correct cart URL (not 404)
Result: [ ] PASS [ ] FAIL
```

### Feature 3: Custom Orders Modal ✅
```
Test: Click "View" button on custom order
Check: Modal opens immediately with .is-active class
Result: [ ] PASS [ ] FAIL
```

---

## 📝 TESTING LOG

```
Date: ___________
Tester: ___________
Environment: WordPress ___, WooCommerce ___, PHP ___

[ ] Part 1: Production Options Setup - Complete
[ ] Part 2: Product Creation - Complete
[ ] Part 3: Frontend Testing - Complete
[ ] Part 4: WooCommerce Order - Complete
[ ] Part 5: Custom Order Management - Complete
[ ] Part 6: Frontend Custom Order Form - Complete

v5.0.0 Features:
[ ] CSRF Protection - Verified
[ ] View Cart Link - Verified
[ ] Custom Orders Modal - Verified

Issues Found:
1. _________________________________
2. _________________________________
3. _________________________________

Overall Status: [ ] PASS [ ] FAIL [ ] NEEDS FIXES
```

---

## 🎯 Expected Results Summary

### All Tests Should Show:
- ✅ No JavaScript console errors
- ✅ No PHP errors in error log
- ✅ All AJAX requests return 200 status
- ✅ All modals open/close correctly
- ✅ All forms submit successfully
- ✅ All data syncs correctly
- ✅ All links work (no 404s)

---

**Follow this guide step-by-step to test the complete production workflow!**

For detailed explanations, see: `MANUAL_TESTING_GUIDE.md`  
For quick testing, see: `QUICK_TEST_CHECKLIST.md`
