/**
 * Xelite Repost Engine Tutorials and Tooltips JavaScript
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Tutorials and Tooltips functionality
    const XeliteTutorials = {
        currentVideo: null,
        player: null,

        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initVideoPlayer();
        },

        bindEvents: function() {
            // Tutorial video events
            $(document).on('click', '.watch-tutorial, .play-button', this.openVideoModal.bind(this));
            $(document).on('click', '.close-modal', this.closeVideoModal.bind(this));
            $(document).on('click', '.category-link', this.filterTutorials.bind(this));
            
            // Tooltip events
            $(document).on('click', '.help-icon', this.showTooltip.bind(this));
            $(document).on('click', '.tooltip-close', this.hideTooltip.bind(this));
            $(document).on('click', '#tooltip-overlay', this.hideTooltip.bind(this));
            $(document).on('change', '#dismiss-tooltip', this.dismissTooltip.bind(this));
            
            // Close modal on outside click
            $(document).on('click', '#video-modal', function(e) {
                if (e.target === this) {
                    XeliteTutorials.closeVideoModal();
                }
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboard.bind(this));
        },

        initTooltips: function() {
            // Add help icons to form fields if they don't exist
            this.addHelpIcons();
        },

        addHelpIcons: function() {
            // Add help icons to common form fields
            const helpFields = {
                'input[name="x_api_key"]': 'x_api_key',
                'input[name="openai_api_key"]': 'openai_api_key',
                'input[name="target_accounts"]': 'target_accounts',
                'input[name="auto_scrape"]': 'auto_scrape',
                'select[name="scrape_interval"]': 'scrape_interval'
            };

            $.each(helpFields, function(selector, tooltipId) {
                if ($(selector).length && !$(selector).next('.help-icon').length) {
                    $(selector).after('<span class="help-icon" data-tooltip="' + tooltipId + '">?</span>');
                }
            });
        },

        initVideoPlayer: function() {
            // Initialize YouTube API if available
            if (typeof YT !== 'undefined' && YT.Player) {
                this.createPlayer();
            } else {
                // Load YouTube API
                const tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                const firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
                
                // Set up callback
                window.onYouTubeIframeAPIReady = this.createPlayer.bind(this);
            }
        },

        createPlayer: function() {
            if (typeof YT !== 'undefined' && YT.Player) {
                this.player = new YT.Player('video-player', {
                    height: '400',
                    width: '100%',
                    videoId: '',
                    playerVars: {
                        'rel': 0,
                        'showinfo': 0,
                        'modestbranding': 1
                    }
                });
            }
        },

        openVideoModal: function(e) {
            e.preventDefault();
            
            const tutorialId = $(e.currentTarget).data('tutorial');
            if (!tutorialId) return;

            // Show loading state
            $('#video-modal').show();
            $('#video-title').text('Loading...');
            $('#video-description').text('');
            $('#video-player').html('<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #fff;">Loading video...</div>');

            // Get video data
            $.ajax({
                url: xeliteTutorials.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_tutorial_video',
                    tutorial_id: tutorialId,
                    nonce: xeliteTutorials.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const video = response.data;
                        $('#video-title').text(video.title);
                        $('#video-description').text(video.description);
                        
                        // Load video
                        if (XeliteTutorials.player && video.youtube_id) {
                            XeliteTutorials.player.loadVideoById(video.youtube_id);
                        } else {
                            // Fallback to iframe
                            const iframe = $('<iframe>', {
                                src: 'https://www.youtube.com/embed/' + video.youtube_id + '?rel=0&showinfo=0&modestbranding=1',
                                width: '100%',
                                height: '400',
                                frameborder: '0',
                                allowfullscreen: true
                            });
                            $('#video-player').html(iframe);
                        }
                        
                        XeliteTutorials.currentVideo = tutorialId;
                        
                        // Update watched status
                        XeliteTutorials.updateWatchedStatus(tutorialId);
                    } else {
                        $('#video-title').text('Error');
                        $('#video-description').text('Failed to load video. Please try again.');
                    }
                },
                error: function() {
                    $('#video-title').text('Error');
                    $('#video-description').text('Failed to load video. Please try again.');
                }
            });
        },

        closeVideoModal: function() {
            $('#video-modal').hide();
            
            // Stop video
            if (this.player && this.player.stopVideo) {
                this.player.stopVideo();
            }
            
            this.currentVideo = null;
        },

        filterTutorials: function(e) {
            e.preventDefault();
            
            const category = $(e.currentTarget).data('category');
            
            // Update active category
            $('.category-link').removeClass('active');
            $(e.currentTarget).addClass('active');
            
            // Show/hide categories
            if (category === 'all') {
                $('.tutorial-category').show();
            } else {
                $('.tutorial-category').hide();
                $('#category-' + category).show();
            }
        },

        showTooltip: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const tooltipId = $(e.currentTarget).data('tooltip');
            const tooltip = xeliteTutorials.tooltips[tooltipId];
            
            if (!tooltip) return;
            
            // Check if tooltip is dismissed
            if (xeliteTutorials.dismissedTooltips.includes(tooltipId)) {
                return;
            }
            
            // Position tooltip
            const $icon = $(e.currentTarget);
            const iconOffset = $icon.offset();
            const $overlay = $('#tooltip-overlay');
            const $content = $overlay.find('.tooltip-content');
            
            // Set content
            $('#tooltip-title').text(tooltip.title);
            $('#tooltip-text').text(tooltip.content);
            
            // Position based on tooltip position setting
            let left = iconOffset.left + $icon.outerWidth() + 10;
            let top = iconOffset.top;
            
            switch (tooltip.position) {
                case 'left':
                    left = iconOffset.left - $content.outerWidth() - 10;
                    break;
                case 'top':
                    left = iconOffset.left + ($icon.outerWidth() / 2) - ($content.outerWidth() / 2);
                    top = iconOffset.top - $content.outerHeight() - 10;
                    break;
                case 'bottom':
                    left = iconOffset.left + ($icon.outerWidth() / 2) - ($content.outerWidth() / 2);
                    top = iconOffset.top + $icon.outerHeight() + 10;
                    break;
                default: // right
                    left = iconOffset.left + $icon.outerWidth() + 10;
                    top = iconOffset.top;
            }
            
            // Ensure tooltip stays within viewport
            const viewportWidth = $(window).width();
            const viewportHeight = $(window).height();
            
            if (left + $content.outerWidth() > viewportWidth) {
                left = viewportWidth - $content.outerWidth() - 20;
            }
            
            if (top + $content.outerHeight() > viewportHeight) {
                top = viewportHeight - $content.outerHeight() - 20;
            }
            
            if (left < 20) left = 20;
            if (top < 20) top = 20;
            
            $content.css({
                position: 'absolute',
                left: left + 'px',
                top: top + 'px'
            });
            
            $overlay.show();
            
            // Store current tooltip ID
            $overlay.data('tooltip-id', tooltipId);
        },

        hideTooltip: function() {
            $('#tooltip-overlay').hide();
        },

        dismissTooltip: function() {
            const tooltipId = $('#tooltip-overlay').data('tooltip-id');
            const dismiss = $('#dismiss-tooltip').is(':checked');
            
            if (dismiss && tooltipId) {
                $.ajax({
                    url: xeliteTutorials.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'dismiss_tooltip',
                        tooltip_id: tooltipId,
                        nonce: xeliteTutorials.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            xeliteTutorials.dismissedTooltips.push(tooltipId);
                        }
                    }
                });
            }
            
            this.hideTooltip();
        },

        updateWatchedStatus: function(tutorialId) {
            const $tutorial = $('[data-tutorial="' + tutorialId + '"]');
            const $badge = $tutorial.find('.watched-badge');
            
            if ($badge.length === 0) {
                const badge = '<span class="watched-badge"><span class="dashicons dashicons-yes-alt"></span> Watched</span>';
                $tutorial.find('.tutorial-actions').append(badge);
            }
            
            // Update progress
            this.updateProgress();
        },

        updateProgress: function() {
            const watchedCount = $('.watched-badge').length;
            const totalCount = $('.tutorial-item').length;
            const percentage = totalCount > 0 ? Math.round((watchedCount / totalCount) * 100) : 0;
            
            $('.progress-fill').css('width', percentage + '%');
            $('.tutorial-stats p').text(watchedCount + ' of ' + totalCount + ' tutorials watched');
        },

        handleKeyboard: function(e) {
            // ESC key closes modals
            if (e.keyCode === 27) {
                if ($('#video-modal').is(':visible')) {
                    this.closeVideoModal();
                }
                if ($('#tooltip-overlay').is(':visible')) {
                    this.hideTooltip();
                }
            }
        },

        // Initialize tooltips on page load
        initPageTooltips: function() {
            // Add tooltips to specific page elements
            const pageTooltips = {
                'dashboard': {
                    '.engagement-rate': 'engagement_rate',
                    '.repost-opportunities': 'repost_opportunities',
                    '.ai-suggestions': 'ai_suggestions'
                },
                'content-analysis': {
                    '.pattern-analysis': 'pattern_analysis',
                    '.hashtag-optimization': 'hashtag_optimization',
                    '.timing-insights': 'timing_insights'
                },
                'chrome-extension': {
                    '.extension-installation': 'extension_installation',
                    '.wordpress-integration': 'wordpress_integration',
                    '.data-sync': 'data_sync'
                }
            };
            
            // Detect current page and add tooltips
            const currentPage = this.getCurrentPage();
            if (pageTooltips[currentPage]) {
                $.each(pageTooltips[currentPage], function(selector, tooltipId) {
                    $(selector).each(function() {
                        if (!$(this).find('.help-icon').length) {
                            $(this).append('<span class="help-icon" data-tooltip="' + tooltipId + '">?</span>');
                        }
                    });
                });
            }
        },

        getCurrentPage: function() {
            const url = window.location.href;
            if (url.includes('dashboard')) return 'dashboard';
            if (url.includes('content-analysis')) return 'content-analysis';
            if (url.includes('chrome-extension')) return 'chrome-extension';
            return 'settings';
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        XeliteTutorials.init();
        XeliteTutorials.initPageTooltips();
    });

    // Add CSS for better styling
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .help-icon {
                transition: all 0.2s ease;
            }
            
            .help-icon:hover {
                transform: scale(1.1);
            }
            
            .tutorial-item {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .tutorial-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            }
            
            .play-button {
                transition: all 0.2s ease;
            }
            
            .play-button:hover {
                transform: scale(1.1);
            }
            
            .category-link {
                transition: all 0.2s ease;
            }
            
            .category-link:hover {
                transform: translateX(5px);
            }
            
            .video-modal {
                animation: fadeIn 0.3s ease;
            }
            
            .tooltip-content {
                animation: slideIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideIn {
                from { 
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to { 
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `)
        .appendTo('head');

})(jQuery); 