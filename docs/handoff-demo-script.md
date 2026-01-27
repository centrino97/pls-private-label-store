# PLS System Handoff Demo Script

This script provides a structured walkthrough for demonstrating the PLS system to Rober and Raniya.

## Pre-Demo Setup (15 minutes before session)

1. **Verify Sample Data**
   - Go to System Test page
   - Run full system test
   - Verify all tests pass
   - Ensure sample product exists and is fully configured

2. **Prepare User Accounts**
   - Verify Rober and Raniya accounts exist
   - Check they have `pls_user` role
   - Have login credentials ready to share

3. **Prepare Demo Environment**
   - Clear browser cache
   - Open fresh browser session
   - Have sample product ID ready
   - Have WooCommerce admin open in another tab

## Demo Structure (90 minutes total)

### Part 1: Introduction (10 minutes)

**Welcome & Overview**
- "Welcome to the PLS system walkthrough"
- "Today we'll cover everything you need to know to start adding products"
- "We'll use a sample product to demonstrate all features"

**System Overview**
- "PLS is a WordPress plugin that manages private label products"
- "It syncs with WooCommerce for e-commerce functionality"
- "You'll be working in the WordPress admin panel"

**Navigation Structure**
- Show PLS menu in WordPress sidebar
- Explain each menu item briefly:
  - Dashboard: Overview
  - Products: Main product management
  - Bundles: Product bundles and deals
  - Custom Orders: Lead management
  - Orders: WooCommerce orders
  - Categories, Product Options, Ingredients: Configuration
  - Commission, Revenue: Financial tracking
  - Settings: System configuration
  - System Test: Diagnostics

**User Roles & Permissions**
- "You have PLS User role - restricted to PLS pages only"
- "You can manage products, bundles, orders"
- "You cannot access full WordPress admin (by design)"
- "If you need WordPress admin access, contact administrator"

### Part 2: Dashboard Tour (5 minutes)

**Navigate to Dashboard**
- Click "PLS" → "Dashboard"
- Show summary cards:
  - Total Products: "Shows count of all products"
  - Active Orders: "Orders from last 30 days with PLS products"
  - Pending Custom Orders: "Custom order leads in pipeline"
  - Monthly Revenue: "Total sales this month"
  - Pending Commission: "Commission awaiting payment"

**Help System**
- Point to Help (?) button in top right
- Click to show help modal
- "This is available on every page - use it when you need guidance"

**Navigation**
- Show top navigation menu
- "All PLS features accessible from here"

### Part 3: Core Workflow - Product Management (30 minutes)

**Step 1: Products List (5 min)**
- Navigate to Products page
- Show product list with columns:
  - ID, Name, Slug, Status, WooCommerce ID
- Point out sample product
- "This is our sample product - fully configured"

**Step 2: View Sample Product (10 min)**
- Click "Edit" on sample product
- Show product modal opens

**Basic Information:**
- Name: "Hydrating Face Cleanser" (or whatever sample product is)
- Slug: Auto-generated
- Status: "Live" (published)
- Categories: Show assigned categories

**Pack Tiers:**
- Show all 5 tiers configured
- Explain each tier:
  - Tier 1: 50 units, $15.90/unit
  - Tier 2: 100 units, $14.50/unit
  - Tier 3: 250 units, $12.50/unit
  - Tier 4: 500 units, $9.50/unit
  - Tier 5: 1000 units, $7.90/unit
- "You can enable/disable tiers as needed"
- "Only enabled tiers sync to WooCommerce"

**Product Profile:**
- Short Description: Brief description
- Long Description: Detailed description
- Featured Image: Show image
- Gallery Images: Show gallery
- Ingredients: Show assigned ingredients
- Key Ingredients: Point out starred ingredients
- Product Options: Show assigned options
  - Package Type, Color, Cap, Fragrances

**Step 3: Product Preview (5 min)**
- Close edit modal
- Click "Preview" button
- Show preview opens in new window/tab
- "This shows how product appears to customers"
- Point out:
  - Product images
  - Configurator (pack tier selector)
  - Product description
  - Ingredients list
  - Bundle offers (if applicable)
- "Always preview before going live"

**Step 4: WooCommerce Sync (5 min)**
- Go back to products list
- Show "Sync" button
- "After creating/editing, you must sync to WooCommerce"
- Click sync (or explain process)
- "Sync creates WooCommerce product and variations"
- Open WooCommerce products in another tab
- Show synced product:
  - Variable product type
  - Variations for each pack tier
  - Product attributes
  - Categories

**Step 5: Create New Product Demo (5 min)**
- Click "Create Product" button
- Walk through form:
  - Enter name: "Test Product"
  - Status: "Draft" (explain draft vs live)
  - Select category
  - Configure one pack tier (Tier 1)
  - Add basic product profile
- "We'll save this as draft for now"
- "You can come back and complete it later"
- Save product (or cancel if just demo)

### Part 4: Product Options & Configuration (10 minutes)

**Navigate to Product Options**
- Click "Product Options" in menu
- Show options list:
  - Pack Tier (primary option)
  - Package Type
  - Package Color
  - Package Cap
  - Fragrances

**Pack Tier Defaults**
- Click "Manage Pack Tier Defaults"
- Show default units and prices
- "These are defaults - you can customize per product"

