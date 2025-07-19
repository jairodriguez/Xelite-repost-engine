<?php
/**
 * Test User Context Completeness Check
 *
 * This file contains comprehensive tests for the user context completeness
 * checking functionality to ensure it works correctly with various data combinations.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for completeness checking
 */
class XeliteRepostEngine_Completeness_Test {
    
    /**
     * Run all tests
     */
    public static function run_all_tests() {
        echo "<h2>User Context Completeness Tests</h2>\n";
        
        self::test_empty_user_context();
        self::test_partial_user_context();
        self::test_complete_user_context();
        self::test_optional_fields_only();
        self::test_required_fields_only();
        self::test_mixed_completeness();
        self::test_edge_cases();
        self::test_filter_functionality();
        
        echo "<h3>All tests completed!</h3>\n";
    }
    
    /**
     * Test empty user context
     */
    public static function test_empty_user_context() {
        echo "<h3>Test: Empty User Context</h3>\n";
        
        // Create a test user with no context data
        $user_id = self::create_test_user('empty_user');
        
        $user_meta = xelite_repost_engine()->container->get('user_meta');
        $completeness = $user_meta->is_context_complete($user_id);
        
        echo "<p><strong>Expected:</strong> 0% complete, incomplete status</p>\n";
        echo "<p><strong>Actual:</strong> {$completeness['completeness_percentage']}% complete, " . 
             ($completeness['complete'] ? 'complete' : 'incomplete') . " status</p>\n";
        
        echo "<p><strong>Missing Fields:</strong> " . count($completeness['missing_fields']) . "</p>\n";
        echo "<p><strong>Message:</strong> {$completeness['message']}</p>\n";
        
        self::cleanup_test_user($user_id);
        echo "<hr>\n";
    }
    
    /**
     * Test partial user context
     */
    public static function test_partial_user_context() {
        echo "<h3>Test: Partial User Context</h3>\n";
        
        // Create a test user with some context data
        $user_id = self::create_test_user('partial_user');
        
        // Add some but not all required fields
        update_user_meta($user_id, 'dream-client', 'Small business owners');
        update_user_meta($user_id, 'writing-style', 'Professional and direct');
        // Missing: dream-client-pain-points
        
        $user_meta = xelite_repost_engine()->container->get('user_meta');
        $completeness = $user_meta->is_context_complete($user_id);
        
        echo "<p><strong>Expected:</strong> ~50% complete, incomplete status</p>\n";
        echo "<p><strong>Actual:</strong> {$completeness['completeness_percentage']}% complete, " . 
             ($completeness['complete'] ? 'complete' : 'incomplete') . " status</p>\n";
        
        echo "<p><strong>Missing Fields:</strong> " . count($completeness['missing_fields']) . "</p>\n";
        echo "<p><strong>Message:</strong> {$completeness['message']}</p>\n";
        
        self::cleanup_test_user($user_id);
        echo "<hr>\n";
    }
    
    /**
     * Test complete user context
     */
    public static function test_complete_user_context() {
        echo "<h3>Test: Complete User Context</h3>\n";
        
        // Create a test user with complete context data
        $user_id = self::create_test_user('complete_user');
        
        // Add all required fields
        update_user_meta($user_id, 'dream-client', 'Digital marketers struggling with lead generation');
        update_user_meta($user_id, 'writing-style', 'Conversational and engaging');
        update_user_meta($user_id, 'dream-client-pain-points', 'Low conversion rates and poor ROI');
        
        // Add some optional fields
        update_user_meta($user_id, 'topic', 'Digital Marketing');
        update_user_meta($user_id, 'personal-context', '10+ years in digital marketing');
        update_user_meta($user_id, 'irresistible-offer', 'Triple your leads in 30 days');
        update_user_meta($user_id, 'ikigai', 'Helping businesses grow through effective marketing');
        
        $user_meta = xelite_repost_engine()->container->get('user_meta');
        $completeness = $user_meta->is_context_complete($user_id);
        
        echo "<p><strong>Expected:</strong> 100% complete, complete status</p>\n";
        echo "<p><strong>Actual:</strong> {$completeness['completeness_percentage']}% complete, " . 
             ($completeness['complete'] ? 'complete' : 'incomplete') . " status</p>\n";
        
        echo "<p><strong>Missing Fields:</strong> " . count($completeness['missing_fields']) . "</p>\n";
        echo "<p><strong>Message:</strong> {$completeness['message']}</p>\n";
        
        self::cleanup_test_user($user_id);
        echo "<hr>\n";
    }
    
