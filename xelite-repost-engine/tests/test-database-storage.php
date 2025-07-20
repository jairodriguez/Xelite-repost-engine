<?php
/**
 * Test suite for Database Storage
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for Database Storage
 */
class Test_XeliteRepostEngine_Database_Storage extends WP_UnitTestCase {

    /**
     * Processor instance
     *
     * @var XeliteRepostEngine_X_Processor
     */
    private $processor;

    /**
     * Database instance
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
        
        // Create mocks
        $this->x_api = $this->createMock('XeliteRepostEngine_X_API');
        $this->database = new XeliteRepostEngine_Database();
        
        // Create processor instance
        $this->processor = new XeliteRepostEngine_X_Processor($this->database, $this->x_api);
        
        // Create test tables
        $this->database->create_tables();
    }

    /**
     * Test storing single repost data
     */
    public function test_store_repost_data() {
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'This is a test repost',
            'source_handle' => 'testuser',
            'created_at' => '2024-01-01T12:00:00Z',
            'engagement_metrics' => array(
                'retweet_count' => 10,
                'like_count' => 50,
                'reply_count' => 5,
                'quote_count' => 2
            ),
            'entities' => array(
                'hashtags' => array(array('tag' => 'test')),
                'mentions' => array(array('username' => 'user1'))
            ),
            'context_annotations' => array()
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertTrue($result);
        
        // Verify data was stored correctly
        $stored_repost = $this->database->get_repost_by_tweet('123456789', 'testuser');
        $this->assertNotNull($stored_repost);
        $this->assertEquals('123456789', $stored_repost['original_tweet_id']);
        $this->assertEquals('This is a test repost', $stored_repost['original_text']);
        $this->assertEquals('testuser', $stored_repost['source_handle']);
        $this->assertEquals('x', $stored_repost['platform']);
    }

    /**
     * Test storing repost data with invalid data
     */
    public function test_store_repost_data_invalid() {
        $repost_data = array(
            'original_text' => 'Missing tweet ID'
            // Missing required fields
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('missing_field', $result->get_error_code());
    }

    /**
     * Test storing repost data with invalid tweet ID
     */
    public function test_store_repost_data_invalid_tweet_id() {
        $repost_data = array(
            'original_tweet_id' => 'invalid_id',
            'original_text' => 'Test repost',
            'source_handle' => 'testuser'
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_tweet_id', $result->get_error_code());
    }

    /**
     * Test storing repost data with invalid handle
     */
    public function test_store_repost_data_invalid_handle() {
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Test repost',
            'source_handle' => 'invalid@handle#'
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_handle', $result->get_error_code());
    }

    /**
     * Test updating existing repost data
     */
    public function test_update_existing_repost_data() {
        // First, store a repost
        $original_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Original text',
            'source_handle' => 'testuser',
            'engagement_metrics' => array(
                'retweet_count' => 5,
                'like_count' => 25
            )
        );
        
        $this->processor->store_repost_data($original_data);
        
        // Now update with new data
        $updated_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Updated text',
            'source_handle' => 'testuser',
            'engagement_metrics' => array(
                'retweet_count' => 15,
                'like_count' => 75
            ),
            'entities' => array(
                'hashtags' => array(array('tag' => 'updated'))
            )
        );
        
        $result = $this->processor->store_repost_data($updated_data);
        
        $this->assertTrue($result);
        
        // Verify data was updated
        $stored_repost = $this->database->get_repost_by_tweet('123456789', 'testuser');
        $this->assertEquals('Updated text', $stored_repost['original_text']);
        
        $engagement_metrics = json_decode($stored_repost['engagement_metrics'], true);
        $this->assertEquals(15, $engagement_metrics['retweet_count']);
        $this->assertEquals(75, $engagement_metrics['like_count']);
    }

    /**
     * Test batch storage functionality
     */
    public function test_store_reposts_batch() {
        $reposts_array = array(
            array(
                'original_tweet_id' => '123456789',
                'original_text' => 'First repost',
                'source_handle' => 'testuser1',
                'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
            ),
            array(
                'original_tweet_id' => '123456790',
                'original_text' => 'Second repost',
                'source_handle' => 'testuser2',
                'engagement_metrics' => array('retweet_count' => 20, 'like_count' => 100)
            ),
            array(
                'original_tweet_id' => '123456791',
                'original_text' => 'Third repost',
                'source_handle' => 'testuser3',
                'engagement_metrics' => array('retweet_count' => 30, 'like_count' => 150)
            )
        );
        
        $results = $this->processor->store_reposts_batch($reposts_array);
        
        $this->assertIsArray($results);
        $this->assertEquals(3, $results['success']);
        $this->assertEquals(0, $results['errors']);
        $this->assertArrayHasKey('results', $results);
        $this->assertEquals(3, count($results['results']));
        
        // Verify all reposts were stored
        foreach ($reposts_array as $repost_data) {
            $stored = $this->database->get_repost_by_tweet($repost_data['original_tweet_id'], $repost_data['source_handle']);
            $this->assertNotNull($stored);
        }
    }

