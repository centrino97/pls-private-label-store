# PLS Admin Interface Testing Guide

## How to Test as Admin User

### 1. Access the Admin Interface

1. Log in to WordPress as an administrator
2. Navigate to **PLS → Dashboard** in the WordPress admin menu
3. You should see the PLS custom header with navigation

### 2. Testing Checklist

#### Dashboard Page (`/wp-admin/admin.php?page=pls-dashboard`)

**Visual Checks:**
- [ ] Summary cards show correct numbers
- [ ] Hover over cards to see tooltips explaining what they show
- [ ] Quick Links section is visible and functional
- [ ] Help tip at top explains workflow

**Functionality:**
- [ ] Click each summary card link - should navigate correctly
- [ ] Click Quick Links - should navigate to correct pages
- [ ] Verify numbers match actual data

#### Products Page (`/wp-admin/admin.php?page=pls-products`)

**Visual Checks:**
- [ ] Product cards display correctly
- [ ] Status badges (Live/Draft/Not Synced) are visible
- [ ] Hover over buttons to see tooltips
- [ ] Help button (?) is visible in product modal

**Functionality:**
- [ ] Click "Add product" - modal opens
- [ ] Hover over Edit/Preview/Activate/Delete buttons - tooltips appear
- [ ] Click "?" help button - help panel opens
- [ ] Create a product - verify it appears in list
- [ ] Click "Activate" - product syncs to WooCommerce
- [ ] Click "Preview" - preview opens (if synced)
- [ ] Click "Edit" - edit modal opens
- [ ] Check tooltips on status badges

**UX Checks:**
- [ ] Are pack tiers clear?
- [ ] Is product options section understandable?
- [ ] Do tooltips help explain actions?

#### Orders Page (`/wp-admin/admin.php?page=pls-orders`)

**Visual Checks:**
- [ ] Table headers have tooltips (hover to see)
- [ ] Commission column has help icon (ⓘ)
- [ ] Order status badges are clear
- [ ] View button has tooltip

**Functionality:**
- [ ] Hover over table headers - tooltips explain each column
- [ ] Click "View" on an order - order detail page opens
- [ ] Verify commission amounts are calculated correctly
- [ ] Check that all orders show (not just PLS-filtered)

**UX Checks:**
- [ ] Is commission calculation clear?
- [ ] Do tooltips help understand the data?

#### Custom Orders Page (`/wp-admin/admin.php?page=pls-custom-orders`)

**Visual Checks:**
- [ ] Kanban board displays correctly
- [ ] Stage headers have tooltips (hover to see)
- [ ] Help icon (ⓘ) in description explains workflow
- [ ] Cards are draggable

**Functionality:**
- [ ] Hover over stage headers - tooltips explain each stage
- [ ] Drag a card between stages - should move
- [ ] Click "Add Custom Order" - modal opens
- [ ] Edit a custom order - verify fields work
- [ ] Convert to WC order - verify conversion works

**UX Checks:**
- [ ] Is the workflow clear?
- [ ] Do tooltips help understand stages?

#### Bundles Page (`/wp-admin/admin.php?page=pls-bundles`)

**Visual Checks:**
- [ ] Help icon (ⓘ) in description explains bundle types
- [ ] Bundle cards display correctly
- [ ] Action buttons have tooltips

**Functionality:**
- [ ] Click "Create Bundle" - modal opens
- [ ] Hover over SKU Count field - help icon shows explanation
- [ ] Hover over Units per SKU - help icon shows explanation
- [ ] Hover over Edit/Sync/Delete buttons - tooltips appear
- [ ] Create a bundle - verify it appears

**UX Checks:**
- [ ] Is SKU Count vs Units per SKU clear?
- [ ] Do tooltips help understand bundle creation?

#### Product Options Page (`/wp-admin/admin.php?page=pls-attributes`)

**Visual Checks:**
- [ ] Pack tier defaults section has description
- [ ] Tier badges show correctly
- [ ] Form fields have context

**Functionality:**
- [ ] Create an attribute - verify it works
- [ ] Add values - verify they save
- [ ] Set tier requirements - verify they apply

**UX Checks:**
- [ ] Are tier requirements clear?
- [ ] Is default pricing understandable?

#### Settings Page (`/wp-admin/admin.php?page=pls-settings`)

**Visual Checks:**
- [ ] Commission rate sections have descriptions
- [ ] Frontend display settings have explanations
- [ ] Sample data section has warning

**Functionality:**
- [ ] Configure commission rates - verify they save
- [ ] Toggle frontend display options - verify they work
- [ ] Generate sample data - verify it creates data

**UX Checks:**
- [ ] Are commission rates clear?
- [ ] Is frontend display configuration understandable?

## Helper Text Coverage

### ✅ Pages with Good Helpers
- Dashboard - Tooltips on cards, workflow tip
- Products - Help button, tooltips on buttons and badges
- Orders - Tooltips on headers, help icon on commission
- Bundles - Help icons on fields, tooltips on buttons
- Custom Orders - Help icon in description, tooltips on stages

### ⚠️ Pages Needing More Helpers
- Categories - Could use more SEO field explanations
- Ingredients - Could use more guidance
- Product Options - Could use more tier requirement explanations
- Revenue - Could use more explanation of calculations
- Commission - Could use more workflow guidance

## UX Best Practices Checklist

### Visual Hierarchy
- [ ] Important actions are prominent (primary buttons)
- [ ] Secondary actions are less prominent (ghost buttons)
- [ ] Destructive actions are clearly marked (danger buttons)

### Feedback
- [ ] Loading states show during async operations
- [ ] Success messages appear after actions
- [ ] Error messages are clear and actionable

### Accessibility
- [ ] Tooltips work on hover
- [ ] Keyboard navigation works
- [ ] Screen readers can access help text
- [ ] Color contrast is sufficient

### Discoverability
- [ ] Help icons are visible but not intrusive
- [ ] Tooltips provide context without being overwhelming
- [ ] Empty states guide users on what to do next

## Testing with Browser Tools

To test the interface using browser automation:

1. **Navigate to admin page:**
   ```
   Navigate to: http://your-site.com/wp-admin/admin.php?page=pls-dashboard
   ```

2. **Take snapshot to see page structure:**
   ```
   Use browser_snapshot to see all elements
   ```

3. **Test interactions:**
   ```
   Click buttons, hover over elements, check tooltips
   ```

4. **Verify helpers:**
   ```
   Check that all buttons have tooltips
   Check that help icons appear where needed
   Verify descriptions are helpful
   ```

## Common Issues to Watch For

1. **Missing Tooltips** - Buttons without explanations
2. **Unclear Labels** - Fields that need more context
3. **No Help Text** - Complex features without guidance
4. **Poor Empty States** - Pages that don't guide users
5. **Confusing Workflows** - Steps that aren't clear

## Recommendations for Improvement

### High Priority
1. Add tooltips to all action buttons ✅ (Done)
2. Add help icons to complex fields ✅ (Done)
3. Add descriptions to table headers ✅ (Done)
4. Add workflow explanations ✅ (Done)

### Medium Priority
1. Add contextual help modals for complex features
2. Add "First time?" onboarding banners
3. Add inline validation messages
4. Improve empty state messages with action buttons

### Low Priority
1. Add guided tours for key workflows
2. Add video tutorials
3. Add keyboard shortcuts
4. Add advanced search/filters with help text
