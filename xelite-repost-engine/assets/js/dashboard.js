/**
 * Xelite Repost Engine Dashboard JavaScript
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Dashboard namespace
    window.XeliteDashboard = {
        
        /**
         * Initialize dashboard functionality
         */
        init: function() {
            this.bindEvents();
            this.initModals();
            this.initTabs();
            this.initForms();
            this.initCharts();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Quick generate button
            $(document).on('click', '#quick-generate', this.showQuickGenerateModal);
            
            // Generate content button
            $(document).on('click', '#generate-content', this.generateContent);
            
            // Save content button
            $(document).on('click', '#save-content', this.saveContent);
            
            // Copy content buttons
            $(document).on('click', '.xelite-content-btn.copy', this.copyContent);
            
            // Save content buttons
            $(document).on('click', '.xelite-content-btn.save', this.saveContent);
            
            // Edit content buttons
            $(document).on('click', '.xelite-content-btn.edit', this.editContent);
            
            // Optimize content buttons
            $(document).on('click', '.xelite-content-btn.optimize', this.optimizeContent);
            
            // Regenerate content buttons
            $(document).on('click', '.xelite-content-btn.regenerate', this.regenerateContent);
            
            // Pattern analysis buttons
            $(document).on('click', '.xelite-content-btn.analyze', this.analyzePattern);
            
            // Generate similar content buttons
            $(document).on('click', '.xelite-content-btn.generate-similar', this.generateSimilarContent);
            
            // Bookmark pattern buttons
            $(document).on('click', '.xelite-content-btn.bookmark', this.bookmarkPattern);
            
            // Settings form submission
            $(document).on('submit', '#user-settings-form', this.saveSettings);
            
            // Export buttons
            $(document).on('click', '#export-patterns', this.exportPatterns);
            $(document).on('click', '#export-csv', this.exportCSV);
            $(document).on('click', '#export-json', this.exportJSON);
            $(document).on('click', '#export-pdf', this.exportPDF);
            
            // Import/Export settings
            $(document).on('click', '#export-settings', this.exportSettings);
            $(document).on('click', '#import-settings', this.showImportModal);
            $(document).on('click', '#confirm-import', this.importSettings);
            
            // Reset settings
            $(document).on('click', '#reset-settings', this.resetSettings);
            
            // Filter controls
            $(document).on('click', '#apply-filters', this.applyFilters);
            $(document).on('click', '#reset-filters', this.resetFilters);
            
            // Enhanced pattern display controls
            $(document).on('click', '.export-option', this.exportPatterns);
            $(document).on('change', '#chart-type', this.loadChart);
            $(document).on('click', '#refresh-chart', this.loadChart);
            $(document).on('input', '#pattern-search', this.debounce(this.handleSearch, 500));
            
            // Chart initialization
            $(document).ready(function() {
                if ($('#pattern-chart').length) {
                    XeliteDashboard.initCharts();
                }
            });
            
            // Pagination
            $(document).on('click', '#prev-page', this.previousPage);
            $(document).on('click', '#next-page', this.nextPage);
            
            // Quick actions
            $(document).on('click', '[data-action]', this.handleQuickAction);
            
            // Modal close buttons
            $(document).on('click', '.xelite-modal-close', this.closeModal);
            
            // Close modal on overlay click
            $(document).on('click', '.xelite-modal', function(e) {
                if (e.target === this) {
                    XeliteDashboard.closeModal();
                }
            });
            
            // Escape key to close modals
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    XeliteDashboard.closeModal();
                }
            });
        },

        /**
         * Initialize modals
         */
        initModals: function() {
            // Modal functionality is handled by event bindings
        },

        /**
         * Initialize tabs
         */
        initTabs: function() {
            // Tab switching is handled by WordPress admin
        },

        /**
         * Initialize forms
         */
        initForms: function() {
            // Form validation and submission
            this.validateForms();
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            // Initialize analytics charts if Chart.js is available
            if (typeof Chart !== 'undefined') {
                this.initAnalyticsCharts();
            }
        },

        /**
         * Show quick generate modal
         */
        showQuickGenerateModal: function() {
            $('#quick-generate-modal').show();
        },

        /**
         * Generate content
         */
        generateContent: function() {
            var $button = $(this);
            var $form = $button.closest('.xelite-generator-form');
            
            // Collect form data
            var formData = {
                action: 'xelite_dashboard_generate_content',
                nonce: xelite_dashboard.nonce,
                topic: $('#content-topic').val(),
                tone: $('#content-tone').val(),
                length: $('#content-length').val(),
                creativity: $('#content-creativity').val(),
                count: $('#content-count').val(),
                pattern_influence: $('#pattern-influence').val(),
                include_hashtags: $('#include-hashtags').val(),
                include_cta: $('#include-cta').val(),
                custom_instructions: $('#custom-instructions').val(),
                pattern_accounts: $('input[name="pattern_accounts[]"]:checked').map(function() {
                    return this.value;
                }).get()
            };
            
            // Show loading state
            XeliteDashboard.showLoading('#generation-loading');
            $button.prop('disabled', true);
            
            // Make AJAX request
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                XeliteDashboard.hideLoading('#generation-loading');
                $button.prop('disabled', false);
                
                if (response.success) {
                    XeliteDashboard.displayGeneratedContent(response.data);
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                XeliteDashboard.hideLoading('#generation-loading');
                $button.prop('disabled', false);
                XeliteDashboard.showError('Network error occurred. Please try again.');
            });
        },

        /**
         * Display generated content
         */
        displayGeneratedContent: function(content) {
            var $results = $('#generated-content-results');
            var $variations = $('#content-variations');
            
            // Clear previous results
            $variations.empty();
            
            // Add each variation
            if (Array.isArray(content)) {
                content.forEach(function(variation, index) {
                    var template = $('#content-variation-template').html();
                    var html = template
                        .replace(/\{\{variation_id\}\}/g, variation.id || index)
                        .replace(/\{\{variation_number\}\}/g, index + 1)
                        .replace(/\{\{content_text\}\}/g, variation.text)
                        .replace(/\{\{content_length\}\}/g, variation.text.length)
                        .replace(/\{\{token_count\}\}/g, variation.tokens || 0)
                        .replace(/\{\{tone\}\}/g, variation.tone || 'N/A')
                        .replace(/\{\{repost_score\}\}/g, variation.repost_score || 0);
                    
                    $variations.append(html);
                });
            } else {
                // Single content item
                var template = $('#content-variation-template').html();
                var html = template
                    .replace(/\{\{variation_id\}\}/g, content.id || 0)
                    .replace(/\{\{variation_number\}\}/g, 1)
                    .replace(/\{\{content_text\}\}/g, content.text)
                    .replace(/\{\{content_length\}\}/g, content.text.length)
                    .replace(/\{\{token_count\}\}/g, content.tokens || 0)
                    .replace(/\{\{tone\}\}/g, content.tone || 'N/A')
                    .replace(/\{\{repost_score\}\}/g, content.repost_score || 0);
                
                $variations.append(html);
            }
            
            // Show results
            $results.show();
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: $results.offset().top - 100
            }, 500);
        },

        /**
         * Save content
         */
        saveContent: function() {
            var $button = $(this);
            var content = $button.data('content') || $button.closest('.content-variation').find('.content-text').text();
            var title = prompt('Enter a title for this content:', 'Generated Content');
            
            if (!title) return;
            
            var formData = {
                action: 'xelite_dashboard_save_content',
                nonce: xelite_dashboard.nonce,
                content: content,
                title: title
            };
            
            $button.prop('disabled', true);
            
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                $button.prop('disabled', false);
                
                if (response.success) {
                    XeliteDashboard.showSuccess('Content saved successfully!');
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                $button.prop('disabled', false);
                XeliteDashboard.showError('Failed to save content. Please try again.');
            });
        },

        /**
         * Copy content to clipboard
         */
        copyContent: function() {
            var $button = $(this);
            var content = $button.data('content') || $button.closest('.content-variation').find('.content-text').text();
            
            // Use modern clipboard API if available
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(content).then(function() {
                    XeliteDashboard.showSuccess('Content copied to clipboard!');
                }).catch(function() {
                    XeliteDashboard.fallbackCopyTextToClipboard(content);
                });
            } else {
                XeliteDashboard.fallbackCopyTextToClipboard(content);
            }
        },

        /**
         * Fallback copy method for older browsers
         */
        fallbackCopyTextToClipboard: function(text) {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.position = 'fixed';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                XeliteDashboard.showSuccess('Content copied to clipboard!');
            } catch (err) {
                XeliteDashboard.showError('Failed to copy content. Please copy manually.');
            }
            
            document.body.removeChild(textArea);
        },

        /**
         * Edit content
         */
        editContent: function() {
            var $button = $(this);
            var $variation = $button.closest('.content-variation');
            var $contentText = $variation.find('.content-text');
            var currentText = $contentText.text();
            
            // Create textarea for editing
            var $textarea = $('<textarea class="content-edit-textarea">').val(currentText);
            $contentText.hide().after($textarea);
            
            // Add save/cancel buttons
            var $editActions = $('<div class="edit-actions">')
                .append('<button type="button" class="button button-primary save-edit">Save</button>')
                .append('<button type="button" class="button button-secondary cancel-edit">Cancel</button>');
            
            $button.hide().after($editActions);
            
            // Handle save
            $editActions.on('click', '.save-edit', function() {
                var newText = $textarea.val();
                $contentText.text(newText).show();
                $textarea.remove();
                $editActions.remove();
                $button.show();
            });
            
            // Handle cancel
            $editActions.on('click', '.cancel-edit', function() {
                $contentText.show();
                $textarea.remove();
                $editActions.remove();
                $button.show();
            });
        },

        /**
         * Optimize content
         */
        optimizeContent: function() {
            var $button = $(this);
            var $variation = $button.closest('.content-variation');
            var content = $variation.find('.content-text').text();
            
            $button.prop('disabled', true);
            
            var formData = {
                action: 'xelite_dashboard_optimize_content',
                nonce: xelite_dashboard.nonce,
                content: content
            };
            
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                $button.prop('disabled', false);
                
                if (response.success) {
                    $variation.find('.content-text').text(response.data.optimized_content);
                    XeliteDashboard.showSuccess('Content optimized successfully!');
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                $button.prop('disabled', false);
                XeliteDashboard.showError('Failed to optimize content. Please try again.');
            });
        },

        /**
         * Regenerate content
         */
        regenerateContent: function() {
            var $button = $(this);
            var variationId = $button.data('variation-id');
            
            $button.prop('disabled', true);
            
            var formData = {
                action: 'xelite_dashboard_regenerate_content',
                nonce: xelite_dashboard.nonce,
                variation_id: variationId
            };
            
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                $button.prop('disabled', false);
                
                if (response.success) {
                    var $variation = $button.closest('.content-variation');
                    $variation.find('.content-text').text(response.data.content);
                    XeliteDashboard.showSuccess('Content regenerated successfully!');
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                $button.prop('disabled', false);
                XeliteDashboard.showError('Failed to regenerate content. Please try again.');
            });
        },

        /**
         * Analyze pattern
         */
        analyzePattern: function() {
            var $button = $(this);
            var patternId = $button.data('pattern-id');
            
            var formData = {
                action: 'xelite_dashboard_analyze_pattern',
                nonce: xelite_dashboard.nonce,
                pattern_id: patternId
            };
            
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                if (response.success) {
                    $('#analysis-content').html(response.data.html);
                    $('#pattern-analysis-modal').show();
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                XeliteDashboard.showError('Failed to analyze pattern. Please try again.');
            });
        },

        /**
         * Generate similar content
         */
        generateSimilarContent: function() {
            var $button = $(this);
            var patternId = $button.data('pattern-id');
            
            // Close analysis modal if open
            $('#pattern-analysis-modal').hide();
            
            // Switch to content generator tab
            window.location.href = window.location.href.split('?')[0] + '?page=repost-intelligence-dashboard&tab=content-generator&pattern_id=' + patternId;
        },

        /**
         * Bookmark pattern
         */
        bookmarkPattern: function() {
            var $button = $(this);
            var patternId = $button.data('pattern-id');
            
            var formData = {
                action: 'xelite_dashboard_bookmark_pattern',
                nonce: xelite_dashboard.nonce,
                pattern_id: patternId
            };
            
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                if (response.success) {
                    $button.addClass('bookmarked');
                    XeliteDashboard.showSuccess('Pattern bookmarked!');
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                XeliteDashboard.showError('Failed to bookmark pattern. Please try again.');
            });
        },

        /**
         * Save settings
         */
        saveSettings: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('#save-settings');
            
            // Collect form data
            var formData = new FormData($form[0]);
            formData.append('action', 'xelite_dashboard_update_settings');
            formData.append('nonce', xelite_dashboard.nonce);
            
            $submitButton.prop('disabled', true);
            XeliteDashboard.showLoading('#settings-loading');
            
            $.ajax({
                url: xelite_dashboard.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $submitButton.prop('disabled', false);
                    XeliteDashboard.hideLoading('#settings-loading');
                    
                    if (response.success) {
                        XeliteDashboard.showSuccess('Settings saved successfully!');
                    } else {
                        XeliteDashboard.showError(response.data);
                    }
                },
                error: function() {
                    $submitButton.prop('disabled', false);
                    XeliteDashboard.hideLoading('#settings-loading');
                    XeliteDashboard.showError('Failed to save settings. Please try again.');
                }
            });
        },

        /**
         * Export patterns
         */
        exportPatterns: function() {
            var filters = {
                source: $('#pattern-source').val(),
                min_reposts: $('#pattern-min-reposts').val(),
                sort: $('#pattern-sort').val(),
                search: $('#pattern-search').val()
            };
            
            var formData = {
                action: 'xelite_dashboard_export_patterns',
                nonce: xelite_dashboard.nonce,
                filters: filters
            };
            
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                if (response.success) {
                    // Create download link
                    var blob = new Blob([response.data.csv], { type: 'text/csv' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'repost-patterns.csv';
                    a.click();
                    window.URL.revokeObjectURL(url);
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                XeliteDashboard.showError('Failed to export patterns. Please try again.');
            });
        },

        /**
         * Export settings
         */
        exportSettings: function() {
            var formData = {
                action: 'xelite_dashboard_export_settings',
                nonce: xelite_dashboard.nonce
            };
            
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                if (response.success) {
                    var blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'xelite-settings.json';
                    a.click();
                    window.URL.revokeObjectURL(url);
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                XeliteDashboard.showError('Failed to export settings. Please try again.');
            });
        },

        /**
         * Show import modal
         */
        showImportModal: function() {
            $('#settings-import-modal').show();
        },

        /**
         * Import settings
         */
        importSettings: function() {
            var fileInput = document.getElementById('settings-file');
            var file = fileInput.files[0];
            
            if (!file) {
                XeliteDashboard.showError('Please select a file to import.');
                return;
            }
            
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var settings = JSON.parse(e.target.result);
                    
                    var formData = {
                        action: 'xelite_dashboard_import_settings',
                        nonce: xelite_dashboard.nonce,
                        settings: settings
                    };
                    
                    $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                        if (response.success) {
                            XeliteDashboard.showSuccess('Settings imported successfully!');
                            $('#settings-import-modal').hide();
                            location.reload();
                        } else {
                            XeliteDashboard.showError(response.data);
                        }
                    }).fail(function() {
                        XeliteDashboard.showError('Failed to import settings. Please try again.');
                    });
                } catch (error) {
                    XeliteDashboard.showError('Invalid settings file format.');
                }
            };
            reader.readAsText(file);
        },

        /**
         * Reset settings
         */
        resetSettings: function() {
            if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
                var formData = {
                    action: 'xelite_dashboard_reset_settings',
                    nonce: xelite_dashboard.nonce
                };
                
                $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                    if (response.success) {
                        XeliteDashboard.showSuccess('Settings reset to defaults!');
                        location.reload();
                    } else {
                        XeliteDashboard.showError(response.data);
                    }
                }).fail(function() {
                    XeliteDashboard.showError('Failed to reset settings. Please try again.');
                });
            }
        },

        /**
         * Apply filters
         */
        applyFilters: function() {
            var filters = {
                source: $('#pattern-source').val(),
                min_reposts: $('#pattern-min-reposts').val(),
                sort: $('#pattern-sort').val(),
                search: $('#pattern-search').val()
            };
            
            var formData = {
                action: 'xelite_dashboard_filter_patterns',
                nonce: xelite_dashboard.nonce,
                filters: filters
            };
            
            XeliteDashboard.showLoading('#patterns-loading');
            
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                XeliteDashboard.hideLoading('#patterns-loading');
                
                if (response.success) {
                    $('#patterns-list').html(response.data.html);
                    XeliteDashboard.updatePagination(response.data.pagination);
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                XeliteDashboard.hideLoading('#patterns-loading');
                XeliteDashboard.showError('Failed to apply filters. Please try again.');
            });
        },

        /**
         * Reset filters
         */
        resetFilters: function() {
            $('#pattern-source').val('');
            $('#pattern-min-reposts').val('5');
            $('#pattern-sort').val('repost_count');
            $('#pattern-search').val('');
            
            XeliteDashboard.applyFilters();
        },

        /**
         * Handle quick actions
         */
        handleQuickAction: function() {
            var action = $(this).data('action');
            
            switch (action) {
                case 'generate-content':
                    window.location.href = window.location.href.split('?')[0] + '?page=repost-intelligence-dashboard&tab=content-generator';
                    break;
                case 'view-patterns':
                    window.location.href = window.location.href.split('?')[0] + '?page=repost-intelligence-dashboard&tab=patterns';
                    break;
                case 'update-settings':
                    window.location.href = window.location.href.split('?')[0] + '?page=repost-intelligence-dashboard&tab=settings';
                    break;
                case 'view-analytics':
                    window.location.href = window.location.href.split('?')[0] + '?page=repost-intelligence-dashboard&tab=analytics';
                    break;
                case 'add-target-accounts':
                    window.location.href = window.location.href.split('?')[0] + '?page=repost-intelligence-dashboard&tab=settings';
                    break;
                case 'run-analysis':
                    XeliteDashboard.runPatternAnalysis();
                    break;
                case 'setup-profile':
                    window.location.href = window.location.href.split('?')[0] + '?page=repost-intelligence-dashboard&tab=settings';
                    break;
                case 'update-context':
                    window.location.href = window.location.href.split('?')[0] + '?page=repost-intelligence-dashboard&tab=settings';
                    break;
            }
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.xelite-modal').hide();
        },

        /**
         * Show loading state
         */
        showLoading: function(selector) {
            $(selector).show();
        },

        /**
         * Hide loading state
         */
        hideLoading: function(selector) {
            $(selector).hide();
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            this.showMessage(message, 'success');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            this.showMessage(message, 'error');
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            var $messages = $('#xelite-messages');
            var $message = $('<div class="xelite-message ' + type + '">')
                .text(message)
                .append('<button type="button" class="message-close">&times;</button>');
            
            $messages.append($message);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close
            $message.on('click', '.message-close', function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Validate forms
         */
        validateForms: function() {
            // Add form validation logic here
        },

        /**
         * Initialize analytics charts
         */
        initAnalyticsCharts: function() {
            // Initialize Chart.js charts for analytics
            // This would be implemented based on the analytics data structure
        },

        /**
         * Update pagination
         */
        updatePagination: function(pagination) {
            // Update pagination controls based on response
        },

        /**
         * Run pattern analysis
         */
        runPatternAnalysis: function() {
            var formData = {
                action: 'xelite_dashboard_run_analysis',
                nonce: xelite_dashboard.nonce
            };
            
            XeliteDashboard.showLoading('#patterns-loading');
            
            $.post(xelite_dashboard.ajaxUrl, formData, function(response) {
                XeliteDashboard.hideLoading('#patterns-loading');
                
                if (response.success) {
                    XeliteDashboard.showSuccess('Pattern analysis completed!');
                    location.reload();
                } else {
                    XeliteDashboard.showError(response.data);
                }
            }).fail(function() {
                XeliteDashboard.hideLoading('#patterns-loading');
                XeliteDashboard.showError('Failed to run pattern analysis. Please try again.');
            });
        },

        /**
         * Load chart data and render
         */
        loadChart: function() {
            var chartType = $('#chart-type').val();
            var filters = this.getCurrentFilters();
            
            $('#chart-loading').show();
            $('#chart-error').hide();
            
            $.ajax({
                url: xelite_dashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'xelite_get_pattern_charts',
                    nonce: xelite_dashboard.nonce,
                    chart_type: chartType,
                    source_handle: filters.source_handle,
                    date_from: filters.date_from,
                    date_to: filters.date_to
                },
                success: function(response) {
                    $('#chart-loading').hide();
                    if (response.success) {
                        XeliteDashboard.renderChart(response.data, chartType);
                        XeliteDashboard.updateInsights(response.data);
                    } else {
                        $('#chart-error').show().find('p').text(response.data);
                    }
                },
                error: function() {
                    $('#chart-loading').hide();
                    $('#chart-error').show().find('p').text('Failed to load chart data');
                }
            });
        },

        /**
         * Render Chart.js chart
         */
        renderChart: function(data, chartType) {
            var ctx = document.getElementById('pattern-chart');
            if (!ctx) return;
            
            // Destroy existing chart
            if (this.currentChart) {
                this.currentChart.destroy();
            }
            
            var config = this.getChartConfig(data, chartType);
            this.currentChart = new Chart(ctx, config);
        },

        /**
         * Get chart configuration based on type
         */
        getChartConfig: function(data, chartType) {
            var baseConfig = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            };
            
            switch (chartType) {
                case 'repost_trends':
                    return this.getLineChartConfig(data, baseConfig);
                case 'content_types':
                case 'tone_analysis':
                    return this.getPieChartConfig(data, baseConfig);
                case 'length_distribution':
                    return this.getBarChartConfig(data, baseConfig);
                case 'engagement_correlation':
                    return this.getScatterChartConfig(data, baseConfig);
                default:
                    return this.getBarChartConfig(data, baseConfig);
            }
        },

        /**
         * Get line chart configuration
         */
        getLineChartConfig: function(data, baseConfig) {
            return {
                type: 'line',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: data.label || 'Reposts',
                        data: data.values || [],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    ...baseConfig.options,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
        },

        /**
         * Get pie chart configuration
         */
        getPieChartConfig: function(data, baseConfig) {
            return {
                type: 'pie',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        data: data.values || [],
                        backgroundColor: [
                            '#0073aa', '#00a32a', '#dba617', '#d63638',
                            '#3B82F6', '#10B981', '#F59E0B', '#EF4444'
                        ]
                    }]
                },
                options: baseConfig.options
            };
        },

        /**
         * Get bar chart configuration
         */
        getBarChartConfig: function(data, baseConfig) {
            return {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: data.label || 'Count',
                        data: data.values || [],
                        backgroundColor: '#0073aa',
                        borderColor: '#005a87',
                        borderWidth: 1
                    }]
                },
                options: {
                    ...baseConfig.options,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
        },

        /**
         * Get scatter chart configuration
         */
        getScatterChartConfig: function(data, baseConfig) {
            return {
                type: 'scatter',
                data: {
                    datasets: [{
                        label: data.label || 'Engagement vs Reposts',
                        data: data.points || [],
                        backgroundColor: '#0073aa'
                    }]
                },
                options: {
                    ...baseConfig.options,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Repost Count'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Engagement Rate'
                            }
                        }
                    }
                }
                };
            },

        /**
         * Update insights panel
         */
        updateInsights: function(data) {
            var insights = $('#insights-content');
            if (!insights.length) return;
            
            var html = '';
            if (data.insights && data.insights.length > 0) {
                data.insights.forEach(function(insight) {
                    html += '<div class="insight-item">';
                    html += '<strong>' + insight.title + ':</strong> ' + insight.description;
                    html += '</div>';
                });
            } else {
                html = '<p>No insights available for this chart.</p>';
            }
            
            insights.html(html);
        },

        /**
         * Apply filters and reload data
         */
        applyFilters: function() {
            var filters = XeliteDashboard.getCurrentFilters();
            XeliteDashboard.loadFilteredPatterns(filters);
        },

        /**
         * Reset filters to defaults
         */
        resetFilters: function() {
            $('#pattern-source').val('');
            $('#pattern-min-reposts').val('5');
            $('#pattern-min-engagement').val('');
            $('#pattern-date-from').val('');
            $('#pattern-date-to').val('');
            $('#pattern-content-type').val('');
            $('#pattern-tone').val('');
            $('#pattern-sort').val('repost_count');
            $('#pattern-sort-order').val('desc');
            $('#pattern-search').val('');
            
            XeliteDashboard.applyFilters();
        },

        /**
         * Get current filter values
         */
        getCurrentFilters: function() {
            return {
                source_handle: $('#pattern-source').val(),
                min_reposts: $('#pattern-min-reposts').val(),
                min_engagement: $('#pattern-min-engagement').val(),
                date_from: $('#pattern-date-from').val(),
                date_to: $('#pattern-date-to').val(),
                content_type: $('#pattern-content-type').val(),
                tone: $('#pattern-tone').val(),
                sort_by: $('#pattern-sort').val(),
                sort_order: $('#pattern-sort-order').val(),
                search: $('#pattern-search').val()
            };
        },

        /**
         * Load filtered patterns via AJAX
         */
        loadFilteredPatterns: function(filters) {
            $('#patterns-loading').show();
            
            $.ajax({
                url: xelite_dashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'xelite_get_filtered_patterns',
                    nonce: xelite_dashboard.nonce,
                    ...filters
                },
                success: function(response) {
                    $('#patterns-loading').hide();
                    if (response.success) {
                        XeliteDashboard.renderPatterns(response.data.patterns);
                        XeliteDashboard.updatePagination(response.data);
                        XeliteDashboard.updateStatistics(response.data);
                    } else {
                        XeliteDashboard.showMessage('error', response.data);
                    }
                },
                error: function() {
                    $('#patterns-loading').hide();
                    XeliteDashboard.showMessage('error', 'Failed to load patterns');
                }
            });
        },

        /**
         * Render patterns list
         */
        renderPatterns: function(patterns) {
            var container = $('#patterns-list');
            if (!container.length) return;
            
            if (patterns.length === 0) {
                container.html('<div class="xelite-empty-state"><p>No patterns found matching your criteria.</p></div>');
                return;
            }
            
            var html = '';
            patterns.forEach(function(pattern) {
                html += XeliteDashboard.renderPatternItem(pattern);
            });
            
            container.html(html);
        },

        /**
         * Render single pattern item
         */
        renderPatternItem: function(pattern) {
            return `
                <div class="xelite-pattern-item" data-pattern-id="${pattern.id}">
                    <div class="pattern-header">
                        <div class="pattern-source">
                            <span class="source-handle">@${pattern.source_handle}</span>
                            <span class="repost-count">${pattern.repost_count} reposts</span>
                        </div>
                        
                        <div class="pattern-meta">
                            <span class="pattern-date">${new Date(pattern.created_at).toLocaleDateString()}</span>
                            <span class="pattern-engagement">${pattern.avg_engagement} avg engagement</span>
                        </div>
                    </div>
                    
                    <div class="pattern-content">
                        <div class="pattern-text">${pattern.text}</div>
                        
                        <div class="pattern-details">
                            <div class="detail-item">
                                <span class="detail-label">Length:</span>
                                <span class="detail-value">${pattern.text.length} chars</span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Tone:</span>
                                <span class="detail-value">${pattern.tone || 'N/A'}</span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Format:</span>
                                <span class="detail-value">${pattern.format || 'Text'}</span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Hashtags:</span>
                                <span class="detail-value">${pattern.hashtag_count || 0}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pattern-actions">
                        <button type="button" class="xelite-content-btn copy" data-content="${pattern.text}">
                            <span class="dashicons dashicons-clipboard"></span>
                            Copy
                        </button>
                        
                        <button type="button" class="xelite-content-btn analyze" data-pattern-id="${pattern.id}">
                            <span class="dashicons dashicons-chart-line"></span>
                            Analyze
                        </button>
                        
                        <button type="button" class="xelite-content-btn generate-similar" data-pattern-id="${pattern.id}">
                            <span class="dashicons dashicons-admin-generic"></span>
                            Generate Similar
                        </button>
                        
                        <button type="button" class="xelite-content-btn bookmark" data-pattern-id="${pattern.id}">
                            <span class="dashicons dashicons-bookmark"></span>
                            Bookmark
                        </button>
                    </div>
                </div>
            `;
        },

        /**
         * Update pagination controls
         */
        updatePagination: function(data) {
            var pagination = $('.patterns-pagination');
            if (!pagination.length) return;
            
            $('#showing-start').text((data.current_page - 1) * data.filters.per_page + 1);
            $('#showing-end').text(Math.min(data.current_page * data.filters.per_page, data.total_count));
            $('#total-count').text(data.total_count);
            
            $('.current-page').text(data.current_page);
            $('.total-pages').text(data.total_pages);
            
            $('#prev-page').prop('disabled', data.current_page <= 1);
            $('#next-page').prop('disabled', data.current_page >= data.total_pages);
        },

        /**
         * Update statistics cards
         */
        updateStatistics: function(data) {
            $('#total-patterns').text(data.total_count);
            
            if (data.patterns.length > 0) {
                var avgReposts = data.patterns.reduce(function(sum, pattern) {
                    return sum + pattern.repost_count;
                }, 0) / data.patterns.length;
                
                var avgEngagement = data.patterns.reduce(function(sum, pattern) {
                    return sum + pattern.avg_engagement;
                }, 0) / data.patterns.length;
                
                $('#avg-reposts').text(avgReposts.toFixed(1));
                $('#avg-engagement').text(avgEngagement.toFixed(1));
                
                var uniqueSources = [...new Set(data.patterns.map(function(pattern) {
                    return pattern.source_handle;
                }))];
                $('#unique-sources').text(uniqueSources.length);
            }
        },

        /**
         * Handle search input with debouncing
         */
        handleSearch: function() {
            XeliteDashboard.applyFilters();
        },

        /**
         * Export patterns
         */
        exportPatterns: function() {
            var format = $(this).data('format');
            var filters = XeliteDashboard.getCurrentFilters();
            
            var form = $('<form>', {
                method: 'POST',
                action: xelite_dashboard.ajaxUrl,
                target: '_blank'
            });
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'xelite_export_patterns'
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: xelite_dashboard.nonce
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'format',
                value: format
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'filters',
                value: JSON.stringify(filters)
            }));
            
            $('body').append(form);
            form.submit();
            form.remove();
        },

        /**
         * Debounce function for search
         */
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var later = function() {
                    clearTimeout(timeout);
                    func();
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Initialize shortcode functionality
         */
        initShortcodes: function() {
            if (typeof xelite_dashboard !== 'undefined' && xelite_dashboard.isShortcode) {
                this.bindShortcodeEvents();
                this.initShortcodeCharts();
            }
        },

        /**
         * Bind shortcode-specific event handlers
         */
        bindShortcodeEvents: function() {
            // Shortcode pattern search
            $(document).on('input', '#shortcode-pattern-search', this.debounce(this.handleShortcodeSearch, 500));
            
            // Shortcode pattern sort
            $(document).on('change', '#shortcode-pattern-sort', this.handleShortcodeSort);
            
            // Shortcode chart type change
            $(document).on('change', '#shortcode-chart-type', this.loadShortcodeChart);
            
            // Shortcode generate suggestion
            $(document).on('click', '#shortcode-generate-suggestion', this.generateShortcodeSuggestion);
            
            // Shortcode copy suggestion
            $(document).on('click', '.xelite-copy-suggestion', this.copyShortcodeSuggestion);
            
            // Shortcode edit suggestion
            $(document).on('click', '.xelite-edit-suggestion', this.editShortcodeSuggestion);
        },

        /**
         * Handle shortcode search
         */
        handleShortcodeSearch: function() {
            var searchTerm = $('#shortcode-pattern-search').val();
            var patterns = $('.xelite-pattern-item');
            
            patterns.each(function() {
                var patternText = $(this).find('.xelite-pattern-content p').text().toLowerCase();
                if (patternText.includes(searchTerm.toLowerCase())) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },

        /**
         * Handle shortcode sort
         */
        handleShortcodeSort: function() {
            var sortBy = $('#shortcode-pattern-sort').val();
            var patternsList = $('#shortcode-patterns-list');
            var patterns = patternsList.find('.xelite-pattern-item').get();
            
            patterns.sort(function(a, b) {
                var aVal, bVal;
                
                switch(sortBy) {
                    case 'date':
                        aVal = new Date($(a).find('.xelite-pattern-date').text());
                        bVal = new Date($(b).find('.xelite-pattern-date').text());
                        return bVal - aVal;
                    case 'engagement':
                        aVal = parseFloat($(a).find('.xelite-pattern-engagement').text());
                        bVal = parseFloat($(b).find('.xelite-pattern-engagement').text());
                        return bVal - aVal;
                    case 'frequency':
                        // For frequency, we'd need to implement a more complex sorting
                        // For now, just sort by date
                        aVal = new Date($(a).find('.xelite-pattern-date').text());
                        bVal = new Date($(b).find('.xelite-pattern-date').text());
                        return bVal - aVal;
                    default:
                        return 0;
                }
            });
            
            patternsList.empty().append(patterns);
        },

        /**
         * Initialize shortcode charts
         */
        initShortcodeCharts: function() {
            // Initialize pattern chart if it exists
            if ($('#shortcode-pattern-chart').length) {
                this.loadShortcodeChart();
            }
            
            // Initialize analytics charts if they exist
            if ($('#shortcode-engagement-chart').length) {
                this.loadShortcodeEngagementChart();
            }
            
            if ($('#shortcode-content-chart').length) {
                this.loadShortcodeContentChart();
            }
        },

        /**
         * Load shortcode pattern chart
         */
        loadShortcodeChart: function() {
            var chartType = $('#shortcode-chart-type').val();
            var canvas = document.getElementById('shortcode-pattern-chart');
            
            if (!canvas) return;
            
            // Destroy existing chart
            if (window.shortcodePatternChart) {
                window.shortcodePatternChart.destroy();
            }
            
            // Create sample data (in real implementation, this would come from AJAX)
            var ctx = canvas.getContext('2d');
            var data = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Engagement Score',
                    data: [65, 59, 80, 81, 56, 55],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            };
            
            var config = {
                type: chartType,
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
            
            window.shortcodePatternChart = new Chart(ctx, config);
        },

        /**
         * Load shortcode engagement chart
         */
        loadShortcodeEngagementChart: function() {
            var canvas = document.getElementById('shortcode-engagement-chart');
            
            if (!canvas) return;
            
            // Destroy existing chart
            if (window.shortcodeEngagementChart) {
                window.shortcodeEngagementChart.destroy();
            }
            
            var ctx = canvas.getContext('2d');
            var data = {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Average Engagement',
                    data: [75, 82, 68, 91],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4
                }]
            };
            
            var config = {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            };
            
            window.shortcodeEngagementChart = new Chart(ctx, config);
        },

        /**
         * Load shortcode content chart
         */
        loadShortcodeContentChart: function() {
            var canvas = document.getElementById('shortcode-content-chart');
            
            if (!canvas) return;
            
            // Destroy existing chart
            if (window.shortcodeContentChart) {
                window.shortcodeContentChart.destroy();
            }
            
            var ctx = canvas.getContext('2d');
            var data = {
                labels: ['Educational', 'Entertainment', 'Inspirational', 'Promotional'],
                datasets: [{
                    data: [30, 25, 25, 20],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c'
                    ]
                }]
            };
            
            var config = {
                type: 'pie',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            };
            
            window.shortcodeContentChart = new Chart(ctx, config);
        },

        /**
         * Generate shortcode suggestion
         */
        generateShortcodeSuggestion: function() {
            var button = $('#shortcode-generate-suggestion');
            var buttonText = button.find('.xelite-button-text');
            var buttonLoading = button.find('.xelite-button-loading');
            
            // Show loading state
            button.addClass('loading');
            
            $.ajax({
                url: xelite_dashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'xelite_generate_content',
                    nonce: xelite_dashboard.nonce,
                    context: 'shortcode'
                },
                success: function(response) {
                    if (response.success) {
                        // Add new suggestion to the list
                        var newSuggestion = XeliteDashboard.createSuggestionHTML(response.data);
                        $('#shortcode-suggestions-list').prepend(newSuggestion);
                        
                        // Show success message
                        XeliteDashboard.showMessage('Suggestion generated successfully!', 'success');
                    } else {
                        XeliteDashboard.showMessage('Failed to generate suggestion: ' + response.data, 'error');
                    }
                },
                error: function() {
                    XeliteDashboard.showMessage('Error generating suggestion. Please try again.', 'error');
                },
                complete: function() {
                    // Hide loading state
                    button.removeClass('loading');
                }
            });
        },

        /**
         * Copy shortcode suggestion
         */
        copyShortcodeSuggestion: function() {
            var content = $(this).data('content');
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(content).then(function() {
                    XeliteDashboard.showMessage('Content copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = content;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                XeliteDashboard.showMessage('Content copied to clipboard!', 'success');
            }
        },

        /**
         * Edit shortcode suggestion
         */
        editShortcodeSuggestion: function() {
            var suggestionId = $(this).data('id');
            var content = $(this).closest('.xelite-suggestion-item').find('.xelite-suggestion-content p').text();
            
            // Create a simple edit modal
            var modal = $('<div class="xelite-modal">' +
                '<div class="xelite-modal-content">' +
                '<h3>Edit Suggestion</h3>' +
                '<textarea id="edit-suggestion-text" rows="4" style="width: 100%; margin: 10px 0;">' + content + '</textarea>' +
                '<div style="text-align: right; margin-top: 15px;">' +
                '<button type="button" class="xelite-button xelite-button-secondary" onclick="$(this).closest(\'.xelite-modal\').remove()">Cancel</button> ' +
                '<button type="button" class="xelite-button" onclick="XeliteDashboard.saveShortcodeSuggestion(' + suggestionId + ')">Save</button>' +
                '</div>' +
                '</div>' +
                '</div>');
            
            $('body').append(modal);
        },

        /**
         * Save shortcode suggestion
         */
        saveShortcodeSuggestion: function(suggestionId) {
            var newContent = $('#edit-suggestion-text').val();
            
            $.ajax({
                url: xelite_dashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'xelite_update_suggestion',
                    nonce: xelite_dashboard.nonce,
                    suggestion_id: suggestionId,
                    content: newContent
                },
                success: function(response) {
                    if (response.success) {
                        // Update the content in the DOM
                        $('.xelite-suggestion-item[data-id="' + suggestionId + '"] .xelite-suggestion-content p').text(newContent);
                        $('.xelite-modal').remove();
                        XeliteDashboard.showMessage('Suggestion updated successfully!', 'success');
                    } else {
                        XeliteDashboard.showMessage('Failed to update suggestion: ' + response.data, 'error');
                    }
                },
                error: function() {
                    XeliteDashboard.showMessage('Error updating suggestion. Please try again.', 'error');
                }
            });
        },

        /**
         * Create suggestion HTML
         */
        createSuggestionHTML: function(suggestion) {
            return '<div class="xelite-suggestion-item">' +
                '<div class="xelite-suggestion-header">' +
                '<span class="xelite-suggestion-date">' + new Date().toLocaleDateString() + '</span>' +
                '<span class="xelite-suggestion-score">' + suggestion.repost_score + '% repost likelihood</span>' +
                '</div>' +
                '<div class="xelite-suggestion-content">' +
                '<p>' + suggestion.content + '</p>' +
                '</div>' +
                '<div class="xelite-suggestion-actions">' +
                '<button type="button" class="xelite-button xelite-button-small xelite-copy-suggestion" data-content="' + suggestion.content.replace(/"/g, '&quot;') + '">Copy</button>' +
                '<button type="button" class="xelite-button xelite-button-small xelite-button-secondary xelite-edit-suggestion" data-id="' + suggestion.id + '">Edit</button>' +
                '</div>' +
                '</div>';
        },

        /**
         * Show message for shortcodes
         */
        showMessage: function(message, type) {
            var messageClass = 'xelite-message ' + type;
            var messageHTML = '<div class="' + messageClass + '">' + message + '</div>';
            
            // Remove existing messages
            $('.xelite-message').remove();
            
            // Add new message
            $('body').append(messageHTML);
            
            // Auto-remove after 3 seconds
            setTimeout(function() {
                $('.xelite-message').fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        XeliteDashboard.init();
        XeliteDashboard.initShortcodes();
    });

})(jQuery); 