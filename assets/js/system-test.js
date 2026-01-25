/**
 * PLS System Test JavaScript
 * Handles AJAX test execution and result rendering.
 */

(function($) {
    'use strict';

    const SystemTest = {
        // Test categories in order
        categories: [
            'pls_info',
            'server_config',
            'wc_settings',
            'user_roles',
            'database',
            'product_options',
            'products_sync',
            'variations',
            'bundles',
            'wc_orders',
            'custom_orders',
            'commissions',
            'revenue',
            'frontend_display'
        ],

        // Current test state
        isRunning: false,
        currentCategory: 0,
        results: {},

        /**
         * Initialize the system test page.
         */
        init: function() {
            this.bindEvents();
            this.initTemplates();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            // Run all tests
            $('#pls-run-all-tests').on('click', () => this.runAllTests());

            // Run individual category
            $('.pls-run-category').on('click', (e) => {
                const category = $(e.currentTarget).data('category');
                this.runCategory(category);
            });

            // Toggle category details
            $('.pls-test-category .category-header').on('click', (e) => {
                if (!$(e.target).is('button')) {
                    const $category = $(e.currentTarget).closest('.pls-test-category');
                    this.toggleCategory($category);
                }
            });

            // Quick actions
            $('#pls-resync-products').on('click', () => this.runAction('resync_products'));
            $('#pls-resync-bundles').on('click', () => this.runAction('resync_bundles'));
            $('#pls-generate-sample-data').on('click', () => this.runAction('generate_sample_data'));
            $('#pls-delete-sample-data').on('click', () => this.confirmAndDelete());
        },

        /**
         * Initialize underscore templates.
         */
        initTemplates: function() {
            this.resultTemplate = wp.template('test-result');
        },

        /**
         * Run all test categories.
         */
        runAllTests: async function() {
            if (this.isRunning) return;

            this.isRunning = true;
            this.currentCategory = 0;
            this.results = {};

            // Reset UI
            this.resetUI();
            $('#pls-test-progress').show();
            $('#pls-test-summary').hide();
            $('#pls-run-all-tests').addClass('is-loading');

            // Run each category
            for (let i = 0; i < this.categories.length; i++) {
                this.currentCategory = i;
                this.updateProgress(i, this.categories.length);
                
                const category = this.categories[i];
                this.setCategoryStatus(category, 'running');
                
                try {
                    const results = await this.fetchCategoryTests(category);
                    this.results[category] = results;
                    this.renderCategoryResults(category, results);
                    this.setCategoryStatusFromResults(category, results);
                } catch (error) {
                    console.error('Test error:', error);
                    this.setCategoryStatus(category, 'fail');
                    this.results[category] = [{
                        name: 'Test Error',
                        status: 'fail',
                        message: error.message || 'An error occurred while running tests.'
                    }];
                }
            }

            // Show summary
            this.updateProgress(this.categories.length, this.categories.length);
            this.showSummary();

            this.isRunning = false;
            $('#pls-run-all-tests').removeClass('is-loading');
        },

        /**
         * Run a single test category.
         */
        runCategory: async function(category) {
            if (this.isRunning) return;

            this.isRunning = true;
            const $button = $(`.pls-run-category[data-category="${category}"]`);
            $button.addClass('is-loading');

            this.setCategoryStatus(category, 'running');

            try {
                const results = await this.fetchCategoryTests(category);
                this.results[category] = results;
                this.renderCategoryResults(category, results);
                this.setCategoryStatusFromResults(category, results);
                
                // Expand the category to show results
                const $category = $(`.pls-test-category[data-category="${category}"]`);
                this.expandCategory($category);
            } catch (error) {
                console.error('Test error:', error);
                this.setCategoryStatus(category, 'fail');
            }

            this.isRunning = false;
            $button.removeClass('is-loading');
        },

        /**
         * Fetch test results for a category via AJAX.
         */
        fetchCategoryTests: function(category) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: plsSystemTest.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'pls_run_test_category',
                        nonce: plsSystemTest.nonce,
                        category: category
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data?.message || 'Test failed'));
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error(error || 'AJAX error'));
                    }
                });
            });
        },

        /**
         * Confirm and delete sample data.
         */
        confirmAndDelete: function() {
            if (confirm('Are you sure you want to delete ALL sample data?\n\nThis will remove:\n• All products and variations\n• All bundles\n• All sample WooCommerce orders\n• All custom orders\n• All commission records\n• All categories and ingredients\n\nThis action cannot be undone.')) {
                this.runAction('delete_sample_data');
            }
        },

        /**
         * Run a quick action (resync, generate, etc.).
         */
        runAction: async function(action) {
            if (this.isRunning) return;

            const $button = $(`#pls-${action.replace('_', '-')}`);
            $button.addClass('is-loading');

            this.showActionLog();
            const actionName = action.replace('_', ' ');
            this.logAction('Starting ' + actionName + '...');
            
            // Show longer timeout message for sample data generation
            if (action === 'generate_sample_data') {
                this.logAction('This may take 1-3 minutes. Please wait...', 'info');
            }

            try {
                // Increase timeout for sample data generation (can take 1-3 minutes)
                const timeout = action === 'generate_sample_data' ? 300000 : 30000; // 5 minutes for sample data, 30s for others
                
                const response = await $.ajax({
                    url: plsSystemTest.ajaxUrl,
                    type: 'POST',
                    timeout: timeout,
                    data: {
                        action: 'pls_fix_issue',
                        nonce: plsSystemTest.nonce,
                        fix_action: action
                    }
                });

                if (response.success) {
                    // Display action log if available
                    if (response.data?.action_log && Array.isArray(response.data.action_log)) {
                        response.data.action_log.forEach(logEntry => {
                            const message = logEntry.message || logEntry;
                            const type = logEntry.type || 'info';
                            this.logAction(message, type);
                        });
                    } else {
                        this.logAction(response.data.message || 'Action completed successfully', 'success');
                    }
                    
                    // Refresh stats after action
                    setTimeout(() => location.reload(), 1500);
                } else {
                    // Display action log even on error
                    if (response.data?.action_log && Array.isArray(response.data.action_log)) {
                        response.data.action_log.forEach(logEntry => {
                            const message = logEntry.message || logEntry;
                            const type = logEntry.type || 'error';
                            this.logAction(message, type);
                        });
                    } else {
                        this.logAction(response.data?.message || 'Action failed', 'error');
                    }
                }
            } catch (error) {
                this.logAction(error.message || 'Action failed', 'error');
            }

            $button.removeClass('is-loading');
        },

        /**
         * Render test results for a category.
         */
        renderCategoryResults: function(category, results) {
            const $category = $(`.pls-test-category[data-category="${category}"]`);
            const $resultsList = $category.find('.results-list');
            
            $resultsList.empty();

            if (!results || results.length === 0) {
                $resultsList.html('<p>No test results.</p>');
                return;
            }

            results.forEach(result => {
                const html = this.resultTemplate(result);
                $resultsList.append(html);
            });

            // Show toggle button
            $category.find('.toggle-details').show();
        },

        /**
         * Set category status icon.
         */
        setCategoryStatus: function(category, status) {
            const $category = $(`.pls-test-category[data-category="${category}"]`);
            const $status = $category.find('.category-status');
            
            $status.attr('data-status', status);

            let icon = 'clock';
            switch (status) {
                case 'running':
                    icon = 'update';
                    break;
                case 'pass':
                    icon = 'yes-alt';
                    break;
                case 'fail':
                    icon = 'dismiss';
                    break;
                case 'warning':
                    icon = 'warning';
                    break;
            }

            $status.html(`<span class="dashicons dashicons-${icon}"></span>`);
        },

        /**
         * Set category status based on test results.
         */
        setCategoryStatusFromResults: function(category, results) {
            let hasFail = false;
            let hasWarning = false;

            results.forEach(result => {
                if (result.status === 'fail') hasFail = true;
                if (result.status === 'warning') hasWarning = true;
            });

            if (hasFail) {
                this.setCategoryStatus(category, 'fail');
            } else if (hasWarning) {
                this.setCategoryStatus(category, 'warning');
            } else {
                this.setCategoryStatus(category, 'pass');
            }
        },

        /**
         * Toggle category expansion.
         */
        toggleCategory: function($category) {
            if ($category.hasClass('expanded')) {
                this.collapseCategory($category);
            } else {
                this.expandCategory($category);
            }
        },

        /**
         * Expand a category.
         */
        expandCategory: function($category) {
            $category.addClass('expanded');
            $category.find('.category-results').slideDown(200);
        },

        /**
         * Collapse a category.
         */
        collapseCategory: function($category) {
            $category.removeClass('expanded');
            $category.find('.category-results').slideUp(200);
        },

        /**
         * Update progress bar.
         */
        updateProgress: function(current, total) {
            const percent = Math.round((current / total) * 100);
            $('#progress-fill').css('width', percent + '%');
            $('#progress-text').text(`Running tests... ${current}/${total} categories`);

            if (current === total) {
                $('#progress-text').text('All tests completed!');
            }
        },

        /**
         * Reset UI state.
         */
        resetUI: function() {
            // Reset all category statuses
            this.categories.forEach(category => {
                this.setCategoryStatus(category, 'pending');
                const $category = $(`.pls-test-category[data-category="${category}"]`);
                $category.find('.results-list').empty();
                $category.find('.toggle-details').hide();
                this.collapseCategory($category);
            });
        },

        /**
         * Show test summary.
         */
        showSummary: function() {
            let total = 0;
            let passed = 0;
            let failed = 0;
            let warnings = 0;
            let skipped = 0;

            Object.values(this.results).forEach(categoryResults => {
                categoryResults.forEach(result => {
                    total++;
                    switch (result.status) {
                        case 'pass':
                            passed++;
                            break;
                        case 'fail':
                            failed++;
                            break;
                        case 'warning':
                            warnings++;
                            break;
                        case 'skip':
                            skipped++;
                            break;
                    }
                });
            });

            const health = total > 0 ? Math.round((passed / total) * 100) : 0;

            $('#stat-passed').text(passed);
            $('#stat-failed').text(failed);
            $('#stat-warnings').text(warnings);
            $('#stat-skipped').text(skipped);

            const $healthValue = $('#health-score');
            $healthValue.text(health + '%');
            $healthValue.removeClass('health-warning health-error');
            
            if (health < 50) {
                $healthValue.addClass('health-error');
            } else if (health < 80) {
                $healthValue.addClass('health-warning');
            }

            $('#pls-test-summary').show();

            // Auto-scroll to failed tests
            if (failed > 0) {
                const $firstFail = $('.test-result.test-fail').first();
                if ($firstFail.length) {
                    const $category = $firstFail.closest('.pls-test-category');
                    this.expandCategory($category);
                    
                    setTimeout(() => {
                        $('html, body').animate({
                            scrollTop: $firstFail.offset().top - 100
                        }, 300);
                    }, 300);
                }
            }
        },

        /**
         * Show action log.
         */
        showActionLog: function() {
            $('#pls-action-log').show();
        },

        /**
         * Log an action.
         */
        logAction: function(message, type = '') {
            const time = new Date().toLocaleTimeString();
            const typeClass = type ? `log-${type}` : '';
            const html = `<div class="log-entry ${typeClass}">
                <span class="log-time">[${time}]</span>
                <span class="log-message">${message}</span>
            </div>`;
            
            $('#log-content').append(html);
            
            // Auto-scroll to bottom
            const $logContent = $('#log-content');
            $logContent.scrollTop($logContent[0].scrollHeight);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SystemTest.init();
    });

})(jQuery);
