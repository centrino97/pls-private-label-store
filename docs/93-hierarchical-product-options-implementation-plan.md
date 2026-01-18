# Hierarchical Product Options - Implementation Plan

## Overview

This plan implements a hierarchical product options system where **Pack Tier** is the PRIMARY option, and all other options (including ingredients) are organized hierarchically. This ensures 100% success by breaking down the work into clear, testable phases.

## Success Criteria

✅ Pack Tier is clearly marked as PRIMARY option  
✅ Product Options are separated from Pack Tier  
✅ Ingredients are integrated as special options (Tier 3+)  
✅ UI clearly shows hierarchy and tier-based availability  
✅ All existing functionality continues to work  
✅ Migration is seamless and reversible  

## Phase 1: Database Schema Updates

### 1.1 Create Migration Class
**File**: `includes/core/class-pls-migration-v083.php` (new)

**Tasks**:
- [ ] Create migration class following pattern from `class-pls-migration-v080.php`
- [ ] Add `maybe_migrate()` static method
- [ ] Check version before running migration
- [ ] Add rollback capability (optional but recommended)

**SQL Changes**:
```sql
ALTER TABLE wp_pls_attribute
ADD COLUMN parent_attribute_id BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER id,
ADD COLUMN option_type VARCHAR(50) NOT NULL DEFAULT 'product-option' AFTER label,
ADD COLUMN is_primary TINYINT(1) NOT NULL DEFAULT 0 AFTER option_type,
ADD KEY parent_attribute_id (parent_attribute_id),
ADD KEY option_type (option_type),
ADD KEY is_primary (is_primary);
```

**Migration Logic**:
1. Add new columns with safe defaults
2. Identify Pack Tier attribute (`attr_key = 'pack-tier'` or via option `pls_pack_tier_attribute_id`)
3. Set `is_primary = 1` and `option_type = 'pack-tier'` for Pack Tier
4. Set `option_type = 'product-option'` for all other existing attributes
5. Set `parent_attribute_id = NULL` for all (can be enhanced later)

**Testing**:
- [ ] Migration runs without errors on fresh install
- [ ] Migration runs without errors on existing install
- [ ] Existing attributes remain functional
- [ ] Pack Tier is correctly marked as primary

### 1.2 Update Activator
**File**: `includes/core/class-pls-activator.php`

**Tasks**:
- [ ] Update table creation SQL to include new columns
- [ ] Add migration call for v0.8.3 in `maybe_run_migrations()`

**Changes**:
```php
$tables[] = "CREATE TABLE {$p}pls_attribute (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_attribute_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
    wc_attribute_id BIGINT(20) UNSIGNED NULL,
    attr_key VARCHAR(100) NOT NULL,
    label VARCHAR(255) NOT NULL,
    option_type VARCHAR(50) NOT NULL DEFAULT 'product-option',
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    is_variation TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY attr_key (attr_key),
    KEY parent_attribute_id (parent_attribute_id),
    KEY wc_attribute_id (wc_attribute_id),
    KEY option_type (option_type),
    KEY is_primary (is_primary),
    KEY is_variation (is_variation)
) $charset_collate;";
```

**Testing**:
- [ ] Fresh install creates table with new columns
- [ ] Default attributes are created with correct `option_type`

## Phase 2: Ingredient-Attribute Sync System

### 2.1 Create Ingredient Sync Class
**File**: `includes/core/class-pls-ingredient-sync.php` (new)

**Purpose**: Keep `pls_ingredient` taxonomy and `pls_attribute` table in sync

**Methods**:
- `sync_ingredient_to_attribute($term_id)`: Create/update attribute entry for ingredient
- `sync_attribute_to_ingredient($attribute_id)`: Create/update taxonomy term for attribute
- `sync_all_ingredients()`: Initial sync of all existing ingredients
- `on_ingredient_created($term_id)`: Hook handler for ingredient creation
- `on_ingredient_updated($term_id)`: Hook handler for ingredient updates
- `on_ingredient_deleted($term_id)`: Hook handler for ingredient deletion

