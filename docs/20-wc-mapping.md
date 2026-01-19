# WooCommerce Mapping

## Product Sync

### Base Product → Variable Product
- Each PLS base product syncs to one WooCommerce variable product
- Product status: `live` → `publish`, `draft` → `draft`
- Categories synced from PLS category_path

### Pack Tier → Variation
- Each enabled pack tier becomes one WooCommerce variation
- Attribute: `pa_pack-tier` with tier term
- Price set from PLS pack tier price
- Meta: `_pls_units` stores units for commission calculation

### Bundle → Grouped Product
- Each PLS bundle syncs to one WooCommerce Grouped Product
- Bundle metadata stored in product meta:
  - `_pls_bundle_id` - PLS bundle ID
  - `_pls_bundle_key` - Bundle key identifier
  - `_pls_bundle_rules` - Bundle rules JSON

## Cart Detection

- Cart automatically detects when products qualify for bundle pricing
- Checks total units per product across cart items
- Applies bundle pricing and adds meta to cart items
- Meta preserved through checkout to order items

## Commission Tracking

- Commission calculated when order status changes to `processing` or `completed`
- Reads order item meta for bundle information
- Calculates commission based on tier rates or bundle rates
- Stores commission records in `pls_order_commission` table
