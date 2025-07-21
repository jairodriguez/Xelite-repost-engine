<?php
/**
 * Test file for X API Posting
 *
 * This file tests the X API posting functionality for the Xelite Repost Engine plugin.
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for X API posting
 */
class Xelite_X_API_Test {

    /**
     * X Poster instance
     *
     * @var XeliteRepostEngine_X_Poster
     */
    private $x_poster;

    /**
     * Initialize the test class
     */
    public function __construct() {
        add_action('wp_loaded', array($this, 'run_tests'));
    }

    /**
     * Run all tests
     */
    public function run_tests() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get X Poster instance
        global $xelite_repost_engine;
        if (!$xelite_repost_engine) {
            echo '<div class="notice notice-error"><p>Xelite Repost Engine not initialized.</p></div>';
            return;
        }

        $this->x_poster = $xelite_repost_engine->get_x_poster();
        if (!$this->x_poster) {
            echo '<div class="notice notice-error"><p>X Poster not available.</p></div>';
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>X API Posting Tests</h1>';

        $this->test_credentials();
        $this->test_authentication();
        $this->test_content_validation();
        $this->test_api_endpoints();
        $this->test_scheduling();
        $this->test_media_upload();
        $this->test_error_handling();

        echo '</div>';
    }

    /**
     * Test API credentials
     */
    private function test_credentials() {
        echo '<h2>Testing API Credentials</h2>';

        $has_credentials = $this->x_poster->has_credentials();
        echo '<p><strong>Credentials Configured:</strong> ' . ($has_credentials ? '✅ Yes' : '❌ No') . '</p>';

        if ($has_credentials) {
            $client_id = get_option('xelite_x_client_id', '');
            $client_secret = get_option('xelite_x_client_secret', '');
            $redirect_uri = get_option('xelite_x_redirect_uri', '');
            
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Setting</th><th>Value</th><th>Status</th></tr></thead>';
            echo '<tbody>';
            echo '<tr><td>Client ID</td><td>' . esc_html($client_id) . '</td><td>' . (!empty($client_id) ? '✅ Set' : '❌ Missing') . '</td></tr>';
            echo '<tr><td>Client Secret</td><td>' . str_repeat('*', strlen($client_secret)) . '</td><td>' . (!empty($client_secret) ? '✅ Set' : '❌ Missing') . '</td></tr>';
            echo '<tr><td>Redirect URI</td><td>' . esc_html($redirect_uri) . '</td><td>' . (!empty($redirect_uri) ? '✅ Set' : '❌ Missing') . '</td></tr>';
            echo '</tbody></table>';
        } else {
            echo '<p>Please configure X API credentials in the <a href="' . admin_url('admin.php?page=xelite-x-settings') . '">X Settings</a> page.</p>';
        }

        echo '<hr>';
    }

    /**
     * Test authentication
     */
    private function test_authentication() {
        echo '<h2>Testing Authentication</h2>';

        $current_user_id = get_current_user_id();
        $is_authenticated = $this->x_poster->is_user_authenticated($current_user_id);
        
        echo '<p><strong>User Authentication:</strong> ' . ($is_authenticated ? '✅ Authenticated' : '❌ Not Authenticated') . '</p>';

        if ($is_authenticated) {
            $access_token = get_user_meta($current_user_id, 'xelite_x_access_token', true);
            $refresh_token = get_user_meta($current_user_id, 'xelite_x_refresh_token', true);
            $expires_at = get_user_meta($current_user_id, 'xelite_x_token_expires_at', true);
            $authenticated_at = get_user_meta($current_user_id, 'xelite_x_authenticated_at', true);
            
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Token Type</th><th>Status</th><th>Details</th></tr></thead>';
            echo '<tbody>';
            echo '<tr><td>Access Token</td><td>' . (!empty($access_token) ? '✅ Present' : '❌ Missing') . '</td><td>' . substr($access_token, 0, 20) . '...</td></tr>';
            echo '<tr><td>Refresh Token</td><td>' . (!empty($refresh_token) ? '✅ Present' : '❌ Missing') . '</td><td>' . substr($refresh_token, 0, 20) . '...</td></tr>';
            echo '<tr><td>Expires At</td><td>' . (!empty($expires_at) ? '✅ Set' : '❌ Missing') . '</td><td>' . ($expires_at ? date('Y-m-d H:i:s', $expires_at) : 'N/A') . '</td></tr>';
            echo '<tr><td>Authenticated At</td><td>' . (!empty($authenticated_at) ? '✅ Set' : '❌ Missing') . '</td><td>' . esc_html($authenticated_at) . '</td></tr>';
            echo '</tbody></table>';
        } else {
            $auth_url = $this->x_poster->get_authorization_url($current_user_id);
            if ($auth_url) {
                echo '<p><a href="' . esc_url($auth_url) . '" class="button button-primary">Authenticate with X</a></p>';
            } else {
                echo '<p>❌ Cannot generate authorization URL. Please check API credentials.</p>';
            }
        }

        echo '<hr>';
    }

