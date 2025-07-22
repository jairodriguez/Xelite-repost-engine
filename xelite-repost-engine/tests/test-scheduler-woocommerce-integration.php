<?php
/**
 * Test file for Scheduler WooCommerce Integration
 *
 * Tests the integration between the scheduler and WooCommerce subscription tiers
 * for feature access control and scheduling limits.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for Scheduler WooCommerce Integration
 */
class Xelite_Scheduler_WooCommerce_Test extends WP_UnitTestCase {

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
     * WooCommerce instance
     *
     * @var XeliteRepostEngine_WooCommerce
     */
    private $woocommerce;

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
    private $user_id;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create test user
        $this->user_id = $this->factory->user->create(array('role' => 'subscriber'));
        
        // Initialize services
        $this->database = new XeliteRepostEngine_Database();
        $this->user_meta = new XeliteRepostEngine_User_Meta();
        $this->woocommerce = new XeliteRepostEngine_WooCommerce($this->database, $this->user_meta, null);
        $this->x_poster = new XeliteRepostEngine_X_Poster($this->database, $this->user_meta, null, $this->woocommerce);
        $this->scheduler = new XeliteRepostEngine_Scheduler($this->database, $this->user_meta, $this->x_poster, null, $this->woocommerce);
        
        // Create scheduling tables
        $this->scheduler->create_scheduling_tables();
    }

    /**
     * Test subscription access checking
     */
    public function test_check_subscription_access() {
        // Test without WooCommerce integration
        $scheduler_no_wc = new XeliteRepostEngine_Scheduler($this->database, $this->user_meta, $this->x_poster, null, null);
        $result = $scheduler_no_wc->check_subscription_access($this->user_id, 'scheduling');
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals('WooCommerce integration not available', $result['reason']);
        
        // Test with WooCommerce integration but no subscription
        $result = $this->scheduler->check_subscription_access($this->user_id, 'scheduling');
        
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('subscription', strtolower($result['reason']));
    }

    /**
     * Test scheduling limits checking
     */
    public function test_check_scheduling_limits() {
        // Test without WooCommerce integration
        $scheduler_no_wc = new XeliteRepostEngine_Scheduler($this->database, $this->user_meta, $this->x_poster, null, null);
        $result = $scheduler_no_wc->check_scheduling_limits($this->user_id);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals('WooCommerce integration not available', $result['reason']);
        
        // Test with WooCommerce integration
        $result = $this->scheduler->check_scheduling_limits($this->user_id);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals('Within scheduling limits', $result['reason']);
        $this->assertEquals(0, $result['current']);
        $this->assertGreaterThan(0, $result['limit']);
    }

    /**
     * Test getting user scheduling limits
     */
    public function test_get_user_scheduling_limits() {
        // Test without WooCommerce integration
        $scheduler_no_wc = new XeliteRepostEngine_Scheduler($this->database, $this->user_meta, $this->x_poster, null, null);
        $limits = $scheduler_no_wc->get_user_scheduling_limits($this->user_id);
        
        $this->assertEquals(10, $limits['max_scheduled_posts']);
        $this->assertEquals(30, $limits['scheduling_window_days']);
        $this->assertTrue($limits['can_schedule_media']);
        
        // Test with WooCommerce integration
        $limits = $this->scheduler->get_user_scheduling_limits($this->user_id);
        
        $this->assertArrayHasKey('max_scheduled_posts', $limits);
        $this->assertArrayHasKey('scheduling_window_days', $limits);
        $this->assertArrayHasKey('can_schedule_media', $limits);
    }

    /**
     * Test getting user scheduled count
     */
    public function test_get_user_scheduled_count() {
        // Initially should be 0
        $count = $this->scheduler->get_user_scheduled_count($this->user_id);
        $this->assertEquals(0, $count);
        
        // Add a scheduled post
        global $wpdb;
        $table = $wpdb->prefix . 'xelite_scheduled_posts';
        $wpdb->insert($table, array(
            'user_id' => $this->user_id,
            'content' => 'Test scheduled post',
            'scheduled_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'timezone' => 'UTC',
            'status' => 'scheduled',
            'created_at' => current_time('mysql')
        ));
        
        $count = $this->scheduler->get_user_scheduled_count($this->user_id);
        $this->assertEquals(1, $count);
    }

    /**
     * Test scheduling post with subscription checks
     */
    public function test_schedule_post_with_subscription_checks() {
        // Test scheduling without subscription (should fail)
        $result = $this->scheduler->schedule_post(
            'Test scheduled post',
            date('Y-m-d H:i:s', strtotime('+1 hour')),
            'UTC',
            $this->user_id
        );
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertTrue($result['subscription_required']);
        $this->assertStringContainsString('subscription', strtolower($result['error']));
    }

    /**
     * Test executing scheduled post with subscription checks
     */
    public function test_execute_scheduled_post_with_subscription_checks() {
        // Create a scheduled post
        global $wpdb;
        $table = $wpdb->prefix . 'xelite_scheduled_posts';
        $scheduled_id = $wpdb->insert($table, array(
            'user_id' => $this->user_id,
            'content' => 'Test scheduled post',
            'scheduled_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'timezone' => 'UTC',
            'status' => 'scheduled',
            'created_at' => current_time('mysql')
        ));
        
        // Test execution without subscription (should fail)
        $this->scheduler->execute_scheduled_post($scheduled_id, $this->user_id);
        
        // Check that the post status was updated to failed
        $post_data = $this->scheduler->get_scheduled_post($scheduled_id);
        $this->assertEquals('failed', $post_data->status);
        $this->assertStringContainsString('subscription', strtolower($post_data->error_message));
    }

    /**
     * Test scheduling limits enforcement
     */
    public function test_scheduling_limits_enforcement() {
        // Mock WooCommerce to return low limits
        $mock_woocommerce = $this->createMock('XeliteRepostEngine_WooCommerce');
        $mock_woocommerce->method('get_user_limits')
            ->willReturn(array('scheduled_posts' => 1));
        
        $scheduler_limited = new XeliteRepostEngine_Scheduler(
            $this->database, 
            $this->user_meta, 
            $this->x_poster, 
            null, 
            $mock_woocommerce
        );
        
        // Add one scheduled post
        global $wpdb;
        $table = $wpdb->prefix . 'xelite_scheduled_posts';
        $wpdb->insert($table, array(
            'user_id' => $this->user_id,
            'content' => 'Test scheduled post 1',
            'scheduled_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'timezone' => 'UTC',
            'status' => 'scheduled',
            'created_at' => current_time('mysql')
        ));
        
        // Try to schedule another post (should fail due to limits)
        $result = $scheduler_limited->schedule_post(
            'Test scheduled post 2',
            date('Y-m-d H:i:s', strtotime('+2 hours')),
            'UTC',
            $this->user_id
        );
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertTrue($result['limit_exceeded']);
        $this->assertStringContainsString('limit', strtolower($result['error']));
    }

    /**
     * Test subscription access with different features
     */
    public function test_subscription_access_different_features() {
        // Test scheduling feature
        $result = $this->scheduler->check_subscription_access($this->user_id, 'scheduling');
        $this->assertFalse($result['allowed']);
        
        // Test posting feature
        $result = $this->scheduler->check_subscription_access($this->user_id, 'posting');
        $this->assertFalse($result['allowed']);
        
        // Test content generation feature
        $result = $this->scheduler->check_subscription_access($this->user_id, 'content_generation');
        $this->assertFalse($result['allowed']);
    }

    /**
     * Test scheduling window validation
     */
    public function test_scheduling_window_validation() {
        $limits = $this->scheduler->get_user_scheduling_limits($this->user_id);
        $scheduling_window = $limits['scheduling_window_days'];
        
        // Test scheduling within window
        $valid_time = date('Y-m-d H:i:s', strtotime("+{$scheduling_window} days"));
        $validation = $this->scheduler->validate_scheduled_time($valid_time, 'UTC');
        $this->assertTrue($validation['valid']);
        
        // Test scheduling beyond window
        $invalid_time = date('Y-m-d H:i:s', strtotime("+" . ($scheduling_window + 10) . " days"));
        $validation = $this->scheduler->validate_scheduled_time($invalid_time, 'UTC');
        $this->assertFalse($validation['valid']);
    }

    /**
     * Test media scheduling permissions
     */
    public function test_media_scheduling_permissions() {
        $limits = $this->scheduler->get_user_scheduling_limits($this->user_id);
        
        // Test scheduling with media
        $options_with_media = array('media_ids' => array('123', '456'));
        
        if (!$limits['can_schedule_media']) {
            // Should fail if media scheduling is not allowed
            $result = $this->scheduler->schedule_post(
                'Test post with media',
                date('Y-m-d H:i:s', strtotime('+1 hour')),
                'UTC',
                $this->user_id,
                $options_with_media
            );
            
            $this->assertIsArray($result);
            $this->assertFalse($result['success']);
        }
    }

    /**
     * Test subscription status changes affecting scheduled posts
     */
    public function test_subscription_status_changes() {
        // Create a scheduled post
        global $wpdb;
        $table = $wpdb->prefix . 'xelite_scheduled_posts';
        $scheduled_id = $wpdb->insert($table, array(
            'user_id' => $this->user_id,
            'content' => 'Test scheduled post',
            'scheduled_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'timezone' => 'UTC',
            'status' => 'scheduled',
            'created_at' => current_time('mysql')
        ));
        
        // Simulate subscription cancellation
        // The post should still be scheduled but will fail when executed
        $this->scheduler->execute_scheduled_post($scheduled_id, $this->user_id);
        
        $post_data = $this->scheduler->get_scheduled_post($scheduled_id);
        $this->assertEquals('failed', $post_data->status);
    }

    /**
     * Test error handling and logging
     */
    public function test_error_handling_and_logging() {
        // Test scheduling with invalid user
        $result = $this->scheduler->schedule_post(
            'Test post',
            date('Y-m-d H:i:s', strtotime('+1 hour')),
            'UTC',
            99999 // Non-existent user
        );
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        
        // Test scheduling with invalid time
        $result = $this->scheduler->schedule_post(
            'Test post',
            'invalid-time',
            'UTC',
            $this->user_id
        );
        
        $this->assertFalse($result);
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up scheduled posts
        global $wpdb;
        $table = $wpdb->prefix . 'xelite_scheduled_posts';
        $wpdb->query("DELETE FROM {$table} WHERE user_id = {$this->user_id}");
        
        parent::tearDown();
    }
} 