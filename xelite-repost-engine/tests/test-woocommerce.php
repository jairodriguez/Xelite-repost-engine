<?php
/**
 * Test suite for WooCommerce Integration
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for WooCommerce Integration
 */
class Test_XeliteRepostEngine_WooCommerce extends WP_UnitTestCase {

    /**
     * WooCommerce instance
     *
     * @var XeliteRepostEngine_WooCommerce
     */
    private $woocommerce;

    /**
     * Database mock
     *
     * @var XeliteRepostEngine_Database
     */
    private $database_mock;

    /**
     * User meta mock
     *
     * @var XeliteRepostEngine_User_Meta
     */
    private $user_meta_mock;

    /**
     * Logger mock
     *
     * @var XeliteRepostEngine_Logger
     */
    private $logger_mock;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create mocks
        $this->database_mock = $this->createMock('XeliteRepostEngine_Database');
        $this->user_meta_mock = $this->createMock('XeliteRepostEngine_User_Meta');
        $this->logger_mock = $this->createMock('XeliteRepostEngine_Logger');
        
        // Create WooCommerce instance
        $this->woocommerce = new XeliteRepostEngine_WooCommerce($this->database_mock, $this->user_meta_mock, $this->logger_mock);
    }

    /**
     * Test constructor initialization
     */
    public function test_constructor_initialization() {
        $this->assertInstanceOf('XeliteRepostEngine_WooCommerce', $this->woocommerce);
    }

    /**
     * Test WooCommerce active detection
     */
    public function test_is_woocommerce_active() {
        // Test when WooCommerce is not active
        $this->assertFalse($this->woocommerce->is_woocommerce_active());
        
        // Mock WooCommerce classes
        if (!class_exists('WooCommerce')) {
            eval('class WooCommerce {}');
        }
        if (!class_exists('WC_Subscriptions')) {
            eval('class WC_Subscriptions {}');
        }
        
        // Test when both are active
        $this->assertTrue($this->woocommerce->is_woocommerce_active());
    }

    /**
     * Test subscription status change handling
     */
    public function test_subscription_status_changed() {
        // Create mock subscription
        $subscription_mock = $this->createMock('WC_Subscription');
        $subscription_mock->method('get_user_id')->willReturn(1);
        $subscription_mock->method('get_id')->willReturn(123);
        
        // Test activation
        $this->woocommerce->subscription_status_changed($subscription_mock, 'active', 'pending');
        
        // Verify user meta was updated
        $this->assertEquals('active', get_user_meta(1, 'xelite_subscription_status', true));
        
        // Test deactivation
        $this->woocommerce->subscription_status_changed($subscription_mock, 'cancelled', 'active');
        
        // Verify user meta was updated
        $this->assertEquals('cancelled', get_user_meta(1, 'xelite_subscription_status', true));
    }

    /**
     * Test user subscription tier detection
     */
    public function test_get_user_subscription_tier() {
        // Test with no active subscription
        $tier = $this->woocommerce->get_user_subscription_tier(1);
        $this->assertEquals('none', $tier);
        
        // Test with mock subscription
        $subscription_mock = $this->createMock('WC_Subscription');
        $subscription_mock->method('has_status')->with('active')->willReturn(true);
        
        $item_mock = $this->createMock('WC_Order_Item');
        $item_mock->method('get_product_id')->willReturn(123);
        
        $subscription_mock->method('get_items')->willReturn(array($item_mock));
        
        // Set up tier mapping
        update_option('xelite_woocommerce_tier_mapping', array('123' => 'premium'));
        
        $tier = $this->woocommerce->get_user_subscription_tier(1, $subscription_mock);
        $this->assertEquals('premium', $tier);
    }

    /**
     * Test feature access control
     */
    public function test_can_user_access_feature() {
        // Test with no user logged in
        $this->assertFalse($this->woocommerce->can_user_access_feature('view_patterns'));
        
        // Create test user
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);
        
        // Test with no subscription
        $this->assertFalse($this->woocommerce->can_user_access_feature('view_patterns'));
        
        // Test with basic subscription
        update_user_meta($user_id, 'xelite_subscription_tier', 'basic');
        $user = get_user_by('id', $user_id);
        $user->add_cap('xelite_view_patterns');
        
        $this->assertTrue($this->woocommerce->can_user_access_feature('view_patterns'));
        $this->assertFalse($this->woocommerce->can_user_access_feature('scheduling'));
    }

    /**
     * Test content generation limit checking
     */
    public function test_check_content_generation_limit() {
        $user_id = $this->factory->user->create();
        update_user_meta($user_id, 'xelite_subscription_tier', 'basic');
        
        $current_month = date('Y-m');
        $count_key = "xelite_generation_count_{$current_month}";
        
        // Test with no previous generations
        $this->assertTrue($this->woocommerce->can_user_access_feature('generate_content_limited', $user_id));
        
        // Test with limit reached
        update_user_meta($user_id, $count_key, 10);
        $this->assertFalse($this->woocommerce->can_user_access_feature('generate_content_limited', $user_id));
        
        // Test premium tier (no limit)
        update_user_meta($user_id, 'xelite_subscription_tier', 'premium');
        $this->assertTrue($this->woocommerce->can_user_access_feature('generate_content_limited', $user_id));
    }

    /**
     * Test generation count increment
     */
    public function test_increment_generation_count() {
        $user_id = $this->factory->user->create();
        $current_month = date('Y-m');
        $count_key = "xelite_generation_count_{$current_month}";
        
        // Test initial increment
        $this->woocommerce->increment_generation_count($user_id);
        $this->assertEquals(1, get_user_meta($user_id, $count_key, true));
        
        // Test subsequent increment
        $this->woocommerce->increment_generation_count($user_id);
        $this->assertEquals(2, get_user_meta($user_id, $count_key, true));
    }

    /**
     * Test user registration handling
     */
    public function test_user_registered() {
        $user_id = $this->factory->user->create();
        
        // Test with no subscription
        $this->woocommerce->user_registered($user_id);
        $this->assertEquals('none', get_user_meta($user_id, 'xelite_subscription_tier', true));
        
        // Test with active subscription
        $subscription_mock = $this->createMock('WC_Subscription');
        $subscription_mock->method('has_status')->with('active')->willReturn(true);
        
        $item_mock = $this->createMock('WC_Order_Item');
        $item_mock->method('get_product_id')->willReturn(123);
        
        $subscription_mock->method('get_items')->willReturn(array($item_mock));
        
        // Mock wcs_get_users_subscriptions function
        if (!function_exists('wcs_get_users_subscriptions')) {
            function wcs_get_users_subscriptions($user_id) {
                global $mock_subscriptions;
                return isset($mock_subscriptions[$user_id]) ? $mock_subscriptions[$user_id] : array();
            }
        }
        
        global $mock_subscriptions;
        $mock_subscriptions = array($user_id => array($subscription_mock));
        
        update_option('xelite_woocommerce_tier_mapping', array('123' => 'basic'));
        
        $this->woocommerce->user_registered($user_id);
        $this->assertEquals('basic', get_user_meta($user_id, 'xelite_subscription_tier', true));
    }

    /**
     * Test user login handling
     */
    public function test_user_logged_in() {
        $user_id = $this->factory->user->create();
        $user = get_user_by('id', $user_id);
        
        // Test with no subscription change
        update_user_meta($user_id, 'xelite_subscription_tier', 'basic');
        $this->woocommerce->user_logged_in('testuser', $user);
        $this->assertEquals('basic', get_user_meta($user_id, 'xelite_subscription_tier', true));
        
        // Test with subscription deactivation
        global $mock_subscriptions;
        $mock_subscriptions = array($user_id => array());
        
        $this->woocommerce->user_logged_in('testuser', $user);
        $this->assertEquals('none', get_user_meta($user_id, 'xelite_subscription_tier', true));
    }

    /**
     * Test AJAX subscription check
     */
    public function test_ajax_check_subscription() {
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);
        
        update_user_meta($user_id, 'xelite_subscription_tier', 'premium');
        update_user_meta($user_id, 'xelite_subscription_status', 'active');
        
        // Mock nonce
        $_POST['nonce'] = wp_create_nonce('xelite_woocommerce_nonce');
        
        // Capture output
        ob_start();
        $this->woocommerce->ajax_check_subscription();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('premium', $response['data']['tier']);
        $this->assertEquals('active', $response['data']['status']);
    }

    /**
     * Test user features based on tier
     */
    public function test_get_user_features() {
        $user_id = $this->factory->user->create();
        
        // Test basic tier features
        update_user_meta($user_id, 'xelite_subscription_tier', 'basic');
        
        $reflection = new ReflectionClass($this->woocommerce);
        $method = $reflection->getMethod('get_user_features');
        $method->setAccessible(true);
        
        $features = $method->invoke($this->woocommerce, $user_id);
        
        $this->assertTrue($features['view_patterns']);
        $this->assertTrue($features['generate_content']);
        $this->assertFalse($features['scheduling']);
        $this->assertFalse($features['multi_account']);
        
        // Test premium tier features
        update_user_meta($user_id, 'xelite_subscription_tier', 'premium');
        $features = $method->invoke($this->woocommerce, $user_id);
        
        $this->assertTrue($features['view_patterns']);
        $this->assertTrue($features['generate_content']);
        $this->assertTrue($features['scheduling']);
        $this->assertTrue($features['analytics']);
        $this->assertFalse($features['multi_account']);
        $this->assertFalse($features['advanced_ai']);
        
        // Test enterprise tier features
        update_user_meta($user_id, 'xelite_subscription_tier', 'enterprise');
        $features = $method->invoke($this->woocommerce, $user_id);
        
        $this->assertTrue($features['view_patterns']);
        $this->assertTrue($features['generate_content']);
        $this->assertTrue($features['scheduling']);
        $this->assertTrue($features['multi_account']);
        $this->assertTrue($features['analytics']);
        $this->assertTrue($features['advanced_ai']);
    }

    /**
     * Test user limits based on tier
     */
    public function test_get_user_limits() {
        $user_id = $this->factory->user->create();
        $current_month = date('Y-m');
        
        // Test basic tier limits
        update_user_meta($user_id, 'xelite_subscription_tier', 'basic');
        update_user_meta($user_id, "xelite_generation_count_{$current_month}", 5);
        
        $reflection = new ReflectionClass($this->woocommerce);
        $method = $reflection->getMethod('get_user_limits');
        $method->setAccessible(true);
        
        $limits = $method->invoke($this->woocommerce, $user_id);
        
        $this->assertEquals(10, $limits['monthly_generations']);
        $this->assertEquals(5, $limits['current_generations']);
        
        // Test premium tier limits
        update_user_meta($user_id, 'xelite_subscription_tier', 'premium');
        $limits = $method->invoke($this->woocommerce, $user_id);
        
        $this->assertEquals(100, $limits['monthly_generations']);
        $this->assertEquals(5, $limits['current_generations']);
        
        // Test enterprise tier limits
        update_user_meta($user_id, 'xelite_subscription_tier', 'enterprise');
        $limits = $method->invoke($this->woocommerce, $user_id);
        
        $this->assertEquals(-1, $limits['monthly_generations']); // Unlimited
        $this->assertEquals(5, $limits['current_generations']);
    }

    /**
     * Test tier mapping sanitization
     */
    public function test_sanitize_tier_mapping() {
        // Test valid JSON input
        $input = '{"123": "basic", "456": "premium"}';
        $sanitized = $this->woocommerce->sanitize_tier_mapping($input);
        
        $this->assertEquals('basic', $sanitized['123']);
        $this->assertEquals('premium', $sanitized['456']);
        
        // Test array input
        $input = array('123' => 'basic', '456' => 'premium');
        $sanitized = $this->woocommerce->sanitize_tier_mapping($input);
        
        $this->assertEquals('basic', $sanitized['123']);
        $this->assertEquals('premium', $sanitized['456']);
        
        // Test invalid input
        $input = 'invalid json';
        $sanitized = $this->woocommerce->sanitize_tier_mapping($input);
        
        $this->assertEmpty($sanitized);
    }

    /**
     * Test user activity logging
     */
    public function test_log_user_activity() {
        $user_id = $this->factory->user->create();
        
        $this->database_mock->expects($this->once())
            ->method('insert')
            ->with('user_activity', $this->callback(function($data) use ($user_id) {
                return $data['user_id'] == $user_id && 
                       $data['action'] == 'subscription_activated' &&
                       isset($data['timestamp']);
            }));
        
        $reflection = new ReflectionClass($this->woocommerce);
        $method = $reflection->getMethod('log_user_activity');
        $method->setAccessible(true);
        
        $method->invoke($this->woocommerce, $user_id, 'subscription_activated', array('tier' => 'basic'));
    }

    /**
     * Test feature access checking
     */
    public function test_check_feature_access() {
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);
        
        // Test with no access
        $this->expectException('WPDieException');
        $this->woocommerce->check_feature_access('scheduling');
    }

    /**
     * Test integration status
     */
    public function test_is_integration_active() {
        // Test when WooCommerce is not active
        $this->assertFalse($this->woocommerce->is_integration_active());
        
        // Mock WooCommerce classes
        if (!class_exists('WooCommerce')) {
            eval('class WooCommerce {}');
        }
        if (!class_exists('WC_Subscriptions')) {
            eval('class WC_Subscriptions {}');
        }
        
        // Create new instance with active WooCommerce
        $woocommerce_active = new XeliteRepostEngine_WooCommerce($this->database_mock, $this->user_meta_mock, $this->logger_mock);
        $this->assertTrue($woocommerce_active->is_integration_active());
    }

    /**
     * Test capability setting and removal
     */
    public function test_user_capabilities_management() {
        $user_id = $this->factory->user->create();
        $user = get_user_by('id', $user_id);
        
        // Test setting capabilities
        $reflection = new ReflectionClass($this->woocommerce);
        $set_method = $reflection->getMethod('set_user_capabilities');
        $set_method->setAccessible(true);
        
        $set_method->invoke($this->woocommerce, $user_id, 'basic');
        
        $this->assertTrue($user->has_cap('xelite_view_patterns'));
        $this->assertTrue($user->has_cap('xelite_generate_content_limited'));
        $this->assertFalse($user->has_cap('xelite_scheduling'));
        
        // Test removing capabilities
        $remove_method = $reflection->getMethod('remove_user_capabilities');
        $remove_method->setAccessible(true);
        
        $remove_method->invoke($this->woocommerce, $user_id);
        
        $this->assertFalse($user->has_cap('xelite_view_patterns'));
        $this->assertFalse($user->has_cap('xelite_generate_content_limited'));
    }

    /**
     * Test tier capabilities mapping
     */
    public function test_get_tier_capabilities() {
        $reflection = new ReflectionClass($this->woocommerce);
        $method = $reflection->getMethod('get_tier_capabilities');
        $method->setAccessible(true);
        
        // Test basic tier
        $capabilities = $method->invoke($this->woocommerce, 'basic');
        $this->assertContains('xelite_view_patterns', $capabilities);
        $this->assertContains('xelite_generate_content_limited', $capabilities);
        $this->assertNotContains('xelite_scheduling', $capabilities);
        
        // Test premium tier
        $capabilities = $method->invoke($this->woocommerce, 'premium');
        $this->assertContains('xelite_view_patterns', $capabilities);
        $this->assertContains('xelite_generate_content_unlimited', $capabilities);
        $this->assertContains('xelite_scheduling', $capabilities);
        $this->assertContains('xelite_analytics', $capabilities);
        
        // Test enterprise tier
        $capabilities = $method->invoke($this->woocommerce, 'enterprise');
        $this->assertContains('xelite_view_patterns', $capabilities);
        $this->assertContains('xelite_generate_content_unlimited', $capabilities);
        $this->assertContains('xelite_scheduling', $capabilities);
        $this->assertContains('xelite_multi_account', $capabilities);
        $this->assertContains('xelite_analytics', $capabilities);
        $this->assertContains('xelite_advanced_ai', $capabilities);
        
        // Test invalid tier
        $capabilities = $method->invoke($this->woocommerce, 'invalid');
        $this->assertEmpty($capabilities);
    }

    /**
     * Test admin notice display
     */
    public function test_woocommerce_missing_notice() {
        // Test when user doesn't have manage_options capability
        ob_start();
        $this->woocommerce->woocommerce_missing_notice();
        $output = ob_get_clean();
        $this->assertEmpty($output);
        
        // Test with admin user
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);
        
        ob_start();
        $this->woocommerce->woocommerce_missing_notice();
        $output = ob_get_clean();
        
        $this->assertContains('WooCommerce', $output);
        $this->assertContains('WooCommerce Subscriptions', $output);
    }

    /**
     * Test upgrade notice display
     */
    public function test_upgrade_notice() {
        // Test when user doesn't have manage_options capability
        ob_start();
        $this->woocommerce->upgrade_notice();
        $output = ob_get_clean();
        $this->assertEmpty($output);
        
        // Test with admin user
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);
        
        ob_start();
        $this->woocommerce->upgrade_notice();
        $output = ob_get_clean();
        
        $this->assertContains('Repost Intelligence features', $output);
        $this->assertContains('configure your subscription', $output);
    }

    /**
     * Test settings registration
     */
    public function test_register_settings() {
        $this->woocommerce->register_settings();
        
        // Verify setting is registered
        global $wp_settings_sections;
        $this->assertArrayHasKey('xelite_woocommerce_settings', $wp_settings_sections);
    }

    /**
     * Test settings page rendering
     */
    public function test_render_settings_page() {
        // Test with WooCommerce not active
        ob_start();
        $this->woocommerce->render_settings_page();
        $output = ob_get_clean();
        
        $this->assertContains('WooCommerce Integration Settings', $output);
        $this->assertContains('WooCommerce and WooCommerce Subscriptions must be installed', $output);
        
        // Test with WooCommerce active
        if (!class_exists('WooCommerce')) {
            eval('class WooCommerce {}');
        }
        if (!class_exists('WC_Subscriptions')) {
            eval('class WC_Subscriptions {}');
        }
        
        $woocommerce_active = new XeliteRepostEngine_WooCommerce($this->database_mock, $this->user_meta_mock, $this->logger_mock);
        
        ob_start();
        $woocommerce_active->render_settings_page();
        $output = ob_get_clean();
        
        $this->assertContains('WooCommerce Integration Settings', $output);
        $this->assertContains('Product ID to Tier Mapping', $output);
        $this->assertContains('xelite_woocommerce_tier_mapping', $output);
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up user meta
        $users = get_users(array('fields' => 'ID'));
        foreach ($users as $user_id) {
            delete_user_meta($user_id, 'xelite_subscription_tier');
            delete_user_meta($user_id, 'xelite_subscription_status');
            delete_user_meta($user_id, 'xelite_features_activated');
            delete_user_meta($user_id, 'xelite_features_deactivated');
            
            // Clean up generation counts
            $current_month = date('Y-m');
            delete_user_meta($user_id, "xelite_generation_count_{$current_month}");
        }
        
        // Clean up options
        delete_option('xelite_woocommerce_tier_mapping');
        
        // Clean up global variables
        global $mock_subscriptions;
        unset($mock_subscriptions);
        
        parent::tearDown();
    }
} 