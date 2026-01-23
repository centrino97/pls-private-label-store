/**
 * PLS Debug Console - Client-side logging and console UI
 * CSP-safe implementation (no eval, no new Function)
 */

(function($) {
    'use strict';

    var PLS_Debug_Console = {
        logs: [],
        isOpen: false,
        currentFilter: 'all',

        init: function() {
            // Always create console if debug is enabled (even if PLS_Debug object not yet loaded)
            var debugEnabled = (typeof PLS_Debug !== 'undefined' && PLS_Debug.enabled) || 
                              (typeof window.plsDebugEnabled !== 'undefined' && window.plsDebugEnabled);
            
            if (!debugEnabled) {
                // Check option via AJAX if not available
                return;
            }

            this.createConsole();
            this.bindEvents();
            this.bindKeyboardShortcut();
            this.logPageLoad();
        },
        
        bindKeyboardShortcut: function() {
            var self = this;
            $(document).on('keydown', function(e) {
                // Use Ctrl+Shift+Alt+D to avoid Chrome bookmark conflict
                if (e.ctrlKey && e.shiftKey && e.altKey && e.key === 'D') {
                    e.preventDefault();
                    e.stopPropagation();
                    self.toggle();
                }
            });
        },

        createConsole: function() {
            var $console = $('<div>')
                .attr('id', 'pls-debug-console')
                .addClass('pls-debug-console')
                .html(
                    '<div class="pls-debug-console__header">' +
                        '<span class="pls-debug-console__title">PLS Debug Console</span>' +
                        '<div class="pls-debug-console__controls">' +
                            '<select class="pls-debug-filter">' +
                                '<option value="all">All</option>' +
                                '<option value="debug">Debug</option>' +
                                '<option value="info">Info</option>' +
                                '<option value="warn">Warnings</option>' +
                                '<option value="error">Errors</option>' +
                            '</select>' +
                            '<button class="pls-debug-btn pls-debug-btn--clear" title="Clear logs">Clear</button>' +
                            '<button class="pls-debug-btn pls-debug-btn--export" title="Export logs">Export</button>' +
                            '<button class="pls-debug-btn pls-debug-btn--toggle" title="Toggle console">' +
                                '<span class="pls-debug-toggle-icon">▼</span>' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="pls-debug-console__body">' +
                        '<div class="pls-debug-console__logs"></div>' +
                    '</div>'
                );

            $('body').append($console);
        },

        bindEvents: function() {
            var self = this;

            $(document).on('click', '.pls-debug-btn--toggle', function() {
                self.toggle();
            });

            $(document).on('click', '.pls-debug-btn--clear', function() {
                self.clearLogs();
            });

            $(document).on('click', '.pls-debug-btn--export', function() {
                self.exportLogs();
            });

            $(document).on('change', '.pls-debug-filter', function() {
                self.currentFilter = $(this).val();
                self.renderLogs();
            });
        },

        toggle: function() {
            var $console = $('#pls-debug-console');
            this.isOpen = !this.isOpen;
            
            if (this.isOpen) {
                $console.addClass('pls-debug-console--open');
                $('.pls-debug-toggle-icon').text('▲');
            } else {
                $console.removeClass('pls-debug-console--open');
                $('.pls-debug-toggle-icon').text('▼');
            }
        },

        addLogs: function(logs) {
            if (!Array.isArray(logs)) {
                return;
            }

            var self = this;
            logs.forEach(function(log) {
                self.addLog(log);
            });
            this.renderLogs();
        },

        addLog: function(log) {
            if (!log || typeof log !== 'object') {
                return;
            }

            this.logs.push(log);
            
            // Limit log size
            if (this.logs.length > 1000) {
                this.logs.shift();
            }

            // Auto-scroll if console is open
            if (this.isOpen) {
                this.renderLogs();
            }
        },

        renderLogs: function() {
            var $container = $('.pls-debug-console__logs');
            if (!$container.length) {
                return;
            }

            $container.empty();

            var filteredLogs = this.logs;
            if (this.currentFilter !== 'all') {
                filteredLogs = this.logs.filter(function(log) {
                    return log.level === this.currentFilter;
                }.bind(this));
            }

            var self = this;
            filteredLogs.forEach(function(log) {
                $container.append(self.renderLogEntry(log));
            });

            // Scroll to bottom
            $container.scrollTop($container[0].scrollHeight);
        },

        renderLogEntry: function(log) {
            var level = log.level || 'debug';
            var message = this.escapeHtml(String(log.message || ''));
            var time = log.time || '';
            var context = log.context || {};

            var $entry = $('<div>')
                .addClass('pls-debug-log')
                .addClass('pls-debug-log--' + level)
                .html(
                    '<span class="pls-debug-log__time">' + this.escapeHtml(time) + '</span> ' +
                    '<span class="pls-debug-log__level">[' + level.toUpperCase() + ']</span> ' +
                    '<span class="pls-debug-log__message">' + message + '</span>'
                );

            // Add context if available
            if (Object.keys(context).length > 0) {
                var $context = $('<div>')
                    .addClass('pls-debug-log__context')
                    .text(JSON.stringify(context, null, 2));
                $entry.append($context);
            }

            return $entry;
        },

        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        clearLogs: function() {
            this.logs = [];
            this.renderLogs();
        },

        exportLogs: function() {
            var dataStr = JSON.stringify(this.logs, null, 2);
            var dataBlob = new Blob([dataStr], { type: 'application/json' });
            var url = URL.createObjectURL(dataBlob);
            var link = document.createElement('a');
            link.href = url;
            link.download = 'pls-debug-logs-' + new Date().toISOString().split('T')[0] + '.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        logPageLoad: function() {
            if (typeof PLS_Debug !== 'undefined' && PLS_Debug.enabled) {
                var page = window.location.pathname + window.location.search;
                this.addLog({
                    level: 'debug',
                    message: 'Page Load: ' + page,
                    time: new Date().toLocaleTimeString(),
                    context: {
                        url: window.location.href,
                        user_agent: navigator.userAgent
                    }
                });
            }
        },

        loadInitialLogs: function() {
            // Load logs passed via wp_localize_script (CSP-safe)
            if (typeof PLS_Debug_Logs !== 'undefined' && Array.isArray(PLS_Debug_Logs)) {
                this.addLogs(PLS_Debug_Logs);
            }
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        // Check if debug is enabled (multiple ways to check)
        var debugEnabled = (typeof PLS_Debug !== 'undefined' && PLS_Debug.enabled) || 
                          (typeof window.plsDebugEnabled !== 'undefined' && window.plsDebugEnabled);
        
        if (debugEnabled) {
            PLS_Debug_Console.init();
            // Load initial logs after initialization
            setTimeout(function() {
                PLS_Debug_Console.loadInitialLogs();
            }, 100);
        }
    });

    // Expose globally
    window.PLS_Debug_Console = PLS_Debug_Console;

})(jQuery);
