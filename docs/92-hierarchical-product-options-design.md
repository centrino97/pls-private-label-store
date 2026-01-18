# Hierarchical Product Options Design Document

## Overview

This document outlines the design for implementing a hierarchical product options system where **Pack Tier** serves as the **PRIMARY** product option, and all other options (including ingredients) are organized hierarchically beneath it.

## Current State Analysis

### Current Structure
- **Attributes** (`pls_attribute` table): Flat list of product attributes
- **Ingredients** (`pls_ingredient` taxonomy): Separate WordPress taxonomy, managed independently
- **Pack Tier**: Currently stored as an attribute, but should be PRIMARY/root level

### Problems with Current Approach
1. No clear hierarchy - all attributes are treated equally
2. Ingredients are separate from the attribute system, making tier-based restrictions harder
3. No visual indication that Pack Tier is the primary option
4. Difficult to understand which options depend on which tier

## Target Architecture

### Hierarchy Levels

```
Level 1 (PRIMARY): Pack Tier
├── Tier 1
├── Tier 2
├── Tier 3
├── Tier 4
└── Tier 5

Level 2 (SECONDARY): Product Options (available based on selected tier)
├── Package Type (Tier 1+)
│   ├── Glass Bottle 30ml
│   ├── Glass Bottle 50ml
│   ├── Glass Bottle 120ml
│   └── 50gr Jar
├── Package Colour (Tier 1+)
│   ├── White with White Lid & Pump
│   ├── Frosted with White Lid & Pump
│   ├── White with Silver Lid & Pump (Tier 3+)
│   └── Frosted with Silver Lid & Pump (Tier 3+)
├── Custom Printed Bottles (Tier 4+)
└── External Box Packaging (Tier 4+)

Level 2 (SPECIAL): Ingredients (available Tier 3+)
├── Ingredient 1
├── Ingredient 2
└── ...
```

### Key Concepts

1. **Pack Tier is PRIMARY**: It's the root option that determines availability of all other options
2. **Product Options are SECONDARY**: Regular options like Package Type, Package Colour, etc.
3. **Ingredients are SPECIAL OPTIONS**: Treated as attributes but only available from Tier 3+
4. **Tier-Based Availability**: Each option value has `min_tier_level` that controls when it becomes available

## Database Schema Changes

### 1. Add Hierarchy Support to `pls_attribute` Table

```sql
ALTER TABLE wp_pls_attribute
ADD COLUMN parent_attribute_id BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER id,
ADD COLUMN option_type VARCHAR(50) NOT NULL DEFAULT 'product-option' AFTER label,
ADD COLUMN is_primary TINYINT(1) NOT NULL DEFAULT 0 AFTER option_type,
ADD KEY parent_attribute_id (parent_attribute_id),
ADD KEY option_type (option_type),
ADD KEY is_primary (is_primary);
```

**New Columns:**
- `parent_attribute_id`: NULL for root (Pack Tier), attribute ID for children
- `option_type`: `'pack-tier'` | `'product-option'` | `'ingredient'`
- `is_primary`: 1 for Pack Tier (only one can be primary), 0 for others

### 2. Migrate Ingredients to Attribute System

**Option A: Keep Ingredients as Taxonomy, Link to Attributes**
- Keep `pls_ingredient` taxonomy for backward compatibility
- Create corresponding attribute entries with `option_type = 'ingredient'`
- Sync between taxonomy and attribute system

**Option B: Full Migration to Attributes**
- Migrate all ingredients to `pls_attribute` with `option_type = 'ingredient'`
- Keep taxonomy for WooCommerce compatibility
- Use attribute system as source of truth

**Recommended: Option A** (hybrid approach maintains compatibility)

### 3. Update `pls_attribute_value` Table

No schema changes needed - existing `min_tier_level` column already supports tier-based restrictions.

## Data Model Relationships

