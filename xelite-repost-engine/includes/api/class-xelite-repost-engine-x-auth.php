<?php
/**
 * X (Twitter) API Authentication Class
 *
 * Handles OAuth authentication for X API integration with secure credential storage
 * and connection testing functionality.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * X API Authentication Class
 *
 * Manages X API authentication including OAuth flow, credential storage,
 * connection testing, and admin integration.
 */
class XeliteRepostEngine_X_Auth extends XeliteRepostEngine_Abstract_Base {
    
    /**
     * Option name for storing encrypted credentials
     *
     * @var string
     */
    private $credentials_option = 'xelite_repost_engine_x_credentials';
    
    /**
     * Option name for storing OAuth state
     *
     * @var string
     */
    private $oauth_state_option = 'xelite_repost_engine_x_oauth_state';
    
    /**
     * X API endpoints
     *
     * @var array
     */
    private $api_endpoints = array(
        'request_token' => 'https://api.twitter.com/oauth/request_token',
        'authorize' => 'https://api.twitter.com/oauth/authorize',
        'access_token' => 'https://api.twitter.com/oauth/access_token',
        'verify_credentials' => 'https://api.twitter.com/1.1/account/verify_credentials.json'
    );
    
    /**
     * Initialize the class
     */
    protected function init() {
        // Add admin hooks
        add_action('admin_init', array($this, 'handle_oauth_callback'));
        add_action('wp_ajax_xelite_test_x_connection', array($this, 'handle_test_connection'));
        add_action('wp_ajax_xelite_revoke_x_connection', array($this, 'handle_revoke_connection'));
        
        $this->log_debug('X Auth class initialized');
    }
    
    /**
     * Get stored credentials
     *
     * @return array|false Credentials array or false if not found
     */
    public function get_credentials() {
        // Debug: Log what we're checking
        error_log('Xelite Debug: Checking for X credentials...');
        
        // First try to get encrypted credentials (legacy)
        $encrypted_credentials = get_option($this->credentials_option);
        
        if ($encrypted_credentials) {
            error_log('Xelite Debug: Found encrypted credentials');
            try {
                $credentials = $this->decrypt_data($encrypted_credentials);
                return json_decode($credentials, true);
            } catch (Exception $e) {
                $this->log_error('Failed to decrypt X credentials: ' . $e->getMessage());
            }
        }
        
        // Fallback to main settings (new method)
        $settings = get_option('xelite_repost_engine_settings', array());
        error_log('Xelite Debug: Settings keys found: ' . implode(', ', array_keys($settings)));
        
        if (!empty($settings['x_api_consumer_key']) && 
            !empty($settings['x_api_consumer_secret']) && 
            !empty($settings['x_api_access_token']) && 
            !empty($settings['x_api_access_token_secret'])) {
            
            error_log('Xelite Debug: Found API credentials in settings');
            return array(
                'consumer_key' => $settings['x_api_consumer_key'],
                'consumer_secret' => $settings['x_api_consumer_secret'],
                'access_token' => $settings['x_api_access_token'],
                'access_token_secret' => $settings['x_api_access_token_secret']
            );
        }
        
        error_log('Xelite Debug: No credentials found');
        return false;
    }
    