    /**
     * Test content validation
     */
    private function test_content_validation() {
        echo '<h2>Testing Content Validation</h2>';

        $test_cases = array(
            array(
                'content' => 'This is a valid tweet with normal content.',
                'expected' => true,
                'description' => 'Normal tweet content'
            ),
            array(
                'content' => '',
                'expected' => false,
                'description' => 'Empty content'
            ),
            array(
                'content' => str_repeat('a', 281),
                'expected' => false,
                'description' => 'Content exceeding 280 characters'
            ),
            array(
                'content' => 'Valid tweet with 280 characters exactly' . str_repeat('a', 280 - 35),
                'expected' => true,
                'description' => 'Content with exactly 280 characters'
            ),
            array(
                'content' => "Tweet with\nnewlines\nand\ttabs",
                'expected' => true,
                'description' => 'Content with whitespace characters'
            ),
            array(
                'content' => 'Tweet with special characters: @#$%^&*()_+-=[]{}|;:,.<>?',
                'expected' => true,
                'description' => 'Content with special characters'
            )
        );

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Test Case</th><th>Content</th><th>Expected</th><th>Result</th></tr></thead>';
        echo '<tbody>';

        foreach ($test_cases as $test) {
            $validation = $this->x_poster->validate_tweet_content($test['content']);
            $result = $validation['valid'] === $test['expected'];
            
            echo '<tr>';
            echo '<td>' . esc_html($test['description']) . '</td>';
            echo '<td>' . esc_html(substr($test['content'], 0, 50)) . (strlen($test['content']) > 50 ? '...' : '') . '</td>';
            echo '<td>' . ($test['expected'] ? 'Valid' : 'Invalid') . '</td>';
            echo '<td>' . ($result ? '✅ Pass' : '❌ Fail') . '</td>';
            echo '</tr>';
            
            if (!$result) {
                echo '<tr><td colspan="4"><em>Error: ' . esc_html($validation['error']) . '</em></td></tr>';
            }
        }

        echo '</tbody></table>';

        echo '<hr>';
    }

