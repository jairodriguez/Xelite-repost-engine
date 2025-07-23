<?php
/**
 * Extension API Handler for Xelite Repost Engine
 * 
 * Handles communication between the Chrome extension and WordPress plugin
 * including authentication, data submission, and fallback status checks
 * 
 * @package XeliteRepostEngine
 * @subpackage API
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extension API Handler Class
 * 
 * @since 1.0.0
 */
class XeliteRepostEngine_Extension_API extends XeliteRepostEngine_Abstract_Base {

    /**
     * API namespace
     */
    const API_NAMESPACE = 'repost-intelligence/v1';

    /**
     * Extension token option name
     */
    const EXTENSION_TOKEN_OPTION = 'xelite_extension_tokens';

    /**
     * Initialize the extension API
     */
    public function __construct() {
        parent::__construct();
        $this->init();
    }

    /**
     * Initialize the API
     */
    protected function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
        $this->log_debug('Extension API initialized');
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Extension authentication endpoint
        register_rest_route(self::API_NAMESPACE, '/extension-auth', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_extension_auth'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        // Extension data submission endpoint
        register_rest_route(self::API_NAMESPACE, '/extension-data', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_extension_data'),
            'permission_callback' => array($this, 'verify_extension_token'),
            'args' => array(
                'extension_token' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'data' => array(
                    'required' => true,
                    'type' => 'object',
                ),
                'timestamp' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'url' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw',
                ),
            ),
        ));

        // Fallback status check endpoint
        register_rest_route(self::API_NAMESPACE, '/fallback-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_fallback_status'),
            'permission_callback' => array($this, 'verify_extension_token'),
        ));

        // Extension status endpoint
        register_rest_route(self::API_NAMESPACE, '/extension-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_extension_status'),
            'permission_callback' => array($this, 'verify_extension_token'),
        ));

        $this->log_debug('Extension API routes registered');
    }

    /**
     * Handle extension authentication
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_extension_auth($request) {
        try {
            $username = $request->get_param('username');
            $password = $request->get_param('password');

            // Verify user credentials
            $user = wp_authenticate($username, $password);
            
            if (is_wp_error($user)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => 'Invalid credentials'
                ), 401);
            }

            // Check if user has appropriate capabilities
            if (!current_user_can('edit_posts')) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => 'Insufficient permissions'
                ), 403);
            }

            // Generate extension token
            $extension_token = $this->generate_extension_token($user->ID);
            
            // Store token
            $this->store_extension_token($user->ID, $extension_token);

            $this->log_debug("Extension authenticated for user: {$user->ID}");

            return new WP_REST_Response(array(
                'success' => true,
                'extension_token' => $extension_token,
                'api_endpoint' => rest_url(self::API_NAMESPACE . '/extension-data'),
                'user_id' => $user->ID,
                'user_name' => $user->display_name,
                'site_url' => get_site_url(),
            ));

        } catch (Exception $e) {
            $this->log_error('Extension auth error: ' . $e->getMessage());
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Authentication failed'
            ), 500);
        }
    }

    /**
     * Handle extension data submission
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_extension_data($request) {
        try {
            $extension_token = $request->get_param('extension_token');
            $data = $request->get_param('data');
            $timestamp = $request->get_param('timestamp');
            $url = $request->get_param('url');

            // Get user ID from token
            $user_id = $this->get_user_from_token($extension_token);
            if (!$user_id) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => 'Invalid extension token'
                ), 401);
            }

            // Validate and sanitize data
            $sanitized_data = $this->sanitize_extension_data($data);
            
            // Store extension data
            $extension_data_id = $this->store_extension_data($user_id, $url, $timestamp, $sanitized_data);
            
            // Process posts
            $posts_processed = $this->process_extension_posts($extension_data_id, $sanitized_data['posts']);

            $this->log_debug("Extension data processed: {$posts_processed} posts for user {$user_id}");

            return new WP_REST_Response(array(
                'success' => true,
                'extension_data_id' => $extension_data_id,
                'posts_processed' => $posts_processed,
                'message' => 'Data received and processed successfully'
            ));

        } catch (Exception $e) {
            $this->log_error('Extension data error: ' . $e->getMessage());
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Data processing failed'
            ), 500);
        }
    }

    /**
     * Handle fallback status check
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_fallback_status($request) {
        try {
            $extension_token = $request->get_header('X-Extension-Token');
            
            // Get user ID from token
            $user_id = $this->get_user_from_token($extension_token);
            if (!$user_id) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => 'Invalid extension token'
                ), 401);
            }

            // Check if fallback should be used
            $should_use_fallback = $this->should_use_fallback($user_id);
            $reason = $this->get_fallback_reason($user_id);
            $api_limits = $this->get_api_limits($user_id);

            return new WP_REST_Response(array(
                'success' => true,
                'should_use_fallback' => $should_use_fallback,
                'reason' => $reason,
                'api_limits' => $api_limits,
            ));

        } catch (Exception $e) {
            $this->log_error('Fallback status error: ' . $e->getMessage());
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Failed to check fallback status'
            ), 500);
        }
    }

    /**
     * Handle extension status
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_extension_status($request) {
        try {
            $extension_token = $request->get_header('X-Extension-Token');
            
            // Get user ID from token
            $user_id = $this->get_user_from_token($extension_token);
            if (!$user_id) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => 'Invalid extension token'
                ), 401);
            }

            // Get extension statistics
            $stats = $this->get_extension_stats($user_id);

            return new WP_REST_Response(array(
                'success' => true,
                'stats' => $stats,
            ));

        } catch (Exception $e) {
            $this->log_error('Extension status error: ' . $e->getMessage());
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Failed to get extension status'
            ), 500);
        }
    }

    /**
     * Verify extension token
     * 
     * @param WP_REST_Request $request
     * @return bool
     */
    public function verify_extension_token($request) {
        $extension_token = $request->get_header('X-Extension-Token');
        if (!$extension_token) {
            $extension_token = $request->get_param('extension_token');
        }
        
        return !empty($extension_token) && $this->get_user_from_token($extension_token);
    }

    /**
     * Generate extension token
     * 
     * @param int $user_id
     * @return string
     */
    private function generate_extension_token($user_id) {
        $token_data = array(
            'user_id' => $user_id,
            'created' => time(),
            'nonce' => wp_create_nonce('xelite_extension_token_' . $user_id)
        );
        
        return base64_encode(json_encode($token_data));
    }

    /**
     * Store extension token
     * 
     * @param int $user_id
     * @param string $token
     */
    private function store_extension_token($user_id, $token) {
        $tokens = get_option(self::EXTENSION_TOKEN_OPTION, array());
        $tokens[$token] = array(
            'user_id' => $user_id,
            'created' => time(),
            'last_used' => time()
        );
        
        update_option(self::EXTENSION_TOKEN_OPTION, $tokens);
    }

    /**
     * Get user ID from token
     * 
     * @param string $token
     * @return int|false
     */
    private function get_user_from_token($token) {
        $tokens = get_option(self::EXTENSION_TOKEN_OPTION, array());
        
        if (!isset($tokens[$token])) {
            return false;
        }

        // Update last used time
        $tokens[$token]['last_used'] = time();
        update_option(self::EXTENSION_TOKEN_OPTION, $tokens);

        return $tokens[$token]['user_id'];
    }

    /**
     * Sanitize extension data
     * 
     * @param array $data
     * @return array
     */
    private function sanitize_extension_data($data) {
        $sanitized = array(
            'posts' => array(),
            'timestamp' => intval($data['timestamp'] ?? time()),
            'url' => esc_url_raw($data['url'] ?? ''),
            'userAgent' => sanitize_text_field($data['userAgent'] ?? ''),
            'totalPostsFound' => intval($data['totalPostsFound'] ?? 0),
            'newPostsFound' => intval($data['newPostsFound'] ?? 0)
        );

        // Sanitize posts
        if (isset($data['posts']) && is_array($data['posts'])) {
            foreach ($data['posts'] as $post) {
                $sanitized_post = array(
                    'text' => sanitize_textarea_field($post['text'] ?? ''),
                    'author' => sanitize_text_field($post['author'] ?? ''),
                    'username' => sanitize_text_field($post['username'] ?? ''),
                    'timestamp' => sanitize_text_field($post['timestamp'] ?? ''),
                    'url' => esc_url_raw($post['url'] ?? ''),
                    'engagement' => array(
                        'likes' => intval($post['engagement']['likes'] ?? 0),
                        'retweets' => intval($post['engagement']['retweets'] ?? 0),
                        'replies' => intval($post['engagement']['replies'] ?? 0),
                        'views' => intval($post['engagement']['views'] ?? 0)
                    ),
                    'media' => array(),
                    'hashtags' => array(),
                    'mentions' => array(),
                    'isRetweet' => boolval($post['isRetweet'] ?? false),
                    'isReply' => boolval($post['isReply'] ?? false),
                    'isQuote' => boolval($post['isQuote'] ?? false),
                    'postType' => sanitize_text_field($post['postType'] ?? 'tweet')
                );

                // Sanitize arrays
                if (isset($post['media']) && is_array($post['media'])) {
                    foreach ($post['media'] as $media) {
                        $sanitized_post['media'][] = array(
                            'type' => sanitize_text_field($media['type'] ?? ''),
                            'url' => esc_url_raw($media['url'] ?? ''),
                            'alt' => sanitize_text_field($media['alt'] ?? '')
                        );
                    }
                }

                if (isset($post['hashtags']) && is_array($post['hashtags'])) {
                    $sanitized_post['hashtags'] = array_map('sanitize_text_field', $post['hashtags']);
                }

                if (isset($post['mentions']) && is_array($post['mentions'])) {
                    $sanitized_post['mentions'] = array_map('sanitize_text_field', $post['mentions']);
                }

                $sanitized['posts'][] = $sanitized_post;
            }
        }

        return $sanitized;
    }

    /**
     * Store extension data
     * 
     * @param int $user_id
     * @param string $url
     * @param int $timestamp
     * @param array $data
     * @return int
     */
    private function store_extension_data($user_id, $url, $timestamp, $data) {
        global $wpdb;
        
        $table_name = $this->get_container()->get('database')->get_table_name('extension_data');
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'source_url' => $url,
                'timestamp' => date('Y-m-d H:i:s', $timestamp),
                'total_posts' => $data['totalPostsFound'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            throw new Exception('Failed to store extension data: ' . $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    /**
     * Process extension posts
     * 
     * @param int $extension_data_id
     * @param array $posts
     * @return int
     */
    private function process_extension_posts($extension_data_id, $posts) {
        global $wpdb;
        
        $table_name = $this->get_container()->get('database')->get_table_name('extension_posts');
        $processed = 0;

        foreach ($posts as $post) {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'extension_data_id' => $extension_data_id,
                    'post_text' => $post['text'],
                    'author_name' => $post['author'],
                    'author_username' => $post['username'],
                    'post_timestamp' => $post['timestamp'],
                    'post_url' => $post['url'],
                    'engagement_data' => json_encode($post['engagement']),
                    'media_data' => json_encode($post['media']),
                    'hashtags' => json_encode($post['hashtags']),
                    'mentions' => json_encode($post['mentions']),
                    'post_type' => $post['postType'],
                    'is_retweet' => $post['isRetweet'] ? 1 : 0,
                    'is_reply' => $post['isReply'] ? 1 : 0,
                    'is_quote' => $post['isQuote'] ? 1 : 0,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s')
            );

            if ($result !== false) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Check if fallback should be used
     * 
     * @param int $user_id
     * @return bool
     */
    private function should_use_fallback($user_id) {
        // Check API limits and usage
        $api_limits = $this->get_api_limits($user_id);
        
        // Use fallback if API is rate limited or over quota
        return $api_limits['is_rate_limited'] || $api_limits['is_over_quota'];
    }

    /**
     * Get fallback reason
     * 
     * @param int $user_id
     * @return string
     */
    private function get_fallback_reason($user_id) {
        $api_limits = $this->get_api_limits($user_id);
        
        if ($api_limits['is_rate_limited']) {
            return 'API rate limit exceeded';
        }
        
        if ($api_limits['is_over_quota']) {
            return 'API quota exceeded';
        }
        
        return 'API available';
    }

    /**
     * Get API limits
     * 
     * @param int $user_id
     * @return array
     */
    private function get_api_limits($user_id) {
        // This would integrate with your actual API provider
        // For now, return mock data
        return array(
            'is_rate_limited' => false,
            'is_over_quota' => false,
            'requests_remaining' => 1000,
            'reset_time' => time() + 3600
        );
    }

    /**
     * Get extension statistics
     * 
     * @param int $user_id
     * @return array
     */
    private function get_extension_stats($user_id) {
        global $wpdb;
        
        $extension_data_table = $this->get_container()->get('database')->get_table_name('extension_data');
        $extension_posts_table = $this->get_container()->get('database')->get_table_name('extension_posts');
        
        $total_scrapes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$extension_data_table} WHERE user_id = %d",
            $user_id
        ));
        
        $total_posts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$extension_posts_table} ep 
             JOIN {$extension_data_table} ed ON ep.extension_data_id = ed.id 
             WHERE ed.user_id = %d",
            $user_id
        ));
        
        $last_scrape = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(created_at) FROM {$extension_data_table} WHERE user_id = %d",
            $user_id
        ));
        
        return array(
            'total_scrapes' => intval($total_scrapes),
            'total_posts' => intval($total_posts),
            'last_scrape' => $last_scrape,
            'user_id' => $user_id
        );
    }
} 