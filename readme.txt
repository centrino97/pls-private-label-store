=== PLS – Private Label Store Manager (Woo + Elementor) ===
Contributors: yourteam
Tags: woocommerce, elementor, bundles, swatches
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.7.1
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
= 0.7.1 =
* Development release with improved update system.

= 0.7.0 =
* Cleans up Key ingredients with a single scrollable selector, live counter, and enforced five-item cap tied to selected ingredients.
* Simplifies attribute selection to dropdown + reliable value multi-select with default impact autofill and per-product overrides.
* Adds a unified Manage attributes & values modal for creating attributes/values and editing default price impacts without stacking modals.
* Hardens modal targeting so only the intended dialog opens when adding products or managing ingredients/attributes.