    /**
     * Test optional fields only
     */
    public static function test_optional_fields_only() {
        echo "<h3>Test: Optional Fields Only</h3>\n";
        
        // Create a test user with only optional fields
        $user_id = self::create_test_user('optional_user');
        
        // Add only optional fields
        update_user_meta($user_id, 'topic', 'Fitness');
        update_user_meta($user_id, 'personal-context', 'Certified personal trainer');
        update_user_meta($user_id, 'irresistible-offer', 'Transform your body in 12 weeks');
        update_user_meta($user_id, 'ikigai', 'Empowering people to live healthier lives');
        
        $user_meta = xelite_repost_engine()->container->get('user_meta');
        $completeness = $user_meta->is_context_complete($user_id);
        
        echo "<p><strong>Expected:</strong> ~25% complete, incomplete status</p>\n";
        echo "<p><strong>Actual:</strong> {$completeness['completeness_percentage']}% complete, " . 
             ($completeness['complete'] ? 'complete' : 'incomplete') . " status</p>\n";
        
        echo "<p><strong>Missing Fields:</strong> " . count($completeness['missing_fields']) . "</p>\n";
        echo "<p><strong>Message:</strong> {$completeness['message']}</p>\n";
        
        self::cleanup_test_user($user_id);
        echo "<hr>\n";
    }
    
    /**
     * Test required fields only
     */
    public static function test_required_fields_only() {
        echo "<h3>Test: Required Fields Only</h3>\n";
        
        // Create a test user with only required fields
        $user_id = self::create_test_user('required_user');
        
        // Add only required fields
        update_user_meta($user_id, 'dream-client', 'Startup founders');
        update_user_meta($user_id, 'writing-style', 'Casual and friendly');
        update_user_meta($user_id, 'dream-client-pain-points', 'Scaling challenges and funding issues');
        
        $user_meta = xelite_repost_engine()->container->get('user_meta');
        $completeness = $user_meta->is_context_complete($user_id);
        
        echo "<p><strong>Expected:</strong> 75% complete, complete status</p>\n";
        echo "<p><strong>Actual:</strong> {$completeness['completeness_percentage']}% complete, " . 
             ($completeness['complete'] ? 'complete' : 'incomplete') . " status</p>\n";
        
        echo "<p><strong>Missing Fields:</strong> " . count($completeness['missing_fields']) . "</p>\n";
        echo "<p><strong>Message:</strong> {$completeness['message']}</p>\n";
        
        self::cleanup_test_user($user_id);
        echo "<hr>\n";
    }
    
    /**
     * Test mixed completeness scenarios
     */
    public static function test_mixed_completeness() {
        echo "<h3>Test: Mixed Completeness Scenarios</h3>\n";
        
        $scenarios = array(
            'scenario_1' => array(
                'dream-client' => 'E-commerce store owners',
                'writing-style' => 'Professional',
                // Missing dream-client-pain-points
                'topic' => 'E-commerce'
            ),
            'scenario_2' => array(
                'dream-client' => 'SaaS companies',
                // Missing writing-style
                'dream-client-pain-points' => 'Customer churn and retention',
                'personal-context' => 'Former SaaS founder'
            ),
            'scenario_3' => array(
                // Missing dream-client
                'writing-style' => 'Humorous and engaging',
                'dream-client-pain-points' => 'Low engagement rates',
                'irresistible-offer' => 'Double your engagement'
            )
        );
        
        foreach ($scenarios as $scenario_name => $fields) {
            echo "<h4>Scenario: {$scenario_name}</h4>\n";
            
            $user_id = self::create_test_user($scenario_name);
            
            foreach ($fields as $field => $value) {
                update_user_meta($user_id, $field, $value);
            }
            
            $user_meta = xelite_repost_engine()->container->get('user_meta');
            $completeness = $user_meta->is_context_complete($user_id);
            
            echo "<p><strong>Completeness:</strong> {$completeness['completeness_percentage']}%</p>\n";
            echo "<p><strong>Status:</strong> " . ($completeness['complete'] ? 'Complete' : 'Incomplete') . "</p>\n";
            echo "<p><strong>Missing:</strong> " . count($completeness['missing_fields']) . " fields</p>\n";
            
            self::cleanup_test_user($user_id);
        }
        
        echo "<hr>\n";
    }
    