    /**
     * Test API endpoints
     */
    private function test_api_endpoints() {
        echo '<h2>Testing API Endpoints</h2>';

        $endpoints = array(
            'OAuth Token URL' => 'https://api.twitter.com/2/oauth2/token',
            'API Base URL' => 'https://api.twitter.com/2',
            'Tweets Endpoint' => 'https://api.twitter.com/2/tweets',
            'Media Upload Endpoint' => 'https://api.twitter.com/2/media/upload',
            'User Info Endpoint' => 'https://api.twitter.com/2/users/me'
        );

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Endpoint</th><th>URL</th><th>Status</th></tr></thead>';
        echo '<tbody>';

        foreach ($endpoints as $name => $url) {
            $response = wp_remote_head($url, array('timeout' => 10));
            $status = is_wp_error($response) ? '❌ Error' : '✅ Accessible';
            
            echo '<tr>';
            echo '<td>' . esc_html($name) . '</td>';
            echo '<td><code>' . esc_html($url) . '</code></td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<hr>';
    }

    /**
     * Test scheduling functionality
     */
    private function test_scheduling() {
        echo '<h2>Testing Scheduling Functionality</h2>';

        $current_user_id = get_current_user_id();
        
        // Test scheduling a tweet
        $test_content = 'Test scheduled tweet from Xelite Repost Engine - ' . date('Y-m-d H:i:s');
        $scheduled_time = time() + 300; // 5 minutes from now
        
        $scheduled_id = $this->x_poster->schedule_tweet($test_content, $scheduled_time, $current_user_id);
        
        if ($scheduled_id) {
            echo '<p><strong>Scheduling:</strong> ✅ Success (ID: ' . $scheduled_id . ')</p>';
            echo '<p><strong>Scheduled Time:</strong> ' . date('Y-m-d H:i:s', $scheduled_time) . '</p>';
            
            // Check if cron event was scheduled
            $cron_events = _get_cron_array();
            $event_scheduled = false;
            
            foreach ($cron_events as $timestamp => $events) {
                if (isset($events['xelite_post_scheduled_tweet'])) {
                    foreach ($events['xelite_post_scheduled_tweet'] as $event) {
                        if ($event['args'][0] == $scheduled_id) {
                            $event_scheduled = true;
                            break 2;
                        }
                    }
                }
            }
            
            echo '<p><strong>Cron Event:</strong> ' . ($event_scheduled ? '✅ Scheduled' : '❌ Not Scheduled') . '</p>';
        } else {
            echo '<p><strong>Scheduling:</strong> ❌ Failed</p>';
        }

        // Test invalid scheduling (past time)
        $past_time = time() - 3600; // 1 hour ago
        $invalid_scheduled_id = $this->x_poster->schedule_tweet($test_content, $past_time, $current_user_id);
        echo '<p><strong>Past Time Scheduling:</strong> ' . ($invalid_scheduled_id ? '❌ Should Fail' : '✅ Correctly Failed') . '</p>';

        echo '<hr>';
    }

    /**
     * Test media upload functionality
     */
    private function test_media_upload() {
        echo '<h2>Testing Media Upload</h2>';

        // Test file validation
        $test_file_path = ABSPATH . 'wp-config.php'; // Use existing file for testing
        
        if (file_exists($test_file_path)) {
            $file_size = filesize($test_file_path);
            $file_exists = file_exists($test_file_path);
            $file_readable = is_readable($test_file_path);
            
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Test</th><th>Result</th><th>Details</th></tr></thead>';
            echo '<tbody>';
            echo '<tr><td>File Exists</td><td>' . ($file_exists ? '✅ Yes' : '❌ No') . '</td><td>' . esc_html($test_file_path) . '</td></tr>';
            echo '<tr><td>File Readable</td><td>' . ($file_readable ? '✅ Yes' : '❌ No') . '</td><td>Permission check</td></tr>';
            echo '<tr><td>File Size</td><td>' . ($file_size <= 5 * 1024 * 1024 ? '✅ Valid' : '❌ Too Large') . '</td><td>' . size_format($file_size) . '</td></tr>';
            echo '</tbody></table>';
            
            // Note: We don't actually upload the file in tests to avoid API calls
            echo '<p><em>Note: Actual media upload not tested to avoid API calls.</em></p>';
        } else {
            echo '<p>❌ Test file not found</p>';
        }

        echo '<hr>';
    }

    /**
     * Test error handling
     */
    private function test_error_handling() {
        echo '<h2>Testing Error Handling</h2>';

        $error_tests = array(
            array(
                'test' => 'Invalid content validation',
                'content' => '',
                'expected_error' => 'Tweet content cannot be empty'
            ),
            array(
                'test' => 'Oversized content validation',
                'content' => str_repeat('a', 300),
                'expected_error' => 'Tweet content exceeds 280 character limit'
            ),
            array(
                'test' => 'Invalid characters validation',
                'content' => "Tweet with\x00null\x01characters",
                'expected_error' => 'Tweet content contains invalid characters'
            )
        );

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Test</th><th>Expected Error</th><th>Actual Error</th><th>Status</th></tr></thead>';
        echo '<tbody>';

        foreach ($error_tests as $test) {
            $validation = $this->x_poster->validate_tweet_content($test['content']);
            $error_matches = $validation['error'] === $test['expected_error'];
            
            echo '<tr>';
            echo '<td>' . esc_html($test['test']) . '</td>';
            echo '<td>' . esc_html($test['expected_error']) . '</td>';
            echo '<td>' . esc_html($validation['error']) . '</td>';
            echo '<td>' . ($error_matches ? '✅ Match' : '❌ Mismatch') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<hr>';
    }

    /**
     * Test OAuth flow simulation
     */
    public function test_oauth_flow() {
        echo '<h2>Testing OAuth Flow</h2>';

        $current_user_id = get_current_user_id();
        
        // Test authorization URL generation
        $auth_url = $this->x_poster->get_authorization_url($current_user_id);
        
        if ($auth_url) {
            echo '<p><strong>Authorization URL:</strong> ✅ Generated</p>';
            echo '<p><strong>URL:</strong> <code>' . esc_url($auth_url) . '</code></p>';
            
            // Check if state was stored
            $state = get_user_meta($current_user_id, 'xelite_x_oauth_state', true);
            echo '<p><strong>OAuth State:</strong> ' . (!empty($state) ? '✅ Stored' : '❌ Not Stored') . '</p>';
        } else {
            echo '<p><strong>Authorization URL:</strong> ❌ Failed to generate</p>';
        }

        echo '<hr>';
    }

    /**
     * Test database operations
     */
    public function test_database_operations() {
        echo '<h2>Testing Database Operations</h2>';

        global $wpdb;
        
        // Check if scheduled posts table exists
        $table_name = $wpdb->prefix . 'xelite_scheduled_posts';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        echo '<p><strong>Scheduled Posts Table:</strong> ' . ($table_exists ? '✅ Exists' : '❌ Missing') . '</p>';
        
        if ($table_exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            echo '<p><strong>Scheduled Posts Count:</strong> ' . intval($count) . '</p>';
        }

        // Check if posted tweets table exists
        $posted_table_name = $wpdb->prefix . 'xelite_posted_tweets';
        $posted_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$posted_table_name'") == $posted_table_name;
        
        echo '<p><strong>Posted Tweets Table:</strong> ' . ($posted_table_exists ? '✅ Exists' : '❌ Missing') . '</p>';
        
        if ($posted_table_exists) {
            $posted_count = $wpdb->get_var("SELECT COUNT(*) FROM $posted_table_name");
            echo '<p><strong>Posted Tweets Count:</strong> ' . intval($posted_count) . '</p>';
        }

        echo '<hr>';
    }

    /**
     * Simulate posting a tweet (for testing)
     *
     * @param string $content Tweet content.
     * @param int $user_id User ID.
     * @return array|false Mock response or false.
     */
    public function simulate_post_tweet($content, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Validate content
        $validation = $this->x_poster->validate_tweet_content($content);
        if (!$validation['valid']) {
            return false;
        }
        
        // Simulate API response
        $mock_response = array(
            'data' => array(
                'id' => '1234567890123456789',
                'text' => $content
            )
        );
        
        // Store post record
        global $wpdb;
        $table_name = $wpdb->prefix . 'xelite_posted_tweets';
        
        $data = array(
            'user_id' => $user_id,
            'content' => $content,
            'tweet_id' => $mock_response['data']['id'],
            'options' => json_encode(array()),
            'posted_at' => current_time('mysql')
        );
        
        $wpdb->insert($table_name, $data);
        
        return $mock_response;
    }
}

// Initialize test class
new Xelite_X_API_Test(); 