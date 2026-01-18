# WooCommerce Mapping

## Base product -> Variable product
Each PLS base product should sync into:
- one Woo variable product
- configured categories + attributes

## Pack tier -> Variation
Each enabled pack tier should become one variation:
- attribute `pa_pack-tier` = tier term
- price set from PLS
- meta: units (and optional SKU pattern)

## Bundles (v1.1.0+)

**Bundle → Grouped Product:**
- Each PLS bundle syncs to one WooCommerce Grouped Product
- Grouped product links to child variable products (if bundle items defined)
- Bundle metadata stored in product meta:
  - `_pls_bundle_id` - PLS bundle ID
  - `_pls_bundle_key` - Bundle key identifier
  - `_pls_bundle_rules` - Bundle rules JSON

**Cart Detection:**
- Implemented in `includes/wc/class-pls-bundle-cart.php`
- Hooks into `woocommerce_before_calculate_totals`
- Automatically detects when cart qualifies for bundle pricing
- Applies bundle pricing and stores bundle info in cart item data

**Bundle Qualification Logic:**
1. Count distinct PLS products in cart
2. Check if quantities match bundle criteria (e.g., 2 products × 250 units each)
3. Apply bundle pricing automatically
4. Show bundle discount notice

**Customer Choice:**
- Bundles are customer-choice (customer picks which products to include)
- No pre-defined product combinations required
- Cart detection handles dynamic product selection
