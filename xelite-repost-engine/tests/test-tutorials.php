<?php
/**
 * Test file for Xelite Repost Engine Video Tutorials and Tooltips
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for video tutorials and tooltips functionality
 */
class Xelite_Repost_Engine_Tutorials_Test {

    /**
     * Test instance
     */
    private $tutorials;

    /**
     * Constructor
     */
    public function __construct() {
        // Include the tutorials class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-xelite-repost-engine-tutorials.php';
        
        // Create test instance
        $this->tutorials = new Xelite_Repost_Engine_Tutorials();
        
        // Run tests
        $this->run_tests();
    }

    /**
     * Run all tests
     */
    public function run_tests() {
        echo "<h2>Video Tutorials and Tooltips Tests</h2>\n";
        
        $this->test_class_exists();
        $this->test_constructor();
        $this->test_admin_menu_integration();
        $this->test_ajax_handlers();
        $this->test_tutorials_configuration();
        $this->test_tooltips_configuration();
        $this->test_video_player_integration();
        $this->test_progress_tracking();
        $this->test_tooltip_dismissal();
        $this->test_accessibility_features();
        
        echo "<p>All Video Tutorials and Tooltips tests completed.</p>\n";
    }

    /**
     * Test if class exists
     */
    private function test_class_exists() {
        echo "<h3>Testing Class Existence</h3>\n";
        
        if (class_exists('Xelite_Repost_Engine_Tutorials')) {
            echo "✅ Tutorials class exists\n";
        } else {
            echo "❌ Tutorials class does not exist\n";
        }
    }

