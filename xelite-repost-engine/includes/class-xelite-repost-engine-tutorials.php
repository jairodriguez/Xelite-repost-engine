<?php
/**
 * Xelite Repost Engine Video Tutorials and Tooltips
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles video tutorials and tooltips throughout the plugin
 */
class Xelite_Repost_Engine_Tutorials {

    /**
     * Tutorial videos configuration
     */
    private $tutorials = array(
        'getting_started' => array(
            'title' => 'Getting Started with Xelite Repost Engine',
            'description' => 'Learn how to install, configure, and start using the plugin',
            'duration' => '5:30',
            'youtube_id' => 'demo_video_1',
            'vimeo_id' => 'demo_video_1',
            'category' => 'basics',
            'order' => 1
        ),
        'api_configuration' => array(
            'title' => 'API Configuration Guide',
            'description' => 'Step-by-step guide to setting up X (Twitter) and OpenAI APIs',
            'duration' => '4:15',
            'youtube_id' => 'demo_video_2',
            'vimeo_id' => 'demo_video_2',
            'category' => 'setup',
            'order' => 2
        ),
        'dashboard_overview' => array(
            'title' => 'Dashboard Overview and Analytics',
            'description' => 'Understanding your dashboard metrics and insights',
            'duration' => '6:45',
            'youtube_id' => 'demo_video_3',
            'vimeo_id' => 'demo_video_3',
            'category' => 'features',
            'order' => 3
        ),
        'content_analysis' => array(
            'title' => 'Content Analysis and AI Insights',
            'description' => 'How to use AI-powered content analysis for better engagement',
            'duration' => '7:20',
            'youtube_id' => 'demo_video_4',
            'vimeo_id' => 'demo_video_4',
            'category' => 'features',
            'order' => 4
        ),
        'automated_reposting' => array(
            'title' => 'Automated Reposting Setup',
            'description' => 'Configure automated reposting for optimal engagement',
            'duration' => '5:50',
            'youtube_id' => 'demo_video_5',
            'vimeo_id' => 'demo_video_5',
            'category' => 'features',
            'order' => 5
        ),
        'chrome_extension' => array(
            'title' => 'Chrome Extension Installation and Usage',
            'description' => 'Install and use the Chrome extension for data scraping',
            'duration' => '4:30',
            'youtube_id' => 'demo_video_6',
            'vimeo_id' => 'demo_video_6',
            'category' => 'advanced',
            'order' => 6
        ),
        'woocommerce_integration' => array(
            'title' => 'WooCommerce Integration',
            'description' => 'Integrate the plugin with your WooCommerce store',
            'duration' => '6:10',
            'youtube_id' => 'demo_video_7',
            'vimeo_id' => 'demo_video_7',
            'category' => 'advanced',
            'order' => 7
        )
    );

