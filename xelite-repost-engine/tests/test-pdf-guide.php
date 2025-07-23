<?php
/**
 * Test file for Xelite Repost Engine PDF Guide
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for PDF guide functionality
 */
class Xelite_Repost_Engine_PDF_Guide_Test {

    /**
     * Test instance
     */
    private $pdf_guide;

    /**
     * Constructor
     */
    public function __construct() {
        // Include the PDF guide class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-xelite-repost-engine-pdf-guide.php';
        
        // Create test instance
        $this->pdf_guide = new Xelite_Repost_Engine_PDF_Guide();
        
        // Run tests
        $this->run_tests();
    }

    /**
     * Run all tests
     */
    public function run_tests() {
        echo "<h2>PDF Guide Tests</h2>\n";
        
        $this->test_class_exists();
        $this->test_constructor();
        $this->test_admin_menu_integration();
        $this->test_ajax_handlers();
        $this->test_pdf_generation();
        $this->test_html_content_generation();
        $this->test_content_sections();
        $this->test_options_handling();
        
        echo "<p>All PDF Guide tests completed.</p>\n";
    }

    /**
     * Test if class exists
     */
    private function test_class_exists() {
        echo "<h3>Testing Class Existence</h3>\n";
        
        if (class_exists('Xelite_Repost_Engine_PDF_Guide')) {
            echo "✅ PDF Guide class exists\n";
        } else {
            echo "❌ PDF Guide class does not exist\n";
        }
    }

