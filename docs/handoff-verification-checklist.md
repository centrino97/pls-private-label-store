# PLS Handoff Verification Checklist

This document provides a comprehensive checklist for verifying the PLS system is ready for handoff to Rober and Raniya.

## Pre-Handoff Verification Steps

### 1. Sample Data Verification

#### Check Sample Product Exists
- [ ] At least one product exists in `pls_base_product` table
- [ ] Sample product has status = 'live'
- [ ] Sample product has `wc_product_id` set (synced to WooCommerce)
- [ ] Sample product has complete product profile

#### Verify Pack Tiers
- [ ] All 5 pack tiers exist for sample product (Tier 1-5)
- [ ] Each pack tier has `is_enabled = 1`
- [ ] Each pack tier has `units` configured (50, 100, 250, 500, 1000)
- [ ] Each pack tier has `price` configured
- [ ] Each pack tier has `wc_variation_id` set (synced to WooCommerce)

#### Verify Product Profile
- [ ] Product profile exists for sample product
- [ ] Featured image is set
- [ ] Gallery images are configured
- [ ] Short description is populated
- [ ] Long description is populated
- [ ] Ingredients are assigned
- [ ] Product options (attributes) are assigned
- [ ] Key ingredients are marked

#### Verify WooCommerce Sync
- [ ] WooCommerce product exists and is published
- [ ] Product type is 'variable'
- [ ] All variations exist (one per pack tier)
- [ ] Variations have correct `pa_pack-tier` attribute
- [ ] Variations have `_pls_units` meta set
- [ ] Product categories are synced

### 2. User Accounts & Permissions

#### Create User Accounts
- [ ] User account for Rober exists
- [ ] User account for Raniya exists
- [ ] Both users have `pls_user` role assigned
- [ ] Both users have required capabilities:
  - [ ] `pls_manage_products`
  - [ ] `pls_manage_attributes`
  - [ ] `pls_manage_bundles`

#### Test Restricted Access
- [ ] Rober can only access PLS pages (not full WordPress admin)
- [ ] Raniya can only access PLS pages (not full WordPress admin)
- [ ] Both users can access all PLS admin screens
- [ ] Both users can create/edit products
- [ ] Both users can sync products to WooCommerce

### 3. Admin Screens Testing

#### Dashboard
- [ ] Dashboard loads correctly
- [ ] Summary cards show correct counts
- [ ] Sample product is counted in totals
- [ ] Navigation menu is visible
- [ ] Help button (?) is visible and functional

#### Products Page
- [ ] Product list displays correctly
- [ ] Sample product appears in list
- [ ] Create product button works
- [ ] Edit product modal opens correctly
- [ ] Product preview works
- [ ] Sync button works
- [ ] All product fields are editable

#### Product Options/Attributes Page
- [ ] Pack Tier defaults are configured
- [ ] All product options are listed
- [ ] Options can be added/edited
- [ ] Sync to WooCommerce works

#### Categories Page
- [ ] Categories list displays
- [ ] Sample product's category is visible
- [ ] Categories can be managed

#### Ingredients Page
- [ ] Ingredients list displays
- [ ] Sample product's ingredients are visible
- [ ] Ingredients can be managed

#### Bundles Page
- [ ] Bundles list displays
- [ ] Sample bundles exist (if applicable)
- [ ] Create bundle button works

#### Custom Orders Page
- [ ] Kanban board displays
- [ ] Sample custom orders exist (if applicable)
- [ ] Orders can be created/edited

#### Orders Page
- [ ] Orders list displays
- [ ] Sample orders exist (if applicable)
- [ ] Order details can be viewed

#### Commission Page
- [ ] Commission list displays
- [ ] Sample commissions exist (if applicable)
- [ ] Commission calculations are correct

#### Revenue Page
- [ ] Revenue data displays
- [ ] Revenue snapshots exist

#### Settings Page
- [ ] All settings are accessible
- [ ] Commission rates are configured
- [ ] Email recipients are set

#### System Test Page
- [ ] System test runs successfully
- [ ] All tests pass or show appropriate warnings
- [ ] Test results can be downloaded

### 4. Frontend Display Verification

#### Product Page
- [ ] Sample product displays correctly on frontend
- [ ] Product images show correctly
- [ ] Configurator (pack tier selector) works
- [ ] Product description displays
- [ ] Ingredients list displays
- [ ] Bundle offers display (if applicable)
- [ ] Add to cart functionality works

#### Shortcodes
- [ ] `[pls_single_product]` works correctly
- [ ] `[pls_single_category]` works correctly
- [ ] `[pls_shop_page]` works correctly

#### Configurator
- [ ] Pack tier buttons are clickable
- [ ] Selecting tier updates WooCommerce variation form
- [ ] Price updates when tier is selected
- [ ] Units display correctly

### 5. Help System Verification

#### Help Button
- [ ] Help button (?) appears on all PLS pages
- [ ] Help button opens help modal
- [ ] Help content is accurate and helpful
- [ ] Help content covers all features

#### Onboarding
- [ ] Onboarding system is accessible
- [ ] Help content is comprehensive
- [ ] Tooltips work (if implemented)

### 6. Documentation

#### User Guides
- [ ] Step-by-step guide for adding products exists
- [ ] Quick reference guide exists
- [ ] Troubleshooting guide exists
- [ ] Documentation is clear and easy to follow

### 7. System Health

#### System Test
- [ ] Run full system test
- [ ] All critical tests pass
- [ ] Warnings are documented and acceptable
- [ ] Test results are saved

#### Error Handling
- [ ] Error messages are clear
- [ ] Validation works correctly
- [ ] Sync errors are handled gracefully

## Verification Script

To verify sample data programmatically, use the System Test page at:
`/wp-admin/admin.php?page=pls-system-test`

Or check sample data status via:
```php
$status = PLS_Sample_Data::get_sample_data_status();
$test_results = PLS_System_Test::run_all_tests();
```

## Quick Verification Commands

### Check if sample product exists:
```sql
SELECT * FROM wp_pls_base_product WHERE status = 'live' LIMIT 1;
```

### Check if product is synced:
```sql
SELECT id, name, wc_product_id FROM wp_pls_base_product WHERE wc_product_id IS NOT NULL;
```

### Check pack tiers:
```sql
SELECT * FROM wp_pls_pack_tier WHERE base_product_id = [PRODUCT_ID] AND is_enabled = 1;
```

### Check user roles:
```sql
SELECT u.user_login, um.meta_value as roles 
FROM wp_users u 
LEFT JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities' 
WHERE u.user_login IN ('robert', 'raniya', 'Rober', 'Raniya');
```

## Post-Verification Actions

After completing verification:
1. Document any issues found
2. Fix critical issues before handoff
3. Create demo script for walkthrough
4. Prepare user accounts with passwords
5. Schedule handoff session
