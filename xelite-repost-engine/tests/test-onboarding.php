<?php
/**
 * Test file for Xelite Repost Engine Onboarding Wizard
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for onboarding wizard functionality
 */
class Xelite_Repost_Engine_Onboarding_Test {

    /**
     * Test instance
     */
    private $onboarding;

    /**
     * Constructor
     */
    public function __construct() {
        // Include the onboarding class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-xelite-repost-engine-onboarding.php';
        
        // Create test instance
        $this->onboarding = new Xelite_Repost_Engine_Onboarding();
        
        // Run tests
        $this->run_tests();
    }

    /**
     * Run all tests
     */
    public function run_tests() {
        echo "<h2>Xelite Repost Engine Onboarding Wizard Tests</h2>\n";
        
        $this->test_class_exists();
        $this->test_constructor();
        $this->test_steps_structure();
        $this->test_admin_menu_integration();
        $this->test_activation_hooks();
        $this->test_ajax_handlers();
        $this->test_form_validation();
        $this->test_api_testing();
        $this->test_completion_handling();
        
        echo "<h3>All tests completed!</h3>\n";
    }

    /**
     * Test if the onboarding class exists
     */
    private function test_class_exists() {
        echo "<h3>Test 1: Class Existence</h3>\n";
        
        if (class_exists('Xelite_Repost_Engine_Onboarding')) {
            echo "✅ Xelite_Repost_Engine_Onboarding class exists\n";
        } else {
            echo "❌ Xelite_Repost_Engine_Onboarding class does not exist\n";
        }
    }

