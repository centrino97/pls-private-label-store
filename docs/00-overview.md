# PLS Plugin Overview

## What is PLS?

PLS (Private Label Store) is a WordPress plugin that manages a private label product catalog, syncs with WooCommerce, and provides Elementor widgets for frontend display.

## Core Features

- **Product Management**: Base products with pack tiers, attributes, and options
- **Bundle System**: Create product bundles with automatic cart detection
- **WooCommerce Sync**: Automatic synchronization of products and bundles
- **Commission Tracking**: Automated commission calculation for orders
- **Custom Orders**: Kanban-style custom order management
- **Tutorial System**: Guided onboarding for new users

## Architecture

- **Data Layer**: Custom database tables for products, bundles, orders, commissions
- **Sync Layer**: WooCommerce integration for product/variation sync
- **Frontend**: Elementor widgets for product display
- **Admin UI**: Modern, component-based interface

## Key Files

- `pls-private-label-store.php` - Main plugin file
- `includes/class-pls-plugin.php` - Core plugin class
- `includes/wc/class-pls-wc-sync.php` - WooCommerce sync
- `includes/admin/` - Admin interface
- `includes/data/` - Repository classes
- `includes/elementor/` - Elementor widgets

## Database Tables

- `pls_base_product` - Base products
- `pls_pack_tier` - Pack tier configurations
- `pls_bundle` - Bundle definitions
- `pls_custom_order` - Custom orders
- `pls_order_commission` - Commission records
- `pls_attribute` - Product attributes
- `pls_attribute_value` - Attribute values
- `pls_onboarding_progress` - Tutorial progress

## Version History

- **v1.2.2**: Enhanced sample data with full product options and bundle orders
- **v1.2.1**: Comprehensive sample data generator
- **v1.2.0**: Tutorial overhaul, tiered commission
- **v1.1.0**: Product sync states, bundle functionality, commission automation
