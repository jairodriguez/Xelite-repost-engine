<?php
/**
 * Test suite for Cron Functionality
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for Cron Functionality
 */
class Test_XeliteRepostEngine_Cron extends WP_UnitTestCase {

    /**
     * Cron instance
     *
     * @var XeliteRepostEngine_Cron
     */
    private $cron;

    /**
     * Scraper mock
     *
     * @var XeliteRepostEngine_Scraper
     */
    private $scraper_mock;

    /**
     * Database mock
     *
     * @var XeliteRepostEngine_Database
     */
    private $database_mock;

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
        $this->scraper_mock = $this->createMock('XeliteRepostEngine_Scraper');
        $this->database_mock = $this->createMock('XeliteRepostEngine_Database');
        $this->logger_mock = $this->createMock('XeliteRepostEngine_Logger');
        
        // Create cron instance
        $this->cron = new XeliteRepostEngine_Cron($this->scraper_mock, $this->database_mock, $this->logger_mock);
    }

    /**
     * Test constructor initialization
     */
    public function test_constructor_initialization() {
        $this->assertInstanceOf('XeliteRepostEngine_Cron', $this->cron);
        
        // Test that hooks are added
        $this->assertGreaterThan(0, has_filter('cron_schedules', array($this->cron, 'add_custom_schedules')));
    }

    /**
     * Test custom cron schedules
     */
    public function test_custom_cron_schedules() {
        $schedules = array();
        $result = $this->cron->add_custom_schedules($schedules);
        
        $this->assertArrayHasKey('xelite_hourly', $result);
        $this->assertArrayHasKey('xelite_twice_daily', $result);
        $this->assertArrayHasKey('xelite_daily', $result);
        $this->assertArrayHasKey('xelite_weekly', $result);
        
        $this->assertEquals(HOUR_IN_SECONDS, $result['xelite_hourly']['interval']);
        $this->assertEquals(12 * HOUR_IN_SECONDS, $result['xelite_twice_daily']['interval']);
        $this->assertEquals(DAY_IN_SECONDS, $result['xelite_daily']['interval']);
        $this->assertEquals(WEEK_IN_SECONDS, $result['xelite_weekly']['interval']);
    }

    /**
     * Test cron job activation
     */
    public function test_activate_cron_jobs() {
        // Clear any existing cron jobs
        wp_clear_scheduled_hook('xelite_repost_engine_scraping_cron');
        wp_clear_scheduled_hook('xelite_repost_engine_cleanup_cron');
        wp_clear_scheduled_hook('xelite_repost_engine_monitoring_cron');
        
        // Activate cron jobs
        $this->cron->activate_cron_jobs();
        
        // Check that jobs are scheduled
        $this->assertNotFalse(wp_next_scheduled('xelite_repost_engine_cleanup_cron'));
        $this->assertNotFalse(wp_next_scheduled('xelite_repost_engine_monitoring_cron'));
    }

    /**
     * Test cron job deactivation
     */
    public function test_deactivate_cron_jobs() {
        // Schedule some jobs first
        wp_schedule_event(time(), 'hourly', 'xelite_repost_engine_scraping_cron');
        wp_schedule_event(time(), 'daily', 'xelite_repost_engine_cleanup_cron');
        wp_schedule_event(time(), 'hourly', 'xelite_repost_engine_monitoring_cron');
        
        // Deactivate cron jobs
        $this->cron->deactivate_cron_jobs();
        
        // Check that jobs are cleared
        $this->assertFalse(wp_next_scheduled('xelite_repost_engine_scraping_cron'));
        $this->assertFalse(wp_next_scheduled('xelite_repost_engine_cleanup_cron'));
        $this->assertFalse(wp_next_scheduled('xelite_repost_engine_monitoring_cron'));
    }

    /**
     * Test scraping cron execution
     */
    public function test_run_scraping_cron() {
        // Mock scraper response
        $mock_results = array(
            'total_reposts' => 10,
            'total_errors' => 0,
            'accounts' => array(
                'test_account' => array(
                    'stored' => 5,
                    'errors' => 0
                )
            )
        );
        
        $this->scraper_mock->expects($this->once())
            ->method('scrape_accounts_batch')
            ->willReturn($mock_results);
        
        // Mock settings
        $settings = array(
            'target_accounts' => array('test_account'),
            'notifications_enabled' => false
        );
        
        // Mock get_cron_settings method
        $cron_reflection = new ReflectionClass($this->cron);
        $get_settings_method = $cron_reflection->getMethod('get_cron_settings');
        $get_settings_method->setAccessible(true);
        
        // Create a partial mock to override get_cron_settings
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('get_cron_settings'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('get_cron_settings')
            ->willReturn($settings);
        
        // Run scraping cron
        $cron_partial->run_scraping_cron();
    }

    /**
     * Test scraping cron with no accounts
     */
    public function test_run_scraping_cron_no_accounts() {
        // Mock settings with no accounts
        $settings = array(
            'target_accounts' => array(),
            'notifications_enabled' => false
        );
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('get_cron_settings'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('get_cron_settings')
            ->willReturn($settings);
        
        // Scraper should not be called
        $this->scraper_mock->expects($this->never())
            ->method('scrape_accounts_batch');
        
        // Run scraping cron
        $cron_partial->run_scraping_cron();
    }

    /**
     * Test cleanup cron execution
     */
    public function test_run_cleanup_cron() {
        // Mock database cleanup
        $this->database_mock->expects($this->once())
            ->method('cleanup_old_reposts')
            ->with(365)
            ->willReturn(5);
        
        // Run cleanup cron
        $this->cron->run_cleanup_cron();
    }

    /**
     * Test monitoring cron execution
     */
    public function test_run_monitoring_cron() {
        // Mock health status
        $health_status = array(
            'status' => 'healthy',
            'issues' => array(),
            'last_run' => '2023-01-01 12:00:00',
            'next_run' => '2023-01-02 12:00:00',
            'lock_file_exists' => false,
            'memory_usage' => 1024000
        );
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('check_cron_health_status'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('check_cron_health_status')
            ->willReturn($health_status);
        
        // Run monitoring cron
        $cron_partial->run_monitoring_cron();
    }

    /**
     * Test cron settings management
     */
    public function test_cron_settings_management() {
        // Test default settings
        $default_settings = $this->cron->get_cron_settings();
        
        $this->assertArrayHasKey('scraping_frequency', $default_settings);
        $this->assertArrayHasKey('target_accounts', $default_settings);
        $this->assertArrayHasKey('notifications_enabled', $default_settings);
        $this->assertArrayHasKey('cleanup_days', $default_settings);
        
        // Test updating settings
        $new_settings = array(
            'scraping_frequency' => 'xelite_hourly',
            'target_accounts' => array('test1', 'test2'),
            'notifications_enabled' => true,
            'notification_email' => 'test@example.com'
        );
        
        $result = $this->cron->update_cron_settings($new_settings);
        $this->assertTrue($result);
        
        // Verify settings were updated
        $updated_settings = $this->cron->get_cron_settings();
        $this->assertEquals('xelite_hourly', $updated_settings['scraping_frequency']);
        $this->assertEquals(array('test1', 'test2'), $updated_settings['target_accounts']);
        $this->assertTrue($updated_settings['notifications_enabled']);
        $this->assertEquals('test@example.com', $updated_settings['notification_email']);
    }

    /**
     * Test cron health status
     */
    public function test_check_cron_health_status() {
        $health_status = $this->cron->check_cron_health_status();
        
        $this->assertArrayHasKey('status', $health_status);
        $this->assertArrayHasKey('issues', $health_status);
        $this->assertArrayHasKey('last_run', $health_status);
        $this->assertArrayHasKey('next_run', $health_status);
        $this->assertArrayHasKey('lock_file_exists', $health_status);
        $this->assertArrayHasKey('memory_usage', $health_status);
        $this->assertArrayHasKey('peak_memory', $health_status);
        
        $this->assertIsString($health_status['status']);
        $this->assertIsArray($health_status['issues']);
        $this->assertIsBool($health_status['lock_file_exists']);
        $this->assertIsInt($health_status['memory_usage']);
    }

    /**
     * Test cron status information
     */
    public function test_get_cron_status() {
        $status = $this->cron->get_cron_status();
        
        $this->assertArrayHasKey('scraping_scheduled', $status);
        $this->assertArrayHasKey('cleanup_scheduled', $status);
        $this->assertArrayHasKey('monitoring_scheduled', $status);
        $this->assertArrayHasKey('health_status', $status);
        $this->assertArrayHasKey('statistics', $status);
        $this->assertArrayHasKey('settings', $status);
    }

    /**
     * Test lock file management
     */
    public function test_lock_file_management() {
        // Test is_cron_running when no lock file exists
        $reflection = new ReflectionClass($this->cron);
        $is_running_method = $reflection->getMethod('is_cron_running');
        $is_running_method->setAccessible(true);
        
        $this->assertFalse($is_running_method->invoke($this->cron));
        
        // Test creating lock file
        $create_lock_method = $reflection->getMethod('create_lock_file');
        $create_lock_method->setAccessible(true);
        
        $create_lock_method->invoke($this->cron);
        
        // Test is_cron_running when lock file exists
        $this->assertTrue($is_running_method->invoke($this->cron));
        
        // Test removing lock file
        $remove_lock_method = $reflection->getMethod('remove_lock_file');
        $remove_lock_method->setAccessible(true);
        
        $remove_lock_method->invoke($this->cron);
        
        // Test is_cron_running after removing lock file
        $this->assertFalse($is_running_method->invoke($this->cron));
    }

    /**
     * Test manual scraping
     */
    public function test_manual_scraping() {
        // Mock scraper response
        $mock_results = array(
            'success' => 5,
            'errors' => 0,
            'duplicates' => 2,
            'accounts' => array(
                'test_account' => array(
                    'stored' => 5,
                    'errors' => 0
                )
            )
        );
        
        $this->scraper_mock->expects($this->once())
            ->method('scrape_accounts_batch')
            ->willReturn($mock_results);
        
        // Mock settings
        $settings = array(
            'target_accounts' => array('test_account')
        );
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('get_cron_settings'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('get_cron_settings')
            ->willReturn($settings);
        
        // Run manual scraping
        $cron_partial->run_manual_scraping();
    }

    /**
     * Test manual scraping with no accounts
     */
    public function test_manual_scraping_no_accounts() {
        // Mock settings with no accounts
        $settings = array(
            'target_accounts' => array()
        );
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('get_cron_settings'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('get_cron_settings')
            ->willReturn($settings);
        
        // Scraper should not be called
        $this->scraper_mock->expects($this->never())
            ->method('scrape_accounts_batch');
        
        // Run manual scraping
        $cron_partial->run_manual_scraping();
    }

    /**
     * Test AJAX test cron connection
     */
    public function test_ajax_test_cron_connection() {
        // Mock health status
        $health_status = array(
            'status' => 'healthy',
            'issues' => array(),
            'last_run' => '2023-01-01 12:00:00',
            'next_run' => '2023-01-02 12:00:00',
            'memory_usage' => 1024000
        );
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('check_cron_health_status'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('check_cron_health_status')
            ->willReturn($health_status);
        
        // Test AJAX method
        $cron_partial->ajax_test_cron_connection();
    }

    /**
     * Test AJAX manual scraping
     */
    public function test_ajax_manual_scraping() {
        // Mock scraper response
        $mock_results = array(
            'success' => 3,
            'errors' => 1,
            'duplicates' => 0
        );
        
        $this->scraper_mock->expects($this->once())
            ->method('scrape_accounts_batch')
            ->willReturn($mock_results);
        
        // Mock settings
        $settings = array(
            'target_accounts' => array('test_account')
        );
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('get_cron_settings'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('get_cron_settings')
            ->willReturn($settings);
        
        // Test AJAX method
        $cron_partial->ajax_manual_scraping();
    }

    /**
     * Test error handling in scraping cron
     */
    public function test_scraping_cron_error_handling() {
        // Mock scraper to throw exception
        $this->scraper_mock->expects($this->once())
            ->method('scrape_accounts_batch')
            ->willThrowException(new Exception('Test error'));
        
        // Mock settings
        $settings = array(
            'target_accounts' => array('test_account'),
            'notifications_enabled' => false
        );
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('get_cron_settings'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('get_cron_settings')
            ->willReturn($settings);
        
        // Run scraping cron - should not throw exception
        $cron_partial->run_scraping_cron();
    }

    /**
     * Test notification sending
     */
    public function test_notification_sending() {
        // Mock settings with notifications enabled
        $settings = array(
            'target_accounts' => array('test_account'),
            'notifications_enabled' => true,
            'notification_email' => 'test@example.com'
        );
        
        // Mock scraper response
        $mock_results = array(
            'total_reposts' => 5,
            'total_errors' => 0
        );
        
        $this->scraper_mock->expects($this->once())
            ->method('scrape_accounts_batch')
            ->willReturn($mock_results);
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('get_cron_settings', 'send_cron_notification'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('get_cron_settings')
            ->willReturn($settings);
        
        $cron_partial->expects($this->once())
            ->method('send_cron_notification')
            ->with($mock_results, $this->isType('float'));
        
        // Run scraping cron
        $cron_partial->run_scraping_cron();
    }

    /**
     * Test statistics tracking
     */
    public function test_statistics_tracking() {
        // Mock scraper response
        $mock_results = array(
            'total_reposts' => 10,
            'total_errors' => 2
        );
        
        $this->scraper_mock->expects($this->once())
            ->method('scrape_accounts_batch')
            ->willReturn($mock_results);
        
        // Mock settings
        $settings = array(
            'target_accounts' => array('test_account'),
            'notifications_enabled' => false
        );
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('get_cron_settings', 'update_cron_statistics'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('get_cron_settings')
            ->willReturn($settings);
        
        $cron_partial->expects($this->once())
            ->method('update_cron_statistics')
            ->with($mock_results, $this->isType('float'));
        
        // Run scraping cron
        $cron_partial->run_scraping_cron();
    }

    /**
     * Test cron health check on init
     */
    public function test_check_cron_health_on_init() {
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('check_cron_health_status'))
            ->getMock();
        
        // Should be called occasionally (we can't control the random check)
        $cron_partial->expects($this->any())
            ->method('check_cron_health_status')
            ->willReturn(array('status' => 'healthy'));
        
        // Call the method
        $cron_partial->check_cron_health();
    }

    /**
     * Test cron scheduling with different frequencies
     */
    public function test_schedule_scraping_cron_different_frequencies() {
        $frequencies = array('xelite_hourly', 'xelite_twice_daily', 'xelite_daily', 'xelite_weekly');
        
        foreach ($frequencies as $frequency) {
            // Clear existing cron
            wp_clear_scheduled_hook('xelite_repost_engine_scraping_cron');
            
            // Mock settings
            $settings = array('scraping_frequency' => $frequency);
            
            // Create a partial mock
            $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
                ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
                ->onlyMethods(array('get_cron_settings'))
                ->getMock();
            
            $cron_partial->expects($this->once())
                ->method('get_cron_settings')
                ->willReturn($settings);
            
            // Schedule cron
            $cron_partial->schedule_scraping_cron();
            
            // Verify cron is scheduled
            $this->assertNotFalse(wp_next_scheduled('xelite_repost_engine_scraping_cron'));
        }
    }

    /**
     * Test cleanup old logs
     */
    public function test_cleanup_old_logs() {
        // Mock settings
        $settings = array('cleanup_days' => 30);
        
        // Create a partial mock
        $cron_partial = $this->getMockBuilder('XeliteRepostEngine_Cron')
            ->setConstructorArgs(array($this->scraper_mock, $this->database_mock, $this->logger_mock))
            ->onlyMethods(array('get_cron_settings', 'cleanup_old_statistics'))
            ->getMock();
        
        $cron_partial->expects($this->once())
            ->method('get_cron_settings')
            ->willReturn($settings);
        
        $cron_partial->expects($this->once())
            ->method('cleanup_old_statistics')
            ->with(30);
        
        // Mock scraper clear_logs method
        $this->scraper_mock->expects($this->once())
            ->method('clear_logs')
            ->with(30);
        
        // Test cleanup old logs
        $reflection = new ReflectionClass($cron_partial);
        $cleanup_method = $reflection->getMethod('cleanup_old_logs');
        $cleanup_method->setAccessible(true);
        
        $cleanup_method->invoke($cron_partial);
    }

    /**
     * Test cleanup old statistics
     */
    public function test_cleanup_old_statistics() {
        // Mock old statistics
        $old_stats = array(
            'last_run' => '2020-01-01 12:00:00',
            'total_runs' => 100
        );
        
        update_option('xelite_repost_engine_cron_stats', $old_stats);
        
        // Test cleanup
        $reflection = new ReflectionClass($this->cron);
        $cleanup_method = $reflection->getMethod('cleanup_old_statistics');
        $cleanup_method->setAccessible(true);
        
        $cleanup_method->invoke($this->cron, 365);
        
        // Verify old stats are cleared
        $updated_stats = get_option('xelite_repost_engine_cron_stats', array());
        $this->assertEmpty($updated_stats);
    }

    /**
     * Test format bytes method
     */
    public function test_format_bytes() {
        $reflection = new ReflectionClass($this->cron);
        $format_method = $reflection->getMethod('format_bytes');
        $format_method->setAccessible(true);
        
        $this->assertEquals('0 B', $format_method->invoke($this->cron, 0));
        $this->assertEquals('1 KB', $format_method->invoke($this->cron, 1024));
        $this->assertEquals('1 MB', $format_method->invoke($this->cron, 1024 * 1024));
        $this->assertEquals('1 GB', $format_method->invoke($this->cron, 1024 * 1024 * 1024));
    }

    /**
     * Test cron admin integration
     */
    public function test_cron_admin_integration() {
        // Create cron admin instance
        $cron_admin = new XeliteRepostEngine_Cron_Admin($this->cron);
        
        $this->assertInstanceOf('XeliteRepostEngine_Cron_Admin', $cron_admin);
        
        // Test that admin hooks are added
        $this->assertGreaterThan(0, has_action('admin_menu', array($cron_admin, 'add_admin_menu')));
        $this->assertGreaterThan(0, has_action('admin_init', array($cron_admin, 'register_settings')));
    }

    /**
     * Test cron admin settings sanitization
     */
    public function test_cron_admin_settings_sanitization() {
        $cron_admin = new XeliteRepostEngine_Cron_Admin($this->cron);
        
        $input = array(
            'scraping_frequency' => 'xelite_hourly',
            'target_accounts' => "test1\ntest2\n@test3",
            'notifications_enabled' => '1',
            'notification_email' => 'test@example.com',
            'cleanup_days' => '30',
            'max_execution_time' => '300'
        );
        
        $sanitized = $cron_admin->sanitize_settings($input);
        
        $this->assertEquals('xelite_hourly', $sanitized['scraping_frequency']);
        $this->assertEquals(array('test1', 'test2', 'test3'), $sanitized['target_accounts']);
        $this->assertTrue($sanitized['notifications_enabled']);
        $this->assertEquals('test@example.com', $sanitized['notification_email']);
        $this->assertEquals(30, $sanitized['cleanup_days']);
        $this->assertEquals(300, $sanitized['max_execution_time']);
    }

    /**
     * Test cron admin settings validation
     */
    public function test_cron_admin_settings_validation() {
        $cron_admin = new XeliteRepostEngine_Cron_Admin($this->cron);
        
        $input = array(
            'scraping_frequency' => 'invalid_frequency',
            'target_accounts' => "invalid@account\n@valid_account",
            'notifications_enabled' => '1',
            'notification_email' => 'invalid-email',
            'cleanup_days' => '5000', // Too high
            'max_execution_time' => '30' // Too low
        );
        
        $sanitized = $cron_admin->sanitize_settings($input);
        
        // Should default to valid values
        $this->assertEquals('xelite_daily', $sanitized['scraping_frequency']);
        $this->assertEquals(array('valid_account'), $sanitized['target_accounts']);
        $this->assertTrue($sanitized['notifications_enabled']);
        $this->assertEquals('', $sanitized['notification_email']); // Invalid email becomes empty
        $this->assertEquals(3650, $sanitized['cleanup_days']); // Clamped to max
        $this->assertEquals(60, $sanitized['max_execution_time']); // Clamped to min
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clear any scheduled cron jobs
        wp_clear_scheduled_hook('xelite_repost_engine_scraping_cron');
        wp_clear_scheduled_hook('xelite_repost_engine_cleanup_cron');
        wp_clear_scheduled_hook('xelite_repost_engine_monitoring_cron');
        
        // Remove lock file if it exists
        $lock_file = WP_CONTENT_DIR . '/xelite-repost-engine-cron.lock';
        if (file_exists($lock_file)) {
            unlink($lock_file);
        }
        
        // Clean up options
        delete_option('xelite_repost_engine_cron_settings');
        delete_option('xelite_repost_engine_cron_stats');
        delete_option('xelite_repost_engine_monitoring_stats');
        
        parent::tearDown();
    }
} 