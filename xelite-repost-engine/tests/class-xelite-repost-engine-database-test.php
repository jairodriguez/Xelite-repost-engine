<?php
/**
 * Database Integration Tests for Xelite Repost Engine
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database integration test class
 */
class XeliteRepostEngine_Database_Test extends WP_UnitTestCase {
    
    /**
     * Database instance
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;
    
    /**
     * Test data for reposts
     *
     * @var array
     */
    private $test_reposts = array();
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize database
        $this->database = new XeliteRepostEngine_Database();
        
        // Create test data
        $this->create_test_data();
    }
    
    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        // Clean up test data
        $this->cleanup_test_data();
        
        parent::tearDown();
    }
    
    /**
     * Create test data for testing
     */
    private function create_test_data() {
        $this->test_reposts = array(
            array(
                'source_handle' => 'test_user_1',
                'original_tweet_id' => '1234567890123456789',
                'original_text' => 'This is a test tweet about productivity tips',
                'platform' => 'x',
                'repost_date' => '2024-01-15 10:30:00',
                'engagement_metrics' => array(
                    'likes' => 150,
                    'retweets' => 25,
                    'replies' => 10
                ),
                'user_id' => 1,
                'repost_count' => 1,
                'is_analyzed' => 0
            ),
            array(
                'source_handle' => 'test_user_2',
                'original_tweet_id' => '1234567890123456790',
                'original_text' => 'Another test tweet about marketing strategies',
                'platform' => 'x',
                'repost_date' => '2024-01-16 14:20:00',
                'engagement_metrics' => array(
                    'likes' => 300,
                    'retweets' => 50,
                    'replies' => 20
                ),
                'user_id' => 2,
                'repost_count' => 2,
                'is_analyzed' => 1,
                'analysis_data' => array(
                    'pattern' => 'question',
                    'tone' => 'professional',
                    'length' => 'medium'
                )
            ),
            array(
                'source_handle' => 'test_user_3',
                'original_tweet_id' => '1234567890123456791',
                'original_text' => 'Third test tweet about business growth',
                'platform' => 'x',
                'repost_date' => '2024-01-17 09:15:00',
                'engagement_metrics' => array(
                    'likes' => 75,
                    'retweets' => 15,
                    'replies' => 5
                ),
                'user_id' => 1,
                'repost_count' => 0,
                'is_analyzed' => 0
            )
        );
    }
    
    /**
     * Clean up test data
     */
    private function cleanup_test_data() {
        // Remove test reposts
        foreach ($this->test_reposts as $repost) {
            $this->database->delete('reposts', array(
                'original_tweet_id' => $repost['original_tweet_id']
            ));
        }
    }
    
    /**
     * Test table creation and structure verification
     */
    public function test_table_creation_and_structure() {
        // Test table creation
        $result = $this->database->create_tables();
        $this->assertTrue($result, 'Table creation should succeed');
        
        // Test table exists
        $this->assertTrue($this->database->table_exists('reposts'), 'Reposts table should exist');
        
        // Test table structure
        $schema = $this->database->get_table_schema('reposts');
        $this->assertIsArray($schema, 'Table schema should be an array');
        
        // Test required columns exist
        $required_columns = array(
            'id', 'source_handle', 'original_tweet_id', 'original_text',
            'platform', 'repost_date', 'engagement_metrics', 'content_variations',
            'post_id', 'original_post_id', 'user_id', 'repost_count',
            'is_analyzed', 'analysis_data', 'created_at', 'updated_at'
        );
        
        foreach ($required_columns as $column) {
            $this->assertArrayHasKey($column, $schema, "Column $column should exist in table");
        }
        
        // Test column types
        $this->assertEquals('bigint(20)', $schema['id']['type'], 'ID column should be bigint');
        $this->assertEquals('varchar(255)', $schema['source_handle']['type'], 'source_handle should be varchar');
        $this->assertEquals('text', $schema['original_text']['type'], 'original_text should be text');
        $this->assertEquals('json', $schema['engagement_metrics']['type'], 'engagement_metrics should be json');
    }
    
    /**
     * Test basic CRUD operations
     */
    public function test_basic_crud_operations() {
        $test_repost = $this->test_reposts[0];
        
        // Test insert
        $insert_id = $this->database->insert_repost($test_repost);
        $this->assertIsInt($insert_id, 'Insert should return an integer ID');
        $this->assertGreaterThan(0, $insert_id, 'Insert ID should be greater than 0');
        
        // Test get by ID
        $retrieved_repost = $this->database->get_row('reposts', array('id' => $insert_id));
        $this->assertIsArray($retrieved_repost, 'Retrieved repost should be an array');
        $this->assertEquals($test_repost['source_handle'], $retrieved_repost['source_handle']);
        $this->assertEquals($test_repost['original_tweet_id'], $retrieved_repost['original_tweet_id']);
        
        // Test update
        $update_data = array('repost_count' => 5);
        $update_result = $this->database->update_repost($update_data, array('id' => $insert_id));
        $this->assertIsInt($update_result, 'Update should return number of affected rows');
        $this->assertEquals(1, $update_result, 'Update should affect 1 row');
        
        // Verify update
        $updated_repost = $this->database->get_row('reposts', array('id' => $insert_id));
        $this->assertEquals(5, $updated_repost['repost_count'], 'Repost count should be updated');
        
        // Test delete
        $delete_result = $this->database->delete('reposts', array('id' => $insert_id));
        $this->assertIsInt($delete_result, 'Delete should return number of affected rows');
        $this->assertEquals(1, $delete_result, 'Delete should affect 1 row');
        
        // Verify deletion
        $deleted_repost = $this->database->get_row('reposts', array('id' => $insert_id));
        $this->assertNull($deleted_repost, 'Repost should be deleted');
    }
    
    /**
     * Test batch operations
     */
    public function test_batch_operations() {
        // Test batch insert
        $batch_result = $this->database->batch_insert_reposts($this->test_reposts);
        $this->assertIsInt($batch_result, 'Batch insert should return number of inserted rows');
        $this->assertEquals(count($this->test_reposts), $batch_result, 'All test reposts should be inserted');
        
        // Verify batch insert
        $all_reposts = $this->database->get_reposts();
        $this->assertGreaterThanOrEqual(count($this->test_reposts), count($all_reposts), 'Batch insert should add all reposts');
        
        // Test batch update
        $update_data = array('is_analyzed' => 1);
        $where_conditions = array();
        
        foreach ($this->test_reposts as $repost) {
            $where_conditions[] = array('original_tweet_id' => $repost['original_tweet_id']);
        }
        
        $batch_update_result = $this->database->batch_update('reposts', $update_data, $where_conditions);
        $this->assertIsInt($batch_update_result, 'Batch update should return number of affected rows');
        $this->assertEquals(count($this->test_reposts), $batch_update_result, 'All reposts should be updated');
        
        // Verify batch update
        foreach ($this->test_reposts as $repost) {
            $updated_repost = $this->database->get_repost_by_tweet($repost['original_tweet_id'], $repost['source_handle']);
            $this->assertEquals(1, $updated_repost['is_analyzed'], 'Repost should be marked as analyzed');
        }
    }
    
    /**
     * Test utility methods
     */
    public function test_utility_methods() {
        // Insert test data
        $this->database->batch_insert_reposts($this->test_reposts);
        
        // Test get_reposts_by_user
        $user_reposts = $this->database->get_reposts_by_user(1);
        $this->assertIsArray($user_reposts, 'User reposts should be an array');
        $this->assertEquals(2, count($user_reposts), 'User 1 should have 2 reposts');
        
        // Test get_reposts_by_source
        $source_reposts = $this->database->get_reposts_by_source('test_user_1');
        $this->assertIsArray($source_reposts, 'Source reposts should be an array');
        $this->assertEquals(1, count($source_reposts), 'test_user_1 should have 1 repost');
        
        // Test get_reposts_by_date_range
        $date_reposts = $this->database->get_reposts_by_date_range('2024-01-15 00:00:00', '2024-01-16 23:59:59');
        $this->assertIsArray($date_reposts, 'Date range reposts should be an array');
        $this->assertEquals(2, count($date_reposts), 'Should have 2 reposts in date range');
        
        // Test get_unanalyzed_reposts
        $unanalyzed_reposts = $this->database->get_unanalyzed_reposts();
        $this->assertIsArray($unanalyzed_reposts, 'Unanalyzed reposts should be an array');
        $this->assertEquals(2, count($unanalyzed_reposts), 'Should have 2 unanalyzed reposts');
        
        // Test mark_repost_analyzed
        $first_repost = $unanalyzed_reposts[0];
        $analysis_data = array('pattern' => 'test', 'tone' => 'casual');
        $mark_result = $this->database->mark_repost_analyzed($first_repost['id'], $analysis_data);
        $this->assertTrue($mark_result, 'Should mark repost as analyzed');
        
        // Verify marked as analyzed
        $analyzed_repost = $this->database->get_row('reposts', array('id' => $first_repost['id']));
        $this->assertEquals(1, $analyzed_repost['is_analyzed'], 'Repost should be marked as analyzed');
        $this->assertIsArray($analyzed_repost['analysis_data'], 'Analysis data should be an array');
    }
    
    /**
     * Test top performing reposts
     */
    public function test_top_performing_reposts() {
        // Insert test data
        $this->database->batch_insert_reposts($this->test_reposts);
        
        // Test get_top_performing_reposts
        $top_reposts = $this->database->get_top_performing_reposts(3, 'total');
        $this->assertIsArray($top_reposts, 'Top reposts should be an array');
        $this->assertLessThanOrEqual(3, count($top_reposts), 'Should return at most 3 reposts');
        
        // Test sorting by different metrics
        $top_by_likes = $this->database->get_top_performing_reposts(3, 'likes');
        $this->assertIsArray($top_by_likes, 'Top reposts by likes should be an array');
        
        $top_by_retweets = $this->database->get_top_performing_reposts(3, 'retweets');
        $this->assertIsArray($top_by_retweets, 'Top reposts by retweets should be an array');
        
        // Verify sorting (first should have highest engagement)
        if (count($top_reposts) > 1) {
            $first_total = $top_reposts[0]['total_engagement'];
            $second_total = $top_reposts[1]['total_engagement'];
            $this->assertGreaterThanOrEqual($second_total, $first_total, 'Reposts should be sorted by total engagement');
        }
    }
    
    /**
     * Test data validation and sanitization
     */
    public function test_data_validation_and_sanitization() {
        // Test valid data
        $valid_data = $this->test_reposts[0];
        $validation = $this->database->validate_repost_data($valid_data);
        $this->assertTrue($validation['valid'], 'Valid data should pass validation');
        $this->assertEmpty($validation['errors'], 'Valid data should have no errors');
        
        // Test invalid data
        $invalid_data = array(
            'source_handle' => '', // Missing required field
            'user_id' => 'not_numeric', // Invalid type
            'engagement_metrics' => 'not_array' // Invalid type
        );
        
        $validation = $this->database->validate_repost_data($invalid_data);
        $this->assertFalse($validation['valid'], 'Invalid data should fail validation');
        $this->assertNotEmpty($validation['errors'], 'Invalid data should have errors');
        
        // Test sanitization
        $dirty_data = array(
            'source_handle' => '<script>alert("xss")</script>test_user',
            'original_text' => '  Test text with extra spaces  ',
            'user_id' => '123',
            'repost_count' => '5'
        );
        
        $sanitized_data = $this->database->sanitize_repost_data($dirty_data);
        $this->assertEquals('test_user', $sanitized_data['source_handle'], 'XSS should be removed');
        $this->assertEquals('Test text with extra spaces', $sanitized_data['original_text'], 'Text should be trimmed');
        $this->assertEquals(123, $sanitized_data['user_id'], 'User ID should be integer');
        $this->assertEquals(5, $sanitized_data['repost_count'], 'Repost count should be integer');
    }
    
    /**
     * Test search functionality
     */
    public function test_search_functionality() {
        // Insert test data
        $this->database->batch_insert_reposts($this->test_reposts);
        
        // Test search by text content
        $search_results = $this->database->search_reposts('productivity');
        $this->assertIsArray($search_results, 'Search results should be an array');
        $this->assertGreaterThan(0, count($search_results), 'Should find reposts with "productivity"');
        
        // Test search by source handle
        $search_results = $this->database->search_reposts('test_user_1');
        $this->assertIsArray($search_results, 'Search results should be an array');
        $this->assertGreaterThan(0, count($search_results), 'Should find reposts by source handle');
        
        // Test empty search
        $search_results = $this->database->search_reposts('');
        $this->assertIsArray($search_results, 'Empty search should return array');
        $this->assertEquals(0, count($search_results), 'Empty search should return no results');
    }
    
    /**
     * Test analytics functionality
     */
    public function test_analytics_functionality() {
        // Insert test data
        $this->database->batch_insert_reposts($this->test_reposts);
        
        // Test daily analytics
        $daily_analytics = $this->database->get_repost_analytics('daily', '2024-01-15', '2024-01-17');
        $this->assertIsArray($daily_analytics, 'Daily analytics should be an array');
        $this->assertGreaterThan(0, count($daily_analytics), 'Should have analytics data');
        
        // Test weekly analytics
        $weekly_analytics = $this->database->get_repost_analytics('weekly', '2024-01-15', '2024-01-17');
        $this->assertIsArray($weekly_analytics, 'Weekly analytics should be an array');
        
        // Test monthly analytics
        $monthly_analytics = $this->database->get_repost_analytics('monthly', '2024-01-01', '2024-01-31');
        $this->assertIsArray($monthly_analytics, 'Monthly analytics should be an array');
        
        // Verify analytics data structure
        if (!empty($daily_analytics)) {
            $first_period = $daily_analytics[0];
            $this->assertArrayHasKey('period', $first_period, 'Analytics should have period');
            $this->assertArrayHasKey('total_reposts', $first_period, 'Analytics should have total_reposts');
            $this->assertArrayHasKey('avg_likes', $first_period, 'Analytics should have avg_likes');
        }
    }
    
    /**
     * Test database statistics
     */
    public function test_database_statistics() {
        // Insert test data
        $this->database->batch_insert_reposts($this->test_reposts);
        
        // Get database stats
        $stats = $this->database->get_database_stats();
        $this->assertIsArray($stats, 'Database stats should be an array');
        
        // Verify stats structure
        $this->assertArrayHasKey('total_reposts', $stats, 'Stats should have total_reposts');
        $this->assertArrayHasKey('analyzed_reposts', $stats, 'Stats should have analyzed_reposts');
        $this->assertArrayHasKey('unanalyzed_reposts', $stats, 'Stats should have unanalyzed_reposts');
        $this->assertArrayHasKey('total_users', $stats, 'Stats should have total_users');
        $this->assertArrayHasKey('total_sources', $stats, 'Stats should have total_sources');
        $this->assertArrayHasKey('date_range', $stats, 'Stats should have date_range');
        
        // Verify stats values
        $this->assertGreaterThanOrEqual(3, $stats['total_reposts'], 'Should have at least 3 total reposts');
        $this->assertEquals(1, $stats['analyzed_reposts'], 'Should have 1 analyzed repost');
        $this->assertEquals(2, $stats['unanalyzed_reposts'], 'Should have 2 unanalyzed reposts');
        $this->assertEquals(2, $stats['total_users'], 'Should have 2 unique users');
        $this->assertEquals(3, $stats['total_sources'], 'Should have 3 unique sources');
    }
    
    /**
     * Test export functionality
     */
    public function test_export_functionality() {
        // Insert test data
        $this->database->batch_insert_reposts($this->test_reposts);
        
        // Test export all reposts
        $export_data = $this->database->export_reposts();
        $this->assertIsArray($export_data, 'Export data should be an array');
        $this->assertGreaterThanOrEqual(3, count($export_data), 'Should export at least 3 reposts');
        
        // Verify export structure
        if (!empty($export_data)) {
            $first_export = $export_data[0];
            $this->assertArrayHasKey('id', $first_export, 'Export should have id');
            $this->assertArrayHasKey('source_handle', $first_export, 'Export should have source_handle');
            $this->assertArrayHasKey('original_text', $first_export, 'Export should have original_text');
            $this->assertArrayHasKey('likes', $first_export, 'Export should have likes');
            $this->assertArrayHasKey('retweets', $first_export, 'Export should have retweets');
            $this->assertArrayHasKey('replies', $first_export, 'Export should have replies');
        }
        
        // Test export with filters
        $filtered_export = $this->database->export_reposts(
            array('user_id' => 1),
            array('created_at' => 'DESC'),
            2
        );
        $this->assertIsArray($filtered_export, 'Filtered export should be an array');
        $this->assertLessThanOrEqual(2, count($filtered_export), 'Should respect limit');
    }
    
    /**
     * Test database cleanup
     */
    public function test_database_cleanup() {
        // Insert test data
        $this->database->batch_insert_reposts($this->test_reposts);
        
        // Verify data exists
        $initial_count = $this->database->count('reposts');
        $this->assertGreaterThan(0, $initial_count, 'Should have reposts before cleanup');
        
        // Test cleanup (this won't actually delete our test data since it's recent)
        $cleanup_result = $this->database->cleanup_old_reposts(1); // 1 day old
        $this->assertIsInt($cleanup_result, 'Cleanup should return number of deleted rows');
        
        // Verify data still exists (should be recent)
        $final_count = $this->database->count('reposts');
        $this->assertEquals($initial_count, $final_count, 'Recent data should not be cleaned up');
    }
    
    /**
     * Test concurrent database operations
     */
    public function test_concurrent_operations() {
        // This test simulates concurrent operations
        $results = array();
        
        // Simulate multiple concurrent inserts
        for ($i = 0; $i < 5; $i++) {
            $test_data = array(
                'source_handle' => "concurrent_user_$i",
                'original_tweet_id' => "concurrent_tweet_$i",
                'original_text' => "Concurrent test tweet $i",
                'platform' => 'x',
                'repost_date' => current_time('mysql'),
                'engagement_metrics' => array('likes' => $i * 10, 'retweets' => $i * 2, 'replies' => $i),
                'user_id' => $i + 1,
                'repost_count' => $i,
                'is_analyzed' => 0
            );
            
            $results[] = $this->database->insert_repost($test_data);
        }
        
        // Verify all inserts succeeded
        foreach ($results as $result) {
            $this->assertIsInt($result, 'Concurrent insert should return integer ID');
            $this->assertGreaterThan(0, $result, 'Concurrent insert ID should be greater than 0');
        }
        
        // Verify all data was inserted
        $total_count = $this->database->count('reposts', array('source_handle' => array('LIKE', 'concurrent_user_%')));
        $this->assertEquals(5, $total_count, 'All concurrent inserts should be successful');
    }
    
    /**
     * Test database upgrade functionality
     */
    public function test_database_upgrade() {
        // Test current version
        $current_version = $this->database->get_database_version();
        $this->assertIsString($current_version, 'Database version should be a string');
        
        // Test upgrade detection
        $needs_upgrade = $this->database->needs_upgrade();
        $this->assertIsBool($needs_upgrade, 'Upgrade detection should return boolean');
        
        // Test upgrade process (should not need upgrade in test environment)
        if ($needs_upgrade) {
            $upgrade_result = $this->database->upgrade_database();
            $this->assertTrue($upgrade_result, 'Database upgrade should succeed');
        }
    }
    
    /**
     * Test error handling
     */
    public function test_error_handling() {
        // Test invalid table name
        $result = $this->database->insert('invalid_table', array('test' => 'data'));
        $this->assertFalse($result, 'Insert to invalid table should fail');
        
        // Test invalid data for batch insert
        $invalid_batch = array(
            array('invalid_field' => 'data'), // Missing required fields
            array('source_handle' => 'test', 'original_tweet_id' => '123', 'original_text' => 'test')
        );
        
        $batch_result = $this->database->batch_insert_reposts($invalid_batch);
        $this->assertIsInt($batch_result, 'Batch insert should return integer (partial success)');
        
        // Test invalid user ID
        $user_reposts = $this->database->get_reposts_by_user('invalid_id');
        $this->assertIsArray($user_reposts, 'Invalid user ID should return empty array');
        $this->assertEquals(0, count($user_reposts), 'Invalid user ID should return no results');
    }
    
    /**
     * Test performance with large datasets
     */
    public function test_performance_large_datasets() {
        // Create large dataset
        $large_dataset = array();
        for ($i = 0; $i < 100; $i++) {
            $large_dataset[] = array(
                'source_handle' => "perf_user_$i",
                'original_tweet_id' => "perf_tweet_$i",
                'original_text' => "Performance test tweet $i with some content to make it longer",
                'platform' => 'x',
                'repost_date' => date('Y-m-d H:i:s', strtotime("-$i hours")),
                'engagement_metrics' => array(
                    'likes' => rand(10, 1000),
                    'retweets' => rand(5, 200),
                    'replies' => rand(1, 50)
                ),
                'user_id' => rand(1, 10),
                'repost_count' => rand(0, 5),
                'is_analyzed' => rand(0, 1)
            );
        }
        
        // Test batch insert performance
        $start_time = microtime(true);
        $batch_result = $this->database->batch_insert_reposts($large_dataset);
        $end_time = microtime(true);
        
        $this->assertIsInt($batch_result, 'Large batch insert should return integer');
        $this->assertEquals(100, $batch_result, 'All 100 records should be inserted');
        
        $execution_time = $end_time - $start_time;
        $this->assertLessThan(5.0, $execution_time, 'Large batch insert should complete within 5 seconds');
        
        // Test query performance
        $start_time = microtime(true);
        $query_result = $this->database->get_reposts(array(), array('created_at' => 'DESC'), 50);
        $end_time = microtime(true);
        
        $this->assertIsArray($query_result, 'Large query should return array');
        $this->assertLessThan(1.0, $end_time - $start_time, 'Large query should complete within 1 second');
        
        // Clean up large dataset
        foreach ($large_dataset as $data) {
            $this->database->delete('reposts', array('original_tweet_id' => $data['original_tweet_id']));
        }
    }
} 