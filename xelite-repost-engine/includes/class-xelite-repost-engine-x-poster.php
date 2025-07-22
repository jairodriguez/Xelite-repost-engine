<?php
/**
 * X API Posting Class
 *
 * Handles posting content to X (Twitter) via the API, including authentication,
 * content validation, error handling, and response processing.
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * X API Poster Class
 *
 * Manages all interactions with the X (Twitter) API for posting content.
 */
class XeliteRepostEngine_X_Poster extends XeliteRepostEngine_Abstract_Base {

    /**
     * X API base URL
     *
     * @var string
     */
    private $api_base_url = 'https://api.twitter.com/2';

    /**
     * OAuth 2.0 token endpoint
     *
     * @var string
     */
    private $oauth_token_url = 'https://api.twitter.com/2/oauth2/token';

    /**
     * Current access token
     *
     * @var string|null
     */
    private $access_token = null;

    /**
     * Token expiration time
     *
     * @var int|null
     */
    private $token_expires_at = null;

    /**
     * Logger instance
     *
     * @var XeliteRepostEngine_Logger|null
     */
    private $logger;

    /**
     * WooCommerce integration instance
     *
     * @var XeliteRepostEngine_WooCommerce|null
     */
    private $woocommerce;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Database $database Database instance.
     * @param XeliteRepostEngine_User_Meta $user_meta User meta instance.
     * @param XeliteRepostEngine_Logger|null $logger Logger instance.
     * @param XeliteRepostEngine_WooCommerce|null $woocommerce WooCommerce instance.
     */
    public function __construct($database, $user_meta, $logger = null, $woocommerce = null) {
        parent::__construct($database, $user_meta);
        $this->logger = $logger;
        $this->woocommerce = $woocommerce;
        
        $this->init();
    }

    /**
     * Initialize the class
     */
    private function init() {
        $this->init_hooks();
        $this->load_access_token();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin hooks
        add_action('admin_init', array($this, 'check_api_credentials'));
        add_action('wp_ajax_xelite_test_x_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_xelite_post_to_x', array($this, 'ajax_post_to_x'));
        add_action('wp_ajax_xelite_get_x_credentials', array($this, 'ajax_get_credentials'));
        add_action('wp_ajax_xelite_save_x_credentials', array($this, 'ajax_save_credentials'));
        
        // Settings hooks
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        // Cron hooks for scheduled posts
        add_action('xelite_post_scheduled_tweet', array($this, 'post_scheduled_tweet'), 10, 2);
    }

    /**
     * Check if X API credentials are configured
     *
     * @return bool True if credentials are configured.
     */
    public function has_credentials() {
        $client_id = get_option('xelite_x_client_id', '');
        $client_secret = get_option('xelite_x_client_secret', '');
        $redirect_uri = get_option('xelite_x_redirect_uri', '');
        
        return !empty($client_id) && !empty($client_secret) && !empty($redirect_uri);
    }

    /**
     * Check if user is authenticated with X
     *
     * @param int $user_id User ID.
     * @return bool True if authenticated.
     */
    public function is_user_authenticated($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $access_token = get_user_meta($user_id, 'xelite_x_access_token', true);
        $refresh_token = get_user_meta($user_id, 'xelite_x_refresh_token', true);
        $expires_at = get_user_meta($user_id, 'xelite_x_token_expires_at', true);
        
        if (empty($access_token) || empty($refresh_token)) {
            return false;
        }
        
        // Check if token is expired
        if ($expires_at && time() > intval($expires_at)) {
            return $this->refresh_user_token($user_id);
        }
        
        return true;
    }

    /**
     * Get OAuth 2.0 authorization URL
     *
     * @param int $user_id User ID.
     * @return string Authorization URL.
     */
    public function get_authorization_url($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $client_id = get_option('xelite_x_client_id', '');
        $redirect_uri = get_option('xelite_x_redirect_uri', '');
        
        if (empty($client_id) || empty($redirect_uri)) {
            return '';
        }
        
        $state = wp_create_nonce('xelite_x_oauth_' . $user_id);
        update_user_meta($user_id, 'xelite_x_oauth_state', $state);
        
        $params = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => 'tweet.read tweet.write users.read offline.access',
            'state' => $state,
            'code_challenge' => $this->generate_code_challenge(),
            'code_challenge_method' => 'S256'
        );
        