**Logic**:
1. When ingredient created → create attribute with `option_type = 'ingredient'`
2. When ingredient updated → update corresponding attribute
3. When ingredient deleted → delete corresponding attribute (or mark as deleted)
4. Link via `term_id` in `pls_attribute_value` table
5. Set `min_tier_level = 3` for all ingredient values

**Testing**:
- [ ] Creating ingredient creates attribute entry
- [ ] Updating ingredient updates attribute entry
- [ ] Deleting ingredient removes attribute entry
- [ ] All ingredient values have `min_tier_level = 3`

### 2.2 Hook Ingredient Sync
**File**: `includes/core/class-pls-taxonomies.php` or `includes/class-pls-plugin.php`

**Tasks**:
- [ ] Add hooks for ingredient CRUD operations
- [ ] Initialize sync class
- [ ] Run initial sync on activation/upgrade

**Hooks**:
```php
add_action('created_pls_ingredient', array('PLS_Ingredient_Sync', 'on_ingredient_created'));
add_action('edited_pls_ingredient', array('PLS_Ingredient_Sync', 'on_ingredient_updated'));
add_action('delete_pls_ingredient', array('PLS_Ingredient_Sync', 'on_ingredient_deleted'));
```

**Testing**:
- [ ] Hooks fire correctly
- [ ] Sync happens automatically
- [ ] No duplicate entries created

### 2.3 Update Default Attributes
**File**: `includes/core/class-pls-default-attributes.php`

**Tasks**:
- [ ] Ensure Pack Tier is created with `option_type = 'pack-tier'` and `is_primary = 1`
- [ ] Ensure other attributes are created with `option_type = 'product-option'`
- [ ] Add method to sync existing ingredients on first run

**Testing**:
- [ ] Pack Tier created correctly
- [ ] Other attributes created correctly
- [ ] Existing ingredients synced on upgrade

## Phase 3: Repository Updates

### 3.1 Extend PLS_Repo_Attributes
**File**: `includes/data/repo-attributes.php`

**New Methods**:
- `get_primary_attribute()`: Get the attribute marked as primary
- `get_attributes_by_type($type)`: Get attributes filtered by `option_type`
- `get_product_options()`: Get all `option_type = 'product-option'` attributes
- `get_ingredient_attributes()`: Get all `option_type = 'ingredient'` attributes
- `get_child_attributes($parent_id)`: Get attributes with specific parent (for future use)
- `set_primary_attribute($attribute_id)`: Set an attribute as primary (unset others)

**Updates to Existing Methods**:
- `insert_attr()`: Accept `option_type`, `is_primary`, `parent_attribute_id` parameters
- `attrs_all()`: Optionally filter by `option_type` or `is_primary`

**Testing**:
- [ ] New methods return correct data
- [ ] Existing methods still work
- [ ] Setting primary attribute unsets previous primary

### 3.2 Update Admin AJAX
**File**: `includes/admin/class-pls-admin-ajax.php`

**Tasks**:
- [ ] Update `create_attribute()` to handle `option_type` and `is_primary`
- [ ] Update `create_attribute_value()` to handle ingredient-specific logic
- [ ] Add validation: only one primary attribute allowed
- [ ] Update `format_product_payload()` to include option type info

**Testing**:
- [ ] Creating attribute with `option_type` works
- [ ] Cannot set multiple primary attributes
- [ ] Ingredient values default to `min_tier_level = 3`

## Phase 4: UI Redesign

### 4.1 Update Admin Screen Structure
**File**: `includes/admin/screens/attributes.php`

**New Structure**:
```
┌─────────────────────────────────────────┐
│ Product Options                         │
├─────────────────────────────────────────┤
│ PRIMARY: Pack Tier                      │
│ [Table with Tier 1-5]                  │
│                                         │
│ PRODUCT OPTIONS                         │
│ [Table with Package Type, Colour, etc]  │
│                                         │
│ INGREDIENTS (Tier 3+)                   │
│ [Table with all ingredients]            │
└─────────────────────────────────────────┘
```

