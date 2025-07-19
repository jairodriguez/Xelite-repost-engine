<?php
/**
 * X (Twitter) API Service Class
 *
 * Handles all API requests to X (Twitter) API including user timelines,
 * user information, and post details with caching and error handling.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * X API Service Class
 */
class XeliteRepostEngine_X_API {

    /**
     * Base URL for X API v2
     */
    const API_BASE_URL = 'https://api.twitter.com/2';

    /**
     * Cache expiration time in seconds (15 minutes)
     */
    const CACHE_EXPIRATION = 900;

    /**
     * Rate limit window in seconds (15 minutes)
     */
    const RATE_LIMIT_WINDOW = 900;

    /**
     * Maximum requests per rate limit window
     */
    const MAX_REQUESTS_PER_WINDOW = 300;

    /**
     * X Auth service instance
     *
     * @var XeliteRepostEngine_X_Auth
     */
    private $auth_service;

    /**
     * Logger instance
     *
     * @var XeliteRepostEngine_Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_X_Auth $auth_service Authentication service.
     * @param XeliteRepostEngine_Logger $logger Logger service.
     */
    public function __construct($auth_service, $logger = null) {
        $this->auth_service = $auth_service;
        $this->logger = $logger;
    }

    /**
     * Get user timeline
     *
     * @param string $user_id User ID to fetch timeline for.
     * @param int    $max_results Maximum number of results (default: 100).
     * @param string $pagination_token Pagination token for next page.
     * @return array|WP_Error Response data or WP_Error on failure.
     */
    public function get_user_timeline($user_id, $max_results = 100, $pagination_token = null) {
        $cache_key = "xelite_timeline_{$user_id}_{$max_results}";
        if ($pagination_token) {
            $cache_key .= "_{$pagination_token}";
        }

        // Check cache first
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            $this->log('info', "Retrieved timeline from cache for user {$user_id}");
            return $cached_data;
        }

