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
