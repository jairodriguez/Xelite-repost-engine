<?php
/**
 * API functionality class
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API functionality class
 */
class XeliteRepostEngine_API extends XeliteRepostEngine_Abstract_Base implements XeliteRepostEngine_API_Interface {
    
    /**
     * API credentials
     *
     * @var array
     */
    private $credentials = array();
    
    /**
     * API base URL
     *
     * @var string
     */
    private $base_url = '';
    
    /**
     * Authentication token
     *
     * @var string
     */
    private $auth_token = '';
    
    /**
     * Initialize the class
     */
    protected function init() {
        $this->load_credentials();
        $this->log_debug('API class initialized');
    }
    
    /**
     * Load API credentials from options
     */
    private function load_credentials() {
        $this->credentials = array(
            'x_api_key' => $this->get_option('x_api_key', ''),
            'x_api_secret' => $this->get_option('x_api_secret', ''),
            'x_bearer_token' => $this->get_option('x_bearer_token', ''),
            'openai_api_key' => $this->get_option('openai_api_key', ''),
        );
        
        $this->base_url = 'https://api.twitter.com/2';
    }
    
    /**
     * Authenticate with the API
     *
     * @param array $credentials API credentials
     * @return bool
     */
    public function authenticate($credentials) {
        $this->credentials = array_merge($this->credentials, $credentials);
        
        // For X API, we'll use bearer token authentication
        if (!empty($this->credentials['x_bearer_token'])) {
            $this->auth_token = $this->credentials['x_bearer_token'];
            return true;
        }
        
        // For OpenAI API, we'll test the connection
        if (!empty($this->credentials['openai_api_key'])) {
            return $this->test_openai_connection();
        }
        
        return false;
    }
    
    /**
     * Test API connection
     *
     * @return bool
     */
    public function test_connection() {
        // Test X API connection
        if (!empty($this->credentials['x_bearer_token'])) {
            $response = $this->get('/users/by/username/twitter');
            if (!is_wp_error($response)) {
                return true;
            }
        }
        
        // Test OpenAI API connection
        if (!empty($this->credentials['openai_api_key'])) {
            return $this->test_openai_connection();
        }
        
        return false;
    }
    
    /**
     * Test OpenAI API connection
     *
     * @return bool
     */
    private function test_openai_connection() {
        $response = wp_remote_post('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->credentials['openai_api_key'],
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            $this->log_error('OpenAI API connection failed', array(
                'error' => $response->get_error_message()
            ));
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code === 200;
    }
    
    /**
     * Get API response
     *
     * @param string $endpoint API endpoint
     * @param array  $params   Request parameters
     * @return array|WP_Error
     */
    public function get($endpoint, $params = array()) {
        $url = $this->base_url . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->auth_token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            $this->handle_error($response);
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error = new WP_Error(
                'api_error',
                'API request failed',
                array(
                    'status_code' => $status_code,
                    'response' => $data,
                )
            );
            $this->handle_error($error);
            return $error;
        }
        
        $this->log_debug('API GET request successful', array(
            'endpoint' => $endpoint,
            'status_code' => $status_code
        ));
        
        return $data;
    }
    
    /**
     * Post to API
     *
     * @param string $endpoint API endpoint
     * @param array  $data     Request data
     * @return array|WP_Error
     */
    public function post($endpoint, $data = array()) {
        $url = $this->base_url . $endpoint;
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->auth_token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            $this->handle_error($response);
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code < 200 || $status_code >= 300) {
            $error = new WP_Error(
                'api_error',
                'API request failed',
                array(
                    'status_code' => $status_code,
                    'response' => $data,
                )
            );
            $this->handle_error($error);
            return $error;
        }
        
        $this->log_debug('API POST request successful', array(
            'endpoint' => $endpoint,
            'status_code' => $status_code
        ));
        
        return $data;
    }
    
    /**
     * Handle API errors
     *
     * @param WP_Error $error Error object
     * @return void
     */
    public function handle_error($error) {
        $this->log_error('API error occurred', array(
            'error_code' => $error->get_error_code(),
            'error_message' => $error->get_error_message(),
            'error_data' => $error->get_error_data(),
        ));
    }
    
    /**
     * Get user tweets
     *
     * @param string $username Username
     * @param int    $max_results Maximum results
     * @return array|WP_Error
     */
    public function get_user_tweets($username, $max_results = 100) {
        // First get user ID
        $user_response = $this->get("/users/by/username/$username");
        
        if (is_wp_error($user_response)) {
            return $user_response;
        }
        
        if (empty($user_response['data']['id'])) {
            return new WP_Error('user_not_found', 'User not found');
        }
        
        $user_id = $user_response['data']['id'];
        
        // Get user tweets
        $params = array(
            'max_results' => $max_results,
            'tweet.fields' => 'created_at,public_metrics,text',
            'exclude' => 'retweets,replies',
        );
        
        return $this->get("/users/$user_id/tweets", $params);
    }
    
    /**
     * Get tweet reposts
     *
     * @param string $tweet_id Tweet ID
     * @param int    $max_results Maximum results
     * @return array|WP_Error
     */
    public function get_tweet_reposts($tweet_id, $max_results = 100) {
        $params = array(
            'max_results' => $max_results,
            'tweet.fields' => 'created_at,text',
        );
        
        return $this->get("/tweets/$tweet_id/retweeted_by", $params);
    }
    
    /**
     * Post tweet
     *
     * @param string $text Tweet text
     * @return array|WP_Error
     */
    public function post_tweet($text) {
        $data = array(
            'text' => $text,
        );
        
        return $this->post('/tweets', $data);
    }
    
    /**
     * Generate content with OpenAI
     *
     * @param string $prompt Prompt for content generation
     * @param array  $options Generation options
     * @return array|WP_Error
     */
    public function generate_content($prompt, $options = array()) {
        $default_options = array(
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => 150,
            'temperature' => 0.7,
        );
        
        $options = array_merge($default_options, $options);
        
        $data = array(
            'model' => $options['model'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature'],
        );
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->credentials['openai_api_key'],
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 60,
        ));
        
        if (is_wp_error($response)) {
            $this->handle_error($response);
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error = new WP_Error(
                'openai_error',
                'OpenAI API request failed',
                array(
                    'status_code' => $status_code,
                    'response' => $data,
                )
            );
            $this->handle_error($error);
            return $error;
        }
        
        $this->log_debug('OpenAI content generation successful', array(
            'model' => $options['model'],
            'max_tokens' => $options['max_tokens']
        ));
        
        return $data;
    }
} 