    /**
     * Tooltips configuration
     */
    private $tooltips = array(
        // Settings page tooltips
        'x_api_key' => array(
            'title' => 'X (Twitter) API Key',
            'content' => 'Your X (Twitter) API key from the developer portal. This is required for fetching tweets and user data.',
            'position' => 'right'
        ),
        'openai_api_key' => array(
            'title' => 'OpenAI API Key',
            'content' => 'Your OpenAI API key for AI-powered content analysis and generation.',
            'position' => 'right'
        ),
        'target_accounts' => array(
            'title' => 'Target Accounts',
            'content' => 'X (Twitter) usernames to monitor for repost patterns. Separate multiple accounts with commas.',
            'position' => 'right'
        ),
        'auto_scrape' => array(
            'title' => 'Auto Scrape',
            'content' => 'Automatically scrape content from target accounts at regular intervals.',
            'position' => 'right'
        ),
        'scrape_interval' => array(
            'title' => 'Scrape Interval',
            'content' => 'How often to automatically scrape content from target accounts.',
            'position' => 'right'
        ),
        
        // Dashboard tooltips
        'engagement_rate' => array(
            'title' => 'Engagement Rate',
            'content' => 'Percentage of your followers who interact with your content (likes, retweets, replies).',
            'position' => 'top'
        ),
        'repost_opportunities' => array(
            'title' => 'Repost Opportunities',
            'content' => 'Number of posts identified as good candidates for reposting.',
            'position' => 'top'
        ),
        'ai_suggestions' => array(
            'title' => 'AI Suggestions',
            'content' => 'AI-generated content suggestions based on successful patterns.',
            'position' => 'top'
        ),
        
        // Content analysis tooltips
        'pattern_analysis' => array(
            'title' => 'Pattern Analysis',
            'content' => 'AI analysis of successful content patterns in your niche.',
            'position' => 'left'
        ),
        'hashtag_optimization' => array(
            'title' => 'Hashtag Optimization',
            'content' => 'Suggested hashtags based on trending topics and engagement data.',
            'position' => 'left'
        ),
        'timing_insights' => array(
            'title' => 'Timing Insights',
            'content' => 'Optimal posting times based on your audience activity patterns.',
            'position' => 'left'
        ),
        
        // Chrome extension tooltips
        'extension_installation' => array(
            'title' => 'Extension Installation',
            'content' => 'Step-by-step guide to install the Chrome extension for data scraping.',
            'position' => 'bottom'
        ),
        'wordpress_integration' => array(
            'title' => 'WordPress Integration',
            'content' => 'How the Chrome extension communicates with your WordPress plugin.',
            'position' => 'bottom'
        ),
        'data_sync' => array(
            'title' => 'Data Synchronization',
            'content' => 'Automatic synchronization of scraped data with your WordPress plugin.',
            'position' => 'bottom'
        )
    );

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_tutorials_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_tutorial_scripts'));
        add_action('wp_ajax_dismiss_tooltip', array($this, 'dismiss_tooltip'));
        add_action('wp_ajax_get_tutorial_video', array($this, 'get_tutorial_video'));
        add_action('admin_footer', array($this, 'add_tooltip_overlay'));
        add_action('admin_head', array($this, 'add_tooltip_styles'));
    }

    /**
     * Add tutorials menu
     */
    public function add_tutorials_menu() {
        add_submenu_page(
            'xelite-repost-engine',
            'Video Tutorials',
            'Video Tutorials',
            'manage_options',
            'xelite-repost-engine-tutorials',
            array($this, 'render_tutorials_page')
        );
    }

    /**
     * Enqueue tutorial scripts
     */
    public function enqueue_tutorial_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'xelite-repost-engine') === false) {
            return;
        }

        wp_enqueue_script(
            'xelite-tutorials',
            plugin_dir_url(__FILE__) . '../assets/js/tutorials.js',
            array('jquery', 'wp-pointer'),
            XELITE_REPOST_ENGINE_VERSION,
            true
        );

        wp_enqueue_style(
            'xelite-tutorials',
            plugin_dir_url(__FILE__) . '../assets/css/tutorials.css',
            array(),
            XELITE_REPOST_ENGINE_VERSION
        );

        wp_localize_script('xelite-tutorials', 'xeliteTutorials', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xelite_tutorials_nonce'),
            'tooltips' => $this->tooltips,
            'tutorials' => $this->tutorials,
            'dismissedTooltips' => $this->get_dismissed_tooltips()
        ));
    }

    /**
     * Render tutorials page
     */
    public function render_tutorials_page() {
        $categories = array(
            'basics' => 'Getting Started',
            'setup' => 'Setup & Configuration',
            'features' => 'Core Features',
            'advanced' => 'Advanced Features'
        );

        ?>
        <div class="wrap">
            <h1>Xelite Repost Engine - Video Tutorials</h1>
            
            <div class="tutorials-container">
                <div class="tutorials-sidebar">
                    <h3>Categories</h3>
                    <ul class="tutorial-categories">
                        <?php foreach ($categories as $slug => $name): ?>
                            <li>
                                <a href="#category-<?php echo esc_attr($slug); ?>" class="category-link" data-category="<?php echo esc_attr($slug); ?>">
                                    <?php echo esc_html($name); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="tutorial-stats">
                        <h4>Your Progress</h4>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo esc_attr($this->get_watched_percentage()); ?>%"></div>
                        </div>
                        <p><?php echo esc_html($this->get_watched_count()); ?> of <?php echo esc_html(count($this->tutorials)); ?> tutorials watched</p>
                    </div>
                </div>

                <div class="tutorials-content">
                    <?php foreach ($categories as $slug => $name): ?>
                        <div class="tutorial-category" id="category-<?php echo esc_attr($slug); ?>">
                            <h2><?php echo esc_html($name); ?></h2>
                            
                            <?php
                            $category_tutorials = array_filter($this->tutorials, function($tutorial) use ($slug) {
                                return $tutorial['category'] === $slug;
                            });
                            
                            foreach ($category_tutorials as $tutorial_id => $tutorial):
                            ?>
                                <div class="tutorial-item" data-tutorial="<?php echo esc_attr($tutorial_id); ?>">
                                    <div class="tutorial-thumbnail">
                                        <img src="https://img.youtube.com/vi/<?php echo esc_attr($tutorial['youtube_id']); ?>/mqdefault.jpg" alt="<?php echo esc_attr($tutorial['title']); ?>">
                                        <div class="play-button">
                                            <span class="dashicons dashicons-controls-play"></span>
                                        </div>
                                        <div class="duration"><?php echo esc_html($tutorial['duration']); ?></div>
                                    </div>
                                    
                                    <div class="tutorial-info">
                                        <h3><?php echo esc_html($tutorial['title']); ?></h3>
                                        <p><?php echo esc_html($tutorial['description']); ?></p>
                                        
                                        <div class="tutorial-actions">
                                            <button class="button button-primary watch-tutorial" data-tutorial="<?php echo esc_attr($tutorial_id); ?>">
                                                <span class="dashicons dashicons-video-alt3"></span>
                                                Watch Tutorial
                                            </button>
                                            
                                            <?php if ($this->is_tutorial_watched($tutorial_id)): ?>
                                                <span class="watched-badge">
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                    Watched
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Video Modal -->
            <div id="video-modal" class="video-modal">
                <div class="video-modal-content">
                    <div class="video-modal-header">
                        <h3 id="video-title"></h3>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="video-modal-body">
                        <div id="video-player"></div>
                        <div class="video-description">
                            <p id="video-description"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .tutorials-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .tutorials-sidebar {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .tutorial-categories {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .tutorial-categories li {
            margin-bottom: 10px;
        }

        .tutorial-categories a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .tutorial-categories a:hover,
        .tutorial-categories a.active {
            background: #0073aa;
            color: #fff;
        }

        .tutorial-stats {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa, #005a87);
            transition: width 0.3s ease;
        }

        .tutorial-category {
            margin-bottom: 40px;
        }

        .tutorial-item {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .tutorial-thumbnail {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }

        .tutorial-thumbnail img {
            width: 100%;
            height: auto;
            display: block;
        }

        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: #fff;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .play-button:hover {
            background: rgba(0,0,0,0.9);
        }

        .duration {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        .tutorial-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .tutorial-info p {
            margin: 0 0 15px 0;
            color: #666;
            line-height: 1.5;
        }

        .tutorial-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .watched-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #28a745;
            font-size: 14px;
        }

        .video-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .video-modal-content {
            position: relative;
            margin: 5% auto;
            width: 80%;
            max-width: 800px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .video-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .video-modal-header h3 {
            margin: 0;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .video-modal-body {
            padding: 20px;
        }

        #video-player {
            width: 100%;
            height: 400px;
            background: #000;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .tutorials-container {
                grid-template-columns: 1fr;
            }
            
            .tutorial-item {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }

    /**
     * Add tooltip overlay
     */
    public function add_tooltip_overlay() {
        ?>
        <div id="tooltip-overlay" style="display: none;">
            <div class="tooltip-content">
                <div class="tooltip-header">
                    <h4 id="tooltip-title"></h4>
                    <button class="tooltip-close">&times;</button>
                </div>
                <div class="tooltip-body">
                    <p id="tooltip-text"></p>
                </div>
                <div class="tooltip-footer">
                    <label>
                        <input type="checkbox" id="dismiss-tooltip"> Don't show this again
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Add tooltip styles
     */
    public function add_tooltip_styles() {
        ?>
        <style>
        .help-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            background: #0073aa;
            color: #fff;
            border-radius: 50%;
            text-align: center;
            line-height: 16px;
            font-size: 12px;
            cursor: help;
            margin-left: 5px;
            vertical-align: middle;
        }

        .help-icon:hover {
            background: #005a87;
        }

        #tooltip-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tooltip-content {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 90%;
        }

        .tooltip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }

        .tooltip-header h4 {
            margin: 0;
            color: #333;
        }

        .tooltip-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }

        .tooltip-body {
            padding: 20px;
        }

        .tooltip-body p {
            margin: 0;
            line-height: 1.5;
            color: #666;
        }

        .tooltip-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            background: #f9f9f9;
        }

        .tooltip-footer label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }
        </style>
        <?php
    }

    /**
     * Dismiss tooltip AJAX handler
     */
    public function dismiss_tooltip() {
        check_ajax_referer('xelite_tutorials_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $tooltip_id = sanitize_text_field($_POST['tooltip_id']);
        $dismissed_tooltips = $this->get_dismissed_tooltips();
        
        if (!in_array($tooltip_id, $dismissed_tooltips)) {
            $dismissed_tooltips[] = $tooltip_id;
            update_user_meta(get_current_user_id(), 'xelite_dismissed_tooltips', $dismissed_tooltips);
        }

        wp_send_json_success();
    }

    /**
     * Get tutorial video AJAX handler
     */
    public function get_tutorial_video() {
        check_ajax_referer('xelite_tutorials_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $tutorial_id = sanitize_text_field($_POST['tutorial_id']);
        
        if (!isset($this->tutorials[$tutorial_id])) {
            wp_send_json_error('Tutorial not found');
        }

        $tutorial = $this->tutorials[$tutorial_id];
        
        // Mark as watched
        $this->mark_tutorial_watched($tutorial_id);

        wp_send_json_success(array(
            'title' => $tutorial['title'],
            'description' => $tutorial['description'],
            'youtube_id' => $tutorial['youtube_id'],
            'vimeo_id' => $tutorial['vimeo_id']
        ));
    }

    /**
     * Get dismissed tooltips
     */
    private function get_dismissed_tooltips() {
        return get_user_meta(get_current_user_id(), 'xelite_dismissed_tooltips', true) ?: array();
    }

    /**
     * Check if tutorial is watched
     */
    private function is_tutorial_watched($tutorial_id) {
        $watched_tutorials = get_user_meta(get_current_user_id(), 'xelite_watched_tutorials', true) ?: array();
        return in_array($tutorial_id, $watched_tutorials);
    }

    /**
     * Mark tutorial as watched
     */
    private function mark_tutorial_watched($tutorial_id) {
        $watched_tutorials = get_user_meta(get_current_user_id(), 'xelite_watched_tutorials', true) ?: array();
        
        if (!in_array($tutorial_id, $watched_tutorials)) {
            $watched_tutorials[] = $tutorial_id;
            update_user_meta(get_current_user_id(), 'xelite_watched_tutorials', $watched_tutorials);
        }
    }

    /**
     * Get watched count
     */
    private function get_watched_count() {
        $watched_tutorials = get_user_meta(get_current_user_id(), 'xelite_watched_tutorials', true) ?: array();
        return count($watched_tutorials);
    }

    /**
     * Get watched percentage
     */
    private function get_watched_percentage() {
        $total = count($this->tutorials);
        $watched = $this->get_watched_count();
        return $total > 0 ? round(($watched / $total) * 100) : 0;
    }

    /**
     * Add help icon to form field
     */
    public static function add_help_icon($tooltip_id) {
        if (isset($this->tooltips[$tooltip_id])) {
            echo '<span class="help-icon" data-tooltip="' . esc_attr($tooltip_id) . '">?</span>';
        }
    }
} 