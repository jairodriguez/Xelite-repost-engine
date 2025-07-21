<?php
/**
 * Test file for Subscription Events and Webhooks
 *
 * This file tests the WooCommerce subscription lifecycle events
 * and webhook functionality for the Xelite Repost Engine plugin.
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for subscription events and webhooks
 */
class Xelite_Subscription_Events_Test {

    /**
     * WooCommerce integration instance
     *
     * @var XeliteRepostEngine_WooCommerce
     */
    private $woocommerce;

    /**
     * Test subscription ID
     *
     * @var int
     */
    private $test_subscription_id = 0;

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

        // Get WooCommerce integration instance
        global $xelite_repost_engine;
        if (!$xelite_repost_engine) {
            echo '<div class="notice notice-error"><p>Xelite Repost Engine not initialized.</p></div>';
            return;
        }

        $this->woocommerce = $xelite_repost_engine->get_woocommerce();
        if (!$this->woocommerce) {
            echo '<div class="notice notice-error"><p>WooCommerce integration not available.</p></div>';
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>Subscription Events and Webhooks Tests</h1>';

        $this->test_database_table();
        $this->test_event_handlers();
        $this->test_webhook_endpoints();
        $this->test_email_functionality();
        $this->test_subscription_history();
        $this->test_manual_actions();

        echo '</div>';
    }

    /**
     * Test database table creation
     */
    private function test_database_table() {
        echo '<h2>Testing Database Table</h2>';

        // Test table creation
        $this->woocommerce->create_subscription_log_table();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'xelite_subscription_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        echo '<p><strong>Subscription Log Table:</strong> ' . ($table_exists ? '✅ Created' : '❌ Not Created') . '</p>';

        if ($table_exists) {
            // Test table structure
            $columns = $wpdb->get_results("DESCRIBE $table_name");
            $expected_columns = array('id', 'user_id', 'subscription_id', 'event_type', 'old_status', 'new_status', 'event_data', 'created_at');
            $actual_columns = array_column($columns, 'Field');
            
            $structure_correct = count(array_intersect($expected_columns, $actual_columns)) === count($expected_columns);
            echo '<p><strong>Table Structure:</strong> ' . ($structure_correct ? '✅ Correct' : '❌ Incorrect') . '</p>';
        }

        echo '<hr>';
    }

    /**
     * Test event handlers
     */
    private function test_event_handlers() {
        echo '<h2>Testing Event Handlers</h2>';

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            echo '<p>❌ No current user to test with.</p>';
            return;
        }

        // Test if event handlers are properly hooked
        $hooks = array(
            'woocommerce_subscription_created' => has_action('woocommerce_subscription_created'),
            'woocommerce_subscription_cancelled' => has_action('woocommerce_subscription_cancelled'),
            'woocommerce_subscription_expired' => has_action('woocommerce_subscription_expired'),
            'woocommerce_subscription_renewed' => has_action('woocommerce_subscription_renewed'),
            'woocommerce_subscription_payment_complete' => has_action('woocommerce_subscription_payment_complete'),
            'woocommerce_subscription_payment_failed' => has_action('woocommerce_subscription_payment_failed')
        );

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Event Hook</th><th>Status</th></tr></thead>';
        echo '<tbody>';

        foreach ($hooks as $hook => $priority) {
            echo '<tr>';
            echo '<td>' . esc_html($hook) . '</td>';
            echo '<td>' . ($priority ? '✅ Hooked (Priority: ' . $priority . ')' : '❌ Not Hooked') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<hr>';
    }

    /**
     * Test webhook endpoints
     */
    private function test_webhook_endpoints() {
        echo '<h2>Testing Webhook Endpoints</h2>';

        // Test REST API registration
        $rest_routes = rest_get_server()->get_routes();
        $webhook_route = '/xelite/v1/subscription-webhook';
        
        $route_exists = isset($rest_routes[$webhook_route]);
        echo '<p><strong>Webhook Route:</strong> ' . ($route_exists ? '✅ Registered' : '❌ Not Registered') . '</p>';

        if ($route_exists) {
            $route_data = $rest_routes[$webhook_route];
            $methods = array_keys($route_data[0]);
            echo '<p><strong>Supported Methods:</strong> ' . implode(', ', $methods) . '</p>';
        }

        // Test webhook URL
        $webhook_url = rest_url('xelite/v1/subscription-webhook');
        echo '<p><strong>Webhook URL:</strong> <code>' . esc_url($webhook_url) . '</code></p>';

        // Test webhook secret
        $webhook_secret = get_option('xelite_webhook_secret', '');
        echo '<p><strong>Webhook Secret:</strong> ' . ($webhook_secret ? '✅ Configured' : '⚠️ Not Configured') . '</p>';

        echo '<hr>';
    }

    /**
     * Test email functionality
     */
    private function test_email_functionality() {
        echo '<h2>Testing Email Functionality</h2>';

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            echo '<p>❌ No current user to test with.</p>';
            return;
        }

        $user = get_user_by('id', $current_user_id);
        