**Tasks**:
- [ ] Separate Pack Tier section (always visible, distinct styling)
- [ ] Separate Product Options section (collapsible)
- [ ] Separate Ingredients section (collapsible, Tier 3+ badge)
- [ ] Update forms to handle `option_type`
- [ ] Add visual indicators (badges, icons) for PRIMARY and tier restrictions
- [ ] Update table headers and columns

**Key Changes**:
1. Query attributes by type: `get_primary_attribute()`, `get_product_options()`, `get_ingredient_attributes()`
2. Render three separate sections
3. Add "PRIMARY" badge to Pack Tier section
4. Add "Tier 3+" badge to Ingredients section
5. Show tier availability on each value row

**Testing**:
- [ ] Three sections display correctly
- [ ] Pack Tier section is visually distinct
- [ ] Ingredients section shows Tier 3+ indicator
- [ ] Forms work for each section
- [ ] Tables are responsive

### 4.2 Update CSS
**File**: `assets/css/admin.css`

**New Styles**:
- `.pls-primary-section`: Styling for PRIMARY option section
- `.pls-primary-badge`: Badge for "PRIMARY" indicator
- `.pls-tier-badge`: Badge for tier restrictions (existing, enhance)
- `.pls-ingredient-section`: Styling for ingredients section
- `.pls-option-type-indicator`: Visual indicator for option type

**Tasks**:
- [ ] Add styles for PRIMARY section (border, background, etc.)
- [ ] Add badge styles
- [ ] Ensure sections are visually distinct
- [ ] Maintain compact, responsive design

**Testing**:
- [ ] Visual hierarchy is clear
- [ ] Badges are visible and readable
- [ ] Layout is responsive
- [ ] No excessive spacing

### 4.3 Update JavaScript
**File**: `assets/js/admin.js`

**Tasks**:
- [ ] Update attribute filtering to respect `option_type`
- [ ] Update tier-based filtering to work with ingredient attributes
- [ ] Add handlers for option type selection in forms
- [ ] Update UI to show/hide sections based on option type

**Testing**:
- [ ] Filtering works correctly
- [ ] Tier restrictions apply to ingredients
- [ ] Forms handle option types correctly

## Phase 5: WooCommerce Sync Updates

### 5.1 Update WC Sync
**File**: `includes/wc/class-pls-wc-sync.php`

**Tasks**:
- [ ] Ensure Pack Tier syncs as primary variation attribute
- [ ] Sync ingredients as non-variation attributes (for filtering/display)
- [ ] Maintain backward compatibility
- [ ] Update `ensure_pack_tier_attribute()` to use `get_primary_attribute()`

**Testing**:
- [ ] Pack Tier syncs correctly
- [ ] Ingredients appear in product attributes (non-variation)
- [ ] Existing products continue to work
- [ ] Variations are created correctly

## Phase 6: Menu & Naming Updates

### 6.1 Update Menu Labels
**File**: `includes/admin/class-pls-admin-menu.php`

**Tasks**:
- [ ] Ensure menu item is labeled "Product Options" (not "Attributes")
- [ ] Update page title
- [ ] Update any tooltips/descriptions

**Testing**:
- [ ] Menu shows "Product Options"
- [ ] Page title is correct
- [ ] Descriptions are accurate

### 6.2 Update All References
**Files**: All files that reference "attributes"

**Tasks**:
- [ ] Search for "attribute" references in user-facing strings
- [ ] Replace with "Product Option" where appropriate
- [ ] Keep "attribute" in code/internal references
- [ ] Update help text, tooltips, etc.

**Testing**:
- [ ] User-facing text says "Product Options"
- [ ] Code still uses "attribute" internally
- [ ] No broken references

## Phase 7: Testing & Validation

### 7.1 Unit Testing Checklist
- [ ] Migration runs successfully on fresh install
- [ ] Migration runs successfully on existing install (v0.8.2 → v0.8.3)
- [ ] Pack Tier is marked as primary
- [ ] Ingredients sync to attribute system
- [ ] Repository methods work correctly
- [ ] AJAX endpoints handle new fields
- [ ] WooCommerce sync maintains compatibility

