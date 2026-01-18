=== PLS – Private Label Store Manager (Woo + Elementor) ===
Contributors: yourteam
Tags: woocommerce, elementor, bundles, swatches
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.8.6
License: GPLv2 or later

A scaffold plugin for a private-label WooCommerce store built with Hello Elementor + Elementor Pro.

== Description ==
This plugin creates custom tables for a structured catalog model (base products, pack tiers, bundles, attributes/swatches)
and provides Elementor widgets as placeholders for a seamless Theme Builder integration.

== Installation ==
1. Upload the ZIP in WP Admin → Plugins → Add New → Upload Plugin
2. Activate
3. Configure under WooCommerce → Private Label (PLS)

== Notes ==
* Enforces required fields with server-side validation and syncs every save/delete to WooCommerce (plus reconciliation when Woo products are missing).
* Provides modal ingredient search/creation, attribute/value creation modals, and media pickers with live thumbnails and remove controls.

== Changelog ==
= 0.8.6 =
* **AJAX-Powered Product Options:** Complete rewrite to use AJAX for all operations - no page reloads!
* **Modals for Add/Edit:** Beautiful modals for adding and editing product options and values.
* **Delete Functionality:** Delete buttons with confirmation for both options and values.
* **Pack Tier Modal:** Pack Tier defaults moved to separate modal accessible via button.
* **Better UX:** Instant feedback, smooth animations, and improved user experience throughout.
* **Dynamic Updates:** All changes reflect immediately without page refreshes.

= 0.8.5 =
* **Tabbed Product Options UI:** Complete redesign with tabs for Pack Tier (PRIMARY), Product Options, and Ingredients.
* **Pack Tier Defaults:** Pack tiers now have default units and prices that are always loaded but editable per product.
* **Default Prices:** Added default price per unit for all pack tiers (Tier 1: $15.90, Tier 2: $14.50, Tier 3: $12.50, Tier 4: $9.50, Tier 5: $7.90).
* **Ingredients as Options:** Ingredients are now treated as Product Options accessible via tabs.
* **UI/UX Improvements:** Enhanced product creation form with auto-calculating tier totals, better visual hierarchy, and improved responsiveness.
* **Pack Tier Management:** New interface to manage pack tier defaults (units, prices) with descriptions for each tier level.

= 0.8.4 =
* **UUPD Fix:** Fixed update detection by forcing direct JSON fetch instead of GitHub API.
* **Update Mechanism:** Improved reliability of automatic updates from GitHub releases.

= 0.8.3 =
* **Hierarchical Product Options:** Pack Tier is now PRIMARY option with clear visual hierarchy.
* **Ingredient Integration:** Ingredients integrated as Tier 3+ options, automatically synced with attribute system.
* **UI Redesign:** 3-section layout (Pack Tier PRIMARY, Product Options, Ingredients) with distinct styling and badges.
* **Database:** Added option_type, is_primary, and parent_attribute_id columns to pls_attribute table.
* **Ingredient Sync:** Automatic bidirectional sync between pls_ingredient taxonomy and pls_attribute table.
* **Repository Updates:** New methods for filtering by option type and managing primary attributes.
* **WooCommerce Sync:** Updated to use primary attribute system for pack tier identification.

= 0.8.2 =
* **Compact Table UI:** Redesigned Product Options with compact, table-based layout for better navigation.
* **Client Requirements Complete:** Added Custom Printed Bottles and External Box Packaging options for Tier 4+.
* **Improved UX:** Quick add forms, click-to-expand editing, responsive design with minimal spacing.
* **All Client Specs:** Package types (30ml, 50ml, 120ml, 50gr jar), Package colours with silver pump upgrade, Tier-based restrictions fully implemented.

= 0.8.1 =
* **UI/UX Overhaul:** Completely redesigned Product Options page with modern, intuitive interface.
* **Unified Interface:** Integrated ingredients into Product Options - no longer separate menu item.
* **Tabbed Navigation:** Added tabs for Attributes, Ingredients, and Pricing Overview for easier navigation.
* **Improved Pricing UI:** Better visual indicators, bulk editing, and pricing impact overview.
* **Modern Design:** Clean card-based layout, better spacing, improved typography, and visual hierarchy.
* **Easier Configuration:** Streamlined forms, inline editing, and quick stats dashboard.
* **Better UX:** More intuitive workflow for setting up products and configuring pricing impacts.

= 0.8.0 =
* **Major Refactor:** Converted pack tiers from hardcoded system to flexible attribute-based system.
* **New Features:** Tier-based restrictions and pricing rules for attribute values.
* **Attributes Redesign:** Attributes page redesigned to match ingredients page style with tier rule management.
* **Default Attributes:** Auto-creates Pack Tier, Package Type, and Package Colour attributes on activation.
* **Tier Rules:** Implemented tier-based availability (min_tier_level) and tier-specific pricing overrides.
* **WooCommerce Sync:** Refactored to use dynamic pack tier attribute instead of hardcoded values.
* **Client Requirements:** Added Package Type (30ml, 50ml, 120ml, 50gr jar) and Package Colour options with silver pump upgrade.
* **Database Migration:** Added min_tier_level and tier_price_overrides columns to pls_attribute_value table.

= 0.7.1 =
* Development release with improved update system.

= 0.7.0 =
* Cleans up Key ingredients with a single scrollable selector, live counter, and enforced five-item cap tied to selected ingredients.
* Simplifies attribute selection to dropdown + reliable value multi-select with default impact autofill and per-product overrides.
* Adds a unified Manage attributes & values modal for creating attributes/values and editing default price impacts without stacking modals.
* Hardens modal targeting so only the intended dialog opens when adding products or managing ingredients/attributes.
