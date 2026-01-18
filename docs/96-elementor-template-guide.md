# Elementor Template Building Guide

## Overview

This guide explains how to build Elementor Theme Builder templates for PLS products using the preview system and Elementor widgets.

---

## Step 1: Preview Products in Admin

Before building templates, preview how widgets will render:

1. **Go to:** PLS → Products
2. **Sync a product** (if not already synced)
3. **Click "Preview Frontend"** button next to any synced product
4. **Review the preview** to see:
   - How PLS Configurator widget renders
   - Available product attributes
   - Product data structure

**Note:** The preview simulates the frontend environment, so you can see exactly how widgets will appear.

---

## Step 2: Access Elementor Theme Builder

1. **Go to:** Elementor → Theme Builder
2. **Click:** "Add New" → "Single Product"
3. **Select:** "All Products" or specific conditions
4. **Click:** "Create Template"

---

## Step 3: Build Your Template

### Essential Widgets to Add:

#### 1. **Product Title**
- Elementor → WooCommerce → Product Title
- Shows the product name

#### 2. **Product Images**
- Elementor → WooCommerce → Product Images
- Shows featured image and gallery

#### 3. **Product Price**
- Elementor → WooCommerce → Product Price
- Shows price with variation support

#### 4. **PLS Configurator** ⭐
- Elementor → PLS → PLS Configurator
- **This is the key widget!** It shows:
  - Pack tier selection
  - Product option swatches
  - Variation configuration

**Widget Settings:**
- **Show pack tiers:** Yes/No
- **Show swatches:** Yes/No

#### 5. **Product Description**
- Elementor → WooCommerce → Product Description
- Shows short and long descriptions

#### 6. **Product Meta**
- Elementor → WooCommerce → Product Meta
- Shows SKU, categories, tags

#### 7. **Add to Cart**
- Elementor → WooCommerce → Add to Cart
- Shows add to cart button with variation support

---

## Step 4: Use Dynamic Tags

Elementor Dynamic Tags allow you to display PLS-specific data:

### Available PLS Dynamic Tags:

1. **Pack Units**
   - Group: PLS
   - Tag: Pack Units
   - Shows: Selected pack tier units (e.g., "50 units", "100 units")

### How to Use:

1. **Select a text widget** (Heading, Text Editor, etc.)
2. **Click the dynamic icon** (⚡) in the content field
3. **Select:** PLS → Pack Units
4. **The tag will update** based on selected variation

---

## Step 5: Styling & Layout

### Recommended Layout:

```
┌─────────────────────────────────┐
│  Product Images (Left)          │
│                                 │
│  ┌───────────────────────────┐ │
│  │ Product Title              │ │
│  │ Product Price              │ │
│  │ PLS Configurator Widget    │ │ ← Key widget!
│  │ Product Description        │ │
│  │ Add to Cart                │ │
│  └───────────────────────────┘ │
└─────────────────────────────────┘
```

### Styling Tips:

1. **Use Elementor's styling controls** for colors, spacing, typography
2. **Make PLS Configurator prominent** - it's the main customization tool
3. **Ensure responsive design** - test on mobile/tablet
4. **Match your brand** - use consistent colors and fonts

---

## Step 6: Publish Template

1. **Click:** "Publish" in Elementor
2. **Set conditions:**
   - All Products (recommended)
   - Or specific categories/products
3. **Save**

---

## Step 7: Test on Frontend

1. **Go to:** Your WooCommerce shop
2. **Click:** A synced PLS product
3. **Verify:**
   - Template loads correctly
   - PLS Configurator widget works
   - Pack tiers display
   - Variations update correctly
   - Add to cart works

---

## Troubleshooting

### Widget Not Showing

**Problem:** PLS Configurator widget not visible in Elementor

**Solution:**
- Ensure plugin is activated
- Check Elementor → Settings → Advanced → Enable Elementor Widgets
- Clear cache

### Preview Not Working

**Problem:** Preview page shows errors

**Solution:**
- Ensure product is synced to WooCommerce
- Check that WooCommerce is active
- Verify product is set as "Variable" type

### Variations Not Updating

**Problem:** Selecting pack tier doesn't update price/variation

**Solution:**
- Ensure product has pack tier attribute
- Check that variations are created
- Verify WooCommerce variation form is present

---

## Advanced: Custom Widgets

If you need custom functionality, you can create additional Elementor widgets:

1. **Create widget file:** `includes/elementor/widgets/class-pls-widget-yourname.php`
2. **Extend:** `\Elementor\Widget_Base`
3. **Register:** In `includes/elementor/class-pls-elementor.php`

See existing widgets for reference:
- `class-pls-widget-configurator.php`
- `class-pls-widget-bundle-offer.php`

---

## Best Practices

1. **Always preview first** - Use admin preview before building templates
2. **Test variations** - Ensure all pack tiers work correctly
3. **Mobile responsive** - Test on all device sizes
4. **Performance** - Keep template lightweight
5. **User experience** - Make customization intuitive

---

## Quick Reference

- **Preview:** PLS → Products → "Preview Frontend" button
- **Theme Builder:** Elementor → Theme Builder → Single Product
- **Key Widget:** PLS Configurator (shows pack tiers & options)
- **Dynamic Tags:** PLS → Pack Units
- **Template Type:** Single Product

---

## Related Documentation

- `docs/30-elementor-integration.md` - Technical integration details
- `includes/elementor/widgets/` - Widget source code
- `includes/elementor/dynamic-tags/` - Dynamic tag source code
