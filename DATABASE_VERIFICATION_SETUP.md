# Database Verification Setup - Complete

## ✅ What Was Created

I've created a complete database verification system that allows you to connect to your Hostinger MySQL database and verify that the PLS plugin is generating data correctly.

### Files Created

1. **`scripts/db-config.example.php`** - Configuration template
2. **`scripts/db-config.php`** - Your actual config (create from example, NOT in git)
3. **`scripts/db-connect.php`** - Database connection utility
4. **`scripts/db-verify.php`** - Full database verification script
5. **`scripts/db-inspect.php`** - Data inspection tool
6. **`scripts/db-sync-check.php`** - WooCommerce sync verification
7. **`scripts/README.md`** - Quick reference guide
8. **`.cursor/rules/database-verification.md`** - Complete documentation and rules
9. **`.gitignore`** - Ensures db-config.php is never committed

## 🚀 Quick Start

### Step 1: Configure Database Connection

```bash
# Copy the template
cp scripts/db-config.example.php scripts/db-config.php

# Edit db-config.php with your Hostinger MySQL credentials
```

Fill in your Hostinger MySQL details:
- Host (usually `localhost` or your Hostinger MySQL host)
- Database name (your WordPress database)
- Username
- Password
- Table prefix (usually `wp_`)

### Step 2: Test Connection

```bash
php scripts/db-connect.php
```

You should see:
```
✓ Successfully connected to database: your_database_name
  Host: localhost
  Prefix: wp_
```

### Step 3: Run Verification

```bash
# Full database verification
php scripts/db-verify.php

# Check WooCommerce sync
php scripts/db-sync-check.php

# Inspect specific data
php scripts/db-inspect.php pls_base_product 10
```

## 📋 What Gets Verified

### Database Structure
- ✅ All 12 plugin tables exist
- ✅ Table structures match expected schema
- ✅ All required columns are present
- ✅ Indexes and keys are correct

### Data Integrity
- ✅ No orphaned records (pack tiers without base products, etc.)
- ✅ No NULL values in critical fields
- ✅ Relationships are intact

### WooCommerce Sync
- ✅ Base products → WooCommerce products
- ✅ Pack tiers → WooCommerce variations
- ✅ Bundles → WooCommerce grouped products
- ✅ Attributes → WooCommerce attributes
- ✅ Custom orders → WooCommerce orders

## 🎯 Use Cases

### Before Production Deployment
```bash
php scripts/db-verify.php
php scripts/db-sync-check.php
```

### After Plugin Updates
Verify migrations ran successfully and data integrity is maintained.

### When Debugging
```bash
# Check what products exist
php scripts/db-inspect.php pls_base_product 10

# Check sync status
php scripts/db-sync-check.php

# Find orphaned data
php scripts/db-verify.php
```

### During Development
Use `db-inspect.php` to verify what the plugin generates:
```bash
# After creating a product
php scripts/db-inspect.php pls_base_product 1

# After creating pack tiers
php scripts/db-inspect.php pls_pack_tier 5

# Check custom orders
php scripts/db-inspect.php pls_custom_order 20
```

## 📊 Plugin Tables Verified

The scripts verify these 12 tables:

1. `wp_pls_base_product` - Main product catalog
2. `wp_pls_pack_tier` - Pricing tiers
3. `wp_pls_product_profile` - Product details
4. `wp_pls_bundle` - Product bundles
5. `wp_pls_bundle_item` - Bundle contents
6. `wp_pls_attribute` - Product attributes
7. `wp_pls_attribute_value` - Attribute values
8. `wp_pls_swatch` - Visual swatches
9. `wp_pls_custom_order` - Custom orders
10. `wp_pls_order_commission` - Commissions
11. `wp_pls_onboarding_progress` - Onboarding
12. `wp_pls_commission_reports` - Commission reports

## 🤖 Using with AI Assistant

You can now ask the AI assistant to:

- **"Run database verification"** - Execute full verification
- **"Check if all plugin tables exist"** - Verify table structure
- **"Verify WooCommerce sync"** - Check sync status
- **"Show me the first 5 base products"** - Inspect data
- **"Check for orphaned records"** - Find data integrity issues
- **"Why isn't product X syncing?"** - Troubleshoot sync issues

The AI assistant can run these scripts and interpret results to help ensure production-ready code.

## 🔒 Security Notes

- ⚠️ **Never commit `db-config.php`** - It contains sensitive credentials
- ✅ `db-config.php` is already in `.gitignore`
- ✅ Scripts use PDO with prepared statements (SQL injection safe)
- ✅ Scripts are read-only (no data modification)

## 📚 Documentation

- **Quick Reference**: `scripts/README.md`
- **Complete Guide**: `.cursor/rules/database-verification.md`

## ✨ Production Readiness Checklist

Before deploying, verify:

- [ ] All 12 tables exist
- [ ] Table structures match expected schema
- [ ] No orphaned records
- [ ] Published products are synced with WooCommerce
- [ ] Enabled pack tiers are synced with WooCommerce variations
- [ ] Published bundles are synced with WooCommerce
- [ ] No critical errors in verification output

## 🆘 Troubleshooting

### Connection Issues
- Verify credentials in `db-config.php`
- Check database name, username, password
- Verify MySQL is running
- Check host and port settings

### Table Not Found
- Plugin may not be activated
- Run plugin activation to create tables
- Check WordPress table prefix

### Sync Issues
- Run WooCommerce sync from plugin admin
- Verify WooCommerce is active
- Check WooCommerce tables exist

---

**Ready to use!** Configure `db-config.php` and start verifying your plugin data. 🎉
