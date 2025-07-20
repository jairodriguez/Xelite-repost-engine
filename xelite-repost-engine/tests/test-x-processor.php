<?php
/**
 * Test suite for X Processor
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for X Processor
 */
class Test_XeliteRepostEngine_X_Processor extends WP_UnitTestCase {

    /**
     * X Processor instance
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
     * X API mock
     *
     * @var XeliteRepostEngine_X_API
     */
    private $x_api;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create mock database
        $this->database = $this->createMock('XeliteRepostEngine_Database');
        
        // Create mock X API
        $this->x_api = $this->createMock('XeliteRepostEngine_X_API');
        
        // Create processor instance
        $this->processor = new XeliteRepostEngine_X_Processor($this->database, $this->x_api);
    }

    /**
     * Test storing repost data
     */
    public function test_store_repost_data() {
        $repost_data = array(
            'source_handle' => 'testuser',
            'original_tweet_id' => '123456789',
            'original_text' => 'This is a test tweet #test',
            'created_at' => '2024-01-01 12:00:00',
            'engagement_metrics' => array(
                'retweet_count' => 10,
                'like_count' => 50,
                'reply_count' => 5,
                'quote_count' => 2
            )
        );

        $this->database->expects($this->once())
            ->method('insert_repost')
            ->with($this->equalTo($repost_data))
            ->willReturn(1);

        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertTrue($result);
    }

    /**
     * Test storing repost data with invalid data
     */
    public function test_store_repost_data_invalid() {
        $repost_data = array(
            'source_handle' => '', // Invalid empty handle
            'original_tweet_id' => '123456789'
        );

        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertFalse($result);
    }

    /**
     * Test analyzing tweet content
     */
    public function test_analyze_tweet_content() {
        $tweet_text = 'This is an amazing tweet! #awesome #test @user Check this out!';
        
        $expected_analysis = array(
            'hashtags' => array('awesome', 'test'),
            'mentions' => array('user'),
            'has_question' => false,
            'has_call_to_action' => true,
            'content_type' => 'text',
            'sentiment_score' => 25,
            'word_count' => 10,
            'character_count' => 58
        );

        $analysis = $this->processor->analyze_tweet_content($tweet_text);
        
        $this->assertEquals($expected_analysis['hashtags'], $analysis['hashtags']);
        $this->assertEquals($expected_analysis['mentions'], $analysis['mentions']);
        $this->assertEquals($expected_analysis['has_question'], $analysis['has_question']);
        $this->assertEquals($expected_analysis['has_call_to_action'], $analysis['has_call_to_action']);
        $this->assertEquals($expected_analysis['content_type'], $analysis['content_type']);
        $this->assertEquals($expected_analysis['word_count'], $analysis['word_count']);
        $this->assertEquals($expected_analysis['character_count'], $analysis['character_count']);
    }

    /**
     * Test analyzing tweet with question
     */
    public function test_analyze_tweet_content_with_question() {
        $tweet_text = 'What do you think about this? #question';
        
        $analysis = $this->processor->analyze_tweet_content($tweet_text);
        
        $this->assertTrue($analysis['has_question']);
        $this->assertEquals('text', $analysis['content_type']);
    }

    /**
     * Test analyzing tweet with call to action
     */
    public function test_analyze_tweet_content_with_cta() {
        $tweet_text = 'Follow me for more content! #follow';
        
        $analysis = $this->processor->analyze_tweet_content($tweet_text);
        
        $this->assertTrue($analysis['has_call_to_action']);
    }

    /**
     * Test processing and storing posts
     */
    public function test_process_and_store_posts() {
        $posts_data = array(
            array(
                'id' => '123456789',
                'text' => 'Test tweet 1 #test',
                'created_at' => '2024-01-01 12:00:00',
                'public_metrics' => array(
                    'retweet_count' => 10,
                    'like_count' => 50,
                    'reply_count' => 5,
                    'quote_count' => 2
                )
            ),
            array(
                'id' => '987654321',
                'text' => 'Test tweet 2 #awesome',
                'created_at' => '2024-01-01 13:00:00',
                'public_metrics' => array(
                    'retweet_count' => 20,
                    'like_count' => 100,
                    'reply_count' => 10,
                    'quote_count' => 5
                )
            )
        );

        $this->database->expects($this->exactly(2))
            ->method('insert_repost')
            ->willReturn(1);

        $result = $this->processor->process_and_store_posts($posts_data, 'testuser');
        
        $this->assertTrue($result);
    }

    /**
     * Test fetching and storing posts
     */
    public function test_fetch_and_store_posts() {
        $target_accounts = array('user1', 'user2');
        $posts_data = array(
            array(
                'id' => '123456789',
                'text' => 'Test tweet',
                'created_at' => '2024-01-01 12:00:00',
                'public_metrics' => array(
                    'retweet_count' => 10,
                    'like_count' => 50,
                    'reply_count' => 5,
                    'quote_count' => 2
                )
            )
        );

        // Mock settings to return target accounts
        $this->database->expects($this->once())
            ->method('get_settings')
            ->willReturn(array('target_accounts' => $target_accounts));

        // Mock X API to return posts
        $this->x_api->expects($this->exactly(2))
            ->method('get_user_timeline')
            ->willReturn($posts_data);

        // Mock database insert
        $this->database->expects($this->exactly(2))
            ->method('insert_repost')
            ->willReturn(1);

        $result = $this->processor->fetch_and_store_posts();
        
        $this->assertTrue($result);
    }

    /**
     * Test fetching and storing posts with API error
     */
    public function test_fetch_and_store_posts_api_error() {
        $target_accounts = array('user1');

        $this->database->expects($this->once())
            ->method('get_settings')
            ->willReturn(array('target_accounts' => $target_accounts));

        $this->x_api->expects($this->once())
            ->method('get_user_timeline')
            ->willReturn(new WP_Error('api_error', 'API Error'));

        $result = $this->processor->fetch_and_store_posts();
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('api_error', $result->get_error_code());
    }

    /**
     * Test analyzing stored data
     */
    public function test_analyze_stored_data() {
        $reposts = array(
            array(
                'id' => 1,
                'source_handle' => 'user1',
                'original_text' => 'Test tweet 1 #test',
                'engagement_metrics' => json_encode(array(
                    'retweet_count' => 10,
                    'like_count' => 50,
                    'reply_count' => 5,
                    'quote_count' => 2
                )),
                'analysis_data' => json_encode(array(
                    'hashtags' => array('test'),
                    'sentiment_score' => 25
                ))
            ),
            array(
                'id' => 2,
                'source_handle' => 'user2',
                'original_text' => 'Test tweet 2 #awesome',
                'engagement_metrics' => json_encode(array(
                    'retweet_count' => 20,
                    'like_count' => 100,
                    'reply_count' => 10,
                    'quote_count' => 5
                )),
                'analysis_data' => json_encode(array(
                    'hashtags' => array('awesome'),
                    'sentiment_score' => 30
                ))
            )
        );

        $this->database->expects($this->once())
            ->method('get_all_reposts')
            ->willReturn($reposts);

        $this->database->expects($this->once())
            ->method('update_analysis_cache')
            ->with($this->callback(function($analysis) {
                return isset($analysis['total_engagement']) && 
                       isset($analysis['avg_engagement']) && 
                       isset($analysis['top_hashtags']);
            }));

        $result = $this->processor->analyze_stored_data();
        
        $this->assertTrue($result);
    }

    /**
     * Test getting analysis results
     */
    public function test_get_analysis_results() {
        $expected_analysis = array(
            'total_engagement' => 1000,
            'avg_engagement' => 50.5,
            'top_hashtags' => array('test' => 10, 'awesome' => 5),
            'top_mentions' => array('user1' => 8, 'user2' => 3),
            'content_types' => array('text' => 15, 'image' => 5),
            'sentiment_distribution' => array(
                'positive' => 12,
                'neutral' => 5,
                'negative' => 3
            ),
            'last_updated' => '2024-01-01 12:00:00'
        );

        $this->database->expects($this->once())
            ->method('get_analysis_cache')
            ->willReturn($expected_analysis);

        $analysis = $this->processor->get_analysis_results();
        
        $this->assertEquals($expected_analysis, $analysis);
    }

    /**
     * Test getting analysis results with no cache
     */
    public function test_get_analysis_results_no_cache() {
        $this->database->expects($this->once())
            ->method('get_analysis_cache')
            ->willReturn(false);

        $this->database->expects($this->once())
            ->method('get_all_reposts')
            ->willReturn(array());

        $this->database->expects($this->once())
            ->method('update_analysis_cache');

        $analysis = $this->processor->get_analysis_results();
        
        $this->assertIsArray($analysis);
        $this->assertEquals(0, $analysis['total_engagement']);
    }

    /**
     * Test exporting data as CSV
     */
    public function test_export_data_csv() {
        $reposts = array(
            array(
                'id' => 1,
                'source_handle' => 'user1',
                'original_text' => 'Test tweet 1',
                'created_at' => '2024-01-01 12:00:00',
                'engagement_metrics' => json_encode(array(
                    'retweet_count' => 10,
                    'like_count' => 50
                )),
                'analysis_data' => json_encode(array(
                    'hashtags' => array('test'),
                    'sentiment_score' => 25
                ))
            )
        );

        $this->database->expects($this->once())
            ->method('get_all_reposts')
            ->willReturn($reposts);

        $csv_data = $this->processor->export_data('csv');
        
        $this->assertStringContainsString('ID,Source Handle,Original Text', $csv_data);
        $this->assertStringContainsString('1,user1,Test tweet 1', $csv_data);
    }

    /**
     * Test exporting data as JSON
     */
    public function test_export_data_json() {
        $reposts = array(
            array(
                'id' => 1,
                'source_handle' => 'user1',
                'original_text' => 'Test tweet 1',
                'created_at' => '2024-01-01 12:00:00'
            )
        );

        $this->database->expects($this->once())
            ->method('get_all_reposts')
            ->willReturn($reposts);

        $json_data = $this->processor->export_data('json');
        $decoded = json_decode($json_data, true);
        
        $this->assertIsArray($decoded);
        $this->assertEquals(1, count($decoded));
        $this->assertEquals('user1', $decoded[0]['source_handle']);
    }

    /**
     * Test exporting data with invalid format
     */
    public function test_export_data_invalid_format() {
        $result = $this->processor->export_data('invalid');
        
        $this->assertFalse($result);
    }

    /**
     * Test clearing all repost data
     */
    public function test_clear_all_repost_data() {
        $this->database->expects($this->once())
            ->method('clear_all_reposts')
            ->willReturn(true);

        $this->database->expects($this->once())
            ->method('clear_analysis_cache')
            ->willReturn(true);

        $result = $this->processor->clear_all_repost_data();
        
        $this->assertTrue($result);
    }

    /**
     * Test getting repost statistics
     */
    public function test_get_repost_statistics() {
        $reposts = array(
            array('source_handle' => 'user1'),
            array('source_handle' => 'user1'),
            array('source_handle' => 'user2')
        );

        $this->database->expects($this->once())
            ->method('get_all_reposts')
            ->willReturn($reposts);

        $stats = $this->processor->get_repost_statistics();
        
        $this->assertEquals(3, $stats['total_reposts']);
        $this->assertEquals(2, $stats['unique_sources']);
        $this->assertEquals(array('user1' => 2, 'user2' => 1), $stats['posts_per_source']);
    }

    /**
     * Test getting engagement trends
     */
    public function test_get_engagement_trends() {
        $reposts = array(
            array(
                'created_at' => '2024-01-01 12:00:00',
                'engagement_metrics' => json_encode(array(
                    'retweet_count' => 10,
                    'like_count' => 50
                ))
            ),
            array(
                'created_at' => '2024-01-02 12:00:00',
                'engagement_metrics' => json_encode(array(
                    'retweet_count' => 20,
                    'like_count' => 100
                ))
            )
        );

        $this->database->expects($this->once())
            ->method('get_all_reposts')
            ->willReturn($reposts);

        $trends = $this->processor->get_engagement_trends();
        
        $this->assertIsArray($trends);
        $this->assertArrayHasKey('daily_averages', $trends);
        $this->assertArrayHasKey('engagement_growth', $trends);
    }

    /**
     * Test detecting content patterns
     */
    public function test_detect_content_patterns() {
        $reposts = array(
            array(
                'original_text' => 'Question tweet? #test',
                'analysis_data' => json_encode(array(
                    'has_question' => true,
                    'hashtags' => array('test')
                ))
            ),
            array(
                'original_text' => 'Call to action! Follow me #awesome',
                'analysis_data' => json_encode(array(
                    'has_call_to_action' => true,
                    'hashtags' => array('awesome')
                ))
            )
        );

        $this->database->expects($this->once())
            ->method('get_all_reposts')
            ->willReturn($reposts);

        $patterns = $this->processor->detect_content_patterns();
        
        $this->assertIsArray($patterns);
        $this->assertArrayHasKey('question_frequency', $patterns);
        $this->assertArrayHasKey('cta_frequency', $patterns);
        $this->assertArrayHasKey('common_hashtags', $patterns);
    }

    /**
     * Test validating repost data
     */
    public function test_validate_repost_data() {
        $valid_data = array(
            'source_handle' => 'testuser',
            'original_tweet_id' => '123456789',
            'original_text' => 'Test tweet',
            'created_at' => '2024-01-01 12:00:00'
        );

        $result = $this->processor->validate_repost_data($valid_data);
        $this->assertTrue($result);

        $invalid_data = array(
            'source_handle' => '', // Invalid empty handle
            'original_tweet_id' => '123456789'
        );

        $result = $this->processor->validate_repost_data($invalid_data);
        $this->assertFalse($result);
    }

    /**
     * Test calculating engagement score
     */
    public function test_calculate_engagement_score() {
        $metrics = array(
            'retweet_count' => 10,
            'like_count' => 50,
            'reply_count' => 5,
            'quote_count' => 2
        );

        $score = $this->processor->calculate_engagement_score($metrics);
        
        $this->assertIsNumeric($score);
        $this->assertGreaterThan(0, $score);
    }

    /**
     * Test detecting sentiment
     */
    public function test_detect_sentiment() {
        $positive_text = 'This is amazing! I love it!';
        $negative_text = 'This is terrible! I hate it!';
        $neutral_text = 'This is a neutral statement.';

        $positive_score = $this->processor->detect_sentiment($positive_text);
        $negative_score = $this->processor->detect_sentiment($negative_text);
        $neutral_score = $this->processor->detect_sentiment($neutral_text);

        $this->assertGreaterThan(0, $positive_score);
        $this->assertLessThan(0, $negative_score);
        $this->assertGreaterThan(-10, $neutral_score);
        $this->assertLessThan(10, $neutral_score);
    }

    /**
     * Test extracting hashtags
     */
    public function test_extract_hashtags() {
        $text = 'This is a #test tweet with #awesome #hashtags';
        
        $hashtags = $this->processor->extract_hashtags($text);
        
        $this->assertEquals(array('test', 'awesome', 'hashtags'), $hashtags);
    }

    /**
     * Test extracting mentions
     */
    public function test_extract_mentions() {
        $text = 'This is a tweet mentioning @user1 and @user2';
        
        $mentions = $this->processor->extract_mentions($text);
        
        $this->assertEquals(array('user1', 'user2'), $mentions);
    }

    /**
     * Test detecting questions
     */
    public function test_detect_questions() {
        $question_text = 'What do you think about this?';
        $non_question_text = 'This is a statement.';

        $this->assertTrue($this->processor->detect_questions($question_text));
        $this->assertFalse($this->processor->detect_questions($non_question_text));
    }

    /**
     * Test detecting call to action
     */
    public function test_detect_call_to_action() {
        $cta_text = 'Follow me for more content!';
        $non_cta_text = 'This is just a regular tweet.';

        $this->assertTrue($this->processor->detect_call_to_action($cta_text));
        $this->assertFalse($this->processor->detect_call_to_action($non_cta_text));
    }

    /**
     * Test determining content type
     */
    public function test_determine_content_type() {
        $text_only = 'This is just text';
        $text_with_url = 'This has a URL https://example.com';
        $text_with_hashtag = 'This has a #hashtag';

        $this->assertEquals('text', $this->processor->determine_content_type($text_only));
        $this->assertEquals('link', $this->processor->determine_content_type($text_with_url));
        $this->assertEquals('text', $this->processor->determine_content_type($text_with_hashtag));
    }

    /**
     * Test batch processing
     */
    public function test_batch_process_posts() {
        $posts_data = array(
            array(
                'id' => '123456789',
                'text' => 'Test tweet 1',
                'created_at' => '2024-01-01 12:00:00',
                'public_metrics' => array(
                    'retweet_count' => 10,
                    'like_count' => 50
                )
            ),
            array(
                'id' => '987654321',
                'text' => 'Test tweet 2',
                'created_at' => '2024-01-01 13:00:00',
                'public_metrics' => array(
                    'retweet_count' => 20,
                    'like_count' => 100
                )
            )
        );

        $this->database->expects($this->exactly(2))
            ->method('insert_repost')
            ->willReturn(1);

        $result = $this->processor->batch_process_posts($posts_data, 'testuser');
        
        $this->assertTrue($result);
    }

    /**
     * Test error handling in batch processing
     */
    public function test_batch_process_posts_with_errors() {
        $posts_data = array(
            array(
                'id' => '123456789',
                'text' => 'Test tweet 1',
                'created_at' => '2024-01-01 12:00:00'
            ),
            array(
                'id' => '', // Invalid empty ID
                'text' => 'Test tweet 2',
                'created_at' => '2024-01-01 13:00:00'
            )
        );

        $this->database->expects($this->once())
            ->method('insert_repost')
            ->willReturn(1);

        $result = $this->processor->batch_process_posts($posts_data, 'testuser');
        
        $this->assertTrue($result); // Should continue processing despite errors
    }

    /**
     * Test performance with large dataset
     */
    public function test_performance_large_dataset() {
        $large_dataset = array();
        for ($i = 0; $i < 100; $i++) {
            $large_dataset[] = array(
                'id' => 'tweet_' . $i,
                'text' => 'Test tweet ' . $i . ' #test',
                'created_at' => '2024-01-01 12:00:00',
                'public_metrics' => array(
                    'retweet_count' => rand(1, 100),
                    'like_count' => rand(1, 500),
                    'reply_count' => rand(1, 50),
                    'quote_count' => rand(1, 20)
                )
            );
        }

        $start_time = microtime(true);
        
        $this->database->expects($this->exactly(100))
            ->method('insert_repost')
            ->willReturn(1);

        $result = $this->processor->batch_process_posts($large_dataset, 'testuser');
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        $this->assertTrue($result);
        $this->assertLessThan(5.0, $execution_time); // Should complete within 5 seconds
    }

    /**
     * Test memory usage
     */
    public function test_memory_usage() {
        $initial_memory = memory_get_usage();
        
        $posts_data = array();
        for ($i = 0; $i < 50; $i++) {
            $posts_data[] = array(
                'id' => 'tweet_' . $i,
                'text' => str_repeat('Test tweet content ', 10) . '#' . $i,
                'created_at' => '2024-01-01 12:00:00',
                'public_metrics' => array(
                    'retweet_count' => rand(1, 100),
                    'like_count' => rand(1, 500)
                )
            );
        }

        $this->database->expects($this->exactly(50))
            ->method('insert_repost')
            ->willReturn(1);

        $this->processor->batch_process_posts($posts_data, 'testuser');
        
        $final_memory = memory_get_usage();
        $memory_increase = $final_memory - $initial_memory;
        
        // Memory increase should be reasonable (less than 10MB)
        $this->assertLessThan(10 * 1024 * 1024, $memory_increase);
    }

    /**
     * Test data integrity
     */
    public function test_data_integrity() {
        $original_data = array(
            'source_handle' => 'testuser',
            'original_tweet_id' => '123456789',
            'original_text' => 'Test tweet with special chars: @#$%^&*()',
            'created_at' => '2024-01-01 12:00:00',
            'engagement_metrics' => array(
                'retweet_count' => 10,
                'like_count' => 50,
                'reply_count' => 5,
                'quote_count' => 2
            )
        );

        $this->database->expects($this->once())
            ->method('insert_repost')
            ->with($this->callback(function($data) use ($original_data) {
                return $data['source_handle'] === $original_data['source_handle'] &&
                       $data['original_tweet_id'] === $original_data['original_tweet_id'] &&
                       $data['original_text'] === $original_data['original_text'] &&
                       $data['engagement_metrics'] === json_encode($original_data['engagement_metrics']);
            }))
            ->willReturn(1);

        $result = $this->processor->store_repost_data($original_data);
        
        $this->assertTrue($result);
    }

    /**
     * Test concurrent processing
     */
    public function test_concurrent_processing() {
        $posts_data = array(
            array(
                'id' => '123456789',
                'text' => 'Test tweet 1',
                'created_at' => '2024-01-01 12:00:00',
                'public_metrics' => array(
                    'retweet_count' => 10,
                    'like_count' => 50
                )
            )
        );

        $this->database->expects($this->once())
            ->method('insert_repost')
            ->willReturn(1);

        // Simulate concurrent processing
        $results = array();
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->processor->process_and_store_posts($posts_data, 'testuser' . $i);
        }
        
        $this->assertContainsOnly('bool', $results);
        $this->assertNotContains(false, $results);
    }

    /**
     * Test edge cases
     */
    public function test_edge_cases() {
        // Test with very long text
        $long_text = str_repeat('This is a very long tweet content. ', 100);
        $analysis = $this->processor->analyze_tweet_content($long_text);
        
        $this->assertIsArray($analysis);
        $this->assertGreaterThan(100, $analysis['word_count']);

        // Test with empty text
        $empty_analysis = $this->processor->analyze_tweet_content('');
        $this->assertEquals(0, $empty_analysis['word_count']);
        $this->assertEquals(0, $empty_analysis['character_count']);

        // Test with special characters only
        $special_chars = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        $special_analysis = $this->processor->analyze_tweet_content($special_chars);
        $this->assertIsArray($special_analysis);
    }

    /**
     * Test error recovery
     */
    public function test_error_recovery() {
        $posts_data = array(
            array(
                'id' => '123456789',
                'text' => 'Valid tweet',
                'created_at' => '2024-01-01 12:00:00',
                'public_metrics' => array(
                    'retweet_count' => 10,
                    'like_count' => 50
                )
            )
        );

        // First call fails, second call succeeds
        $this->database->expects($this->exactly(2))
            ->method('insert_repost')
            ->willReturnOnConsecutiveCalls(false, 1);

        $result = $this->processor->process_and_store_posts($posts_data, 'testuser');
        
        $this->assertTrue($result);
    }

    /**
     * Test cleanup operations
     */
    public function test_cleanup_operations() {
        $this->database->expects($this->once())
            ->method('clear_old_reposts')
            ->with($this->equalTo(30))
            ->willReturn(true);

        $result = $this->processor->cleanup_old_data(30);
        
        $this->assertTrue($result);
    }

    /**
     * Test backup and restore
     */
    public function test_backup_and_restore() {
        $backup_data = array(
            'reposts' => array(
                array(
                    'id' => 1,
                    'source_handle' => 'testuser',
                    'original_text' => 'Backup tweet'
                )
            ),
            'analysis' => array(
                'total_engagement' => 100
            )
        );

        $this->database->expects($this->once())
            ->method('create_backup')
            ->willReturn($backup_data);

        $this->database->expects($this->once())
            ->method('restore_from_backup')
            ->with($this->equalTo($backup_data))
            ->willReturn(true);

        $backup = $this->processor->create_backup();
        $this->assertEquals($backup_data, $backup);

        $restore_result = $this->processor->restore_from_backup($backup_data);
        $this->assertTrue($restore_result);
    }
} 