<?php
/**
 * Repost Intelligence Dashboard
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Dashboard Class
 * 
 * Handles the user dashboard UI framework, admin menu integration,
 * and basic layout components for the Repost Intelligence plugin.
 */
class Repost_Intelligence_Dashboard extends XeliteRepostEngine_Abstract_Base {

    /**
     * Dashboard page slug
     *
     * @var string
     */
    private $dashboard_page = 'repost-intelligence-dashboard';

    /**
     * Dashboard capability required
     *
     * @var string
     */
    private $capability = 'read';

    /**
     * Current user ID
     *
     * @var int
     */
    private $user_id;

    /**
     * Current tab
     *
     * @var string
     */
    private $current_tab = 'overview';

    /**
     * Available tabs
     *
     * @var array
     */
    private $tabs = array();

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->user_id = get_current_user_id();
        
        // Initialize dashboard
        add_action('init', array($this, 'init_dashboard'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_dashboard_menu'));
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_xelite_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_xelite_dashboard_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_xelite_dashboard_save_content', array($this, 'ajax_save_content'));
        add_action('wp_ajax_xelite_dashboard_get_patterns', array($this, 'ajax_get_patterns'));
        add_action('wp_ajax_xelite_dashboard_update_settings', array($this, 'ajax_update_settings'));
    }

    /**
     * Initialize dashboard
     */
    public function init_dashboard() {
        // Initialize tabs
        $this->init_tabs();
        
        // Get current tab from URL
        $this->current_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->tabs) 
            ? sanitize_text_field($_GET['tab']) 
            : 'overview';
    }

    /**
     * Initialize available tabs
     */
    private function init_tabs() {
        $this->tabs = array(
            'overview' => array(
                'title' => __('Overview', 'xelite-repost-engine'),
                'icon' => 'dashicons-chart-area',
                'description' => __('Dashboard overview and key metrics.', 'xelite-repost-engine'),
                'callback' => array($this, 'render_overview_tab')
            ),
            'content-generator' => array(
                'title' => __('Content Generator', 'xelite-repost-engine'),
                'icon' => 'dashicons-admin-generic',
                'description' => __('Generate AI-powered content based on repost patterns.', 'xelite-repost-engine'),
                'callback' => array($this, 'render_content_generator_tab')
            ),
            'patterns' => array(
                'title' => __('Repost Patterns', 'xelite-repost-engine'),
                'icon' => 'dashicons-chart-line',
                'description' => __('View and analyze repost patterns from target accounts.', 'xelite-repost-engine'),
                'callback' => array($this, 'render_patterns_tab')
            ),
            'analytics' => array(
                'title' => __('Analytics', 'xelite-repost-engine'),
                'icon' => 'dashicons-chart-bar',
                'description' => __('Detailed analytics and insights.', 'xelite-repost-engine'),
                'callback' => array($this, 'render_analytics_tab')
            ),
            'settings' => array(
                'title' => __('Settings', 'xelite-repost-engine'),
                'icon' => 'dashicons-admin-settings',
                'description' => __('Personal settings and preferences.', 'xelite-repost-engine'),
                'callback' => array($this, 'render_settings_tab')
            )
        );
    }

    /**
     * Add dashboard menu
     */
    public function add_dashboard_menu() {
        // Check if user has access
        if (!$this->user_has_access()) {
            return;
        }

        // Add main dashboard page
        add_menu_page(
            __('Repost Intelligence', 'xelite-repost-engine'),
            __('Repost Intelligence', 'xelite-repost-engine'),
            $this->capability,
            $this->dashboard_page,
            array($this, 'render_dashboard_page'),
            'dashicons-share',
            30
        );

        // Add submenu pages for each tab
        foreach ($this->tabs as $tab_slug => $tab) {
            add_submenu_page(
                $this->dashboard_page,
                $tab['title'],
                $tab['title'],
                $this->capability,
                $this->dashboard_page . '&tab=' . $tab_slug,
                array($this, 'render_dashboard_page')
            );
        }
    }

    /**
     * Check if user has access to dashboard
     *
     * @return bool
     */
    public function user_has_access() {
        // Check if user is logged in
        if (!$this->user_id) {
            return false;
        }

        // Check if user has required capability
        if (!current_user_can($this->capability)) {
            return false;
        }

        // Check if plugin is enabled
        $settings = get_option('xelite_repost_engine_settings', array());
        if (isset($settings['plugin_enabled']) && !$settings['plugin_enabled']) {
            return false;
        }

        return true;
    }

