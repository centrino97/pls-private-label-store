# Database Verification Rules

This document provides rules and guidelines for using the database verification scripts to connect to the Hostinger MySQL database and verify PLS plugin data.

## Overview

The database verification scripts allow you to:
- Connect directly to the WordPress MySQL database without loading WordPress
- Verify that all plugin tables exist and have correct structure
- Check data integrity and relationships
- Verify WooCommerce synchronization
- Inspect specific data from plugin tables

## Setup

### 1. Configure Database Connection

1. Copy the configuration template:
   ```bash
   cp scripts/db-config.example.php scripts/db-config.php
   ```

2. Edit `scripts/db-config.php` and fill in your Hostinger MySQL credentials:
   ```php
   return [
       'host'     => 'localhost',           // Your Hostinger MySQL host
       'database' => 'your_database_name',  // Your WordPress database name
       'username' => 'your_username',       // Your MySQL username
       'password' => 'your_password',       // Your MySQL password
       'charset'  => 'utf8mb4',
       'prefix'   => 'wp_',                 // Check wp-config.php for your prefix
       'port'     => 3306,
   ];
   ```

3. **IMPORTANT**: Never commit `db-config.php` to version control! It's already in `.gitignore`.

### 2. Verify Connection

Test the database connection:
```bash
php scripts/db-connect.php
```

You should see:
```
✓ Successfully connected to database: your_database_name
  Host: localhost
  Prefix: wp_
```

## Available Scripts

### 1. `db-verify.php` - Full Database Verification

Verifies all plugin tables, their structure, and data integrity.

**Usage:**
```bash
php scripts/db-verify.php
```

**What it checks:**
- All 12 plugin tables exist
- Table structures match expected schema
- Row counts for each table
- Data integrity (orphaned records, NULL values in key fields)
- Relationships between tables

**Output:**
- ✓ Green checkmarks for passed checks
- ⚠ Warnings for potential issues
- ✗ Errors for critical problems

### 2. `db-inspect.php` - Inspect Specific Data

Allows you to view actual data from plugin tables.

**Usage:**
```bash
# View all available tables
php scripts/db-inspect.php

# Inspect a specific table
php scripts/db-inspect.php pls_base_product 10
php scripts/db-inspect.php pls_pack_tier 5
php scripts/db-inspect.php pls_custom_order 20
```

**Available tables:**
- `pls_base_product` - Base products
- `pls_pack_tier` - Pack tiers
- `pls_product_profile` - Product profiles
- `pls_bundle` - Bundles
- `pls_bundle_item` - Bundle items
- `pls_attribute` - Attributes
- `pls_attribute_value` - Attribute values
- `pls_swatch` - Swatches
- `pls_custom_order` - Custom orders
- `pls_order_commission` - Order commissions
- `pls_onboarding_progress` - Onboarding progress
- `pls_commission_reports` - Commission reports

### 3. `db-sync-check.php` - WooCommerce Sync Verification

Checks if PLS plugin data is properly synced with WooCommerce.

**Usage:**
```bash
php scripts/db-sync-check.php
```

**What it checks:**
- Base products → WooCommerce products
- Pack tiers → WooCommerce variations
- Bundles → WooCommerce grouped products
- Attributes → WooCommerce attributes
- Custom orders → WooCommerce orders

## When to Use These Scripts

### Before Production Deployment

Run full verification:
```bash
php scripts/db-verify.php
php scripts/db-sync-check.php
```

### After Plugin Updates

After updating the plugin, verify:
1. All tables still exist
2. New migrations ran successfully
3. Data integrity is maintained

### When Debugging Issues

1. **Data not appearing in admin:**
   ```bash
   php scripts/db-inspect.php pls_base_product 10
   ```

2. **WooCommerce sync issues:**
   ```bash
   php scripts/db-sync-check.php
   ```

3. **Missing relationships:**
   ```bash
   php scripts/db-verify.php
   ```

### During Development

Use `db-inspect.php` to verify what the plugin generates:
```bash
# After creating a product
php scripts/db-inspect.php pls_base_product 1

# After creating pack tiers
php scripts/db-inspect.php pls_pack_tier 5

# After syncing to WooCommerce
php scripts/db-sync-check.php
```

## Expected Plugin Tables

The plugin creates 12 custom tables (with your WordPress prefix):

1. **wp_pls_base_product** - Main product catalog
2. **wp_pls_pack_tier** - Pricing tiers for products
3. **wp_pls_product_profile** - Product descriptions, images, options
4. **wp_pls_bundle** - Product bundles/packages
5. **wp_pls_bundle_item** - Items in bundles
6. **wp_pls_attribute** - Product attributes (Package Type, Color, etc.)
7. **wp_pls_attribute_value** - Specific attribute values
8. **wp_pls_swatch** - Visual swatches for attributes
9. **wp_pls_custom_order** - Custom order/lead management
10. **wp_pls_order_commission** - Commission tracking
11. **wp_pls_onboarding_progress** - User onboarding
12. **wp_pls_commission_reports** - Monthly commission reports

## Production Readiness Checklist

Before deploying to production, verify:

- [ ] All 12 tables exist
- [ ] Table structures match expected schema
- [ ] No orphaned records (pack tiers without base products, etc.)
- [ ] Published products are synced with WooCommerce
- [ ] Enabled pack tiers are synced with WooCommerce variations
- [ ] Published bundles are synced with WooCommerce
- [ ] No critical errors in verification output

## Troubleshooting

### Connection Errors

**Error: "Database connection failed"**
- Check your credentials in `db-config.php`
- Verify database name, username, and password
- Check if MySQL is running
- Verify host and port settings

**Error: "Table does not exist"**
- Plugin may not be activated
- Run plugin activation to create tables
- Check WordPress table prefix in `db-config.php`

### Data Integrity Issues

**Orphaned records:**
- These indicate broken relationships
- May need to clean up or re-sync data
- Check plugin logs for errors

**Missing WooCommerce sync:**
- Run WooCommerce sync from plugin admin
- Check if WooCommerce is active
- Verify WooCommerce tables exist

## Security Notes

- **Never commit `db-config.php`** - It contains sensitive credentials
- Use read-only database user if possible
- Run scripts from secure location
- Don't expose scripts publicly (keep outside web root or protect with .htaccess)

## Integration with AI Assistant

When working with the AI assistant, you can:

1. **Ask to verify database:**
   - "Run database verification"
   - "Check if all plugin tables exist"
   - "Verify WooCommerce sync"

2. **Ask to inspect data:**
   - "Show me the first 5 base products"
   - "Inspect pack tiers for product ID X"
   - "Check custom orders"

3. **Ask to troubleshoot:**
   - "Why isn't product X syncing to WooCommerce?"
   - "Check for orphaned records"
   - "Verify data integrity"

4. **Ask to generate reports:**
   - "Generate an HTML report of the database"
   - "Export all plugin data to SQL"
   - "Create a verification report"

The AI assistant can run these scripts and interpret the results to help you ensure production-ready code.

## MCP Integration

For even more powerful integration, see `.cursor/rules/wordpress-mcp-integration.md` for:
- Browser Extension MCP (view WordPress admin/frontend)
- WordPress MCP Adapter (access files and database)
- Complete WordPress file and database access
