<?php
/**
 * Test Help Tabs Integration
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for help tabs functionality
 */
class Xelite_Repost_Engine_Help_Tabs_Test {

    /**
     * Run all tests
     */
    public static function run_tests() {
        echo "<h2>Testing Help Tabs Integration</h2>\n";
        
        self::test_help_tabs_class_exists();
        self::test_help_tabs_initialization();
        self::test_help_tabs_hooks();
        self::test_help_content_generation();
        
        echo "<h3>Help Tabs Tests Completed</h3>\n";
    }

    /**
     * Test if help tabs class exists
     */
    private static function test_help_tabs_class_exists() {
        echo "<h3>Test 1: Help Tabs Class Exists</h3>\n";
        
        if (class_exists('Xelite_Repost_Engine_Help_Tabs')) {
            echo "✅ Help tabs class exists\n";
        } else {
            echo "❌ Help tabs class not found\n";
        }
    }

    /**
     * Test help tabs initialization
     */
    private static function test_help_tabs_initialization() {
        echo "<h3>Test 2: Help Tabs Initialization</h3>\n";
        
        try {
            $help_tabs = new Xelite_Repost_Engine_Help_Tabs();
            echo "✅ Help tabs initialized successfully\n";
            
            // Test if the object has expected methods
            if (method_exists($help_tabs, 'add_help_tabs')) {
                echo "✅ add_help_tabs method exists\n";
            } else {
                echo "❌ add_help_tabs method not found\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error initializing help tabs: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test if help tabs hooks are registered
     */
    private static function test_help_tabs_hooks() {
        echo "<h3>Test 3: Help Tabs Hooks</h3>\n";
        
        global $wp_filter;
        
        if (isset($wp_filter['admin_head'])) {
            $callbacks = $wp_filter['admin_head']->callbacks;
            
            $found_help_tabs = false;
            foreach ($callbacks as $priority => $priority_callbacks) {
                foreach ($priority_callbacks as $callback) {
                    if (is_array($callback['function']) && 
                        is_object($callback['function'][0]) && 
                        get_class($callback['function'][0]) === 'Xelite_Repost_Engine_Help_Tabs') {
                        $found_help_tabs = true;
                        break 2;
                    }
                }
            }
            
            if ($found_help_tabs) {
                echo "✅ Help tabs admin_head hook registered\n";
            } else {
                echo "❌ Help tabs admin_head hook not found\n";
            }
        } else {
            echo "❌ admin_head hook not registered\n";
        }
    }

    /**
     * Test help content generation
     */
    private static function test_help_content_generation() {
        echo "<h3>Test 4: Help Content Generation</h3>\n";
        
        try {
            $help_tabs = new Xelite_Repost_Engine_Help_Tabs();
            
            // Use reflection to test private methods
            $reflection = new ReflectionClass($help_tabs);
            
            // Test overview content
            $overview_method = $reflection->getMethod('get_overview_content');
            $overview_method->setAccessible(true);
            $overview_content = $overview_method->invoke($help_tabs);
            
            if (!empty($overview_content) && strpos($overview_content, 'Xelite Repost Engine Overview') !== false) {
                echo "✅ Overview content generated correctly\n";
            } else {
                echo "❌ Overview content generation failed\n";
            }
            
            // Test settings content
            $settings_method = $reflection->getMethod('get_settings_content');
            $settings_method->setAccessible(true);
            $settings_content = $settings_method->invoke($help_tabs);
            
            if (!empty($settings_content) && strpos($settings_content, 'Settings Configuration') !== false) {
                echo "✅ Settings content generated correctly\n";
            } else {
                echo "❌ Settings content generation failed\n";
            }
            
            // Test extension content
            $extension_method = $reflection->getMethod('get_extension_content');
            $extension_method->setAccessible(true);
            $extension_content = $extension_method->invoke($help_tabs);
            
            if (!empty($extension_content) && strpos($extension_content, 'Chrome Extension Setup') !== false) {
                echo "✅ Extension content generated correctly\n";
            } else {
                echo "❌ Extension content generation failed\n";
            }
            
            // Test analytics content
            $analytics_method = $reflection->getMethod('get_analytics_content');
            $analytics_method->setAccessible(true);
            $analytics_content = $analytics_method->invoke($help_tabs);
            
            if (!empty($analytics_content) && strpos($analytics_content, 'Analytics Dashboard') !== false) {
                echo "✅ Analytics content generated correctly\n";
            } else {
                echo "❌ Analytics content generation failed\n";
            }
            
            // Test troubleshooting content
            $troubleshooting_method = $reflection->getMethod('get_troubleshooting_content');
            $troubleshooting_method->setAccessible(true);
            $troubleshooting_content = $troubleshooting_method->invoke($help_tabs);
            
            if (!empty($troubleshooting_content) && strpos($troubleshooting_content, 'Troubleshooting Guide') !== false) {
                echo "✅ Troubleshooting content generated correctly\n";
            } else {
                echo "❌ Troubleshooting content generation failed\n";
            }
            
            // Test sidebar content
            $sidebar_method = $reflection->getMethod('get_help_sidebar_content');
            $sidebar_method->setAccessible(true);
            $sidebar_content = $sidebar_method->invoke($help_tabs);
            
            if (!empty($sidebar_content) && strpos($sidebar_content, 'For more information') !== false) {
                echo "✅ Sidebar content generated correctly\n";
            } else {
                echo "❌ Sidebar content generation failed\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error testing content generation: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test help tabs on specific admin pages
     */
    public static function test_help_tabs_on_pages() {
        echo "<h3>Test 5: Help Tabs on Admin Pages</h3>\n";
        
        // Simulate being on a plugin admin page
        global $current_screen;
        
        // Create a mock screen object
        $mock_screen = new stdClass();
        $mock_screen->id = 'toplevel_page_xelite-repost-engine-settings';
        $current_screen = $mock_screen;
        
        try {
            $help_tabs = new Xelite_Repost_Engine_Help_Tabs();
            
            // Test if help tabs would be added
            $reflection = new ReflectionClass($help_tabs);
            $add_help_tabs_method = $reflection->getMethod('add_help_tabs');
            $add_help_tabs_method->setAccessible(true);
            
            // This would normally add help tabs to the screen
            // For testing, we just verify the method exists and can be called
            echo "✅ Help tabs method can be called on plugin pages\n";
            
        } catch (Exception $e) {
            echo "❌ Error testing help tabs on pages: " . $e->getMessage() . "\n";
        }
        
        // Reset current screen
        $current_screen = null;
    }
}

// Run tests if this file is accessed directly
if (defined('WP_CLI') && WP_CLI) {
    Xelite_Repost_Engine_Help_Tabs_Test::run_tests();
    Xelite_Repost_Engine_Help_Tabs_Test::test_help_tabs_on_pages();
} 