### 7.2 Integration Testing Checklist
- [ ] Creating new product option works
- [ ] Creating new ingredient works and syncs
- [ ] Editing Pack Tier works
- [ ] Tier-based filtering works for all option types
- [ ] Product creation/editing works
- [ ] WooCommerce products sync correctly
- [ ] Variations are created correctly

### 7.3 UI/UX Testing Checklist
- [ ] Three sections display correctly
- [ ] PRIMARY badge is visible
- [ ] Tier 3+ badge on ingredients is visible
- [ ] Forms work for each section
- [ ] Tables are responsive
- [ ] No excessive spacing
- [ ] Visual hierarchy is clear
- [ ] All text is readable

### 7.4 Regression Testing Checklist
- [ ] Existing products continue to work
- [ ] Existing variations continue to work
- [ ] Product editing modal works
- [ ] Bundle system works (if implemented)
- [ ] Elementor widgets work
- [ ] Frontend configurator works

## Phase 8: Documentation & Release

### 8.1 Update Documentation
**Files**: 
- `docs/10-data-model.md`: Update schema documentation
- `docs/92-hierarchical-product-options-design.md`: Reference this plan
- `readme.txt`: Add changelog entry

**Tasks**:
- [ ] Document new database columns
- [ ] Document ingredient sync system
- [ ] Document new repository methods
- [ ] Update changelog

### 8.2 Version Bump & Release
**Files**: 
- `pls-private-label-store.php`
- `uupd/index.json`
- `readme.txt`

**Tasks**:
- [ ] Bump version to 0.8.3
- [ ] Update changelog
- [ ] Commit changes
- [ ] Create git tag
- [ ] Push to repository

## Implementation Order

1. **Phase 1**: Database schema (foundation)
2. **Phase 2**: Ingredient sync (data integrity)
3. **Phase 3**: Repository updates (data access)
4. **Phase 4**: UI redesign (user experience)
5. **Phase 5**: WC sync updates (integration)
6. **Phase 6**: Menu/naming (polish)
7. **Phase 7**: Testing (validation)
8. **Phase 8**: Documentation & release (completion)

## Risk Mitigation

### Risk: Breaking Existing Functionality
**Mitigation**: 
- Maintain backward compatibility
- Test thoroughly on existing installs
- Keep migration reversible (optional)

### Risk: Ingredient Sync Conflicts
**Mitigation**:
- Use `term_id` as linking mechanism
- Handle conflicts gracefully
- Provide manual sync option

### Risk: Performance Issues
**Mitigation**:
- Index new columns properly
- Cache frequently accessed data
- Optimize queries

### Risk: UI Confusion
**Mitigation**:
- Clear visual hierarchy
- Helpful badges and indicators
- User testing before release

## Success Metrics

- ✅ Pack Tier clearly marked as PRIMARY
- ✅ Product Options separated from Pack Tier
- ✅ Ingredients integrated and available Tier 3+
- ✅ UI is intuitive and responsive
- ✅ All existing functionality works
- ✅ Zero data loss during migration
- ✅ Performance is maintained or improved

## Timeline Estimate

- **Phase 1**: 2-3 hours (database schema)
- **Phase 2**: 3-4 hours (ingredient sync)
- **Phase 3**: 2-3 hours (repository updates)
- **Phase 4**: 4-5 hours (UI redesign)
- **Phase 5**: 2-3 hours (WC sync)
- **Phase 6**: 1-2 hours (menu/naming)
- **Phase 7**: 3-4 hours (testing)
- **Phase 8**: 1 hour (documentation/release)

**Total**: ~18-25 hours

## Notes

- This plan ensures 100% success by breaking work into testable phases
- Each phase builds on the previous one
- Testing is integrated throughout, not just at the end
- Backward compatibility is maintained at every step
- Migration is safe and reversible