**Adding Option Values**
- Click on "Package Type" row
- Show values: 30ml Bottle, 50ml Bottle, etc.
- "To add new value: Click 'Add Value'"
- "Configure pricing if needed"
- "Sync attributes to WooCommerce after changes"

**Categories**
- Navigate to Categories
- Show category hierarchy
- "Face → Cleansers, Toning Mists, etc."
- "Assign categories when creating products"

**Ingredients**
- Navigate to Ingredients
- Show ingredients list
- "Add ingredients here"
- "Assign to products when editing"
- "Mark key ingredients with star"

### Part 5: Bundles & Deals (10 minutes)

**Navigate to Bundles**
- Click "Bundles" in menu
- Show bundles list (if any exist)

**Bundle Types**
- Explain bundle types:
  - Mini Line: 2 SKUs
  - Starter Line: 3 SKUs
  - Growth Line: 4 SKUs
  - Premium Line: 6 SKUs

**Create Bundle Demo**
- Click "Create Bundle"
- Show bundle form:
  - Name, Key, Status
  - Bundle Rules:
    - SKU Count
    - Units per SKU
    - Price per unit
  - Bundle Items: Add products + tiers
- "Bundles automatically appear in cart when customers qualify"
- "Sync to WooCommerce after creating"

### Part 6: Orders & Commission (10 minutes)

**Custom Orders**
- Navigate to Custom Orders
- Show Kanban board:
  - New Lead
  - Sampling
  - Production
  - Done
- "This is for custom order leads"
- "Move cards through stages as work progresses"
- Show creating custom order (if time)

**WooCommerce Orders**
- Navigate to Orders
- Show orders list
- "These are actual WooCommerce orders"
- "Orders with PLS products are shown here"
- Click order to show details
- Point out commission tracking

**Commission**
- Navigate to Commission
- Show commission records
- Explain commission rates:
  - Tier 1: 80%
  - Tier 2: 75%
  - Tier 3: 65%
  - Tier 4: 40%
  - Tier 5: 29%
- "Commissions calculated automatically from orders"
- Show commission status flow: pending → invoiced → paid

**Revenue**
- Navigate to Revenue
- Show revenue tracking
- "Monthly revenue from completed orders"

### Part 7: Frontend Display (10 minutes)

**Product Page**
- Open frontend in new tab
- Navigate to sample product page
- Show:
  - Product images
  - Configurator (pack tier buttons)
  - "Click tier to select - price updates"
  - Product description
  - Ingredients list
  - Bundle offers (if applicable)
- "This is what customers see"

**Configurator Demo**
- Click different pack tier buttons
- Show price updates
- Show variation form updates
- "Customers select tier, then add to cart"

**Category Page**
- Navigate to category page
- Show products in category
- Point out tier badges (if visible)
- Show starting prices

### Part 8: Settings & System (5 minutes)

**Navigate to Settings**
- Click "Settings"
- Show commission rates configuration
- Show label pricing
- Show commission email recipients
- "These are system-wide settings"
- "Usually set once, rarely changed"

**System Test**
- Navigate to System Test
- "Run this to check system health"
- "Useful for troubleshooting"
- Show test results
- "All tests should pass"

### Part 9: Q&A and Practice (15 minutes)

**Hands-On Practice**
- "Now let's have you try creating a product"
- Guide them through:
  1. Create product
  2. Configure pack tiers
  3. Add product profile
  4. Save and sync
  5. Preview product

**Common Questions**
- "What if I make a mistake?" → Edit and re-sync
- "How do I delete a product?" → Edit → Delete (with warning)
- "What if sync fails?" → Check System Test, try again
- "Can I edit after going live?" → Yes, edit and re-sync
- "How do I add new options?" → Product Options → Add Value

**Troubleshooting Tips**
- Always preview before going live
- Start with draft status
- Sync after every change
- Use Help (?) button for guidance
- Check System Test if issues arise

**Next Steps**
- "Start by creating a few test products"
- "Use draft status while learning"
- "Preview everything before going live"
- "We'll review your first products together"
- "Don't hesitate to ask questions"

## Post-Demo Checklist

- [ ] Provide login credentials
- [ ] Share documentation links
- [ ] Set up follow-up meeting
- [ ] Answer any remaining questions
- [ ] Confirm they can access system

## Key Points to Emphasize

1. **Always Preview**: Use preview before going live
2. **Draft First**: Create products as drafts, then activate
3. **Sync Required**: Always sync after changes
4. **Help Available**: Use Help (?) button on every page
5. **Test First**: Practice with test products before real ones

## Common Mistakes to Warn About

1. **Forgetting to Sync**: Product won't appear in WooCommerce
2. **Missing Pack Tiers**: Product needs at least one enabled tier
3. **Incomplete Profile**: Missing images or descriptions
4. **Wrong Status**: Product set to draft when should be live
5. **Not Previewing**: Product looks different than expected

## Resources Provided

- User Guide: Adding Products (`docs/user-guide-adding-products.md`)
- Quick Reference Guide (`docs/quick-reference-guide.md`)
- Verification Checklist (`docs/handoff-verification-checklist.md`)
- Help system in admin (?) button
