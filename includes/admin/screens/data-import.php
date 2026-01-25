<?php
/**
 * Multistep Data Import Screen
 * Provides guided data import with validation at each step
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current data status
require_once PLS_PLS_DIR . 'includes/core/class-pls-sample-data.php';
require_once PLS_PLS_DIR . 'includes/data/repo-attributes.php';
$current_status = PLS_Sample_Data::get_sample_data_status();
?>
<div class="wrap pls-wrap pls-page-data-import">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Data Import', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'Import Sample Data', 'pls-private-label-store' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Guided multistep import with validation. Import products, orders, and all related data step by step.', 'pls-private-label-store' ); ?>
                <span class="pls-help-icon" title="<?php esc_attr_e( 'Each step validates before proceeding. You can stop at any step and resume later.', 'pls-private-label-store' ); ?>" style="cursor: help; margin-left: 4px;">ⓘ</span>
            </p>
        </div>
    </div>

    <!-- Current Status -->
    <div class="pls-card" style="margin-bottom: 24px;">
        <h2 style="margin-top: 0;"><?php esc_html_e( 'Current Data Status', 'pls-private-label-store' ); ?></h2>
        <div class="pls-status-grid">
            <?php
            $counts = $current_status['counts'];
            $status_items = array(
                'products' => array( 'label' => __( 'Products', 'pls-private-label-store' ), 'icon' => 'products' ),
                'bundles' => array( 'label' => __( 'Bundles', 'pls-private-label-store' ), 'icon' => 'archive' ),
                'wc_orders' => array( 'label' => __( 'WC Orders', 'pls-private-label-store' ), 'icon' => 'cart' ),
                'custom_orders' => array( 'label' => __( 'Custom Orders', 'pls-private-label-store' ), 'icon' => 'clipboard' ),
                'commissions' => array( 'label' => __( 'Commissions', 'pls-private-label-store' ), 'icon' => 'money-alt' ),
                'categories' => array( 'label' => __( 'Categories', 'pls-private-label-store' ), 'icon' => 'category' ),
                'ingredients' => array( 'label' => __( 'Ingredients', 'pls-private-label-store' ), 'icon' => 'admin-generic' ),
            );
            foreach ( $status_items as $key => $item ) :
                $count = isset( $counts[ $key ] ) ? $counts[ $key ] : 0;
                $has_data = $count > 0;
                ?>
                <div class="pls-status-item <?php echo $has_data ? 'has-data' : 'no-data'; ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr( $item['icon'] ); ?>"></span>
                    <div>
                        <strong><?php echo esc_html( $item['label'] ); ?></strong>
                        <div><?php echo esc_html( $count ); ?> <?php echo $has_data ? '✓' : '—'; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Import Steps -->
    <div class="pls-import-wizard" id="pls-import-wizard">
        <!-- Step 1: Prerequisites -->
        <div class="pls-import-step" data-step="1">
            <div class="pls-step-header">
                <div class="pls-step-number">1</div>
                <div>
                    <h3><?php esc_html_e( 'Prerequisites Check', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Verify system requirements before importing data.', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <div class="pls-validation-list" id="pls-prereq-check">
                    <div class="pls-validation-item">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span><?php esc_html_e( 'Checking WooCommerce...', 'pls-private-label-store' ); ?></span>
                    </div>
                    <div class="pls-validation-item">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span><?php esc_html_e( 'Checking database tables...', 'pls-private-label-store' ); ?></span>
                    </div>
                    <div class="pls-validation-item">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span><?php esc_html_e( 'Checking PHP memory...', 'pls-private-label-store' ); ?></span>
                    </div>
                </div>
                <button type="button" class="button button-primary" id="pls-check-prereq" style="margin-top: 16px;">
                    <?php esc_html_e( 'Check Prerequisites', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>

        <!-- Step 2: Cleanup (Optional) -->
        <div class="pls-import-step" data-step="2" style="display: none;">
            <div class="pls-step-header">
                <div class="pls-step-number">2</div>
                <div>
                    <h3><?php esc_html_e( 'Cleanup Existing Data', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Remove existing data before importing. This step is optional but recommended for clean import.', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <?php if ( $current_status['has_data'] ) : ?>
                    <div class="notice notice-warning">
                        <p><strong><?php esc_html_e( 'Warning:', 'pls-private-label-store' ); ?></strong> <?php esc_html_e( 'Existing data found. Cleaning up will delete all current products, orders, and related data.', 'pls-private-label-store' ); ?></p>
                    </div>
                    <button type="button" class="button button-secondary" id="pls-cleanup-data">
                        <?php esc_html_e( 'Clean Up Existing Data', 'pls-private-label-store' ); ?>
                    </button>
                <?php else : ?>
                    <p class="description"><?php esc_html_e( 'No existing data found. You can proceed to the next step.', 'pls-private-label-store' ); ?></p>
                    <button type="button" class="button button-primary" id="pls-skip-cleanup">
                        <?php esc_html_e( 'Skip & Continue', 'pls-private-label-store' ); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 3: Categories & Ingredients -->
        <div class="pls-import-step" data-step="3" style="display: none;">
            <div class="pls-step-header">
                <div class="pls-step-number">3</div>
                <div>
                    <h3><?php esc_html_e( 'Categories & Ingredients', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Import product categories and ingredient library.', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <div class="pls-import-options">
                    <label>
                        <input type="checkbox" name="import_categories" value="1" checked />
                        <?php esc_html_e( 'Import Categories (7 categories)', 'pls-private-label-store' ); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="import_ingredients" value="1" checked />
                        <?php esc_html_e( 'Import Ingredients (14+ ingredients)', 'pls-private-label-store' ); ?>
                    </label>
                </div>
                <div class="pls-progress-bar" id="pls-step3-progress" style="display: none;">
                    <div class="pls-progress-fill"></div>
                </div>
                <div class="pls-step-log" id="pls-step3-log"></div>
                <button type="button" class="button button-primary" id="pls-import-step3" disabled>
                    <?php esc_html_e( 'Import Categories & Ingredients', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>

        <!-- Step 4: Product Options -->
        <div class="pls-import-step" data-step="4" style="display: none;">
            <div class="pls-step-header">
                <div class="pls-step-number">4</div>
                <div>
                    <h3><?php esc_html_e( 'Product Options', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Import product attributes and options (Pack Tiers, Package Type, Color, etc.).', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <div class="pls-import-options">
                    <label>
                        <input type="checkbox" name="import_attributes" value="1" checked />
                        <?php esc_html_e( 'Import Product Options (Pack Tiers + 20+ options)', 'pls-private-label-store' ); ?>
                    </label>
                </div>
                <div class="pls-progress-bar" id="pls-step4-progress" style="display: none;">
                    <div class="pls-progress-fill"></div>
                </div>
                <div class="pls-step-log" id="pls-step4-log"></div>
                <button type="button" class="button button-primary" id="pls-import-step4" disabled>
                    <?php esc_html_e( 'Import Product Options', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>

        <!-- Step 5: Products -->
        <div class="pls-import-step" data-step="5" style="display: none;">
            <div class="pls-step-header">
                <div class="pls-step-number">5</div>
                <div>
                    <h3><?php esc_html_e( 'Products', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Import products with pack tiers. Products will be synced to WooCommerce automatically.', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <div class="pls-import-options">
                    <label>
                        <input type="checkbox" name="import_products" value="1" checked />
                        <?php esc_html_e( 'Import Products (10 products with pack tiers)', 'pls-private-label-store' ); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="sync_to_wc" value="1" checked />
                        <?php esc_html_e( 'Sync to WooCommerce automatically', 'pls-private-label-store' ); ?>
                    </label>
                </div>
                <div class="pls-progress-bar" id="pls-step5-progress" style="display: none;">
                    <div class="pls-progress-fill"></div>
                </div>
                <div class="pls-step-log" id="pls-step5-log"></div>
                <button type="button" class="button button-primary" id="pls-import-step5" disabled>
                    <?php esc_html_e( 'Import Products', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>

        <!-- Step 6: Bundles -->
        <div class="pls-import-step" data-step="6" style="display: none;">
            <div class="pls-step-header">
                <div class="pls-step-number">6</div>
                <div>
                    <h3><?php esc_html_e( 'Bundles', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Import product bundles and special offers.', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <div class="pls-import-options">
                    <label>
                        <input type="checkbox" name="import_bundles" value="1" checked />
                        <?php esc_html_e( 'Import Bundles (4 bundle types)', 'pls-private-label-store' ); ?>
                    </label>
                </div>
                <div class="pls-progress-bar" id="pls-step6-progress" style="display: none;">
                    <div class="pls-progress-fill"></div>
                </div>
                <div class="pls-step-log" id="pls-step6-log"></div>
                <button type="button" class="button button-primary" id="pls-import-step6" disabled>
                    <?php esc_html_e( 'Import Bundles', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>

        <!-- Step 7: WooCommerce Orders -->
        <div class="pls-import-step" data-step="7" style="display: none;">
            <div class="pls-step-header">
                <div class="pls-step-number">7</div>
                <div>
                    <h3><?php esc_html_e( 'WooCommerce Orders', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Import WooCommerce orders with PLS products. This creates realistic order history.', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <div class="pls-import-options">
                    <label>
                        <input type="checkbox" name="import_wc_orders" value="1" checked />
                        <?php esc_html_e( 'Import WC Orders (50+ orders, 12 months history)', 'pls-private-label-store' ); ?>
                    </label>
                    <p class="description" style="margin-top: 8px;">
                        <?php esc_html_e( 'Note: Orders require products to be synced first. If products are not synced, orders will be skipped.', 'pls-private-label-store' ); ?>
                    </p>
                </div>
                <div class="pls-progress-bar" id="pls-step7-progress" style="display: none;">
                    <div class="pls-progress-fill"></div>
                </div>
                <div class="pls-step-log" id="pls-step7-log"></div>
                <button type="button" class="button button-primary" id="pls-import-step7" disabled>
                    <?php esc_html_e( 'Import WooCommerce Orders', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>

        <!-- Step 8: Custom Orders -->
        <div class="pls-import-step" data-step="8" style="display: none;">
            <div class="pls-step-header">
                <div class="pls-step-number">8</div>
                <div>
                    <h3><?php esc_html_e( 'Custom Orders', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Import custom order leads across all Kanban stages.', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <div class="pls-import-options">
                    <label>
                        <input type="checkbox" name="import_custom_orders" value="1" checked />
                        <?php esc_html_e( 'Import Custom Orders (15 orders across all stages)', 'pls-private-label-store' ); ?>
                    </label>
                </div>
                <div class="pls-progress-bar" id="pls-step8-progress" style="display: none;">
                    <div class="pls-progress-fill"></div>
                </div>
                <div class="pls-step-log" id="pls-step8-log"></div>
                <button type="button" class="button button-primary" id="pls-import-step8" disabled>
                    <?php esc_html_e( 'Import Custom Orders', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>

        <!-- Step 9: Commissions -->
        <div class="pls-import-step" data-step="9" style="display: none;">
            <div class="pls-step-header">
                <div class="pls-step-number">9</div>
                <div>
                    <h3><?php esc_html_e( 'Commissions', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Generate commission records from completed orders.', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <div class="pls-import-options">
                    <label>
                        <input type="checkbox" name="import_commissions" value="1" checked />
                        <?php esc_html_e( 'Generate Commissions (from completed orders)', 'pls-private-label-store' ); ?>
                    </label>
                    <p class="description" style="margin-top: 8px;">
                        <?php esc_html_e( 'Commissions are automatically calculated from completed WooCommerce orders.', 'pls-private-label-store' ); ?>
                    </p>
                </div>
                <div class="pls-progress-bar" id="pls-step9-progress" style="display: none;">
                    <div class="pls-progress-fill"></div>
                </div>
                <div class="pls-step-log" id="pls-step9-log"></div>
                <button type="button" class="button button-primary" id="pls-import-step9" disabled>
                    <?php esc_html_e( 'Generate Commissions', 'pls-private-label-store' ); ?>
                </button>
            </div>
        </div>

        <!-- Step 10: Verification -->
        <div class="pls-import-step" data-step="10" style="display: none;">
            <div class="pls-step-header">
                <div class="pls-step-number">10</div>
                <div>
                    <h3><?php esc_html_e( 'Verification & Summary', 'pls-private-label-store' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Verify all data was imported correctly and view summary.', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
            <div class="pls-step-content">
                <div id="pls-verification-results"></div>
                <button type="button" class="button button-primary" id="pls-run-verification">
                    <?php esc_html_e( 'Run Verification', 'pls-private-label-store' ); ?>
                </button>
                <div style="margin-top: 24px;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-system-test' ) ); ?>" class="button button-secondary">
                        <?php esc_html_e( 'Go to System Test', 'pls-private-label-store' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pls-dashboard' ) ); ?>" class="button button-secondary">
                        <?php esc_html_e( 'Go to Dashboard', 'pls-private-label-store' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Import (All at Once) -->
    <div class="pls-card" style="margin-top: 32px; border-top: 2px solid var(--pls-gray-200); padding-top: 24px;">
        <h2><?php esc_html_e( 'Quick Import (All Steps)', 'pls-private-label-store' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Import all data at once. This is faster but provides less control. Recommended for fresh installations.', 'pls-private-label-store' ); ?>
        </p>
        <button type="button" class="button button-primary button-hero" id="pls-quick-import-all">
            <?php esc_html_e( 'Import All Data', 'pls-private-label-store' ); ?>
        </button>
    </div>
</div>

<style>
.pls-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-top: 16px;
}
.pls-status-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--pls-gray-50);
    border-radius: 8px;
    border: 1px solid var(--pls-gray-200);
}
.pls-status-item.has-data {
    background: var(--pls-success-light);
    border-color: var(--pls-success);
}
.pls-status-item.no-data {
    opacity: 0.6;
}
.pls-import-step {
    margin-bottom: 32px;
    padding: 24px;
    background: white;
    border-radius: 12px;
    border: 2px solid var(--pls-gray-200);
    transition: all 0.3s ease;
}
.pls-import-step.active {
    border-color: var(--pls-accent);
    box-shadow: 0 4px 12px rgba(0, 122, 255, 0.1);
}
.pls-import-step.completed {
    border-color: var(--pls-success);
    background: var(--pls-success-light);
}
.pls-step-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}
.pls-step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--pls-gray-200);
    color: var(--pls-gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
    flex-shrink: 0;
}
.pls-import-step.active .pls-step-number {
    background: var(--pls-accent);
    color: white;
}
.pls-import-step.completed .pls-step-number {
    background: var(--pls-success);
    color: white;
}
.pls-step-content {
    margin-left: 56px;
}
.pls-import-options {
    margin-bottom: 16px;
}
.pls-import-options label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}
.pls-progress-bar {
    width: 100%;
    height: 8px;
    background: var(--pls-gray-200);
    border-radius: 4px;
    overflow: hidden;
    margin: 16px 0;
}
.pls-progress-fill {
    height: 100%;
    background: var(--pls-accent);
    width: 0%;
    transition: width 0.3s ease;
}
.pls-step-log {
    min-height: 100px;
    max-height: 300px;
    overflow-y: auto;
    padding: 12px;
    background: var(--pls-gray-50);
    border-radius: 6px;
    font-family: monospace;
    font-size: 12px;
    margin-bottom: 16px;
    display: none;
}
.pls-step-log.active {
    display: block;
}
.pls-validation-list {
    margin-bottom: 16px;
}
.pls-validation-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
}
.pls-validation-item .dashicons {
    color: var(--pls-gray-400);
}
.pls-validation-item.pass .dashicons {
    color: var(--pls-success);
}
.pls-validation-item.fail .dashicons {
    color: var(--pls-error);
}
</style>

<script>
jQuery(document).ready(function($) {
    var currentStep = 1;
    var importNonce = '<?php echo wp_create_nonce( 'pls_data_import' ); ?>';
    var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
    
    // Step navigation
    function showStep(step) {
        $('.pls-import-step').hide();
        $('.pls-import-step[data-step="' + step + '"]').show().addClass('active');
        currentStep = step;
    }
    
    // Step 1: Check Prerequisites
    $('#pls-check-prereq').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Checking...');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'pls_check_prerequisites',
                nonce: importNonce
            },
            success: function(response) {
                if (response.success) {
                    var checks = response.data.checks || {};
                    $.each(checks, function(key, result) {
                        var $item = $('#pls-prereq-check .pls-validation-item').eq(Object.keys(checks).indexOf(key));
                        if (result.pass) {
                            $item.addClass('pass').find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-yes-alt');
                            $item.find('span').last().text(result.message || 'Passed');
                        } else {
                            $item.addClass('fail').find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-dismiss');
                            $item.find('span').last().text(result.message || 'Failed');
                        }
                    });
                    
                    if (response.data.all_passed) {
                        $btn.text('✓ All Checks Passed').addClass('button-primary');
                        setTimeout(function() {
                            showStep(2);
                        }, 1000);
                    } else {
                        $btn.prop('disabled', false).text('Retry Check');
                    }
                }
            }
        });
    });
    
    // Step 2: Cleanup
    $('#pls-cleanup-data').on('click', function() {
        if (!confirm('This will delete ALL existing data. Are you sure?')) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Cleaning up...');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'pls_import_action',
                step: 'cleanup',
                nonce: importNonce
            },
            success: function(response) {
                if (response.success) {
                    $btn.text('✓ Cleanup Complete').addClass('button-primary');
                    setTimeout(function() {
                        showStep(3);
                    }, 1000);
                } else {
                    alert('Cleanup failed: ' + (response.data.message || 'Unknown error'));
                    $btn.prop('disabled', false).text('Retry Cleanup');
                }
            }
        });
    });
    
    $('#pls-skip-cleanup').on('click', function() {
        showStep(3);
    });
    
    // Generic step import handler
    function handleStepImport(step, actionName) {
        var $step = $('.pls-import-step[data-step="' + step + '"]');
        var $btn = $step.find('button[type="button"]');
        var $progress = $('#pls-step' + step + '-progress');
        var $log = $('#pls-step' + step + '-log');
        
        $btn.prop('disabled', true).text('Importing...');
        $progress.show();
        $log.show().addClass('active').html('<div>Starting import...</div>');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'pls_import_action',
                step: actionName,
                nonce: importNonce
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total * 100;
                        $progress.find('.pls-progress-fill').css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data || {};
                    var logHtml = '<div style="color: var(--pls-success);">✓ Import completed successfully!</div>';
                    
                    if (data.action_log) {
                        $.each(data.action_log, function(i, entry) {
                            var icon = entry.type === 'success' ? '✓' : entry.type === 'error' ? '✗' : '⚠';
                            var color = entry.type === 'success' ? 'var(--pls-success)' : entry.type === 'error' ? 'var(--pls-error)' : 'var(--pls-warning)';
                            logHtml += '<div style="color: ' + color + ';">' + icon + ' ' + entry.message + '</div>';
                        });
                    }
                    
                    $log.html(logHtml);
                    $progress.find('.pls-progress-fill').css('width', '100%');
                    $btn.text('✓ Import Complete').addClass('button-primary');
                    $step.addClass('completed');
                    
                    setTimeout(function() {
                        showStep(step + 1);
                    }, 1500);
                } else {
                    $log.html('<div style="color: var(--pls-error);">✗ Import failed: ' + (response.data.message || 'Unknown error') + '</div>');
                    $btn.prop('disabled', false).text('Retry Import');
                }
            },
            error: function() {
                $log.html('<div style="color: var(--pls-error);">✗ Import failed: Network error</div>');
                $btn.prop('disabled', false).text('Retry Import');
            }
        });
    }
    
    // Step 3: Categories & Ingredients
    $('#pls-import-step3').on('click', function() {
        handleStepImport(3, 'categories_ingredients');
    });
    
    // Step 4: Product Options
    $('#pls-import-step4').on('click', function() {
        handleStepImport(4, 'attributes');
    });
    
    // Step 5: Products
    $('#pls-import-step5').on('click', function() {
        handleStepImport(5, 'products');
    });
    
    // Step 6: Bundles
    $('#pls-import-step6').on('click', function() {
        handleStepImport(6, 'bundles');
    });
    
    // Step 7: WC Orders
    $('#pls-import-step7').on('click', function() {
        handleStepImport(7, 'wc_orders');
    });
    
    // Step 8: Custom Orders
    $('#pls-import-step8').on('click', function() {
        handleStepImport(8, 'custom_orders');
    });
    
    // Step 9: Commissions
    $('#pls-import-step9').on('click', function() {
        handleStepImport(9, 'commissions');
    });
    
    // Quick Import All
    $('#pls-quick-import-all').on('click', function() {
        if (!confirm('This will import all data at once. Continue?')) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Importing all data...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pls_import_action',
                step: 'all',
                nonce: importNonce
            },
            success: function(response) {
                if (response.success) {
                    $btn.text('✓ All Data Imported').addClass('button-primary');
                    alert('All data imported successfully!');
                    location.reload();
                } else {
                    alert('Import failed: ' + (response.data.message || 'Unknown error'));
                    $btn.prop('disabled', false).text('Retry Import');
                }
            }
        });
    });
    
    // Enable step buttons when previous step completes
    $('.pls-import-step').on('step-complete', function() {
        var step = $(this).data('step');
        $('.pls-import-step[data-step="' + (step + 1) + '"] button').prop('disabled', false);
    });
    
    // Initialize - show step 1
    showStep(1);
});
</script>