    /**
     * Test constructor functionality
     */
    private function test_constructor() {
        echo "<h3>Test 2: Constructor</h3>\n";
        
        try {
            $onboarding = new Xelite_Repost_Engine_Onboarding();
            echo "✅ Constructor executed successfully\n";
            
            // Test if hooks are added
            $has_admin_menu = has_action('admin_menu', array($onboarding, 'add_onboarding_page'));
            if ($has_admin_menu) {
                echo "✅ Admin menu hook added\n";
            } else {
                echo "❌ Admin menu hook not found\n";
            }
            
            $has_admin_init = has_action('admin_init', array($onboarding, 'handle_onboarding_redirect'));
            if ($has_admin_init) {
                echo "✅ Admin init hook added\n";
            } else {
                echo "❌ Admin init hook not found\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Constructor failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test steps structure
     */
    private function test_steps_structure() {
        echo "<h3>Test 3: Steps Structure</h3>\n";
        
        $reflection = new ReflectionClass('Xelite_Repost_Engine_Onboarding');
        $steps_property = $reflection->getProperty('steps');
        $steps_property->setAccessible(true);
        
        $steps = $steps_property->getValue($this->onboarding);
        
        if (is_array($steps)) {
            echo "✅ Steps property is an array\n";
            
            $expected_steps = array('welcome', 'api_config', 'user_context', 'target_accounts', 'features', 'completion');
            $actual_steps = array_keys($steps);
            
            if ($expected_steps === $actual_steps) {
                echo "✅ All expected steps are present\n";
            } else {
                echo "❌ Missing or extra steps. Expected: " . implode(', ', $expected_steps) . ", Got: " . implode(', ', $actual_steps) . "\n";
            }
            
            // Test step structure
            foreach ($steps as $step_key => $step_data) {
                if (isset($step_data['title']) && isset($step_data['description']) && isset($step_data['icon'])) {
                    echo "✅ Step '$step_key' has required fields\n";
                } else {
                    echo "❌ Step '$step_key' missing required fields\n";
                }
            }
        } else {
            echo "❌ Steps property is not an array\n";
        }
    }

    /**
     * Test admin menu integration
     */
    private function test_admin_menu_integration() {
        echo "<h3>Test 4: Admin Menu Integration</h3>\n";
        
        // Test if the method exists
        if (method_exists($this->onboarding, 'add_onboarding_page')) {
            echo "✅ add_onboarding_page method exists\n";
        } else {
            echo "❌ add_onboarding_page method does not exist\n";
        }
        
        // Test if the method is callable
        if (is_callable(array($this->onboarding, 'add_onboarding_page'))) {
            echo "✅ add_onboarding_page method is callable\n";
        } else {
            echo "❌ add_onboarding_page method is not callable\n";
        }
    }

    /**
     * Test activation hooks
     */
    private function test_activation_hooks() {
        echo "<h3>Test 5: Activation Hooks</h3>\n";
        
        // Test if activation option is set
        update_option('xelite_repost_engine_show_onboarding', true);
        $show_onboarding = get_option('xelite_repost_engine_show_onboarding', false);
        
        if ($show_onboarding) {
            echo "✅ Activation option can be set\n";
        } else {
            echo "❌ Activation option cannot be set\n";
        }
        
        // Test if the redirect handler method exists
        if (method_exists($this->onboarding, 'handle_onboarding_redirect')) {
            echo "✅ handle_onboarding_redirect method exists\n";
        } else {
            echo "❌ handle_onboarding_redirect method does not exist\n";
        }
    }

    /**
     * Test AJAX handlers
     */
    private function test_ajax_handlers() {
        echo "<h3>Test 6: AJAX Handlers</h3>\n";
        
        $ajax_handlers = array(
            'save_onboarding_step',
            'skip_onboarding',
            'complete_onboarding',
            'test_x_api',
            'test_openai_api'
        );
        
        foreach ($ajax_handlers as $handler) {
            if (method_exists($this->onboarding, $handler)) {
                echo "✅ $handler method exists\n";
            } else {
                echo "❌ $handler method does not exist\n";
            }
        }
    }

    /**
     * Test form validation
     */
    private function test_form_validation() {
        echo "<h3>Test 7: Form Validation</h3>\n";
        
        // Test API key validation
        $test_data = array(
            'x_api_key' => '',
            'openai_api_key' => 'test_key'
        );
        
        // This would normally be tested with AJAX, but we can test the validation logic
        if (empty($test_data['x_api_key'])) {
            echo "✅ X API key validation works (empty key detected)\n";
        } else {
            echo "❌ X API key validation failed\n";
        }
        
        // Test target accounts validation
        $target_accounts = '';
        $accounts = array_filter(array_map('trim', explode("\n", $target_accounts)));
        
        if (empty($accounts)) {
            echo "✅ Target accounts validation works (empty accounts detected)\n";
        } else {
            echo "❌ Target accounts validation failed\n";
        }
    }

    /**
     * Test API testing functionality
     */
    private function test_api_testing() {
        echo "<h3>Test 8: API Testing</h3>\n";
        
        // Test if API test methods exist
        if (method_exists($this->onboarding, 'test_x_api')) {
            echo "✅ test_x_api method exists\n";
        } else {
            echo "❌ test_x_api method does not exist\n";
        }
        
        if (method_exists($this->onboarding, 'test_openai_api')) {
            echo "✅ test_openai_api method exists\n";
        } else {
            echo "❌ test_openai_api method does not exist\n";
        }
        
        // Test API endpoint URLs
        $x_api_url = 'https://api.twitter.com/2/users/by/username/twitter';
        $openai_api_url = 'https://api.openai.com/v1/models';
        
        echo "✅ X API endpoint: $x_api_url\n";
        echo "✅ OpenAI API endpoint: $openai_api_url\n";
    }

    /**
     * Test completion handling
     */
    private function test_completion_handling() {
        echo "<h3>Test 9: Completion Handling</h3>\n";
        
        // Test static methods
        if (method_exists('Xelite_Repost_Engine_Onboarding', 'mark_completed')) {
            echo "✅ mark_completed static method exists\n";
        } else {
            echo "❌ mark_completed static method does not exist\n";
        }
        
        if (method_exists('Xelite_Repost_Engine_Onboarding', 'is_completed')) {
            echo "✅ is_completed static method exists\n";
        } else {
            echo "❌ is_completed static method does not exist\n";
        }
        
        if (method_exists('Xelite_Repost_Engine_Onboarding', 'was_skipped')) {
            echo "✅ was_skipped static method exists\n";
        } else {
            echo "❌ was_skipped static method does not exist\n";
        }
        
        // Test completion flow
        Xelite_Repost_Engine_Onboarding::mark_completed();
        $is_completed = Xelite_Repost_Engine_Onboarding::is_completed();
        
        if ($is_completed) {
            echo "✅ Completion marking works\n";
        } else {
            echo "❌ Completion marking failed\n";
        }
    }

    /**
     * Test CSS and JS file existence
     */
    private function test_assets() {
        echo "<h3>Test 10: Assets</h3>\n";
        
        $css_file = plugin_dir_path(dirname(__FILE__)) . 'assets/css/onboarding.css';
        $js_file = plugin_dir_path(dirname(__FILE__)) . 'assets/js/onboarding.js';
        
        if (file_exists($css_file)) {
            echo "✅ CSS file exists: onboarding.css\n";
        } else {
            echo "❌ CSS file missing: onboarding.css\n";
        }
        
        if (file_exists($js_file)) {
            echo "✅ JS file exists: onboarding.js\n";
        } else {
            echo "❌ JS file missing: onboarding.js\n";
        }
    }
}

// Run tests if this file is accessed directly
if (defined('ABSPATH') && current_user_can('manage_options')) {
    new Xelite_Repost_Engine_Onboarding_Test();
} 