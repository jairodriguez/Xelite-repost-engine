/**
 * Cron Admin JavaScript
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        XeliteCronAdmin.init();
    });

    // Main object
    var XeliteCronAdmin = {
        
        /**
         * Initialize the admin interface
         */
        init: function() {
            this.bindEvents();
            this.autoRefreshStatus();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $('#xelite-test-cron').on('click', this.testCronConnection);
            $('#xelite-manual-scraping').on('click', this.runManualScraping);
            $('#xelite-refresh-status').on('click', this.refreshStatus);
            
            // Settings form submission
            $('form').on('submit', this.handleFormSubmit);
        },

        /**
         * Test cron connection
         */
        testCronConnection: function(e) {
            e.preventDefault();
            
            if (!confirm(xeliteCronAdmin.strings.confirmTest)) {
                return;
            }
            
            var $button = $(this);
            var $results = $('#xelite-action-results');
            
            $button.prop('disabled', true).text(xeliteCronAdmin.strings.testing);
            $results.html('<div class="notice notice-info"><p>' + xeliteCronAdmin.strings.testing + '</p></div>');
            
            $.ajax({
                url: xeliteCronAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'xelite_test_cron_connection',
                    nonce: xeliteCronAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var status = response.data;
                        var statusClass = 'notice-success';
                        var statusText = xeliteCronAdmin.strings.success;
                        
                        if (status.status === 'error') {
                            statusClass = 'notice-error';
                            statusText = xeliteCronAdmin.strings.error;
                        } else if (status.status === 'warning') {
                            statusClass = 'notice-warning';
                            statusText = 'Warning';
                        }
                        
                        var issuesHtml = '';
                        if (status.issues && status.issues.length > 0) {
                            issuesHtml = '<ul><li>' + status.issues.join('</li><li>') + '</li></ul>';
                        }
                        
                        $results.html(
                            '<div class="notice ' + statusClass + '">' +
                            '<p><strong>' + statusText + ':</strong> Cron health check completed.</p>' +
                            '<p><strong>Status:</strong> ' + status.status + '</p>' +
                            '<p><strong>Last Run:</strong> ' + (status.last_run || 'Never') + '</p>' +
                            '<p><strong>Next Run:</strong> ' + (status.next_run || 'Not scheduled') + '</p>' +
                            '<p><strong>Memory Usage:</strong> ' + XeliteCronAdmin.formatBytes(status.memory_usage) + '</p>' +
                            issuesHtml +
                            '</div>'
                        );
                    } else {
                        $results.html('<div class="notice notice-error"><p>Error: ' + (response.data || 'Unknown error') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $results.html('<div class="notice notice-error"><p>AJAX Error: ' + error + '</p></div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Cron Connection');
                }
            });
        },

        /**
         * Run manual scraping
         */
        runManualScraping: function(e) {
            e.preventDefault();
            
            if (!confirm(xeliteCronAdmin.strings.confirmManual)) {
                return;
            }
            
            var $button = $(this);
            var $results = $('#xelite-action-results');
            
            $button.prop('disabled', true).text('Running...');
            $results.html('<div class="notice notice-info"><p>Running manual scraping...</p></div>');
            
            $.ajax({
                url: xeliteCronAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'xelite_manual_scraping',
                    nonce: xeliteCronAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var results = response.data;
                        var successCount = results.success || 0;
                        var errorCount = results.errors || 0;
                        var duplicateCount = results.duplicates || 0;
                        
                        var resultHtml = '<div class="notice notice-success">' +
                            '<p><strong>Manual scraping completed!</strong></p>' +
                            '<p><strong>Successful:</strong> ' + successCount + '</p>' +
                            '<p><strong>Errors:</strong> ' + errorCount + '</p>' +
                            '<p><strong>Duplicates:</strong> ' + duplicateCount + '</p>';
                        
                        if (results.accounts && Object.keys(results.accounts).length > 0) {
                            resultHtml += '<p><strong>Account Results:</strong></p><ul>';
                            $.each(results.accounts, function(account, accountResults) {
                                resultHtml += '<li><strong>' + account + ':</strong> ' + 
                                    accountResults.stored + ' stored, ' + 
                                    accountResults.errors + ' errors</li>';
                            });
                            resultHtml += '</ul>';
                        }
                        
                        resultHtml += '</div>';
                        $results.html(resultHtml);
                    } else {
                        $results.html('<div class="notice notice-error"><p>Error: ' + (response.data || 'Unknown error') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $results.html('<div class="notice notice-error"><p>AJAX Error: ' + error + '</p></div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Run Manual Scraping');
                }
            });
        },

        /**
         * Refresh status
         */
        refreshStatus: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $results = $('#xelite-action-results');
            
            $button.prop('disabled', true).text('Refreshing...');
            $results.html('<div class="notice notice-info"><p>Refreshing status...</p></div>');
            
            $.ajax({
                url: xeliteCronAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'xelite_get_cron_status',
                    nonce: xeliteCronAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var status = response.data;
                        var health = status.health_status;
                        var stats = status.statistics;
                        
                        var statusHtml = '<div class="notice notice-info">' +
                            '<p><strong>Status refreshed!</strong></p>' +
                            '<p><strong>Overall Status:</strong> ' + health.status + '</p>' +
                            '<p><strong>Last Run:</strong> ' + (health.last_run || 'Never') + '</p>' +
                            '<p><strong>Next Run:</strong> ' + (health.next_run || 'Not scheduled') + '</p>' +
                            '<p><strong>Total Runs:</strong> ' + (stats.total_runs || 0) + '</p>' +
                            '<p><strong>Success Rate:</strong> ' + (stats.success_rate || 0) + '%</p>' +
                            '<p><strong>Total Reposts Scraped:</strong> ' + (stats.total_reposts_scraped || 0) + '</p>' +
                            '</div>';
                        
                        $results.html(statusHtml);
                        
                        // Update the status overview section
                        XeliteCronAdmin.updateStatusOverview(status);
                    } else {
                        $results.html('<div class="notice notice-error"><p>Error: ' + (response.data || 'Unknown error') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $results.html('<div class="notice notice-error"><p>AJAX Error: ' + error + '</p></div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Refresh Status');
                }
            });
        },

        /**
         * Update status overview section
         */
        updateStatusOverview: function(status) {
            var health = status.health_status;
            var stats = status.statistics;
            
            // Update status badge
            $('.xelite-status-badge').text(health.status.charAt(0).toUpperCase() + health.status.slice(1));
            
            // Update status class
            $('.xelite-status-overview').removeClass('xelite-status-healthy xelite-status-warning xelite-status-error')
                .addClass('xelite-status-' + health.status);
            
            // Update status items
            $('.xelite-status-item').each(function() {
                var $item = $(this);
                var label = $item.find('strong').text();
                
                if (label.includes('Last Run:')) {
                    $item.find('span').text(health.last_run || 'Never');
                } else if (label.includes('Next Run:')) {
                    $item.find('span').text(health.next_run || 'Not scheduled');
                } else if (label.includes('Lock File:')) {
                    $item.find('span').text(health.lock_file_exists ? 'Exists (may be stuck)' : 'None');
                }
            });
            
            // Update statistics
            $('.xelite-stat-item').each(function() {
                var $item = $(this);
                var label = $item.find('strong').text();
                
                if (label.includes('Total Runs:')) {
                    $item.find('span').text(stats.total_runs || 0);
                } else if (label.includes('Success Rate:')) {
                    $item.find('span').text((stats.success_rate || 0) + '%');
                } else if (label.includes('Total Reposts Scraped:')) {
                    $item.find('span').text(stats.total_reposts_scraped || 0);
                } else if (label.includes('Total Errors:')) {
                    $item.find('span').text(stats.total_errors || 0);
                } else if (label.includes('Average Execution Time:')) {
                    $item.find('span').text((stats.execution_time || 0) + 's');
                } else if (label.includes('Memory Usage:')) {
                    $item.find('span').text(XeliteCronAdmin.formatBytes(health.memory_usage));
                }
            });
            
            // Update issues section
            var $issuesSection = $('.xelite-status-issues');
            if (health.issues && health.issues.length > 0) {
                var issuesHtml = '<h3>Issues Found:</h3><ul>';
                $.each(health.issues, function(index, issue) {
                    issuesHtml += '<li>' + issue + '</li>';
                });
                issuesHtml += '</ul>';
                
                if ($issuesSection.length) {
                    $issuesSection.html(issuesHtml);
                } else {
                    $('.xelite-status-overview').append('<div class="xelite-status-issues">' + issuesHtml + '</div>');
                }
            } else {
                $issuesSection.remove();
            }
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            
            $submitButton.prop('disabled', true).val('Saving...');
            
            // Re-enable after a short delay to allow form submission
            setTimeout(function() {
                $submitButton.prop('disabled', false).val('Save Changes');
            }, 1000);
        },

        /**
         * Auto refresh status every 30 seconds
         */
        autoRefreshStatus: function() {
            setInterval(function() {
                // Only refresh if the page is visible and no actions are in progress
                if (!document.hidden && !$('#xelite-action-results .notice').length) {
                    XeliteCronAdmin.refreshStatus({ preventDefault: function() {} });
                }
            }, 30000); // 30 seconds
        },

        /**
         * Format bytes to human readable format
         */
        formatBytes: function(bytes) {
            if (bytes === 0) return '0 B';
            
            var units = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(1024));
            
            return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + units[i];
        }
    };

})(jQuery); 