    /**
     * Test constructor
     */
    private function test_constructor() {
        echo "<h3>Testing Constructor</h3>\n";
        
        try {
            $pdf_guide = new Xelite_Repost_Engine_PDF_Guide();
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
        
        $reflection = new ReflectionClass($this->pdf_guide);
        
        if ($reflection->hasMethod('add_pdf_menu')) {
            echo "✅ add_pdf_menu method exists\n";
        } else {
            echo "❌ add_pdf_menu method missing\n";
        }
        
        if ($reflection->hasMethod('render_pdf_page')) {
            echo "✅ render_pdf_page method exists\n";
        } else {
            echo "❌ render_pdf_page method missing\n";
        }
    }

    /**
     * Test AJAX handlers
     */
    private function test_ajax_handlers() {
        echo "<h3>Testing AJAX Handlers</h3>\n";
        
        $reflection = new ReflectionClass($this->pdf_guide);
        
        if ($reflection->hasMethod('generate_and_download_pdf')) {
            echo "✅ generate_and_download_pdf method exists\n";
        } else {
            echo "❌ generate_and_download_pdf method missing\n";
        }
        
        if ($reflection->hasMethod('enqueue_pdf_scripts')) {
            echo "✅ enqueue_pdf_scripts method exists\n";
        } else {
            echo "❌ enqueue_pdf_scripts method missing\n";
        }
    }

    /**
     * Test PDF generation
     */
    private function test_pdf_generation() {
        echo "<h3>Testing PDF Generation</h3>\n";
        
        $reflection = new ReflectionClass($this->pdf_guide);
        
        if ($reflection->hasMethod('generate_pdf_content')) {
            echo "✅ generate_pdf_content method exists\n";
        } else {
            echo "❌ generate_pdf_content method missing\n";
        }
        
        if ($reflection->hasMethod('generate_html_content')) {
            echo "✅ generate_html_content method exists\n";
        } else {
            echo "❌ generate_html_content method missing\n";
        }
        
        if ($reflection->hasMethod('html_to_pdf')) {
            echo "✅ html_to_pdf method exists\n";
        } else {
            echo "❌ html_to_pdf method missing\n";
        }
    }

    /**
     * Test HTML content generation
     */
    private function test_html_content_generation() {
        echo "<h3>Testing HTML Content Generation</h3>\n";
        
        $reflection = new ReflectionClass($this->pdf_guide);
        
        // Test content section methods
        $content_methods = [
            'generate_cover_page',
            'generate_table_of_contents',
            'generate_introduction_section',
            'generate_installation_section',
            'generate_configuration_section',
            'generate_dashboard_section',
            'generate_content_analysis_section',
            'generate_automated_reposting_section',
            'generate_chrome_extension_section',
            'generate_advanced_features_section',
            'generate_troubleshooting_section',
            'generate_api_reference_section',
            'generate_case_studies_section',
            'generate_appendices_section'
        ];
        
        foreach ($content_methods as $method) {
            if ($reflection->hasMethod($method)) {
                echo "✅ {$method} method exists\n";
            } else {
                echo "❌ {$method} method missing\n";
            }
        }
    }

    /**
     * Test content sections
     */
    private function test_content_sections() {
        echo "<h3>Testing Content Sections</h3>\n";
        
        // Test that content sections generate valid HTML
        $reflection = new ReflectionClass($this->pdf_guide);
        
        try {
            $method = $reflection->getMethod('generate_cover_page');
            $method->setAccessible(true);
            $content = $method->invoke($this->pdf_guide);
            
            if (strpos($content, '<h1') !== false && strpos($content, 'Xelite Repost Engine') !== false) {
                echo "✅ Cover page generates valid content\n";
            } else {
                echo "❌ Cover page content is invalid\n";
            }
        } catch (Exception $e) {
            echo "❌ Cover page generation failed: " . $e->getMessage() . "\n";
        }
        
        try {
            $method = $reflection->getMethod('generate_table_of_contents');
            $method->setAccessible(true);
            $content = $method->invoke($this->pdf_guide);
            
            if (strpos($content, 'Table of Contents') !== false && strpos($content, '<ul>') !== false) {
                echo "✅ Table of contents generates valid content\n";
            } else {
                echo "❌ Table of contents content is invalid\n";
            }
        } catch (Exception $e) {
            echo "❌ Table of contents generation failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test options handling
     */
    private function test_options_handling() {
        echo "<h3>Testing Options Handling</h3>\n";
        
        // Test default options
        $default_options = [
            'language' => 'en',
            'include_screenshots' => true,
            'include_code' => true,
            'include_troubleshooting' => true
        ];
        
        $reflection = new ReflectionClass($this->pdf_guide);
        
        try {
            $method = $reflection->getMethod('generate_html_content');
            $method->setAccessible(true);
            $content = $method->invoke($this->pdf_guide, $default_options);
            
            if (strpos($content, '<html') !== false && strpos($content, '<body>') !== false) {
                echo "✅ HTML content generation works with default options\n";
            } else {
                echo "❌ HTML content generation failed with default options\n";
            }
        } catch (Exception $e) {
            echo "❌ HTML content generation failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test PDF file generation
     */
    private function test_pdf_file_generation() {
        echo "<h3>Testing PDF File Generation</h3>\n";
        
        // Test that the system can handle PDF generation requests
        $test_options = [
            'language' => 'en',
            'include_screenshots' => false,
            'include_code' => true,
            'include_troubleshooting' => true
        ];
        
        $reflection = new ReflectionClass($this->pdf_guide);
        
        try {
            $method = $reflection->getMethod('generate_pdf_content');
            $method->setAccessible(true);
            $content = $method->invoke($this->pdf_guide, $test_options);
            
            if (!empty($content)) {
                echo "✅ PDF content generation works\n";
            } else {
                echo "❌ PDF content generation returned empty content\n";
            }
        } catch (Exception $e) {
            echo "❌ PDF content generation failed: " . $e->getMessage() . "\n";
        }
    }
}

// Run tests if accessed directly
if (defined('WP_CLI') && WP_CLI) {
    $test = new Xelite_Repost_Engine_PDF_Guide_Test();
} elseif (isset($_GET['run_pdf_tests']) && current_user_can('manage_options')) {
    $test = new Xelite_Repost_Engine_PDF_Guide_Test();
} 