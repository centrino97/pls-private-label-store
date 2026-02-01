# Database Verification Scripts

This directory contains scripts for connecting to and verifying the WordPress database for the PLS Private Label Store plugin.

## Quick Start

1. **Copy the configuration template:**
   ```bash
   cp db-config.example.php db-config.php
   ```

2. **Edit `db-config.php`** with your Hostinger MySQL credentials

3. **Run verification:**
   ```bash
   php db-verify.php
   ```

4. **Generate reports:**
   ```bash
   # HTML report
   php db-export-html.php report.html
   
   # SQL dump
   php db-export-sql.php backup.sql
   ```

## Scripts

### `cleanup-and-resync.php`
**Purpose:** Clean up incorrectly synced ingredient WooCommerce attributes and re-sync ingredients with correct tier levels.

**Usage:**
```bash
# Via WP-CLI
wp eval-file scripts/cleanup-and-resync.php

# Or via browser (requires admin login)
# Navigate to: /wp-content/plugins/pls-private-label-store/scripts/cleanup-and-resync.php
```

**What it does:**
1. Deletes incorrectly synced ingredient WooCommerce attributes (ingredients should NOT be WooCommerce attributes)
2. Re-syncs all ingredients to PLS attributes with correct tier levels:
   - Base/INCI ingredients = Tier 1-2 (always included, no price impact)
   - Key/active ingredients = Tier 3+ (unlockable, price affecting)
3. Verifies ingredient sync status
4. Checks for remaining incorrectly synced WooCommerce attributes

**When to use:**
- After updating to v5.3.0+ to clean up old incorrectly synced attributes
- When ingredient sync tests are failing
- When you see thousands of ingredient WooCommerce attributes that shouldn't exist

### `db-config.example.php`
Template configuration file. Copy to `db-config.php` and fill in your credentials.

### `db-connect.php`
Database connection utility. Used by other scripts.

### `db-verify.php`
Full database verification - checks all tables, structure, and data integrity.

### `db-inspect.php`
Inspect specific data from plugin tables.

### `db-sync-check.php`
Verify WooCommerce synchronization.

### `db-export-html.php`
Export database verification results to a beautiful HTML report.

### `db-export-sql.php`
Export all PLS plugin tables to SQL dump file.

## Documentation

See `.cursor/rules/database-verification.md` for complete documentation and usage guidelines.

## Security

⚠️ **Never commit `db-config.php` to version control!** It contains sensitive database credentials.
