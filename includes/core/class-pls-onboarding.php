<?php
/**
 * Simplified Help System for PLS plugin.
 * Provides detailed page-specific guides accessible from a consistent location.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Onboarding {

    /**
     * Initialize help system.
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_ajax_pls_get_helper_content', array( __CLASS__, 'get_helper_content_ajax' ) );
    }

    /**
     * Enqueue help system assets.
     */
    public static function enqueue_assets( $hook ) {
        if ( false === strpos( (string) $hook, 'pls-' ) && false === strpos( (string) $hook, 'woocommerce_page_pls' ) ) {
            return;
        }

        wp_enqueue_style(
            'pls-onboarding',
            PLS_PLS_URL . 'assets/css/onboarding.css',
            array(),
            PLS_PLS_VERSION
        );

        wp_enqueue_script(
            'pls-onboarding',
            PLS_PLS_URL . 'assets/js/onboarding.js',
            array( 'jquery' ),
            PLS_PLS_VERSION,
            true
        );

        $current_page = self::get_current_page_from_hook( $hook );

        wp_localize_script(
            'pls-onboarding',
            'PLS_Onboarding',
            array(
                'nonce' => wp_create_nonce( 'pls_onboarding_nonce' ),
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'current_page' => $current_page,
            )
        );
    }

    /**
     * Get detailed helper content for a specific page.
     *
     * @param string $page Page identifier.
     * @return array Detailed guide content.
     */
    public static function get_helper_content( $page ) {
        $content = array();

        // Dashboard guide
        if ( 'dashboard' === $page ) {
            $content = array(
                'title' => __( 'Dashboard Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'Overview', 'pls-private-label-store' ),
                        'content' => __( 'The dashboard provides a quick overview of your store\'s key metrics and recent activity.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Summary Cards', 'pls-private-label-store' ),
                        'content' => __( 'Click on any summary card (Products, Orders, Revenue, Commission) to navigate to the detailed page for that section.', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Total Products: Shows count of all products in your catalog', 'pls-private-label-store' ),
                            __( 'Active Orders: Displays orders from the last 30 days containing PLS products', 'pls-private-label-store' ),
                            __( 'Pending Custom Orders: Shows custom order leads in the pipeline', 'pls-private-label-store' ),
                            __( 'Monthly Revenue: Total sales from completed orders this month', 'pls-private-label-store' ),
                            __( 'Pending Commission: Total commission awaiting payment', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Quick Actions', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Use the navigation menu at the top to access all PLS features', 'pls-private-label-store' ),
                            __( 'Click "Help" button (?) in the top right corner for detailed guides on any page', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // Product Options (Attributes) guide
        if ( 'attributes' === $page ) {
            $content = array(
                'title' => __( 'Product Options Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'What are Product Options?', 'pls-private-label-store' ),
                        'content' => __( 'Product Options define all configurable aspects of your products that customers can choose from. These include Package Type, Color, Cap, Fragrances, Ingredients, and more.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Pack Tier (PRIMARY Option)', 'pls-private-label-store' ),
                        'content' => __( 'Pack Tier is the PRIMARY option that determines pricing and available features. It must be configured first.', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Click "Manage Pack Tier Defaults" to configure default units and prices for each tier', 'pls-private-label-store' ),
                            __( 'Tier 1: 50 units - Default price: $15.90 per unit', 'pls-private-label-store' ),
                            __( 'Tier 2: 100 units - Default price: $14.50 per unit', 'pls-private-label-store' ),
                            __( 'Tier 3: 250 units - Default price: $12.50 per unit', 'pls-private-label-store' ),
                            __( 'Tier 4: 500 units - Default price: $9.50 per unit', 'pls-private-label-store' ),
                            __( 'Tier 5: 1000 units - Default price: $7.90 per unit', 'pls-private-label-store' ),
                            __( 'These defaults are used when creating products but can be customized per product', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Package Type', 'pls-private-label-store' ),
                        'items' => array(
                            __( '30ml Bottle: Small size container', 'pls-private-label-store' ),
                            __( '50ml Bottle: Medium size container', 'pls-private-label-store' ),
                            __( '120ml Bottle: Large size container', 'pls-private-label-store' ),
                            __( '50gr Jar: Alternative container type (uses lids instead of pumps)', 'pls-private-label-store' ),
                            __( 'To add/edit: Click on Package Type row, then use "Add Value" or edit existing values', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Package Color', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Standard White: Default option, no additional cost', 'pls-private-label-store' ),
                            __( 'Standard Frosted: Frosted finish option', 'pls-private-label-store' ),
                            __( 'Amber: Amber-colored bottle (has additional cost)', 'pls-private-label-store' ),
                            __( 'Each color can have tier-based pricing rules', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Package Cap', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'White Pump: Standard white pump applicator', 'pls-private-label-store' ),
                            __( 'Silver Pump: Premium silver pump (has tier-based pricing)', 'pls-private-label-store' ),
                            __( 'Lid: For jar containers only', 'pls-private-label-store' ),
                            __( 'Compatibility: Jars only support lids, bottles support pumps', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Fragrances', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Available only for Tier 3+ orders (250+ units)', 'pls-private-label-store' ),
                            __( 'Can have tier-based pricing (different prices for Tier 3, 4, 5)', 'pls-private-label-store' ),
                            __( 'Add fragrance options by clicking "Add Value" in the Fragrances row', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Label Application Pricing', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Configure pricing for Tier 1 and Tier 2 orders only', 'pls-private-label-store' ),
                            __( 'Tier 3, 4, and 5 automatically get FREE label application', 'pls-private-label-store' ),
                            __( 'Price is per unit - multiply by pack tier units for total cost', 'pls-private-label-store' ),
                            __( 'Set the price in the "Label Application Pricing" section', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Tier 4+ Options', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Custom Printed Bottles: Available from Tier 4+ (500+ units)', 'pls-private-label-store' ),
                            __( 'External Box Packaging: Available from Tier 4+ (500+ units)', 'pls-private-label-store' ),
                            __( 'These premium options require minimum order quantities', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Managing Options', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'To add a new option: Click "Add Option" button, enter name and slug', 'pls-private-label-store' ),
                            __( 'To add values: Click on an option row, then click "Add Value"', 'pls-private-label-store' ),
                            __( 'To edit: Click on option or value row to open edit modal', 'pls-private-label-store' ),
                            __( 'To delete: Click delete button (trash icon) - be careful, this affects products using this option', 'pls-private-label-store' ),
                            __( 'Set tier restrictions: Use "Min Tier Level" to restrict options to certain tiers', 'pls-private-label-store' ),
                            __( 'Set tier pricing: Configure different prices for each tier level', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // Products guide
        if ( 'products' === $page ) {
            $content = array(
                'title' => __( 'Products Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'Creating a Product', 'pls-private-label-store' ),
                        'content' => __( 'Click the "Add Product" button in the top right corner to create a new product. Follow the 6-step wizard.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Step 1: General Information', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Product Name: Enter a descriptive name (e.g., "Collagen Serum") - this becomes the WooCommerce product title', 'pls-private-label-store' ),
                            __( 'Categories: Select at least one category to organize your product (required)', 'pls-private-label-store' ),
                            __( 'Featured Image: Upload the main product photo (appears on product cards and product page)', 'pls-private-label-store' ),
                            __( 'Gallery Images: Add multiple product photos (customers can view these on the product page)', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Step 2: Product Data', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Short Description: 1-2 sentences for product cards and listings (keep it concise)', 'pls-private-label-store' ),
                            __( 'Long Description: Full product story, benefits, and detailed information', 'pls-private-label-store' ),
                            __( 'Directions: How customers should use the product (step-by-step instructions)', 'pls-private-label-store' ),
                            __( 'Skin Types: Select applicable types (Normal, Oily, Dry, Combination, Sensitive)', 'pls-private-label-store' ),
                            __( 'Benefits: List key benefits, one per line (e.g., "Hydrates instantly", "Reduces fine lines")', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Step 3: Ingredients', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Search and select ingredients from your ingredient list', 'pls-private-label-store' ),
                            __( 'Create new ingredients on the fly if needed', 'pls-private-label-store' ),
                            __( 'Key Ingredients: Choose up to 5 ingredients to spotlight with icons on the product page', 'pls-private-label-store' ),
                            __( 'Key ingredients appear prominently on the frontend', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Step 4: Pack Tiers (PRIMARY)', 'pls-private-label-store' ),
                        'content' => __( 'Pack Tiers are the PRIMARY option - they determine pricing and must be configured before Product Options.', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Review default tier pricing (loaded from Product Options settings)', 'pls-private-label-store' ),
                            __( 'Enable/Disable tiers: Check boxes to make tiers available for this product', 'pls-private-label-store' ),
                            __( 'Adjust prices: Override default prices if needed for this specific product', 'pls-private-label-store' ),
                            __( 'At least one pack tier must be enabled (required)', 'pls-private-label-store' ),
                            __( 'Each enabled tier becomes a WooCommerce product variation', 'pls-private-label-store' ),
                            __( 'Use the price calculator to see total costs with selected options', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Step 5: Product Options', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Package Type: Select container size (30ml, 50ml, 120ml bottle or 50gr jar)', 'pls-private-label-store' ),
                            __( 'Package Color: Choose Standard White (included), Frosted, or Amber (adds cost)', 'pls-private-label-store' ),
                            __( 'Package Cap: Select White or Silver pump (or lid for jars)', 'pls-private-label-store' ),
                            __( 'Fragrances: Add if using Tier 3+ (optional, available from 250+ units)', 'pls-private-label-store' ),
                            __( 'Other Options: Add any custom product options you\'ve created', 'pls-private-label-store' ),
                            __( 'Tier-based pricing: Each option value can have different prices per tier', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Step 6: Label Application', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Enable label application if you offer custom labels', 'pls-private-label-store' ),
                            __( 'Set price per unit for label application (Tier 1-2 only)', 'pls-private-label-store' ),
                            __( 'Tier 3-5 automatically get FREE label application', 'pls-private-label-store' ),
                            __( 'Label guide URL is automatically included for reference', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'After Creating a Product', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Save Product: Click "Save Product" to save your product', 'pls-private-label-store' ),
                            __( 'Sync: Click "Sync" button to create/update the WooCommerce product', 'pls-private-label-store' ),
                            __( 'Activate: Click "Activate" to make the product visible to customers', 'pls-private-label-store' ),
                            __( 'Deactivate: Click "Deactivate" to hide the product from customers', 'pls-private-label-store' ),
                            __( 'Update Available: Badge appears when product changes need syncing', 'pls-private-label-store' ),
                            __( 'Preview: Click "Preview Frontend" to see how the product appears to customers', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Editing Products', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Click on any product row to edit', 'pls-private-label-store' ),
                            __( 'Make changes and click "Save Product"', 'pls-private-label-store' ),
                            __( 'Click "Sync" to update WooCommerce with your changes', 'pls-private-label-store' ),
                            __( 'Products sync automatically on save, but manual sync ensures immediate updates', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // Bundles guide
        if ( 'bundles' === $page ) {
            $content = array(
                'title' => __( 'Bundles Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'What are Bundles?', 'pls-private-label-store' ),
                        'content' => __( 'Bundles offer special pricing when customers purchase multiple products together. The cart automatically detects when customers qualify for bundle pricing.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Creating a Bundle', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Click "Create Bundle" button', 'pls-private-label-store' ),
                            __( 'Select Bundle Type: Mini Line, Starter Line, Growth Line, or Premium Line', 'pls-private-label-store' ),
                            __( 'SKU Count: Number of different products required in the bundle (e.g., 2 = customer must buy 2 different products)', 'pls-private-label-store' ),
                            __( 'Units per SKU: Quantity required for each product (e.g., 50 = customer must buy 50 units of each product)', 'pls-private-label-store' ),
                            __( 'Price per Unit: Special bundle price per unit (lower than regular tier pricing)', 'pls-private-label-store' ),
                            __( 'Commission per Unit: Your commission rate for bundle sales', 'pls-private-label-store' ),
                            __( 'Save and Sync: Click "Save Bundle" then "Sync" to create WooCommerce Grouped Product', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'How Bundle Detection Works', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Cart automatically checks if customer qualifies for any bundle', 'pls-private-label-store' ),
                            __( 'Qualification: Customer must have the required number of different products (SKU count) with the required quantity (units per SKU)', 'pls-private-label-store' ),
                            __( 'When qualified: Bundle pricing is automatically applied to cart items', 'pls-private-label-store' ),
                            __( 'Cart notices: Customers see messages when they qualify or are close to qualifying', 'pls-private-label-store' ),
                            __( 'Product pages: "Frequently Bought Together" banner shows applicable bundles', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Bundle Examples', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Mini Line: 2 products × 50 units each = Bundle pricing', 'pls-private-label-store' ),
                            __( 'Starter Line: 3 products × 100 units each = Bundle pricing', 'pls-private-label-store' ),
                            __( 'Growth Line: 4 products × 250 units each = Bundle pricing', 'pls-private-label-store' ),
                            __( 'Premium Line: 5 products × 500 units each = Bundle pricing', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Managing Bundles', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Edit: Click on bundle row to modify settings', 'pls-private-label-store' ),
                            __( 'Delete: Click delete button to remove bundle (also removes from WooCommerce)', 'pls-private-label-store' ),
                            __( 'Sync Status: Shows if bundle is synced to WooCommerce', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // Categories guide
        if ( 'categories' === $page ) {
            $content = array(
                'title' => __( 'Categories Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'What are Categories?', 'pls-private-label-store' ),
                        'content' => __( 'Categories help organize your products in the store. They sync to WooCommerce product categories and help customers find products.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Creating Categories', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Click "Add Category" button', 'pls-private-label-store' ),
                            __( 'Enter category name (e.g., "Skincare", "Hair Care", "Body Care")', 'pls-private-label-store' ),
                            __( 'Enter slug (URL-friendly version, auto-generated from name)', 'pls-private-label-store' ),
                            __( 'Select parent category if creating a subcategory', 'pls-private-label-store' ),
                            __( 'Save category', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Assigning Categories', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Categories are assigned when creating or editing products', 'pls-private-label-store' ),
                            __( 'Select at least one category per product (required)', 'pls-private-label-store' ),
                            __( 'Products can belong to multiple categories', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Managing Categories', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Edit: Click on category row to modify name or slug', 'pls-private-label-store' ),
                            __( 'Delete: Click delete button (be careful - products using this category will need reassignment)', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // Orders guide
        if ( 'orders' === $page ) {
            $content = array(
                'title' => __( 'Orders Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'Overview', 'pls-private-label-store' ),
                        'content' => __( 'This page shows all WooCommerce orders that contain PLS products. Commissions are automatically calculated based on pack tier pricing.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Viewing Orders', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Orders are displayed in a table with key information', 'pls-private-label-store' ),
                            __( 'Order Number: Click to view full order details in WooCommerce', 'pls-private-label-store' ),
                            __( 'Customer: Customer name and email', 'pls-private-label-store' ),
                            __( 'Products: List of PLS products in the order', 'pls-private-label-store' ),
                            __( 'Total: Order total amount', 'pls-private-label-store' ),
                            __( 'Status: Order status (Completed, Processing, On Hold, etc.)', 'pls-private-label-store' ),
                            __( 'Date: Order date', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Filtering Orders', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Status Filter: Filter by order status (Completed, Processing, etc.)', 'pls-private-label-store' ),
                            __( 'Date Range: Select date range to view orders from specific periods', 'pls-private-label-store' ),
                            __( 'Product Filter: Filter orders containing a specific product', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Commission Calculation', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Commissions are automatically calculated when order status changes to Processing or Completed', 'pls-private-label-store' ),
                            __( 'Calculation is based on pack tier pricing rules', 'pls-private-label-store' ),
                            __( 'Bundle orders use bundle commission rates', 'pls-private-label-store' ),
                            __( 'View commission details on the Commission page', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // Custom Orders guide
        if ( 'custom-orders' === $page ) {
            $content = array(
                'title' => __( 'Custom Orders Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'What are Custom Orders?', 'pls-private-label-store' ),
                        'content' => __( 'Custom orders are special order requests from customers that don\'t fit standard product catalog. They flow through a Kanban pipeline from initial inquiry to completion.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Kanban Board Stages', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'New Leads: Initial customer inquiries and custom order requests', 'pls-private-label-store' ),
                            __( 'Sampling: Orders in the sampling/testing phase', 'pls-private-label-store' ),
                            __( 'Production: Orders currently being manufactured', 'pls-private-label-store' ),
                            __( 'On-hold: Orders temporarily paused or waiting for information', 'pls-private-label-store' ),
                            __( 'Done: Completed orders ready for delivery', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Managing Custom Orders', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Drag and Drop: Move orders between stages by dragging cards', 'pls-private-label-store' ),
                            __( 'View Details: Click on any order card to view full information', 'pls-private-label-store' ),
                            __( 'Edit: Update order information, production cost, total value', 'pls-private-label-store' ),
                            __( 'Delete: Remove orders that are no longer needed', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Order Information', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Customer Details: Name, email, phone, company', 'pls-private-label-store' ),
                            __( 'Order Details: Category, quantity, budget, timeline, message', 'pls-private-label-store' ),
                            __( 'Financials: Production cost, total value, commission (calculated when status is "Done")', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Commission for Custom Orders', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Commission is calculated when order status changes to "Done"', 'pls-private-label-store' ),
                            __( 'Commission rates: 3% below threshold, 5% above threshold (configured in Settings)', 'pls-private-label-store' ),
                            __( 'Commission is based on final order value (total value)', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // Revenue guide
        if ( 'revenue' === $page ) {
            $content = array(
                'title' => __( 'Revenue Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'Overview', 'pls-private-label-store' ),
                        'content' => __( 'Revenue shows total sales from WooCommerce orders containing PLS products. This is different from commission - revenue is total sales, commission is your share.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Revenue Metrics', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Total Revenue: Sum of all order totals', 'pls-private-label-store' ),
                            __( 'Monthly Trends: Chart showing revenue over time', 'pls-private-label-store' ),
                            __( 'Top Products: Products generating the most revenue', 'pls-private-label-store' ),
                            __( 'Revenue by Tier: Breakdown by pack tier (50, 100, 250, 500, 1000 units)', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Filtering Revenue', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Date Range: View revenue for specific time periods', 'pls-private-label-store' ),
                            __( 'Product Filter: See revenue breakdown by individual products', 'pls-private-label-store' ),
                            __( 'Status Filter: Filter by order status (Completed, Processing, etc.)', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Understanding Revenue vs Commission', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Revenue: Total amount customers paid (gross sales)', 'pls-private-label-store' ),
                            __( 'Commission: Your share of revenue (calculated based on tier/bundle rates)', 'pls-private-label-store' ),
                            __( 'Profit: Revenue minus commission minus marketing costs (view on BI Dashboard)', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // Commission guide
        if ( 'commission' === $page ) {
            $content = array(
                'title' => __( 'Commission Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'Overview', 'pls-private-label-store' ),
                        'content' => __( 'Commission tracking shows your earnings from product orders and custom orders. Commissions are automatically calculated and can be tracked through invoicing and payment.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Commission Types', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Product Commissions: From regular WooCommerce orders with PLS products', 'pls-private-label-store' ),
                            __( 'Custom Order Commissions: From custom order leads (separate calculation)', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Commission Calculation', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Product Orders: Based on pack tier pricing rules (configured in Settings)', 'pls-private-label-store' ),
                            __( 'Bundle Orders: Uses bundle commission rates', 'pls-private-label-store' ),
                            __( 'Custom Orders: Percentage-based (3% below threshold, 5% above threshold)', 'pls-private-label-store' ),
                            __( 'Auto-calculation: Commissions calculated when orders are completed', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Commission Status Flow', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Pending: Commission calculated but not yet invoiced', 'pls-private-label-store' ),
                            __( 'Invoiced: Commission has been invoiced', 'pls-private-label-store' ),
                            __( 'Paid: Commission payment received', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Managing Commissions', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'View Details: Click on commission row to see full breakdown', 'pls-private-label-store' ),
                            __( 'Bulk Actions: Mark multiple commissions as invoiced or paid', 'pls-private-label-store' ),
                            __( 'Date Filters: View commissions for specific time periods', 'pls-private-label-store' ),
                            __( 'Product/Tier Filters: See commission breakdown by product or tier', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Monthly Commission Reports', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Automated emails sent on 2nd of each month', 'pls-private-label-store' ),
                            __( 'Recipients configured in Settings', 'pls-private-label-store' ),
                            __( 'Manual send: Use "Send Report" button to generate and send immediately', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // BI Dashboard guide
        if ( 'bi' === $page ) {
            $content = array(
                'title' => __( 'BI Dashboard Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'Overview', 'pls-private-label-store' ),
                        'content' => __( 'The BI Dashboard provides comprehensive analytics for your PLS operations, including marketing costs, revenue, commission, and profit calculations.', 'pls-private-label-store' ),
                    ),
                    array(
                        'title' => __( 'Marketing Cost Tracking', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Record marketing expenses by channel (Meta, Google, Creative, Other)', 'pls-private-label-store' ),
                            __( 'Add costs with date and description', 'pls-private-label-store' ),
                            __( 'Track spending over time to understand ROI', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Key Metrics', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Revenue: Total sales from product and custom orders', 'pls-private-label-store' ),
                            __( 'Commission: Total commissions earned', 'pls-private-label-store' ),
                            __( 'Marketing Costs: Total marketing expenses', 'pls-private-label-store' ),
                            __( 'Net Profit: Revenue - Commission - Marketing Costs', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Charts and Visualizations', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Visual charts show trends over time', 'pls-private-label-store' ),
                            __( 'Revenue trends: See how revenue changes month-to-month', 'pls-private-label-store' ),
                            __( 'Commission trends: Track commission earnings over time', 'pls-private-label-store' ),
                            __( 'Profit analysis: Understand profitability trends', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Date Range Filtering', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Select date range to analyze performance for specific periods', 'pls-private-label-store' ),
                            __( 'Compare different time periods', 'pls-private-label-store' ),
                            __( 'Export data for external analysis', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        // Settings guide
        if ( 'settings' === $page ) {
            $content = array(
                'title' => __( 'Settings Guide', 'pls-private-label-store' ),
                'sections' => array(
                    array(
                        'title' => __( 'Commission Rates', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Pack Tier Rates: Set commission per unit for each tier (Tier 1-5)', 'pls-private-label-store' ),
                            __( 'Bundle Rates: Set commission per unit for each bundle type (Mini Line, Starter Line, Growth Line, Premium Line)', 'pls-private-label-store' ),
                            __( 'Custom Order Threshold: Set the amount threshold for custom order commission rates', 'pls-private-label-store' ),
                            __( 'Custom Order Rates: Set percentage rates (below threshold and above threshold)', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Commission Email Settings', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Configure email recipients for automated monthly commission reports', 'pls-private-label-store' ),
                            __( 'Reports are sent automatically on the 2nd of each month', 'pls-private-label-store' ),
                            __( 'Add multiple email addresses separated by commas', 'pls-private-label-store' ),
                        ),
                    ),
                    array(
                        'title' => __( 'Sample Data', 'pls-private-label-store' ),
                        'items' => array(
                            __( 'Generate sample data to populate your store with realistic products and orders', 'pls-private-label-store' ),
                            __( 'Includes: 10 sample products, product options, bundles, orders, custom orders', 'pls-private-label-store' ),
                            __( 'Useful for testing and understanding the system', 'pls-private-label-store' ),
                            __( 'Can clear existing data before generating new sample data', 'pls-private-label-store' ),
                        ),
                    ),
                ),
            );
        }

        return $content;
    }

    /**
     * Get current page from hook.
     *
     * @param string $hook Admin hook.
     * @return string
     */
    private static function get_current_page_from_hook( $hook ) {
        if ( strpos( $hook, 'pls-dashboard' ) !== false ) {
            return 'dashboard';
        } elseif ( strpos( $hook, 'pls-products' ) !== false ) {
            return 'products';
        } elseif ( strpos( $hook, 'pls-orders' ) !== false ) {
            return 'orders';
        } elseif ( strpos( $hook, 'pls-custom-orders' ) !== false ) {
            return 'custom-orders';
        } elseif ( strpos( $hook, 'pls-revenue' ) !== false ) {
            return 'revenue';
        } elseif ( strpos( $hook, 'pls-commission' ) !== false ) {
            return 'commission';
        } elseif ( strpos( $hook, 'pls-bi' ) !== false ) {
            return 'bi';
        } elseif ( strpos( $hook, 'pls-categories' ) !== false ) {
            return 'categories';
        } elseif ( strpos( $hook, 'pls-attributes' ) !== false ) {
            return 'attributes';
        } elseif ( strpos( $hook, 'pls-bundles' ) !== false ) {
            return 'bundles';
        } elseif ( strpos( $hook, 'pls-settings' ) !== false ) {
            return 'settings';
        }
        return '';
    }

    /**
     * AJAX: Get helper content for a page.
     */
    public static function get_helper_content_ajax() {
        check_ajax_referer( 'pls_onboarding_nonce', 'nonce' );

        $page = isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : '';

        if ( empty( $page ) ) {
            wp_send_json_error( array( 'message' => __( 'Page parameter required.', 'pls-private-label-store' ) ), 400 );
        }

        $content = self::get_helper_content( $page );

        if ( empty( $content ) ) {
            wp_send_json_error( array( 'message' => __( 'Helper content not found.', 'pls-private-label-store' ) ), 404 );
        }

        wp_send_json_success( array( 'content' => $content ) );
    }
}
