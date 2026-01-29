# PLS v5.0.0 - Complete Manual Testing Guide

**Date:** January 29, 2026  
**Purpose:** Step-by-step manual testing of entire production workflow

---

## 🎯 Testing Scope

This guide tests:
1. ✅ Complete product creation workflow
2. ✅ Production options setup
3. ✅ Creating orders with products
4. ✅ Custom orders creation and management
5. ✅ All v5.0.0 fixes and features

---

## 📋 PRE-TEST CHECKLIST

Before starting, verify:
- [ ] WordPress is running (6.5+)
- [ ] WooCommerce is active and configured
- [ ] PLS plugin is active (version 5.0.0)
- [ ] WooCommerce pages exist: Cart, Checkout, My Account
- [ ] Browser DevTools open (Network tab, Console tab)
- [ ] Error logging enabled (check WordPress error log)

---

## PHASE 1: Production Options Setup

### Step 1.1: Create Product Categories
```
1. Go to: WordPress Admin → PLS → Categories
2. Click "Add Category"
3. Create category: "Test Serums"
   - Name: Test Serums
   - Description: Testing category for manual testing
   - Meta Title: Test Serums – Private Label
   - Meta Description: Test category for PLS v5.0.0
4. Click "Save Category"
5. EXPECTED: Category appears in list
```

### Step 1.2: Create Ingredients
```
1. Go to: WordPress Admin → PLS → Ingredients
2. Click "Add Ingredient"
3. Create ingredient: "Test Vitamin C"
   - Name: Test Vitamin C
   - Short Description: Antioxidant brightener
   - Is Active: ✓ (checked)
   - Min Tier Level: 1
4. Click "Save"
5. EXPECTED: Ingredient appears in table

6. Repeat for 3-5 more ingredients:
   - Test Hyaluronic Acid (Tier 1)
   - Test Retinol (Tier 3+)
   - Test Niacinamide (Tier 1)
   - Test Peptides (Tier 2+)
```

### Step 1.3: Create Product Options
```
1. Go to: WordPress Admin → PLS → Product Options
2. Click "Add Product Option"

3. Create "Package Type" option:
   - Label: Package Type
   - Type: Select
   - Default Min Tier: 1
   - Click "Save"
   
4. Add values to Package Type:
   - Click "Manage Values" on Package Type row
   - Add values:
     * Airless Pump (Tier 1, Price: $0.00)
     * Dropper Bottle (Tier 1, Price: $0.00)
     * Custom Bottle (Tier 4+, Price: $2.50)
   - Click "Save Values"
   - EXPECTED: Values appear in dropdown

5. Create "Package Color" option:
   - Label: Package Color
   - Type: Select
   - Default Min Tier: 1
   - Add values:
     * Clear (Tier 1, Price: $0.00)
     * Amber (Tier 1, Price: $0.00)
     * Custom Color (Tier 3+, Price: $1.00)
   - EXPECTED: Option and values created

6. Create "Package Cap" option:
   - Label: Package Cap
   - Type: Select
   - Default Min Tier: 1
   - Add values:
     * Standard Cap (Tier 1, Price: $0.00)
     * Premium Cap (Tier 2+, Price: $0.50)
   - EXPECTED: All options ready
```

---

## PHASE 2: Complete Product Creation

### Step 2.1: Create Base Product
```
1. Go to: WordPress Admin → PLS → Products
2. Click "Add Product"
3. Fill in Product Basics:
   - Name: Test Serum - Complete Workflow
   - Slug: test-serum-complete-workflow (auto-generated)
   - Status: Live
   - Category: Test Serums (select from dropdown)
   - Short Description: A complete test product for manual testing workflow
   - Long Description: This is a comprehensive test product created to verify the entire PLS v5.0.0 workflow including product creation, options, orders, and custom orders.
   - Directions: Apply to cleansed skin morning and evening. Press gently until absorbed.
   - Benefits: 
     * Hydrates instantly
     * Boosts elasticity
     * Brightens skin tone
   - EXPECTED: All fields save correctly
```

### Step 2.2: Add Ingredients
```
1. In product modal, scroll to "Ingredients" section
2. Click ingredient search field
3. Search and select:
   - Test Vitamin C
   - Test Hyaluronic Acid
   - Test Niacinamide
4. Mark 1-2 as "Key Ingredients" (click star icon)
5. EXPECTED: Selected ingredients appear as chips
6. EXPECTED: Key ingredients show in separate section
```

