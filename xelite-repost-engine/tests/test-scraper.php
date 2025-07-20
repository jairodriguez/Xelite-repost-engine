<?php
/**
 * Test suite for Scraper
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for Scraper
 */
class Test_XeliteRepostEngine_Scraper extends WP_UnitTestCase {

    /**
     * Scraper instance
     *
     * @var XeliteRepostEngine_Scraper
     */
    private $scraper;

    /**
     * X API mock
     *
     * @var XeliteRepostEngine_X_API
     */
    private $x_api;

    /**
     * X Processor mock
     *
     * @var XeliteRepostEngine_X_Processor
     */
    private $processor;

    /**
     * Database mock
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create mocks
        $this->x_api = $this->createMock('XeliteRepostEngine_X_API');
        $this->processor = $this->createMock('XeliteRepostEngine_X_Processor');
        $this->database = $this->createMock('XeliteRepostEngine_Database');
        
        // Create scraper instance
        $this->scraper = new XeliteRepostEngine_Scraper($this->x_api, $this->processor, $this->database);
    }

    /**
     * Test constructor initialization
     */
    public function test_constructor_initialization() {
        $this->assertInstanceOf('XeliteRepostEngine_Scraper', $this->scraper);
        
        // Test that configuration is loaded
        $config = $this->scraper->get_configuration();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('max_posts_per_account', $config);
        $this->assertArrayHasKey('enable_logging', $config);
    }

    /**
     * Test rate limiting functionality
     */
    public function test_rate_limiting() {
        // Test initial state
        $this->assertTrue($this->scraper->can_make_request());
        
        // Get rate limit status
        $status = $this->scraper->get_rate_limit_status();
        $this->assertIsArray($status);
        $this->assertArrayHasKey('can_make_request', $status);
        $this->assertArrayHasKey('remaining_15min', $status);
        $this->assertArrayHasKey('remaining_day', $status);
    }

    /**
     * Test getting reposts by account
     */
    public function test_get_reposts_by_account() {
        $account_handle = 'testuser';
        $user_info = array(
            'id' => '123456789',
            'username' => 'testuser',
            'name' => 'Test User'
        );
        
        $posts_data = array(
            'data' => array(
                array(
                    'id' => '123456789',
                    'text' => 'RT @originaluser This is a retweet',
                    'created_at' => '2024-01-01T12:00:00Z',
                    'public_metrics' => array(
                        'retweet_count' => 10,
                        'like_count' => 50,
                        'reply_count' => 5,
                        'quote_count' => 2
                    ),
                    'referenced_tweets' => array(
                        array(
                            'type' => 'retweeted',
                            'id' => '987654321'
                        )
                    )
                ),
                array(
                    'id' => '123456790',
                    'text' => 'This is not a retweet',
                    'created_at' => '2024-01-01T13:00:00Z',
                    'public_metrics' => array(
                        'retweet_count' => 0,
                        'like_count' => 10,
                        'reply_count' => 2,
                        'quote_count' => 0
                    )
                )
            ),
            'meta' => array(
                'next_token' => 'next_page_token'
            )
        );
        
        // Mock X API responses
        $this->x_api->expects($this->once())
            ->method('get_user_info')
            ->with($account_handle)
            ->willReturn($user_info);
            
        $this->x_api->expects($this->once())
            ->method('get_user_timeline')
            ->willReturn($posts_data);
        
        $result = $this->scraper->get_reposts_by_account($account_handle);
        
        $this->assertIsArray($result);
        $this->assertEquals($account_handle, $result['account_handle']);
        $this->assertEquals($user_info, $result['user_info']);
        $this->assertEquals(1, $result['total_reposts']); // Only one repost
        $this->assertEquals(2, $result['total_fetched']); // Two total posts
        $this->assertEquals('next_page_token', $result['next_token']);
    }

    /**
     * Test getting reposts with API error
     */
    public function test_get_reposts_by_account_api_error() {
        $account_handle = 'testuser';
        
        $this->x_api->expects($this->once())
            ->method('get_user_info')
            ->willReturn(new WP_Error('api_error', 'API Error'));
        
        $result = $this->scraper->get_reposts_by_account($account_handle);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('api_error', $result->get_error_code());
    }

    /**
     * Test getting reposts with invalid account
     */
    public function test_get_reposts_by_account_invalid() {
        $result = $this->scraper->get_reposts_by_account('');
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_account', $result->get_error_code());
    }

