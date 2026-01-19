# Data Model

## Core Entities

### Base Product
- Stores product name, slug, category, status
- Linked to WooCommerce product via `wc_product_id`
- Has multiple pack tiers (variations)
- Has product profile (description, images, options)

### Pack Tier
- Defines units and price for a tier level
- Maps to WooCommerce variation
- Tier levels: 1-5 (50, 100, 250, 500, 1000 units)

### Bundle
- Defines bundle type (mini_line, starter_line, growth_line, premium_line)
- Stores SKU count, units per SKU, pricing
- Synced to WooCommerce as Grouped Product
- Cart automatically detects bundle qualification

### Custom Order
- Lead capture from frontend form
- Kanban stages: new_lead → sampling → production → done
- Financial tracking: production_cost, total_value, commission

### Commission
- Linked to WooCommerce orders or custom orders
- Tracks units sold, commission rate, amount
- Status flow: pending → invoiced → paid

### Attribute & Attribute Value
- Product options (Package Type, Color, Cap, Fragrances)
- Tier-based pricing rules
- Tier restrictions (min_tier_level)

## Relationships

```
Base Product
├── Pack Tiers (1:N)
├── Product Profile (1:1)
├── Attributes (M:N via basics_json)
└── Ingredients (M:N via taxonomy)

Bundle
├── Bundle Items (1:N) - optional
└── WooCommerce Grouped Product (1:1)

WooCommerce Order
└── Commissions (1:N)

Custom Order
└── Commission (1:1) - when status is "done"
```
