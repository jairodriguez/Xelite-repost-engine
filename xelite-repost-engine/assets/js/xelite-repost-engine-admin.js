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
            $('.xelite-api-test').on('click', this.testApiConnection);
            
            // Form submissions
            $('.xelite-form').on('submit', this.handleFormSubmit);
            
            // Dynamic form fields
            $('.xelite-add-field').on('click', this.addFormField);
            $('.xelite-remove-field').on('click', this.removeFormField);
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
            var $status = $button.siblings('.api-status');
            var apiType = $button.data('api-type');
            
            $button.prop('disabled', true).text('Testing...');
            $status.removeClass('success error').text('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'xelite_test_api',
                    api_type: apiType,
                    nonce: xelite_repost_engine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.addClass('success').text('Connection successful!');
                    } else {
                        $status.addClass('error').text('Connection failed: ' + response.data);
                    }
                },
                error: function() {
                    $status.addClass('error').text('Connection failed. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
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
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        XeliteRepostEngine.init();
    });

})(jQuery); 