        // Test email sending capability
        $test_subject = 'Test Email from Repost Intelligence';
        $test_message = '<p>This is a test email to verify email functionality.</p>';
        
        $email_sent = $this->woocommerce->send_manual_subscription_email($current_user_id, 'welcome');
        echo '<p><strong>Email Sending:</strong> ' . ($email_sent ? '✅ Working' : '❌ Failed') . '</p>';

        // Test email content generation
        $subscriptions = wcs_get_users_subscriptions($current_user_id);
        $subscription = null;
        
        foreach ($subscriptions as $sub) {
            if ($sub->has_status('active')) {
                $subscription = $sub;
                break;
            }
        }

        if ($subscription) {
            $tier = $this->woocommerce->get_user_subscription_tier($current_user_id, $subscription);
            echo '<p><strong>Current Tier:</strong> ' . esc_html($tier) . '</p>';
            
            // Test different email types
            $email_types = array('welcome', 'cancellation', 'expiration', 'payment_failure');
            echo '<p><strong>Email Types Available:</strong> ' . implode(', ', $email_types) . '</p>';
        } else {
            echo '<p>⚠️ No active subscription found for testing email content.</p>';
        }

        echo '<hr>';
    }

    /**
     * Test subscription history
     */
    private function test_subscription_history() {
        echo '<h2>Testing Subscription History</h2>';

        // Test history retrieval
        $history = $this->woocommerce->get_subscription_history(0, 10, 0);
        
        echo '<p><strong>History Records:</strong> ' . count($history) . ' found</p>';

        if (!empty($history)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Date</th><th>User</th><th>Event</th><th>Subscription ID</th><th>Status Change</th></tr></thead>';
            echo '<tbody>';

            foreach (array_slice($history, 0, 5) as $event) {
                echo '<tr>';
                echo '<td>' . esc_html($event['created_at']) . '</td>';
                echo '<td>' . esc_html($event['user_id']) . '</td>';
                echo '<td>' . esc_html($event['event_type']) . '</td>';
                echo '<td>' . esc_html($event['subscription_id']) . '</td>';
                echo '<td>' . esc_html($event['old_status'] . ' → ' . $event['new_status']) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>No subscription history found.</p>';
        }

        echo '<hr>';
    }

    /**
     * Test manual actions
     */
    private function test_manual_actions() {
        echo '<h2>Testing Manual Actions</h2>';

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            echo '<p>❌ No current user to test with.</p>';
            return;
        }

        // Test cache operations
        $tier_before = $this->woocommerce->get_user_tier($current_user_id);
        $this->woocommerce->clear_user_cache($current_user_id);
        $tier_after = $this->woocommerce->get_user_tier($current_user_id);
        
        echo '<p><strong>Cache Operations:</strong> ' . ($tier_before === $tier_after ? '✅ Working' : '❌ Failed') . '</p>';

        // Test subscription refresh
        $this->woocommerce->refresh_user_subscription($current_user_id);
        $tier_refreshed = $this->woocommerce->get_user_tier($current_user_id);
        
        echo '<p><strong>Subscription Refresh:</strong> ' . ($tier_refreshed === $tier_after ? '✅ Working' : '❌ Failed') . '</p>';

        // Test subscription status
        $status = $this->woocommerce->get_user_subscription_status($current_user_id);
        echo '<p><strong>Subscription Status:</strong> ' . esc_html(json_encode($status, JSON_PRETTY_PRINT)) . '</p>';

        echo '<hr>';
    }

    /**
     * Simulate subscription event (for testing)
     *
     * @param string $event_type Event type.
     * @param int $user_id User ID.
     * @param array $event_data Event data.
     */
    public function simulate_subscription_event($event_type, $user_id, $event_data = array()) {
        if (!current_user_can('manage_options')) {
            return false;
        }

        // Create a mock subscription object for testing
        $mock_subscription = new stdClass();
        $mock_subscription->id = 999999;
        $mock_subscription->user_id = $user_id;
        $mock_subscription->total = 29.99;
        $mock_subscription->status = 'active';
        
        // Mock methods
        $mock_subscription->get_id = function() { return 999999; };
        $mock_subscription->get_user_id = function() use ($user_id) { return $user_id; };
        $mock_subscription->get_total = function() { return 29.99; };
        $mock_subscription->get_date = function($date_type) { 
            return current_time('mysql'); 
        };
        $mock_subscription->has_status = function($status) { 
            return $status === 'active'; 
        };
        $mock_subscription->get_items = function() { 
            return array(); 
        };

        // Log the event
        $this->woocommerce->log_subscription_event($user_id, $mock_subscription, $event_type, null, 'active', $event_data);
        
        return true;
    }

    /**
     * Test webhook signature verification
     *
     * @param string $payload Payload data.
     * @param string $secret Secret key.
     * @param string $signature Expected signature.
     * @return bool True if signature is valid.
     */
    public function test_webhook_signature($payload, $secret, $signature) {
        $expected_signature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected_signature, $signature);
    }
}

// Initialize test class
new Xelite_Subscription_Events_Test(); 