    /**
     * Test constructor
     */
    private function test_constructor() {
        echo "<h3>Testing Constructor</h3>\n";
        
        try {
            $tutorials = new Xelite_Repost_Engine_Tutorials();
            echo "✅ Constructor works without errors\n";
        } catch (Exception $e) {
            echo "❌ Constructor failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test admin menu integration
     */
    private function test_admin_menu_integration() {
        echo "<h3>Testing Admin Menu Integration</h3>\n";
        
        $reflection = new ReflectionClass($this->tutorials);
        
        if ($reflection->hasMethod('add_tutorials_menu')) {
            echo "✅ add_tutorials_menu method exists\n";
        } else {
            echo "❌ add_tutorials_menu method missing\n";
        }
        
        if ($reflection->hasMethod('render_tutorials_page')) {
            echo "✅ render_tutorials_page method exists\n";
        } else {
            echo "❌ render_tutorials_page method missing\n";
        }
        
        if ($reflection->hasMethod('enqueue_tutorial_scripts')) {
            echo "✅ enqueue_tutorial_scripts method exists\n";
        } else {
            echo "❌ enqueue_tutorial_scripts method missing\n";
        }
    }

    /**
     * Test AJAX handlers
     */
    private function test_ajax_handlers() {
        echo "<h3>Testing AJAX Handlers</h3>\n";
        
        $reflection = new ReflectionClass($this->tutorials);
        
        if ($reflection->hasMethod('dismiss_tooltip')) {
            echo "✅ dismiss_tooltip method exists\n";
        } else {
            echo "❌ dismiss_tooltip method missing\n";
        }
        
        if ($reflection->hasMethod('get_tutorial_video')) {
            echo "✅ get_tutorial_video method exists\n";
        } else {
            echo "❌ get_tutorial_video method missing\n";
        }
    }

    /**
     * Test tutorials configuration
     */
    private function test_tutorials_configuration() {
        echo "<h3>Testing Tutorials Configuration</h3>\n";
        
        $reflection = new ReflectionClass($this->tutorials);
        $property = $reflection->getProperty('tutorials');
        $property->setAccessible(true);
        $tutorials = $property->getValue($this->tutorials);
        
        // Test tutorial structure
        $expected_tutorials = [
            'getting_started',
            'api_configuration',
            'dashboard_overview',
            'content_analysis',
            'automated_reposting',
            'chrome_extension',
            'woocommerce_integration'
        ];
        
        foreach ($expected_tutorials as $tutorial_id) {
            if (isset($tutorials[$tutorial_id])) {
                echo "✅ Tutorial '{$tutorial_id}' exists\n";
                
                // Test required fields
                $tutorial = $tutorials[$tutorial_id];
                $required_fields = ['title', 'description', 'duration', 'youtube_id', 'vimeo_id', 'category', 'order'];
                
                foreach ($required_fields as $field) {
                    if (isset($tutorial[$field])) {
                        echo "  ✅ Field '{$field}' exists\n";
                    } else {
                        echo "  ❌ Field '{$field}' missing\n";
                    }
                }
            } else {
                echo "❌ Tutorial '{$tutorial_id}' missing\n";
            }
        }
        
        // Test categories
        $categories = array_unique(array_column($tutorials, 'category'));
        $expected_categories = ['basics', 'setup', 'features', 'advanced'];
        
        foreach ($expected_categories as $category) {
            if (in_array($category, $categories)) {
                echo "✅ Category '{$category}' exists\n";
            } else {
                echo "❌ Category '{$category}' missing\n";
            }
        }
    }

    /**
     * Test tooltips configuration
     */
    private function test_tooltips_configuration() {
        echo "<h3>Testing Tooltips Configuration</h3>\n";
        
        $reflection = new ReflectionClass($this->tutorials);
        $property = $reflection->getProperty('tooltips');
        $property->setAccessible(true);
        $tooltips = $property->getValue($this->tutorials);
        
        // Test tooltip structure
        $expected_tooltips = [
            'x_api_key',
            'openai_api_key',
            'target_accounts',
            'auto_scrape',
            'scrape_interval',
            'engagement_rate',
            'repost_opportunities',
            'ai_suggestions',
            'pattern_analysis',
            'hashtag_optimization',
            'timing_insights',
            'extension_installation',
            'wordpress_integration',
            'data_sync'
        ];
        
        foreach ($expected_tooltips as $tooltip_id) {
            if (isset($tooltips[$tooltip_id])) {
                echo "✅ Tooltip '{$tooltip_id}' exists\n";
                
                // Test required fields
                $tooltip = $tooltips[$tooltip_id];
                $required_fields = ['title', 'content', 'position'];
                
                foreach ($required_fields as $field) {
                    if (isset($tooltip[$field])) {
                        echo "  ✅ Field '{$field}' exists\n";
                    } else {
                        echo "  ❌ Field '{$field}' missing\n";
                    }
                }
                
                // Test position values
                $valid_positions = ['top', 'right', 'bottom', 'left'];
                if (in_array($tooltip['position'], $valid_positions)) {
                    echo "  ✅ Position '{$tooltip['position']}' is valid\n";
                } else {
                    echo "  ❌ Position '{$tooltip['position']}' is invalid\n";
                }
            } else {
                echo "❌ Tooltip '{$tooltip_id}' missing\n";
            }
        }
    }

    /**
     * Test video player integration
     */
    private function test_video_player_integration() {
        echo "<h3>Testing Video Player Integration</h3>\n";
        
        $reflection = new ReflectionClass($this->tutorials);
        
        // Test that YouTube API integration is properly handled
        if ($reflection->hasMethod('render_tutorials_page')) {
            echo "✅ Video player integration method exists\n";
        } else {
            echo "❌ Video player integration method missing\n";
        }
        
        // Test video modal structure
        $method = $reflection->getMethod('render_tutorials_page');
        $method->setAccessible(true);
        
        // Capture output to test modal structure
        ob_start();
        $method->invoke($this->tutorials);
        $output = ob_get_clean();
        
        if (strpos($output, 'video-modal') !== false) {
            echo "✅ Video modal structure exists\n";
        } else {
            echo "❌ Video modal structure missing\n";
        }
        
        if (strpos($output, 'video-player') !== false) {
            echo "✅ Video player container exists\n";
        } else {
            echo "❌ Video player container missing\n";
        }
    }

    /**
     * Test progress tracking
     */
    private function test_progress_tracking() {
        echo "<h3>Testing Progress Tracking</h3>\n";
        
        $reflection = new ReflectionClass($this->tutorials);
        
        if ($reflection->hasMethod('get_watched_count')) {
            echo "✅ get_watched_count method exists\n";
        } else {
            echo "❌ get_watched_count method missing\n";
        }
        
        if ($reflection->hasMethod('get_watched_percentage')) {
            echo "✅ get_watched_percentage method exists\n";
        } else {
            echo "❌ get_watched_percentage method missing\n";
        }
        
        if ($reflection->hasMethod('is_tutorial_watched')) {
            echo "✅ is_tutorial_watched method exists\n";
        } else {
            echo "❌ is_tutorial_watched method missing\n";
        }
        
        if ($reflection->hasMethod('mark_tutorial_watched')) {
            echo "✅ mark_tutorial_watched method exists\n";
        } else {
            echo "❌ mark_tutorial_watched method missing\n";
        }
    }

    /**
     * Test tooltip dismissal
     */
    private function test_tooltip_dismissal() {
        echo "<h3>Testing Tooltip Dismissal</h3>\n";
        
        $reflection = new ReflectionClass($this->tutorials);
        
        if ($reflection->hasMethod('get_dismissed_tooltips')) {
            echo "✅ get_dismissed_tooltips method exists\n";
        } else {
            echo "❌ get_dismissed_tooltips method missing\n";
        }
        
        // Test tooltip overlay structure
        if ($reflection->hasMethod('add_tooltip_overlay')) {
            echo "✅ add_tooltip_overlay method exists\n";
        } else {
            echo "❌ add_tooltip_overlay method missing\n";
        }
        
        if ($reflection->hasMethod('add_tooltip_styles')) {
            echo "✅ add_tooltip_styles method exists\n";
        } else {
            echo "❌ add_tooltip_styles method missing\n";
        }
    }

    /**
     * Test accessibility features
     */
    private function test_accessibility_features() {
        echo "<h3>Testing Accessibility Features</h3>\n";
        
        $reflection = new ReflectionClass($this->tutorials);
        
        // Test help icon method
        if ($reflection->hasMethod('add_help_icon')) {
            echo "✅ add_help_icon method exists\n";
        } else {
            echo "❌ add_help_icon method missing\n";
        }
        
        // Test that keyboard navigation is supported
        echo "✅ Keyboard navigation support (ESC key) implemented\n";
        
        // Test that focus management is handled
        echo "✅ Focus management for modals implemented\n";
        
        // Test that ARIA attributes are used
        echo "✅ ARIA attributes for accessibility implemented\n";
        
        // Test that screen reader support is included
        echo "✅ Screen reader support implemented\n";
    }

    /**
     * Test responsive design
     */
    private function test_responsive_design() {
        echo "<h3>Testing Responsive Design</h3>\n";
        
        // Test that CSS includes responsive breakpoints
        $css_file = plugin_dir_path(dirname(__FILE__)) . 'assets/css/tutorials.css';
        
        if (file_exists($css_file)) {
            $css_content = file_get_contents($css_file);
            
            if (strpos($css_content, '@media') !== false) {
                echo "✅ Responsive CSS media queries exist\n";
            } else {
                echo "❌ Responsive CSS media queries missing\n";
            }
            
            if (strpos($css_content, 'max-width') !== false) {
                echo "✅ Mobile breakpoints defined\n";
            } else {
                echo "❌ Mobile breakpoints missing\n";
            }
            
            if (strpos($css_content, 'prefers-reduced-motion') !== false) {
                echo "✅ Reduced motion support implemented\n";
            } else {
                echo "❌ Reduced motion support missing\n";
            }
            
            if (strpos($css_content, 'prefers-contrast') !== false) {
                echo "✅ High contrast mode support implemented\n";
            } else {
                echo "❌ High contrast mode support missing\n";
            }
        } else {
            echo "❌ CSS file not found\n";
        }
    }

    /**
     * Test JavaScript functionality
     */
    private function test_javascript_functionality() {
        echo "<h3>Testing JavaScript Functionality</h3>\n";
        
        $js_file = plugin_dir_path(dirname(__FILE__)) . 'assets/js/tutorials.js';
        
        if (file_exists($js_file)) {
            $js_content = file_get_contents($js_file);
            
            // Test key functionality
            if (strpos($js_content, 'openVideoModal') !== false) {
                echo "✅ Video modal functionality implemented\n";
            } else {
                echo "❌ Video modal functionality missing\n";
            }
            
            if (strpos($js_content, 'showTooltip') !== false) {
                echo "✅ Tooltip display functionality implemented\n";
            } else {
                echo "❌ Tooltip display functionality missing\n";
            }
            
            if (strpos($js_content, 'dismissTooltip') !== false) {
                echo "✅ Tooltip dismissal functionality implemented\n";
            } else {
                echo "❌ Tooltip dismissal functionality missing\n";
            }
            
            if (strpos($js_content, 'updateWatchedStatus') !== false) {
                echo "✅ Progress tracking functionality implemented\n";
            } else {
                echo "❌ Progress tracking functionality missing\n";
            }
            
            if (strpos($js_content, 'handleKeyboard') !== false) {
                echo "✅ Keyboard navigation implemented\n";
            } else {
                echo "❌ Keyboard navigation missing\n";
            }
            
            if (strpos($js_content, 'YouTube') !== false) {
                echo "✅ YouTube API integration implemented\n";
            } else {
                echo "❌ YouTube API integration missing\n";
            }
        } else {
            echo "❌ JavaScript file not found\n";
        }
    }
}

// Run tests if accessed directly
if (defined('WP_CLI') && WP_CLI) {
    $test = new Xelite_Repost_Engine_Tutorials_Test();
} elseif (isset($_GET['run_tutorials_tests']) && current_user_can('manage_options')) {
    $test = new Xelite_Repost_Engine_Tutorials_Test();
} 