```
pls_attribute (Root: Pack Tier)
├── id: 1, option_type: 'pack-tier', is_primary: 1, parent_attribute_id: NULL
└── pls_attribute_value
    ├── Tier 1 (min_tier_level: 1)
    ├── Tier 2 (min_tier_level: 2)
    ├── Tier 3 (min_tier_level: 3)
    ├── Tier 4 (min_tier_level: 4)
    └── Tier 5 (min_tier_level: 5)

pls_attribute (Child: Package Type)
├── id: 2, option_type: 'product-option', is_primary: 0, parent_attribute_id: NULL
└── pls_attribute_value
    ├── Glass Bottle 30ml (min_tier_level: 1)
    ├── Glass Bottle 50ml (min_tier_level: 1)
    └── ...

pls_attribute (Child: Ingredients)
├── id: 3, option_type: 'ingredient', is_primary: 0, parent_attribute_id: NULL
└── pls_attribute_value
    ├── Ingredient 1 (min_tier_level: 3)
    ├── Ingredient 2 (min_tier_level: 3)
    └── ...
```

**Note**: For now, all options are siblings (parent_attribute_id = NULL) except Pack Tier is marked as PRIMARY. Future enhancements could add true parent-child relationships if needed.

## UI/UX Changes

### Admin Interface Structure

```
Product Options (Main Menu)
├── Pack Tier (PRIMARY) [Collapsed by default, always visible]
│   └── Tier 1, Tier 2, Tier 3, Tier 4, Tier 5
├── Product Options [Expandable section]
│   ├── Package Type
│   ├── Package Colour
│   ├── Custom Printed Bottles
│   └── External Box Packaging
└── Ingredients [Expandable section, Tier 3+ indicator]
    ├── Ingredient 1
    ├── Ingredient 2
    └── ...
```

### Visual Hierarchy Indicators

1. **Pack Tier Section**:
   - Badge: "PRIMARY OPTION"
   - Always expanded/visible
   - Distinct styling (e.g., border, background color)

2. **Product Options Section**:
   - Badge: "PRODUCT OPTIONS"
   - Collapsible section
   - Shows tier availability badges on values

3. **Ingredients Section**:
   - Badge: "INGREDIENTS (Tier 3+)"
   - Collapsible section
   - Clear indication that these are only available from Tier 3+

### Table Layout