    /**
     * Test edge cases
     */
    public static function test_edge_cases() {
        echo "<h3>Test: Edge Cases</h3>\n";
        
        // Test with invalid user ID
        echo "<h4>Invalid User ID</h4>\n";
        $user_meta = xelite_repost_engine()->container->get('user_meta');
        $completeness = $user_meta->is_context_complete(999999);
        
        echo "<p><strong>Result:</strong> {$completeness['completeness_percentage']}% complete</p>\n";
        echo "<p><strong>Message:</strong> {$completeness['message']}</p>\n";
        
        // Test with empty string values
        echo "<h4>Empty String Values</h4>\n";
        $user_id = self::create_test_user('empty_strings');
        update_user_meta($user_id, 'dream-client', '');
        update_user_meta($user_id, 'writing-style', '   ');
        update_user_meta($user_id, 'dream-client-pain-points', '');
        
        $completeness = $user_meta->is_context_complete($user_id);
        echo "<p><strong>Result:</strong> {$completeness['completeness_percentage']}% complete</p>\n";
        echo "<p><strong>Status:</strong> " . ($completeness['complete'] ? 'Complete' : 'Incomplete') . "</p>\n";
        
        self::cleanup_test_user($user_id);
        echo "<hr>\n";
    }
    
    /**
     * Test filter functionality
     */
    public static function test_filter_functionality() {
        echo "<h3>Test: Filter Functionality</h3>\n";
        
        // Test custom completeness criteria filter
        add_filter('xelite_repost_engine_completeness_criteria', function($criteria) {
            $criteria['required_fields'] = array('dream-client'); // Only require dream-client
            $criteria['field_weights'] = array(
                'dream-client' => 50,
                'writing-style' => 25,
                'dream-client-pain-points' => 25
            );
            return $criteria;
        });
        
        $user_id = self::create_test_user('filter_test');
        update_user_meta($user_id, 'dream-client', 'Test client');
        // Missing other fields
        
        $user_meta = xelite_repost_engine()->container->get('user_meta');
        $completeness = $user_meta->is_context_complete($user_id);
        
        echo "<p><strong>Custom Criteria Result:</strong> {$completeness['completeness_percentage']}% complete</p>\n";
        echo "<p><strong>Status:</strong> " . ($completeness['complete'] ? 'Complete' : 'Incomplete') . "</p>\n";
        
        // Remove the filter
        remove_all_filters('xelite_repost_engine_completeness_criteria');
        
        self::cleanup_test_user($user_id);
        echo "<hr>\n";
    }
    
    /**
     * Create a test user
     *
     * @param string $username Username
     * @return int User ID
     */
    private static function create_test_user($username) {
        $user_id = wp_create_user(
            'test_' . $username . '_' . time(),
            'test_password_' . time(),
            'test_' . $username . '@example.com'
        );
        
        if (is_wp_error($user_id)) {
            echo "<p style='color: red;'>Error creating test user: " . $user_id->get_error_message() . "</p>\n";
            return 0;
        }
        
        return $user_id;
    }
    
    /**
     * Clean up test user
     *
     * @param int $user_id User ID
     */
    private static function cleanup_test_user($user_id) {
        if ($user_id > 0) {
            wp_delete_user($user_id);
        }
    }
}

// Run tests if this file is accessed directly
if (defined('WP_CLI') && WP_CLI) {
    XeliteRepostEngine_Completeness_Test::run_all_tests();
} elseif (isset($_GET['run_completeness_tests']) && current_user_can('manage_options')) {
    XeliteRepostEngine_Completeness_Test::run_all_tests();
} 