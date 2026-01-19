=== PLS – Private Label Store Manager (Woo + Elementor) ===
Contributors: yourteam
Tags: woocommerce, elementor, bundles, swatches
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.2
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
= 1.2.2 =
* **Enhanced Sample Data:** Products now include full product options (Package Type, Color, Cap, Fragrances) with all price impacts.
* **Bundle-Qualified Orders:** Added orders that qualify for bundle pricing to test bundle detection and commission calculation.
* **Complete Product Configuration:** Sample products are set to 'live' status and include all product options, ingredients, benefits, and label settings.
* **Email Testing Ready:** Commission email recipient automatically configured to n.nikolic97@gmail.com for testing.

= 1.2.1 =
* **Comprehensive Sample Data:** Complete sample data generator now includes WooCommerce orders, custom orders, and commission records.
* **Full Feature Coverage:** Sample data covers all plugin features - products, bundles, orders, commissions, revenue tracking, and custom orders.
* **Restored Sample Data Button:** Sample data generator button restored in Settings for complete testing environment setup.

= 1.2.0 =
* **Tutorial System Overhaul:** Complete redesign of onboarding into guided step-by-step tutorial for Raniya/Robert.
* **Sequential Tutorial Flow:** Tutorial guides users through Product Options → Products → Bundles → Categories with clear step-by-step instructions.
* **Tutorial UI:** New tutorial panel at top of page with progress indicator, checkboxes for each step, and Next/Previous navigation.
* **Auto-Navigation:** Tutorial automatically navigates to next page when section is complete.
* **Tiered Custom Order Commission:** Commission rates now based on order value thresholds (3% below 100k AUD, 5% above 100k AUD).
* **Auto-Commission Calculation:** Commission automatically calculated when custom order status changes to "Done" based on final order value.
* **Commission Settings:** New settings UI for configuring threshold amount and both commission rates.
* **Sample Data Removed:** Sample data generator button removed from Settings (data already configured).
* **Settings & Commission:** These sections excluded from tutorial (for Nikola only).

= 1.1.0 =
* **Product Sync State Detection:** Intelligent sync state detection with 4 states (Synced/Active, Synced/Inactive, Update Available, Not Synced) and visual badges.
* **Product Status Control:** Activate/Deactivate buttons for quick product visibility control. Update button appears when product changes need syncing.
* **Full Bundle Functionality:** Complete bundle management system with creation, editing, and WooCommerce Grouped Product sync.
* **Bundle Cart Detection:** Automatic cart detection of bundle qualification with automatic pricing application. Customers can pick products and qualify for bundle pricing.
* **Semi-Automated Commission:** Commission automatically calculated when orders are completed, with approval workflow for review and adjustment.
* **Integrated Helper System:** Per-section help buttons with contextual tooltips in modals, replacing floating helper card.
* **Enhanced Onboarding:** Updated onboarding steps covering all v1.1.0 features including sync states, bundle creation, and commission workflow.
* **Technical Documentation:** Comprehensive technical documentation covering architecture, API endpoints, database schema, and developer guide.

= 1.0.0 =
* **Complete UI/UX Modernization:** Major visual overhaul with Apple-inspired design system.
* **Design System:** New CSS variable-based design system with neutral gray palette and subtle blue accents.
* **Settings Page:** Redesigned with accordion layout, compact form inputs, and improved organization.
* **Modern Tables:** All tables updated with modern striped design, rounded corners, and hover effects.
* **Dashboard:** Compact cards with improved visual hierarchy and better quick links.
* **Products Page:** Refined product cards with hover effects and modern button styles.
* **Commission & Revenue:** Polished summary cards and modern table designs.
* **Custom Orders:** Enhanced kanban board with cleaner styling.
* **Product Options:** Modernized tabs with underline style and refined tables.
* **Bundles Page:** Complete UI redesign matching design system (functionality in development).
* **Categories Page:** Modern card-based layout with improved form styling.
* **Component Library:** Comprehensive button, input, card, badge, and accordion components.
* **Responsive Design:** Fully responsive across all screen sizes with mobile optimizations.
* **Performance:** Minimal animations for instant, responsive interactions.

= 0.11.1 =
* **Sample Data Generator:** Added comprehensive sample data generator accessible from Settings page. Generates sample categories, ingredients, product options with values, and 6 sample products for testing.
* **Elementor Widget:** Added PLS Product Info widget for displaying product description, directions, skin types, and key ingredients on product pages.
* **Development Tools:** Sample data generator helps with testing and onboarding by populating realistic data based on actual product requirements.

= 0.11.0 =
* **Onboarding, Revenue & Commission Overhaul:** Complete separation of Revenue and Commission, plus comprehensive onboarding system.
* **Revenue Page:** Dedicated revenue tracking page with sales summary, orders list, charts (monthly trends, top products, revenue by tier), and comprehensive filters (date range, product, status).
* **Commission Page:** Separate commission tracking page with monthly summary view and detailed list view. Status flow: Pending → Invoiced → Paid with bulk actions.
* **Commission Auto-Tracking:** WooCommerce orders automatically create commission records when payment is received (processing/completed status).
* **Monthly Commission Email:** Automated WP Cron sends monthly commission report on 2nd of each month to n.nikolic97@gmail.com (configurable). Manual send button available.
* **Payment Notifications:** Email notifications sent to Nikola when commissions are marked as paid by owners.
* **Onboarding System:** Comprehensive tutorial system with floating guide card, per-page checklists, progress tracking, and test product creation/deletion.
* **Help Button:** "Help" button on each page shows relevant tips after onboarding completion. "Start Tutorial" button on dashboard.
* **Custom Order Commission Confirmation:** Checkbox appears when custom order stage is "Done" to manually confirm commission payment.
* **Settings Updates:** Commission email recipients configuration and onboarding reset options (per user or all users).
* **Menu Restructure:** Revenue and Commission grouped together in menu for better organization.
* **Database:** New tables for onboarding progress (`pls_onboarding_progress`) and commission reports (`pls_commission_reports`). Added `status` column to `pls_order_commission` and `commission_confirmed` to `pls_custom_order`.
* **UI/UX:** Enhanced commission page with monthly summary, detailed list, bulk actions, and improved status badges.

