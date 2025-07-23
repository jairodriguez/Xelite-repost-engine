/**
 * Xelite Repost Engine Onboarding JavaScript
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Onboarding wizard functionality
    const XeliteOnboarding = {
        currentStep: xeliteOnboarding.currentStep || 'welcome',
        steps: xeliteOnboarding.steps || {},
        ajaxUrl: xeliteOnboarding.ajaxUrl || '',
        nonce: xeliteOnboarding.nonce || '',

        init: function() {
            this.bindEvents();
            this.initializeStep();
        },

        bindEvents: function() {
            // Navigation buttons
            $('#next-step').on('click', this.nextStep.bind(this));
            $('#prev-step').on('click', this.prevStep.bind(this));
            $('#skip-wizard').on('click', this.skipWizard.bind(this));
            $('#complete-setup').on('click', this.completeSetup.bind(this));

            // API test buttons
            $('#test-x-api').on('click', this.testXApi.bind(this));
            $('#test-openai-api').on('click', this.testOpenAIApi.bind(this));

            // Suggestion buttons
            $('.suggestion-btn').on('click', this.addSuggestion.bind(this));

            // Form validation
            $('.onboarding-form input, .onboarding-form select, .onboarding-form textarea').on('change', this.validateForm.bind(this));
        },

        initializeStep: function() {
            this.updateProgress();
            this.showCurrentStep();
        },

        updateProgress: function() {
            const stepKeys = Object.keys(this.steps);
            const currentIndex = stepKeys.indexOf(this.currentStep);
            const progress = ((currentIndex + 1) / stepKeys.length) * 100;
            
            $('.progress-fill').css('width', progress + '%');
            $('.progress-text').text('Step ' + (currentIndex + 1) + ' of ' + stepKeys.length);
        },

        showCurrentStep: function() {
            $('.step-content').hide();
            $('.step-content.' + this.currentStep + '-step').show();
            
            // Update navigation buttons
            this.updateNavigationButtons();
        },

        updateNavigationButtons: function() {
            const stepKeys = Object.keys(this.steps);
            const currentIndex = stepKeys.indexOf(this.currentStep);
            
            // Show/hide previous button
            if (currentIndex > 0) {
                $('#prev-step').show();
            } else {
                $('#prev-step').hide();
            }
            
            // Update next/complete button
            if (currentIndex < stepKeys.length - 1) {
                $('#next-step').show();
                $('#complete-setup').hide();
            } else {
                $('#next-step').hide();
                $('#complete-setup').show();
            }
        },

        nextStep: function() {
            if (this.validateCurrentStep()) {
                this.saveCurrentStep().then(() => {
                    this.goToStep(this.getNextStep());
                }).catch((error) => {
                    this.showMessage('Error saving step: ' + error, 'error');
                });
            }
        },

        prevStep: function() {
            this.goToStep(this.getPrevStep());
        },

        goToStep: function(step) {
            this.currentStep = step;
            this.updateProgress();
            this.showCurrentStep();
            
            // Smooth scroll to top
            $('html, body').animate({
                scrollTop: $('.xelite-onboarding').offset().top - 50
            }, 300);
        },

        getNextStep: function() {
            const stepKeys = Object.keys(this.steps);
            const currentIndex = stepKeys.indexOf(this.currentStep);
            
            if (currentIndex < stepKeys.length - 1) {
                return stepKeys[currentIndex + 1];
            }
            
            return this.currentStep;
        },

        getPrevStep: function() {
            const stepKeys = Object.keys(this.steps);
            const currentIndex = stepKeys.indexOf(this.currentStep);
            
            if (currentIndex > 0) {
                return stepKeys[currentIndex - 1];
            }
            
            return this.currentStep;
        },

        validateCurrentStep: function() {
            const currentStepElement = $('.step-content.' + this.currentStep + '-step');
            
            // Check required fields
            const requiredFields = currentStepElement.find('[required]');
            let isValid = true;
            
            requiredFields.each(function() {
                const field = $(this);
                const value = field.val().trim();
                
                if (!value) {
                    field.addClass('error');
                    isValid = false;
                } else {
                    field.removeClass('error');
                }
            });
            
            // Step-specific validation
            switch (this.currentStep) {
                case 'api_config':
                    isValid = this.validateApiConfig() && isValid;
                    break;
                case 'target_accounts':
                    isValid = this.validateTargetAccounts() && isValid;
                    break;
            }
            
            return isValid;
        },

        validateApiConfig: function() {
            const xApiKey = $('#x_api_key').val().trim();
            
            if (!xApiKey) {
                this.showMessage('X (Twitter) API key is required.', 'error');
                return false;
            }
            
            return true;
        },

        validateTargetAccounts: function() {
            const targetAccounts = $('#target_accounts').val().trim();
            
            if (!targetAccounts) {
                this.showMessage('Please add at least one target account to monitor.', 'error');
                return false;
            }
            
            const accounts = targetAccounts.split('\n').filter(account => account.trim());
            
            if (accounts.length === 0) {
                this.showMessage('Please add at least one target account to monitor.', 'error');
                return false;
            }
            
            return true;
        },

        saveCurrentStep: function() {
            return new Promise((resolve, reject) => {
                const formData = this.getFormData();
                
                if (Object.keys(formData).length === 0) {
                    resolve();
                    return;
                }
                
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'save_onboarding_step',
                        nonce: this.nonce,
                        step: this.currentStep,
                        data: formData
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(response.data || 'Unknown error');
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(error);
                    }
                });
            });
        },

        getFormData: function() {
            const formData = {};
            const currentStepElement = $('.step-content.' + this.currentStep + '-step');
            
            // Get form fields
            currentStepElement.find('input, select, textarea').each(function() {
                const field = $(this);
                const name = field.attr('name');
                const value = field.val();
                
                if (name && value !== undefined) {
                    formData[name] = value;
                }
            });
            
            return formData;
        },

        skipWizard: function() {
            if (confirm('Are you sure you want to skip the setup wizard? You can always access it later from the plugin menu.')) {
                this.showLoading();
                
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'skip_onboarding',
                        nonce: this.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            this.hideLoading();
                            this.showMessage('Error skipping wizard: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: (xhr, status, error) => {
                        this.hideLoading();
                        this.showMessage('Error skipping wizard: ' + error, 'error');
                    }
                });
            }
        },

        completeSetup: function() {
            this.saveCurrentStep().then(() => {
                this.showLoading();
                
                // Mark onboarding as completed
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'complete_onboarding',
                        nonce: this.nonce
                    },
                    success: (response) => {
                        this.hideLoading();
                        if (response.success) {
                            this.showMessage('Setup completed successfully!', 'success');
                            setTimeout(() => {
                                window.location.href = response.data.redirect_url || 'admin.php?page=repost-intelligence';
                            }, 2000);
                        } else {
                            this.showMessage('Error completing setup: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: (xhr, status, error) => {
                        this.hideLoading();
                        this.showMessage('Error completing setup: ' + error, 'error');
                    }
                });
            }).catch((error) => {
                this.showMessage('Error saving final step: ' + error, 'error');
            });
        },

        testXApi: function() {
            const apiKey = $('#x_api_key').val().trim();
            
            if (!apiKey) {
                this.showMessage('Please enter your X (Twitter) API key first.', 'warning');
                return;
            }
            
            this.showApiTestResult('Testing X API...', 'info');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'test_x_api',
                    nonce: this.nonce,
                    api_key: apiKey
                },
                success: (response) => {
                    if (response.success) {
                        this.showApiTestResult('X API connection successful!', 'success');
                    } else {
                        this.showApiTestResult('X API test failed: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showApiTestResult('X API test failed: ' + error, 'error');
                }
            });
        },

        testOpenAIApi: function() {
            const apiKey = $('#openai_api_key').val().trim();
            
            if (!apiKey) {
                this.showMessage('Please enter your OpenAI API key first.', 'warning');
                return;
            }
            
            this.showApiTestResult('Testing OpenAI API...', 'info');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'test_openai_api',
                    nonce: this.nonce,
                    api_key: apiKey
                },
                success: (response) => {
                    if (response.success) {
                        this.showApiTestResult('OpenAI API connection successful!', 'success');
                    } else {
                        this.showApiTestResult('OpenAI API test failed: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showApiTestResult('OpenAI API test failed: ' + error, 'error');
                }
            });
        },

        showApiTestResult: function(message, type) {
            const resultElement = $('#api-test-results');
            resultElement.removeClass('success error info').addClass(type);
            resultElement.text(message).show();
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                resultElement.fadeOut();
            }, 5000);
        },

        addSuggestion: function(e) {
            const account = $(e.target).data('account');
            const textarea = $('#target_accounts');
            const currentValue = textarea.val();
            
            if (currentValue) {
                textarea.val(currentValue + '\n' + account);
            } else {
                textarea.val(account);
            }
            
            // Highlight the button briefly
            $(e.target).addClass('added').delay(500).queue(function() {
                $(this).removeClass('added').dequeue();
            });
        },

        validateForm: function() {
            // Remove error class when user starts typing
            $(this).removeClass('error');
        },

        showMessage: function(message, type) {
            // Remove existing messages
            $('.onboarding-message').remove();
            
            // Create new message
            const messageElement = $('<div class="onboarding-message ' + type + '">' + message + '</div>');
            $('.xelite-onboarding-content').prepend(messageElement);
            messageElement.show();
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                messageElement.fadeOut();
            }, 5000);
        },

        showLoading: function() {
            $('.xelite-onboarding').addClass('loading');
        },

        hideLoading: function() {
            $('.xelite-onboarding').removeClass('loading');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        XeliteOnboarding.init();
    });

    // Add CSS for suggestion button animation
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .suggestion-btn.added {
                background: #28a745 !important;
                color: #fff !important;
                border-color: #28a745 !important;
                transform: scale(1.05);
            }
            .form-group input.error,
            .form-group select.error,
            .form-group textarea.error {
                border-color: #dc3545;
                box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            }
        `)
        .appendTo('head');

})(jQuery); 