### Step 2.3: Configure Pack Tiers
```
1. Scroll to "Pack Tiers" section
2. Enable and configure tiers:

   Tier 1 - Starter (50 units):
   - Enabled: ✓
   - Units: 50
   - Price: $15.00
   - Sort Order: 1

   Tier 2 - Growth (100 units):
   - Enabled: ✓
   - Units: 100
   - Price: $25.00
   - Sort Order: 2

   Tier 3 - Brand Entry (250 units):
   - Enabled: ✓
   - Units: 250
   - Price: $55.00
   - Sort Order: 3

3. EXPECTED: All tiers save correctly
4. EXPECTED: Price calculator shows correct totals
```

### Step 2.4: Add Product Options
```
1. Scroll to "Product Options" section
2. Click "Select product option"
3. Select "Package Type"
4. Select values: Airless Pump, Dropper Bottle (both Tier 1)
5. Click "Select product option" again
6. Select "Package Color"
7. Select values: Clear, Amber (both Tier 1)
8. Click "Select product option" again
9. Select "Package Cap"
10. Select values: Standard Cap (Tier 1)
11. EXPECTED: All options appear in list
12. EXPECTED: Tier badges show correctly
```

### Step 2.5: Set Product Images
```
1. Scroll to "Product Images" section
2. Click "Set Featured Image"
3. Upload/select a test image
4. Click "Add to Gallery" and add 2-3 more images
5. EXPECTED: Featured image shows in header
6. EXPECTED: Gallery images appear as thumbnails
```

### Step 2.6: Save and Sync Product
```
1. Click "Save Product" button
2. EXPECTED: Success message appears
3. EXPECTED: Product appears in product list
4. EXPECTED: Sync status shows "Syncing..." then "Synced & Active"
5. Click "View" on product to verify:
   - EXPECTED: All data displays correctly
   - EXPECTED: Pack tiers show correctly
   - EXPECTED: Options show correctly
```

### Step 2.7: Verify WooCommerce Sync
```
1. Go to: WordPress Admin → Products → All Products (WooCommerce)
2. Find "Test Serum - Complete Workflow"
3. Click to edit
4. Verify:
   - EXPECTED: Product type is "Variable product"
   - EXPECTED: Status is "Published"
   - EXPECTED: Has 3 variations (one per pack tier)
   - EXPECTED: Variations have correct prices
   - EXPECTED: Attributes show: Package Type, Package Color, Package Cap
5. Check one variation:
   - EXPECTED: Variation has correct price ($15, $25, or $55)
   - EXPECTED: Variation has `_pls_units` meta (50, 100, or 250)
   - EXPECTED: Variation attributes set correctly
```

---

## PHASE 3: Frontend Product Testing

### Step 3.1: View Product on Frontend
```
1. Go to frontend shop page (or product archive)
2. Find "Test Serum - Complete Workflow"
3. Click to view product page
4. EXPECTED: Product page loads without errors
5. EXPECTED: Product images display
6. EXPECTED: Pack tier cards show (3 tiers)
7. EXPECTED: Product options show (Package Type, Color, Cap)
8. EXPECTED: Price calculator visible
```

### Step 3.2: Test Product Configurator
```
1. On product page, click "Configure & Order" button
2. EXPECTED: Configurator modal opens
3. Select Tier 1 - Starter (50 units)
4. EXPECTED: Price updates in real-time
5. EXPECTED: "Add to Cart" button becomes enabled
6. Select Package Type: Airless Pump
7. EXPECTED: Price updates (if option has price)
8. Select Package Color: Clear
9. Select Package Cap: Standard Cap
10. EXPECTED: Total price calculates correctly
11. EXPECTED: Price per unit shows correctly
```

### Step 3.3: Test Add to Cart (CSRF Protection)
```
1. With product configured, open Browser DevTools → Network tab
2. Click "Add to Cart" button
3. Check Network request:
   - EXPECTED: Request to `admin-ajax.php`
   - EXPECTED: Action: `pls_add_to_cart`
   - EXPECTED: Includes `nonce` parameter
   - EXPECTED: Includes `product_id`, `variation_id`, `quantity`
   - EXPECTED: Status: 200 (success)
4. Check Console tab:
   - EXPECTED: No JavaScript errors
   - EXPECTED: No CSRF errors (403)
5. EXPECTED: Success message appears: "✓ Added to cart successfully!"
6. EXPECTED: Bundle popup appears after 500ms
7. EXPECTED: Configurator modal closes
```