    /**
     * Store credentials securely
     *
     * @param array $credentials Credentials array
     * @return bool Success status
     */
    public function store_credentials($credentials) {
        if (!is_array($credentials) || empty($credentials)) {
            return false;
        }
        
        // Validate required fields
        $required_fields = array('consumer_key', 'consumer_secret', 'access_token', 'access_token_secret');
        foreach ($required_fields as $field) {
            if (empty($credentials[$field])) {
                $this->log_error("Missing required credential field: {$field}");
                return false;
            }
        }
        
        try {
            $encrypted_credentials = $this->encrypt_data(json_encode($credentials));
            $result = update_option($this->credentials_option, $encrypted_credentials);
            
            if ($result) {
                $this->log_debug('X credentials stored successfully');
                // Clear any cached connection status
                delete_transient('xelite_x_connection_status');
            }
            
            return $result;
        } catch (Exception $e) {
            $this->log_error('Failed to encrypt and store X credentials: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete stored credentials
     *
     * @return bool Success status
     */
    public function delete_credentials() {
        $result = delete_option($this->credentials_option);
        
        if ($result) {
            $this->log_debug('X credentials deleted successfully');
            // Clear cached connection status
            delete_transient('xelite_x_connection_status');
        }
        
        return $result;
    }
    
    /**
     * Check if credentials are stored
     *
     * @return bool True if credentials exist
     */
    public function has_credentials() {
        return (bool) get_option($this->credentials_option);
    }
    
    /**
     * Test X API connection
     *
     * @return array Connection test result
     */
    public function test_connection() {
        $credentials = $this->get_credentials();
        
        if (!$credentials) {
            return array(
                'success' => false,
                'message' => __('No X API credentials found.', 'xelite-repost-engine'),
                'error_code' => 'no_credentials'
            );
        }
        
        try {
            $response = $this->make_api_request(
                $this->api_endpoints['verify_credentials'],
                'GET',
                array(),
                $credentials
            );
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => $response->get_error_message(),
                    'error_code' => 'api_error'
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['errors'])) {
                $error_message = isset($data['errors'][0]['message']) 
                    ? $data['errors'][0]['message'] 
                    : __('Unknown API error', 'xelite-repost-engine');
                
                return array(
                    'success' => false,
                    'message' => $error_message,
                    'error_code' => 'api_error'
                );
            }
            
            // Store successful connection status
            set_transient('xelite_x_connection_status', array(
                'success' => true,
                'timestamp' => time(),
                'user_info' => $data
            ), 3600); // Cache for 1 hour
            
            return array(
                'success' => true,
                'message' => sprintf(
                    __('Connection successful! Authenticated as @%s', 'xelite-repost-engine'),
                    $data['screen_name']
                ),
                'user_info' => $data
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'exception'
            );
        }
    }
    
    /**
     * Get cached connection status
     *
     * @return array|false Connection status or false if not cached
     */
    public function get_connection_status() {
        return get_transient('xelite_x_connection_status');
    }
    
    /**
     * Generate OAuth signature
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $params Request parameters
     * @param array $credentials OAuth credentials
     * @return string OAuth signature
     */
    private function generate_oauth_signature($method, $url, $params, $credentials) {
        // Add OAuth parameters
        $oauth_params = array(
            'oauth_consumer_key' => $credentials['consumer_key'],
            'oauth_nonce' => wp_generate_password(32, false),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $credentials['access_token'],
            'oauth_version' => '1.0'
        );
        
        // Merge all parameters
        $all_params = array_merge($params, $oauth_params);
        ksort($all_params);
        
        // Build parameter string
        $param_string = '';
        foreach ($all_params as $key => $value) {
            $param_string .= rawurlencode($key) . '=' . rawurlencode($value) . '&';
        }
        $param_string = rtrim($param_string, '&');
        
        // Build signature base string
        $signature_base = strtoupper($method) . '&' . 
                         rawurlencode($url) . '&' . 
                         rawurlencode($param_string);
        
        // Generate signing key
        $signing_key = rawurlencode($credentials['consumer_secret']) . '&' . 
                      rawurlencode($credentials['access_token_secret']);
        
        // Generate signature
        return base64_encode(hash_hmac('sha1', $signature_base, $signing_key, true));
    }
    
    /**
     * Make authenticated API request
     *
     * @param string $url Request URL
     * @param string $method HTTP method
     * @param array $params Request parameters
     * @param array $credentials OAuth credentials
     * @return WP_Error|array Response or error
     */
    private function make_api_request($url, $method = 'GET', $params = array(), $credentials = null) {
        if (!$credentials) {
            $credentials = $this->get_credentials();
        }
        
        if (!$credentials) {
            return new WP_Error('no_credentials', __('No X API credentials available.', 'xelite-repost-engine'));
        }
        
        // Generate OAuth signature
        $oauth_signature = $this->generate_oauth_signature($method, $url, $params, $credentials);
        
        // Build OAuth header
        $oauth_header = 'OAuth ';
        $oauth_params = array(
            'oauth_consumer_key' => $credentials['consumer_key'],
            'oauth_nonce' => wp_generate_password(32, false),
            'oauth_signature' => $oauth_signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $credentials['access_token'],
            'oauth_version' => '1.0'
        );
        
        foreach ($oauth_params as $key => $value) {
            $oauth_header .= rawurlencode($key) . '="' . rawurlencode($value) . '", ';
        }
        $oauth_header = rtrim($oauth_header, ', ');
        
        // Prepare request
        $request_args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => $oauth_header,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'timeout' => 30
        );
        
        // Add parameters to request
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        } elseif ($method === 'POST' && !empty($params)) {
            $request_args['body'] = http_build_query($params);
        }
        
