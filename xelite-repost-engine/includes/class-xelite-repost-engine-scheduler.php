<?php
/**
 * X Posting Scheduler Class
 *
 * Handles scheduling of X posts using WordPress cron jobs, including
 * timezone management, queue management, and admin interface.
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * X Posting Scheduler Class
 *
 * Manages scheduling of X posts with advanced features including
 * timezone handling, queue management, and WordPress cron integration.
 */
class XeliteRepostEngine_Scheduler extends XeliteRepostEngine_Abstract_Base {

    /**
     * X Poster instance
     *
     * @var XeliteRepostEngine_X_Poster
     */
    private $x_poster;

    /**
     * WooCommerce instance
     *
     * @var XeliteRepostEngine_WooCommerce|null
     */
    private $woocommerce;

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
     * @param XeliteRepostEngine_X_Poster $x_poster X Poster instance.
     * @param XeliteRepostEngine_Logger|null $logger Logger instance.
     * @param XeliteRepostEngine_WooCommerce|null $woocommerce WooCommerce instance.
     */
    public function __construct($database, $user_meta, $x_poster, $logger = null, $woocommerce = null) {
        parent::__construct($database, $user_meta);
        $this->x_poster = $x_poster;
        $this->logger = $logger;
        $this->woocommerce = $woocommerce;
        
        $this->init();
    }

    /**
     * Initialize the class
     */
    private function init() {
        $this->init_hooks();
        $this->create_scheduling_tables();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Cron hooks
        add_action('xelite_execute_scheduled_post', array($this, 'execute_scheduled_post'), 10, 2);
        add_action('xelite_cleanup_expired_schedules', array($this, 'cleanup_expired_schedules'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'check_scheduling_permissions'));
        add_action('wp_ajax_xelite_schedule_post', array($this, 'ajax_schedule_post'));
        add_action('wp_ajax_xelite_get_scheduled_posts', array($this, 'ajax_get_scheduled_posts'));
        add_action('wp_ajax_xelite_update_scheduled_post', array($this, 'ajax_update_scheduled_post'));
        add_action('wp_ajax_xelite_delete_scheduled_post', array($this, 'ajax_delete_scheduled_post'));
        add_action('wp_ajax_xelite_execute_scheduled_post_now', array($this, 'ajax_execute_scheduled_post_now'));
        add_action('wp_ajax_xelite_get_timezone_options', array($this, 'ajax_get_timezone_options'));
        
        // Settings hooks
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        // Activation/deactivation hooks
        register_activation_hook(XELITE_REPOST_ENGINE_FILE, array($this, 'activate_scheduler'));
        register_deactivation_hook(XELITE_REPOST_ENGINE_FILE, array($this, 'deactivate_scheduler'));
        
        // Daily cleanup
        if (!wp_next_scheduled('xelite_cleanup_expired_schedules')) {
            wp_schedule_event(time(), 'daily', 'xelite_cleanup_expired_schedules');
        }
    }

