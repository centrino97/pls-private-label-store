<?php
/**
 * System Test admin screen.
 * Provides comprehensive validation of all PLS functionality.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure test class is loaded
if ( ! class_exists( 'PLS_System_Test' ) ) {
    require_once PLS_PLS_DIR . 'includes/core/class-pls-system-test.php';
}

// Get quick stats
$stats = PLS_System_Test::get_quick_stats();

// Get sample data status
$sample_data_status = PLS_System_Test::get_sample_data_status();

// Get PLS version info
$pls_version = PLS_PLS_VERSION;
$uupd_file = PLS_PLS_DIR . 'uupd/index.json';
$uupd_version = '';
if ( file_exists( $uupd_file ) ) {
    $uupd = json_decode( file_get_contents( $uupd_file ), true );
    if ( is_array( $uupd ) && isset( $uupd['version'] ) ) {
        $uupd_version = $uupd['version'];
    }
}

// Define test categories for UI (organized by groups)
$test_categories = array(
    // Core Tests
    'pls_info'          => array(
        'label'       => 'PLS Info & Version',
        'description' => 'Plugin version, UUPD version match, and database tables count.',
        'icon'        => 'info',
        'group'       => 'core',
    ),
    'server_config'     => array(
        'label'       => 'Server Configuration',
        'description' => 'PHP version, memory limit, execution time, and required extensions.',
        'icon'        => 'admin-generic',
        'group'       => 'core',
    ),
    'database'           => array(
        'label'       => 'Database',
        'description' => 'Verify all PLS tables exist and have correct schema.',
        'icon'        => 'database',
        'group'       => 'core',
    ),
    'product_options'   => array(
        'label'       => 'Product Options',
        'description' => 'Check Pack Tier attribute, values, and product options.',
        'icon'        => 'admin-settings',
        'group'       => 'core',
    ),
    'product_profiles'  => array(
        'label'       => 'Product Profiles',
        'description' => 'Validate product profile JSON fields, images, and content structure.',
        'icon'        => 'admin-page',
        'group'       => 'core',
    ),
    'tier_rules'        => array(
        'label'       => 'Tier Rules System',
        'description' => 'Test tier-based pricing, restrictions, and label fee calculations.',
        'icon'        => 'chart-line',
        'group'       => 'core',
    ),
    'swatches'          => array(
        'label'       => 'Swatch System',
        'description' => 'Verify swatch data, types, and WooCommerce sync.',
        'icon'        => 'admin-appearance',
        'group'       => 'core',
    ),
    
    // WooCommerce Sync
    'wc_settings'       => array(
        'label'       => 'WooCommerce Settings',
        'description' => 'Currency, taxes, payment gateways, and shipping zones.',
        'icon'        => 'admin-settings',
        'group'       => 'wc_sync',
    ),
    'products_sync'     => array(
        'label'       => 'Products Sync',
        'description' => 'Verify products sync to WooCommerce as variable products.',
        'icon'        => 'products',
        'group'       => 'wc_sync',
    ),
    'variations'        => array(
        'label'       => 'Variations',
        'description' => 'Check pack tier variations have correct attributes and metadata.',
        'icon'        => 'tag',
        'group'       => 'wc_sync',
    ),
    'bundle_cart'       => array(
        'label'       => 'Bundle Cart Logic',
        'description' => 'Test bundle detection, pricing, and cart integration.',
        'icon'        => 'cart',
        'group'       => 'wc_sync',
    ),
    
    // Data Management
    'stock_management'   => array(
        'label'       => 'Stock Management',
        'description' => 'Verify stock tracking, quantities, and WooCommerce sync.',
        'icon'        => 'database-export',
        'group'       => 'data',
    ),
    'cost_management'    => array(
        'label'       => 'Cost Management',
        'description' => 'Check shipping and packaging costs, and WooCommerce sync.',
        'icon'        => 'money-alt',
        'group'       => 'data',
    ),
    'marketing_costs'   => array(
        'label'       => 'Marketing Costs',
        'description' => 'Verify marketing cost tracking by channel and date range queries.',
        'icon'        => 'megaphone',
        'group'       => 'data',
    ),
    'revenue_snapshots'  => array(
        'label'       => 'Revenue Snapshots',
        'description' => 'Test daily revenue snapshot generation and date range queries.',
        'icon'        => 'camera',
        'group'       => 'data',
    ),
    'revenue'           => array(
        'label'       => 'Revenue',
        'description' => 'Verify revenue tracking and summary statistics.',
        'icon'        => 'chart-bar',
        'group'       => 'data',
    ),
    
    // Orders & Commissions
    'bundles'           => array(
        'label'       => 'Bundles',
        'description' => 'Verify bundles sync as WooCommerce grouped products.',
        'icon'        => 'archive',
        'group'       => 'orders',
    ),
    'wc_orders'         => array(
        'label'       => 'WooCommerce Orders',
        'description' => 'Check sample orders exist with correct products and variations.',
        'icon'        => 'cart',
        'group'       => 'orders',
    ),
    'custom_orders'     => array(
        'label'       => 'Custom Orders',
        'description' => 'Verify custom orders exist in all Kanban stages.',
        'icon'        => 'clipboard',
        'group'       => 'orders',
    ),
    'commissions'       => array(
        'label'       => 'Commissions',
        'description' => 'Check commission records and calculations.',
        'icon'        => 'money-alt',
        'group'       => 'orders',
    ),
    'commission_reports' => array(
        'label'       => 'Commission Reports',
        'description' => 'Verify monthly commission reports, totals accuracy, and status tracking.',
        'icon'        => 'media-document',
        'group'       => 'orders',
    ),
    
    // Infrastructure
    'user_roles'        => array(
        'label'       => 'User Roles & Capabilities',
        'description' => 'PLS User role, Robert/Raniya users, and admin capabilities.',
        'icon'        => 'groups',
        'group'       => 'infrastructure',
    ),
    'ingredient_sync'   => array(
        'label'       => 'Ingredient Sync',
        'description' => 'Test ingredient taxonomy sync to attribute system and images.',
        'icon'        => 'admin-links',
        'group'       => 'infrastructure',
    ),
    'shortcodes'        => array(
        'label'       => 'Shortcodes',
        'description' => 'Verify all PLS shortcodes are registered and render correctly.',
        'icon'        => 'shortcode',
        'group'       => 'infrastructure',
    ),
    'ajax_endpoints'    => array(
        'label'       => 'AJAX Endpoints',
        'description' => 'Check admin and frontend AJAX actions are registered with nonce validation.',
        'icon'        => 'admin-tools',
        'group'       => 'infrastructure',
    ),
    
    // Admin
    'onboarding'       => array(
        'label'       => 'Onboarding/Help System',
        'description' => 'Verify help content exists for all PLS admin pages.',
        'icon'        => 'lightbulb',
        'group'       => 'admin',
    ),
    'admin_filter'     => array(
        'label'       => 'Admin Dashboard Filter',
        'description' => 'Test menu restrictions and page redirects for restricted users.',
        'icon'        => 'admin-users',
        'group'       => 'admin',
    ),
    'seo_integration'  => array(
        'label'       => 'SEO Integration',
        'description' => 'Verify Yoast SEO meta sync, schema markup, and category SEO.',
        'icon'        => 'search',
        'group'       => 'admin',
    ),
    
    // Frontend
    'frontend_display'  => array(
        'label'       => 'Frontend Display',
        'description' => 'Check auto-injection settings, CSS/JS files, and product page accessibility.',
        'icon'        => 'visibility',
        'group'       => 'frontend',
    ),
    
    // v4.9.99 Features
    'tier_unlocking'    => array(
        'label'       => 'Tier-Based Unlocking',
        'description' => 'Test tier-based ingredient and fragrance unlocking system.',
        'icon'        => 'unlock',
        'group'       => 'v4.9.99',
    ),
    'inline_configurator' => array(
        'label'       => 'Inline Configurator',
        'description' => 'Verify inline configurator method exists and supports multiple instances.',
        'icon'        => 'admin-customizer',
        'group'       => 'v4.9.99',
    ),
    'cro_features'      => array(
        'label'       => 'CRO Features',
        'description' => 'Test multiple CTAs, long-form content, and social proof sections.',
        'icon'        => 'chart-area',
        'group'       => 'v4.9.99',
    ),
    'sample_data_completeness' => array(
        'label'       => 'Sample Data Completeness',
        'description' => 'Verify sample data has full complexity: 3-5 products, tier-based ingredients/fragrances, all bundle rules.',
        'icon'        => 'database-add',
        'group'       => 'v4.9.99',
    ),
    'landing_pages'     => array(
        'label'       => 'Landing Pages',
        'description' => 'Test landing page post type, keyword mapping, and product integration.',
        'icon'        => 'admin-page',
        'group'       => 'v4.9.99',
    ),
);
?>

<div class="pls-system-test-wrap">
    <div class="pls-test-header">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h1>System Test</h1>
                <p class="description">
                    Comprehensive validation of all PLS functionality. Run tests to ensure the system is 100% production-ready
                    with realistic sample data representing a full year of business activity.
                </p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 14px; color: #666; margin-bottom: 4px;">Plugin Version</div>
                <div style="font-size: 20px; font-weight: 600; color: #2271b1;">
                    v<?php echo esc_html( $pls_version ); ?>
                </div>
                <?php if ( $uupd_version ) : ?>
                    <div style="font-size: 12px; color: <?php echo ( $uupd_version === $pls_version ) ? '#00a32a' : '#d63638'; ?>; margin-top: 4px;">
                        UUPD: v<?php echo esc_html( $uupd_version ); ?>
                        <?php if ( $uupd_version !== $pls_version ) : ?>
                            <span style="color: #d63638;">⚠ MISMATCH</span>
                        <?php else : ?>
                            <span style="color: #00a32a;">✓ Match</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="pls-test-stats">
        <div class="pls-stat-card">
            <span class="dashicons dashicons-products"></span>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html( $stats['products'] ); ?></span>
                <span class="stat-label">Products (<?php echo esc_html( $stats['products_live'] ); ?> live, <?php echo esc_html( $stats['products_draft'] ); ?> draft)</span>
            </div>
        </div>
        <div class="pls-stat-card">
            <span class="dashicons dashicons-tag"></span>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html( $stats['pack_tiers'] ); ?></span>
                <span class="stat-label">Pack Tiers</span>
            </div>
        </div>
        <div class="pls-stat-card">
            <span class="dashicons dashicons-cart"></span>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html( $stats['wc_orders'] ); ?></span>
                <span class="stat-label">WC Orders</span>
            </div>
        </div>
        <div class="pls-stat-card">
            <span class="dashicons dashicons-clipboard"></span>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html( $stats['custom_orders'] ); ?></span>
                <span class="stat-label">Custom Orders</span>
            </div>
        </div>
        <div class="pls-stat-card">
            <span class="dashicons dashicons-money-alt"></span>
            <div class="stat-content">
                <span class="stat-value">$<?php echo esc_html( number_format( $stats['commission_total'], 2 ) ); ?></span>
                <span class="stat-label">Commission (<?php echo esc_html( $stats['commissions'] ); ?> records)</span>
            </div>
        </div>
    </div>

    <!-- Test Controls -->
    <div class="pls-test-controls">
        <div class="pls-control-group">
            <h3>Run Tests</h3>
            <button type="button" class="button button-primary button-hero" id="pls-run-all-tests">
                <span class="dashicons dashicons-yes-alt"></span>
                Run All Tests
            </button>
            <p class="description">Run all test categories to validate the complete system.</p>
        </div>
        
        <div class="pls-control-group pls-sample-data-control">
            <h3>Sample Data</h3>
            <div class="pls-sample-data-status" style="margin-bottom: 12px; padding: 12px; background: <?php echo $sample_data_status['has_data'] ? '#e7f5e9' : '#f0f0f1'; ?>; border-radius: 6px;">
                <?php if ( $sample_data_status['has_data'] ) : ?>
                    <span style="color: #00a32a; font-weight: 600;">
                        <span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span>
                        <?php esc_html_e( 'Sample data exists', 'pls-private-label-store' ); ?>
                    </span>
                    <div style="margin-top: 8px; font-size: 12px; color: #666;">
                        <?php
                        $counts = $sample_data_status['counts'];
                        $items = array();
                        if ( $counts['products'] > 0 ) $items[] = $counts['products'] . ' products';
                        if ( $counts['bundles'] > 0 ) $items[] = $counts['bundles'] . ' bundles';
                        if ( $counts['wc_orders'] > 0 ) $items[] = $counts['wc_orders'] . ' WC orders';
                        if ( $counts['custom_orders'] > 0 ) $items[] = $counts['custom_orders'] . ' custom orders';
                        if ( $counts['commissions'] > 0 ) $items[] = $counts['commissions'] . ' commissions';
                        if ( $counts['categories'] > 0 ) $items[] = $counts['categories'] . ' categories';
                        if ( $counts['ingredients'] > 0 ) $items[] = $counts['ingredients'] . ' ingredients';
                        echo esc_html( implode( ' • ', $items ) );
                        ?>
                    </div>
                <?php else : ?>
                    <span style="color: #666;">
                        <span class="dashicons dashicons-minus" style="vertical-align: middle;"></span>
                        <?php esc_html_e( 'No sample data - click Generate to create test data', 'pls-private-label-store' ); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="pls-action-buttons">
                <button type="button" class="button button-primary" id="pls-generate-sample-data">
                    <span class="dashicons dashicons-database-add"></span>
                    <?php echo $sample_data_status['has_data'] ? esc_html__( 'Regenerate Sample Data', 'pls-private-label-store' ) : esc_html__( 'Generate Sample Data', 'pls-private-label-store' ); ?>
                </button>
                <button type="button" class="button button-secondary" id="pls-generate-orders" <?php echo ( ! $sample_data_status['has_data'] || empty( $sample_data_status['counts']['products'] ) ) ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-cart"></span>
                    <?php esc_html_e( 'Generate Orders', 'pls-private-label-store' ); ?>
                </button>
                <?php if ( $sample_data_status['has_data'] ) : ?>
                    <button type="button" class="button button-link-delete" id="pls-delete-sample-data" style="color: #d63638;">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e( 'Delete All Sample Data', 'pls-private-label-store' ); ?>
                    </button>
                <?php endif; ?>
            </div>
            <p class="description">
                <?php esc_html_e( 'Generate Sample Data creates: products, bundles, custom orders, categories, ingredients, and commissions. Generate Orders creates WooCommerce orders (requires products to exist first).', 'pls-private-label-store' ); ?>
            </p>
        </div>

        <div class="pls-control-group">
            <h3>View Last Log</h3>
            <div id="pls-log-viewer" style="display: none;">
                <div style="margin-bottom: 12px;">
                    <strong id="pls-log-filename"></strong>
                    <span style="color: #666; font-size: 12px; margin-left: 8px;" id="pls-log-time"></span>
                </div>
                <textarea id="pls-log-content" readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;"></textarea>
                <div style="margin-top: 12px;">
                    <button type="button" class="button" id="pls-copy-log">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php esc_html_e( 'Copy Log', 'pls-private-label-store' ); ?>
                    </button>
                    <a href="#" id="pls-download-log" class="button button-secondary" download style="margin-left: 8px;">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e( 'Download Log', 'pls-private-label-store' ); ?>
                    </a>
                </div>
            </div>
            <button type="button" class="button" id="pls-view-last-log">
                <span class="dashicons dashicons-visibility"></span>
                <?php esc_html_e( 'View Last Log', 'pls-private-label-store' ); ?>
            </button>
            <p class="description">
                <?php esc_html_e( 'View and copy the last generation log file. Useful for troubleshooting and sharing with support.', 'pls-private-label-store' ); ?>
            </p>
        </div>
    </div>
    </div>

    <!-- Test Summary (shown after running tests) -->
    <div class="pls-test-summary" id="pls-test-summary" style="display: none;">
        <div class="summary-header">
            <h2>Test Results</h2>
            <div class="health-score">
                <span class="health-label">System Health:</span>
                <span class="health-value" id="health-score">0%</span>
            </div>
            <button type="button" class="button button-secondary" id="pls-download-test-results" style="margin-left: auto;">
                <span class="dashicons dashicons-download"></span> Download Test Results
            </button>
        </div>
        <div class="summary-stats">
            <span class="stat-passed"><span class="dashicons dashicons-yes-alt"></span> <span id="stat-passed">0</span> Passed</span>
            <span class="stat-failed"><span class="dashicons dashicons-dismiss"></span> <span id="stat-failed">0</span> Failed</span>
            <span class="stat-warnings"><span class="dashicons dashicons-warning"></span> <span id="stat-warnings">0</span> Warnings</span>
            <span class="stat-skipped"><span class="dashicons dashicons-minus"></span> <span id="stat-skipped">0</span> Skipped</span>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="pls-test-progress" id="pls-test-progress" style="display: none;">
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>
        <span class="progress-text" id="progress-text">Running tests...</span>
    </div>

    <!-- Test Results -->
    <div class="pls-test-results" id="pls-test-results">
        <?php
        // Group tests by category
        $grouped_tests = array();
        foreach ( $test_categories as $key => $category ) {
            $group = isset( $category['group'] ) ? $category['group'] : 'other';
            if ( ! isset( $grouped_tests[ $group ] ) ) {
                $grouped_tests[ $group ] = array();
            }
            $grouped_tests[ $group ][ $key ] = $category;
        }
        
        // Group labels
        $group_labels = array(
            'core'         => 'Core Tests',
            'wc_sync'      => 'WooCommerce Sync',
            'data'         => 'Data Management',
            'orders'       => 'Orders & Commissions',
            'infrastructure' => 'Infrastructure',
            'admin'        => 'Admin Features',
            'frontend'     => 'Frontend',
            'v4.9.99'      => 'v4.9.99 Features',
            'other'        => 'Other',
        );
        
        // Display grouped tests
        foreach ( $grouped_tests as $group_key => $tests ) :
            $group_label = isset( $group_labels[ $group_key ] ) ? $group_labels[ $group_key ] : ucfirst( $group_key );
            ?>
            <div class="pls-test-group" data-group="<?php echo esc_attr( $group_key ); ?>">
                <h2 class="test-group-title" style="margin: 30px 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #ddd; font-size: 18px; font-weight: 600;">
                    <?php echo esc_html( $group_label ); ?>
                    <span class="test-group-count" style="font-size: 14px; font-weight: 400; color: #666; margin-left: 8px;">
                        (<?php echo count( $tests ); ?> tests)
                    </span>
                </h2>
                <?php foreach ( $tests as $key => $category ) : ?>
                    <div class="pls-test-category" data-category="<?php echo esc_attr( $key ); ?>">
                        <div class="category-header">
                            <div class="category-info">
                                <span class="dashicons dashicons-<?php echo esc_attr( $category['icon'] ); ?>"></span>
                                <h3><?php echo esc_html( $category['label'] ); ?></h3>
                                <span class="category-status" data-status="pending">
                                    <span class="dashicons dashicons-clock"></span>
                                </span>
                            </div>
                            <p class="category-description"><?php echo esc_html( $category['description'] ); ?></p>
                            <button type="button" class="button button-small pls-run-category" data-category="<?php echo esc_attr( $key ); ?>">
                                Run
                            </button>
                            <button type="button" class="button button-link toggle-details" style="display: none;">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </div>
                        <div class="category-results" style="display: none;">
                            <div class="results-list"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- Generation Progress Modal -->
<div class="pls-modal" id="pls-generation-modal">
    <div class="pls-modal__dialog" style="max-width: 600px;">
        <div class="pls-modal__head">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;" id="pls-modal-title">Generating Data...</h2>
            <button type="button" class="pls-modal__close" id="pls-modal-close" style="display: none;">×</button>
        </div>
        <div class="pls-modal__section" style="margin-bottom: 0;">
            <div style="text-align: center; padding: 20px 0;">
                <div class="pls-spinner" style="width: 48px; height: 48px; margin: 0 auto 20px; border: 4px solid var(--pls-gray-200); border-top-color: var(--pls-accent); border-radius: 50%; animation: pls-spin 1s linear infinite;"></div>
                <p style="margin: 0 0 8px; font-size: 16px; font-weight: 500;" id="pls-modal-status">Initializing...</p>
                <p style="margin: 0; font-size: 14px; color: var(--pls-gray-500);" id="pls-modal-message">Please wait, this may take 1-3 minutes.</p>
            </div>
            <div id="pls-progress-log" style="max-height: 300px; overflow-y: auto; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--pls-gray-200); display: none;">
                <div style="font-size: 12px; color: var(--pls-gray-600); font-family: monospace; line-height: 1.6;">
                    <div id="pls-log-entries"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pls-spin {
    to { transform: rotate(360deg); }
}
.pls-log-entry {
    padding: 4px 0;
    color: var(--pls-gray-700);
}
.pls-log-entry.pls-log-success {
    color: var(--pls-success);
}
.pls-log-entry.pls-log-error {
    color: var(--pls-error);
}
.pls-log-entry.pls-log-warning {
    color: var(--pls-warning);
}
.pls-log-entry.pls-log-info {
    color: var(--pls-gray-500);
}
</style>

<script type="text/template" id="tmpl-test-result">
    <div class="test-result test-{{ data.status }}">
        <span class="result-icon">
            <# if ( data.status === 'pass' ) { #>
                <span class="dashicons dashicons-yes-alt"></span>
            <# } else if ( data.status === 'fail' ) { #>
                <span class="dashicons dashicons-dismiss"></span>
            <# } else if ( data.status === 'warning' ) { #>
                <span class="dashicons dashicons-warning"></span>
            <# } else { #>
                <span class="dashicons dashicons-minus"></span>
            <# } #>
        </span>
        <div class="result-content">
            <span class="result-name">{{ data.name }}</span>
            <span class="result-message">{{ data.message }}</span>
            <# if ( data.fix ) { #>
                <span class="result-fix"><strong>Fix:</strong> {{ data.fix }}</span>
            <# } #>
        </div>
    </div>
</script>
