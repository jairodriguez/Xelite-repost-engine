<?php
/**
 * Test file for Subscription Tier Management and Feature Access Control
 *
 * This file tests the WooCommerce integration subscription tier management
 * and feature access control functionality.
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for subscription tier management
 */
class Xelite_Subscription_Tier_Test {

    /**
     * WooCommerce integration instance
     *
     * @var XeliteRepostEngine_WooCommerce
     */
    private $woocommerce;

    /**
     * Test user IDs
     *
     * @var array
     */
    private $test_users = array();

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
        echo '<h1>Subscription Tier Management Tests</h1>';

        $this->test_subscription_detection();
        $this->test_feature_access_control();
        $this->test_caching_functionality();
        $this->test_admin_settings();
        $this->test_generation_limits();
        $this->test_helper_methods();

        echo '</div>';
    }

    /**
     * Test subscription detection functionality
     */
    private function test_subscription_detection() {
        echo '<h2>Testing Subscription Detection</h2>';

        // Test WooCommerce active check
        $is_active = $this->woocommerce->is_woocommerce_active();
        echo '<p><strong>WooCommerce Active:</strong> ' . ($is_active ? '✅ Yes' : '❌ No') . '</p>';

        // Test integration active check
        $integration_active = $this->woocommerce->is_integration_active();
        echo '<p><strong>Integration Active:</strong> ' . ($integration_active ? '✅ Yes' : '❌ No') . '</p>';

        // Test current user subscription status
        $current_user_id = get_current_user_id();
        if ($current_user_id) {
            $tier = $this->woocommerce->get_user_tier($current_user_id);
            $has_subscription = $this->woocommerce->has_active_subscription($current_user_id);
            $status = $this->woocommerce->get_user_subscription_status($current_user_id);

            echo '<p><strong>Current User Tier:</strong> ' . esc_html($tier) . '</p>';
            echo '<p><strong>Has Active Subscription:</strong> ' . ($has_subscription ? '✅ Yes' : '❌ No') . '</p>';
            echo '<p><strong>Subscription Status:</strong> ' . esc_html(json_encode($status, JSON_PRETTY_PRINT)) . '</p>';
        }

        echo '<hr>';
    }

    /**
     * Test feature access control
     */
    private function test_feature_access_control() {
        echo '<h2>Testing Feature Access Control</h2>';

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            echo '<p>❌ No current user to test with.</p>';
            return;
        }

        $features = array(
            'view_patterns',
            'generate_content',
            'generate_content_limited',
            'scheduling',
            'multi_account',
            'analytics',
            'advanced_ai'
        );

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Feature</th><th>Access</th><th>Method</th></tr></thead>';
        echo '<tbody>';

        foreach ($features as $feature) {
            $can_access = $this->woocommerce->can_access_feature($feature, $current_user_id);
            $can_access_alt = $this->woocommerce->can_user_access_feature($feature, $current_user_id);

            echo '<tr>';
            echo '<td>' . esc_html($feature) . '</td>';
            echo '<td>' . ($can_access ? '✅ Yes' : '❌ No') . '</td>';
            echo '<td>' . ($can_access === $can_access_alt ? '✅ Consistent' : '❌ Inconsistent') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<hr>';
    }

    /**
     * Test caching functionality
     */
    private function test_caching_functionality() {
        echo '<h2>Testing Caching Functionality</h2>';

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            echo '<p>❌ No current user to test with.</p>';
            return;
        }

        // Test cache operations
        $tier_before = $this->woocommerce->get_user_tier($current_user_id);
        $this->woocommerce->clear_user_cache($current_user_id);
        $tier_after = $this->woocommerce->get_user_tier($current_user_id);

        echo '<p><strong>Cache Clear Test:</strong> ' . ($tier_before === $tier_after ? '✅ Consistent' : '❌ Inconsistent') . '</p>';

        // Test refresh functionality
        $this->woocommerce->refresh_user_subscription($current_user_id);
        $tier_refreshed = $this->woocommerce->get_user_tier($current_user_id);

        echo '<p><strong>Refresh Test:</strong> ' . ($tier_refreshed === $tier_after ? '✅ Consistent' : '❌ Inconsistent') . '</p>';

        echo '<hr>';
    }

    /**
     * Test admin settings functionality
     */
    private function test_admin_settings() {
        echo '<h2>Testing Admin Settings</h2>';

        // Test tier mapping
        $tier_mapping = $this->woocommerce->get_tier_mapping();
        echo '<p><strong>Tier Mapping:</strong> ' . esc_html(json_encode($tier_mapping, JSON_PRETTY_PRINT)) . '</p>';

        // Test subscription products
        $subscription_products = $this->woocommerce->get_subscription_products();
        echo '<p><strong>Available Subscription Products:</strong> ' . count($subscription_products) . ' found</p>';

        if (!empty($subscription_products)) {
            echo '<ul>';
            foreach ($subscription_products as $product) {
                echo '<li>' . esc_html($product['name']) . ' (ID: ' . esc_html($product['id']) . ') - ' . esc_html($product['price']) . '</li>';
            }
            echo '</ul>';
        }

        // Test generation limits
        $basic_limit = get_option('xelite_basic_generation_limit', 10);
        $premium_limit = get_option('xelite_premium_generation_limit', 100);
        $enterprise_limit = get_option('xelite_enterprise_generation_limit', -1);

        echo '<p><strong>Generation Limits:</strong></p>';
        echo '<ul>';
        echo '<li>Basic: ' . esc_html($basic_limit) . ' per month</li>';
        echo '<li>Premium: ' . esc_html($premium_limit) . ' per month</li>';
        echo '<li>Enterprise: ' . ($enterprise_limit === -1 ? 'Unlimited' : esc_html($enterprise_limit)) . ' per month</li>';
        echo '</ul>';

        echo '<hr>';
    }

    /**
     * Test generation limits
     */
    private function test_generation_limits() {
        echo '<h2>Testing Generation Limits</h2>';

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            echo '<p>❌ No current user to test with.</p>';
            return;
        }

        $tier = $this->woocommerce->get_user_tier($current_user_id);
        $limits = $this->woocommerce->get_user_limits($current_user_id);

        echo '<p><strong>Current User Tier:</strong> ' . esc_html($tier) . '</p>';
        echo '<p><strong>Generation Limits:</strong> ' . esc_html(json_encode($limits, JSON_PRETTY_PRINT)) . '</p>';

        // Test generation count increment
        $current_month = date('Y-m');
        $count_before = intval(get_user_meta($current_user_id, "xelite_generation_count_{$current_month}", true));
        
        $this->woocommerce->increment_generation_count($current_user_id);
        
        $count_after = intval(get_user_meta($current_user_id, "xelite_generation_count_{$current_month}", true));

        echo '<p><strong>Generation Count Test:</strong> ' . ($count_after === $count_before + 1 ? '✅ Incremented' : '❌ Failed') . '</p>';

        echo '<hr>';
    }

    /**
     * Test helper methods
     */
    private function test_helper_methods() {
        echo '<h2>Testing Helper Methods</h2>';

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            echo '<p>❌ No current user to test with.</p>';
            return;
        }

        // Test helper methods
        $methods = array(
            'can_access_feature' => $this->woocommerce->can_access_feature('view_patterns', $current_user_id),
            'has_active_subscription' => $this->woocommerce->has_active_subscription($current_user_id),
            'get_user_tier' => $this->woocommerce->get_user_tier($current_user_id),
            'get_user_subscription_status' => $this->woocommerce->get_user_subscription_status($current_user_id)
        );

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Method</th><th>Result</th></tr></thead>';
        echo '<tbody>';

        foreach ($methods as $method => $result) {
            echo '<tr>';
            echo '<td>' . esc_html($method) . '</td>';
            echo '<td>' . esc_html(is_bool($result) ? ($result ? 'true' : 'false') : json_encode($result)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<hr>';
    }

    /**
     * Get subscription products (public method for testing)
     */
    public function get_subscription_products() {
        return $this->woocommerce->get_subscription_products();
    }

    /**
     * Get tier mapping (public method for testing)
     */
    public function get_tier_mapping() {
        return $this->woocommerce->get_tier_mapping();
    }
}

// Initialize test class
new Xelite_Subscription_Tier_Test(); 