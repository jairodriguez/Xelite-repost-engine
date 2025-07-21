<?php
/**
 * Repost Intelligence Shortcodes
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles shortcode registration and rendering for front-end dashboard components
 */
class Xelite_Repost_Intelligence_Shortcodes {

    /**
     * Initialize the shortcode class
     */
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_shortcode_assets'));
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('repost_patterns', array($this, 'render_patterns_shortcode'));
        add_shortcode('repost_suggestions', array($this, 'render_suggestions_shortcode'));
        add_shortcode('repost_analytics', array($this, 'render_analytics_shortcode'));
    }

    /**
     * Enqueue assets for shortcodes
     */
    public function enqueue_shortcode_assets() {
        global $post;
        
        // Check if any of our shortcodes are present in the current post
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'repost_patterns') ||
            has_shortcode($post->post_content, 'repost_suggestions') ||
            has_shortcode($post->post_content, 'repost_analytics')
        )) {
            // Enqueue dashboard styles
            wp_enqueue_style(
                'xelite-dashboard-shortcode',
                plugin_dir_url(__FILE__) . '../assets/css/dashboard.css',
                array(),
                XELITE_REPOST_ENGINE_VERSION
            );

            // Enqueue Chart.js for analytics
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js',
                array(),
                '4.4.0',
                true
            );

            // Enqueue dashboard JavaScript
            wp_enqueue_script(
                'xelite-dashboard-shortcode',
                plugin_dir_url(__FILE__) . '../assets/js/dashboard.js',
                array('jquery', 'chartjs'),
                XELITE_REPOST_ENGINE_VERSION,
                true
            );

            // Localize script for AJAX
            wp_localize_script('xelite-dashboard-shortcode', 'xelite_dashboard', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('xelite_dashboard_nonce'),
                'isShortcode' => true
            ));
        }
    }

    /**
     * Render the repost patterns shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_patterns_shortcode($atts) {
        // Check user capabilities
        if (!$this->user_has_access()) {
            return $this->get_access_denied_message();
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'limit' => 10,
            'period' => '30', // days
            'show_filters' => 'true',
            'show_charts' => 'true',
            'theme' => 'light'
        ), $atts, 'repost_patterns');

        // Get user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p class="xelite-error">You must be logged in to view repost patterns.</p>';
        }

        // Get patterns data
        $patterns = $this->get_user_patterns($user_id, intval($atts['limit']), intval($atts['period']));

        ob_start();
        ?>
        <div class="xelite-shortcode-container xelite-patterns-shortcode" data-theme="<?php echo esc_attr($atts['theme']); ?>">
            <div class="xelite-shortcode-header">
                <h3>Repost Patterns</h3>
                <p class="xelite-shortcode-subtitle">Analysis of successful repost patterns from your target accounts</p>
            </div>

            <?php if ($atts['show_filters'] === 'true'): ?>
            <div class="xelite-shortcode-filters">
                <div class="xelite-filter-row">
                    <div class="xelite-filter-group">
                        <label for="shortcode-pattern-search">Search Patterns:</label>
                        <input type="text" id="shortcode-pattern-search" placeholder="Search patterns..." class="xelite-search-input">
                    </div>
                    <div class="xelite-filter-group">
                        <label for="shortcode-pattern-sort">Sort By:</label>
                        <select id="shortcode-pattern-sort" class="xelite-select">
                            <option value="date">Date</option>
                            <option value="engagement">Engagement</option>
                            <option value="frequency">Frequency</option>
                        </select>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_charts'] === 'true'): ?>
            <div class="xelite-shortcode-charts">
                <div class="xelite-chart-container">
                    <canvas id="shortcode-pattern-chart"></canvas>
                </div>
                <div class="xelite-chart-controls">
                    <select id="shortcode-chart-type" class="xelite-select">
                        <option value="line">Line Chart</option>
                        <option value="bar">Bar Chart</option>
                        <option value="pie">Pie Chart</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <div class="xelite-patterns-list" id="shortcode-patterns-list">
                <?php if (!empty($patterns)): ?>
                    <?php foreach ($patterns as $pattern): ?>
                    <div class="xelite-pattern-item">
                        <div class="xelite-pattern-header">
                            <span class="xelite-pattern-date"><?php echo esc_html(date('M j, Y', strtotime($pattern['timestamp']))); ?></span>
                            <span class="xelite-pattern-engagement"><?php echo esc_html($pattern['engagement_score']); ?> engagement</span>
                        </div>
                        <div class="xelite-pattern-content">
                            <p><?php echo esc_html($pattern['content']); ?></p>
                        </div>
                        <div class="xelite-pattern-meta">
                            <span class="xelite-pattern-tone"><?php echo esc_html($pattern['tone']); ?></span>
                            <span class="xelite-pattern-length"><?php echo esc_html($pattern['length']); ?> chars</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="xelite-no-data">
                        <p>No repost patterns found for the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="xelite-shortcode-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=xelite-repost-dashboard&tab=patterns')); ?>" class="xelite-button xelite-button-secondary">
                    View Full Dashboard
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the repost suggestions shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_suggestions_shortcode($atts) {
        // Check user capabilities
        if (!$this->user_has_access()) {
            return $this->get_access_denied_message();
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'limit' => 5,
            'show_generate' => 'true',
            'theme' => 'light'
        ), $atts, 'repost_suggestions');

        // Get user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p class="xelite-error">You must be logged in to view repost suggestions.</p>';
        }

        // Get user's recent suggestions
        $suggestions = $this->get_user_suggestions($user_id, intval($atts['limit']));

        ob_start();
        ?>
        <div class="xelite-shortcode-container xelite-suggestions-shortcode" data-theme="<?php echo esc_attr($atts['theme']); ?>">
            <div class="xelite-shortcode-header">
                <h3>AI-Generated Content Suggestions</h3>
                <p class="xelite-shortcode-subtitle">Personalized content based on your profile and repost patterns</p>
            </div>

            <?php if ($atts['show_generate'] === 'true'): ?>
            <div class="xelite-generate-section">
                <button type="button" id="shortcode-generate-suggestion" class="xelite-button xelite-button-primary">
                    <span class="xelite-button-text">Generate New Suggestion</span>
                    <span class="xelite-button-loading" style="display: none;">Generating...</span>
                </button>
            </div>
            <?php endif; ?>

            <div class="xelite-suggestions-list" id="shortcode-suggestions-list">
                <?php if (!empty($suggestions)): ?>
                    <?php foreach ($suggestions as $suggestion): ?>
                    <div class="xelite-suggestion-item">
                        <div class="xelite-suggestion-header">
                            <span class="xelite-suggestion-date"><?php echo esc_html(date('M j, Y', strtotime($suggestion['created_at']))); ?></span>
                            <span class="xelite-suggestion-score"><?php echo esc_html($suggestion['repost_score']); ?>% repost likelihood</span>
                        </div>
                        <div class="xelite-suggestion-content">
                            <p><?php echo esc_html($suggestion['content']); ?></p>
                        </div>
                        <div class="xelite-suggestion-actions">
                            <button type="button" class="xelite-button xelite-button-small xelite-copy-suggestion" data-content="<?php echo esc_attr($suggestion['content']); ?>">
                                Copy
                            </button>
                            <button type="button" class="xelite-button xelite-button-small xelite-button-secondary xelite-edit-suggestion" data-id="<?php echo esc_attr($suggestion['id']); ?>">
                                Edit
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="xelite-no-data">
                        <p>No suggestions generated yet. Click "Generate New Suggestion" to get started.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="xelite-shortcode-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=xelite-repost-dashboard&tab=content-generator')); ?>" class="xelite-button xelite-button-secondary">
                    View Full Generator
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the repost analytics shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_analytics_shortcode($atts) {
        // Check user capabilities
        if (!$this->user_has_access()) {
            return $this->get_access_denied_message();
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'period' => '30', // days
            'show_charts' => 'true',
            'show_stats' => 'true',
            'theme' => 'light'
        ), $atts, 'repost_analytics');

        // Get user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p class="xelite-error">You must be logged in to view analytics.</p>';
        }

        // Get analytics data
        $analytics = $this->get_user_analytics($user_id, intval($atts['period']));

        ob_start();
        ?>
        <div class="xelite-shortcode-container xelite-analytics-shortcode" data-theme="<?php echo esc_attr($atts['theme']); ?>">
            <div class="xelite-shortcode-header">
                <h3>Repost Analytics</h3>
                <p class="xelite-shortcode-subtitle">Performance insights and trends for your content strategy</p>
            </div>

            <?php if ($atts['show_stats'] === 'true'): ?>
            <div class="xelite-analytics-stats">
                <div class="xelite-stat-card">
                    <div class="xelite-stat-value"><?php echo esc_html($analytics['total_patterns']); ?></div>
                    <div class="xelite-stat-label">Patterns Analyzed</div>
                </div>
                <div class="xelite-stat-card">
                    <div class="xelite-stat-value"><?php echo esc_html($analytics['avg_engagement']); ?>%</div>
                    <div class="xelite-stat-label">Avg Engagement</div>
                </div>
                <div class="xelite-stat-card">
                    <div class="xelite-stat-value"><?php echo esc_html($analytics['suggestions_generated']); ?></div>
                    <div class="xelite-stat-label">Suggestions Generated</div>
                </div>
                <div class="xelite-stat-card">
                    <div class="xelite-stat-value"><?php echo esc_html($analytics['top_tone']); ?></div>
                    <div class="xelite-stat-label">Top Performing Tone</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_charts'] === 'true'): ?>
            <div class="xelite-analytics-charts">
                <div class="xelite-chart-section">
                    <h4>Engagement Trends</h4>
                    <div class="xelite-chart-container">
                        <canvas id="shortcode-engagement-chart"></canvas>
                    </div>
                </div>
                <div class="xelite-chart-section">
                    <h4>Content Type Distribution</h4>
                    <div class="xelite-chart-container">
                        <canvas id="shortcode-content-chart"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="xelite-shortcode-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=xelite-repost-dashboard&tab=analytics')); ?>" class="xelite-button xelite-button-secondary">
                    View Full Analytics
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if user has access to shortcode features
     *
     * @return bool
     */
    private function user_has_access() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }

        // Check if user has basic access (can be extended for subscription tiers)
        $user_id = get_current_user_id();
        $has_access = get_user_meta($user_id, 'xelite_repost_access', true);
        
        // For now, grant access to all logged-in users
        // This can be modified to check WooCommerce subscription status
        return true;
    }

    /**
     * Get access denied message
     *
     * @return string
     */
    private function get_access_denied_message() {
        return '<div class="xelite-access-denied">
            <p>You need to be logged in to view this content.</p>
            <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="xelite-button xelite-button-primary">Log In</a>
        </div>';
    }

    /**
     * Get user's repost patterns
     *
     * @param int $user_id User ID
     * @param int $limit Number of patterns to retrieve
     * @param int $period Days to look back
     * @return array
     */
    private function get_user_patterns($user_id, $limit = 10, $period = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xelite_repost_patterns';
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE user_id = %d 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
             ORDER BY timestamp DESC 
             LIMIT %d",
            $user_id,
            $period,
            $limit
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        if (!$results) {
            return array();
        }
        
        // Process and format the data
        $patterns = array();
        foreach ($results as $row) {
            $patterns[] = array(
                'id' => $row['id'],
                'content' => $row['content'],
                'tone' => $row['tone'],
                'length' => strlen($row['content']),
                'engagement_score' => $row['engagement_score'],
                'timestamp' => $row['timestamp']
            );
        }
        
        return $patterns;
    }

    /**
     * Get user's AI suggestions
     *
     * @param int $user_id User ID
     * @param int $limit Number of suggestions to retrieve
     * @return array
     */
    private function get_user_suggestions($user_id, $limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xelite_ai_suggestions';
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id,
            $limit
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        if (!$results) {
            return array();
        }
        
        return $results;
    }

    /**
     * Get user's analytics data
     *
     * @param int $user_id User ID
     * @param int $period Days to look back
     * @return array
     */
    private function get_user_analytics($user_id, $period = 30) {
        global $wpdb;
        
        $patterns_table = $wpdb->prefix . 'xelite_repost_patterns';
        $suggestions_table = $wpdb->prefix . 'xelite_ai_suggestions';
        
        // Get basic stats
        $total_patterns = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$patterns_table} 
             WHERE user_id = %d 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $user_id,
            $period
        ));
        
        $avg_engagement = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(engagement_score) FROM {$patterns_table} 
             WHERE user_id = %d 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $user_id,
            $period
        ));
        
        $suggestions_generated = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$suggestions_table} 
             WHERE user_id = %d 
             AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $user_id,
            $period
        ));
        
        $top_tone = $wpdb->get_var($wpdb->prepare(
            "SELECT tone FROM {$patterns_table} 
             WHERE user_id = %d 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY tone 
             ORDER BY COUNT(*) DESC 
             LIMIT 1",
            $user_id,
            $period
        ));
        
        return array(
            'total_patterns' => intval($total_patterns),
            'avg_engagement' => round(floatval($avg_engagement), 1),
            'suggestions_generated' => intval($suggestions_generated),
            'top_tone' => $top_tone ?: 'N/A'
        );
    }
}

// Initialize the shortcode class
new Xelite_Repost_Intelligence_Shortcodes(); 