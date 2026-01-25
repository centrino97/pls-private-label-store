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

            // Sample data actions
            $('#pls-generate-sample-data').on('click', () => this.runAction('generate_sample_data'));
            $('#pls-generate-orders').on('click', () => this.runAction('generate_orders'));
            $('#pls-delete-sample-data').on('click', () => this.confirmAndDelete());
            
            // Log viewing
            $('#pls-view-last-log').on('click', () => this.viewLastLog());
            $('#pls-copy-log').on('click', () => this.copyLog());
            
            // Modal close
            $('#pls-modal-close').on('click', () => this.hideGenerationModal());
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
         * Run a quick action (generate sample data, generate orders, etc.).
         */
        runAction: async function(action) {
            if (this.isRunning) return;

            let $button = $(`#pls-${action.replace('_', '-')}`);
            if (!$button.length) {
                // Try alternative selector format
                $button = $(`#pls-${action}`);
            }
            
            $button.addClass('is-loading').prop('disabled', true);
            this.isRunning = true;

            // Show loading modal
            const actionName = action.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            const isLongOperation = action === 'generate_sample_data' || action === 'generate_orders';
            
            this.showGenerationModal(actionName, isLongOperation);

            try {
                // Determine AJAX action name
                let ajaxAction = 'pls_fix_issue';
                if (action === 'generate_orders') {
                    ajaxAction = 'pls_generate_orders';
                }
                
                // Increase timeout for sample data generation (can take 1-3 minutes)
                const timeout = isLongOperation ? 300000 : 30000;
                
                const response = await $.ajax({
                    url: plsSystemTest.ajaxUrl,
                    type: 'POST',
                    timeout: timeout,
                    data: {
                        action: ajaxAction,
                        nonce: plsSystemTest.nonce,
                        fix_action: action
                    },
                    // Show progress updates if available
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        // Note: Server-sent events would be better, but for now we'll update on response
                        return xhr;
                    }
                });

                // Update modal with final status
                if (response.success) {
                    // Display action log entries if available
                    if (response.data?.action_log && Array.isArray(response.data.action_log)) {
                        response.data.action_log.forEach(logEntry => {
                            const message = logEntry.message || logEntry;
                            const type = logEntry.type || 'info';
                            this.addLogEntry(message, type);
                        });
                    }
                    
                    this.updateModalStatus('success', 'Generation completed successfully!');
                    
                    if (response.data?.log_file_path) {
                        this.addLogEntry('Log file saved. Use "View Last Log" to see details.', 'info');
                    }
                    
                    // Show close button and auto-close after 3 seconds
                    $('#pls-modal-close').show();
                    setTimeout(() => {
                        this.hideGenerationModal();
                        location.reload();
                    }, 3000);
                } else {
                    // Display error log entries
                    if (response.data?.action_log && Array.isArray(response.data.action_log)) {
                        response.data.action_log.forEach(logEntry => {
                            const message = logEntry.message || logEntry;
                            const type = logEntry.type || 'error';
                            this.addLogEntry(message, type);
                        });
                    } else {
                        this.addLogEntry(response.data?.message || 'Action failed', 'error');
                    }
                    
                    this.updateModalStatus('error', 'Generation failed');
                    
                    if (response.data?.log_file_path) {
                        this.addLogEntry('Log file saved. Click "View Last Log" button below to see detailed error information.', 'info');
                    } else {
                        this.addLogEntry('Note: Log file may not have been created. Try "View Last Log" to check.', 'warning');
                    }
                    
                    $('#pls-modal-close').show();
                }
            } catch (error) {
                const errorMsg = error.responseJSON?.data?.message || error.message || 'Action failed';
                this.addLogEntry(errorMsg, 'error');
                
                // Try to show log file path if available
                if (error.responseJSON?.data?.log_file_path) {
                    this.addLogEntry('Log file saved. Click "View Last Log" button below to see detailed error information.', 'info');
                } else {
                    this.addLogEntry('Note: Try "View Last Log" to check if a log file was created.', 'warning');
                }
                
                this.updateModalStatus('error', 'Generation failed');
                $('#pls-modal-close').show();
            }

            $button.removeClass('is-loading').prop('disabled', false);
            this.isRunning = false;
        },

        /**
         * Show generation modal.
         */
        showGenerationModal: function(actionName, isLongOperation) {
            $('#pls-modal-title').text(actionName);
            $('#pls-modal-status').text('Starting...');
            $('#pls-modal-message').text(isLongOperation 
                ? 'This may take 1-3 minutes. Please wait...' 
                : 'Processing...');
            $('#pls-log-entries').empty();
            $('#pls-progress-log').hide();
            $('#pls-modal-close').hide();
            $('#pls-generation-modal').addClass('is-active');
        },

        /**
         * Hide generation modal.
         */
        hideGenerationModal: function() {
            $('#pls-generation-modal').removeClass('is-active');
        },

        /**
         * Update modal status.
         */
        updateModalStatus: function(status, message) {
            const $status = $('#pls-modal-status');
            const $spinner = $('.pls-spinner');
            
            $status.text(message);
            
            if (status === 'success') {
                $status.css('color', 'var(--pls-success)');
                $spinner.css('border-top-color', 'var(--pls-success)');
            } else if (status === 'error') {
                $status.css('color', 'var(--pls-error)');
                $spinner.css('border-top-color', 'var(--pls-error)');
            }
        },

        /**
         * Add log entry to modal.
         */
        addLogEntry: function(message, type) {
            const $log = $('#pls-progress-log');
            const $entries = $('#pls-log-entries');
            
            // Show log area if hidden
            if (!$log.is(':visible')) {
                $log.show();
            }
            
            // Update status with latest message
            if (type === 'success' || type === 'error') {
                $('#pls-modal-status').text(message);
            }
            
            // Add log entry
            const typeClass = 'pls-log-' + (type || 'info');
            const $entry = $('<div class="pls-log-entry ' + typeClass + '">' + this.escapeHtml(message) + '</div>');
            $entries.append($entry);
            
            // Auto-scroll to bottom
            $log.scrollTop($log[0].scrollHeight);
        },

        /**
         * Escape HTML to prevent XSS.
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
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
         * View last generation log.
         */
        viewLastLog: async function() {
            const $button = $('#pls-view-last-log');
            const $viewer = $('#pls-log-viewer');
            
            // Toggle if already visible
            if ($viewer.is(':visible')) {
                $viewer.slideUp(300);
                $button.html('<span class="dashicons dashicons-visibility"></span> View Last Log');
                return;
            }
            
            $button.addClass('is-loading').prop('disabled', true);

            try {
                const response = await $.ajax({
                    url: plsSystemTest.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'pls_get_last_log',
                        nonce: plsSystemTest.nonce
                    }
                });

                if (response.success) {
                    $('#pls-log-filename').text(response.data.filename);
                    $('#pls-log-time').text('(' + response.data.file_time_formatted + ')');
                    $('#pls-log-content').val(response.data.log_content);
                    
                    // Set download link
                    const uploadDir = plsSystemTest.uploadUrl || '/wp-content/uploads/';
                    const logUrl = uploadDir + 'pls-logs/' + response.data.filename;
                    $('#pls-download-log').attr('href', logUrl);
                    
                    $viewer.slideDown(300);
                    $button.html('<span class="dashicons dashicons-hidden"></span> Hide Log').removeClass('is-loading');
                } else {
                    alert('No log file found. Generate sample data or orders first.');
                }
            } catch (error) {
                alert('Failed to load log: ' + (error.message || 'Unknown error'));
            }

            $button.prop('disabled', false);
        },

        /**
         * Copy log content to clipboard.
         */
        copyLog: function() {
            const $logContent = $('#pls-log-content');
            const logText = $logContent.val();

            if (!logText) {
                alert('No log content to copy.');
                return;
            }

            // Use Clipboard API if available
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(logText).then(() => {
                    const $button = $('#pls-copy-log');
                    const originalText = $button.html();
                    $button.html('<span class="dashicons dashicons-yes-alt"></span> Copied!');
                    setTimeout(() => {
                        $button.html(originalText);
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    this.fallbackCopyLog(logText);
                });
            } else {
                this.fallbackCopyLog(logText);
            }
        },

        /**
         * Fallback copy method for older browsers.
         */
        fallbackCopyLog: function(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                const $button = $('#pls-copy-log');
                const originalText = $button.html();
                $button.html('<span class="dashicons dashicons-yes-alt"></span> Copied!');
                setTimeout(() => {
                    $button.html(originalText);
                }, 2000);
            } catch (err) {
                alert('Failed to copy. Please select and copy manually.');
            }
            
            document.body.removeChild(textarea);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SystemTest.init();
    });

})(jQuery);
