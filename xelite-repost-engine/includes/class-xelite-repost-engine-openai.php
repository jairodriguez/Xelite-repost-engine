<?php
/**
 * OpenAI API Service for Repost Intelligence
 *
 * Handles all OpenAI API interactions with proper authentication,
 * error handling, and response processing.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OpenAI API Service Class
 */
class XeliteRepostEngine_OpenAI extends XeliteRepostEngine_Abstract_Base {

    /**
     * OpenAI API base URL
     *
     * @var string
     */
    private $api_base_url = 'https://api.openai.com/v1';

    /**
     * OpenAI API key
     *
     * @var string
     */
    private $api_key;

    /**
     * Default model to use
     *
     * @var string
     */
    private $default_model = 'gpt-4';

    /**
     * Logger instance
     *
     * @var XeliteRepostEngine_Logger
     */
    private $logger;

    /**
     * Cache expiration time (in seconds)
     *
     * @var int
     */
    private $cache_expiration;

    /**
     * Rate limiting settings
     *
     * @var array
     */
    private $rate_limits;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Logger $logger Logger service.
     */
    public function __construct($logger = null) {
        $this->logger = $logger;
        $this->cache_expiration = 3600; // 1 hour default
        $this->rate_limits = array(
            'requests_per_minute' => 60,
            'tokens_per_minute' => 150000,
            'last_request_time' => 0,
            'request_count' => 0,
            'token_count' => 0
        );
        
        $this->init_api_key();
        $this->init_hooks();
    }

    /**
     * Initialize API key from WordPress options
     */
    private function init_api_key() {
        $this->api_key = get_option('xelite_repost_engine_openai_api_key', '');
        
        if (empty($this->api_key)) {
            $this->log('warning', 'OpenAI API key not configured');
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_xelite_test_openai_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_xelite_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_xelite_validate_api_key', array($this, 'ajax_validate_api_key'));
    }

    /**
     * Validate API key
     *
     * @param string $api_key API key to validate.
     * @return array Validation result.
     */
    public function validate_api_key($api_key = null) {
        if ($api_key) {
            $this->api_key = $api_key;
        }

        if (empty($this->api_key)) {
            return array(
                'valid' => false,
                'error' => 'API key is empty'
            );
        }

        // Test API key by making a simple request
        $response = $this->make_api_request('models', 'GET');
        
        if (is_wp_error($response)) {
            return array(
                'valid' => false,
                'error' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return array(
                'valid' => false,
                'error' => $data['error']['message'] ?? 'Invalid API key'
            );
        }

        return array(
            'valid' => true,
            'models' => $data['data'] ?? array()
        );
    }

    /**
     * Test API connection
     *
     * @return array Connection test result.
     */
    public function test_connection() {
        $validation = $this->validate_api_key();
        
        if (!$validation['valid']) {
            return $validation;
        }

        // Test with a simple completion request
        $test_prompt = "Hello, this is a test.";
        $result = $this->generate_completion($test_prompt, 10);

        if (is_wp_error($result)) {
            return array(
                'connected' => false,
                'error' => $result->get_error_message()
            );
        }

        return array(
            'connected' => true,
            'response' => $result,
            'models' => $validation['models']
        );
    }

    /**
     * Generate content based on user context and patterns
     *
     * @param array $user_context User context data.
     * @param array $patterns Pattern analysis data.
     * @param array $options Generation options.
     * @return array|WP_Error Generated content or error.
     */
    public function generate_content($user_context, $patterns, $options = array()) {
        if (!$this->validate_api_key()['valid']) {
            return new WP_Error('invalid_api_key', 'OpenAI API key is not valid');
        }

        // Check rate limits
        if (!$this->check_rate_limits()) {
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded. Please try again later.');
        }

        // Build prompt from user context and patterns
        $prompt = $this->build_content_prompt($user_context, $patterns, $options);
        
        // Generate completion
        $completion = $this->generate_completion($prompt, $options['max_tokens'] ?? 280);
        
        if (is_wp_error($completion)) {
            return $completion;
        }

        // Process and format the response
        $formatted_content = $this->format_generated_content($completion, $options);

        // Cache the result
        $this->cache_generated_content($user_context, $patterns, $formatted_content);

        return array(
            'content' => $formatted_content,
            'prompt' => $prompt,
            'usage' => $completion['usage'] ?? array(),
            'model' => $completion['model'] ?? $this->default_model
        );
    }

    /**
     * Generate completion using OpenAI API
     *
     * @param string $prompt The prompt to send.
     * @param int $max_tokens Maximum tokens to generate.
     * @param array $options Additional options.
     * @return array|WP_Error Response or error.
     */
    public function generate_completion($prompt, $max_tokens = 280, $options = array()) {
        $default_options = array(
            'model' => $this->default_model,
            'temperature' => 0.7,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
            'stop' => null
        );

        $options = wp_parse_args($options, $default_options);

        $request_data = array(
            'model' => $options['model'],
            'prompt' => $prompt,
            'max_tokens' => min($max_tokens, 4000), // OpenAI limit
            'temperature' => $options['temperature'],
            'top_p' => $options['top_p'],
            'frequency_penalty' => $options['frequency_penalty'],
            'presence_penalty' => $options['presence_penalty']
        );

        if ($options['stop']) {
            $request_data['stop'] = $options['stop'];
        }

        $response = $this->make_api_request('completions', 'POST', $request_data);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            $error_message = $data['error']['message'] ?? 'Unknown API error';
            $this->log('error', 'OpenAI API error: ' . $error_message, $data['error']);
            return new WP_Error('openai_api_error', $error_message);
        }

        // Update rate limiting
        $this->update_rate_limits($data['usage'] ?? array());

        return $data;
    }

    /**
     * Generate chat completion using OpenAI API
     *
     * @param array $messages Array of message objects.
     * @param int $max_tokens Maximum tokens to generate.
     * @param array $options Additional options.
     * @return array|WP_Error Response or error.
     */
    public function generate_chat_completion($messages, $max_tokens = 280, $options = array()) {
        $default_options = array(
            'model' => 'gpt-4',
            'temperature' => 0.7,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        );

        $options = wp_parse_args($options, $default_options);

        $request_data = array(
            'model' => $options['model'],
            'messages' => $messages,
            'max_tokens' => min($max_tokens, 4000),
            'temperature' => $options['temperature'],
            'top_p' => $options['top_p'],
            'frequency_penalty' => $options['frequency_penalty'],
            'presence_penalty' => $options['presence_penalty']
        );

        $response = $this->make_api_request('chat/completions', 'POST', $request_data);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            $error_message = $data['error']['message'] ?? 'Unknown API error';
            $this->log('error', 'OpenAI API error: ' . $error_message, $data['error']);
            return new WP_Error('openai_api_error', $error_message);
        }

        // Update rate limiting
        $this->update_rate_limits($data['usage'] ?? array());

        return $data;
    }

