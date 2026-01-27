# PLS Quick Reference Guide

Quick reference for common tasks in the PLS system.

## Navigation

- **Dashboard**: Overview of products, orders, revenue, commissions
- **Products**: Manage all products
- **Bundles**: Create and manage product bundles
- **Custom Orders**: Kanban board for custom order leads
- **Orders**: View WooCommerce orders
- **Categories**: Manage product categories
- **Product Options**: Configure product attributes/options
- **Ingredients**: Manage ingredient taxonomy
- **Commission**: View commission records
- **Revenue**: Revenue tracking and reports
- **Settings**: System configuration
- **System Test**: Run diagnostics and generate sample data

## Product Management

### Create Product
1. Products → Create Product
2. Enter name, slug, status, categories
3. Configure pack tiers (5 tiers: 50, 100, 250, 500, 1000 units)
4. Set up product profile (description, images, options, ingredients)
5. Save → Sync to WooCommerce

### Edit Product
1. Products → Find product → Edit
2. Make changes
3. Save → Sync

### Sync Product
- Products → Click "Sync" button next to product
- Or: Edit product → Save → Sync

### Preview Product
- Products → Click "Preview" button
- Shows frontend display

## Pack Tiers

| Tier | Units | Typical Price Range |
|------|-------|---------------------|
| Tier 1 | 50 | $15-16 per unit |
| Tier 2 | 100 | $14-15 per unit |
| Tier 3 | 250 | $12-13 per unit |
| Tier 4 | 500 | $9-10 per unit |
| Tier 5 | 1000 | $7-8 per unit |

**Note**: Prices are per unit. Enable/disable tiers as needed.

## Product Options

### Available Options
- **Package Type**: 30ml Bottle, 50ml Bottle, 120ml Bottle, 50gr Jar
- **Package Color**: Standard White, Standard Frosted, Amber
- **Package Cap**: White Pump, Silver Pump, Lid (jars only)
- **Fragrances**: Various fragrance options

### Adding Option Values
1. Product Options → Find option → Click row
2. Click "Add Value"
3. Enter name, configure pricing
4. Save

## Bundles

### Bundle Types
- **Mini Line**: 2 SKUs
- **Starter Line**: 3 SKUs
- **Growth Line**: 4 SKUs
- **Premium Line**: 6 SKUs

### Create Bundle
1. Bundles → Create Bundle
2. Enter bundle name, key, status
3. Configure bundle rules (SKU count, units per SKU, pricing)
4. Add bundle items (products + tiers)
5. Save → Sync

## Custom Orders

### Kanban Stages
1. **New Lead**: Initial inquiry
2. **Sampling**: Sample stage
3. **Production**: In production
4. **Done**: Completed

### Create Custom Order
1. Custom Orders → Create Order
2. Fill in customer details
3. Add products and quantities
4. Set production cost, total value
5. Move through stages as work progresses

## Commissions

### Commission Rates
- **Tier 1**: 80%
- **Tier 2**: 75%
- **Tier 3**: 65%
- **Tier 4**: 40%
- **Tier 5**: 29%

### Bundle Commissions
- **Mini Line**: 59%
- **Starter Line**: 49%
- **Growth Line**: 32%
- **Premium Line**: 25%

### Custom Order Commissions
- **Below $100k**: 3%
- **Above $100k**: 5%

## Keyboard Shortcuts

- **Ctrl+S**: Save (in modals)
- **Esc**: Close modal
- **?**: Open help

## Common Issues & Solutions

### Product Won't Sync
- Check at least one pack tier is enabled
- Verify product has name and slug
- Try re-syncing

### Variations Missing
- Ensure pack tiers are enabled
- Check tier has units and price
- Re-sync product

### Product Not on Frontend
- Verify status is "Live"
- Check sync completed
- Ensure WooCommerce product is published

### Images Not Showing
- Check images uploaded to media library
- Verify featured image is set
- Clear browser cache

## Status Indicators

- **Live**: Product is published and visible
- **Draft**: Product is saved but not visible
- **Synced**: Product synced to WooCommerce
- **Not Synced**: Needs sync to WooCommerce
- **Update Available**: Changes need re-sync

## Help & Support

- **Help Button**: Click (?) on any page for detailed guides
- **System Test**: Run diagnostics at System Test page
- **Documentation**: See docs/ folder for detailed guides

## Important Notes

- Always sync after making changes
- Preview before going live
- Start with draft status
- Complete product profile for best results
- Test pack tiers before activating