= 0.10.0 =
* **Client Self-Service Edition:** Complete order management and revenue tracking system.
* **Frontend Custom Order Page:** New `/custom-order` page with detailed lead capture form (name, email, phone, company, category, quantity, budget, timeline, message).
* **Custom Orders Management:** Kanban-style board with drag-and-drop to manage leads through stages (New Leads → Sampling → Production → On-hold → Done).
* **PLS Orders Screen:** View all WooCommerce orders containing PLS products with automatic commission calculation based on tier/bundle rates.
* **Revenue Tracking:** Comprehensive commission tracking with invoiced/paid status, date filters, and separate tabs for product orders and custom orders.
* **Commission System:** Per-unit rates for pack tiers and bundles, plus percentage-based commission for custom orders (configurable in Settings).
* **Settings Screen:** Centralized settings for commission rates (tiers, bundles, custom order %), plus label pricing moved from dashboard.
* **Dashboard Redesign:** Summary cards showing total products, active orders, pending custom orders, monthly revenue, and pending commission.
* **Access Control:** robertbodoci@gmail.com sees full WordPress admin, bodocibiophysics.com users see only PLS plugin and WooCommerce.
* **Database:** New tables for custom orders (`pls_custom_order`) and commission tracking (`pls_order_commission`).
* **UI Improvements:** Compact, aligned layouts throughout all admin pages with consistent spacing and modern design.

= 0.9.1 =
* **Simplified Custom Product Request:** Changed to simple contact form with category selection instead of complex form.
* **Admin Dashboard Filter:** Automatically hides all WordPress menus except PLS and WooCommerce for bodocibiophysics.com users.
* **Streamlined UX:** Custom product requests now auto-fill user name and email for faster submission.
* **Default Attributes:** Ensured comprehensive default product options are created during migration for immediate use.

= 0.9.0 =
* **Self-Service Edition:** Complete product management overhaul for client self-service without onboarding videos.
* **Separate Product Attributes:** Restructured into Package Type, Package Color, and Package Cap as separate, flexible attributes.
* **Tier-Variable Pricing:** Each option can have different prices at different tier levels (e.g., Frosted costs $2.00 at Tier 1, $1.00 at Tier 5).
* **Live Price Calculator:** Real-time price calculator in product modal showing base price + addons with tier-specific pricing.
* **Inline Validation:** Field-level validation with helpful error messages as users type.
* **Cap Compatibility:** Automatic compatibility hints based on package type (e.g., jar only supports lid).
* **Label Application Pricing:** Global setting for Tier 1-2 pricing, automatically FREE for Tier 3+.
* **Custom Product Requests:** New workflow to request custom products via form that creates WooCommerce draft orders.
* **Live Preview:** Real-time preview inside product modal showing how products will look on frontend.
* **Enhanced Preview:** Preview shows tier-variable pricing, compatibility warnings, and tier restrictions.
* **Database Migration:** Added tier_price_overrides and ingredient_category columns for flexible pricing.
* **Default Attributes:** Auto-creates Package Type (30ml, 50ml, 120ml, 50gr jar), Package Color (Clear, Frosted, Amber), and Package Cap (White/Silver options) on activation.

= 0.8.91 =
* **Product Preview System:** Added frontend preview functionality in admin to see how Elementor widgets render.
* **Preview Button:** Added "Preview Frontend" button next to each synced product in Products page.
* **Elementor Template Guide:** Created comprehensive guide for building Elementor Theme Builder templates.
* **Documentation:** Added workflow documentation for GitHub Actions and UUPD system.

= 0.8.9 =
* **Pack Tier Defaults Fix:** Added fallback defaults to Pack Tier Defaults modal to ensure values always display correctly.
* **Perfect Alignment:** Pack Tier Defaults modal now shows the same default values as product creation modal.
* **Consistent Behavior:** Both modals now use identical fallback logic for units and prices.

= 0.8.8 =
* **Product Creation Hierarchy:** Reordered product creation modal to follow hierarchical structure - Pack Tier (PRIMARY) comes before Product Options.
* **Visual Hierarchy:** Pack Tier section now clearly marked as PRIMARY with badge and distinct styling.
* **Filtered Options:** Product Options dropdown now excludes Pack Tier and ingredient attributes (only shows actual product options).
* **Better Descriptions:** Updated descriptions to reflect hierarchy and clarify that options depend on selected Pack Tier.
* **Improved UX:** Product creation flow now matches the hierarchical structure established in Product Options settings.

= 0.8.7 =
* **Bug Fix:** Fixed JavaScript error `updateTierTotal is not defined` in product creation modal.
* **Function Scope:** Moved `updateTierTotal` function to proper scope for accessibility.

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
