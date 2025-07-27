<?php
/**
 * Analytics Data Collection System
 *
 * Collects and stores analytics data for repost patterns and content performance metrics.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Data Collector Class
 *
 * Handles collection, storage, and aggregation of analytics data for the plugin.
 */
class XeliteRepostEngine_Analytics_Collector extends XeliteRepostEngine_Abstract_Base {

    /**
     * Logger instance
     *
     * @var XeliteRepostEngine_Logger|null
     */
    private $logger;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Database $database Database instance.
     * @param XeliteRepostEngine_User_Meta $user_meta User meta instance.
     * @param XeliteRepostEngine_Logger|null $logger Logger instance.
     */
    public function __construct($database, $user_meta, $logger = null) {
        parent::__construct($database, $user_meta);
        $this->logger = $logger;
        
        $this->init();
    }

    /**
     * Initialize the class
     */
    protected function init() {
        $this->init_hooks();
        $this->create_analytics_tables();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Hook into post creation and repost events
        add_action('xelite_post_created', array($this, 'track_post_created'), 10, 2);
        add_action('xelite_repost_detected', array($this, 'track_repost_detected'), 10, 3);
        add_action('xelite_engagement_updated', array($this, 'track_engagement_update'), 10, 3);
        
        // Scheduled data aggregation
        add_action('xelite_aggregate_analytics_daily', array($this, 'aggregate_daily_data'));
        add_action('xelite_aggregate_analytics_weekly', array($this, 'aggregate_weekly_data'));
        add_action('xelite_aggregate_analytics_monthly', array($this, 'aggregate_monthly_data'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        // AJAX handlers
        add_action('wp_ajax_xelite_get_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_xelite_export_analytics', array($this, 'ajax_export_analytics'));
        
        // Activation/deactivation hooks
        register_activation_hook(XELITE_REPOST_ENGINE_PLUGIN_BASENAME, array($this, 'activate_analytics'));
        register_deactivation_hook(XELITE_REPOST_ENGINE_PLUGIN_BASENAME, array($this, 'deactivate_analytics'));
        
        // Schedule aggregation tasks
        if (!wp_next_scheduled('xelite_aggregate_analytics_daily')) {
            wp_schedule_event(time(), 'daily', 'xelite_aggregate_analytics_daily');
        }
        if (!wp_next_scheduled('xelite_aggregate_analytics_weekly')) {
            wp_schedule_event(time(), 'weekly', 'xelite_aggregate_analytics_weekly');
        }
        if (!wp_next_scheduled('xelite_aggregate_analytics_monthly')) {
            wp_schedule_event(time(), 'monthly', 'xelite_aggregate_analytics_monthly');
        }
    }

    /**
     * Create analytics database tables
     */
    public function create_analytics_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Post analytics table
        $post_analytics_table = $wpdb->prefix . 'xelite_post_analytics';
        $sql_post_analytics = "CREATE TABLE $post_analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            content_hash varchar(64) NOT NULL,
            content_length int(11) NOT NULL,
            content_type varchar(20) DEFAULT 'text',
            engagement_score decimal(5,2) DEFAULT 0.00,
            repost_likelihood_score decimal(5,2) DEFAULT 0.00,
            likes_count int(11) DEFAULT 0,
            retweets_count int(11) DEFAULT 0,
            replies_count int(11) DEFAULT 0,
            views_count int(11) DEFAULT 0,
            reposts_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            KEY user_id (user_id),
            KEY content_hash (content_hash),
            KEY engagement_score (engagement_score),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Repost analytics table
        $repost_analytics_table = $wpdb->prefix . 'xelite_repost_analytics';
        $sql_repost_analytics = "CREATE TABLE $repost_analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            original_post_id varchar(50) NOT NULL,
            repost_post_id varchar(50) NOT NULL,
            repost_user_id bigint(20) NOT NULL,
            repost_user_followers int(11) DEFAULT 0,
            repost_user_verified tinyint(1) DEFAULT 0,
            repost_engagement_score decimal(5,2) DEFAULT 0.00,
            repost_likes int(11) DEFAULT 0,
            repost_retweets int(11) DEFAULT 0,
            repost_replies int(11) DEFAULT 0,
            repost_views int(11) DEFAULT 0,
            repost_created_at datetime NOT NULL,
            detected_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY repost_post_id (repost_post_id),
            KEY original_post_id (original_post_id),
            KEY repost_user_id (repost_user_id),
            KEY repost_engagement_score (repost_engagement_score),
            KEY repost_created_at (repost_created_at)
        ) $charset_collate;";
        
        // Pattern analytics table
        $pattern_analytics_table = $wpdb->prefix . 'xelite_pattern_analytics';
        $sql_pattern_analytics = "CREATE TABLE $pattern_analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pattern_type varchar(50) NOT NULL,
            pattern_value text NOT NULL,
            success_count int(11) DEFAULT 0,
            total_count int(11) DEFAULT 0,
            success_rate decimal(5,2) DEFAULT 0.00,
            avg_engagement decimal(5,2) DEFAULT 0.00,
            avg_repost_likelihood decimal(5,2) DEFAULT 0.00,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pattern_type_value (pattern_type, pattern_value(255)),
            KEY pattern_type (pattern_type),
            KEY success_rate (success_rate),
            KEY avg_engagement (avg_engagement)
        ) $charset_collate;";
        