    /**
     * Enqueue dashboard assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_dashboard_assets($hook) {
        // Only load on our dashboard page
        if (strpos($hook, $this->dashboard_page) === false) {
            return;
        }

        // Enqueue dashboard styles
        wp_enqueue_style(
            'xelite-repost-engine-dashboard',
            XELITE_REPOST_ENGINE_PLUGIN_URL . 'assets/css/dashboard.css',
            array(),
            XELITE_REPOST_ENGINE_VERSION
        );

        // Enqueue dashboard scripts
        wp_enqueue_script(
            'xelite-repost-engine-dashboard',
            XELITE_REPOST_ENGINE_PLUGIN_URL . 'assets/js/dashboard.js',
            array('jquery', 'wp-util'),
            XELITE_REPOST_ENGINE_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('xelite-repost-engine-dashboard', 'xelite_dashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xelite_dashboard_nonce'),
            'currentTab' => $this->current_tab,
            'strings' => array(
                'loading' => __('Loading...', 'xelite-repost-engine'),
                'success' => __('Success!', 'xelite-repost-engine'),
                'error' => __('Error!', 'xelite-repost-engine'),
                'confirmDelete' => __('Are you sure you want to delete this?', 'xelite-repost-engine'),
                'generating' => __('Generating content...', 'xelite-repost-engine'),
                'saving' => __('Saving...', 'xelite-repost-engine')
            )
        ));
    }

    /**
     * Render main dashboard page
     */
    public function render_dashboard_page() {
        // Check user access
        if (!$this->user_has_access()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'xelite-repost-engine'));
        }

        // Get current tab
        $current_tab = $this->current_tab;
        $tabs = $this->tabs;

        // Include dashboard template
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/main.php';
    }

    /**
     * Render overview tab
     */
    public function render_overview_tab() {
        // Get dashboard data
        $dashboard_data = $this->get_dashboard_data();
        
        // Include overview template
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/overview.php';
    }

    /**
     * Render content generator tab
     */
    public function render_content_generator_tab() {
        // Get user context
        $user_context = $this->get_user_context();
        
        // Get available patterns
        $patterns = $this->get_available_patterns();
        
        // Include content generator template
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/content-generator.php';
    }

    /**
     * Render patterns tab
     */
    public function render_patterns_tab() {
        // Get pattern data
        $patterns = $this->get_pattern_data();
        
        // Include patterns template
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/patterns.php';
    }

    /**
     * Render analytics tab
     */
    public function render_analytics_tab() {
        // Get analytics data
        $analytics_data = $this->get_analytics_data();
        
        // Include analytics template
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/analytics.php';
    }

    /**
     * Render settings tab
     */
    public function render_settings_tab() {
        // Get user settings
        $user_settings = $this->get_user_settings();
        
        // Include settings template
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/settings.php';
    }

    /**
     * Get dashboard data
     *
     * @return array
     */
    private function get_dashboard_data() {
        $data = array(
            'user_context' => $this->get_user_context(),
            'recent_patterns' => $this->get_recent_patterns(),
            'generation_stats' => $this->get_generation_stats(),
            'account_stats' => $this->get_account_stats()
        );

        return $data;
    }

    /**
     * Get user context
     *
     * @return array
     */
    public function get_user_context() {
        $user_meta = new XeliteRepostEngine_User_Meta();
        return $user_meta->get_user_context($this->user_id);
    }

    /**
     * Get recent patterns
     *
     * @return array
     */
    private function get_recent_patterns() {
        $container = XeliteRepostEngine_Container::instance();
        $pattern_analyzer = $container->get('pattern_analyzer');
        
        return $pattern_analyzer->get_recent_patterns(5);
    }

    /**
     * Get generation stats
     *
     * @return array
     */
    public function get_generation_stats() {
        // Get user's content generation statistics
        $stats = get_user_meta($this->user_id, 'xelite_generation_stats', true);
        
        if (!$stats) {
            $stats = array(
                'total_generated' => 0,
                'total_saved' => 0,
                'total_posted' => 0,
                'last_generated' => null
            );
        }
        
        return $stats;
    }

    /**
     * Get account stats
     *
     * @return array
     */
    private function get_account_stats() {
        $container = XeliteRepostEngine_Container::instance();
        $database = $container->get('database');
        
        return $database->get_account_statistics();
    }

    /**
     * Get available patterns
     *
     * @return array
     */
    private function get_available_patterns() {
        $container = XeliteRepostEngine_Container::instance();
        $pattern_analyzer = $container->get('pattern_analyzer');
        
        return $pattern_analyzer->get_all_patterns();
    }

    /**
     * Get pattern data
     *
     * @return array
     */
    private function get_pattern_data() {
        $container = XeliteRepostEngine_Container::instance();
        $pattern_visualizer = $container->get('pattern_visualizer');
        
        return $pattern_visualizer->generate_dashboard_data();
    }

    /**
     * Get analytics data
     *
     * @return array
     */
    private function get_analytics_data() {
        $container = XeliteRepostEngine_Container::instance();
        $pattern_visualizer = $container->get('pattern_visualizer');
        
        return $pattern_visualizer->generate_dashboard_data();
    }

    /**
     * Get user settings
     *
     * @return array
     */
    public function get_user_settings() {
        $settings = get_user_meta($this->user_id, 'xelite_user_settings', true);
        
        if (!$settings) {
            $settings = array(
                'default_tone' => 'conversational',
                'max_tokens' => 280,
                'temperature' => 0.7,
                'auto_save' => true,
                'notifications' => true
            );
        }
        
        return $settings;
    }

    /**
     * AJAX handler for getting dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('xelite_dashboard_nonce', 'nonce');
        
        if (!$this->user_has_access()) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $data = $this->get_dashboard_data();
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for generating content
     */
    public function ajax_generate_content() {
        check_ajax_referer('xelite_dashboard_nonce', 'nonce');
        
        if (!$this->user_has_access()) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $user_context = json_decode(stripslashes($_POST['user_context'] ?? '{}'), true);
            $patterns = json_decode(stripslashes($_POST['patterns'] ?? '{}'), true);
            $options = json_decode(stripslashes($_POST['options'] ?? '{}'), true);
            
            $container = XeliteRepostEngine_Container::instance();
            $openai = $container->get('openai');
            
            $result = $openai->generate_content($user_context, $patterns, $options);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for saving content
     */
    public function ajax_save_content() {
        check_ajax_referer('xelite_dashboard_nonce', 'nonce');
        
        if (!$this->user_has_access()) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $content = sanitize_textarea_field($_POST['content'] ?? '');
            $title = sanitize_text_field($_POST['title'] ?? 'Generated Content');
            
            if (empty($content)) {
                wp_send_json_error('Content is required');
            }
            
            // Save to user meta
            $saved_content = get_user_meta($this->user_id, 'xelite_saved_content', true) ?: array();
            $saved_content[] = array(
                'id' => uniqid(),
                'title' => $title,
                'content' => $content,
                'created_at' => current_time('mysql'),
                'user_context' => json_decode(stripslashes($_POST['user_context'] ?? '{}'), true)
            );
            
            update_user_meta($this->user_id, 'xelite_saved_content', $saved_content);
            
            wp_send_json_success('Content saved successfully');
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for getting patterns
     */
    public function ajax_get_patterns() {
        check_ajax_referer('xelite_dashboard_nonce', 'nonce');
        
        if (!$this->user_has_access()) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $patterns = $this->get_available_patterns();
            wp_send_json_success($patterns);
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for updating settings
     */
    public function ajax_update_settings() {
        check_ajax_referer('xelite_dashboard_nonce', 'nonce');
        
        if (!$this->user_has_access()) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $settings = json_decode(stripslashes($_POST['settings'] ?? '{}'), true);
            
            if (empty($settings)) {
                wp_send_json_error('Settings are required');
            }
            
            // Sanitize settings
            $sanitized_settings = array(
                'default_tone' => sanitize_text_field($settings['default_tone'] ?? 'conversational'),
                'max_tokens' => absint($settings['max_tokens'] ?? 280),
                'temperature' => floatval($settings['temperature'] ?? 0.7),
                'auto_save' => (bool) ($settings['auto_save'] ?? true),
                'notifications' => (bool) ($settings['notifications'] ?? true)
            );
            
            update_user_meta($this->user_id, 'xelite_user_settings', $sanitized_settings);
            
            wp_send_json_success('Settings updated successfully');
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Get dashboard URL
     *
     * @param string $tab Optional tab parameter
     * @return string
     */
    public function get_dashboard_url($tab = '') {
        $url = admin_url('admin.php?page=' . $this->dashboard_page);
        
        if (!empty($tab) && array_key_exists($tab, $this->tabs)) {
            $url .= '&tab=' . $tab;
        }
        
        return $url;
    }

    /**
     * Check if current page is dashboard
     *
     * @return bool
     */
    public function is_dashboard_page() {
        global $pagenow;
        return $pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === $this->dashboard_page;
    }
} 