### Step 3.4: Test View Cart Link
```
1. In bundle popup, click "View Cart" button
2. EXPECTED: Redirects to WooCommerce cart page (/cart/)
3. EXPECTED: No 404 errors
4. EXPECTED: Cart shows the added product
5. EXPECTED: Product shows correct:
   - Variation (pack tier)
   - Options selected (Package Type, Color, Cap)
   - Price
   - Quantity (1)
```

### Step 3.5: Test Cart Functionality
```
1. On cart page, verify product details
2. Try to update quantity (if allowed)
3. Click "Proceed to Checkout"
4. EXPECTED: Checkout page loads
5. EXPECTED: Product appears in checkout
6. EXPECTED: Order total calculates correctly
```

---

## PHASE 4: Create WooCommerce Order

### Step 4.1: Complete Test Order
```
1. On checkout page, fill in test details:
   - Email: test@example.com
   - First Name: Test
   - Last Name: Customer
   - Address: 123 Test Street
   - City: Test City
   - Postcode: 12345
   - Phone: 1234567890
2. Select payment method (or use test gateway)
3. Click "Place Order"
4. EXPECTED: Order completes successfully
5. EXPECTED: Order confirmation page shows
6. EXPECTED: Order number displayed
```

### Step 4.2: Verify Order in Admin
```
1. Go to: WordPress Admin → PLS → Orders
2. Find the test order
3. Click to view order details
4. Verify:
   - EXPECTED: Order shows PLS product
   - EXPECTED: Order item meta includes PLS data
   - EXPECTED: Variation ID matches
   - EXPECTED: Pack tier information present
   - EXPECTED: Options selected are shown
```

### Step 4.3: Verify Commission Created
```
1. Go to: WordPress Admin → PLS → Commissions
2. Find commission for test order
3. EXPECTED: Commission created automatically
4. EXPECTED: Commission amount calculated correctly
5. EXPECTED: Commission linked to order
```

---

## PHASE 5: Custom Order Creation

### Step 5.1: Create Custom Order (Admin)
```
1. Go to: WordPress Admin → PLS → Custom Orders
2. Click "Add Custom Order"
3. Fill in Custom Order form:
   - Contact Name: Test Custom Customer
   - Contact Email: custom@example.com
   - Phone: 1234567890
   - Company Name: Test Custom Company
   - Product Category: Test Serums
   - Quantity Needed: 500
   - Budget: $5000
   - Timeline: 4-6 weeks
   - Message: This is a test custom order for manual testing workflow verification.
4. Click "Create Order"
5. EXPECTED: Order appears in "New Leads" column
6. EXPECTED: Order card shows all information
```

### Step 5.2: Test Custom Order View Button (v5.0.0 Fix)
```
1. On Custom Orders page, click "View" button on test order
2. EXPECTED: Modal opens IMMEDIATELY (no delay)
3. EXPECTED: Modal shows all order details
4. Check DevTools → Elements tab:
   - EXPECTED: `#pls-order-detail-modal` has class `is-active`
   - EXPECTED: CSS shows `display: flex` (not `display: block`)
   - EXPECTED: Body has class `pls-modal-open`
5. Test modal close:
   - Click X button
   - EXPECTED: Modal closes
   - EXPECTED: Body class removed
   - Click backdrop
   - EXPECTED: Modal closes
```

### Step 5.3: Edit Custom Order
```
1. In Custom Order modal, edit fields:
   - Change Contact Name
   - Change Budget
   - Change Status dropdown
2. Click "Save All Changes"
3. EXPECTED: Changes save successfully
4. EXPECTED: Modal closes
5. EXPECTED: Page reloads with updated data
```

### Step 5.4: Move Custom Order Between Stages
```
1. Drag order card from "New Leads" to "Sampling" column
2. EXPECTED: Order moves smoothly
3. EXPECTED: Order appears in "Sampling" column
4. EXPECTED: Column counts update
5. OR use "Next Stage" button:
   - Click "Next Stage" in modal
   - EXPECTED: Order moves to next stage
   - EXPECTED: Status updates in database
```

### Step 5.5: Add Sampling Information
```
1. Open Custom Order modal
2. Scroll to "Sampling" section
3. Fill in:
   - Sample Status: Sent
   - Sample Cost: $25.00
   - Sample Sent Date: Today's date
   - Sample Tracking: TEST123456
   - Sample Feedback: Sample received and approved
