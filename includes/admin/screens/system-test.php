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

// Define test categories for UI (order matters - diagnostics first)
$test_categories = array(
    'pls_info'       => array(
        'label'       => 'PLS Info & Version',
        'description' => 'Plugin version, UUPD version match, and database tables count.',
        'icon'        => 'info',
    ),
    'server_config'  => array(
        'label'       => 'Server Configuration',
        'description' => 'PHP version, memory limit, execution time, and required extensions.',
        'icon'        => 'admin-generic',
    ),
    'wc_settings'    => array(
        'label'       => 'WooCommerce Settings',
        'description' => 'Currency, taxes, payment gateways, and shipping zones.',
        'icon'        => 'admin-settings',
    ),
    'user_roles'     => array(
        'label'       => 'User Roles & Capabilities',
        'description' => 'PLS User role, Robert/Raniya users, and admin capabilities.',
        'icon'        => 'groups',
    ),
    'database'        => array(
        'label'       => 'Database',
        'description' => 'Verify all PLS tables exist and have correct schema.',
        'icon'        => 'database',
    ),
    'product_options' => array(
        'label'       => 'Product Options',
        'description' => 'Check Pack Tier attribute, values, and product options.',
        'icon'        => 'admin-settings',
    ),
    'products_sync'   => array(
        'label'       => 'Products Sync',
        'description' => 'Verify products sync to WooCommerce as variable products.',
        'icon'        => 'products',
    ),
    'variations'      => array(
        'label'       => 'Variations',
        'description' => 'Check pack tier variations have correct attributes and metadata.',
        'icon'        => 'tag',
    ),
    'bundles'         => array(
        'label'       => 'Bundles',
        'description' => 'Verify bundles sync as WooCommerce grouped products.',
        'icon'        => 'archive',
    ),
    'wc_orders'       => array(
        'label'       => 'WooCommerce Orders',
        'description' => 'Check sample orders exist with correct products and variations.',
        'icon'        => 'cart',
    ),
    'custom_orders'   => array(
        'label'       => 'Custom Orders',
        'description' => 'Verify custom orders exist in all Kanban stages.',
        'icon'        => 'clipboard',
    ),
    'commissions'     => array(
        'label'       => 'Commissions',
        'description' => 'Check commission records and calculations.',
        'icon'        => 'money-alt',
    ),
    'revenue'         => array(
        'label'       => 'Revenue',
        'description' => 'Verify revenue tracking and summary statistics.',
        'icon'        => 'chart-bar',
    ),
    'frontend_display' => array(
        'label'       => 'Frontend Display',
        'description' => 'Check auto-injection settings, CSS/JS files, and product page accessibility.',
        'icon'        => 'visibility',
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
        
        <div class="pls-control-group">
            <h3>Quick Actions</h3>
            <div class="pls-action-buttons">
                <button type="button" class="button" id="pls-resync-products">
                    <span class="dashicons dashicons-update"></span>
                    Re-sync Products
                </button>
                <button type="button" class="button" id="pls-resync-bundles">
                    <span class="dashicons dashicons-update"></span>
                    Re-sync Bundles
                </button>
            </div>
            <p class="description">Use these actions to fix sync issues.</p>
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
                <?php if ( $sample_data_status['has_data'] ) : ?>
                    <button type="button" class="button button-link-delete" id="pls-delete-sample-data" style="color: #d63638;">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e( 'Delete All Sample Data', 'pls-private-label-store' ); ?>
                    </button>
                <?php endif; ?>
            </div>
            <p class="description">
                <?php esc_html_e( 'Generate creates: 10 products, 4 bundles, 50+ WC orders (12 months history), 15 custom orders, categories, ingredients, and commissions.', 'pls-private-label-store' ); ?>
            </p>
            <p style="margin-top: 12px;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-data-import' ) ); ?>" class="button button-secondary">
                    <?php esc_html_e( 'Use Multistep Import Wizard', 'pls-private-label-store' ); ?>
                </a>
                <span class="pls-help-icon" title="<?php esc_attr_e( 'The multistep wizard provides step-by-step import with validation at each stage. Use this for better control and troubleshooting.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px;">ⓘ</span>
            </p>
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
        <?php foreach ( $test_categories as $key => $category ) : ?>
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

    <!-- Action Log -->
    <div class="pls-action-log" id="pls-action-log" style="display: none;">
        <h3>Action Log</h3>
        <div class="log-content" id="log-content"></div>
    </div>
</div>

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
