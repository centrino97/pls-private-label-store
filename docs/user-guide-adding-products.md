# PLS User Guide: Adding Products

This guide will walk you through the complete process of adding a new product to the PLS system.

## Prerequisites

Before adding a product, ensure you have:
- Access to PLS admin panel
- Product information ready (name, description, images, etc.)
- Pack tier pricing information
- Product category selected
- Ingredients list (if applicable)

## Step-by-Step Process

### Step 1: Navigate to Products Page

1. Log in to WordPress admin
2. Click on **PLS** in the left sidebar menu
3. Click on **Products** from the PLS submenu
4. You'll see the products list page

### Step 2: Create New Product

1. Click the **"Create Product"** button (top right)
2. A modal will open with the product creation form

### Step 3: Enter Basic Information

Fill in the following required fields:

- **Product Name**: Enter the full product name (e.g., "Hydrating Face Cleanser")
- **Slug**: Auto-generated from name, but can be edited (e.g., "hydrating-face-cleanser")
- **Status**: Choose "Draft" (not visible to customers) or "Live" (published)
- **Categories**: Select one or more product categories from the dropdown

### Step 4: Configure Pack Tiers

Pack tiers determine pricing and available features. Configure all 5 tiers:

1. **Tier 1** (50 units):
   - Check "Enabled" if this tier should be available
   - Enter "Units": 50
   - Enter "Price per unit": e.g., $15.90

2. **Tier 2** (100 units):
   - Check "Enabled"
   - Enter "Units": 100
   - Enter "Price per unit": e.g., $14.50

3. **Tier 3** (250 units):
   - Check "Enabled"
   - Enter "Units": 250
   - Enter "Price per unit": e.g., $12.50

4. **Tier 4** (500 units):
   - Check "Enabled"
   - Enter "Units": 500
   - Enter "Price per unit": e.g., $9.50

5. **Tier 5** (1000 units):
   - Check "Enabled"
   - Enter "Units": 1000
   - Enter "Price per unit": e.g., $7.90

**Note**: You can enable/disable tiers as needed. Only enabled tiers will sync to WooCommerce.

### Step 5: Set Up Product Profile

The product profile contains detailed information about your product:

#### Basic Information
- **Short Description**: Brief product description (appears in product listings)
- **Long Description**: Detailed product description (appears on product page)
- **Directions**: How to use the product
- **Skin Types**: Suitable skin types (e.g., "All skin types", "Oily", "Dry")

#### Images
- **Featured Image**: Main product image (click "Set featured image" to upload/select)
- **Gallery Images**: Additional product images (click "Add gallery images" to upload/select multiple)

#### Ingredients
- Click "Add Ingredients" to search and select ingredients
- Mark key ingredients with the star icon (★)
- Ingredients will display on the frontend product page

#### Product Options
- **Package Type**: Select available package types (30ml Bottle, 50ml Bottle, etc.)
- **Package Color**: Select available colors (Standard White, Amber, etc.)
- **Package Cap**: Select cap type (White Pump, Silver Pump, Lid)
- **Fragrances**: Select available fragrances (if applicable)

**Note**: Product options may have tier-based pricing. Higher tiers may unlock additional options.

### Step 6: Save Product

1. Review all information
2. Click **"Save Product"** button
3. The product will be saved to the PLS database

### Step 7: Sync to WooCommerce

After saving, you need to sync the product to WooCommerce:

1. Find your product in the products list
2. Click the **"Sync"** button next to the product
3. Wait for sync to complete (you'll see a success message)
4. The product will now appear in WooCommerce

**What happens during sync:**
- A WooCommerce variable product is created
- Each enabled pack tier becomes a variation
- Product categories are synced
- Product attributes (options) are synced
- Variations are linked to pack tiers

### Step 8: Preview Product

Before making the product live, preview how it will appear:

1. Click the **"Preview"** button next to your product
2. A preview window will open showing the frontend display
3. Review all information, images, and options
4. Close preview when done

### Step 9: Activate Product

Once everything looks good:

1. Edit the product
2. Change status from "Draft" to "Live"
3. Save and sync again
4. The product is now visible to customers

## Editing an Existing Product

1. Go to Products page
2. Find the product in the list
3. Click **"Edit"** button
4. Make your changes
5. Click **"Save Product"**
6. Click **"Sync"** to update WooCommerce

## Common Tasks

### Adding Product Options

1. Go to **Product Options** page (PLS → Product Options)
2. Find the option you want to add (e.g., "Package Type")
3. Click on the option row
4. Click **"Add Value"**
5. Enter value name and configure pricing
6. Click **"Save"**
7. Sync attributes to WooCommerce if needed

### Assigning Ingredients

1. Go to **Ingredients** page (PLS → Ingredients)
2. Add new ingredients if needed
3. When editing a product, use "Add Ingredients" to assign them
4. Mark key ingredients with the star icon

### Managing Categories

1. Go to **Categories** page (PLS → Categories)
2. Add/edit categories as needed
3. Assign categories to products when creating/editing

## Troubleshooting

### Product Not Syncing

- Check that at least one pack tier is enabled
- Verify product has a name and slug
- Check WooCommerce is active
- Try syncing again (sync is idempotent - safe to retry)

### Variations Not Appearing

- Ensure pack tiers are enabled
- Check that pack tier has units and price set
- Verify sync completed successfully
- Try re-syncing the product

### Product Not Showing on Frontend

- Verify product status is "Live"
- Check that product is synced to WooCommerce
- Ensure WooCommerce product is published
- Verify Elementor templates are set up correctly

### Images Not Displaying

- Check that images are uploaded to WordPress media library
- Verify featured image is set
- Ensure image file sizes are reasonable (< 5MB recommended)
- Clear browser cache

## Best Practices

1. **Always preview before going live**: Use the preview feature to check everything
2. **Start with draft status**: Create products as drafts, then activate when ready
3. **Complete product profile**: Fill in all fields for best customer experience
4. **Use high-quality images**: Product images should be clear and professional
5. **Test pack tiers**: Verify all enabled tiers have correct pricing
6. **Sync after changes**: Always sync after making changes to ensure WooCommerce is updated

## Quick Reference

- **Create Product**: Products → Create Product
- **Edit Product**: Products → Click Edit
- **Sync Product**: Products → Click Sync
- **Preview Product**: Products → Click Preview
- **Manage Options**: Product Options → Add/Edit Values
- **Manage Ingredients**: Ingredients → Add/Assign
- **Manage Categories**: Categories → Add/Edit

## Need Help?

- Click the **Help (?)** button on any PLS page for detailed guides
- Check the System Test page for diagnostics
- Contact administrator for technical issues