    /**
     * Build content prompt from user context and patterns
     *
     * @param array $user_context User context data.
     * @param array $patterns Pattern analysis data.
     * @param array $options Generation options.
     * @return string Built prompt.
     */
    private function build_content_prompt($user_context, $patterns, $options = array()) {
        $prompt_parts = array();

        // System instruction
        $prompt_parts[] = "You are an expert social media content creator specializing in creating engaging, repost-worthy content for X (Twitter).";

        // User context
        if (!empty($user_context)) {
            $prompt_parts[] = "\nUser Context:";
            if (!empty($user_context['writing_style'])) {
                $prompt_parts[] = "- Writing Style: " . $user_context['writing_style'];
            }
            if (!empty($user_context['offer'])) {
                $prompt_parts[] = "- Offer: " . $user_context['offer'];
            }
            if (!empty($user_context['audience'])) {
                $prompt_parts[] = "- Target Audience: " . $user_context['audience'];
            }
            if (!empty($user_context['pain_points'])) {
                $prompt_parts[] = "- Pain Points: " . $user_context['pain_points'];
            }
            if (!empty($user_context['topic'])) {
                $prompt_parts[] = "- Topic: " . $user_context['topic'];
            }
        }

        // Pattern analysis
        if (!empty($patterns)) {
            $prompt_parts[] = "\nPattern Analysis (based on successful reposts):";
            
            if (!empty($patterns['length_patterns']['optimal_length_range'])) {
                $optimal = $patterns['length_patterns']['optimal_length_range'];
                $prompt_parts[] = "- Optimal Length: {$optimal['min']}-{$optimal['max']} characters";
            }
            
            if (!empty($patterns['tone_patterns']['top_effective_tones'])) {
                $top_tone = $patterns['tone_patterns']['top_effective_tones'][0];
                $prompt_parts[] = "- Most Effective Tone: " . ucfirst($top_tone['key']);
            }
            
            if (!empty($patterns['format_patterns'])) {
                $prompt_parts[] = "- Format Recommendations:";
                foreach ($patterns['format_patterns'] as $type => $data) {
                    if (isset($data['optimal_count'])) {
                        $prompt_parts[] = "  * {$type}: {$data['optimal_count']} optimal";
                    }
                }
            }
            
            if (!empty($patterns['content_patterns']['top_words'])) {
                $top_words = array_slice($patterns['content_patterns']['top_words'], 0, 5);
                $words = array_map(function($word) { return $word['key']; }, $top_words);
                $prompt_parts[] = "- High-Engagement Words: " . implode(', ', $words);
            }
        }

        // Content requirements
        $prompt_parts[] = "\nRequirements:";
        $prompt_parts[] = "- Create engaging, repost-worthy content";
        $prompt_parts[] = "- Follow the identified patterns and user context";
        $prompt_parts[] = "- Keep within optimal length range";
        $prompt_parts[] = "- Use the most effective tone";
        $prompt_parts[] = "- Include recommended format elements";
        $prompt_parts[] = "- Make it authentic and valuable to the target audience";

        // Specific instructions
        if (!empty($options['instructions'])) {
            $prompt_parts[] = "\nAdditional Instructions:";
            $prompt_parts[] = $options['instructions'];
        }

        $prompt_parts[] = "\nGenerate 3 different content variations:";

        return implode("\n", $prompt_parts);
    }

