# Elementor Integration

## Shortcodes (Primary Method)

PLS uses **shortcodes** to render complete pages. Use these shortcodes in Elementor templates via the **Shortcode widget**.

### Available Shortcodes

#### `[pls_single_product]`
- Renders complete product page with configurator, description, ingredients, and bundles
- Auto-detects product from current WooCommerce product page
- Can specify product ID: `[pls_single_product product_id="123"]`
- Use in Elementor Single Product templates

#### `[pls_single_category]`
- Renders category/archive page with tier badges and starting prices
- Auto-detects category from current archive page
- Can specify category ID: `[pls_single_category category_id="5"]`
- Use in Elementor Archive templates

#### `[pls_shop_page]`
- Renders shop page browsing all PLS products
- Use in Elementor templates for shop pages

## Dynamic Tags

### Pack Units
- Returns units for selected pack tier
- Used in product displays
- Available in Elementor Dynamic Tags under "PLS" group

## Theme Builder Setup

1. **Use Hello Elementor theme**
2. **Create Elementor Theme Builder templates:**
   - Single Product template → Add Shortcode widget → `[pls_single_product]`
   - Product Archive template → Add Shortcode widget → `[pls_single_category]`
   - Shop template → Add Shortcode widget → `[pls_shop_page]`
3. **Assign templates** to product categories/pages
4. **Use Dynamic Tags** for pack units display if needed

## Notes

- **No custom widgets** - PLS uses shortcodes only
- **Shortcodes render complete pages** - no need for multiple widgets
- **Frontend assets** are automatically loaded when shortcodes are used
- **Dynamic tags** provide additional data for custom displays