        // Check rate limits
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded. Please try again later.');
        }

        $endpoint = "/users/{$user_id}/tweets";
        $params = array(
            'max_results' => min($max_results, 100), // API max is 100
            'tweet.fields' => 'created_at,public_metrics,entities,context_annotations',
            'expansions' => 'author_id,referenced_tweets',
            'user.fields' => 'username,name,profile_image_url,verified',
        );

        if ($pagination_token) {
            $params['pagination_token'] = $pagination_token;
        }

        $response = $this->make_api_request($endpoint, $params);

        if (is_wp_error($response)) {
            return $response;
        }

        // Normalize the response
        $normalized_data = $this->normalize_timeline_response($response);

        // Cache the response
        set_transient($cache_key, $normalized_data, self::CACHE_EXPIRATION);

        $this->log('info', "Fetched timeline for user {$user_id}, cached for " . self::CACHE_EXPIRATION . " seconds");

        return $normalized_data;
    }

    /**
     * Get user information
     *
     * @param string $user_id_or_username User ID or username.
     * @param bool   $is_username Whether the parameter is a username (default: false).
     * @return array|WP_Error Response data or WP_Error on failure.
     */
    public function get_user_info($user_id_or_username, $is_username = false) {
        $cache_key = "xelite_user_info_" . ($is_username ? "username_{$user_id_or_username}" : "id_{$user_id_or_username}");

        // Check cache first
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            $this->log('info', "Retrieved user info from cache for " . ($is_username ? "username" : "user ID") . " {$user_id_or_username}");
            return $cached_data;
        }

        // Check rate limits
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded. Please try again later.');
        }

        $endpoint = $is_username ? "/users/by/username/{$user_id_or_username}" : "/users/{$user_id_or_username}";
        $params = array(
            'user.fields' => 'id,username,name,description,profile_image_url,verified,public_metrics,created_at',
        );

        $response = $this->make_api_request($endpoint, $params);

        if (is_wp_error($response)) {
            return $response;
        }

        // Normalize the response
        $normalized_data = $this->normalize_user_response($response);

        // Cache the response (longer cache for user info)
        set_transient($cache_key, $normalized_data, self::CACHE_EXPIRATION * 2);

        $this->log('info', "Fetched user info for " . ($is_username ? "username" : "user ID") . " {$user_id_or_username}");

        return $normalized_data;
    }

    /**
     * Get tweet details
     *
     * @param string $tweet_id Tweet ID to fetch details for.
     * @return array|WP_Error Response data or WP_Error on failure.
     */
    public function get_tweet_details($tweet_id) {
        $cache_key = "xelite_tweet_{$tweet_id}";

        // Check cache first
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            $this->log('info', "Retrieved tweet details from cache for tweet {$tweet_id}");
            return $cached_data;
        }

        // Check rate limits
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded. Please try again later.');
        }

        $endpoint = "/tweets/{$tweet_id}";
        $params = array(
            'tweet.fields' => 'created_at,public_metrics,entities,context_annotations,conversation_id,referenced_tweets',
            'expansions' => 'author_id,referenced_tweets',
            'user.fields' => 'username,name,profile_image_url,verified',
        );

        $response = $this->make_api_request($endpoint, $params);

        if (is_wp_error($response)) {
            return $response;
        }

        // Normalize the response
        $normalized_data = $this->normalize_tweet_response($response);

        // Cache the response
        set_transient($cache_key, $normalized_data, self::CACHE_EXPIRATION);

        $this->log('info', "Fetched tweet details for tweet {$tweet_id}");

        return $normalized_data;
    }

    /**
     * Search tweets
     *
     * @param string $query Search query.
     * @param int    $max_results Maximum number of results (default: 100).
     * @param string $next_token Pagination token for next page.
     * @return array|WP_Error Response data or WP_Error on failure.
     */
    public function search_tweets($query, $max_results = 100, $next_token = null) {
        $cache_key = "xelite_search_" . md5($query . $max_results . $next_token);

        // Check cache first
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            $this->log('info', "Retrieved search results from cache for query: {$query}");
            return $cached_data;
        }

        // Check rate limits
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded. Please try again later.');
        }

        $endpoint = "/tweets/search/recent";
        $params = array(
            'query' => $query,
            'max_results' => min($max_results, 100), // API max is 100
            'tweet.fields' => 'created_at,public_metrics,entities,context_annotations',
            'expansions' => 'author_id,referenced_tweets',
            'user.fields' => 'username,name,profile_image_url,verified',
        );

        if ($next_token) {
            $params['next_token'] = $next_token;
        }

        $response = $this->make_api_request($endpoint, $params);

        if (is_wp_error($response)) {
            return $response;
        }

        // Normalize the response
        $normalized_data = $this->normalize_search_response($response);

        // Cache the response
        set_transient($cache_key, $normalized_data, self::CACHE_EXPIRATION);

        $this->log('info', "Searched tweets for query: {$query}");

        return $normalized_data;
    }

    /**
     * Make API request to X API
     *
     * @param string $endpoint API endpoint.
     * @param array  $params Query parameters.
     * @return array|WP_Error Response data or WP_Error on failure.
     */
    private function make_api_request($endpoint, $params = array()) {
        // Get credentials
        $credentials = $this->auth_service->get_credentials();
        if (is_wp_error($credentials)) {
            return $credentials;
        }

        // Build URL
        $url = self::API_BASE_URL . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // Prepare request
        $args = array(
            'method' => 'GET',
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'OAuth ' . $this->build_oauth_header($url, 'GET', $credentials),
                'Content-Type' => 'application/json',
            ),
        );

        $this->log('debug', "Making API request to: {$url}");

        // Make request
        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            $this->log('error', "API request failed: " . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        $this->log('debug', "API response code: {$response_code}");

        // Handle different response codes
        switch ($response_code) {
            case 200:
                $data = json_decode($response_body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->log('error', "Failed to decode JSON response: " . json_last_error_msg());
                    return new WP_Error('json_decode_error', 'Failed to decode API response');
                }
                return $data;

            case 401:
                $this->log('error', "Authentication failed for API request");
                return new WP_Error('authentication_failed', 'Authentication failed. Please check your API credentials.');

            case 403:
                $this->log('error', "Access forbidden for API request");
                return new WP_Error('access_forbidden', 'Access forbidden. Please check your API permissions.');

            case 429:
                $this->log('error', "Rate limit exceeded for API request");
                $this->update_rate_limit_counter();
                return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded. Please try again later.');

            default:
                $this->log('error', "API request failed with code {$response_code}: {$response_body}");
                return new WP_Error('api_error', "API request failed with code {$response_code}");
        }
    }

    /**
     * Build OAuth header for API request
     *
     * @param string $url Request URL.
     * @param string $method HTTP method.
     * @param array  $credentials API credentials.
     * @return string OAuth header string.
     */
    private function build_oauth_header($url, $method, $credentials) {
        $oauth_params = array(
            'oauth_consumer_key' => $credentials['consumer_key'],
            'oauth_nonce' => wp_generate_password(32, false),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $credentials['access_token'],
            'oauth_version' => '1.0',
        );

        // Create signature base string
        $base_string = $this->create_signature_base_string($url, $method, $oauth_params);
        
        // Create signing key
        $signing_key = $this->create_signing_key($credentials['consumer_secret'], $credentials['access_token_secret']);
        
        // Generate signature
        $signature = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
        
        // Add signature to OAuth parameters
        $oauth_params['oauth_signature'] = $signature;
        
        // Build OAuth header
        $oauth_header = 'OAuth ';
        $oauth_parts = array();
        foreach ($oauth_params as $key => $value) {
            $oauth_parts[] = $key . '="' . rawurlencode($value) . '"';
        }
        $oauth_header .= implode(', ', $oauth_parts);
        
        return $oauth_header;
    }

    /**
     * Create signature base string for OAuth
     *
     * @param string $url Request URL.
     * @param string $method HTTP method.
     * @param array  $oauth_params OAuth parameters.
     * @return string Signature base string.
     */
    private function create_signature_base_string($url, $method, $oauth_params) {
        // Parse URL to get base URL and query parameters
        $parsed_url = parse_url($url);
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
        
        // Get query parameters
        $query_params = array();
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
        }
        
        // Combine all parameters
        $all_params = array_merge($query_params, $oauth_params);
        
        // Sort parameters
        ksort($all_params);
        
        // Build parameter string
        $param_string = '';
        foreach ($all_params as $key => $value) {
            if ($param_string !== '') {
                $param_string .= '&';
            }
            $param_string .= rawurlencode($key) . '=' . rawurlencode($value);
        }
        
        // Create signature base string
        $base_string = strtoupper($method) . '&' . rawurlencode($base_url) . '&' . rawurlencode($param_string);
        
        return $base_string;
    }

    /**
     * Create signing key for OAuth
     *
     * @param string $consumer_secret Consumer secret.
     * @param string $access_token_secret Access token secret.
     * @return string Signing key.
     */
    private function create_signing_key($consumer_secret, $access_token_secret) {
        return rawurlencode($consumer_secret) . '&' . rawurlencode($access_token_secret);
    }

    /**
     * Check rate limits
     *
     * @return bool True if within rate limits, false otherwise.
     */
    private function check_rate_limit() {
        $rate_limit_key = 'xelite_api_rate_limit';
        $current_time = time();
        
        $rate_limit_data = get_transient($rate_limit_key);
        if ($rate_limit_data === false) {
            $rate_limit_data = array(
                'count' => 0,
                'window_start' => $current_time,
            );
        }
        
        // Check if we're in a new window
        if ($current_time - $rate_limit_data['window_start'] >= self::RATE_LIMIT_WINDOW) {
            $rate_limit_data = array(
                'count' => 0,
                'window_start' => $current_time,
            );
        }
        
        // Check if we've exceeded the limit
        if ($rate_limit_data['count'] >= self::MAX_REQUESTS_PER_WINDOW) {
            return false;
        }
        
        // Increment counter
        $rate_limit_data['count']++;
        set_transient($rate_limit_key, $rate_limit_data, self::RATE_LIMIT_WINDOW);
        
        return true;
    }

    /**
     * Update rate limit counter (for when we get 429 responses)
     */
    private function update_rate_limit_counter() {
        $rate_limit_key = 'xelite_api_rate_limit';
        $rate_limit_data = get_transient($rate_limit_key);
        
        if ($rate_limit_data !== false) {
            $rate_limit_data['count'] = self::MAX_REQUESTS_PER_WINDOW;
            set_transient($rate_limit_key, $rate_limit_data, self::RATE_LIMIT_WINDOW);
        }
    }

    /**
     * Normalize timeline response
     *
     * @param array $response Raw API response.
     * @return array Normalized response.
     */
    private function normalize_timeline_response($response) {
        $normalized = array(
            'tweets' => array(),
            'users' => array(),
            'meta' => array(),
        );

        if (isset($response['data'])) {
            foreach ($response['data'] as $tweet) {
                $normalized['tweets'][] = $this->normalize_tweet($tweet);
            }
        }

        if (isset($response['includes']['users'])) {
            foreach ($response['includes']['users'] as $user) {
                $normalized['users'][$user['id']] = $this->normalize_user($user);
            }
        }

        if (isset($response['meta'])) {
            $normalized['meta'] = $response['meta'];
        }

        return $normalized;
    }

    /**
     * Normalize user response
     *
     * @param array $response Raw API response.
     * @return array Normalized response.
     */
    private function normalize_user_response($response) {
        if (isset($response['data'])) {
            return $this->normalize_user($response['data']);
        }

        return array();
    }

    /**
     * Normalize tweet response
     *
     * @param array $response Raw API response.
     * @return array Normalized response.
     */
    private function normalize_tweet_response($response) {
        $normalized = array(
            'tweet' => array(),
            'users' => array(),
        );

        if (isset($response['data'])) {
            $normalized['tweet'] = $this->normalize_tweet($response['data']);
        }

        if (isset($response['includes']['users'])) {
            foreach ($response['includes']['users'] as $user) {
                $normalized['users'][$user['id']] = $this->normalize_user($user);
            }
        }

        return $normalized;
    }

    /**
     * Normalize search response
     *
     * @param array $response Raw API response.
     * @return array Normalized response.
     */
    private function normalize_search_response($response) {
        return $this->normalize_timeline_response($response);
    }

    /**
     * Normalize individual tweet
     *
     * @param array $tweet Raw tweet data.
     * @return array Normalized tweet.
     */
    private function normalize_tweet($tweet) {
        return array(
            'id' => $tweet['id'],
            'text' => $tweet['text'],
            'created_at' => isset($tweet['created_at']) ? $tweet['created_at'] : '',
            'author_id' => isset($tweet['author_id']) ? $tweet['author_id'] : '',
            'public_metrics' => isset($tweet['public_metrics']) ? $tweet['public_metrics'] : array(),
            'entities' => isset($tweet['entities']) ? $tweet['entities'] : array(),
            'context_annotations' => isset($tweet['context_annotations']) ? $tweet['context_annotations'] : array(),
            'conversation_id' => isset($tweet['conversation_id']) ? $tweet['conversation_id'] : '',
            'referenced_tweets' => isset($tweet['referenced_tweets']) ? $tweet['referenced_tweets'] : array(),
        );
    }

    /**
     * Normalize individual user
     *
     * @param array $user Raw user data.
     * @return array Normalized user.
     */
    private function normalize_user($user) {
        return array(
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'description' => isset($user['description']) ? $user['description'] : '',
            'profile_image_url' => isset($user['profile_image_url']) ? $user['profile_image_url'] : '',
            'verified' => isset($user['verified']) ? $user['verified'] : false,
            'public_metrics' => isset($user['public_metrics']) ? $user['public_metrics'] : array(),
            'created_at' => isset($user['created_at']) ? $user['created_at'] : '',
        );
    }

    /**
     * Log message
     *
     * @param string $level Log level.
     * @param string $message Log message.
     */
    private function log($level, $message) {
        if ($this->logger) {
            $this->logger->log($level, $message);
        } else {
            // Fallback to WordPress error log
            error_log("XeliteRepostEngine X API [{$level}]: {$message}");
        }
    }

    /**
     * Clear cache for specific user
     *
     * @param string $user_id User ID to clear cache for.
     */
    public function clear_user_cache($user_id) {
        global $wpdb;
        
        // Get all transients that match the user pattern
        $transients = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_xelite_timeline_' . $user_id . '%'
            )
        );
        
        foreach ($transients as $transient) {
            $transient_name = str_replace('_transient_', '', $transient);
            delete_transient($transient_name);
        }
        
        // Clear user info cache
        delete_transient("xelite_user_info_id_{$user_id}");
        delete_transient("xelite_user_info_username_{$user_id}");
        
        $this->log('info', "Cleared cache for user {$user_id}");
    }

    /**
     * Clear all API cache
     */
    public function clear_all_cache() {
        global $wpdb;
        
        // Get all transients that match our pattern
        $transients = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_xelite_%'"
        );
        
        foreach ($transients as $transient) {
            $transient_name = str_replace('_transient_', '', $transient);
            delete_transient($transient_name);
        }
        
        $this->log('info', 'Cleared all API cache');
    }

    /**
     * Get rate limit status
     *
     * @return array Rate limit information.
     */
    public function get_rate_limit_status() {
        $rate_limit_key = 'xelite_api_rate_limit';
        $rate_limit_data = get_transient($rate_limit_key);
        
        if ($rate_limit_data === false) {
            return array(
                'remaining' => self::MAX_REQUESTS_PER_WINDOW,
                'reset_time' => time() + self::RATE_LIMIT_WINDOW,
                'window_start' => time(),
            );
        }
        
        $remaining = max(0, self::MAX_REQUESTS_PER_WINDOW - $rate_limit_data['count']);
        $reset_time = $rate_limit_data['window_start'] + self::RATE_LIMIT_WINDOW;
        
        return array(
            'remaining' => $remaining,
            'reset_time' => $reset_time,
            'window_start' => $rate_limit_data['window_start'],
        );
    }
} 