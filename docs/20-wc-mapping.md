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

## Bundles
Phase 1 recommended:
- bundle is a simple product
- adding it to cart injects the child line items (pack variations)
- children grouped with cart_item_data parent id

Implement in: `includes/wc/class-pls-wc-sync.php` and a bundle-cart handler (future file).