        // Aggregated analytics table
        $aggregated_analytics_table = $wpdb->prefix . 'xelite_aggregated_analytics';
        $sql_aggregated_analytics = "CREATE TABLE $aggregated_analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            period_type varchar(20) NOT NULL,
            period_start datetime NOT NULL,
            period_end datetime NOT NULL,
            total_posts int(11) DEFAULT 0,
            total_engagement int(11) DEFAULT 0,
            total_reposts int(11) DEFAULT 0,
            avg_engagement_rate decimal(5,2) DEFAULT 0.00,
            avg_repost_rate decimal(5,2) DEFAULT 0.00,
            best_performing_post_id varchar(50) DEFAULT NULL,
            best_engagement_score decimal(5,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_period (user_id, period_type, period_start),
            KEY user_id (user_id),
            KEY period_type (period_type),
            KEY period_start (period_start),
            KEY avg_engagement_rate (avg_engagement_rate)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_post_analytics);
        dbDelta($sql_repost_analytics);
        dbDelta($sql_pattern_analytics);
        dbDelta($sql_aggregated_analytics);
        
        $this->log('info', 'Analytics tables created/updated');
    }

    /**
     * Track post creation
     *
     * @param string $post_id Post ID.
     * @param array $post_data Post data.
     */
    public function track_post_created($post_id, $post_data) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            $this->log('warning', 'Cannot track post creation: no user ID');
            return;
        }
        
        $content_hash = hash('sha256', $post_data['content']);
        $content_length = strlen($post_data['content']);
        $content_type = $this->determine_content_type($post_data);
        
        $data = array(
            'post_id' => $post_id,
            'user_id' => $user_id,
            'content_hash' => $content_hash,
            'content_length' => $content_length,
            'content_type' => $content_type,
            'engagement_score' => 0.00,
            'repost_likelihood_score' => $this->calculate_repost_likelihood($post_data),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->database->insert('xelite_post_analytics', $data);
        
        if ($result) {
            $this->log('info', "Post analytics tracked for post {$post_id}");
            $this->update_pattern_analytics($post_data, $post_id);
        } else {
            $this->log('error', "Failed to track post analytics for post {$post_id}");
        }
    }

    /**
     * Track repost detection
     *
     * @param string $original_post_id Original post ID.
     * @param string $repost_post_id Repost post ID.
     * @param array $repost_data Repost data.
     */
    public function track_repost_detected($original_post_id, $repost_post_id, $repost_data) {
        $data = array(
            'original_post_id' => $original_post_id,
            'repost_post_id' => $repost_post_id,
            'repost_user_id' => $repost_data['user_id'],
            'repost_user_followers' => $repost_data['followers_count'],
            'repost_user_verified' => $repost_data['verified'] ? 1 : 0,
            'repost_engagement_score' => $this->calculate_engagement_score($repost_data),
            'repost_likes' => $repost_data['likes_count'],
            'repost_retweets' => $repost_data['retweets_count'],
            'repost_replies' => $repost_data['replies_count'],
            'repost_views' => $repost_data['views_count'],
            'repost_created_at' => $repost_data['created_at'],
            'detected_at' => current_time('mysql')
        );
        
        $result = $this->database->insert('xelite_repost_analytics', $data);
        
        if ($result) {
            $this->log('info', "Repost analytics tracked: {$original_post_id} -> {$repost_post_id}");
            $this->update_post_analytics($original_post_id, $repost_data);
        } else {
            $this->log('error', "Failed to track repost analytics: {$original_post_id} -> {$repost_post_id}");
        }
    }

    /**
     * Track engagement updates
     *
     * @param string $post_id Post ID.
     * @param array $engagement_data Engagement data.
     * @param string $update_type Update type.
     */
    public function track_engagement_update($post_id, $engagement_data, $update_type) {
        $data = array(
            'engagement_score' => $this->calculate_engagement_score($engagement_data),
            'likes_count' => $engagement_data['likes_count'],
            'retweets_count' => $engagement_data['retweets_count'],
            'replies_count' => $engagement_data['replies_count'],
            'views_count' => $engagement_data['views_count'],
            'reposts_count' => $engagement_data['reposts_count'],
            'updated_at' => current_time('mysql')
        );
        
        $result = $this->database->update(
            'xelite_post_analytics',
            $data,
            array('post_id' => $post_id)
        );
        
        if ($result) {
            $this->log('info', "Engagement analytics updated for post {$post_id}");
        } else {
            $this->log('error', "Failed to update engagement analytics for post {$post_id}");
        }
    }

    /**
     * Aggregate daily analytics data
     */
    public function aggregate_daily_data() {
        $this->aggregate_data('daily');
    }

    /**
     * Aggregate weekly analytics data
     */
    public function aggregate_weekly_data() {
        $this->aggregate_data('weekly');
    }

    /**
     * Aggregate monthly analytics data
     */
    public function aggregate_monthly_data() {
        $this->aggregate_data('monthly');
    }

    /**
     * Aggregate analytics data for a specific period
     *
     * @param string $period_type Period type (daily, weekly, monthly).
     */
    private function aggregate_data($period_type) {
        global $wpdb;
        
        $period_start = $this->get_period_start($period_type);
        $period_end = $this->get_period_end($period_type);
        
        // Get all users with analytics data
        $users = $wpdb->get_col("
            SELECT DISTINCT user_id 
            FROM {$wpdb->prefix}xelite_post_analytics 
            WHERE created_at BETWEEN '{$period_start}' AND '{$period_end}'
        ");
        
        foreach ($users as $user_id) {
            $this->aggregate_user_data($user_id, $period_type, $period_start, $period_end);
        }
        
        $this->log('info', "Aggregated {$period_type} analytics data for " . count($users) . " users");
    }

    /**
     * Aggregate data for a specific user and period
     *
     * @param int $user_id User ID.
     * @param string $period_type Period type.
     * @param string $period_start Period start.
     * @param string $period_end Period end.
     */
    private function aggregate_user_data($user_id, $period_type, $period_start, $period_end) {
        global $wpdb;
        
        // Get post analytics for the period
        $post_analytics = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}xelite_post_analytics 
            WHERE user_id = %d 
            AND created_at BETWEEN %s AND %s
        ", $user_id, $period_start, $period_end));
        
        if (empty($post_analytics)) {
            return;
        }
        
        $total_posts = count($post_analytics);
        $total_engagement = array_sum(array_column($post_analytics, 'engagement_score'));
        $total_reposts = array_sum(array_column($post_analytics, 'reposts_count'));
        
        $avg_engagement_rate = $total_posts > 0 ? $total_engagement / $total_posts : 0;
        $avg_repost_rate = $total_posts > 0 ? $total_reposts / $total_posts : 0;
        
        // Find best performing post
        $best_post = null;
        $best_score = 0;
        foreach ($post_analytics as $post) {
            if ($post->engagement_score > $best_score) {
                $best_score = $post->engagement_score;
                $best_post = $post;
            }
        }
        
        $data = array(
            'user_id' => $user_id,
            'period_type' => $period_type,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'total_posts' => $total_posts,
            'total_engagement' => $total_engagement,
            'total_reposts' => $total_reposts,
            'avg_engagement_rate' => round($avg_engagement_rate, 2),
            'avg_repost_rate' => round($avg_repost_rate, 2),
            'best_performing_post_id' => $best_post ? $best_post->post_id : null,
            'best_engagement_score' => round($best_score, 2),
            'created_at' => current_time('mysql')
        );
        
        // Insert or update aggregated data
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}xelite_aggregated_analytics 
            WHERE user_id = %d AND period_type = %s AND period_start = %s
        ", $user_id, $period_type, $period_start));
        
        if ($existing) {
            $this->database->update(
                'xelite_aggregated_analytics',
                $data,
                array('id' => $existing->id)
            );
        } else {
            $this->database->insert('xelite_aggregated_analytics', $data);
        }
    }

    /**
     * Update pattern analytics
     *
     * @param array $post_data Post data.
     * @param string $post_id Post ID.
     */
    private function update_pattern_analytics($post_data, $post_id) {
        $patterns = $this->extract_patterns($post_data);
        
        foreach ($patterns as $pattern_type => $pattern_value) {
            $this->update_pattern_statistics($pattern_type, $pattern_value, $post_id);
        }
    }

    /**
     * Update pattern statistics
     *
     * @param string $pattern_type Pattern type.
     * @param string $pattern_value Pattern value.
     * @param string $post_id Post ID.
     */
    private function update_pattern_statistics($pattern_type, $pattern_value, $post_id) {
        global $wpdb;
        
        // Get existing pattern record
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}xelite_pattern_analytics 
            WHERE pattern_type = %s AND pattern_value = %s
        ", $pattern_type, $pattern_value));
        
        if ($existing) {
            // Update existing pattern
            $total_count = $existing->total_count + 1;
            $success_count = $existing->success_count; // Will be updated when engagement data comes in
            
            $data = array(
                'total_count' => $total_count,
                'last_updated' => current_time('mysql')
            );
            
            $this->database->update(
                'xelite_pattern_analytics',
                $data,
                array('id' => $existing->id)
            );
        } else {
            // Create new pattern record
            $data = array(
                'pattern_type' => $pattern_type,
                'pattern_value' => $pattern_value,
                'success_count' => 0,
                'total_count' => 1,
                'success_rate' => 0.00,
                'avg_engagement' => 0.00,
                'avg_repost_likelihood' => 0.00,
                'last_updated' => current_time('mysql')
            );
            
            $this->database->insert('xelite_pattern_analytics', $data);
        }
    }

    /**
     * Extract patterns from post data
     *
     * @param array $post_data Post data.
     * @return array Patterns.
     */
    private function extract_patterns($post_data) {
        $patterns = array();
        
        // Content length pattern
        $content_length = strlen($post_data['content']);
        if ($content_length <= 50) {
            $patterns['content_length'] = 'short';
        } elseif ($content_length <= 150) {
            $patterns['content_length'] = 'medium';
        } else {
            $patterns['content_length'] = 'long';
        }
        
        // Content type pattern
        $patterns['content_type'] = $this->determine_content_type($post_data);
        
        // Hashtag pattern
        if (preg_match_all('/#\w+/', $post_data['content'], $matches)) {
            $patterns['hashtag_count'] = count($matches[0]);
        } else {
            $patterns['hashtag_count'] = 0;
        }
        
        // Mention pattern
        if (preg_match_all('/@\w+/', $post_data['content'], $matches)) {
            $patterns['mention_count'] = count($matches[0]);
        } else {
            $patterns['mention_count'] = 0;
        }
        
        // Question pattern
        if (strpos($post_data['content'], '?') !== false) {
            $patterns['contains_question'] = 'yes';
        } else {
            $patterns['contains_question'] = 'no';
        }
        
        return $patterns;
    }

    /**
     * Determine content type
     *
     * @param array $post_data Post data.
     * @return string Content type.
     */
    private function determine_content_type($post_data) {
        if (!empty($post_data['media_ids']) && count($post_data['media_ids']) > 0) {
            return 'media';
        }
        
        if (strlen($post_data['content']) > 200) {
            return 'long_text';
        }
        
        return 'text';
    }

    /**
     * Calculate engagement score
     *
     * @param array $engagement_data Engagement data.
     * @return float Engagement score.
     */
    private function calculate_engagement_score($engagement_data) {
        $likes = $engagement_data['likes_count'] ?? 0;
        $retweets = $engagement_data['retweets_count'] ?? 0;
        $replies = $engagement_data['replies_count'] ?? 0;
        $views = $engagement_data['views_count'] ?? 1; // Avoid division by zero
        
        // Weighted engagement calculation
        $engagement = ($likes * 1) + ($retweets * 2) + ($replies * 3);
        $engagement_rate = $views > 0 ? ($engagement / $views) * 100 : 0;
        
        return round($engagement_rate, 2);
    }

    /**
     * Calculate repost likelihood score
     *
     * @param array $post_data Post data.
     * @return float Repost likelihood score.
     */
    private function calculate_repost_likelihood($post_data) {
        $score = 0.0;
        
        // Content length factor
        $content_length = strlen($post_data['content']);
        if ($content_length >= 100 && $content_length <= 200) {
            $score += 0.3; // Optimal length
        } elseif ($content_length >= 50 && $content_length <= 250) {
            $score += 0.2; // Good length
        }
        
        // Hashtag factor
        if (preg_match_all('/#\w+/', $post_data['content'], $matches)) {
            $hashtag_count = count($matches[0]);
            if ($hashtag_count >= 1 && $hashtag_count <= 3) {
                $score += 0.2; // Optimal hashtag count
            } elseif ($hashtag_count > 0) {
                $score += 0.1; // Some hashtags
            }
        }
        
        // Question factor
        if (strpos($post_data['content'], '?') !== false) {
            $score += 0.15; // Questions tend to get more engagement
        }
        
        // Mention factor
        if (preg_match_all('/@\w+/', $post_data['content'], $matches)) {
            $mention_count = count($matches[0]);
            if ($mention_count >= 1 && $mention_count <= 2) {
                $score += 0.15; // Strategic mentions
            }
        }
        
        // Media factor
        if (!empty($post_data['media_ids']) && count($post_data['media_ids']) > 0) {
            $score += 0.2; // Media content gets more engagement
        }
        
        return round(min($score, 1.0), 2);
    }

    /**
     * Get period start date
     *
     * @param string $period_type Period type.
     * @return string Period start date.
     */
    private function get_period_start($period_type) {
        switch ($period_type) {
            case 'daily':
                return date('Y-m-d 00:00:00', strtotime('-1 day'));
            case 'weekly':
                return date('Y-m-d 00:00:00', strtotime('-1 week'));
            case 'monthly':
                return date('Y-m-d 00:00:00', strtotime('-1 month'));
            default:
                return date('Y-m-d 00:00:00', strtotime('-1 day'));
        }
    }

    /**
     * Get period end date
     *
     * @param string $period_type Period type.
     * @return string Period end date.
     */
    private function get_period_end($period_type) {
        switch ($period_type) {
            case 'daily':
                return date('Y-m-d 23:59:59', strtotime('-1 day'));
            case 'weekly':
                return date('Y-m-d 23:59:59', strtotime('-1 week'));
            case 'monthly':
                return date('Y-m-d 23:59:59', strtotime('-1 month'));
            default:
                return date('Y-m-d 23:59:59', strtotime('-1 day'));
        }
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('xelite_analytics_settings', 'xelite_analytics_enabled');
        register_setting('xelite_analytics_settings', 'xelite_analytics_retention_days');
        register_setting('xelite_analytics_settings', 'xelite_analytics_aggregation_enabled');
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'xelite-repost-engine',
            'Analytics Settings',
            'Analytics',
            'manage_options',
            'xelite-analytics-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Analytics Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('xelite_analytics_settings');
                do_settings_sections('xelite_analytics_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Analytics</th>
                        <td>
                            <input type="checkbox" name="xelite_analytics_enabled" value="1" 
                                <?php checked(1, get_option('xelite_analytics_enabled', 1)); ?> />
                            <p class="description">Enable analytics data collection and aggregation.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Data Retention (Days)</th>
                        <td>
                            <input type="number" name="xelite_analytics_retention_days" value="<?php echo esc_attr(get_option('xelite_analytics_retention_days', 365)); ?>" min="30" max="1095" />
                            <p class="description">How long to keep analytics data (30-1095 days).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Aggregation</th>
                        <td>
                            <input type="checkbox" name="xelite_analytics_aggregation_enabled" value="1" 
                                <?php checked(1, get_option('xelite_analytics_aggregation_enabled', 1)); ?> />
                            <p class="description">Enable automatic data aggregation for performance optimization.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Activate analytics
     */
    public function activate_analytics() {
        $this->create_analytics_tables();
        
        // Set default options
        add_option('xelite_analytics_enabled', 1);
        add_option('xelite_analytics_retention_days', 365);
        add_option('xelite_analytics_aggregation_enabled', 1);
        
        $this->log('info', 'Analytics system activated');
    }

    /**
     * Deactivate analytics
     */
    public function deactivate_analytics() {
        // Clear scheduled events
        wp_clear_scheduled_hook('xelite_aggregate_analytics_daily');
        wp_clear_scheduled_hook('xelite_aggregate_analytics_weekly');
        wp_clear_scheduled_hook('xelite_aggregate_analytics_monthly');
        
        $this->log('info', 'Analytics system deactivated');
    }

    /**
     * Log message
     *
     * @param string $level Log level.
     * @param string $message Log message.
     * @param array $context Log context.
     */
    private function log($level, $message, $context = array()) {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * AJAX handler for getting analytics data
     */
    public function ajax_get_analytics_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user permissions
        if (!current_user_can('read')) {
            wp_die('Insufficient permissions');
        }

        $user_id = get_current_user_id();
        $date_range = sanitize_text_field($_POST['date_range']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $content_type = sanitize_text_field($_POST['content_type']);
        $engagement_range = sanitize_text_field($_POST['engagement_range']);

        try {
            $data = $this->get_dashboard_data($user_id, $date_range, $start_date, $end_date, $content_type, $engagement_range);
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * AJAX handler for exporting analytics data
     */
    public function ajax_export_analytics() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user permissions
        if (!current_user_can('read')) {
            wp_die('Insufficient permissions');
        }

        $user_id = get_current_user_id();
        $format = sanitize_text_field($_POST['format']);
        $date_range = sanitize_text_field($_POST['date_range']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $content_type = sanitize_text_field($_POST['content_type']);
        $engagement_range = sanitize_text_field($_POST['engagement_range']);

        try {
            $export_data = $this->export_analytics_data($user_id, $format, $date_range, $start_date, $end_date, $content_type, $engagement_range);
            wp_send_json_success($export_data);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Get dashboard data for analytics
     *
     * @param int $user_id User ID.
     * @param string $date_range Date range.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array Dashboard data.
     */
    public function get_dashboard_data($user_id, $date_range = '30', $start_date = '', $end_date = '', $content_type = '', $engagement_range = '') {
        // Calculate date range
        if ($date_range === 'custom' && $start_date && $end_date) {
            $start = $start_date;
            $end = $end_date;
        } else {
            $days = intval($date_range);
            $end = date('Y-m-d');
            $start = date('Y-m-d', strtotime("-{$days} days"));
        }

        $data = array(
            'metrics' => $this->get_metrics_overview($user_id, $start, $end, $content_type, $engagement_range),
            'charts' => $this->get_chart_data($user_id, $start, $end, $content_type, $engagement_range),
            'top_content' => $this->get_top_content($user_id, $start, $end, $content_type, $engagement_range),
            'insights' => $this->get_ai_insights($user_id, $start, $end, $content_type, $engagement_range)
        );

        return $data;
    }

    /**
     * Get metrics overview
     *
     * @param int $user_id User ID.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array Metrics data.
     */
    private function get_metrics_overview($user_id, $start_date, $end_date, $content_type, $engagement_range) {
        global $wpdb;

        $where_conditions = array('user_id = %d');
        $where_values = array($user_id);

        if ($content_type) {
            $where_conditions[] = 'content_type = %s';
            $where_values[] = $content_type;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        $where_clause .= " AND created_at BETWEEN %s AND %s";
        $where_values[] = $start_date . ' 00:00:00';
        $where_values[] = $end_date . ' 23:59:59';

        // Get current period data
        $current_query = $wpdb->prepare("
            SELECT 
                COUNT(*) as total_reposts,
                AVG(engagement_score) as avg_engagement_rate,
                AVG(repost_likelihood_score) as avg_repost_likelihood
            FROM {$wpdb->prefix}xelite_post_analytics 
            {$where_clause}
        ", $where_values);

        $current_data = $wpdb->get_row($current_query);

        // Get previous period data for comparison
        $days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
        $prev_start = date('Y-m-d', strtotime($start_date . " -{$days_diff} days"));
        $prev_end = date('Y-m-d', strtotime($start_date . ' -1 day'));

        $prev_where_values = array($user_id);
        if ($content_type) {
            $prev_where_values[] = $content_type;
        }
        $prev_where_values[] = $prev_start . ' 00:00:00';
        $prev_where_values[] = $prev_end . ' 23:59:59';

        $prev_query = $wpdb->prepare("
            SELECT 
                COUNT(*) as total_reposts,
                AVG(engagement_score) as avg_engagement_rate
            FROM {$wpdb->prefix}xelite_post_analytics 
            {$where_clause}
        ", $prev_where_values);

        $prev_data = $wpdb->get_row($prev_query);

        // Calculate changes
        $reposts_change = $prev_data->total_reposts > 0 ? 
            (($current_data->total_reposts - $prev_data->total_reposts) / $prev_data->total_reposts) * 100 : 0;
        
        $engagement_change = $prev_data->avg_engagement_rate > 0 ? 
            (($current_data->avg_engagement_rate - $prev_data->avg_engagement_rate) / $prev_data->avg_engagement_rate) * 100 : 0;

        // Get best posting time
        $best_time_query = $wpdb->prepare("
            SELECT 
                HOUR(created_at) as hour,
                AVG(engagement_score) as avg_engagement
            FROM {$wpdb->prefix}xelite_post_analytics 
            {$where_clause}
            GROUP BY HOUR(created_at)
            ORDER BY avg_engagement DESC
            LIMIT 1
        ", $where_values);

        $best_time_data = $wpdb->get_row($best_time_query);
        $best_time = $best_time_data ? date('g:i A', strtotime("2000-01-01 {$best_time_data->hour}:00:00")) : 'N/A';

        // Get top content type
        $top_type_query = $wpdb->prepare("
            SELECT 
                content_type,
                COUNT(*) as count
            FROM {$wpdb->prefix}xelite_post_analytics 
            {$where_clause}
            GROUP BY content_type
            ORDER BY count DESC
            LIMIT 1
        ", $where_values);

        $top_type_data = $wpdb->get_row($top_type_query);
        $top_content_type = $top_type_data ? ucfirst($top_type_data->content_type) : 'N/A';

        return array(
            'total_reposts' => intval($current_data->total_reposts),
            'avg_engagement_rate' => round($current_data->avg_engagement_rate, 2),
            'avg_repost_likelihood' => round($current_data->avg_repost_likelihood, 2),
            'best_posting_time' => $best_time,
            'top_content_type' => $top_content_type,
            'reposts_change' => round($reposts_change, 1),
            'engagement_change' => round($engagement_change, 1)
        );
    }

    /**
     * Get chart data
     *
     * @param int $user_id User ID.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array Chart data.
     */
    private function get_chart_data($user_id, $start_date, $end_date, $content_type, $engagement_range) {
        return array(
            'engagement_trends' => $this->get_engagement_trends_data($user_id, $start_date, $end_date, $content_type, $engagement_range),
            'content_types' => $this->get_content_type_data($user_id, $start_date, $end_date, $content_type, $engagement_range),
            'posting_times' => $this->get_posting_time_data($user_id, $start_date, $end_date, $content_type, $engagement_range),
            'hashtags' => $this->get_hashtag_data($user_id, $start_date, $end_date, $content_type, $engagement_range)
        );
    }

    /**
     * Get engagement trends data
     *
     * @param int $user_id User ID.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array Engagement trends data.
     */
    private function get_engagement_trends_data($user_id, $start_date, $end_date, $content_type, $engagement_range) {
        global $wpdb;

        $where_conditions = array('user_id = %d');
        $where_values = array($user_id);

        if ($content_type) {
            $where_conditions[] = 'content_type = %s';
            $where_values[] = $content_type;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        $where_clause .= " AND created_at BETWEEN %s AND %s";
        $where_values[] = $start_date . ' 00:00:00';
        $where_values[] = $end_date . ' 23:59:59';

        $query = $wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                AVG(engagement_score) as avg_engagement,
                COUNT(*) as total_posts
            FROM {$wpdb->prefix}xelite_post_analytics 
            {$where_clause}
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $where_values);

        $results = $wpdb->get_results($query);

        $labels = array();
        $engagement = array();
        $reposts = array();

        foreach ($results as $row) {
            $labels[] = date('M j', strtotime($row->date));
            $engagement[] = round($row->avg_engagement, 2);
            $reposts[] = $row->total_posts;
        }

        return array(
            'labels' => $labels,
            'engagement' => $engagement,
            'reposts' => $reposts
        );
    }

    /**
     * Get content type data
     *
     * @param int $user_id User ID.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array Content type data.
     */
    private function get_content_type_data($user_id, $start_date, $end_date, $content_type, $engagement_range) {
        global $wpdb;

        $where_conditions = array('user_id = %d');
        $where_values = array($user_id);

        if ($content_type) {
            $where_conditions[] = 'content_type = %s';
            $where_values[] = $content_type;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        $where_clause .= " AND created_at BETWEEN %s AND %s";
        $where_values[] = $start_date . ' 00:00:00';
        $where_values[] = $end_date . ' 23:59:59';

        $query = $wpdb->prepare("
            SELECT 
                content_type,
                COUNT(*) as count,
                AVG(engagement_score) as avg_engagement
            FROM {$wpdb->prefix}xelite_post_analytics 
            {$where_clause}
            GROUP BY content_type
            ORDER BY count DESC
        ", $where_values);

        $results = $wpdb->get_results($query);

        $labels = array();
        $data = array();

        foreach ($results as $row) {
            $labels[] = ucfirst($row->content_type);
            $data[] = $row->count;
        }

        return array(
            'labels' => $labels,
            'data' => $data
        );
    }

    /**
     * Get posting time data
     *
     * @param int $user_id User ID.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array Posting time data.
     */
    private function get_posting_time_data($user_id, $start_date, $end_date, $content_type, $engagement_range) {
        global $wpdb;

        $where_conditions = array('user_id = %d');
        $where_values = array($user_id);

        if ($content_type) {
            $where_conditions[] = 'content_type = %s';
            $where_values[] = $content_type;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        $where_clause .= " AND created_at BETWEEN %s AND %s";
        $where_values[] = $start_date . ' 00:00:00';
        $where_values[] = $end_date . ' 23:59:59';

        $query = $wpdb->prepare("
            SELECT 
                HOUR(created_at) as hour,
                AVG(engagement_score) as avg_engagement
            FROM {$wpdb->prefix}xelite_post_analytics 
            {$where_clause}
            GROUP BY HOUR(created_at)
            ORDER BY hour ASC
        ", $where_values);

        $results = $wpdb->get_results($query);

        // Initialize data array with 24 hours
        $data = array_fill(0, 24, 0);

        foreach ($results as $row) {
            $data[$row->hour] = round($row->avg_engagement, 2);
        }

        // Group into 8 time periods
        $grouped_data = array(
            $data[0] + $data[1] + $data[2], // 12 AM - 3 AM
            $data[3] + $data[4] + $data[5], // 3 AM - 6 AM
            $data[6] + $data[7] + $data[8], // 6 AM - 9 AM
            $data[9] + $data[10] + $data[11], // 9 AM - 12 PM
            $data[12] + $data[13] + $data[14], // 12 PM - 3 PM
            $data[15] + $data[16] + $data[17], // 3 PM - 6 PM
            $data[18] + $data[19] + $data[20], // 6 PM - 9 PM
            $data[21] + $data[22] + $data[23]  // 9 PM - 12 AM
        );

        return array(
            'data' => $grouped_data
        );
    }

    /**
     * Get hashtag data
     *
     * @param int $user_id User ID.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array Hashtag data.
     */
    private function get_hashtag_data($user_id, $start_date, $end_date, $content_type, $engagement_range) {
        global $wpdb;

        $where_conditions = array('user_id = %d');
        $where_values = array($user_id);

        if ($content_type) {
            $where_conditions[] = 'content_type = %s';
            $where_values[] = $content_type;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        $where_clause .= " AND created_at BETWEEN %s AND %s";
        $where_values[] = $start_date . ' 00:00:00';
        $where_values[] = $end_date . ' 23:59:59';

        // This is a simplified version - in a real implementation, you'd extract hashtags from content
        $query = $wpdb->prepare("
            SELECT 
                'general' as hashtag,
                AVG(engagement_score) as avg_engagement,
                COUNT(*) as usage_count
            FROM {$wpdb->prefix}xelite_post_analytics 
            {$where_clause}
            GROUP BY 'general'
            ORDER BY avg_engagement DESC
            LIMIT 10
        ", $where_values);

        $results = $wpdb->get_results($query);

        $labels = array();
        $data = array();

        foreach ($results as $row) {
            $labels[] = $row->hashtag;
            $data[] = round($row->avg_engagement, 2);
        }

        return array(
            'labels' => $labels,
            'data' => $data
        );
    }

    /**
     * Get top content
     *
     * @param int $user_id User ID.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array Top content data.
     */
    private function get_top_content($user_id, $start_date, $end_date, $content_type, $engagement_range) {
        global $wpdb;

        $where_conditions = array('user_id = %d');
        $where_values = array($user_id);

        if ($content_type) {
            $where_conditions[] = 'content_type = %s';
            $where_values[] = $content_type;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        $where_clause .= " AND created_at BETWEEN %s AND %s";
        $where_values[] = $start_date . ' 00:00:00';
        $where_values[] = $end_date . ' 23:59:59';

        $query = $wpdb->prepare("
            SELECT 
                post_id,
                content_type,
                engagement_score,
                likes_count,
                retweets_count,
                replies_count,
                views_count,
                created_at
            FROM {$wpdb->prefix}xelite_post_analytics 
            {$where_clause}
            ORDER BY engagement_score DESC
            LIMIT 10
        ", $where_values);

        $results = $wpdb->get_results($query);

        $content = array();
        foreach ($results as $row) {
            $content[] = array(
                'post_id' => $row->post_id,
                'content_type' => ucfirst($row->content_type),
                'content' => 'Sample content for post ' . $row->post_id, // In real implementation, get actual content
                'engagement_rate' => round($row->engagement_score, 2),
                'likes' => $row->likes_count,
                'retweets' => $row->retweets_count,
                'replies' => $row->replies_count,
                'views' => $row->views_count,
                'posted_date' => date('M j, Y', strtotime($row->created_at))
            );
        }

        return $content;
    }

    /**
     * Get AI insights
     *
     * @param int $user_id User ID.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array AI insights data.
     */
    private function get_ai_insights($user_id, $start_date, $end_date, $content_type, $engagement_range) {
        // This would integrate with OpenAI to generate insights
        // For now, return sample insights
        return array(
            array(
                'title' => 'Engagement Trend Analysis',
                'description' => 'Your engagement rate has increased by 15% over the last 30 days. This suggests your content strategy is working well.',
                'icon' => 'dashicons-chart-line',
                'recommendation' => 'Continue posting during peak hours (3-6 PM) when engagement is highest.'
            ),
            array(
                'title' => 'Content Type Performance',
                'description' => 'Video content is performing 25% better than text posts. Consider increasing video content frequency.',
                'icon' => 'dashicons-video-alt3',
                'recommendation' => 'Aim for 40% video content in your posting schedule.'
            ),
            array(
                'title' => 'Hashtag Strategy',
                'description' => 'Posts with 3-5 hashtags are performing best. Avoid over-hashtagging.',
                'icon' => 'dashicons-tag',
                'recommendation' => 'Use 3-5 relevant hashtags per post for optimal reach.'
            )
        );
    }

    /**
     * Export analytics data
     *
     * @param int $user_id User ID.
     * @param string $format Export format.
     * @param string $date_range Date range.
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @param string $content_type Content type filter.
     * @param string $engagement_range Engagement range filter.
     * @return array Export data.
     */
    private function export_analytics_data($user_id, $format, $date_range, $start_date, $end_date, $content_type, $engagement_range) {
        $data = $this->get_dashboard_data($user_id, $date_range, $start_date, $end_date, $content_type, $engagement_range);
        
        $filename = 'xelite-analytics-' . date('Y-m-d-H-i-s') . '.' . $format;
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/xelite-exports/' . $filename;
        
        // Ensure export directory exists
        wp_mkdir_p($upload_dir['basedir'] . '/xelite-exports/');
        
        switch ($format) {
            case 'csv':
                $this->export_to_csv($data, $file_path);
                break;
            case 'json':
                $this->export_to_json($data, $file_path);
                break;
            case 'pdf':
                $file_path = $this->export_to_pdf($data, $file_path);
                break;
            default:
                throw new Exception('Unsupported export format');
        }
        
        return array(
            'download_url' => $upload_dir['baseurl'] . '/xelite-exports/' . $filename,
            'file_path' => $file_path
        );
    }

    /**
     * Export data to CSV
     *
     * @param array $data Data to export.
     * @param string $file_path File path.
     */
    private function export_to_csv($data, $file_path) {
        $handle = fopen($file_path, 'w');
        
        // Write metrics
        fputcsv($handle, array('Metric', 'Value'));
        foreach ($data['metrics'] as $key => $value) {
            fputcsv($handle, array($key, $value));
        }
        
        // Write top content
        fputcsv($handle, array(''));
        fputcsv($handle, array('Top Content'));
        fputcsv($handle, array('Post ID', 'Content Type', 'Engagement Rate', 'Likes', 'Retweets', 'Replies', 'Posted Date'));
        foreach ($data['top_content'] as $content) {
            fputcsv($handle, array(
                $content['post_id'],
                $content['content_type'],
                $content['engagement_rate'],
                $content['likes'],
                $content['retweets'],
                $content['replies'],
                $content['posted_date']
            ));
        }
        
        fclose($handle);
    }

    /**
     * Export data to JSON
     *
     * @param array $data Data to export.
     * @param string $file_path File path.
     */
    private function export_to_json($data, $file_path) {
        file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Export data to PDF
     *
     * @param array $data Data to export.
     * @param string $file_path File path.
     * @return string Updated file path.
     */
    private function export_to_pdf($data, $file_path) {
        // This would integrate with a PDF library like TCPDF or mPDF
        // For now, create a simple HTML file that can be converted to PDF
        $html = $this->generate_pdf_html($data);
        $html_file = str_replace('.pdf', '.html', $file_path);
        file_put_contents($html_file, $html);
        
        return $html_file;
    }

    /**
     * Generate HTML for PDF export
     *
     * @param array $data Data to include.
     * @return string HTML content.
     */
    private function generate_pdf_html($data) {
        $html = '<!DOCTYPE html><html><head><title>Analytics Report</title></head><body>';
        $html .= '<h1>Analytics Report</h1>';
        $html .= '<h2>Metrics Overview</h2>';
        $html .= '<table border="1"><tr><th>Metric</th><th>Value</th></tr>';
        
        foreach ($data['metrics'] as $key => $value) {
            $html .= '<tr><td>' . esc_html($key) . '</td><td>' . esc_html($value) . '</td></tr>';
        }
        
        $html .= '</table></body></html>';
        return $html;
    }

    /**
     * Get content types for filter
     *
     * @return array Content types.
     */
    public function get_content_types() {
        return array(
            '' => 'All Types',
            'text' => 'Text',
            'image' => 'Image',
            'video' => 'Video',
            'link' => 'Link',
            'poll' => 'Poll'
        );
    }

    /**
     * Get engagement ranges for filter
     *
     * @return array Engagement ranges.
     */
    public function get_engagement_ranges() {
        return array(
            '' => 'All Engagement',
            'low' => 'Low (0-2%)',
            'medium' => 'Medium (2-5%)',
            'high' => 'High (5%+)'
        );
    }
} 