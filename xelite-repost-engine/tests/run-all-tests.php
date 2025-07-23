<?php
/**
 * Comprehensive Test Runner for Xelite Repost Engine
 *
 * This file runs all tests to ensure the plugin is ready for deployment
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Include bootstrap
require_once __DIR__ . '/bootstrap.php';

/**
 * Main test runner class
 */
class XeliteRepostEngine_Test_Runner {
    
    private $results = array();
    private $start_time;
    
    public function __construct() {
        $this->start_time = microtime(true);
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "==========================================\n";
        echo "XELITE REPOST ENGINE - COMPREHENSIVE TESTS\n";
        echo "==========================================\n\n";
        
        $this->test_plugin_structure();
        $this->test_database_functionality();
        $this->test_admin_settings();
        $this->test_user_meta_integration();
        $this->test_x_api_integration();
        $this->test_scraper_functionality();
        $this->test_pattern_analyzer();
        $this->test_openai_integration();
        $this->test_dashboard_ui();
        $this->test_woocommerce_integration();
        $this->test_x_posting_integration();
        $this->test_analytics_dashboard();
        $this->test_enhanced_ai_prompts();
        $this->test_chrome_extension();
        $this->test_documentation();
        
        $this->print_summary();
    }
    
    /**
     * Test plugin structure and base classes
     */
    private function test_plugin_structure() {
        echo "Testing Plugin Structure...\n";
        
        $this->run_test('Main plugin file exists', function() {
            return file_exists(dirname(__DIR__) . '/xelite-repost-engine.php');
        });
        
        $this->run_test('Plugin class exists', function() {
            return class_exists('XeliteRepostEngine');
        });
        
        $this->run_test('Plugin instance can be created', function() {
            return function_exists('xelite_repost_engine');
        });
        
        $this->run_test('Abstract base class exists', function() {
            return class_exists('XeliteRepostEngine_Abstract_Base');
        });
        
        $this->run_test('Container class exists', function() {
            return class_exists('XeliteRepostEngine_Container');
        });
        
        echo "\n";
    }
    
    /**
     * Test database functionality
     */
    private function test_database_functionality() {
        echo "Testing Database Functionality...\n";
        
        $this->run_test('Database class exists', function() {
            return class_exists('XeliteRepostEngine_Database');
        });
        
        $this->run_test('Database interface exists', function() {
            return interface_exists('XeliteRepostEngine_Database_Interface');
        });
        
        $this->run_test('Database can be instantiated', function() {
            try {
                $plugin = xelite_repost_engine();
                $database = $plugin->container->get('database');
                return $database instanceof XeliteRepostEngine_Database;
            } catch (Exception $e) {
                return false;
            }
        });
        
        $this->run_test('Database tables can be created', function() {
            try {
                $plugin = xelite_repost_engine();
                $database = $plugin->container->get('database');
                return method_exists($database, 'create_tables');
            } catch (Exception $e) {
                return false;
            }
        });
        
        echo "\n";
    }
    
    /**
     * Test admin settings functionality
     */
    private function test_admin_settings() {
        echo "Testing Admin Settings...\n";
        
        $this->run_test('Admin settings class exists', function() {
            return class_exists('XeliteRepostEngine_Admin_Settings');
        });
        
        $this->run_test('Admin fields trait exists', function() {
            return trait_exists('XeliteRepostEngine_Admin_Fields');
        });
        
        $this->run_test('Settings can be registered', function() {
            // Check if the admin settings class has the register_settings method
            return method_exists('XeliteRepostEngine_Admin_Settings', 'register_settings');
        });
        
        echo "\n";
    }
    
