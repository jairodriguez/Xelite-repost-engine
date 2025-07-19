/**
 * Xelite Repost Engine - Public JavaScript
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Main plugin object
    var XeliteRepostEngine = {
        
        // Initialize the plugin
        init: function() {
            this.bindEvents();
            this.initComponents();
            console.log('Xelite Repost Engine Public initialized');
        },

        // Bind event handlers
        bindEvents: function() {
            // Content generation form
            $(document).on('submit', '.xelite-generator-form', this.handleContentGeneration);
            
            // Copy content button
            $(document).on('click', '.xelite-content-btn.copy', this.handleCopyContent);
            
            // Post content button
            $(document).on('click', '.xelite-content-btn.post', this.handlePostContent);
            
            // Refresh patterns button
            $(document).on('click', '.xelite-refresh-patterns', this.handleRefreshPatterns);
            
            // Load more patterns
            $(document).on('click', '.xelite-load-more', this.handleLoadMore);
        },

        // Initialize components
        initComponents: function() {
            this.initTooltips();
            this.initCharacterCount();
            this.initAutoSave();
        },

        // Handle content generation
        handleContentGeneration: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $input = $form.find('input[name="topic"]');
            var $output = $form.siblings('.xelite-generated-content');
            
            var topic = $input.val().trim();
            
            if (!topic) {
                XeliteRepostEngine.showError('Please enter a topic for content generation.');
                return;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true).text(xeliteRepostEngine.strings.generating);
            $output.html('<div class="xelite-loading">' + xeliteRepostEngine.strings.generating + '</div>');
            
            // Make AJAX request
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_generate_content',
                    topic: topic,
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteRepostEngine.displayGeneratedContent(response.data.content, $output);
                    } else {
                        XeliteRepostEngine.showError(response.data.message || xeliteRepostEngine.strings.error);
                    }
                },
                error: function() {
                    XeliteRepostEngine.showError(xeliteRepostEngine.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Generate Content');
                }
            });
        },

        // Display generated content
        displayGeneratedContent: function(content, $container) {
            var html = '<div class="content-text">' + this.escapeHtml(content) + '</div>' +
                      '<div class="content-actions">' +
                      '<button class="xelite-content-btn copy" data-content="' + this.escapeHtml(content) + '">Copy</button>' +
                      '<button class="xelite-content-btn post" data-content="' + this.escapeHtml(content) + '">Post to X</button>' +
                      '</div>';
            
            $container.html(html);
        },

        // Handle copy content
        handleCopyContent: function(e) {
            e.preventDefault();
            
            var content = $(this).data('content');
            
            // Use modern clipboard API if available
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(content).then(function() {
                    XeliteRepostEngine.showSuccess('Content copied to clipboard!');
                }).catch(function() {
                    XeliteRepostEngine.fallbackCopyTextToClipboard(content);
                });
            } else {
                XeliteRepostEngine.fallbackCopyTextToClipboard(content);
            }
        },

        // Fallback copy method
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
                this.showSuccess('Content copied to clipboard!');
            } catch (err) {
                this.showError('Failed to copy content to clipboard.');
            }
            
            document.body.removeChild(textArea);
        },

        // Handle post content
        handlePostContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var content = $button.data('content');
            
            if (!confirm('Are you sure you want to post this content to X?')) {
                return;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Posting...');
            
            // Make AJAX request to post content
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_post_content',
                    content: content,
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteRepostEngine.showSuccess('Content posted successfully!');
                        $button.text('Posted').addClass('posted');
                    } else {
                        XeliteRepostEngine.showError(response.data.message || xeliteRepostEngine.strings.error);
                        $button.prop('disabled', false).text('Post to X');
                    }
                },
                error: function() {
                    XeliteRepostEngine.showError(xeliteRepostEngine.strings.error);
                    $button.prop('disabled', false).text('Post to X');
                }
            });
        },

        // Handle refresh patterns
        handleRefreshPatterns: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.siblings('.xelite-repost-patterns');
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Refreshing...');
            $container.html('<div class="xelite-loading">Loading patterns...</div>');
            
            // Make AJAX request to refresh patterns
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_refresh_patterns',
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteRepostEngine.displayRepostPatterns(response.data.patterns, $container);
                    } else {
                        XeliteRepostEngine.showError(response.data.message || xeliteRepostEngine.strings.error);
                    }
                },
                error: function() {
                    XeliteRepostEngine.showError(xeliteRepostEngine.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Refresh Patterns');
                }
            });
        },

        // Display repost patterns
        displayRepostPatterns: function(patterns, $container) {
            if (!patterns || patterns.length === 0) {
                $container.html('<div class="xelite-no-data">' + xeliteRepostEngine.strings.no_data + '</div>');
                return;
            }
            
            var html = '';
            patterns.forEach(function(pattern) {
                html += '<div class="xelite-pattern-item">' +
                       '<div class="xelite-pattern-header">' +
                       '<span class="xelite-pattern-source">@' + XeliteRepostEngine.escapeHtml(pattern.source_handle) + '</span>' +
                       '<span class="xelite-pattern-count">' + pattern.repost_count + ' reposts</span>' +
                       '</div>' +
                       '<div class="xelite-pattern-text">' + XeliteRepostEngine.escapeHtml(pattern.original_text) + '</div>' +
                       '<div class="xelite-pattern-meta">' + XeliteRepostEngine.formatDate(pattern.created_at) + '</div>' +
                       '</div>';
            });
            
            $container.html(html);
        },

        // Handle load more
        handleLoadMore: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.siblings('.xelite-repost-patterns');
            var offset = parseInt($button.data('offset') || 0);
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Loading...');
            
            // Make AJAX request to load more patterns
            $.ajax({
                url: xeliteRepostEngine.ajax_url,
                type: 'POST',
                data: {
                    action: 'xelite_repost_engine_load_more_patterns',
                    offset: offset,
                    nonce: xeliteRepostEngine.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XeliteRepostEngine.appendRepostPatterns(response.data.patterns, $container);
                        $button.data('offset', offset + response.data.patterns.length);
                        
                        if (response.data.has_more === false) {
                            $button.hide();
                        }
                    } else {
                        XeliteRepostEngine.showError(response.data.message || xeliteRepostEngine.strings.error);
                    }
                },
                error: function() {
                    XeliteRepostEngine.showError(xeliteRepostEngine.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Load More');
                }
            });
        },

        // Append repost patterns
        appendRepostPatterns: function(patterns, $container) {
            patterns.forEach(function(pattern) {
                var html = '<div class="xelite-pattern-item">' +
                          '<div class="xelite-pattern-header">' +
                          '<span class="xelite-pattern-source">@' + XeliteRepostEngine.escapeHtml(pattern.source_handle) + '</span>' +
                          '<span class="xelite-pattern-count">' + pattern.repost_count + ' reposts</span>' +
                          '</div>' +
                          '<div class="xelite-pattern-text">' + XeliteRepostEngine.escapeHtml(pattern.original_text) + '</div>' +
                          '<div class="xelite-pattern-meta">' + XeliteRepostEngine.formatDate(pattern.created_at) + '</div>' +
                          '</div>';
                
                $container.append(html);
            });
        },

        // Initialize tooltips
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var $element = $(this);
                var tooltip = $element.data('tooltip');
                
                $element.attr('title', tooltip);
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

        // Initialize auto save
        initAutoSave: function() {
            var autoSaveTimer;
            
            $('.xelite-auto-save').each(function() {
                var $input = $(this);
                
                $input.on('input', function() {
                    clearTimeout(autoSaveTimer);
                    
                    autoSaveTimer = setTimeout(function() {
                        XeliteRepostEngine.autoSave($input);
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

        // Show success message
        showSuccess: function(message) {
            this.showMessage(message, 'success');
        },

        // Show error message
        showError: function(message) {
            this.showMessage(message, 'error');
        },

        // Show message
        showMessage: function(message, type) {
            var $message = $('<div class="xelite-' + type + '">' + message + '</div>');
            
            $('body').append($message);
            
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
            var now = new Date();
            var diff = now - date;
            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            
            if (days === 0) {
                return 'Today';
            } else if (days === 1) {
                return 'Yesterday';
            } else if (days < 7) {
                return days + ' days ago';
            } else {
                return date.toLocaleDateString();
            }
        },

        // Utility function to check if element is in viewport
        isInViewport: function(element) {
            var rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        // Lazy load images
        lazyLoadImages: function() {
            $('img[data-src]').each(function() {
                var $img = $(this);
                
                if (XeliteRepostEngine.isInViewport(this)) {
                    $img.attr('src', $img.data('src')).removeAttr('data-src');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        XeliteRepostEngine.init();
    });

    // Lazy load on scroll
    $(window).on('scroll', function() {
        XeliteRepostEngine.lazyLoadImages();
    });

    // Expose to global scope for debugging
    window.XeliteRepostEngine = XeliteRepostEngine;

})(jQuery); 