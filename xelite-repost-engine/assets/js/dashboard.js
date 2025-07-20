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
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        XeliteDashboard.init();
    });

})(jQuery); 