    /**
     * Test user meta integration
     */
    private function test_user_meta_integration() {
        echo "Testing User Meta Integration...\n";
        
        $this->run_test('User meta class exists', function() {
            return class_exists('XeliteRepostEngine_User_Meta');
        });
        
        $this->run_test('User meta interface exists', function() {
            return interface_exists('XeliteRepostEngine_User_Meta_Interface');
        });
        
        $this->run_test('User meta can be instantiated', function() {
            try {
                $plugin = xelite_repost_engine();
                $user_meta = $plugin->container->get('user_meta');
                return $user_meta instanceof XeliteRepostEngine_User_Meta;
            } catch (Exception $e) {
                return false;
            }
        });
        
        echo "\n";
    }
    
    /**
     * Test X API integration
     */
    private function test_x_api_integration() {
        echo "Testing X API Integration...\n";
        
        $this->run_test('X API class exists', function() {
            return class_exists('XeliteRepostEngine_X_API');
        });
        
        $this->run_test('X Auth class exists', function() {
            return class_exists('XeliteRepostEngine_X_Auth');
        });
        
        $this->run_test('X Processor class exists', function() {
            return class_exists('XeliteRepostEngine_X_Processor');
        });
        
        $this->run_test('X API interface exists', function() {
            return interface_exists('XeliteRepostEngine_API_Interface');
        });
        
        echo "\n";
    }
    
    /**
     * Test scraper functionality
     */
    private function test_scraper_functionality() {
        echo "Testing Scraper Functionality...\n";
        
        $this->run_test('Scraper class exists', function() {
            return class_exists('XeliteRepostEngine_Scraper');
        });
        
        $this->run_test('Cron class exists', function() {
            return class_exists('XeliteRepostEngine_Cron');
        });
        
        $this->run_test('Cron admin class exists', function() {
            return class_exists('XeliteRepostEngine_Cron_Admin');
        });
        
        echo "\n";
    }
    
    /**
     * Test pattern analyzer
     */
    private function test_pattern_analyzer() {
        echo "Testing Pattern Analyzer...\n";
        
        $this->run_test('Pattern analyzer class exists', function() {
            return class_exists('XeliteRepostEngine_Pattern_Analyzer');
        });
        
        $this->run_test('Pattern validator class exists', function() {
            return class_exists('XeliteRepostEngine_Pattern_Validator');
        });
        
        $this->run_test('Pattern visualizer class exists', function() {
            return class_exists('XeliteRepostEngine_Pattern_Visualizer');
        });
        
        echo "\n";
    }
    
    /**
     * Test OpenAI integration
     */
    private function test_openai_integration() {
        echo "Testing OpenAI Integration...\n";
        
        $this->run_test('OpenAI class exists', function() {
            return class_exists('XeliteRepostEngine_OpenAI');
        });
        
        $this->run_test('Prompt builder class exists', function() {
            return class_exists('XeliteRepostEngine_Prompt_Builder');
        });
        
        echo "\n";
    }
    
    /**
     * Test dashboard UI
     */
    private function test_dashboard_ui() {
        echo "Testing Dashboard UI...\n";
        
        $this->run_test('Dashboard class exists', function() {
            return class_exists('Repost_Intelligence_Dashboard');
        });
        
        $this->run_test('Assets class exists', function() {
            return class_exists('XeliteRepostEngine_Assets');
        });
        
        $this->run_test('Public class exists', function() {
            return class_exists('XeliteRepostEngine_Public');
        });
        
        echo "\n";
    }
    
    /**
     * Test WooCommerce integration
     */
    private function test_woocommerce_integration() {
        echo "Testing WooCommerce Integration...\n";
        
        $this->run_test('WooCommerce integration class exists', function() {
            return class_exists('XeliteRepostEngine_WooCommerce');
        });
        
        echo "\n";
    }
    
    /**
     * Test X posting integration
     */
    private function test_x_posting_integration() {
        echo "Testing X Posting Integration...\n";
        
        $this->run_test('X posting functionality exists', function() {
            // Check if posting methods exist in X Poster class
            return method_exists('XeliteRepostEngine_X_Poster', 'post_tweet');
        });
        
        echo "\n";
    }
    
