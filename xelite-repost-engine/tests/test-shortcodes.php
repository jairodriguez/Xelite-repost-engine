<?php
/**
 * Test file for Xelite Repost Engine Shortcodes
 *
 * This file demonstrates how to use the shortcodes in WordPress.
 * You can copy these examples into your pages or posts.
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for shortcode functionality
 */
class Xelite_Shortcode_Test {

    /**
     * Initialize the test class
     */
    public function __construct() {
        add_action('wp_loaded', array($this, 'test_shortcodes'));
    }

    /**
     * Test shortcode functionality
     */
    public function test_shortcodes() {
        // Test if shortcodes are registered
        $this->test_shortcode_registration();
        
        // Test shortcode rendering
        $this->test_shortcode_rendering();
        
        // Test shortcode attributes
        $this->test_shortcode_attributes();
    }

    /**
     * Test if shortcodes are properly registered
     */
    private function test_shortcode_registration() {
        global $shortcode_tags;
        
        $expected_shortcodes = array(
            'repost_patterns',
            'repost_suggestions', 
            'repost_analytics'
        );
        
        foreach ($expected_shortcodes as $shortcode) {
            if (!array_key_exists($shortcode, $shortcode_tags)) {
                error_log("Xelite Shortcode Test: Shortcode '{$shortcode}' is not registered");
            } else {
                error_log("Xelite Shortcode Test: Shortcode '{$shortcode}' is properly registered");
            }
        }
    }

    /**
     * Test shortcode rendering
     */
    private function test_shortcode_rendering() {
        // Test patterns shortcode
        $patterns_output = do_shortcode('[repost_patterns]');
        if (empty($patterns_output)) {
            error_log("Xelite Shortcode Test: Patterns shortcode output is empty");
        } else {
            error_log("Xelite Shortcode Test: Patterns shortcode renders successfully");
        }
        
        // Test suggestions shortcode
        $suggestions_output = do_shortcode('[repost_suggestions]');
        if (empty($suggestions_output)) {
            error_log("Xelite Shortcode Test: Suggestions shortcode output is empty");
        } else {
            error_log("Xelite Shortcode Test: Suggestions shortcode renders successfully");
        }
        
        // Test analytics shortcode
        $analytics_output = do_shortcode('[repost_analytics]');
        if (empty($analytics_output)) {
            error_log("Xelite Shortcode Test: Analytics shortcode output is empty");
        } else {
            error_log("Xelite Shortcode Test: Analytics shortcode renders successfully");
        }
    }

    /**
     * Test shortcode attributes
     */
    private function test_shortcode_attributes() {
        // Test patterns shortcode with attributes
        $patterns_with_attrs = do_shortcode('[repost_patterns limit="5" period="7" show_filters="false" theme="dark"]');
        if (empty($patterns_with_attrs)) {
            error_log("Xelite Shortcode Test: Patterns shortcode with attributes output is empty");
        } else {
            error_log("Xelite Shortcode Test: Patterns shortcode with attributes renders successfully");
        }
        
        // Test suggestions shortcode with attributes
        $suggestions_with_attrs = do_shortcode('[repost_suggestions limit="3" show_generate="false" theme="dark"]');
        if (empty($suggestions_with_attrs)) {
            error_log("Xelite Shortcode Test: Suggestions shortcode with attributes output is empty");
        } else {
            error_log("Xelite Shortcode Test: Suggestions shortcode with attributes renders successfully");
        }
        
        // Test analytics shortcode with attributes
        $analytics_with_attrs = do_shortcode('[repost_analytics period="14" show_charts="false" show_stats="true" theme="dark"]');
        if (empty($analytics_with_attrs)) {
            error_log("Xelite Shortcode Test: Analytics shortcode with attributes output is empty");
        } else {
            error_log("Xelite Shortcode Test: Analytics shortcode with attributes renders successfully");
        }
    }
}

// Initialize test class if in test mode
if (defined('WP_DEBUG') && WP_DEBUG) {
    new Xelite_Shortcode_Test();
}

/**
 * Shortcode Usage Examples
 * 
 * Copy these examples into your WordPress pages or posts:
 */

/*
=== BASIC USAGE ===

1. Display repost patterns:
[repost_patterns]

2. Display AI-generated suggestions:
[repost_suggestions]

3. Display analytics:
[repost_analytics]

=== ADVANCED USAGE ===

1. Patterns with custom settings:
[repost_patterns limit="15" period="60" show_filters="true" show_charts="true" theme="light"]

2. Suggestions with custom settings:
[repost_suggestions limit="10" show_generate="true" theme="dark"]

3. Analytics with custom settings:
[repost_analytics period="90" show_charts="true" show_stats="true" theme="light"]

=== ATTRIBUTE REFERENCE ===

REPOST PATTERNS SHORTCODE:
- limit: Number of patterns to display (default: 10)
- period: Days to look back (default: 30)
- show_filters: Show/hide filter controls (default: true)
- show_charts: Show/hide chart visualization (default: true)
- theme: Visual theme - "light" or "dark" (default: light)

REPOST SUGGESTIONS SHORTCODE:
- limit: Number of suggestions to display (default: 5)
- show_generate: Show/hide generate button (default: true)
- theme: Visual theme - "light" or "dark" (default: light)

REPOST ANALYTICS SHORTCODE:
- period: Days to analyze (default: 30)
- show_charts: Show/hide charts (default: true)
- show_stats: Show/hide statistics cards (default: true)
- theme: Visual theme - "light" or "dark" (default: light)

=== FEATURES ===

1. Responsive Design: All shortcodes are mobile-friendly
2. Theme Support: Light and dark themes available
3. Interactive Elements: Charts, filters, and search functionality
4. User Access Control: Only logged-in users can view content
5. AJAX Loading: Dynamic content loading without page refresh
6. Export Options: Copy content and export data
7. Customization: Extensive attribute options for customization

=== SECURITY ===

- All shortcodes verify user authentication
- Nonce verification for AJAX requests
- Input sanitization and validation
- SQL injection protection
- XSS prevention through proper escaping

=== PERFORMANCE ===

- Assets loaded only when shortcodes are present
- Debounced search input for better performance
- Optimized database queries
- Caching-friendly output
- Minimal JavaScript footprint

=== INTEGRATION ===

- Works with all WordPress themes
- Compatible with page builders (Elementor, Divi, etc.)
- WooCommerce subscription integration ready
- REST API endpoints available
- Custom hooks and filters for developers
*/ 