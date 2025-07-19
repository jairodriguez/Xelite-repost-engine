<?php
/**
 * Database Integration Test Runner
 *
 * This script runs database integration tests for the Xelite Repost Engine plugin.
 * It can be run independently or as part of a WordPress testing environment.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // For testing outside WordPress
    define('ABSPATH', dirname(__FILE__) . '/../');
    define('WP_DEBUG', true);
    define('XELITE_REPOST_ENGINE_VERSION', '1.0.0');
}

// Load required files
require_once dirname(__FILE__) . '/../includes/class-xelite-repost-engine-database.php';
require_once dirname(__FILE__) . '/class-xelite-repost-engine-mock-data-generator.php';

/**
 * Simple test runner class
 */
class XeliteRepostEngine_Test_Runner {
    
    /**
     * Database instance
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;
    
    /**
     * Mock data generator
     *
     * @var XeliteRepostEngine_Mock_Data_Generator
     */
    private $mock_generator;
    
    /**
     * Test results
     *
     * @var array
     */
    private $test_results = array();
    
    /**
     * Initialize test runner
     */
    public function __construct() {
        $this->database = new XeliteRepostEngine_Database();
        $this->mock_generator = new XeliteRepostEngine_Mock_Data_Generator();
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "Starting Xelite Repost Engine Database Integration Tests\n";
        echo "=======================================================\n\n";
        
        $tests = array(
            'test_table_creation_and_structure' => 'Table Creation and Structure',
            'test_basic_crud_operations' => 'Basic CRUD Operations',
            'test_batch_operations' => 'Batch Operations',
            'test_utility_methods' => 'Utility Methods',
            'test_top_performing_reposts' => 'Top Performing Reposts',
            'test_data_validation_and_sanitization' => 'Data Validation and Sanitization',
            'test_search_functionality' => 'Search Functionality',
            'test_analytics_functionality' => 'Analytics Functionality',
            'test_database_statistics' => 'Database Statistics',
            'test_export_functionality' => 'Export Functionality',
            'test_database_cleanup' => 'Database Cleanup',
            'test_concurrent_operations' => 'Concurrent Operations',
            'test_database_upgrade' => 'Database Upgrade',
            'test_error_handling' => 'Error Handling',
            'test_performance_large_datasets' => 'Performance with Large Datasets'
        );
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test_method => $test_name) {
            echo "Running: $test_name... ";
            
            try {
                $result = $this->$test_method();
                if ($result) {
                    echo "✅ PASSED\n";
                    $passed++;
                } else {
                    echo "❌ FAILED\n";
                    $failed++;
                }
            } catch (Exception $e) {
                echo "❌ FAILED (Exception: " . $e->getMessage() . ")\n";
                $failed++;
            }
            
            // Clean up after each test
            $this->cleanup_test_data();
        }
        
        echo "\nTest Results Summary:\n";
        echo "====================\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        echo "Total: " . ($passed + $failed) . "\n";
        
        if ($failed === 0) {
            echo "\n🎉 All tests passed! Database integration is working correctly.\n";
        } else {
            echo "\n⚠️  Some tests failed. Please review the implementation.\n";
        }
        