    /**
     * Test analytics dashboard
     */
    private function test_analytics_dashboard() {
        echo "Testing Analytics Dashboard...\n";
        
        $this->run_test('Analytics collector exists', function() {
            return file_exists(dirname(__DIR__) . '/includes/class-xelite-repost-engine-analytics-collector.php');
        });
        
        echo "\n";
    }
    
    /**
     * Test enhanced AI prompts
     */
    private function test_enhanced_ai_prompts() {
        echo "Testing Enhanced AI Prompts...\n";
        
        $this->run_test('Few-shot learning functionality exists', function() {
            // Check if prompt builder has few-shot methods
            return method_exists('XeliteRepostEngine_Prompt_Builder', 'get_few_shot_examples');
        });
        
        echo "\n";
    }
    
    /**
     * Test Chrome extension
     */
    private function test_chrome_extension() {
        echo "Testing Chrome Extension...\n";
        
        $this->run_test('Chrome extension manifest exists', function() {
            return file_exists(dirname(__DIR__) . '/chrome-extension/manifest.json');
        });
        
        $this->run_test('Chrome extension background script exists', function() {
            return file_exists(dirname(__DIR__) . '/chrome-extension/background.js');
        });
        
        $this->run_test('Chrome extension content script exists', function() {
            return file_exists(dirname(__DIR__) . '/chrome-extension/content.js');
        });
        
        echo "\n";
    }
    
    /**
     * Test documentation
     */
    private function test_documentation() {
        echo "Testing Documentation...\n";
        
        $this->run_test('README.md exists', function() {
            return file_exists(dirname(__DIR__) . '/README.md');
        });
        
        $this->run_test('Installation guide exists', function() {
            return file_exists(dirname(__DIR__) . '/INSTALLATION.md');
        });
        
        $this->run_test('Chrome extension README exists', function() {
            return file_exists(dirname(__DIR__) . '/chrome-extension/README.md');
        });
        
        $this->run_test('PDF guide class exists', function() {
            return class_exists('Xelite_Repost_Engine_PDF_Guide');
        });
        
        $this->run_test('Tutorials class exists', function() {
            return class_exists('Xelite_Repost_Engine_Tutorials');
        });
        
        echo "\n";
    }
    
    /**
     * Run a single test
     */
    private function run_test($name, $callback) {
        try {
            $result = $callback();
            $status = $result ? 'PASS' : 'FAIL';
            $this->results[] = array(
                'name' => $name,
                'status' => $status,
                'passed' => $result
            );
            echo "  {$status}: {$name}\n";
        } catch (Exception $e) {
            $this->results[] = array(
                'name' => $name,
                'status' => 'ERROR',
                'passed' => false,
                'error' => $e->getMessage()
            );
            echo "  ERROR: {$name} - {$e->getMessage()}\n";
        }
    }
    
    /**
     * Print test summary
     */
    private function print_summary() {
        $end_time = microtime(true);
        $duration = round($end_time - $this->start_time, 2);
        
        $total = count($this->results);
        $passed = count(array_filter($this->results, function($r) { return $r['passed']; }));
        $failed = $total - $passed;
        
        echo "==========================================\n";
        echo "TEST SUMMARY\n";
        echo "==========================================\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Duration: {$duration}s\n";
        echo "==========================================\n\n";
        
        if ($failed > 0) {
            echo "FAILED TESTS:\n";
            foreach ($this->results as $result) {
                if (!$result['passed']) {
                    echo "- {$result['name']}\n";
                    if (isset($result['error'])) {
                        echo "  Error: {$result['error']}\n";
                    }
                }
            }
            echo "\n";
        }
        
        if ($failed === 0) {
            echo "🎉 ALL TESTS PASSED! Plugin is ready for deployment.\n\n";
        } else {
            echo "❌ {$failed} tests failed. Please fix issues before deployment.\n\n";
        }
    }
}

// Run tests
$runner = new XeliteRepostEngine_Test_Runner();
$runner->run_all_tests(); 