        // Make request
        $response = wp_remote_request($url, $request_args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf(__('X API request failed with status %d', 'xelite-repost-engine'), $status_code)
            );
        }
        
        return $response;
    }
    
    /**
     * Encrypt data
     *
     * @param string $data Data to encrypt
     * @return string Encrypted data
     */
    private function encrypt_data($data) {
        $key = wp_salt('auth');
        $method = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        
        if ($encrypted === false) {
            throw new Exception('Encryption failed');
        }
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     *
     * @param string $encrypted_data Encrypted data
     * @return string Decrypted data
     */
    private function decrypt_data($encrypted_data) {
        $key = wp_salt('auth');
        $method = 'AES-256-CBC';
        
        $data = base64_decode($encrypted_data);
        $iv_length = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);
        
        if ($decrypted === false) {
            throw new Exception('Decryption failed');
        }
        
        return $decrypted;
    }
    
    /**
     * Handle OAuth callback
     */
    public function handle_oauth_callback() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'xelite-repost-engine') {
            return;
        }
        
        if (!isset($_GET['tab']) || $_GET['tab'] !== 'x-api') {
            return;
        }
        
        // Handle OAuth callback
        if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])) {
            $this->process_oauth_callback();
        }
    }
    
    /**
     * Process OAuth callback
     */
    private function process_oauth_callback() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'xelite-repost-engine'));
        }
        
        // Verify nonce if needed
        if (isset($_GET['_wpnonce']) && !wp_verify_nonce($_GET['_wpnonce'], 'xelite_x_oauth')) {
            wp_die(__('Security check failed.', 'xelite-repost-engine'));
        }
        
        $oauth_token = sanitize_text_field($_GET['oauth_token']);
        $oauth_verifier = sanitize_text_field($_GET['oauth_verifier']);
        
        // Get stored request token
        $stored_state = get_option($this->oauth_state_option);
        
        if (!$stored_state || $stored_state['oauth_token'] !== $oauth_token) {
            add_settings_error(
                'xelite_repost_engine',
                'oauth_error',
                __('Invalid OAuth token.', 'xelite-repost-engine'),
                'error'
            );
            return;
        }
        
        // Exchange for access token
        $credentials = $this->exchange_oauth_token($oauth_token, $oauth_verifier, $stored_state);
        
        if ($credentials) {
            // Store credentials
            if ($this->store_credentials($credentials)) {
                add_settings_error(
                    'xelite_repost_engine',
                    'oauth_success',
                    __('X API authentication successful!', 'xelite-repost-engine'),
                    'success'
                );
            } else {
                add_settings_error(
                    'xelite_repost_engine',
                    'oauth_error',
                    __('Failed to store X API credentials.', 'xelite-repost-engine'),
                    'error'
                );
            }
        } else {
            add_settings_error(
                'xelite_repost_engine',
                'oauth_error',
                __('Failed to complete X API authentication.', 'xelite-repost-engine'),
                'error'
            );
        }
        
        // Clean up OAuth state
        delete_option($this->oauth_state_option);
    }
    
    /**
     * Exchange OAuth token for access token
     *
     * @param string $oauth_token OAuth token
     * @param string $oauth_verifier OAuth verifier
     * @param array $stored_state Stored OAuth state
     * @return array|false Credentials or false on failure
     */
    private function exchange_oauth_token($oauth_token, $oauth_verifier, $stored_state) {
        $params = array(
            'oauth_token' => $oauth_token,
            'oauth_verifier' => $oauth_verifier
        );
        
        $response = wp_remote_post($this->api_endpoints['access_token'], array(
            'body' => $params,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        parse_str($body, $response_params);
        
        if (!isset($response_params['oauth_token']) || !isset($response_params['oauth_token_secret'])) {
            return false;
        }
        
        return array(
            'consumer_key' => $stored_state['consumer_key'],
            'consumer_secret' => $stored_state['consumer_secret'],
            'access_token' => $response_params['oauth_token'],
            'access_token_secret' => $response_params['oauth_token_secret']
        );
    }
    
    /**
     * Handle AJAX test connection request
     */
    public function handle_test_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_x_test_connection')) {
            wp_send_json_error(__('Security check failed.', 'xelite-repost-engine'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'xelite-repost-engine'));
        }
        
        $result = $this->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle AJAX revoke connection request
     */
    public function handle_revoke_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_x_revoke_connection')) {
            wp_send_json_error(__('Security check failed.', 'xelite-repost-engine'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'xelite-repost-engine'));
        }
        
        $result = $this->delete_credentials();
        
        if ($result) {
            wp_send_json_success(__('X API connection revoked successfully.', 'xelite-repost-engine'));
        } else {
            wp_send_json_error(__('Failed to revoke X API connection.', 'xelite-repost-engine'));
        }
    }
    
    /**
     * Get admin settings fields
     *
     * @return array Settings fields configuration
     */
    public function get_admin_settings_fields() {
        $credentials = $this->get_credentials();
        $connection_status = $this->get_connection_status();
        
        return array(
            'x_api_section' => array(
                'title' => __('X (Twitter) API Settings', 'xelite-repost-engine'),
                'description' => __('Configure X API authentication for repost analysis and content generation.', 'xelite-repost-engine'),
                'fields' => array(
                    'x_api_consumer_key' => array(
                        'title' => __('API Key (Consumer Key)', 'xelite-repost-engine'),
                        'type' => 'password',
                        'description' => __('Your X API consumer key from the X Developer Portal.', 'xelite-repost-engine'),
                        'value' => $credentials ? $credentials['consumer_key'] : '',
                        'required' => true
                    ),
                    'x_api_consumer_secret' => array(
                        'title' => __('API Secret (Consumer Secret)', 'xelite-repost-engine'),
                        'type' => 'password',
                        'description' => __('Your X API consumer secret from the X Developer Portal.', 'xelite-repost-engine'),
                        'value' => $credentials ? $credentials['consumer_secret'] : '',
                        'required' => true
                    ),
                    'x_api_access_token' => array(
                        'title' => __('Access Token', 'xelite-repost-engine'),
                        'type' => 'password',
                        'description' => __('Your X API access token from the X Developer Portal.', 'xelite-repost-engine'),
                        'value' => $credentials ? $credentials['access_token'] : '',
                        'required' => true
                    ),
                    'x_api_access_token_secret' => array(
                        'title' => __('Access Token Secret', 'xelite-repost-engine'),
                        'type' => 'password',
                        'description' => __('Your X API access token secret from the X Developer Portal.', 'xelite-repost-engine'),
                        'value' => $credentials ? $credentials['access_token_secret'] : '',
                        'required' => true
                    )
                )
            ),
            'x_api_connection' => array(
                'title' => __('Connection Status', 'xelite-repost-engine'),
                'description' => __('Test your X API connection and manage authentication.', 'xelite-repost-engine'),
                'fields' => array(
                    'x_api_connection_status' => array(
                        'title' => __('Status', 'xelite-repost-engine'),
                        'type' => 'connection_status',
                        'value' => $connection_status,
                        'test_action' => 'xelite_test_x_connection',
                        'revoke_action' => 'xelite_revoke_x_connection',
                        'test_nonce' => wp_create_nonce('xelite_x_test_connection'),
                        'revoke_nonce' => wp_create_nonce('xelite_x_revoke_connection')
                    )
                )
            )
        );
    }
} 