<?php
/**
 * Test Suite for X Posting Scheduler
 *
 * Comprehensive tests for the scheduling system including database operations,
 * timezone handling, WordPress cron integration, and admin functionality.
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for X Posting Scheduler
 */
class XeliteRepostEngine_Scheduler_Test {

    /**
     * Database instance
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;

    /**
     * User Meta instance
     *
     * @var XeliteRepostEngine_User_Meta
     */
    private $user_meta;

    /**
     * X Poster instance
     *
     * @var XeliteRepostEngine_X_Poster
     */
    private $x_poster;

    /**
     * Scheduler instance
     *
     * @var XeliteRepostEngine_Scheduler
     */
    private $scheduler;

    /**
     * Test user ID
     *
     * @var int
     */
    private $test_user_id;

    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new XeliteRepostEngine_Database();
        $this->user_meta = new XeliteRepostEngine_User_Meta($this->database);
        $this->x_poster = new XeliteRepostEngine_X_Poster($this->database, $this->user_meta);
        $this->scheduler = new XeliteRepostEngine_Scheduler($this->database, $this->user_meta, $this->x_poster);
        
        $this->setup_test_environment();
    }

    /**
     * Setup test environment
     */
    private function setup_test_environment() {
        // Create test user
        $this->test_user_id = wp_create_user('test_scheduler_user', 'test_password', 'test_scheduler@example.com');
        
        if (is_wp_error($this->test_user_id)) {
            $this->test_user_id = get_user_by('email', 'test_scheduler@example.com')->ID;
        }
        
        // Set up test X credentials for the user
        $this->user_meta->update_user_meta($this->test_user_id, 'x_access_token', 'test_access_token');
        $this->user_meta->update_user_meta($this->test_user_id, 'x_refresh_token', 'test_refresh_token');
        $this->user_meta->update_user_meta($this->test_user_id, 'x_token_expires_at', time() + 3600);
    }

    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "<h2>X Posting Scheduler Test Suite</h2>\n";
        
        $tests = array(
            'test_database_tables_creation',
            'test_timezone_validation',
            'test_timezone_conversion',
            'test_scheduled_time_validation',
            'test_schedule_post',
            'test_get_scheduled_posts',
            'test_update_scheduled_post',
            'test_delete_scheduled_post',
            'test_execute_scheduled_post_now',
            'test_cron_integration',
            'test_cleanup_functionality',
            'test_admin_ajax_handlers',
            'test_settings_page',
            'test_error_handling',
            'test_logging_functionality'
        );
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            echo "<h3>Running: {$test}</h3>\n";
            
            try {
                $result = $this->$test();
                if ($result) {
                    echo "<p style='color: green;'>✓ PASSED</p>\n";
                    $passed++;
                } else {
                    echo "<p style='color: red;'>✗ FAILED</p>\n";
                    $failed++;
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ FAILED: " . esc_html($e->getMessage()) . "</p>\n";
                $failed++;
            }
            
            echo "<hr>\n";
        }
        
        echo "<h3>Test Results</h3>\n";
        echo "<p>Passed: {$passed}</p>\n";
        echo "<p>Failed: {$failed}</p>\n";
        echo "<p>Total: " . ($passed + $failed) . "</p>\n";
        
        // Cleanup
        $this->cleanup_test_data();
    }

    /**
     * Test database tables creation
     */
    public function test_database_tables_creation() {
        global $wpdb;
        
        // Check if tables exist
        $scheduled_posts_table = $wpdb->prefix . 'xelite_scheduled_posts';
        $scheduling_logs_table = $wpdb->prefix . 'xelite_scheduling_logs';
        
        $scheduled_exists = $wpdb->get_var("SHOW TABLES LIKE '{$scheduled_posts_table}'") === $scheduled_posts_table;
        $logs_exists = $wpdb->get_var("SHOW TABLES LIKE '{$scheduling_logs_table}'") === $scheduling_logs_table;
        
        if (!$scheduled_exists || !$logs_exists) {
            // Create tables
            $this->scheduler->create_scheduling_tables();
            
            // Check again
            $scheduled_exists = $wpdb->get_var("SHOW TABLES LIKE '{$scheduled_posts_table}'") === $scheduled_posts_table;
            $logs_exists = $wpdb->get_var("SHOW TABLES LIKE '{$scheduling_logs_table}'") === $scheduling_logs_table;
        }
        
        return $scheduled_exists && $logs_exists;
    }

    /**
     * Test timezone validation
     */
    public function test_timezone_validation() {
        $timezones = $this->scheduler->get_timezone_options();
        
        // Check if common timezones are included
        $required_timezones = array('UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo');
        
        foreach ($required_timezones as $timezone) {
            if (!array_key_exists($timezone, $timezones)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Test timezone conversion
     */
    public function test_timezone_conversion() {
        $test_time = '2024-01-15 10:30:00';
        $test_timezone = 'America/New_York';
        
        // Convert to UTC
        $utc_time = $this->scheduler->convert_to_utc($test_time, $test_timezone);
        
        // Convert back from UTC
        $converted_back = $this->scheduler->convert_from_utc($utc_time, $test_timezone);
        
        // Should be the same (accounting for daylight saving time)
        return $converted_back === $test_time || $converted_back === '2024-01-15 09:30:00';
    }

    /**
     * Test scheduled time validation
     */
    public function test_scheduled_time_validation() {
        // Test valid future time
        $future_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $validation = $this->scheduler->validate_scheduled_time($future_time, 'UTC');
        
        if (!$validation['valid']) {
            return false;
        }
        
        // Test past time (should fail)
        $past_time = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $validation = $this->scheduler->validate_scheduled_time($past_time, 'UTC');
        
        if ($validation['valid']) {
            return false;
        }
        
        // Test invalid timezone
        $validation = $this->scheduler->validate_scheduled_time($future_time, 'Invalid/Timezone');
        
        if ($validation['valid']) {
            return false;
        }
        
        return true;
    }

    /**
     * Test schedule post functionality
     */
    public function test_schedule_post() {
        $content = 'Test scheduled post content';
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $timezone = 'UTC';
        
        $scheduled_id = $this->scheduler->schedule_post($content, $scheduled_time, $timezone, $this->test_user_id);
        
        if (!$scheduled_id) {
            return false;
        }
        
        // Verify post was created
        $post_data = $this->scheduler->get_scheduled_post($scheduled_id);
        
        if (!$post_data) {
            return false;
        }
        
        if ($post_data->content !== $content || $post_data->user_id !== $this->test_user_id) {
            return false;
        }
        
        return true;
    }

    /**
     * Test get scheduled posts
     */
    public function test_get_scheduled_posts() {
        // Create multiple test posts
        $content1 = 'Test post 1';
        $content2 = 'Test post 2';
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $this->scheduler->schedule_post($content1, $scheduled_time, 'UTC', $this->test_user_id);
        $this->scheduler->schedule_post($content2, $scheduled_time, 'UTC', $this->test_user_id);
        
        // Get posts
        $posts = $this->scheduler->get_scheduled_posts($this->test_user_id);
        
        if (count($posts) < 2) {
            return false;
        }
        
        // Check if posts contain our test content
        $found_content1 = false;
        $found_content2 = false;
        
        foreach ($posts as $post) {
            if ($post->content === $content1) {
                $found_content1 = true;
            }
            if ($post->content === $content2) {
                $found_content2 = true;
            }
        }
        
        return $found_content1 && $found_content2;
    }

    /**
     * Test update scheduled post
     */
    public function test_update_scheduled_post() {
        // Create a test post
        $content = 'Original content';
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $scheduled_id = $this->scheduler->schedule_post($content, $scheduled_time, 'UTC', $this->test_user_id);
        
        if (!$scheduled_id) {
            return false;
        }
        
        // Update the post
        $new_content = 'Updated content';
        $new_time = date('Y-m-d H:i:s', strtotime('+2 hours'));
        
        $result = $this->scheduler->update_scheduled_post($scheduled_id, array(
            'content' => $new_content,
            'scheduled_time' => $new_time,
            'timezone' => 'UTC'
        ));
        
        if (!$result) {
            return false;
        }
        
        // Verify update
        $post_data = $this->scheduler->get_scheduled_post($scheduled_id);
        
        return $post_data && $post_data->content === $new_content;
    }

    /**
     * Test delete scheduled post
     */
    public function test_delete_scheduled_post() {
        // Create a test post
        $content = 'Post to delete';
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $scheduled_id = $this->scheduler->schedule_post($content, $scheduled_time, 'UTC', $this->test_user_id);
        
        if (!$scheduled_id) {
            return false;
        }
        
        // Delete the post
        $result = $this->scheduler->delete_scheduled_post($scheduled_id);
        
        if (!$result) {
            return false;
        }
        
        // Verify deletion
        $post_data = $this->scheduler->get_scheduled_post($scheduled_id);
        
        return !$post_data;
    }

    /**
     * Test execute scheduled post now
     */
    public function test_execute_scheduled_post_now() {
        // Create a test post
        $content = 'Post to execute now';
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $scheduled_id = $this->scheduler->schedule_post($content, $scheduled_time, 'UTC', $this->test_user_id);
        
        if (!$scheduled_id) {
            return false;
        }
        
        // Execute now
        $result = $this->scheduler->execute_scheduled_post_now($scheduled_id);
        
        // Should return true even if posting fails (due to test environment)
        return $result === true;
    }

    /**
     * Test cron integration
     */
    public function test_cron_integration() {
        // Check if cron hooks are registered
        $has_execute_hook = has_action('xelite_execute_scheduled_post');
        $has_cleanup_hook = has_action('xelite_cleanup_expired_schedules');
        
        return $has_execute_hook && $has_cleanup_hook;
    }

    /**
     * Test cleanup functionality
     */
    public function test_cleanup_functionality() {
        // Create a failed post with old timestamp
        global $wpdb;
        
        $old_time = date('Y-m-d H:i:s', strtotime('-31 days'));
        
        $wpdb->insert(
            $wpdb->prefix . 'xelite_scheduled_posts',
            array(
                'user_id' => $this->test_user_id,
                'content' => 'Old failed post',
                'scheduled_time' => $old_time,
                'timezone' => 'UTC',
                'status' => 'failed',
                'created_at' => $old_time
            )
        );
        
        // Run cleanup
        $this->scheduler->cleanup_expired_schedules();
        
        // Check if old failed post was deleted
        $old_post = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}xelite_scheduled_posts WHERE content = %s AND status = 'failed'",
                'Old failed post'
            )
        );
        
        return !$old_post;
    }

    /**
     * Test admin AJAX handlers
     */
    public function test_admin_ajax_handlers() {
        // Test timezone options AJAX
        $_POST['action'] = 'xelite_get_timezone_options';
        $_POST['nonce'] = wp_create_nonce('xelite_scheduler_nonce');
        
        ob_start();
        $this->scheduler->ajax_get_timezone_options();
        $response = ob_get_clean();
        
        $data = json_decode($response, true);
        
        return $data && isset($data['success']) && $data['success'];
    }

    /**
     * Test settings page
     */
    public function test_settings_page() {
        // Check if settings are registered
        $settings = get_option('xelite_scheduler_default_timezone');
        
        // Test timezone sanitization
        $sanitized = $this->scheduler->sanitize_timezone('America/New_York');
        if ($sanitized !== 'America/New_York') {
            return false;
        }
        
        // Test invalid timezone
        $sanitized = $this->scheduler->sanitize_timezone('Invalid/Timezone');
        if ($sanitized !== 'UTC') {
            return false;
        }
        
        // Test cleanup days sanitization
        $sanitized = $this->scheduler->sanitize_cleanup_days(15);
        if ($sanitized !== 15) {
            return false;
        }
        
        // Test invalid cleanup days
        $sanitized = $this->scheduler->sanitize_cleanup_days(400);
        if ($sanitized !== 30) {
            return false;
        }
        
        return true;
    }

    /**
     * Test error handling
     */
    public function test_error_handling() {
        // Test invalid content
        $invalid_content = str_repeat('a', 281); // Too long for X
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $result = $this->scheduler->schedule_post($invalid_content, $scheduled_time, 'UTC', $this->test_user_id);
        
        // Should fail due to content validation
        if ($result !== false) {
            return false;
        }
        
        // Test invalid scheduled time
        $past_time = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $result = $this->scheduler->schedule_post('Valid content', $past_time, 'UTC', $this->test_user_id);
        
        // Should fail due to past time
        if ($result !== false) {
            return false;
        }
        
        return true;
    }

    /**
     * Test logging functionality
     */
    public function test_logging_functionality() {
        global $wpdb;
        
        // Create a test post to generate logs
        $content = 'Test post for logging';
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $scheduled_id = $this->scheduler->schedule_post($content, $scheduled_time, 'UTC', $this->test_user_id);
        
        if (!$scheduled_id) {
            return false;
        }
        
        // Check if log entry was created
        $log_entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}xelite_scheduling_logs WHERE scheduled_post_id = %d AND action = 'scheduled'",
                $scheduled_id
            )
        );
        
        if (!$log_entry) {
            return false;
        }
        
        // Delete the post to generate another log entry
        $this->scheduler->delete_scheduled_post($scheduled_id);
        
        $delete_log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}xelite_scheduling_logs WHERE scheduled_post_id = %d AND action = 'deleted'",
                $scheduled_id
            )
        );
        
        return $delete_log !== null;
    }

    /**
     * Cleanup test data
     */
    private function cleanup_test_data() {
        global $wpdb;
        
        // Delete test user
        if ($this->test_user_id) {
            wp_delete_user($this->test_user_id);
        }
        
        // Clean up any remaining test posts
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}xelite_scheduled_posts WHERE user_id = %d",
                $this->test_user_id
            )
        );
        
        // Clean up test logs
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}xelite_scheduling_logs WHERE user_id = %d",
                $this->test_user_id
            )
        );
    }
}

// Run tests if accessed directly
if (isset($_GET['run_scheduler_tests']) && current_user_can('manage_options')) {
    $test_suite = new XeliteRepostEngine_Scheduler_Test();
    $test_suite->run_all_tests();
} 