    /**
     * Format generated content
     *
     * @param array $completion API completion response.
     * @param array $options Formatting options.
     * @return array Formatted content.
     */
    private function format_generated_content($completion, $options = array()) {
        $text = $completion['choices'][0]['text'] ?? '';
        
        // Clean up the text
        $text = trim($text);
        $text = preg_replace('/^\s*[-*]\s*/m', '', $text); // Remove list markers
        
        // Split into variations if multiple are generated
        $variations = array();
        $parts = preg_split('/\n\s*\n/', $text);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (!empty($part) && strlen($part) > 10) {
                $variations[] = $part;
            }
        }

        // If no clear variations, treat the whole text as one
        if (empty($variations)) {
            $variations = array($text);
        }

        // Limit to requested number of variations
        $max_variations = $options['max_variations'] ?? 3;
        $variations = array_slice($variations, 0, $max_variations);

        return array(
            'variations' => $variations,
            'raw_text' => $text,
            'model' => $completion['model'] ?? $this->default_model,
            'usage' => $completion['usage'] ?? array()
        );
    }

    /**
     * Make API request to OpenAI
     *
     * @param string $endpoint API endpoint.
     * @param string $method HTTP method.
     * @param array $data Request data.
     * @return array|WP_Error Response or error.
     */
    private function make_api_request($endpoint, $method = 'GET', $data = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenAI API key is not configured');
        }

        $url = $this->api_base_url . '/' . $endpoint;
        
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'XeliteRepostEngine/1.0.0'
        );

        $request_args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
            'sslverify' => true
        );

        if ($method === 'POST' && !empty($data)) {
            $request_args['body'] = json_encode($data);
        }

        $this->log('debug', 'Making OpenAI API request', array(
            'endpoint' => $endpoint,
            'method' => $method,
            'data' => $data
        ));

        $response = wp_remote_request($url, $request_args);

        if (is_wp_error($response)) {
            $this->log('error', 'OpenAI API request failed', array(
                'endpoint' => $endpoint,
                'error' => $response->get_error_message()
            ));
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error_data = json_decode($body, true);
            
            $error_message = 'API request failed with status ' . $status_code;
            if (isset($error_data['error']['message'])) {
                $error_message = $error_data['error']['message'];
            }

            $this->log('error', 'OpenAI API error response', array(
                'endpoint' => $endpoint,
                'status_code' => $status_code,
                'error' => $error_data
            ));

            return new WP_Error('api_error', $error_message, $error_data);
        }

        $this->log('debug', 'OpenAI API request successful', array(
            'endpoint' => $endpoint,
            'status_code' => $status_code
        ));

        return $response;
    }

    /**
     * Check rate limits
     *
     * @return bool Whether request is allowed.
     */
    private function check_rate_limits() {
        $current_time = time();
        $minute_ago = $current_time - 60;

        // Reset counters if a minute has passed
        if ($this->rate_limits['last_request_time'] < $minute_ago) {
            $this->rate_limits['request_count'] = 0;
            $this->rate_limits['token_count'] = 0;
        }

        // Check if we're within limits
        if ($this->rate_limits['request_count'] >= $this->rate_limits['requests_per_minute']) {
            $this->log('warning', 'Rate limit exceeded: too many requests per minute');
            return false;
        }

        if ($this->rate_limits['token_count'] >= $this->rate_limits['tokens_per_minute']) {
            $this->log('warning', 'Rate limit exceeded: too many tokens per minute');
            return false;
        }

        return true;
    }

    /**
     * Update rate limiting counters
     *
     * @param array $usage Usage data from API response.
     */
    private function update_rate_limits($usage) {
        $this->rate_limits['last_request_time'] = time();
        $this->rate_limits['request_count']++;
        
        if (isset($usage['total_tokens'])) {
            $this->rate_limits['token_count'] += $usage['total_tokens'];
        }
    }

    /**
     * Cache generated content
     *
     * @param array $user_context User context.
     * @param array $patterns Patterns.
     * @param array $content Generated content.
     */
    private function cache_generated_content($user_context, $patterns, $content) {
        $cache_key = 'xelite_generated_content_' . md5(serialize($user_context) . serialize($patterns));
        set_transient($cache_key, $content, $this->cache_expiration);
    }

    /**
     * Get cached generated content
     *
     * @param array $user_context User context.
     * @param array $patterns Patterns.
     * @return array|false Cached content or false.
     */
    public function get_cached_content($user_context, $patterns) {
        $cache_key = 'xelite_generated_content_' . md5(serialize($user_context) . serialize($patterns));
        return get_transient($cache_key);
    }

    /**
     * Clear cache for generated content
     *
     * @param array $user_context User context.
     * @param array $patterns Patterns.
     * @return bool Success status.
     */
    public function clear_cached_content($user_context, $patterns) {
        $cache_key = 'xelite_generated_content_' . md5(serialize($user_context) . serialize($patterns));
        return delete_transient($cache_key);
    }

    /**
     * Get available models
     *
     * @return array|WP_Error Models list or error.
     */
    public function get_models() {
        $response = $this->make_api_request('models', 'GET');
        
        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message']);
        }

        return $data['data'] ?? array();
    }

    /**
     * Get API usage statistics
     *
     * @param string $date Date in YYYY-MM-DD format.
     * @return array|WP_Error Usage data or error.
     */
    public function get_usage($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $response = $this->make_api_request("usage?date={$date}", 'GET');
        
        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message']);
        }

        return $data;
    }

    /**
     * AJAX handlers
     */

    /**
     * AJAX handler for testing connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('xelite_openai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $result = $this->test_connection();
        
        if (isset($result['connected']) && $result['connected']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error'] ?? 'Connection test failed');
        }
    }

    /**
     * AJAX handler for generating content
     */
    public function ajax_generate_content() {
        check_ajax_referer('xelite_openai_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $user_context = json_decode(stripslashes($_POST['user_context'] ?? '{}'), true);
        $patterns = json_decode(stripslashes($_POST['patterns'] ?? '{}'), true);
        $options = json_decode(stripslashes($_POST['options'] ?? '{}'), true);

        if (empty($user_context) && empty($patterns)) {
            wp_send_json_error('User context or patterns are required');
        }

        $result = $this->generate_content($user_context, $patterns, $options);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX handler for validating API key
     */
    public function ajax_validate_api_key() {
        check_ajax_referer('xelite_openai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
        }

        $result = $this->validate_api_key($api_key);
        
        if ($result['valid']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
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
            $this->logger->log($level, "[OpenAI Service] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine OpenAI Service] {$message}");
        }
    }
} 