<?php
/**
 * API Interface
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Interface
 */
interface XeliteRepostEngine_API_Interface {
    
    /**
     * Authenticate with the API
     *
     * @param array $credentials API credentials
     * @return bool
     */
    public function authenticate($credentials);
    
    /**
     * Test API connection
     *
     * @return bool
     */
    public function test_connection();
    
    /**
     * Get API response
     *
     * @param string $endpoint API endpoint
     * @param array  $params   Request parameters
     * @return array|WP_Error
     */
    public function get($endpoint, $params = array());
    
    /**
     * Post to API
     *
     * @param string $endpoint API endpoint
     * @param array  $data     Request data
     * @return array|WP_Error
     */
    public function post($endpoint, $data = array());
    
    /**
     * Handle API errors
     *
     * @param WP_Error $error Error object
     * @return void
     */
    public function handle_error($error);
} 