        return $failed === 0;
    }
    
    /**
     * Test table creation and structure verification
     */
    private function test_table_creation_and_structure() {
        // Test table creation
        $result = $this->database->create_tables();
        if (!$result) {
            return false;
        }
        
        // Test table exists
        if (!$this->database->table_exists('reposts')) {
            return false;
        }
        
        // Test table structure
        $schema = $this->database->get_table_schema('reposts');
        if (!is_array($schema)) {
            return false;
        }
        
        // Test required columns exist
        $required_columns = array(
            'id', 'source_handle', 'original_tweet_id', 'original_text',
            'platform', 'repost_date', 'engagement_metrics', 'content_variations',
            'post_id', 'original_post_id', 'user_id', 'repost_count',
            'is_analyzed', 'analysis_data', 'created_at', 'updated_at'
        );
        
        foreach ($required_columns as $column) {
            if (!array_key_exists($column, $schema)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Test basic CRUD operations
     */
    private function test_basic_crud_operations() {
        $test_repost = $this->mock_generator->generate_repost();
        
        // Test insert
        $insert_id = $this->database->insert_repost($test_repost);
        if (!is_int($insert_id) || $insert_id <= 0) {
            return false;
        }
        
        // Test get by ID
        $retrieved_repost = $this->database->get_row('reposts', array('id' => $insert_id));
        if (!is_array($retrieved_repost)) {
            return false;
        }
        
        if ($retrieved_repost['source_handle'] !== $test_repost['source_handle']) {
            return false;
        }
        
        // Test update
        $update_data = array('repost_count' => 5);
        $update_result = $this->database->update_repost($update_data, array('id' => $insert_id));
        if (!is_int($update_result) || $update_result !== 1) {
            return false;
        }
        
        // Test delete
        $delete_result = $this->database->delete('reposts', array('id' => $insert_id));
        if (!is_int($delete_result) || $delete_result !== 1) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test batch operations
     */
    private function test_batch_operations() {
        $test_reposts = $this->mock_generator->generate_reposts(3);
        
        // Test batch insert
        $batch_result = $this->database->batch_insert_reposts($test_reposts);
        if (!is_int($batch_result) || $batch_result !== count($test_reposts)) {
            return false;
        }
        
        // Test batch update
        $update_data = array('is_analyzed' => 1);
        $where_conditions = array();
        
        foreach ($test_reposts as $repost) {
            $where_conditions[] = array('original_tweet_id' => $repost['original_tweet_id']);
        }
        
        $batch_update_result = $this->database->batch_update('reposts', $update_data, $where_conditions);
        if (!is_int($batch_update_result) || $batch_update_result !== count($test_reposts)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test utility methods
     */
    private function test_utility_methods() {
        $test_reposts = $this->mock_generator->generate_reposts(3, array('user_id' => 1));
        $this->database->batch_insert_reposts($test_reposts);
        
        // Test get_reposts_by_user
        $user_reposts = $this->database->get_reposts_by_user(1);
        if (!is_array($user_reposts)) {
            return false;
        }
        
        // Test get_reposts_by_source
        $source_reposts = $this->database->get_reposts_by_source($test_reposts[0]['source_handle']);
        if (!is_array($source_reposts)) {
            return false;
        }
        
        // Test get_unanalyzed_reposts
        $unanalyzed_reposts = $this->database->get_unanalyzed_reposts();
        if (!is_array($unanalyzed_reposts)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test top performing reposts
     */
    private function test_top_performing_reposts() {
        $test_reposts = $this->mock_generator->generate_reposts(3);
        $this->database->batch_insert_reposts($test_reposts);
        
        $top_reposts = $this->database->get_top_performing_reposts(3, 'total');
        if (!is_array($top_reposts)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test data validation and sanitization
     */
    private function test_data_validation_and_sanitization() {
        $valid_data = $this->mock_generator->generate_repost();
        $validation = $this->database->validate_repost_data($valid_data);
        
        if (!$validation['valid'] || !empty($validation['errors'])) {
            return false;
        }
        
        $invalid_data = array(
            'source_handle' => '', // Missing required field
            'user_id' => 'not_numeric' // Invalid type
        );
        
        $validation = $this->database->validate_repost_data($invalid_data);
        if ($validation['valid'] || empty($validation['errors'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test search functionality
     */
    private function test_search_functionality() {
        $test_reposts = $this->mock_generator->generate_reposts(3);
        $this->database->batch_insert_reposts($test_reposts);
        
        $search_results = $this->database->search_reposts('test');
        if (!is_array($search_results)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test analytics functionality
     */
    private function test_analytics_functionality() {
        $test_reposts = $this->mock_generator->generate_reposts(3);
        $this->database->batch_insert_reposts($test_reposts);
        
        $analytics = $this->database->get_repost_analytics('daily', '2024-01-01', '2024-12-31');
        if (!is_array($analytics)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test database statistics
     */
    private function test_database_statistics() {
        $test_reposts = $this->mock_generator->generate_reposts(3);
        $this->database->batch_insert_reposts($test_reposts);
        
        $stats = $this->database->get_database_stats();
        if (!is_array($stats)) {
            return false;
        }
        
        $required_keys = array('total_reposts', 'analyzed_reposts', 'unanalyzed_reposts', 'total_users', 'total_sources');
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $stats)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Test export functionality
     */
    private function test_export_functionality() {
        $test_reposts = $this->mock_generator->generate_reposts(3);
        $this->database->batch_insert_reposts($test_reposts);
        
        $export_data = $this->database->export_reposts();
        if (!is_array($export_data)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test database cleanup
     */
    private function test_database_cleanup() {
        $test_reposts = $this->mock_generator->generate_reposts(3);
        $this->database->batch_insert_reposts($test_reposts);
        
        $cleanup_result = $this->database->cleanup_old_reposts(1);
        if (!is_int($cleanup_result)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test concurrent operations
     */
    private function test_concurrent_operations() {
        $results = array();
        
        for ($i = 0; $i < 5; $i++) {
            $test_data = $this->mock_generator->generate_repost();
            $results[] = $this->database->insert_repost($test_data);
        }
        
        foreach ($results as $result) {
            if (!is_int($result) || $result <= 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Test database upgrade functionality
     */
    private function test_database_upgrade() {
        $current_version = $this->database->get_database_version();
        if (!is_string($current_version)) {
            return false;
        }
        
        $needs_upgrade = $this->database->needs_upgrade();
        if (!is_bool($needs_upgrade)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test error handling
     */
    private function test_error_handling() {
        // Test invalid table name
        $result = $this->database->insert('invalid_table', array('test' => 'data'));
        if ($result !== false) {
            return false;
        }
        
        // Test invalid user ID
        $user_reposts = $this->database->get_reposts_by_user('invalid_id');
        if (!is_array($user_reposts) || count($user_reposts) !== 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Test performance with large datasets
     */
    private function test_performance_large_datasets() {
        $large_dataset = $this->mock_generator->generate_performance_test_data(100);
        
        $start_time = microtime(true);
        $batch_result = $this->database->batch_insert_reposts($large_dataset);
        $end_time = microtime(true);
        
        if (!is_int($batch_result) || $batch_result !== 100) {
            return false;
        }
        
        $execution_time = $end_time - $start_time;
        if ($execution_time > 5.0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Clean up test data
     */
    private function cleanup_test_data() {
        // Remove test data by source handle patterns
        $test_patterns = array(
            'test_user_%',
            'concurrent_user_%',
            'perf_user_%',
            'date_user_%'
        );
        
        foreach ($test_patterns as $pattern) {
            $this->database->delete('reposts', array('source_handle' => array('LIKE', $pattern)));
        }
    }
}

// Run tests if this file is executed directly
if (php_sapi_name() === 'cli' || !defined('WP_TESTS_DIR')) {
    $runner = new XeliteRepostEngine_Test_Runner();
    $success = $runner->run_all_tests();
    exit($success ? 0 : 1);
} 