```
┌─────────────────────────────────────────────────────────────┐
│ Product Options                                             │
├─────────────────────────────────────────────────────────────┤
│ PRIMARY: Pack Tier                                          │
│ ┌─────────┬──────────┬─────────┬──────────┬─────────────┐ │
│ │ Tier    │ Units    │ Price   │ Actions  │             │ │
│ ├─────────┼──────────┼─────────┼──────────┼─────────────┤ │
│ │ Tier 1  │ 50       │ $X.XX   │ [Edit]   │             │ │
│ │ Tier 2  │ 100      │ $X.XX   │ [Edit]   │             │ │
│ └─────────┴──────────┴─────────┴──────────┴─────────────┘ │
│                                                             │
│ PRODUCT OPTIONS                                             │
│ ┌─────────────────┬──────────────┬──────────┬────────────┐ │
│ │ Option Name     │ Values       │ Tier     │ Actions    │ │
│ ├─────────────────┼──────────────┼──────────┼────────────┤ │
│ │ Package Type    │ 4 values     │ Tier 1+  │ [Edit]     │ │
│ │ Package Colour  │ 4 values     │ Tier 1+  │ [Edit]     │ │
│ └─────────────────┴──────────────┴──────────┴────────────┘ │
│                                                             │
│ INGREDIENTS (Tier 3+)                                       │
│ ┌─────────────────┬──────────────┬──────────┬────────────┐ │
│ │ Ingredient Name │ Icon         │ Tier     │ Actions    │ │
│ ├─────────────────┼──────────────┼──────────┼────────────┤ │
│ │ Ingredient 1    │ [Icon]       │ Tier 3+  │ [Edit]     │ │
│ └─────────────────┴──────────────┴──────────┴────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Implementation Strategy

### Phase 1: Database Schema Updates
1. Add `parent_attribute_id`, `option_type`, `is_primary` columns
2. Create migration script to:
   - Mark Pack Tier attribute as PRIMARY
   - Set `option_type` for all existing attributes
   - Set `option_type = 'product-option'` for non-tier attributes

### Phase 2: Ingredient Integration
1. Create sync mechanism between `pls_ingredient` taxonomy and `pls_attribute` table
2. When ingredient is created/updated, create/update corresponding attribute entry
3. Set `option_type = 'ingredient'` and `min_tier_level = 3` for all ingredient values

### Phase 3: Repository Updates
1. Update `PLS_Repo_Attributes` to support:
   - Filtering by `option_type`
   - Filtering by `is_primary`
   - Getting children of a parent attribute
   - Getting root/primary attribute

### Phase 4: UI Updates
1. Redesign admin screen to show hierarchy
2. Separate sections for Pack Tier, Product Options, Ingredients
3. Update forms to handle `option_type` and `parent_attribute_id`
4. Add visual indicators for PRIMARY option and tier restrictions

### Phase 5: WooCommerce Sync Updates
1. Ensure Pack Tier syncs correctly as primary variation attribute
2. Sync ingredients as non-variation attributes (for display/filtering)
3. Maintain backward compatibility with existing products

## Business Rules

### Pack Tier (PRIMARY)
- **Only ONE** attribute can be marked as PRIMARY (`is_primary = 1`)
- Pack Tier determines availability of all other options
- Pack Tier values determine tier level (1-5)
- Pack Tier is ALWAYS a variation attribute (`is_variation = 1`)

### Product Options
- Can be variation or non-variation attributes
- Availability controlled by `min_tier_level` on values
- Can have tier-specific pricing via `tier_price_overrides`

### Ingredients
- Always `option_type = 'ingredient'`
- Always `min_tier_level >= 3` (only available Tier 3+)
- Typically non-variation attributes (for display/filtering)
- Linked to `pls_ingredient` taxonomy for backward compatibility

## Migration Considerations

### Backward Compatibility
1. Existing attributes continue to work
2. Ingredients taxonomy remains functional
3. Existing products/variations remain valid
4. Gradual migration - new features use new structure

### Data Migration Steps
1. Identify Pack Tier attribute (by `attr_key = 'pack-tier'` or option)
2. Set `is_primary = 1` and `option_type = 'pack-tier'`
3. Set `option_type = 'product-option'` for all other attributes
4. For each ingredient in taxonomy:
   - Create/update attribute entry with `option_type = 'ingredient'`
   - Create/update attribute values with `min_tier_level = 3`
   - Link via term_id

## Testing Checklist

- [ ] Pack Tier marked as PRIMARY and displays correctly
- [ ] Product Options section shows all non-primary, non-ingredient attributes
- [ ] Ingredients section shows all ingredient-type attributes
- [ ] Tier-based filtering works correctly
- [ ] Ingredient creation/update syncs to attribute system
- [ ] WooCommerce sync maintains correct attribute hierarchy
- [ ] Existing products continue to work
- [ ] Migration script runs without errors
- [ ] UI is responsive and intuitive

## Future Enhancements

1. **True Parent-Child Relationships**: Allow options to have parent options (e.g., "Bottle Options" → "Size", "Color")
2. **Conditional Options**: Options that only appear when specific parent option values are selected
3. **Option Groups**: Visual grouping of related options
4. **Option Templates**: Pre-configured option sets for different product categories

## References

- [E-commerce Attribute Database Design Best Practices](https://www.allstarsit.com/blog/ecommerce-product-attributes-database-design-best-practices-patterns)
- [WooCommerce Hierarchical Attributes](https://stackoverflow.com/questions/44271444/woocommerce-product-attributes-with-hierarchy-like-categories)
- Current Plugin Documentation: `docs/10-data-model.md`, `docs/20-wc-mapping.md`