    /**
     * Test batch storage with errors
     */
    public function test_store_reposts_batch_with_errors() {
        $reposts_array = array(
            array(
                'original_tweet_id' => '123456789',
                'original_text' => 'Valid repost',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
            ),
            array(
                'original_text' => 'Invalid repost - missing tweet ID'
                // Missing required fields
            ),
            array(
                'original_tweet_id' => '123456790',
                'original_text' => 'Another valid repost',
                'source_handle' => 'testuser2',
                'engagement_metrics' => array('retweet_count' => 20, 'like_count' => 100)
            )
        );
        
        $results = $this->processor->store_reposts_batch($reposts_array);
        
        $this->assertIsArray($results);
        $this->assertEquals(2, $results['success']);
        $this->assertEquals(1, $results['errors']);
        $this->assertArrayHasKey('results', $results);
    }

    /**
     * Test duplicate filtering
     */
    public function test_filter_duplicates() {
        // First, store some reposts
        $original_reposts = array(
            array(
                'original_tweet_id' => '123456789',
                'original_text' => 'First repost',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
            ),
            array(
                'original_tweet_id' => '123456790',
                'original_text' => 'Second repost',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 20, 'like_count' => 100)
            )
        );
        
        foreach ($original_reposts as $repost) {
            $this->processor->store_repost_data($repost);
        }
        