4. Click "Save All Changes"
5. EXPECTED: Sampling data saves
6. EXPECTED: Order shows sampling information
```

### Step 5.6: Convert Custom Order to WooCommerce Order
```
1. In Custom Order modal, click "Create WooCommerce Order"
2. EXPECTED: Create WC Order modal opens
3. Select Order Status: "Pending Payment"
4. Add products:
   - Product ID: [ID of Test Serum product]
   - Quantity: 1
5. Check "Include Sample Cost"
6. Add custom line item (optional):
   - Item name: Setup Fee
   - Amount: $100.00
7. Add notes: "Test order from custom order conversion"
8. Click "Create Order"
9. EXPECTED: WooCommerce order created
10. EXPECTED: Custom order links to WC order
11. EXPECTED: "View WC Order" button appears
```

---

## PHASE 6: Frontend Custom Order Form

### Step 6.1: Access Custom Order Page
```
OPTION A - Using Shortcode:
1. Create a new WordPress page (Pages → Add New)
2. Title: "Custom Order Request"
3. Add shortcode: [pls_custom_order_form]
4. Publish page
5. Visit the page on frontend
6. EXPECTED: Custom order form displays

OPTION B - Using Page Slug:
1. Create a WordPress page with slug "custom-order"
2. Add shortcode: [pls_custom_order_form]
3. Publish page
4. Visit /custom-order/ on frontend
5. EXPECTED: Custom order form displays

EXPECTED: Form has all required fields:
- Contact Name (required)
- Contact Email (required)
- Phone Number (optional)
- Company Name (optional)
- Product Category (required)
- Quantity Needed (optional)
- Budget (optional)
- Timeline (optional)
- Message (required)
```

### Step 6.2: Submit Custom Order
```
1. Fill in custom order form:
   - Contact Name: Frontend Test Customer
   - Contact Email: frontend@example.com
   - Phone: 9876543210
   - Company Name: Frontend Test Company
   - Product Category: Test Serums
   - Quantity Needed: 1000
   - Budget: $10000
   - Timeline: 6-8 weeks
   - Message: Test custom order from frontend form
2. Click "Submit Request"
3. EXPECTED: Form submits successfully
4. EXPECTED: Success message appears
5. EXPECTED: Form resets OR redirects to thank you page
```

### Step 6.3: Verify Custom Order Created
```
1. Go to: WordPress Admin → PLS → Custom Orders
2. Find "Frontend Test Customer" order
3. EXPECTED: Order appears in "New Leads" column
4. EXPECTED: All information matches form submission
```

---

## PHASE 7: Complete Order Flow Testing

### Step 7.1: Create Another Product (Different Category)
```
1. Create second product: "Test Moisturizer"
   - Category: Create new category "Test Moisturizers"
   - Configure with different options
   - Set up pack tiers
   - Sync to WooCommerce
2. EXPECTED: Second product created and synced
```

### Step 7.2: Create Order with Multiple Products
```
1. Go to frontend
2. Add "Test Serum" to cart (Tier 2)
3. Add "Test Moisturizer" to cart (Tier 1)
4. Go to cart
5. EXPECTED: Both products show correctly
6. EXPECTED: Cart total calculates correctly
7. Proceed to checkout
8. Complete order
9. EXPECTED: Order created with both products
```

### Step 7.3: Verify Order Processing
```
1. Go to: WordPress Admin → WooCommerce → Orders
2. Find test order
3. Change status to "Processing"
4. EXPECTED: Commission created/updated
5. Change status to "Completed"
6. EXPECTED: Commission finalized
```

---

## PHASE 8: System Test Page

### Step 8.1: Run System Tests
```
1. Go to: WordPress Admin → PLS → System Test
2. Click "Run All Tests"
3. EXPECTED: All tests execute
4. EXPECTED: Results show:
   - ✅ Core Functionality: All pass
   - ✅ WooCommerce Sync: All pass
   - ✅ Data Management: All pass
   - ✅ Orders & Commissions: All pass
   - ✅ Admin Interface: All pass
   - ✅ Frontend Display: All pass
   - ✅ AJAX Endpoints: All pass
5. Review any warnings (not errors)
6. EXPECTED: No critical failures
```

### Step 8.2: Verify AJAX Endpoints Test
```
1. In System Test results, check "AJAX Endpoints" section
2. Verify:
   - ✅ `pls_add_to_cart` endpoint registered
   - ✅ Nonce verification working
   - ✅ All endpoints accessible