    /**
     * Test repost filtering
     */
    public function test_filter_reposts() {
        $posts = array(
            array(
                'id' => '1',
                'text' => 'RT @user This is a retweet',
                'referenced_tweets' => array(
                    array('type' => 'retweeted', 'id' => 'original_id')
                )
            ),
            array(
                'id' => '2',
                'text' => 'This is not a retweet'
            ),
            array(
                'id' => '3',
                'text' => 'QT @user This is a quote tweet'
            )
        );
        
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->scraper);
        $method = $reflection->getMethod('filter_reposts');
        $method->setAccessible(true);
        
        $reposts = $method->invoke($this->scraper, $posts);
        
        $this->assertIsArray($reposts);
        $this->assertEquals(2, count($reposts)); // Should find 2 reposts
    }

    /**
     * Test repost detection
     */
    public function test_is_repost() {
        $reflection = new ReflectionClass($this->scraper);
        $method = $reflection->getMethod('is_repost');
        $method->setAccessible(true);
        
        // Test retweet with referenced_tweets
        $retweet = array(
            'referenced_tweets' => array(
                array('type' => 'retweeted', 'id' => 'original_id')
            )
        );
        $this->assertTrue($method->invoke($this->scraper, $retweet));
        
        // Test retweet with RT prefix
        $rt_tweet = array('text' => 'RT @user This is a retweet');
        $this->assertTrue($method->invoke($this->scraper, $rt_tweet));
        
        // Test quote tweet
        $qt_tweet = array('text' => 'QT @user This is a quote tweet');
        $this->assertTrue($method->invoke($this->scraper, $qt_tweet));
        
        // Test regular tweet
        $regular_tweet = array('text' => 'This is a regular tweet');
        $this->assertFalse($method->invoke($this->scraper, $regular_tweet));
    }

    /**
     * Test repost data processing
     */
    public function test_process_repost_data() {
        $reflection = new ReflectionClass($this->scraper);
        $method = $reflection->getMethod('process_repost_data');
        $method->setAccessible(true);
        
        $post = array(
            'id' => '123456789',
            'text' => 'RT @user This is a retweet',
            'created_at' => '2024-01-01T12:00:00Z',
            'public_metrics' => array(
                'retweet_count' => 10,
                'like_count' => 50,
                'reply_count' => 5,
                'quote_count' => 2
            ),
            'entities' => array('hashtags' => array()),
            'context_annotations' => array(),
            'referenced_tweets' => array(
                array('type' => 'retweeted', 'id' => '987654321')
            )
        );
        
        $repost_data = $method->invoke($this->scraper, $post);
        
        $this->assertIsArray($repost_data);
        $this->assertEquals('123456789', $repost_data['original_tweet_id']);
        $this->assertEquals('RT @user This is a retweet', $repost_data['original_text']);
        $this->assertEquals('2024-01-01T12:00:00Z', $repost_data['created_at']);
        $this->assertEquals('987654321', $repost_data['referenced_tweet_id']);
        $this->assertArrayHasKey('engagement_metrics', $repost_data);
        $this->assertEquals(10, $repost_data['engagement_metrics']['retweet_count']);
    }

    /**
     * Test saving repost data
     */
    public function test_save_to_database() {
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Test repost',
            'created_at' => '2024-01-01T12:00:00Z'
        );
        
        $this->processor->expects($this->once())
            ->method('store_repost_data')
            ->willReturn(true);
        
        $result = $this->scraper->save_to_database($repost_data, 'testuser');
        
        $this->assertTrue($result);
    }

    /**
     * Test saving repost data with error
     */
    public function test_save_to_database_error() {
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Test repost'
        );
        
        $this->processor->expects($this->once())
            ->method('store_repost_data')
            ->willReturn(false);
        
        $result = $this->scraper->save_to_database($repost_data, 'testuser');
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('save_failed', $result->get_error_code());
    }

    /**
     * Test batch scraping
     */
    public function test_scrape_accounts_batch() {
        $accounts = array('user1', 'user2');
        $user_info = array('id' => '123', 'username' => 'testuser');
        $posts_data = array(
            'data' => array(
                array(
                    'id' => '123456789',
                    'text' => 'RT @user This is a retweet',
                    'created_at' => '2024-01-01T12:00:00Z',
                    'public_metrics' => array(
                        'retweet_count' => 10,
                        'like_count' => 50,
                        'reply_count' => 5,
                        'quote_count' => 2
                    ),
                    'referenced_tweets' => array(
                        array('type' => 'retweeted', 'id' => '987654321')
                    )
                )
            )
        );
        
        // Mock X API responses for both accounts
        $this->x_api->expects($this->exactly(2))
            ->method('get_user_info')
            ->willReturn($user_info);
            
        $this->x_api->expects($this->exactly(2))
            ->method('get_user_timeline')
            ->willReturn($posts_data);
        
        // Mock processor for saving
        $this->processor->expects($this->exactly(2))
            ->method('store_repost_data')
            ->willReturn(true);
        
        $result = $this->scraper->scrape_accounts_batch($accounts);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertEquals(2, $result['summary']['total_accounts']);
        $this->assertEquals(2, $result['summary']['successful_accounts']);
        $this->assertEquals(2, $result['summary']['total_reposts_saved']);
    }

    /**
     * Test batch scraping with errors
     */
    public function test_scrape_accounts_batch_with_errors() {
        $accounts = array('user1', 'user2');
        
        // First account succeeds, second fails
        $user_info = array('id' => '123', 'username' => 'testuser');
        $posts_data = array('data' => array());
        
        $this->x_api->expects($this->exactly(2))
            ->method('get_user_info')
            ->willReturnOnConsecutiveCalls($user_info, new WP_Error('api_error', 'API Error'));
            
        $this->x_api->expects($this->once())
            ->method('get_user_timeline')
            ->willReturn($posts_data);
        
        $result = $this->scraper->scrape_accounts_batch($accounts);
        
        $this->assertIsArray($result);
        $this->assertEquals(2, $result['summary']['total_accounts']);
        $this->assertEquals(1, $result['summary']['successful_accounts']);
        $this->assertEquals(1, $result['summary']['failed_accounts']);
    }

    /**
     * Test batch scraping with no accounts
     */
    public function test_scrape_accounts_batch_no_accounts() {
        $result = $this->scraper->scrape_accounts_batch(array());
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('no_accounts', $result->get_error_code());
    }

    /**
     * Test getting scraping statistics
     */
    public function test_get_scraping_statistics() {
        $stats = $this->scraper->get_scraping_statistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('rate_limits', $stats);
        $this->assertArrayHasKey('config', $stats);
        $this->assertArrayHasKey('can_make_request', $stats);
        $this->assertArrayHasKey('last_scrape_time', $stats);
        $this->assertArrayHasKey('total_scrapes_today', $stats);
        $this->assertArrayHasKey('total_reposts_scraped', $stats);
    }

    /**
     * Test API connection test
     */
    public function test_test_api_connection() {
        $user_info = array('id' => '123', 'username' => 'twitter');
        
        $this->x_api->expects($this->once())
            ->method('get_user_info')
            ->with('twitter')
            ->willReturn($user_info);
        
        $result = $this->scraper->test_api_connection();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('API connection successful', $result['message']);
        $this->assertEquals($user_info, $result['user_info']);
    }

    /**
     * Test API connection test with error
     */
    public function test_test_api_connection_error() {
        $this->x_api->expects($this->once())
            ->method('get_user_info')
            ->willReturn(new WP_Error('api_error', 'API Error'));
        
        $result = $this->scraper->test_api_connection();
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('api_error', $result->get_error_code());
    }

    /**
     * Test configuration update
     */
    public function test_update_configuration() {
        $new_config = array(
            'max_posts_per_account' => 200,
            'delay_between_requests' => 2
        );
        
        $result = $this->scraper->update_configuration($new_config);
        
        $this->assertTrue($result);
        
        // Verify configuration was updated
        $config = $this->scraper->get_configuration();
        $this->assertEquals(200, $config['max_posts_per_account']);
        $this->assertEquals(2, $config['delay_between_requests']);
    }

    /**
     * Test logging functionality
     */
    public function test_logging() {
        // Test getting logs
        $logs = $this->scraper->get_logs();
        $this->assertIsArray($logs);
        
        // Test getting logs with level filter
        $logs = $this->scraper->get_logs('info');
        $this->assertIsArray($logs);
        
        // Test clearing logs
        $result = $this->scraper->clear_logs();
        $this->assertTrue($result);
    }

    /**
     * Test scheduled scraping functionality
     */
    public function test_scheduled_scraping() {
        $accounts = array('user1', 'user2');
        
        // Test scheduling
        $result = $this->scraper->schedule_scraping($accounts, 'hourly');
        $this->assertTrue($result);
        
        // Test getting scheduled info
        $schedule = $this->scraper->get_scheduled_scraping();
        $this->assertIsArray($schedule);
        $this->assertEquals($accounts, $schedule['accounts']);
        $this->assertEquals('hourly', $schedule['schedule']);
        
        // Test cancelling
        $result = $this->scraper->cancel_scheduled_scraping();
        $this->assertTrue($result);
    }

    /**
     * Test scheduled scraping with no accounts
     */
    public function test_schedule_scraping_no_accounts() {
        $result = $this->scraper->schedule_scraping(array(), 'hourly');
        $this->assertFalse($result);
    }

    /**
     * Test running scheduled scraping
     */
    public function test_run_scheduled_scraping() {
        $accounts = array('user1');
        $user_info = array('id' => '123', 'username' => 'testuser');
        $posts_data = array('data' => array());
        
        $this->x_api->expects($this->once())
            ->method('get_user_info')
            ->willReturn($user_info);
            
        $this->x_api->expects($this->once())
            ->method('get_user_timeline')
            ->willReturn($posts_data);
        
        $result = $this->scraper->run_scheduled_scraping($accounts);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
    }

    /**
     * Test rate limit reset functionality
     */
    public function test_rate_limit_reset() {
        // Manually set old timestamps to trigger reset
        $old_limits = array(
            'requests_per_15min' => 300,
            'requests_per_day' => 5000,
            'current_15min_count' => 100,
            'current_day_count' => 1000,
            'last_reset_15min' => time() - 1000, // Old timestamp
            'last_reset_day' => time() - 90000   // Old timestamp
        );
        
        update_option('xelite_scraper_rate_limits', $old_limits);
        
        // Create new scraper instance to trigger reset
        $new_scraper = new XeliteRepostEngine_Scraper($this->x_api, $this->processor, $this->database);
        
        $status = $new_scraper->get_rate_limit_status();
        
        // Should have reset counters
        $this->assertEquals(0, $status['current_15min_count']);
        $this->assertEquals(0, $status['current_day_count']);
    }

    /**
     * Test performance with large batch
     */
    public function test_performance_large_batch() {
        $accounts = array();
        for ($i = 0; $i < 20; $i++) {
            $accounts[] = "user{$i}";
        }
        
        $user_info = array('id' => '123', 'username' => 'testuser');
        $posts_data = array('data' => array());
        
        // Mock API responses
        $this->x_api->expects($this->exactly(10)) // Should be limited by max_accounts_per_batch
            ->method('get_user_info')
            ->willReturn($user_info);
            
        $this->x_api->expects($this->exactly(10))
            ->method('get_user_timeline')
            ->willReturn($posts_data);
        
        $start_time = microtime(true);
        
        $result = $this->scraper->scrape_accounts_batch($accounts);
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        $this->assertIsArray($result);
        $this->assertEquals(10, $result['summary']['total_accounts']); // Should be limited
        $this->assertLessThan(10.0, $execution_time); // Should complete within reasonable time
    }

    /**
     * Test error handling in batch processing
     */
    public function test_error_handling_batch_processing() {
        $accounts = array('user1', 'user2', 'user3');
        
        // First account succeeds, second fails, third succeeds
        $user_info = array('id' => '123', 'username' => 'testuser');
        $posts_data = array('data' => array());
        
        $this->x_api->expects($this->exactly(3))
            ->method('get_user_info')
            ->willReturnOnConsecutiveCalls(
                $user_info,
                new WP_Error('api_error', 'API Error'),
                $user_info
            );
            
        $this->x_api->expects($this->exactly(2))
            ->method('get_user_timeline')
            ->willReturn($posts_data);
        
        $result = $this->scraper->scrape_accounts_batch($accounts);
        
        $this->assertIsArray($result);
        $this->assertEquals(3, $result['summary']['total_accounts']);
        $this->assertEquals(2, $result['summary']['successful_accounts']);
        $this->assertEquals(1, $result['summary']['failed_accounts']);
    }

    /**
     * Test memory usage during batch processing
     */
    public function test_memory_usage_batch_processing() {
        $initial_memory = memory_get_usage();
        
        $accounts = array('user1', 'user2', 'user3');
        $user_info = array('id' => '123', 'username' => 'testuser');
        $posts_data = array(
            'data' => array(
                array(
                    'id' => '123456789',
                    'text' => str_repeat('Test repost content ', 50), // Large content
                    'created_at' => '2024-01-01T12:00:00Z',
                    'public_metrics' => array(
                        'retweet_count' => 10,
                        'like_count' => 50,
                        'reply_count' => 5,
                        'quote_count' => 2
                    )
                )
            )
        );
        
        $this->x_api->expects($this->exactly(3))
            ->method('get_user_info')
            ->willReturn($user_info);
            
        $this->x_api->expects($this->exactly(3))
            ->method('get_user_timeline')
            ->willReturn($posts_data);
        
        $this->scraper->scrape_accounts_batch($accounts);
        
        $final_memory = memory_get_usage();
        $memory_increase = $final_memory - $initial_memory;
        
        // Memory increase should be reasonable (less than 5MB)
        $this->assertLessThan(5 * 1024 * 1024, $memory_increase);
    }

    /**
     * Test data integrity in batch processing
     */
    public function test_data_integrity_batch_processing() {
        $accounts = array('user1');
        $user_info = array('id' => '123', 'username' => 'testuser');
        $original_post = array(
            'id' => '123456789',
            'text' => 'RT @originaluser This is a retweet with special chars: @#$%^&*()',
            'created_at' => '2024-01-01T12:00:00Z',
            'public_metrics' => array(
                'retweet_count' => 10,
                'like_count' => 50,
                'reply_count' => 5,
                'quote_count' => 2
            ),
            'referenced_tweets' => array(
                array('type' => 'retweeted', 'id' => '987654321')
            )
        );
        
        $posts_data = array('data' => array($original_post));
        
        $this->x_api->expects($this->once())
            ->method('get_user_info')
            ->willReturn($user_info);
            
        $this->x_api->expects($this->once())
            ->method('get_user_timeline')
            ->willReturn($posts_data);
        
        $this->processor->expects($this->once())
            ->method('store_repost_data')
            ->with($this->callback(function($data) use ($original_post) {
                return $data['original_tweet_id'] === $original_post['id'] &&
                       $data['original_text'] === $original_post['text'] &&
                       $data['source_handle'] === 'user1';
            }))
            ->willReturn(true);
        
        $result = $this->scraper->scrape_accounts_batch($accounts);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['summary']['total_reposts_saved']);
    }

    /**
     * Test concurrent processing simulation
     */
    public function test_concurrent_processing() {
        $accounts = array('user1');
        $user_info = array('id' => '123', 'username' => 'testuser');
        $posts_data = array('data' => array());
        
        $this->x_api->expects($this->exactly(3))
            ->method('get_user_info')
            ->willReturn($user_info);
            
        $this->x_api->expects($this->exactly(3))
            ->method('get_user_timeline')
            ->willReturn($posts_data);
        
        // Simulate concurrent processing
        $results = array();
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->scraper->scrape_accounts_batch($accounts);
        }
        
        $this->assertContainsOnly('array', $results);
        foreach ($results as $result) {
            $this->assertArrayHasKey('summary', $result);
        }
    }

    /**
     * Test edge cases
     */
    public function test_edge_cases() {
        // Test with very long account names
        $long_account = str_repeat('a', 1000);
        $result = $this->scraper->get_reposts_by_account($long_account);
        $this->assertInstanceOf('WP_Error', $result);
        
        // Test with special characters in account names
        $special_account = 'user@#$%^&*()';
        $result = $this->scraper->get_reposts_by_account($special_account);
        $this->assertInstanceOf('WP_Error', $result);
        
        // Test with empty posts data
        $user_info = array('id' => '123', 'username' => 'testuser');
        $empty_posts = array('data' => array());
        
        $this->x_api->expects($this->once())
            ->method('get_user_info')
            ->willReturn($user_info);
            
        $this->x_api->expects($this->once())
            ->method('get_user_timeline')
            ->willReturn($empty_posts);
        
        $result = $this->scraper->get_reposts_by_account('testuser');
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_reposts']);
    }

    /**
     * Test cleanup operations
     */
    public function test_cleanup_operations() {
        // Test resetting daily statistics
        $result = $this->scraper->reset_daily_statistics();
        $this->assertTrue($result);
        
        // Test clearing logs
        $result = $this->scraper->clear_logs();
        $this->assertTrue($result);
    }

    /**
     * Test backup and restore functionality
     */
    public function test_backup_and_restore() {
        // Test getting current configuration
        $config = $this->scraper->get_configuration();
        $this->assertIsArray($config);
        
        // Test updating configuration
        $new_config = array('max_posts_per_account' => 150);
        $result = $this->scraper->update_configuration($new_config);
        $this->assertTrue($result);
        
        // Verify configuration was updated
        $updated_config = $this->scraper->get_configuration();
        $this->assertEquals(150, $updated_config['max_posts_per_account']);
    }
} 