        return 'https://twitter.com/i/oauth2/authorize?' . http_build_query($params);
    }

    /**
     * Handle OAuth 2.0 callback
     *
     * @param string $code Authorization code.
     * @param string $state State parameter.
     * @param int $user_id User ID.
     * @return bool True if successful.
     */
    public function handle_oauth_callback($code, $state, $user_id) {
        // Verify state
        $expected_state = get_user_meta($user_id, 'xelite_x_oauth_state', true);
        if ($state !== $expected_state) {
            $this->log('error', 'OAuth state mismatch for user ' . $user_id);
            return false;
        }
        
        // Exchange code for tokens
        $tokens = $this->exchange_code_for_tokens($code, $user_id);
        if (!$tokens) {
            return false;
        }
        
        // Store tokens
        update_user_meta($user_id, 'xelite_x_access_token', $tokens['access_token']);
        update_user_meta($user_id, 'xelite_x_refresh_token', $tokens['refresh_token']);
        update_user_meta($user_id, 'xelite_x_token_expires_at', time() + $tokens['expires_in']);
        update_user_meta($user_id, 'xelite_x_authenticated_at', current_time('mysql'));
        
        // Clear state
        delete_user_meta($user_id, 'xelite_x_oauth_state');
        
        $this->log('info', 'User ' . $user_id . ' successfully authenticated with X');
        return true;
    }

    /**
     * Exchange authorization code for access tokens
     *
     * @param string $code Authorization code.
     * @param int $user_id User ID.
     * @return array|false Token data or false on failure.
     */
    private function exchange_code_for_tokens($code, $user_id) {
        $client_id = get_option('xelite_x_client_id', '');
        $client_secret = get_option('xelite_x_client_secret', '');
        $redirect_uri = get_option('xelite_x_redirect_uri', '');
        
        if (empty($client_id) || empty($client_secret) || empty($redirect_uri)) {
            $this->log('error', 'Missing X API credentials');
            return false;
        }
        
        $code_verifier = get_user_meta($user_id, 'xelite_x_code_verifier', true);
        
        $data = array(
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $code,
            'code_verifier' => $code_verifier
        );
        
        $response = wp_remote_post($this->oauth_token_url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            $this->log('error', 'Token exchange failed: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $tokens = json_decode($body, true);
        
        if (empty($tokens['access_token'])) {
            $this->log('error', 'Token exchange failed: ' . $body);
            return false;
        }
        
        // Clear code verifier
        delete_user_meta($user_id, 'xelite_x_code_verifier');
        
        return $tokens;
    }

    /**
     * Refresh user's access token
     *
     * @param int $user_id User ID.
     * @return bool True if successful.
     */
    public function refresh_user_token($user_id) {
        $refresh_token = get_user_meta($user_id, 'xelite_x_refresh_token', true);
        
        if (empty($refresh_token)) {
            return false;
        }
        
        $client_id = get_option('xelite_x_client_id', '');
        $client_secret = get_option('xelite_x_client_secret', '');
        
        $data = array(
            'grant_type' => 'refresh_token',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token
        );
        
        $response = wp_remote_post($this->oauth_token_url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            $this->log('error', 'Token refresh failed for user ' . $user_id . ': ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $tokens = json_decode($body, true);
        
        if (empty($tokens['access_token'])) {
            $this->log('error', 'Token refresh failed for user ' . $user_id . ': ' . $body);
            return false;
        }
        
        // Update tokens
        update_user_meta($user_id, 'xelite_x_access_token', $tokens['access_token']);
        if (!empty($tokens['refresh_token'])) {
            update_user_meta($user_id, 'xelite_x_refresh_token', $tokens['refresh_token']);
        }
        update_user_meta($user_id, 'xelite_x_token_expires_at', time() + $tokens['expires_in']);
        
        $this->log('info', 'Token refreshed for user ' . $user_id);
        return true;
    }

    /**
     * Check subscription access for posting
     *
     * @param int $user_id User ID.
     * @param string $feature Feature name.
     * @return array Access check result.
     */
    public function check_subscription_access($user_id, $feature) {
        if (!$this->woocommerce) {
            return array(
                'allowed' => true,
                'reason' => 'WooCommerce integration not available'
            );
        }

        if (!$this->woocommerce->is_integration_active()) {
            return array(
                'allowed' => true,
                'reason' => 'WooCommerce integration not active'
            );
        }

        if (!$this->woocommerce->can_access_feature($feature, $user_id)) {
            $tier = $this->woocommerce->get_user_tier($user_id);
            return array(
                'allowed' => false,
                'reason' => "Feature '{$feature}' requires an active subscription. Current tier: {$tier}",
                'current_tier' => $tier
            );
        }

        return array(
            'allowed' => true,
            'reason' => 'Access granted'
        );
    }

    /**
     * Check posting limits for user
     *
     * @param int $user_id User ID.
     * @return array Limit check result.
     */
    public function check_posting_limits($user_id) {
        if (!$this->woocommerce) {
            return array(
                'allowed' => true,
                'reason' => 'WooCommerce integration not available'
            );
        }

        if (!$this->woocommerce->is_integration_active()) {
            return array(
                'allowed' => true,
                'reason' => 'WooCommerce integration not active'
            );
        }

        // Get user's posting limits
        $limits = $this->get_user_posting_limits($user_id);
        
        // Check daily posting count
        $daily_posts = $this->get_user_daily_posts($user_id);
        
        if ($limits['daily_posts'] > 0 && $daily_posts >= $limits['daily_posts']) {
            return array(
                'allowed' => false,
                'reason' => "Daily posting limit reached ({$daily_posts}/{$limits['daily_posts']})",
                'current' => $daily_posts,
                'limit' => $limits['daily_posts']
            );
        }

        return array(
            'allowed' => true,
            'reason' => 'Within posting limits'
        );
    }

    /**
     * Get user's posting limits based on subscription tier
     *
     * @param int $user_id User ID.
     * @return array Posting limits.
     */
    public function get_user_posting_limits($user_id) {
        if (!$this->woocommerce) {
            return array(
                'daily_posts' => -1, // Unlimited
                'scheduling_window' => 30, // 30 days
                'media_uploads' => -1 // Unlimited
            );
        }

        $tier = $this->woocommerce->get_user_tier($user_id);
        
        // Default limits by tier
        $tier_limits = array(
            'basic' => array(
                'daily_posts' => 5,
                'scheduling_window' => 7, // 7 days
                'media_uploads' => 2
            ),
            'premium' => array(
                'daily_posts' => 20,
                'scheduling_window' => 30, // 30 days
                'media_uploads' => 10
            ),
            'enterprise' => array(
                'daily_posts' => -1, // Unlimited
                'scheduling_window' => 90, // 90 days
                'media_uploads' => -1 // Unlimited
            )
        );

        return isset($tier_limits[$tier]) ? $tier_limits[$tier] : $tier_limits['basic'];
    }

    /**
     * Get user's daily post count
     *
     * @param int $user_id User ID.
     * @return int Daily post count.
     */
    public function get_user_daily_posts($user_id) {
        global $wpdb;
        
        $today = date('Y-m-d');
        $table_name = $wpdb->prefix . 'xelite_posted_tweets';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND DATE(posted_at) = %s",
            $user_id,
            $today
        ));
        
        return intval($count);
    }

    /**
     * Increment user's daily post count
     *
     * @param int $user_id User ID.
     */
    public function increment_daily_posts($user_id) {
        // This is handled by store_post_record method
        // Just log the action for tracking
        $this->log('info', "Incremented daily post count for user {$user_id}");
    }

    /**
     * Post content to X
     *
     * @param string $content Tweet content.
     * @param int $user_id User ID.
     * @param array $options Additional options.
     * @return array|false Response data or false on failure.
     */
    public function post_tweet($content, $user_id = null, $options = array()) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Validate content
        $validation = $this->validate_tweet_content($content);
        if (!$validation['valid']) {
            $this->log('error', 'Tweet validation failed: ' . $validation['error']);
            return false;
        }
        
        // Check authentication
        if (!$this->is_user_authenticated($user_id)) {
            $this->log('error', 'User ' . $user_id . ' not authenticated with X');
            return false;
        }
        
        // Get access token
        $access_token = get_user_meta($user_id, 'xelite_x_access_token', true);
        
        // Prepare tweet data
        $tweet_data = array(
            'text' => $content
        );
        
        // Add reply settings if specified
        if (!empty($options['reply_settings'])) {
            $tweet_data['reply'] = array(
                'in_reply_to_tweet_id' => $options['reply_settings']
            );
        }
        
        // Add media if specified
        if (!empty($options['media_ids'])) {
            $tweet_data['media'] = array(
                'media_ids' => $options['media_ids']
            );
        }
        
        // Make API request
        $response = wp_remote_post($this->api_base_url . '/tweets', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($tweet_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            $this->log('error', 'Tweet posting failed for user ' . $user_id . ': ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 201) {
            $this->log('error', 'Tweet posting failed for user ' . $user_id . ': ' . $body);
            return false;
        }
        
        // Log successful post
        $this->log('info', 'Tweet posted successfully for user ' . $user_id . ': ' . $result['data']['id']);
        
        // Store post record
        $this->store_post_record($user_id, $content, $result['data']['id'], $options);
        
        return $result;
    }

    /**
     * Validate tweet content
     *
     * @param string $content Tweet content.
     * @return array Validation result.
     */
    public function validate_tweet_content($content) {
        $content = trim($content);
        
        // Check if content is empty
        if (empty($content)) {
            return array(
                'valid' => false,
                'error' => 'Tweet content cannot be empty'
            );
        }
        
        // Check character limit (280 characters for tweets)
        if (mb_strlen($content) > 280) {
            return array(
                'valid' => false,
                'error' => 'Tweet content exceeds 280 character limit'
            );
        }
        
        // Check for invalid characters
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content)) {
            return array(
                'valid' => false,
                'error' => 'Tweet content contains invalid characters'
            );
        }
        
        return array(
            'valid' => true,
            'error' => null
        );
    }

    /**
     * Upload media to X
     *
     * @param string $file_path File path.
     * @param int $user_id User ID.
     * @return string|false Media ID or false on failure.
     */
    public function upload_media($file_path, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Check authentication
        if (!$this->is_user_authenticated($user_id)) {
            $this->log('error', 'User ' . $user_id . ' not authenticated with X');
            return false;
        }
        
        // Validate file
        if (!file_exists($file_path)) {
            $this->log('error', 'Media file not found: ' . $file_path);
            return false;
        }
        
        $file_size = filesize($file_path);
        if ($file_size > 5 * 1024 * 1024) { // 5MB limit
            $this->log('error', 'Media file too large: ' . $file_size . ' bytes');
            return false;
        }
        
        // Get access token
        $access_token = get_user_meta($user_id, 'xelite_x_access_token', true);
        
        // Prepare file data
        $boundary = wp_generate_password(24);
        $body = '';
        
        // Add file
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="media"; filename="' . basename($file_path) . '"' . "\r\n";
        $body .= 'Content-Type: ' . mime_content_type($file_path) . "\r\n\r\n";
        $body .= file_get_contents($file_path) . "\r\n";
        $body .= '--' . $boundary . '--' . "\r\n";
        
        // Make API request
        $response = wp_remote_post($this->api_base_url . '/media/upload', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary
            ),
            'body' => $body,
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            $this->log('error', 'Media upload failed for user ' . $user_id . ': ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            $this->log('error', 'Media upload failed for user ' . $user_id . ': ' . $body);
            return false;
        }
        
        $this->log('info', 'Media uploaded successfully for user ' . $user_id . ': ' . $result['media_id_string']);
        return $result['media_id_string'];
    }

    /**
     * Schedule a tweet
     *
     * @param string $content Tweet content.
     * @param int $timestamp Unix timestamp for posting time.
     * @param int $user_id User ID.
     * @param array $options Additional options.
     * @return int|false Scheduled post ID or false on failure.
     */
    public function schedule_tweet($content, $timestamp, $user_id = null, $options = array()) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Validate content
        $validation = $this->validate_tweet_content($content);
        if (!$validation['valid']) {
            $this->log('error', 'Tweet validation failed: ' . $validation['error']);
            return false;
        }
        
        // Check if time is in the future
        if ($timestamp <= time()) {
            $this->log('error', 'Scheduled time must be in the future');
            return false;
        }
        
        // Store scheduled post
        $post_data = array(
            'user_id' => $user_id,
            'content' => $content,
            'scheduled_time' => $timestamp,
            'options' => json_encode($options),
            'status' => 'scheduled',
            'created_at' => current_time('mysql')
        );
        
        $post_id = $this->database->insert('xelite_scheduled_posts', $post_data);
        
        if ($post_id) {
            // Schedule WordPress cron event
            wp_schedule_single_event($timestamp, 'xelite_post_scheduled_tweet', array($post_id, $user_id));
            
            $this->log('info', 'Tweet scheduled for user ' . $user_id . ' at ' . date('Y-m-d H:i:s', $timestamp));
            return $post_id;
        }
        
        return false;
    }

    /**
     * Post scheduled tweet (called by cron)
     *
     * @param int $post_id Scheduled post ID.
     * @param int $user_id User ID.
     */
    public function post_scheduled_tweet($post_id, $user_id) {
        // Get scheduled post data
        $post_data = $this->database->get_row(
            $this->database->prepare("SELECT * FROM {$this->database->prefix}xelite_scheduled_posts WHERE id = %d", $post_id)
        );
        
        if (!$post_data) {
            $this->log('error', 'Scheduled post not found: ' . $post_id);
            return;
        }
        
        $options = json_decode($post_data->options, true);
        
        // Post the tweet
        $result = $this->post_tweet($post_data->content, $user_id, $options);
        
        if ($result) {
            // Update post status
            $this->database->update(
                'xelite_scheduled_posts',
                array(
                    'status' => 'posted',
                    'posted_at' => current_time('mysql'),
                    'tweet_id' => $result['data']['id']
                ),
                array('id' => $post_id)
            );
            
            $this->log('info', 'Scheduled tweet posted successfully: ' . $post_id);
        } else {
            // Update post status to failed
            $this->database->update(
                'xelite_scheduled_posts',
                array(
                    'status' => 'failed',
                    'error_message' => 'Failed to post tweet'
                ),
                array('id' => $post_id)
            );
            
            $this->log('error', 'Scheduled tweet failed: ' . $post_id);
        }
    }

    /**
     * Store post record in database
     *
     * @param int $user_id User ID.
     * @param string $content Tweet content.
     * @param string $tweet_id X tweet ID.
     * @param array $options Additional options.
     */
    private function store_post_record($user_id, $content, $tweet_id, $options = array()) {
        $data = array(
            'user_id' => $user_id,
            'content' => $content,
            'tweet_id' => $tweet_id,
            'options' => json_encode($options),
            'posted_at' => current_time('mysql')
        );
        
        $this->database->insert('xelite_posted_tweets', $data);
    }

    /**
     * Generate PKCE code challenge
     *
     * @return string Code challenge.
     */
    private function generate_code_challenge() {
        $code_verifier = bin2hex(random_bytes(32));
        $code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
        
        // Store code verifier for later use
        update_user_meta(get_current_user_id(), 'xelite_x_code_verifier', $code_verifier);
        
        return $code_challenge;
    }

    /**
     * Load access token from user meta
     */
    private function load_access_token() {
        $user_id = get_current_user_id();
        if ($user_id) {
            $this->access_token = get_user_meta($user_id, 'xelite_x_access_token', true);
            $this->token_expires_at = get_user_meta($user_id, 'xelite_x_token_expires_at', true);
        }
    }

    /**
     * Check API credentials on admin init
     */
    public function check_api_credentials() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!$this->has_credentials()) {
            add_action('admin_notices', array($this, 'missing_credentials_notice'));
        }
    }

    /**
     * Display missing credentials notice
     */
    public function missing_credentials_notice() {
        echo '<div class="notice notice-warning"><p>X API credentials are not configured. Please configure them in the <a href="' . admin_url('admin.php?page=xelite-x-settings') . '">X Settings</a> page.</p></div>';
    }

    /**
     * AJAX test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('xelite_x_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = get_current_user_id();
        
        if (!$this->is_user_authenticated($user_id)) {
            wp_send_json_error('Not authenticated with X');
        }
        
        // Test API connection by getting user info
        $access_token = get_user_meta($user_id, 'xelite_x_access_token', true);
        
        $response = wp_remote_get($this->api_base_url . '/users/me', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            wp_send_json_error('API error: ' . $body);
        }
        
        wp_send_json_success(array(
            'user' => $result['data'],
            'message' => 'Connection successful'
        ));
    }

    /**
     * AJAX post to X
     */
    public function ajax_post_to_x() {
        check_ajax_referer('xelite_x_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        $options = isset($_POST['options']) ? (array) $_POST['options'] : array();
        
        if (empty($content)) {
            wp_send_json_error('Tweet content is required');
        }
        
        $result = $this->post_tweet($content, $user_id, $options);
        
        if ($result) {
            wp_send_json_success(array(
                'tweet_id' => $result['data']['id'],
                'message' => 'Tweet posted successfully'
            ));
        } else {
            wp_send_json_error('Failed to post tweet');
        }
    }

    /**
     * AJAX get credentials
     */
    public function ajax_get_credentials() {
        check_ajax_referer('xelite_x_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $credentials = array(
            'client_id' => get_option('xelite_x_client_id', ''),
            'client_secret' => get_option('xelite_x_client_secret', ''),
            'redirect_uri' => get_option('xelite_x_redirect_uri', '')
        );
        
        wp_send_json_success($credentials);
    }

    /**
     * AJAX save credentials
     */
    public function ajax_save_credentials() {
        check_ajax_referer('xelite_x_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
        $client_secret = isset($_POST['client_secret']) ? sanitize_text_field($_POST['client_secret']) : '';
        $redirect_uri = isset($_POST['redirect_uri']) ? esc_url_raw($_POST['redirect_uri']) : '';
        
        update_option('xelite_x_client_id', $client_id);
        update_option('xelite_x_client_secret', $client_secret);
        update_option('xelite_x_redirect_uri', $redirect_uri);
        
        wp_send_json_success('Credentials saved successfully');
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'xelite_x_settings',
            'xelite_x_client_id',
            array($this, 'sanitize_client_id')
        );
        register_setting(
            'xelite_x_settings',
            'xelite_x_client_secret',
            array($this, 'sanitize_client_secret')
        );
        register_setting(
            'xelite_x_settings',
            'xelite_x_redirect_uri',
            array($this, 'sanitize_redirect_uri')
        );
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'xelite-repost-engine',
            'X Settings',
            'X Settings',
            'manage_options',
            'xelite-x-settings',
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
            <h1>X API Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('xelite_x_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Client ID</th>
                        <td>
                            <input type="text" name="xelite_x_client_id" value="<?php echo esc_attr(get_option('xelite_x_client_id', '')); ?>" class="regular-text">
                            <p class="description">Your X API Client ID from the X Developer Portal.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Client Secret</th>
                        <td>
                            <input type="password" name="xelite_x_client_secret" value="<?php echo esc_attr(get_option('xelite_x_client_secret', '')); ?>" class="regular-text">
                            <p class="description">Your X API Client Secret from the X Developer Portal.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect URI</th>
                        <td>
                            <input type="url" name="xelite_x_redirect_uri" value="<?php echo esc_attr(get_option('xelite_x_redirect_uri', '')); ?>" class="regular-text">
                            <p class="description">OAuth 2.0 redirect URI configured in your X app.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <?php if ($this->has_credentials()): ?>
                <div class="xelite-settings-section">
                    <h2>Authentication</h2>
                    <?php if ($this->is_user_authenticated()): ?>
                        <p>✅ You are authenticated with X.</p>
                        <button type="button" id="test-x-connection" class="button button-secondary">Test Connection</button>
                    <?php else: ?>
                        <p>❌ You are not authenticated with X.</p>
                        <a href="<?php echo esc_url($this->get_authorization_url()); ?>" class="button button-primary">Authenticate with X</a>
                    <?php endif; ?>
                </div>
                
                <div class="xelite-settings-section">
                    <h2>Test Posting</h2>
                    <textarea id="test-tweet-content" placeholder="Enter test tweet content..." rows="3" cols="50"></textarea>
                    <br><br>
                    <button type="button" id="test-post-tweet" class="button button-secondary">Post Test Tweet</button>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-x-connection').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'xelite_test_x_connection',
                        nonce: '<?php echo wp_create_nonce('xelite_x_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Connection successful! User: ' + response.data.user.username);
                        } else {
                            alert('Connection failed: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Connection test failed. Please try again.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Connection');
                    }
                });
            });
            
            $('#test-post-tweet').on('click', function() {
                var button = $(this);
                var content = $('#test-tweet-content').val();
                
                if (!content) {
                    alert('Please enter tweet content.');
                    return;
                }
                
                button.prop('disabled', true).text('Posting...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'xelite_post_to_x',
                        nonce: '<?php echo wp_create_nonce('xelite_x_nonce'); ?>',
                        content: content,
                        options: {}
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Tweet posted successfully! ID: ' + response.data.tweet_id);
                            $('#test-tweet-content').val('');
                        } else {
                            alert('Failed to post tweet: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Tweet posting failed. Please try again.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Post Test Tweet');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Sanitize client ID
     *
     * @param string $input Raw input.
     * @return string Sanitized input.
     */
    public function sanitize_client_id($input) {
        return sanitize_text_field($input);
    }

    /**
     * Sanitize client secret
     *
     * @param string $input Raw input.
     * @return string Sanitized input.
     */
    public function sanitize_client_secret($input) {
        return sanitize_text_field($input);
    }

    /**
     * Sanitize redirect URI
     *
     * @param string $input Raw input.
     * @return string Sanitized input.
     */
    public function sanitize_redirect_uri($input) {
        return esc_url_raw($input);
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
            $this->logger->log($level, "[X Poster] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine X Poster] {$message}");
        }
    }
} 