```

---

## PHASE 9: Edge Cases & Error Handling

### Step 9.1: Test Invalid Add-to-Cart
```
1. On product page, open DevTools Console
2. Try to add product without selecting tier
3. EXPECTED: Validation error: "Please select a pack size"
4. Try with invalid variation ID (modify in console)
5. EXPECTED: Server returns error message
6. Try with missing nonce
7. EXPECTED: 403 Security error
```

### Step 9.2: Test Out of Stock Variation
```
1. In WooCommerce, set one variation to "Out of stock"
2. Try to add that variation to cart
3. EXPECTED: Error: "This variation is not available for purchase"
4. EXPECTED: User-friendly error message
```

### Step 9.3: Test Network Errors
```
1. Open DevTools → Network tab
2. Set throttling to "Offline"
3. Try to add product to cart
4. EXPECTED: Network error message appears
5. EXPECTED: Button re-enables
6. EXPECTED: User can retry
```

### Step 9.4: Test Modal Edge Cases
```
1. Open Custom Order modal
2. Rapidly click "View" button multiple times
3. EXPECTED: Only one modal opens
4. EXPECTED: No duplicate modals
5. Open modal, then refresh page
6. EXPECTED: No JavaScript errors
```

---

## PHASE 10: Performance & UX

### Step 10.1: Test Loading States
```
1. Open product modal
2. EXPECTED: Shows loading spinner while data loads
3. Save product
4. EXPECTED: Shows "Saving..." feedback
5. Run system tests
6. EXPECTED: Shows progress indicator
```

### Step 10.2: Test Responsive Design
```
1. Resize browser to mobile width (375px)
2. Test product page
3. EXPECTED: Layout adapts correctly
4. EXPECTED: Modals fullscreen on mobile
5. EXPECTED: No horizontal scroll
6. Test on tablet width (768px)
7. EXPECTED: Layout adapts correctly
```

---

## ✅ SUCCESS CRITERIA

### Critical (Must Pass):
- [ ] Product creation works end-to-end
- [ ] Product syncs to WooCommerce correctly
- [ ] Frontend add-to-cart works with CSRF protection
- [ ] View Cart link redirects correctly
- [ ] Custom Orders View button opens modal immediately
- [ ] Orders can be created and processed
- [ ] Custom orders can be created and managed
- [ ] No JavaScript console errors
- [ ] No PHP errors in error log

### High Priority (Should Pass):
- [ ] All system tests pass
- [ ] Product options work correctly
- [ ] Pack tiers display and function correctly
- [ ] Price calculator works accurately
- [ ] Modal functionality works perfectly
- [ ] Order conversion works
- [ ] Commission calculation works

### Medium Priority (Nice to Have):
- [ ] Loading states show properly
- [ ] Error messages are clear
- [ ] Responsive design works
- [ ] Performance is acceptable

---

## 📝 TESTING LOG

```
Date: ___________
Tester: ___________
Environment: WordPress ___, WooCommerce ___, PHP ___

[ ] Phase 1: Production Options Setup - Complete
[ ] Phase 2: Product Creation - Complete
[ ] Phase 3: Frontend Product Testing - Complete
[ ] Phase 4: WooCommerce Order - Complete
[ ] Phase 5: Custom Order Creation - Complete
[ ] Phase 6: Frontend Custom Order Form - Complete
[ ] Phase 7: Complete Order Flow - Complete
[ ] Phase 8: System Test Page - Complete
[ ] Phase 9: Edge Cases - Complete
[ ] Phase 10: Performance & UX - Complete

Issues Found:
1. _________________________________
2. _________________________________
3. _________________________________

Overall Status: [ ] PASS [ ] FAIL [ ] NEEDS FIXES
```

---

## 🐛 TROUBLESHOOTING

### If Add-to-Cart Fails:
1. Check browser console for errors
2. Check Network tab for failed requests
3. Verify nonce is being sent
4. Check WordPress error log
5. Verify WooCommerce is active

### If Modal Doesn't Open:
1. Check browser console for JavaScript errors
2. Verify `#pls-order-detail-modal` exists in DOM
3. Check if `.is-active` class is being added
4. Verify CSS is loading correctly

### If Sync Fails:
1. Check System Test page for sync errors
2. Verify WooCommerce is active
3. Check product data is valid
4. Review error log for details

---

**END OF MANUAL TESTING GUIDE**

Follow this guide step-by-step to test the entire production workflow!
