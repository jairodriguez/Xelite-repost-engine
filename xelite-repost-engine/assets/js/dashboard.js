/**
 * Xelite Repost Engine - Dashboard JavaScript
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Dashboard plugin object
    var XeliteDashboard = {
        
        // Initialize the dashboard
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.loadInitialData();
            console.log('Xelite Repost Engine Dashboard initialized');
        },

        // Bind event handlers
        bindEvents: function() {
            // Tab navigation
            $(document).on('click', '.xelite-nav-tabs a', this.handleTabNavigation);
            
            // Settings form submission
            $(document).on('submit', '.xelite-settings-form', this.handleSettingsSave);
            
            // API test buttons
            $(document).on('click', '.xelite-test-api', this.handleApiTest);
            
            // Refresh analytics
            $(document).on('click', '.xelite-refresh-analytics', this.handleRefreshAnalytics);
            
            // Export data
            $(document).on('click', '.xelite-export-data', this.handleExportData);
            
            // Import data
            $(document).on('change', '.xelite-import-file', this.handleImportData);
            
            // Delete patterns
            $(document).on('click', '.xelite-delete-patterns', this.handleDeletePatterns);
            
            // Bulk actions
            $(document).on('change', '.xelite-bulk-action', this.handleBulkAction);
        },

        // Initialize components
        initComponents: function() {
            this.initCharts();
            this.initDataTables();
            this.initTooltips();
            this.initAutoSave();
            this.initCharacterCount();
        },

        // Load initial data
        loadInitialData: function() {
            this.loadAnalytics();
            this.loadRecentPatterns();
            this.loadSettings();
        },

        // Handle tab navigation
        handleTabNavigation: function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var target = $link.attr('href');
            
            // Update active tab
            $('.xelite-nav-tabs a').removeClass('active');
            $link.addClass('active');
            
            // Show target content
            $('.xelite-tab-content').hide();
            $(target).show();
            
            // Load tab-specific data
            XeliteDashboard.loadTabData(target);
        },

        // Load tab-specific data
        loadTabData: function(tabId) {
            switch (tabId) {
                case '#analytics':
                    XeliteDashboard.loadAnalytics();
                    break;
                case '#patterns':
                    XeliteDashboard.loadPatterns();
                    break;
                case '#generator':
                    XeliteDashboard.loadGeneratorSettings();
                    break;
                case '#settings':
                    XeliteDashboard.loadSettings();
                    break;
            }
        },

        // Load analytics data
        loadAnalytics: function() {
            var $container = $('#analytics-content');
            
            $container.html('<div class="xelite-loading">Loading analytics...</div>');
            
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_get_analytics',
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteDashboard.displayAnalytics(response.data);
                    } else {
                        $container.html('<div class="xelite-error">' + (response.data.message || xeliteRepostEngine.strings.error) + '</div>');
                    }
                },
                error: function() {
                    $container.html('<div class="xelite-error">' + xeliteRepostEngine.strings.error + '</div>');
                }
            });
        },

        // Display analytics
        displayAnalytics: function(data) {
            var html = '<div class="xelite-analytics-grid">';
            
            // Analytics cards
            html += '<div class="xelite-analytics-card">';
            html += '<span class="xelite-analytics-number">' + data.total_patterns + '</span>';
            html += '<span class="xelite-analytics-label">Total Patterns</span>';
            html += '</div>';
            
            html += '<div class="xelite-analytics-card secondary">';
            html += '<span class="xelite-analytics-number">' + data.total_reposts + '</span>';
            html += '<span class="xelite-analytics-label">Total Reposts</span>';
            html += '</div>';
            
            html += '<div class="xelite-analytics-card warning">';
            html += '<span class="xelite-analytics-number">' + data.avg_reposts + '</span>';
            html += '<span class="xelite-analytics-label">Avg Reposts</span>';
            html += '</div>';
            
            html += '<div class="xelite-analytics-card danger">';
            html += '<span class="xelite-analytics-number">' + data.top_accounts + '</span>';
            html += '<span class="xelite-analytics-label">Top Accounts</span>';
            html += '</div>';
            
            html += '</div>';
            
            // Charts container
            html += '<div class="xelite-charts-container">';
            html += '<div class="xelite-chart-wrapper">';
            html += '<canvas id="reposts-chart"></canvas>';
            html += '</div>';
            html += '<div class="xelite-chart-wrapper">';
            html += '<canvas id="patterns-chart"></canvas>';
            html += '</div>';
            html += '</div>';
            
            $('#analytics-content').html(html);
            
            // Initialize charts
            XeliteDashboard.initCharts(data);
        },

        // Initialize charts
        initCharts: function(data) {
            // Reposts over time chart
            if (data.reposts_chart && $('#reposts-chart').length) {
                var ctx = document.getElementById('reposts-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.reposts_chart.labels,
                        datasets: [{
                            label: 'Reposts',
                            data: data.reposts_chart.data,
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Reposts Over Time'
                            }
                        }
                    }
                });
            }
            
            // Patterns by account chart
            if (data.patterns_chart && $('#patterns-chart').length) {
                var ctx = document.getElementById('patterns-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.patterns_chart.labels,
                        datasets: [{
                            data: data.patterns_chart.data,
                            backgroundColor: [
                                '#0073aa',
                                '#00a32a',
                                '#dba617',
                                '#d63638',
                                '#8c8f94'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Patterns by Account'
                            }
                        }
                    }
                });
            }
        },

        // Load patterns
        loadPatterns: function() {
            var $container = $('#patterns-content');
            
            $container.html('<div class="xelite-loading">Loading patterns...</div>');
            
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_get_patterns',
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteDashboard.displayPatterns(response.data);
                    } else {
                        $container.html('<div class="xelite-error">' + (response.data.message || xeliteRepostEngine.strings.error) + '</div>');
                    }
                },
                error: function() {
                    $container.html('<div class="xelite-error">' + xeliteRepostEngine.strings.error + '</div>');
                }
            });
        },

        // Display patterns
        displayPatterns: function(data) {
            var html = '<div class="xelite-patterns-header">';
            html += '<h4>Repost Patterns (' + data.total + ')</h4>';
            html += '<div class="xelite-patterns-actions">';
            html += '<button class="xelite-refresh-patterns button">Refresh</button>';
            html += '<button class="xelite-export-data button">Export</button>';
            html += '<button class="xelite-delete-patterns button button-link-delete">Delete All</button>';
            html += '</div>';
            html += '</div>';
            
            if (data.patterns && data.patterns.length > 0) {
                html += '<div class="xelite-patterns-list">';
                data.patterns.forEach(function(pattern) {
                    html += '<div class="xelite-pattern-item" data-id="' + pattern.id + '">';
                    html += '<div class="xelite-pattern-header">';
                    html += '<span class="xelite-pattern-source">@' + XeliteDashboard.escapeHtml(pattern.source_handle) + '</span>';
                    html += '<span class="xelite-pattern-count">' + pattern.repost_count + ' reposts</span>';
                    html += '</div>';
                    html += '<div class="xelite-pattern-text">' + XeliteDashboard.escapeHtml(pattern.original_text) + '</div>';
                    html += '<div class="xelite-pattern-meta">';
                    html += '<span>' + XeliteDashboard.formatDate(pattern.created_at) + '</span>';
                    html += '<input type="checkbox" class="xelite-pattern-select" value="' + pattern.id + '">';
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
                
                if (data.has_more) {
                    html += '<div class="xelite-load-more-container">';
                    html += '<button class="xelite-load-more button" data-offset="' + data.patterns.length + '">Load More</button>';
                    html += '</div>';
                }
            } else {
                html += '<div class="xelite-no-data">No patterns found.</div>';
            }
            
            $('#patterns-content').html(html);
        },

        // Handle settings save
        handleSettingsSave: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var formData = $form.serialize();
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: formData + '&action=xelite_repost_engine_save_settings&nonce=' + xeliteRepostEngine.nonce,
                success: function(response) {
                    if (response.success) {
                        XeliteDashboard.showMessage('Settings saved successfully!', 'success');
                    } else {
                        XeliteDashboard.showMessage(response.data.message || xeliteRepostEngine.strings.error, 'error');
                    }
                },
                error: function() {
                    XeliteDashboard.showMessage(xeliteRepostEngine.strings.error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save Settings');
                }
            });
        },

        // Handle API test
        handleApiTest: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var apiType = $button.data('api');
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_test_api',
                    api_type: apiType,
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteDashboard.showMessage(apiType + ' API connection successful!', 'success');
                    } else {
                        XeliteDashboard.showMessage(response.data.message || 'API test failed.', 'error');
                    }
                },
                error: function() {
                    XeliteDashboard.showMessage('API test failed.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test ' + apiType + ' API');
                }
            });
        },

        // Handle refresh analytics
        handleRefreshAnalytics: function(e) {
            e.preventDefault();
            XeliteDashboard.loadAnalytics();
        },

        // Handle export data
        handleExportData: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Exporting...');
            
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_export_data',
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        var link = document.createElement('a');
                        link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data.csv);
                        link.download = 'xelite-repost-patterns-' + new Date().toISOString().split('T')[0] + '.csv';
                        link.click();
                        
                        XeliteDashboard.showMessage('Data exported successfully!', 'success');
                    } else {
                        XeliteDashboard.showMessage(response.data.message || 'Export failed.', 'error');
                    }
                },
                error: function() {
                    XeliteDashboard.showMessage('Export failed.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Export');
                }
            });
        },

        // Handle import data
        handleImportData: function(e) {
            var file = e.target.files[0];
            if (!file) return;
            
            var reader = new FileReader();
            reader.onload = function(e) {
                var csv = e.target.result;
                
                $.ajax({
                    url: xeliteRepostEngine.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'xelite_repost_engine_import_data',
                        csv: csv,
                        nonce: xeliteRepostEngine.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            XeliteDashboard.showMessage('Data imported successfully!', 'success');
                            XeliteDashboard.loadPatterns();
                        } else {
                            XeliteDashboard.showMessage(response.data.message || 'Import failed.', 'error');
                        }
                    },
                    error: function() {
                        XeliteDashboard.showMessage('Import failed.', 'error');
                    }
                });
            };
            reader.readAsText(file);
        },

        // Handle delete patterns
        handleDeletePatterns: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete all patterns? This action cannot be undone.')) {
                return;
            }
            
            var $button = $(this);
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_delete_patterns',
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteDashboard.showMessage('Patterns deleted successfully!', 'success');
                        XeliteDashboard.loadPatterns();
                    } else {
                        XeliteDashboard.showMessage(response.data.message || 'Delete failed.', 'error');
                    }
                },
                error: function() {
                    XeliteDashboard.showMessage('Delete failed.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Delete All');
                }
            });
        },

        // Handle bulk action
        handleBulkAction: function(e) {
            var action = $(this).val();
            var selectedPatterns = $('.xelite-pattern-select:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedPatterns.length === 0) {
                XeliteDashboard.showMessage('Please select patterns to perform bulk action.', 'warning');
                return;
            }
            
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete the selected patterns?')) {
                    return;
                }
            }
            
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_bulk_action',
                    bulk_action: action,
                    pattern_ids: selectedPatterns,
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteDashboard.showMessage('Bulk action completed successfully!', 'success');
                        XeliteDashboard.loadPatterns();
                    } else {
                        XeliteDashboard.showMessage(response.data.message || 'Bulk action failed.', 'error');
                    }
                },
                error: function() {
                    XeliteDashboard.showMessage('Bulk action failed.', 'error');
                }
            });
        },

        // Initialize data tables
        initDataTables: function() {
            if ($.fn.DataTable) {
                $('.xelite-data-table').DataTable({
                    responsive: true,
                    pageLength: 25,
                    order: [[0, 'desc']]
                });
            }
        },

        // Initialize tooltips
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var $element = $(this);
                var tooltip = $element.data('tooltip');
                
                $element.attr('title', tooltip);
            });
        },

        // Initialize auto save
        initAutoSave: function() {
            var autoSaveTimer;
            
            $('.xelite-auto-save').each(function() {
                var $input = $(this);
                
                $input.on('input', function() {
                    clearTimeout(autoSaveTimer);
                    
                    autoSaveTimer = setTimeout(function() {
                        XeliteDashboard.autoSave($input);
                    }, 2000);
                });
            });
        },

        // Auto save functionality
        autoSave: function($input) {
            var data = {
                action: 'xelite_repost_engine_auto_save',
                field: $input.attr('name'),
                value: $input.val(),
                nonce: xeliteRepostEngine.nonce
            };
            
            $.post(xeliteRepostEngine.ajax_url, data, function(response) {
                if (response.success) {
                    console.log('Auto-saved successfully');
                }
            });
        },

        // Initialize character count
        initCharacterCount: function() {
            $('.xelite-character-count').each(function() {
                var $input = $(this);
                var $counter = $input.siblings('.xelite-char-counter');
                var maxLength = parseInt($input.attr('maxlength') || 280);
                
                $input.on('input', function() {
                    var length = $input.val().length;
                    var remaining = maxLength - length;
                    
                    $counter.text(remaining + ' characters remaining');
                    
                    if (remaining < 0) {
                        $counter.addClass('over-limit');
                    } else {
                        $counter.removeClass('over-limit');
                    }
                });
            });
        },

        // Show message
        showMessage: function(message, type) {
            var $message = $('<div class="xelite-message ' + type + '">' + message + '</div>');
            
            $('.xelite-dashboard').prepend($message);
            
            // Auto remove after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Escape HTML
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        // Format date
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        XeliteDashboard.init();
    });

    // Expose to global scope for debugging
    window.XeliteDashboard = XeliteDashboard;

})(jQuery); 