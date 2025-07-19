/**
 * Xelite Repost Engine Admin JavaScript
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Main admin object
    var XeliteRepostEngine = {
        
        /**
         * Initialize the admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // API key validation
            $('.test-api-button').on('click', this.testApiConnection);
            
            // Password field toggle
            $('.toggle-password').on('click', this.togglePassword);
            
            // Form submissions
            $('.xelite-form').on('submit', this.handleFormSubmit);
            
            // Dynamic form fields
            $('.xelite-add-field').on('click', this.addFormField);
            $('.xelite-remove-field').on('click', this.removeFormField);
            
            // Repeater fields
            $('.add-item').on('click', this.addRepeaterRow);
            $('.remove-item').on('click', this.removeRepeaterRow);
            
            // Connection status testing
            $('.test-connection').on('click', this.testConnectionStatus);
            
            // X API Authentication
            $('.save-x-credentials').on('click', this.saveXCredentials);
            $('.test-x-connection').on('click', this.testXConnection);
            $('.revoke-x-connection').on('click', this.revokeXConnection);
        },

        /**
         * Initialize components
         */
        initComponents: function() {
            // Initialize tooltips
            this.initTooltips();
            
            // Initialize tabs
            this.initTabs();
        },

        /**
         * Test API connection
         */
        testApiConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $field = $button.closest('.password-field-container').find('input[type="password"]');
            var $result = $('#test-result-' + $field.attr('name').match(/\[([^\]]+)\]/)[1]);
            var fieldName = $field.attr('name').match(/\[([^\]]+)\]/)[1];
            var apiKey = $field.val();
            
            $button.prop('disabled', true);
            $button.find('span.dashicons').removeClass('dashicons-admin-network').addClass('dashicons-update-alt');
            $result.hide();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'xelite_test_api_connection',
                    field: fieldName,
                    api_key: apiKey,
                    nonce: xelite_repost_engine.nonce
                },
                success: function(response) {
                    $result.show();
                    if (response.success) {
                        $result.find('.test-status').removeClass('error').addClass('success').html('<span class="dashicons dashicons-yes"></span>');
                        $result.find('.test-message').text(response.message);
                    } else {
                        $result.find('.test-status').removeClass('success').addClass('error').html('<span class="dashicons dashicons-no"></span>');
                        $result.find('.test-message').text(response.message);
                    }
                },
                error: function() {
                    $result.show();
                    $result.find('.test-status').removeClass('success').addClass('error').html('<span class="dashicons dashicons-no"></span>');
                    $result.find('.test-message').text('Connection test failed. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.find('span.dashicons').removeClass('dashicons-update-alt').addClass('dashicons-admin-network');
                }
            });
        },

        /**
         * Test connection status
         */
        testConnectionStatus: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var apiType = $button.data('api');
            var $status = $('#' + apiType + '-api-status');
            
            $button.prop('disabled', true).text('Testing...');
            $status.removeClass('success error unknown').addClass('testing');
            $status.html('<span class="dashicons dashicons-update spinning"></span> Testing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'xelite_test_api_connection',
                    api_type: apiType,
                    nonce: xelite_repost_engine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.removeClass('testing').addClass('success');
                        $status.html('<span class="dashicons dashicons-yes"></span> ' + (response.data.message || 'Connection successful!'));
                    } else {
                        $status.removeClass('testing').addClass('error');
                        $status.html('<span class="dashicons dashicons-no"></span> ' + (response.data.message || 'Connection failed'));
                    }
                },
                error: function() {
                    $status.removeClass('testing').addClass('error');
                    $status.html('<span class="dashicons dashicons-no"></span> Network error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Now');
                }
            });
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            
            $submitButton.prop('disabled', true).val('Saving...');
            
            // Form will submit normally, just show loading state
        },

        /**
         * Add form field dynamically
         */
        addFormField: function(e) {
            e.preventDefault();
            
            var $container = $(this).siblings('.dynamic-fields');
            var fieldTemplate = $container.data('template');
            var fieldCount = $container.find('.field-row').length;
            
            var newField = fieldTemplate.replace(/\{index\}/g, fieldCount);
            $container.append(newField);
        },

        /**
         * Remove form field
         */
        removeFormField: function(e) {
            e.preventDefault();
            
            $(this).closest('.field-row').remove();
        },

        /**
         * Toggle password visibility
         */
        togglePassword: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $input = $button.siblings('input[type="password"]');
            var $icon = $button.find('.dashicons');
            var $text = $button.contents().filter(function() {
                return this.nodeType === 3;
            });
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                $text.replaceWith(' Hide');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                $text.replaceWith(' Show');
            }
        },

        /**
         * Add repeater row
         */
        addRepeaterRow: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.closest('.repeater-field').find('.repeater-items');
            var $template = $('#repeater-template-' + $button.closest('.repeater-field').data('field'));
            var rowIndex = $container.find('.repeater-item').length;
            
            var newRow = $template.html()
                .replace(/\{\{index\}\}/g, rowIndex)
                .replace(/\{\{number\}\}/g, rowIndex + 1);
            
            $container.append(newRow);
        },

        /**
         * Remove repeater row
         */
        removeRepeaterRow: function(e) {
            e.preventDefault();
            
            var $item = $(this).closest('.repeater-item');
            var $container = $item.closest('.repeater-items');
            
            $item.remove();
            
            // Reindex remaining items
            $container.find('.repeater-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('.item-number').text(index + 1);
                $(this).find('input, select, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('.xelite-tooltip').tooltip({
                position: { my: 'left+5 center', at: 'right center' }
            });
        },

        /**
         * Initialize tabs
         */
        initTabs: function() {
            $('.xelite-tabs').tabs();
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.xelite-repost-engine-wrap').prepend($notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notification.fadeOut();
            }, 5000);
        },

        /**
         * Show loading state
         */
        showLoading: function($element) {
            $element.addClass('xelite-repost-engine-loading');
        },

        /**
         * Hide loading state
         */
        hideLoading: function($element) {
            $element.removeClass('xelite-repost-engine-loading');
        },

        /**
         * Save X API credentials
         */
        saveXCredentials: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.closest('.x-api-auth-container');
            
            // Get form data
            var credentials = {
                x_api_consumer_key: $container.find('#x_api_consumer_key').val(),
                x_api_consumer_secret: $container.find('#x_api_consumer_secret').val(),
                x_api_access_token: $container.find('#x_api_access_token').val(),
                x_api_access_token_secret: $container.find('#x_api_access_token_secret').val()
            };
            
            // Validate required fields
            var requiredFields = ['x_api_consumer_key', 'x_api_consumer_secret', 'x_api_access_token', 'x_api_access_token_secret'];
            var missingFields = [];
            
            requiredFields.forEach(function(field) {
                if (!credentials[field]) {
                    missingFields.push(field.replace('x_api_', '').replace(/_/g, ' '));
                }
            });
            
            if (missingFields.length > 0) {
                XeliteRepostEngine.showNotification('Please fill in all required fields: ' + missingFields.join(', '), 'error');
                return;
            }
            
            $button.prop('disabled', true).text('Saving...');
            $container.addClass('loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'xelite_save_x_credentials',
                    nonce: xelite_repost_engine.nonce,
                    x_api_consumer_key: credentials.x_api_consumer_key,
                    x_api_consumer_secret: credentials.x_api_consumer_secret,
                    x_api_access_token: credentials.x_api_access_token,
                    x_api_access_token_secret: credentials.x_api_access_token_secret
                },
                success: function(response) {
                    if (response.success) {
                        XeliteRepostEngine.showNotification(response.data, 'success');
                        // Reload page to show connected state
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        XeliteRepostEngine.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    XeliteRepostEngine.showNotification('Failed to save credentials. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save Credentials');
                    $container.removeClass('loading');
                }
            });
        },

        /**
         * Test X API connection
         */
        testXConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.closest('.x-api-auth-container');
            var nonce = $button.data('nonce');
            
            $button.prop('disabled', true);
            $button.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update-alt');
            $container.addClass('loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'xelite_test_x_connection',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteRepostEngine.showNotification(response.data.message, 'success');
                        // Update connection status if available
                        if (response.data.user_info) {
                            var $userInfo = $container.find('.user-info');
                            if ($userInfo.length === 0) {
                                $container.find('.x-api-connected').append(
                                    '<div class="user-info">' +
                                    '<p><strong>Authenticated as:</strong> @' + response.data.user_info.screen_name + '</p>' +
                                    '<p><strong>Name:</strong> ' + response.data.user_info.name + '</p>' +
                                    '</div>'
                                );
                            }
                        }
                    } else {
                        XeliteRepostEngine.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    XeliteRepostEngine.showNotification('Connection test failed. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.find('.dashicons').removeClass('dashicons-update-alt').addClass('dashicons-update');
                    $container.removeClass('loading');
                }
            });
        },

        /**
         * Revoke X API connection
         */
        revokeXConnection: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to revoke the X API connection? This will remove all stored credentials.')) {
                return;
            }
            
            var $button = $(this);
            var $container = $button.closest('.x-api-auth-container');
            var nonce = $button.data('nonce');
            
            $button.prop('disabled', true).text('Revoking...');
            $container.addClass('loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'xelite_revoke_x_connection',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteRepostEngine.showNotification(response.data, 'success');
                        // Reload page to show manual setup form
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        XeliteRepostEngine.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    XeliteRepostEngine.showNotification('Failed to revoke connection. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Revoke Connection');
                    $container.removeClass('loading');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        XeliteRepostEngine.init();
    });

})(jQuery); 