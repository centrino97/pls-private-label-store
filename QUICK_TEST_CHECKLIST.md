# PLS v5.0.0 - Quick Testing Checklist

**Use this checklist for rapid testing of critical v5.0.0 features**

---

## 🚀 Quick Start Testing (30 minutes)

### 1. Setup (5 min)
- [ ] WordPress Admin → Plugins → Verify PLS version is 5.0.0
- [ ] Go to PLS → System Test → Run All Tests → Verify all pass
- [ ] Create one test category: "Test Products"
- [ ] Create 3-5 test ingredients

### 2. Create Complete Product (10 min)
- [ ] PLS → Products → Add Product
- [ ] Fill: Name, Category, Description
- [ ] Add 3 ingredients (mark 1 as key)
- [ ] Enable 3 pack tiers (50, 100, 250 units)
- [ ] Add 2-3 product options (Package Type, Color)
- [ ] Set featured image
- [ ] Click "Save Product"
- [ ] ✅ Verify: Product syncs to WooCommerce
- [ ] ✅ Verify: Variations created correctly

### 3. Test Frontend Add-to-Cart (5 min)
- [ ] Go to frontend product page
- [ ] Click "Configure & Order"
- [ ] Select Tier 1 (50 units)
- [ ] Select options (Package Type, Color)
- [ ] Open DevTools → Network tab
- [ ] Click "Add to Cart"
- [ ] ✅ Verify: Request includes `nonce` parameter
- [ ] ✅ Verify: Status 200 (success)
- [ ] ✅ Verify: Success message appears
- [ ] ✅ Verify: "View Cart" link works (no 404)

### 4. Test Custom Orders Modal Fix (5 min)
- [ ] PLS → Custom Orders
- [ ] Click "Add Custom Order"
- [ ] Fill form and create order
- [ ] Click "View" button on order
- [ ] ✅ Verify: Modal opens IMMEDIATELY
- [ ] ✅ Verify: Modal has `.is-active` class
- [ ] ✅ Verify: CSS shows `display: flex`
- [ ] ✅ Verify: Can close with X button
- [ ] ✅ Verify: Can close with backdrop click

### 5. Test Custom Order Form (5 min)
- [ ] Create page with shortcode: `[pls_custom_order_form]`
- [ ] OR go to page slug "custom-order" (if exists)
- [ ] Fill custom order form
- [ ] Submit form
- [ ] ✅ Verify: Order created in admin
- [ ] ✅ Verify: Appears in "New Leads" column

---

## ✅ Critical v5.0.0 Features Test

### CSRF Protection Test
- [ ] Add product to cart → Check Network tab → Verify `nonce` in request
- [ ] Try request without nonce → Should get 403 error

### Cart URL Test
- [ ] Add product → Click "View Cart" → Should go to `/cart/` (not 404)

### Custom Orders Modal Test
- [ ] Click "View" → Modal opens immediately (no delay)
- [ ] Inspect element → Has `.is-active` class
- [ ] Check CSS → Shows `display: flex`

---

## 📝 Full Testing Guide

For complete step-by-step testing, see: `MANUAL_TESTING_GUIDE.md`

---

**Quick Test Status:** [ ] PASS [ ] FAIL
