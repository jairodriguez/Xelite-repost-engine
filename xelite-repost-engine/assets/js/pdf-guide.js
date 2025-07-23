/**
 * Xelite Repost Engine PDF Guide JavaScript
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // PDF Guide functionality
    const XelitePdfGuide = {
        ajaxUrl: xelitePdfGuide.ajaxUrl || '',
        nonce: xelitePdfGuide.nonce || '',

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Generate PDF button
            $('#generate-pdf').on('click', this.generatePdf.bind(this));
            
            // Form validation
            $('#pdf-generation-form input, #pdf-generation-form select').on('change', this.validateForm.bind(this));
        },

        generatePdf: function(e) {
            e.preventDefault();
            
            if (!this.validateForm()) {
                return;
            }

            const formData = this.getFormData();
            
            // Show progress
            this.showProgress();
            
            // Disable button
            $('#generate-pdf').prop('disabled', true).text('Generating PDF...');
            
            // Make AJAX request
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'download_user_guide',
                    nonce: this.nonce,
                    ...formData
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(response, status, xhr) {
                    // Create download link
                    const blob = new Blob([response], { type: 'application/pdf' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'xelite-repost-engine-user-guide.pdf';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    // Show success message
                    XelitePdfGuide.showMessage('PDF generated successfully!', 'success');
                },
                error: function(xhr, status, error) {
                    console.error('PDF generation error:', error);
                    XelitePdfGuide.showMessage('PDF generation failed. Please try again.', 'error');
                },
                complete: function() {
                    // Hide progress and re-enable button
                    XelitePdfGuide.hideProgress();
                    $('#generate-pdf').prop('disabled', false).html('<span class="dashicons dashicons-pdf"></span> Generate PDF User Guide');
                }
            });
        },

        validateForm: function() {
            let isValid = true;
            const errors = [];
            
            // Validate language selection
            const language = $('#pdf_language').val();
            if (!language) {
                errors.push('Please select a language');
                isValid = false;
            }
            
            // Show/hide error messages
            if (errors.length > 0) {
                this.showMessage(errors.join('<br>'), 'error');
            } else {
                $('#pdf-result').empty();
            }
            
            return isValid;
        },

        getFormData: function() {
            return {
                language: $('#pdf_language').val(),
                include_screenshots: $('#pdf_include_screenshots').is(':checked'),
                include_code: $('#pdf_include_code').is(':checked'),
                include_troubleshooting: $('#pdf_include_troubleshooting').is(':checked')
            };
        },

        showProgress: function() {
            $('#pdf-progress').show();
            $('.progress-fill').css('width', '0%');
            
            // Animate progress bar
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) {
                    progress = 90;
                    clearInterval(interval);
                }
                $('.progress-fill').css('width', progress + '%');
            }, 200);
            
            this.progressInterval = interval;
        },

        hideProgress: function() {
            $('#pdf-progress').hide();
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }
            $('.progress-fill').css('width', '100%');
            setTimeout(() => {
                $('.progress-fill').css('width', '0%');
            }, 500);
        },

        showMessage: function(message, type) {
            const alertClass = type === 'error' ? 'notice-error' : 'notice-success';
            const icon = type === 'error' ? 'dashicons-warning' : 'dashicons-yes-alt';
            
            const alertHtml = `
                <div class="notice ${alertClass} is-dismissible">
                    <p>
                        <span class="dashicons ${icon}"></span>
                        ${message}
                    </p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `;
            
            $('#pdf-result').html(alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $('#pdf-result .notice').fadeOut();
            }, 5000);
        },

        updatePreview: function() {
            const language = $('#pdf_language').val();
            const includeScreenshots = $('#pdf_include_screenshots').is(':checked');
            const includeCode = $('#pdf_include_code').is(':checked');
            const includeTroubleshooting = $('#pdf_include_troubleshooting').is(':checked');
            
            // Update preview content based on options
            let previewContent = '<h4>Table of Contents</h4><ul>';
            
            const sections = [
                '1. Introduction',
                '2. Installation & Setup',
                '3. Basic Configuration',
                '4. Dashboard Overview',
                '5. Content Analysis',
                '6. Automated Reposting',
                '7. Chrome Extension',
                '8. Advanced Features'
            ];
            
            if (includeTroubleshooting) {
                sections.push('9. Troubleshooting');
            }
            
            sections.push('10. API Reference', '11. Case Studies', '12. Appendices');
            
            sections.forEach(section => {
                previewContent += `<li>${section}</li>`;
            });
            
            previewContent += '</ul>';
            
            if (includeScreenshots) {
                previewContent += '<p><em>📸 Screenshots will be included</em></p>';
            }
            
            if (includeCode) {
                previewContent += '<p><em>💻 Code examples will be included</em></p>';
            }
            
            $('.preview-content').html(previewContent);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        XelitePdfGuide.init();
        
        // Update preview when options change
        $('#pdf-generation-form input, #pdf-generation-form select').on('change', function() {
            XelitePdfGuide.updatePreview();
        });
        
        // Initial preview update
        XelitePdfGuide.updatePreview();
    });

    // Add CSS for better styling
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .pdf-guide-container .notice {
                margin: 15px 0;
            }
            .pdf-guide-container .notice p {
                margin: 0;
                padding: 10px 0;
            }
            .pdf-guide-container .dashicons {
                margin-right: 5px;
                vertical-align: middle;
            }
            .progress-bar {
                background: linear-gradient(90deg, #f0f0f0, #e0e0e0);
                border-radius: 10px;
                overflow: hidden;
                box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
            }
            .progress-fill {
                background: linear-gradient(90deg, #0073aa, #005a87);
                height: 100%;
                border-radius: 10px;
                transition: width 0.3s ease;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            }
        `)
        .appendTo('head');

})(jQuery); 