    /**
     * Create scheduling database tables
     */
    public function create_scheduling_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Scheduled posts table
        $scheduled_posts_table = $wpdb->prefix . 'xelite_scheduled_posts';
        $sql_scheduled = "CREATE TABLE $scheduled_posts_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            content text NOT NULL,
            scheduled_time datetime NOT NULL,
            timezone varchar(50) NOT NULL DEFAULT 'UTC',
            status varchar(20) NOT NULL DEFAULT 'scheduled',
            options longtext DEFAULT NULL,
            tweet_id varchar(50) DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            posted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY scheduled_time (scheduled_time),
            KEY status (status),
            KEY timezone (timezone)
        ) $charset_collate;";
        
        // Scheduling logs table
        $scheduling_logs_table = $wpdb->prefix . 'xelite_scheduling_logs';
        $sql_logs = "CREATE TABLE $scheduling_logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            scheduled_post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text DEFAULT NULL,
            data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY scheduled_post_id (scheduled_post_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_scheduled);
        dbDelta($sql_logs);
        
        $this->log('info', 'Scheduling tables created/updated');
    }

    /**
     * Check if user has subscription access to scheduling feature
     *
     * @param int $user_id User ID.
     * @param string $feature Feature name (default: 'scheduling').
     * @return array Access check result.
     */
    public function check_subscription_access($user_id, $feature = 'scheduling') {
        if (!$this->woocommerce) {
            return array(
                'allowed' => true,
                'reason' => 'WooCommerce integration not available'
            );
        }

        return $this->woocommerce->can_access_feature($user_id, $feature);
    }

    /**
     * Check if user has exceeded their scheduling limits
     *
     * @param int $user_id User ID.
     * @return array Limit check result.
     */
    public function check_scheduling_limits($user_id) {
        if (!$this->woocommerce) {
            return array(
                'allowed' => true,
                'reason' => 'WooCommerce integration not available'
            );
        }

        $limits = $this->get_user_scheduling_limits($user_id);
        $current_scheduled = $this->get_user_scheduled_count($user_id);

        if ($current_scheduled >= $limits['max_scheduled_posts']) {
            return array(
                'allowed' => false,
                'reason' => sprintf(
                    'You have reached your limit of %d scheduled posts. Please upgrade your subscription for more.',
                    $limits['max_scheduled_posts']
                ),
                'current' => $current_scheduled,
                'limit' => $limits['max_scheduled_posts']
            );
        }

        return array(
            'allowed' => true,
            'reason' => 'Within scheduling limits',
            'current' => $current_scheduled,
            'limit' => $limits['max_scheduled_posts']
        );
    }

    /**
     * Get user's scheduling limits based on subscription tier
     *
     * @param int $user_id User ID.
     * @return array Scheduling limits.
     */
    public function get_user_scheduling_limits($user_id) {
        if (!$this->woocommerce) {
            return array(
                'max_scheduled_posts' => 10,
                'scheduling_window_days' => 30,
                'can_schedule_media' => true
            );
        }

        $limits = $this->woocommerce->get_user_limits($user_id);
        
        return array(
            'max_scheduled_posts' => isset($limits['scheduled_posts']) ? $limits['scheduled_posts'] : 10,
            'scheduling_window_days' => isset($limits['scheduling_window']) ? $limits['scheduling_window'] : 30,
            'can_schedule_media' => isset($limits['media_uploads']) ? $limits['media_uploads'] > 0 : true
        );
    }

    /**
     * Get count of currently scheduled posts for a user
     *
     * @param int $user_id User ID.
     * @return int Number of scheduled posts.
     */
    public function get_user_scheduled_count($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'xelite_scheduled_posts';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND status = 'scheduled'",
            $user_id
        ));
        
        return (int) $count;
    }

    /**
     * Schedule a post to X
     *
     * @param string $content Tweet content.
     * @param string $scheduled_time Scheduled time (Y-m-d H:i:s).
     * @param string $timezone Timezone.
     * @param int $user_id User ID.
     * @param array $options Additional options.
     * @return int|false Scheduled post ID or false on failure.
     */
    public function schedule_post($content, $scheduled_time, $timezone = 'UTC', $user_id = null, $options = array()) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Check subscription access
        $subscription_check = $this->check_subscription_access($user_id, 'scheduling');
        if (!$subscription_check['allowed']) {
            $this->log('warning', "User {$user_id} subscription check failed: " . $subscription_check['reason']);
            return array(
                'success' => false,
                'error' => $subscription_check['reason'],
                'subscription_required' => true
            );
        }

        // Check scheduling limits
        $limit_check = $this->check_scheduling_limits($user_id);
        if (!$limit_check['allowed']) {
            $this->log('warning', "User {$user_id} scheduling limit exceeded: " . $limit_check['reason']);
            return array(
                'success' => false,
                'error' => $limit_check['reason'],
                'limit_exceeded' => true
            );
        }
        
        // Validate content
        $validation = $this->x_poster->validate_tweet_content($content);
        if (!$validation['valid']) {
            $this->log('error', 'Content validation failed: ' . $validation['error']);
            return false;
        }
        
        // Validate scheduled time
        $validation = $this->validate_scheduled_time($scheduled_time, $timezone);
        if (!$validation['valid']) {
            $this->log('error', 'Scheduled time validation failed: ' . $validation['error']);
            return false;
        }
        
        // Convert to UTC for storage
        $utc_time = $this->convert_to_utc($scheduled_time, $timezone);
        
        // Store in database
        $data = array(
            'user_id' => $user_id,
            'content' => $content,
            'scheduled_time' => $utc_time,
            'timezone' => $timezone,
            'status' => 'scheduled',
            'options' => json_encode($options),
            'created_at' => current_time('mysql')
        );
        
        $scheduled_id = $this->database->insert('xelite_scheduled_posts', $data);
        
        if ($scheduled_id) {
            // Schedule WordPress cron event
            $timestamp = strtotime($utc_time);
            wp_schedule_single_event($timestamp, 'xelite_execute_scheduled_post', array($scheduled_id, $user_id));
            
            // Log the scheduling
            $this->log_scheduling_action($scheduled_id, $user_id, 'scheduled', 'success', 'Post scheduled successfully');
            
            $this->log('info', "Post scheduled for user {$user_id} at {$scheduled_time} ({$timezone})");
            return $scheduled_id;
        }
        
        return false;
    }

    /**
     * Execute a scheduled post
     *
     * @param int $scheduled_id Scheduled post ID.
     * @param int $user_id User ID.
     */
    public function execute_scheduled_post($scheduled_id, $user_id) {
        // Get scheduled post data
        $post_data = $this->get_scheduled_post($scheduled_id);
        
        if (!$post_data) {
            $this->log('error', "Scheduled post not found: {$scheduled_id}");
            return;
        }
        
        // Check if post is still scheduled
        if ($post_data->status !== 'scheduled') {
            $this->log('info', "Scheduled post {$scheduled_id} is not in scheduled status: {$post_data->status}");
            return;
        }

        // Check subscription access before execution
        $subscription_check = $this->check_subscription_access($user_id, 'scheduling');
        if (!$subscription_check['allowed']) {
            $this->update_scheduled_post_status($scheduled_id, 'failed', 'Subscription access denied: ' . $subscription_check['reason']);
            $this->log('warning', "User {$user_id} subscription check failed for scheduled post {$scheduled_id}: " . $subscription_check['reason']);
            return;
        }
        
        // Check if user is still authenticated
        if (!$this->x_poster->is_user_authenticated($user_id)) {
            $this->update_scheduled_post_status($scheduled_id, 'failed', 'User not authenticated with X');
            $this->log('error', "User {$user_id} not authenticated for scheduled post {$scheduled_id}");
            return;
        }
        
        // Execute the post
        $options = json_decode($post_data->options, true);
        $result = $this->x_poster->post_tweet($post_data->content, $user_id, $options);
        
        if ($result) {
            // Update post status to posted
            $this->update_scheduled_post_status($scheduled_id, 'posted', null, $result['data']['id']);
            $this->log_scheduling_action($scheduled_id, $user_id, 'executed', 'success', 'Post executed successfully');
            $this->log('info', "Scheduled post {$scheduled_id} executed successfully");
        } else {
            // Update post status to failed
            $this->update_scheduled_post_status($scheduled_id, 'failed', 'Failed to post tweet');
            $this->log_scheduling_action($scheduled_id, $user_id, 'executed', 'failed', 'Failed to post tweet');
            $this->log('error', "Scheduled post {$scheduled_id} failed to execute");
        }
    }

    /**
     * Get scheduled post by ID
     *
     * @param int $scheduled_id Scheduled post ID.
     * @return object|false Post data or false.
     */
    public function get_scheduled_post($scheduled_id) {
        return $this->database->get_row(
            $this->database->prepare("SELECT * FROM {$this->database->prefix}xelite_scheduled_posts WHERE id = %d", $scheduled_id)
        );
    }

    /**
     * Get scheduled posts for user
     *
     * @param int $user_id User ID.
     * @param string $status Status filter.
     * @param int $limit Number of posts to return.
     * @param int $offset Offset for pagination.
     * @return array Scheduled posts.
     */
    public function get_scheduled_posts($user_id = null, $status = null, $limit = 50, $offset = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $where_clause = "WHERE user_id = %d";
        $where_values = array($user_id);
        
        if ($status) {
            $where_clause .= " AND status = %s";
            $where_values[] = $status;
        }
        
        $query = $this->database->prepare(
            "SELECT * FROM {$this->database->prefix}xelite_scheduled_posts {$where_clause} ORDER BY scheduled_time DESC LIMIT %d OFFSET %d",
            array_merge($where_values, array($limit, $offset))
        );
        
        return $this->database->get_results($query);
    }

    /**
     * Update scheduled post status
     *
     * @param int $scheduled_id Scheduled post ID.
     * @param string $status New status.
     * @param string $error_message Error message.
     * @param string $tweet_id Tweet ID.
     */
    private function update_scheduled_post_status($scheduled_id, $status, $error_message = null, $tweet_id = null) {
        $data = array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        );
        
        if ($status === 'posted') {
            $data['posted_at'] = current_time('mysql');
            if ($tweet_id) {
                $data['tweet_id'] = $tweet_id;
            }
        } elseif ($status === 'failed' && $error_message) {
            $data['error_message'] = $error_message;
        }
        
        $this->database->update(
            'xelite_scheduled_posts',
            $data,
            array('id' => $scheduled_id)
        );
    }

    /**
     * Update scheduled post
     *
     * @param int $scheduled_id Scheduled post ID.
     * @param array $data Update data.
     * @return bool True if successful.
     */
    public function update_scheduled_post($scheduled_id, $data) {
        $post_data = $this->get_scheduled_post($scheduled_id);
        if (!$post_data) {
            return false;
        }
        
        // Validate content if provided
        if (isset($data['content'])) {
            $validation = $this->x_poster->validate_tweet_content($data['content']);
            if (!$validation['valid']) {
                return false;
            }
        }
        
        // Validate scheduled time if provided
        if (isset($data['scheduled_time']) && isset($data['timezone'])) {
            $validation = $this->validate_scheduled_time($data['scheduled_time'], $data['timezone']);
            if (!$validation['valid']) {
                return false;
            }
            
            // Convert to UTC
            $data['scheduled_time'] = $this->convert_to_utc($data['scheduled_time'], $data['timezone']);
        }
        
        // Update the post
        $data['updated_at'] = current_time('mysql');
        $result = $this->database->update(
            'xelite_scheduled_posts',
            $data,
            array('id' => $scheduled_id)
        );
        
        if ($result && isset($data['scheduled_time'])) {
            // Reschedule cron event
            $this->reschedule_post($scheduled_id, strtotime($data['scheduled_time']));
        }
        
        return $result;
    }

    /**
     * Delete scheduled post
     *
     * @param int $scheduled_id Scheduled post ID.
     * @return bool True if successful.
     */
    public function delete_scheduled_post($scheduled_id) {
        $post_data = $this->get_scheduled_post($scheduled_id);
        if (!$post_data) {
            return false;
        }
        
        // Clear cron event
        wp_clear_scheduled_hook('xelite_execute_scheduled_post', array($scheduled_id, $post_data->user_id));
        
        // Delete from database
        $result = $this->database->delete(
            'xelite_scheduled_posts',
            array('id' => $scheduled_id)
        );
        
        if ($result) {
            $this->log_scheduling_action($scheduled_id, $post_data->user_id, 'deleted', 'success', 'Post deleted');
        }
        
        return $result;
    }

    /**
     * Execute scheduled post immediately
     *
     * @param int $scheduled_id Scheduled post ID.
     * @return bool True if successful.
     */
    public function execute_scheduled_post_now($scheduled_id) {
        $post_data = $this->get_scheduled_post($scheduled_id);
        if (!$post_data || $post_data->status !== 'scheduled') {
            return false;
        }
        
        // Execute immediately
        $this->execute_scheduled_post($scheduled_id, $post_data->user_id);
        
        return true;
    }

    /**
     * Reschedule a post
     *
     * @param int $scheduled_id Scheduled post ID.
     * @param int $timestamp New timestamp.
     */
    private function reschedule_post($scheduled_id, $timestamp) {
        $post_data = $this->get_scheduled_post($scheduled_id);
        if (!$post_data) {
            return;
        }
        
        // Clear existing cron event
        wp_clear_scheduled_hook('xelite_execute_scheduled_post', array($scheduled_id, $post_data->user_id));
        
        // Schedule new cron event
        wp_schedule_single_event($timestamp, 'xelite_execute_scheduled_post', array($scheduled_id, $post_data->user_id));
    }

    /**
     * Validate scheduled time
     *
     * @param string $scheduled_time Scheduled time.
     * @param string $timezone Timezone.
     * @return array Validation result.
     */
    public function validate_scheduled_time($scheduled_time, $timezone) {
        // Check if timezone is valid
        if (!in_array($timezone, DateTimeZone::listIdentifiers())) {
            return array(
                'valid' => false,
                'error' => 'Invalid timezone'
            );
        }
        
        // Parse scheduled time
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $scheduled_time, new DateTimeZone($timezone));
        if (!$date) {
            return array(
                'valid' => false,
                'error' => 'Invalid date format'
            );
        }
        
        // Convert to UTC for comparison
        $utc_date = clone $date;
        $utc_date->setTimezone(new DateTimeZone('UTC'));
        
        // Check if time is in the future
        if ($utc_date <= new DateTime('now', new DateTimeZone('UTC'))) {
            return array(
                'valid' => false,
                'error' => 'Scheduled time must be in the future'
            );
        }
        
        // Check if time is not too far in the future (e.g., 1 year)
        $max_future = new DateTime('now', new DateTimeZone('UTC'));
        $max_future->add(new DateInterval('P1Y'));
        
        if ($utc_date > $max_future) {
            return array(
                'valid' => false,
                'error' => 'Scheduled time cannot be more than 1 year in the future'
            );
        }
        
        return array(
            'valid' => true,
            'error' => null
        );
    }

    /**
     * Convert time to UTC
     *
     * @param string $time Time string.
     * @param string $timezone Source timezone.
     * @return string UTC time string.
     */
    public function convert_to_utc($time, $timezone) {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $time, new DateTimeZone($timezone));
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Convert UTC time to timezone
     *
     * @param string $utc_time UTC time string.
     * @param string $timezone Target timezone.
     * @return string Time in target timezone.
     */
    public function convert_from_utc($utc_time, $timezone) {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $utc_time, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($timezone));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Get available timezones
     *
     * @return array Timezone options.
     */
    public function get_timezone_options() {
        $timezones = DateTimeZone::listIdentifiers();
        $options = array();
        
        foreach ($timezones as $timezone) {
            $date = new DateTime('now', new DateTimeZone($timezone));
            $offset = $date->format('P');
            $options[$timezone] = "({$offset}) {$timezone}";
        }
        
        return $options;
    }

    /**
     * Cleanup expired schedules
     */
    public function cleanup_expired_schedules() {
        global $wpdb;
        
        // Delete failed posts older than 30 days
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}xelite_scheduled_posts WHERE status = 'failed' AND created_at < %s",
                $thirty_days_ago
            )
        );
        
        if ($deleted > 0) {
            $this->log('info', "Cleaned up {$deleted} expired failed schedules");
        }
    }

    /**
     * Log scheduling action
     *
     * @param int $scheduled_id Scheduled post ID.
     * @param int $user_id User ID.
     * @param string $action Action performed.
     * @param string $status Status.
     * @param string $message Message.
     * @param array $data Additional data.
     */
    private function log_scheduling_action($scheduled_id, $user_id, $action, $status, $message, $data = array()) {
        global $wpdb;
        
        $log_data = array(
            'scheduled_post_id' => $scheduled_id,
            'user_id' => $user_id,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'data' => json_encode($data),
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert($wpdb->prefix . 'xelite_scheduling_logs', $log_data);
    }

    /**
     * AJAX schedule post
     */
    public function ajax_schedule_post() {
        check_ajax_referer('xelite_scheduler_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        $scheduled_time = isset($_POST['scheduled_time']) ? sanitize_text_field($_POST['scheduled_time']) : '';
        $timezone = isset($_POST['timezone']) ? sanitize_text_field($_POST['timezone']) : 'UTC';
        $options = isset($_POST['options']) ? (array) $_POST['options'] : array();
        
        if (empty($content) || empty($scheduled_time)) {
            wp_send_json_error('Content and scheduled time are required');
        }
        
        $scheduled_id = $this->schedule_post($content, $scheduled_time, $timezone, $user_id, $options);
        
        if ($scheduled_id) {
            wp_send_json_success(array(
                'scheduled_id' => $scheduled_id,
                'message' => 'Post scheduled successfully'
            ));
        } else {
            wp_send_json_error('Failed to schedule post');
        }
    }

    /**
     * AJAX get scheduled posts
     */
    public function ajax_get_scheduled_posts() {
        check_ajax_referer('xelite_scheduler_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        $posts = $this->get_scheduled_posts($user_id, $status, $limit, $offset);
        
        // Convert UTC times back to user's timezone
        foreach ($posts as &$post) {
            $post->scheduled_time_local = $this->convert_from_utc($post->scheduled_time, $post->timezone);
        }
        
        wp_send_json_success($posts);
    }

    /**
     * AJAX update scheduled post
     */
    public function ajax_update_scheduled_post() {
        check_ajax_referer('xelite_scheduler_nonce', 'nonce');
        
        $scheduled_id = isset($_POST['scheduled_id']) ? intval($_POST['scheduled_id']) : 0;
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        $scheduled_time = isset($_POST['scheduled_time']) ? sanitize_text_field($_POST['scheduled_time']) : '';
        $timezone = isset($_POST['timezone']) ? sanitize_text_field($_POST['timezone']) : 'UTC';
        
        if (!$scheduled_id) {
            wp_send_json_error('Scheduled post ID is required');
        }
        
        $data = array();
        if (!empty($content)) {
            $data['content'] = $content;
        }
        if (!empty($scheduled_time)) {
            $data['scheduled_time'] = $scheduled_time;
            $data['timezone'] = $timezone;
        }
        
        if (empty($data)) {
            wp_send_json_error('No data to update');
        }
        
        $result = $this->update_scheduled_post($scheduled_id, $data);
        
        if ($result) {
            wp_send_json_success('Post updated successfully');
        } else {
            wp_send_json_error('Failed to update post');
        }
    }

    /**
     * AJAX delete scheduled post
     */
    public function ajax_delete_scheduled_post() {
        check_ajax_referer('xelite_scheduler_nonce', 'nonce');
        
        $scheduled_id = isset($_POST['scheduled_id']) ? intval($_POST['scheduled_id']) : 0;
        
        if (!$scheduled_id) {
            wp_send_json_error('Scheduled post ID is required');
        }
        
        $result = $this->delete_scheduled_post($scheduled_id);
        
        if ($result) {
            wp_send_json_success('Post deleted successfully');
        } else {
            wp_send_json_error('Failed to delete post');
        }
    }

    /**
     * AJAX execute scheduled post now
     */
    public function ajax_execute_scheduled_post_now() {
        check_ajax_referer('xelite_scheduler_nonce', 'nonce');
        
        $scheduled_id = isset($_POST['scheduled_id']) ? intval($_POST['scheduled_id']) : 0;
        
        if (!$scheduled_id) {
            wp_send_json_error('Scheduled post ID is required');
        }
        
        $result = $this->execute_scheduled_post_now($scheduled_id);
        
        if ($result) {
            wp_send_json_success('Post executed successfully');
        } else {
            wp_send_json_error('Failed to execute post');
        }
    }

    /**
     * AJAX get timezone options
     */
    public function ajax_get_timezone_options() {
        check_ajax_referer('xelite_scheduler_nonce', 'nonce');
        
        $timezones = $this->get_timezone_options();
        wp_send_json_success($timezones);
    }

    /**
     * Check scheduling permissions
     */
    public function check_scheduling_permissions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if X poster is available
        if (!$this->x_poster) {
            add_action('admin_notices', array($this, 'x_poster_missing_notice'));
        }
    }

    /**
     * Display X poster missing notice
     */
    public function x_poster_missing_notice() {
        echo '<div class="notice notice-warning"><p>X Poster is required for scheduling functionality. Please ensure the X Poster is properly configured.</p></div>';
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'xelite_scheduler_settings',
            'xelite_scheduler_default_timezone',
            array($this, 'sanitize_timezone')
        );
        register_setting(
            'xelite_scheduler_settings',
            'xelite_scheduler_cleanup_days',
            array($this, 'sanitize_cleanup_days')
        );
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'xelite-repost-engine',
            'Scheduling Settings',
            'Scheduling',
            'manage_options',
            'xelite-scheduler-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1>Scheduling Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('xelite_scheduler_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Timezone</th>
                        <td>
                            <select name="xelite_scheduler_default_timezone">
                                <?php
                                $default_timezone = get_option('xelite_scheduler_default_timezone', 'UTC');
                                $timezones = $this->get_timezone_options();
                                foreach ($timezones as $value => $label) {
                                    $selected = ($value === $default_timezone) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Default timezone for new scheduled posts.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cleanup Failed Posts After</th>
                        <td>
                            <input type="number" name="xelite_scheduler_cleanup_days" value="<?php echo esc_attr(get_option('xelite_scheduler_cleanup_days', 30)); ?>" min="1" max="365">
                            <span>days</span>
                            <p class="description">Automatically delete failed scheduled posts after this many days.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="xelite-settings-section">
                <h2>Scheduling Statistics</h2>
                <?php
                global $wpdb;
                $total_scheduled = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}xelite_scheduled_posts WHERE status = 'scheduled'");
                $total_posted = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}xelite_scheduled_posts WHERE status = 'posted'");
                $total_failed = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}xelite_scheduled_posts WHERE status = 'failed'");
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Scheduled</td>
                            <td><?php echo intval($total_scheduled); ?></td>
                        </tr>
                        <tr>
                            <td>Posted</td>
                            <td><?php echo intval($total_posted); ?></td>
                        </tr>
                        <tr>
                            <td>Failed</td>
                            <td><?php echo intval($total_failed); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Sanitize timezone
     *
     * @param string $input Raw input.
     * @return string Sanitized timezone.
     */
    public function sanitize_timezone($input) {
        $timezones = DateTimeZone::listIdentifiers();
        return in_array($input, $timezones) ? $input : 'UTC';
    }

    /**
     * Sanitize cleanup days
     *
     * @param int $input Raw input.
     * @return int Sanitized days.
     */
    public function sanitize_cleanup_days($input) {
        $days = intval($input);
        return ($days >= 1 && $days <= 365) ? $days : 30;
    }

    /**
     * Activate scheduler
     */
    public function activate_scheduler() {
        $this->create_scheduling_tables();
        
        // Schedule daily cleanup
        if (!wp_next_scheduled('xelite_cleanup_expired_schedules')) {
            wp_schedule_event(time(), 'daily', 'xelite_cleanup_expired_schedules');
        }
    }

    /**
     * Deactivate scheduler
     */
    public function deactivate_scheduler() {
        // Clear scheduled events
        wp_clear_scheduled_hook('xelite_cleanup_expired_schedules');
        
        // Clear all scheduled posts
        global $wpdb;
        $scheduled_posts = $wpdb->get_results("SELECT id, user_id FROM {$wpdb->prefix}xelite_scheduled_posts WHERE status = 'scheduled'");
        
        foreach ($scheduled_posts as $post) {
            wp_clear_scheduled_hook('xelite_execute_scheduled_post', array($post->id, $post->user_id));
        }
    }

    /**
     * Log message
     *
     * @param string $level Log level.
     * @param string $message Log message.
     * @param array $context Additional context.
     */
    private function log($level, $message, $context = array()) {
        if ($this->logger) {
            $this->logger->log($level, "[Scheduler] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine Scheduler] {$message}");
        }
    }
} 