        // Now try to filter duplicates
        $new_reposts = array(
            array(
                'original_tweet_id' => '123456789', // Duplicate
                'original_text' => 'First repost updated',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 15, 'like_count' => 75)
            ),
            array(
                'original_tweet_id' => '123456791', // New
                'original_text' => 'Third repost',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 30, 'like_count' => 150)
            ),
            array(
                'original_tweet_id' => '123456790', // Duplicate
                'original_text' => 'Second repost updated',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 25, 'like_count' => 125)
            )
        );
        
        $filtered_reposts = $this->processor->filter_duplicates($new_reposts);
        
        $this->assertIsArray($filtered_reposts);
        $this->assertEquals(1, count($filtered_reposts)); // Only one new repost
        $this->assertEquals('123456791', $filtered_reposts[0]['original_tweet_id']);
    }

    /**
     * Test transaction support
     */
    public function test_transaction_support() {
        // Test successful transaction
        $this->database->start_transaction();
        
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Transaction test',
            'source_handle' => 'testuser',
            'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        $this->assertTrue($result);
        
        $this->database->commit_transaction();
        
        // Verify data was committed
        $stored = $this->database->get_repost_by_tweet('123456789', 'testuser');
        $this->assertNotNull($stored);
    }

    /**
     * Test transaction rollback
     */
    public function test_transaction_rollback() {
        // Test rollback on error
        $this->database->start_transaction();
        
        $repost_data = array(
            'original_tweet_id' => '123456790',
            'original_text' => 'Rollback test',
            'source_handle' => 'testuser',
            'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        $this->assertTrue($result);
        
        // Simulate an error and rollback
        $this->database->rollback_transaction();
        
        // Verify data was rolled back
        $stored = $this->database->get_repost_by_tweet('123456790', 'testuser');
        $this->assertNull($stored);
    }

    /**
     * Test data sanitization
     */
    public function test_data_sanitization() {
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => '<script>alert("XSS")</script>This is a test repost with <strong>HTML</strong>',
            'source_handle' => 'testuser<script>',
            'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertTrue($result);
        
        // Verify data was sanitized
        $stored_repost = $this->database->get_repost_by_tweet('123456789', 'testuser');
        $this->assertNotNull($stored_repost);
        $this->assertEquals('This is a test repost with HTML', $stored_repost['original_text']);
        $this->assertEquals('testuser', $stored_repost['source_handle']);
    }

    /**
     * Test date formatting
     */
    public function test_date_formatting() {
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Date test',
            'source_handle' => 'testuser',
            'created_at' => '2024-01-01T12:00:00Z',
            'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertTrue($result);
        
        // Verify date was formatted correctly
        $stored_repost = $this->database->get_repost_by_tweet('123456789', 'testuser');
        $this->assertNotNull($stored_repost);
        $this->assertEquals('2024-01-01 12:00:00', $stored_repost['repost_date']);
    }

    /**
     * Test engagement metrics storage
     */
    public function test_engagement_metrics_storage() {
        $engagement_metrics = array(
            'retweet_count' => 15,
            'like_count' => 75,
            'reply_count' => 8,
            'quote_count' => 3
        );
        
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Engagement test',
            'source_handle' => 'testuser',
            'engagement_metrics' => $engagement_metrics
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertTrue($result);
        
        // Verify engagement metrics were stored correctly
        $stored_repost = $this->database->get_repost_by_tweet('123456789', 'testuser');
        $this->assertNotNull($stored_repost);
        
        $stored_metrics = json_decode($stored_repost['engagement_metrics'], true);
        $this->assertEquals($engagement_metrics, $stored_metrics);
    }

    /**
     * Test entities and context annotations storage
     */
    public function test_entities_and_context_storage() {
        $entities = array(
            'hashtags' => array(
                array('tag' => 'test'),
                array('tag' => 'wordpress')
            ),
            'mentions' => array(
                array('username' => 'user1'),
                array('username' => 'user2')
            ),
            'urls' => array(
                array('url' => 'https://example.com')
            )
        );
        
        $context_annotations = array(
            'domain' => array(
                'id' => '1',
                'name' => 'Technology',
                'description' => 'Technology related content'
            )
        );
        
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Entities test #test #wordpress @user1 @user2 https://example.com',
            'source_handle' => 'testuser',
            'entities' => $entities,
            'context_annotations' => $context_annotations,
            'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertTrue($result);
        
        // Verify entities and context were stored correctly
        $stored_repost = $this->database->get_repost_by_tweet('123456789', 'testuser');
        $this->assertNotNull($stored_repost);
        
        $analysis_data = json_decode($stored_repost['analysis_data'], true);
        $this->assertEquals($entities, $analysis_data['entities']);
        $this->assertEquals($context_annotations, $analysis_data['context_annotations']);
    }

    /**
     * Test referenced tweet storage
     */
    public function test_referenced_tweet_storage() {
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => 'Referenced tweet test',
            'source_handle' => 'testuser',
            'referenced_tweet_id' => '987654321',
            'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        
        $this->assertTrue($result);
        
        // Verify referenced tweet was stored correctly
        $stored_repost = $this->database->get_repost_by_tweet('123456789', 'testuser');
        $this->assertNotNull($stored_repost);
        
        $analysis_data = json_decode($stored_repost['analysis_data'], true);
        $this->assertEquals('987654321', $analysis_data['referenced_tweet_id']);
    }

    /**
     * Test cleanup functionality
     */
    public function test_cleanup_old_reposts() {
        // Create some old reposts
        $old_reposts = array(
            array(
                'original_tweet_id' => '123456789',
                'original_text' => 'Old repost 1',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
            ),
            array(
                'original_tweet_id' => '123456790',
                'original_text' => 'Old repost 2',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 20, 'like_count' => 100)
            )
        );
        
        foreach ($old_reposts as $repost) {
            $this->processor->store_repost_data($repost);
        }
        
        // Manually set old dates
        $this->database->update('reposts', array('created_at' => '2022-01-01 00:00:00'), array());
        
        // Test cleanup
        $deleted_count = $this->processor->cleanup_old_reposts(365);
        
        $this->assertEquals(2, $deleted_count);
        
        // Verify reposts were deleted
        $remaining = $this->database->count('reposts');
        $this->assertEquals(0, $remaining);
    }

    /**
     * Test export functionality
     */
    public function test_export_repost_data() {
        // Create test reposts
        $reposts = array(
            array(
                'original_tweet_id' => '123456789',
                'original_text' => 'Export test 1',
                'source_handle' => 'testuser1',
                'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
            ),
            array(
                'original_tweet_id' => '123456790',
                'original_text' => 'Export test 2',
                'source_handle' => 'testuser2',
                'engagement_metrics' => array('retweet_count' => 20, 'like_count' => 100)
            )
        );
        
        foreach ($reposts as $repost) {
            $this->processor->store_repost_data($repost);
        }
        
        // Test CSV export
        $csv_export = $this->processor->export_repost_data(array(), 'csv');
        $this->assertIsString($csv_export);
        $this->assertStringContainsString('original_tweet_id', $csv_export);
        $this->assertStringContainsString('123456789', $csv_export);
        
        // Test JSON export
        $json_export = $this->processor->export_repost_data(array(), 'json');
        $this->assertIsString($json_export);
        $json_data = json_decode($json_export, true);
        $this->assertIsArray($json_data);
        $this->assertEquals(2, count($json_data));
    }

    /**
     * Test performance with large batch
     */
    public function test_performance_large_batch() {
        $reposts_array = array();
        
        // Create 100 test reposts
        for ($i = 0; $i < 100; $i++) {
            $reposts_array[] = array(
                'original_tweet_id' => '123456' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'original_text' => "Test repost {$i}",
                'source_handle' => 'testuser',
                'engagement_metrics' => array(
                    'retweet_count' => rand(1, 100),
                    'like_count' => rand(10, 500)
                )
            );
        }
        
        $start_time = microtime(true);
        
        $results = $this->processor->store_reposts_batch($reposts_array);
        
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        $this->assertIsArray($results);
        $this->assertEquals(100, $results['success']);
        $this->assertEquals(0, $results['errors']);
        $this->assertLessThan(10.0, $execution_time); // Should complete within reasonable time
    }

    /**
     * Test memory usage during batch operations
     */
    public function test_memory_usage_batch_operations() {
        $initial_memory = memory_get_usage();
        
        $reposts_array = array();
        
        // Create 50 test reposts with large content
        for ($i = 0; $i < 50; $i++) {
            $reposts_array[] = array(
                'original_tweet_id' => '123456' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'original_text' => str_repeat('Test repost content ', 50), // Large content
                'source_handle' => 'testuser',
                'engagement_metrics' => array(
                    'retweet_count' => rand(1, 100),
                    'like_count' => rand(10, 500)
                ),
                'entities' => array(
                    'hashtags' => array_fill(0, 10, array('tag' => 'test' . $i))
                )
            );
        }
        
        $this->processor->store_reposts_batch($reposts_array);
        
        $final_memory = memory_get_usage();
        $memory_increase = $final_memory - $initial_memory;
        
        // Memory increase should be reasonable (less than 10MB)
        $this->assertLessThan(10 * 1024 * 1024, $memory_increase);
    }

    /**
     * Test data integrity during concurrent operations
     */
    public function test_data_integrity_concurrent_operations() {
        $reposts_array = array();
        
        // Create test data
        for ($i = 0; $i < 10; $i++) {
            $reposts_array[] = array(
                'original_tweet_id' => '123456' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'original_text' => "Concurrent test {$i}",
                'source_handle' => 'testuser',
                'engagement_metrics' => array(
                    'retweet_count' => $i + 1,
                    'like_count' => ($i + 1) * 10
                )
            );
        }
        
        // Simulate concurrent operations
        $results1 = $this->processor->store_reposts_batch($reposts_array);
        $results2 = $this->processor->store_reposts_batch($reposts_array); // Should be all duplicates
        
        $this->assertEquals(10, $results1['success']);
        $this->assertEquals(0, $results1['errors']);
        $this->assertEquals(0, $results2['success']); // All duplicates
        $this->assertEquals(0, $results2['errors']);
        
        // Verify total count is correct
        $total_count = $this->database->count('reposts');
        $this->assertEquals(10, $total_count); // Should only have 10 unique reposts
    }

    /**
     * Test edge cases
     */
    public function test_edge_cases() {
        // Test with very long text
        $long_text = str_repeat('This is a very long repost text. ', 100);
        $repost_data = array(
            'original_tweet_id' => '123456789',
            'original_text' => $long_text,
            'source_handle' => 'testuser',
            'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        $this->assertTrue($result);
        
        // Test with special characters
        $special_text = "Test with special chars: @#$%^&*()_+-=[]{}|;':\",./<>?";
        $repost_data = array(
            'original_tweet_id' => '123456790',
            'original_text' => $special_text,
            'source_handle' => 'testuser',
            'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        $this->assertTrue($result);
        
        // Test with empty engagement metrics
        $repost_data = array(
            'original_tweet_id' => '123456791',
            'original_text' => 'Empty metrics test',
            'source_handle' => 'testuser',
            'engagement_metrics' => array()
        );
        
        $result = $this->processor->store_repost_data($repost_data);
        $this->assertTrue($result);
    }

    /**
     * Test error recovery
     */
    public function test_error_recovery() {
        // Test recovery from invalid data in batch
        $reposts_array = array(
            array(
                'original_tweet_id' => '123456789',
                'original_text' => 'Valid repost',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 10, 'like_count' => 50)
            ),
            array(
                'original_text' => 'Invalid repost'
                // Missing required fields
            ),
            array(
                'original_tweet_id' => '123456790',
                'original_text' => 'Another valid repost',
                'source_handle' => 'testuser',
                'engagement_metrics' => array('retweet_count' => 20, 'like_count' => 100)
            )
        );
        
        $results = $this->processor->store_reposts_batch($reposts_array);
        
        $this->assertEquals(2, $results['success']);
        $this->assertEquals(1, $results['errors']);
        
        // Verify valid reposts were still stored
        $valid1 = $this->database->get_repost_by_tweet('123456789', 'testuser');
        $valid2 = $this->database->get_repost_by_tweet('123456790', 'testuser');
        
        $this->assertNotNull($valid1);
        $this->assertNotNull($valid2);
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up test data
        $this->database->delete('reposts', array());
        
        parent::tearDown();
    }
} 