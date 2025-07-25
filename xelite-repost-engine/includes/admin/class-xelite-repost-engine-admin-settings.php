<?php
/**
 * Admin Settings Page for Xelite Repost Engine
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin settings page handler class
 */
class XeliteRepostEngine_Admin_Settings extends XeliteRepostEngine_Abstract_Base {
    
    use XeliteRepostEngine_Admin_Fields;
    
    /**
     * Current tab
     *
     * @var string
     */
    private $current_tab = 'general';
    
    /**
     * Available tabs
     *
     * @var array
     */
    private $tabs = array();
    
    /**
     * Settings page slug
     *
     * @var string
     */
    private $settings_page = 'xelite-repost-engine';
    
    /**
     * Settings option name
     *
     * @var string
     */
    private $option_name = 'xelite_repost_engine_settings';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Initialize tabs
        $this->init_tabs();
    }

    /**
     * Initialize the class
     */
    protected function init() {
        // Hook into WordPress admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_xelite_test_api_connection', array($this, 'test_api_connection'));
        add_action('wp_ajax_xelite_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_xelite_save_x_credentials', array($this, 'save_x_credentials'));
        
        // X Data processing AJAX handlers
        add_action('wp_ajax_xelite_fetch_posts', array($this, 'ajax_fetch_posts'));
        add_action('wp_ajax_xelite_analyze_data', array($this, 'ajax_analyze_data'));
        
        // OpenAI AJAX handlers
        add_action('wp_ajax_xelite_test_openai_connection', array($this, 'ajax_test_openai_connection'));
        add_action('wp_ajax_xelite_refresh_openai_usage', array($this, 'ajax_refresh_openai_usage'));
        add_action('wp_ajax_xelite_test_content_generation', array($this, 'ajax_test_content_generation'));
    }
    
    /**
     * Initialize available tabs
     */
    private function init_tabs() {
        $this->tabs = array(
            'general' => array(
                'title' => __('General Settings', 'xelite-repost-engine'),
                'icon' => 'dashicons-admin-generic',
                'description' => __('Configure basic plugin settings and preferences.', 'xelite-repost-engine')
            ),
            'api_keys' => array(
                'title' => __('API Keys', 'xelite-repost-engine'),
                'icon' => 'dashicons-admin-network',
                'description' => __('Configure API keys for X (Twitter) integration.', 'xelite-repost-engine')
            ),
            'openai' => array(
                'title' => __('OpenAI Integration', 'xelite-repost-engine'),
                'icon' => 'dashicons-admin-generic',
                'description' => __('Configure OpenAI API settings, usage tracking, and content generation parameters.', 'xelite-repost-engine')
            ),
            'target_accounts' => array(
                'title' => __('Target Accounts', 'xelite-repost-engine'),
                'icon' => 'dashicons-groups',
                'description' => __('Manage accounts to monitor for repost patterns.', 'xelite-repost-engine')
            ),
            'advanced' => array(
                'title' => __('Advanced Settings', 'xelite-repost-engine'),
                'icon' => 'dashicons-admin-tools',
                'description' => __('Advanced configuration options and performance settings.', 'xelite-repost-engine')
            ),
            'tools' => array(
                'title' => __('Tools & Utilities', 'xelite-repost-engine'),
                'icon' => 'dashicons-admin-tools',
                'description' => __('Database management, data export, and utility tools.', 'xelite-repost-engine')
            )
        );
        
        // Get current tab from URL or default to general
        $this->current_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->tabs) 
            ? sanitize_text_field($_GET['tab']) 
            : 'general';
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add main menu page
        add_menu_page(
            __('Xelite Repost Engine', 'xelite-repost-engine'),
            __('Repost Engine', 'xelite-repost-engine'),
            'manage_options',
            $this->settings_page,
            array($this, 'display_settings_page'),
            'dashicons-share',
            30
        );
        
        // Add submenu pages
        add_submenu_page(
            $this->settings_page,
            __('Settings', 'xelite-repost-engine'),
            __('Settings', 'xelite-repost-engine'),
            'manage_options',
            $this->settings_page,
            array($this, 'display_settings_page')
        );
        
        add_submenu_page(
            $this->settings_page,
            __('Dashboard', 'xelite-repost-engine'),
            __('Dashboard', 'xelite-repost-engine'),
            'manage_options',
            'xelite-repost-engine-dashboard',
            array($this, 'display_dashboard_page')
        );
        
        add_submenu_page(
            $this->settings_page,
            __('Analytics', 'xelite-repost-engine'),
            __('Analytics', 'xelite-repost-engine'),
            'manage_options',
            'xelite-repost-engine-analytics',
            array($this, 'display_analytics_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register the main settings
        register_setting(
            'xelite_repost_engine_settings',
            $this->option_name,
            array(
                'type' => 'array',
                'description' => 'Xelite Repost Engine settings',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->get_default_settings()
            )
        );
        
        // Register settings sections and fields based on current tab
        switch ($this->current_tab) {
            case 'general':
                $this->register_general_settings();
                break;
            case 'api_keys':
                $this->register_api_settings();
                break;
            case 'openai':
                $this->register_openai_settings();
                break;
            case 'target_accounts':
                $this->register_target_accounts_settings();
                break;
            case 'advanced':
                $this->register_advanced_settings();
                break;
            case 'tools':
                $this->register_tools_settings();
                break;
        }
    }
    
    /**
     * Register general settings
     */
    private function register_general_settings() {
        add_settings_section(
            'general_settings_section',
            __('General Settings', 'xelite-repost-engine'),
            array($this, 'general_settings_section_callback'),
            $this->settings_page
        );
        
        add_settings_field(
            'plugin_enabled',
            __('Enable Plugin', 'xelite-repost-engine'),
            array($this, 'checkbox_field_callback'),
            $this->settings_page,
            'general_settings_section',
            array(
                'field' => 'plugin_enabled',
                'description' => __('Enable or disable the plugin functionality.', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'auto_scraping_enabled',
            __('Auto Scraping', 'xelite-repost-engine'),
            array($this, 'checkbox_field_callback'),
            $this->settings_page,
            'general_settings_section',
            array(
                'field' => 'auto_scraping_enabled',
                'description' => __('Automatically scrape repost data from target accounts.', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'scraping_frequency',
            __('Scraping Frequency', 'xelite-repost-engine'),
            array($this, 'select_field_callback'),
            $this->settings_page,
            'general_settings_section',
            array(
                'field' => 'scraping_frequency',
                'options' => array(
                    'hourly' => __('Hourly', 'xelite-repost-engine'),
                    'twice_daily' => __('Twice Daily', 'xelite-repost-engine'),
                    'daily' => __('Daily', 'xelite-repost-engine'),
                    'weekly' => __('Weekly', 'xelite-repost-engine')
                ),
                'description' => __('How often to scrape repost data from target accounts.', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'max_reposts_per_account',
            __('Max Reposts per Account', 'xelite-repost-engine'),
            array($this, 'number_field_callback'),
            $this->settings_page,
            'general_settings_section',
            array(
                'field' => 'max_reposts_per_account',
                'min' => 1,
                'max' => 1000,
                'step' => 1,
                'description' => __('Maximum number of reposts to store per target account.', 'xelite-repost-engine')
            )
        );
    }
    
    /**
     * Register API settings
     */
    private function register_api_settings() {
        add_settings_section(
            'api_settings_section',
            __('API Configuration', 'xelite-repost-engine'),
            array($this, 'api_settings_section_callback'),
            $this->settings_page
        );
        
        // X API Authentication Section
        add_settings_field(
            'x_api_authentication',
            __('X (Twitter) API Authentication', 'xelite-repost-engine'),
            array($this, 'x_api_auth_field_callback'),
            $this->settings_page,
            'api_settings_section',
            array(
                'description' => __('Configure X API authentication using OAuth. This is required for accessing repost data.', 'xelite-repost-engine')
            )
        );
        

        
        add_settings_field(
            'api_connection_status',
            __('Connection Status', 'xelite-repost-engine'),
            array($this, 'connection_status_field_callback'),
            $this->settings_page,
            'api_settings_section',
            array(
                'description' => __('Current status of your API connections. Test each connection to verify your credentials.', 'xelite-repost-engine')
            )
        );
    }
    
    /**
     * Register OpenAI settings
     */
    private function register_openai_settings() {
        // API Configuration Section
        add_settings_section(
            'openai_api_section',
            __('OpenAI API Configuration', 'xelite-repost-engine'),
            array($this, 'openai_api_section_callback'),
            $this->settings_page
        );
        
        add_settings_field(
            'openai_api_key',
            __('API Key', 'xelite-repost-engine'),
            array($this, 'openai_api_key_field_callback'),
            $this->settings_page,
            'openai_api_section',
            array(
                'description' => __('Your OpenAI API key for AI content generation. Keep this secure.', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'openai_model',
            __('Default Model', 'xelite-repost-engine'),
            array($this, 'openai_model_field_callback'),
            $this->settings_page,
            'openai_api_section',
            array(
                'description' => __('Default OpenAI model to use for content generation.', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'openai_connection_status',
            __('Connection Status', 'xelite-repost-engine'),
            array($this, 'openai_connection_status_field_callback'),
            $this->settings_page,
            'openai_api_section',
            array(
                'description' => __('Test your OpenAI API connection and view available models.', 'xelite-repost-engine')
            )
        );
        
        // Content Generation Parameters Section
        add_settings_section(
            'openai_content_section',
            __('Content Generation Parameters', 'xelite-repost-engine'),
            array($this, 'openai_content_section_callback'),
            $this->settings_page
        );
        
        add_settings_field(
            'openai_temperature',
            __('Creativity Level', 'xelite-repost-engine'),
            array($this, 'openai_temperature_field_callback'),
            $this->settings_page,
            'openai_content_section',
            array(
                'description' => __('Controls creativity in content generation. Higher values = more creative, Lower values = more focused.', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'openai_max_tokens',
            __('Maximum Tokens', 'xelite-repost-engine'),
            array($this, 'openai_max_tokens_field_callback'),
            $this->settings_page,
            'openai_content_section',
            array(
                'description' => __('Maximum number of tokens to generate per request.', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'openai_content_tone',
            __('Default Content Tone', 'xelite-repost-engine'),
            array($this, 'openai_content_tone_field_callback'),
            $this->settings_page,
            'openai_content_section',
            array(
                'description' => __('Default tone for generated content.', 'xelite-repost-engine')
            )
        );
        
        // Usage Tracking Section
        add_settings_section(
            'openai_usage_section',
            __('Usage Tracking & Quotas', 'xelite-repost-engine'),
            array($this, 'openai_usage_section_callback'),
            $this->settings_page
        );
        
        add_settings_field(
            'openai_daily_limit',
            __('Daily API Call Limit', 'xelite-repost-engine'),
            array($this, 'openai_daily_limit_field_callback'),
            $this->settings_page,
            'openai_usage_section',
            array(
                'description' => __('Maximum number of API calls per day (0 = unlimited).', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'openai_monthly_budget',
            __('Monthly Budget Limit ($)', 'xelite-repost-engine'),
            array($this, 'openai_monthly_budget_field_callback'),
            $this->settings_page,
            'openai_usage_section',
            array(
                'description' => __('Maximum monthly spending on OpenAI API (0 = unlimited).', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'openai_usage_dashboard',
            __('Usage Statistics', 'xelite-repost-engine'),
            array($this, 'openai_usage_dashboard_field_callback'),
            $this->settings_page,
            'openai_usage_section',
            array(
                'description' => __('View current usage statistics and API consumption.', 'xelite-repost-engine')
            )
        );
        
        // Testing Section
        add_settings_section(
            'openai_testing_section',
            __('Content Generation Testing', 'xelite-repost-engine'),
            array($this, 'openai_testing_section_callback'),
            $this->settings_page
        );
        
        add_settings_field(
            'openai_test_generation',
            __('Test Content Generation', 'xelite-repost-engine'),
            array($this, 'openai_test_generation_field_callback'),
            $this->settings_page,
            'openai_testing_section',
            array(
                'description' => __('Test content generation with current settings.', 'xelite-repost-engine')
            )
        );
    }

    /**
     * Register target accounts settings
     */
    private function register_target_accounts_settings() {
        add_settings_section(
            'target_accounts_section',
            __('Target Accounts', 'xelite-repost-engine'),
            array($this, 'target_accounts_section_callback'),
            $this->settings_page
        );
        
        add_settings_field(
            'target_accounts',
            __('Accounts to Monitor', 'xelite-repost-engine'),
            array($this, 'repeater_field_callback'),
            $this->settings_page,
            'target_accounts_section',
            array(
                'field' => 'target_accounts',
                'description' => __('Add X (Twitter) handles to monitor for repost patterns. Include the @ symbol.', 'xelite-repost-engine'),
                'fields' => array(
                    'handle' => array(
                        'type' => 'text',
                        'label' => __('Handle', 'xelite-repost-engine'),
                        'placeholder' => '@username'
                    ),
                    'name' => array(
                        'type' => 'text',
                        'label' => __('Display Name', 'xelite-repost-engine'),
                        'placeholder' => __('Account Display Name', 'xelite-repost-engine')
                    ),
                    'enabled' => array(
                        'type' => 'checkbox',
                        'label' => __('Enabled', 'xelite-repost-engine')
                    )
                )
            )
        );
    }
    
    /**
     * Register advanced settings
     */
    private function register_advanced_settings() {
        add_settings_section(
            'advanced_settings_section',
            __('Advanced Configuration', 'xelite-repost-engine'),
            array($this, 'advanced_settings_section_callback'),
            $this->settings_page
        );
        
        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'xelite-repost-engine'),
            array($this, 'checkbox_field_callback'),
            $this->settings_page,
            'advanced_settings_section',
            array(
                'field' => 'debug_mode',
                'description' => __('Enable debug logging for troubleshooting.', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'log_retention_days',
            __('Log Retention (Days)', 'xelite-repost-engine'),
            array($this, 'number_field_callback'),
            $this->settings_page,
            'advanced_settings_section',
            array(
                'field' => 'log_retention_days',
                'min' => 1,
                'max' => 365,
                'step' => 1,
                'description' => __('Number of days to retain log files.', 'xelite-repost-engine')
            )
        );
        
        add_settings_field(
            'data_retention_days',
            __('Data Retention (Days)', 'xelite-repost-engine'),
            array($this, 'number_field_callback'),
            $this->settings_page,
            'advanced_settings_section',
            array(
                'field' => 'data_retention_days',
                'min' => 30,
                'max' => 3650,
                'step' => 1,
                'description' => __('Number of days to retain repost data.', 'xelite-repost-engine')
            )
        );
    }
    
    /**
     * Register tools settings
     */
    private function register_tools_settings() {
        add_settings_section(
            'tools_section',
            __('Tools & Utilities', 'xelite-repost-engine'),
            array($this, 'tools_section_callback'),
            $this->settings_page
        );
        
        // Tools are handled via AJAX, not traditional settings fields
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our settings page
        if (strpos($hook, $this->settings_page) === false) {
            return;
        }
        
        wp_enqueue_script(
            'xelite-repost-engine-admin',
            XELITE_REPOST_ENGINE_PLUGIN_URL . 'assets/js/xelite-repost-engine-admin.js',
            array('jquery', 'wp-util'),
            XELITE_REPOST_ENGINE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'xelite-repost-engine-admin',
            XELITE_REPOST_ENGINE_PLUGIN_URL . 'assets/css/xelite-repost-engine-admin.css',
            array(),
            XELITE_REPOST_ENGINE_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script('xelite-repost-engine-admin', 'xelite_repost_engine', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xelite_repost_engine_admin_nonce'),
            'strings' => array(
                'testing' => __('Testing...', 'xelite-repost-engine'),
                'success' => __('Success!', 'xelite-repost-engine'),
                'error' => __('Error!', 'xelite-repost-engine'),
                'confirmDelete' => __('Are you sure you want to delete this data?', 'xelite-repost-engine')
            )
        ));
    }
    
    /**
     * Display the main settings page
     */
    public function display_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'xelite-repost-engine'));
        }
        
        // Verify nonce for form submission
        if (isset($_POST['submit']) && !wp_verify_nonce($_POST['_wpnonce'], 'xelite_repost_engine_settings')) {
            wp_die(__('Security check failed.', 'xelite-repost-engine'));
        }
        
        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php $this->display_admin_notices(); ?>
            
            <h2 class="nav-tab-wrapper">
                <?php foreach ($this->tabs as $tab_id => $tab) : ?>
                    <a href="?page=<?php echo esc_attr($this->settings_page); ?>&tab=<?php echo esc_attr($tab_id); ?>" 
                       class="nav-tab <?php echo $this->current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                        <?php echo esc_html($tab['title']); ?>
                    </a>
                <?php endforeach; ?>
            </h2>
            
            <div class="tab-content">
                <div class="tab-description">
                    <p><?php echo esc_html($this->tabs[$this->current_tab]['description']); ?></p>
                </div>
                
                <?php if ($this->current_tab === 'tools') : ?>
                    <?php $this->display_tools_page(); ?>
                <?php else : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('xelite_repost_engine_settings');
                        do_settings_sections($this->settings_page);
                        submit_button();
                        ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display dashboard page
     */
    public function display_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'xelite-repost-engine'));
        }
        
        // Get dashboard data
        $database = $this->get_service('database');
        $stats = $database->get_database_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Repost Engine Dashboard', 'xelite-repost-engine'); ?></h1>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php _e('Total Reposts', 'xelite-repost-engine'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['total_reposts']); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php _e('Analyzed Reposts', 'xelite-repost-engine'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['analyzed_reposts']); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php _e('Target Accounts', 'xelite-repost-engine'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['total_sources']); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php _e('Active Users', 'xelite-repost-engine'); ?></h3>
                    <div class="stat-number"><?php echo esc_html($stats['total_users']); ?></div>
                </div>
            </div>
            
            <!-- Add more dashboard content here -->
        </div>
        <?php
    }
    
    /**
     * Display analytics page
     */
    public function display_analytics_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'xelite-repost-engine'));
        }
        
        // Get analytics data
        $database = $this->get_service('database');
        $analytics = $database->get_repost_analytics('daily', date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
        ?>
        <div class="wrap">
            <h1><?php _e('Repost Analytics', 'xelite-repost-engine'); ?></h1>
            
            <div class="analytics-container">
                <!-- Add analytics charts and data here -->
                <p><?php _e('Analytics dashboard coming soon...', 'xelite-repost-engine'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display tools page
     */
    private function display_tools_page() {
        ?>
        <div class="tools-container">
            <div class="tool-section">
                <h3><?php _e('Database Management', 'xelite-repost-engine'); ?></h3>
                <p><?php _e('Manage your repost data and database.', 'xelite-repost-engine'); ?></p>
                
                <button type="button" class="button button-secondary" id="export-data">
                    <?php _e('Export Data', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="cleanup-data">
                    <?php _e('Cleanup Old Data', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="reset-database">
                    <?php _e('Reset Database', 'xelite-repost-engine'); ?>
                </button>
            </div>
            
            <div class="tool-section">
                <h3><?php _e('System Information', 'xelite-repost-engine'); ?></h3>
                <p><?php _e('View system information and status.', 'xelite-repost-engine'); ?></p>
                
                <button type="button" class="button button-secondary" id="system-info">
                    <?php _e('View System Info', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="test-connections">
                    <?php _e('Test All Connections', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get current settings
     */
    public function get_settings() {
        $settings = get_option($this->option_name, array());
        return wp_parse_args($settings, $this->get_default_settings());
    }
    
    /**
     * Get default settings
     */
    private function get_default_settings() {
        return array(
            'plugin_enabled' => true,
            'auto_scraping_enabled' => true,
            'scraping_frequency' => 'daily',
            'max_reposts_per_account' => 100,
            'x_api_key' => '',
            'x_api_secret' => '',
            'x_bearer_token' => '',
            'openai_api_key' => '',
            'target_accounts' => array(),
            'debug_mode' => false,
            'log_retention_days' => 30,
            'data_retention_days' => 365
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $defaults = $this->get_default_settings();
        
        // Sanitize each field
        foreach ($defaults as $key => $default_value) {
            if (isset($input[$key])) {
                switch ($key) {
                    case 'plugin_enabled':
                    case 'auto_scraping_enabled':
                    case 'debug_mode':
                        $sanitized[$key] = (bool) $input[$key];
                        break;
                        
                    case 'scraping_frequency':
                        $allowed_frequencies = array('hourly', 'twice_daily', 'daily', 'weekly');
                        $sanitized[$key] = in_array($input[$key], $allowed_frequencies) ? $input[$key] : $default_value;
                        break;
                        
                    case 'max_reposts_per_account':
                    case 'log_retention_days':
                    case 'data_retention_days':
                        $sanitized[$key] = absint($input[$key]);
                        break;
                        
                    case 'x_api_key':
                    case 'x_api_secret':
                    case 'x_bearer_token':
                    case 'openai_api_key':
                        $sanitized[$key] = sanitize_text_field($input[$key]);
                        break;
                        
                    case 'target_accounts':
                        $sanitized[$key] = $this->sanitize_target_accounts($input[$key]);
                        break;
                        
                    default:
                        $sanitized[$key] = sanitize_text_field($input[$key]);
                        break;
                }
            } else {
                $sanitized[$key] = $default_value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize target accounts
     */
    private function sanitize_target_accounts($accounts) {
        if (!is_array($accounts)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($accounts as $account) {
            if (is_array($account) && isset($account['handle'])) {
                $handle = sanitize_text_field($account['handle']);
                if (!empty($handle)) {
                    $sanitized[] = array(
                        'handle' => $handle,
                        'name' => isset($account['name']) ? sanitize_text_field($account['name']) : '',
                        'enabled' => isset($account['enabled']) ? (bool) $account['enabled'] : true
                    );
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Display admin notices
     */
    private function display_admin_notices() {
        $notices = get_transient('xelite_repost_engine_admin_notices');
        if ($notices) {
            foreach ($notices as $notice) {
                $class = isset($notice['type']) ? 'notice-' . $notice['type'] : 'notice-info';
                echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
            }
            delete_transient('xelite_repost_engine_admin_notices');
        }
    }
    
    /**
     * Add admin notice
     */
    public function add_admin_notice($message, $type = 'info') {
        $notices = get_transient('xelite_repost_engine_admin_notices') ?: array();
        $notices[] = array(
            'message' => $message,
            'type' => $type
        );
        set_transient('xelite_repost_engine_admin_notices', $notices, 60);
    }
    
    /**
     * Test API connection via AJAX
     */
    public function test_api_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_repost_engine_admin_nonce')) {
            wp_die(__('Security check failed.', 'xelite-repost-engine'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'xelite-repost-engine'));
        }
        
        $field = sanitize_text_field($_POST['field']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        $result = array(
            'success' => false,
            'message' => ''
        );
        
        switch ($field) {
            case 'x_api_key':
            case 'x_bearer_token':
                $result = $this->test_x_api_connection($api_key);
                break;
                
            case 'openai_api_key':
                $result = $this->test_openai_api_connection($api_key);
                break;
                
            default:
                $result['message'] = __('Unknown API field.', 'xelite-repost-engine');
                break;
        }
        
        wp_send_json($result);
    }
    
    /**
     * Test X (Twitter) API connection
     */
    private function test_x_api_connection($api_key) {
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => __('API key is required.', 'xelite-repost-engine')
            );
        }
        
        // Basic format validation
        if (strlen($api_key) < 10) {
            return array(
                'success' => false,
                'message' => __('API key appears to be invalid.', 'xelite-repost-engine')
            );
        }
        
        // Test actual API connection
        $response = wp_remote_get('https://api.twitter.com/2/users/me', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Connection failed: %s', 'xelite-repost-engine'),
                    $response->get_error_message()
                )
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => __('X API connection successful!', 'xelite-repost-engine')
            );
        } elseif ($status_code === 401) {
            return array(
                'success' => false,
                'message' => __('Invalid API key. Please check your credentials.', 'xelite-repost-engine')
            );
        } elseif ($status_code === 403) {
            return array(
                'success' => false,
                'message' => __('API key lacks required permissions. Ensure it has read access.', 'xelite-repost-engine')
            );
        } else {
            $error_message = isset($data['errors'][0]['message']) ? $data['errors'][0]['message'] : 'Unknown error';
            return array(
                'success' => false,
                'message' => sprintf(
                    __('API error (HTTP %d): %s', 'xelite-repost-engine'),
                    $status_code,
                    $error_message
                )
            );
        }
    }
    
    /**
     * Test OpenAI API connection
     */
    private function test_openai_api_connection($api_key) {
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => __('API key is required.', 'xelite-repost-engine')
            );
        }
        
        // Basic format validation for OpenAI API key
        if (!preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $api_key)) {
            return array(
                'success' => false,
                'message' => __('Invalid OpenAI API key format. Should start with "sk-" followed by 32+ characters.', 'xelite-repost-engine')
            );
        }
        
        // Test actual API connection with a minimal request
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hello'
                    )
                ),
                'max_tokens' => 5
            )),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Connection failed: %s', 'xelite-repost-engine'),
                    $response->get_error_message()
                )
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => __('OpenAI API connection successful!', 'xelite-repost-engine')
            );
        } elseif ($status_code === 401) {
            return array(
                'success' => false,
                'message' => __('Invalid API key. Please check your OpenAI credentials.', 'xelite-repost-engine')
            );
        } elseif ($status_code === 429) {
            return array(
                'success' => false,
                'message' => __('Rate limit exceeded. Please try again later.', 'xelite-repost-engine')
            );
        } else {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            return array(
                'success' => false,
                'message' => sprintf(
                    __('API error (HTTP %d): %s', 'xelite-repost-engine'),
                    $status_code,
                    $error_message
                )
            );
        }
    }
    
    /**
     * Save settings via AJAX
     */
    public function save_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_repost_engine_admin_nonce')) {
            wp_die(__('Security check failed.', 'xelite-repost-engine'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'xelite-repost-engine'));
        }
        
        $settings = $_POST['settings'];
        $sanitized_settings = $this->sanitize_settings($settings);
        
        $updated = update_option($this->option_name, $sanitized_settings);
        
        $result = array(
            'success' => $updated !== false,
            'message' => $updated !== false 
                ? __('Settings saved successfully.', 'xelite-repost-engine')
                : __('Failed to save settings.', 'xelite-repost-engine')
        );
        
        wp_send_json($result);
    }
    
    /**
     * Save X API credentials via AJAX
     */
    public function save_x_credentials() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_repost_engine_admin_nonce')) {
            wp_send_json_error(__('Security check failed.', 'xelite-repost-engine'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'xelite-repost-engine'));
        }
        
        // Get X Auth service
        $x_auth = $this->get_plugin()->container->get('x_auth');
        
        // Validate required fields
        $required_fields = array('consumer_key', 'consumer_secret', 'access_token', 'access_token_secret');
        $credentials = array();
        
        foreach ($required_fields as $field) {
            $field_name = 'x_api_' . $field;
            if (empty($_POST[$field_name])) {
                wp_send_json_error(sprintf(__('Missing required field: %s', 'xelite-repost-engine'), $field));
            }
            $credentials[$field] = sanitize_text_field($_POST[$field_name]);
        }
        
        // Store credentials
        $result = $x_auth->store_credentials($credentials);
        
        if ($result) {
            wp_send_json_success(__('X API credentials saved successfully.', 'xelite-repost-engine'));
        } else {
            wp_send_json_error(__('Failed to save X API credentials.', 'xelite-repost-engine'));
        }
    }
    
    /**
     * Handle save user meta AJAX request
     */
    public function handle_save_user_meta() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_repost_engine_user_meta')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'xelite-repost-engine')));
        }
        
        // Check permissions
        if (!current_user_can('edit_users')) {
            wp_send_json_error(array('message' => __('You do not have permission to edit user data.', 'xelite-repost-engine')));
        }
        
        $user_id = intval($_POST['user_id']);
        
        // Verify user exists and current user can edit them
        if (!$user_id || !current_user_can('edit_user', $user_id)) {
            wp_send_json_error(array('message' => __('Invalid user or insufficient permissions.', 'xelite-repost-engine')));
        }
        
        // Get user meta service
        $user_meta = $this->get_plugin()->container->get('user_meta');
        
        // Define allowed fields
        $allowed_fields = array(
            'topic',
            'personal-context',
            'dream-client',
            'dream-client-pain-points',
            'writing-style',
            'irresistible-offer',
            'ikigai'
        );
        
        $updated_fields = 0;
        $errors = array();
        
        // Update each field
        foreach ($allowed_fields as $field) {
            if (isset($_POST[$field])) {
                $result = $user_meta->update_meta_field($field, $_POST[$field], $user_id);
                if ($result) {
                    $updated_fields++;
                } else {
                    $errors[] = sprintf(__('Failed to update %s.', 'xelite-repost-engine'), $field);
                }
            }
        }
        
        if (empty($errors)) {
            // Get updated completeness data
            $completeness = $user_meta->is_context_complete($user_id);
            
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully updated %d fields.', 'xelite-repost-engine'), $updated_fields),
                'completeness' => $completeness
            ));
        } else {
            wp_send_json_error(array(
                'message' => implode(' ', $errors),
                'completeness' => $user_meta->is_context_complete($user_id)
            ));
        }
    }
    
    /**
     * Handle preview context AJAX request
     */
    public function handle_preview_context() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_repost_engine_user_meta')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'xelite-repost-engine')));
        }
        
        // Check permissions
        if (!current_user_can('edit_users')) {
            wp_send_json_error(array('message' => __('You do not have permission to preview user data.', 'xelite-repost-engine')));
        }
        
        $user_id = intval($_POST['user_id']);
        
        // Verify user exists and current user can edit them
        if (!$user_id || !current_user_can('edit_user', $user_id)) {
            wp_send_json_error(array('message' => __('Invalid user or insufficient permissions.', 'xelite-repost-engine')));
        }
        
        // Create temporary context from form data
        $temp_context = array();
        $allowed_fields = array(
            'topic',
            'personal-context',
            'dream-client',
            'dream-client-pain-points',
            'writing-style',
            'irresistible-offer',
            'ikigai'
        );
        
        foreach ($allowed_fields as $field) {
            if (isset($_POST[$field])) {
                $temp_context[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        
        // Generate preview
        $preview = $this->generate_context_preview($temp_context);
        
        wp_send_json_success(array(
            'preview' => $preview
        ));
    }
    
    /**
     * Generate context preview
     *
     * @param array $context User context data
     * @return string HTML preview
     */
    private function generate_context_preview($context) {
        $preview = '<div class="context-preview">';
        $preview .= '<h4>' . __('Context Summary', 'xelite-repost-engine') . '</h4>';
        
        if (!empty($context['topic'])) {
            $preview .= '<p><strong>' . __('Topic/Niche:', 'xelite-repost-engine') . '</strong> ' . esc_html($context['topic']) . '</p>';
        }
        
        if (!empty($context['writing-style'])) {
            $preview .= '<p><strong>' . __('Writing Style:', 'xelite-repost-engine') . '</strong> ' . esc_html($context['writing-style']) . '</p>';
        }
        
        if (!empty($context['dream-client'])) {
            $preview .= '<p><strong>' . __('Dream Client:', 'xelite-repost-engine') . '</strong> ' . esc_html($context['dream-client']) . '</p>';
        }
        
        if (!empty($context['dream-client-pain-points'])) {
            $preview .= '<p><strong>' . __('Client Pain Points:', 'xelite-repost-engine') . '</strong> ' . esc_html($context['dream-client-pain-points']) . '</p>';
        }
        
        if (!empty($context['irresistible-offer'])) {
            $preview .= '<p><strong>' . __('Offer:', 'xelite-repost-engine') . '</strong> ' . esc_html($context['irresistible-offer']) . '</p>';
        }
        
        if (!empty($context['personal-context'])) {
            $preview .= '<p><strong>' . __('Personal Context:', 'xelite-repost-engine') . '</strong> ' . esc_html($context['personal-context']) . '</p>';
        }
        
        if (!empty($context['ikigai'])) {
            $preview .= '<p><strong>' . __('Ikigai (Purpose):', 'xelite-repost-engine') . '</strong> ' . esc_html($context['ikigai']) . '</p>';
        }
        
        $preview .= '<hr>';
        $preview .= '<p><em>' . __('This context will be used to personalize AI-generated content for your specific audience and style.', 'xelite-repost-engine') . '</em></p>';
        $preview .= '</div>';
        
        return $preview;
    }
    
    /**
     * X API Authentication field callback
     *
     * @param array $args Field arguments
     */
    public function x_api_auth_field_callback($args) {
        // Get X Auth service
        $x_auth = $this->get_plugin()->container->get('x_auth');
        $credentials = $x_auth->get_credentials();
        $connection_status = $x_auth->get_connection_status();
        
        ?>
        <div class="x-api-auth-container">
            <?php if ($credentials): ?>
                <!-- Connected State -->
                <div class="x-api-connected">
                    <div class="connection-status success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('X API Connected', 'xelite-repost-engine'); ?>
                    </div>
                    
                    <?php if ($connection_status && $connection_status['success']): ?>
                        <div class="user-info">
                            <p><strong><?php _e('Authenticated as:', 'xelite-repost-engine'); ?></strong> 
                               @<?php echo esc_html($connection_status['user_info']['screen_name']); ?></p>
                            <p><strong><?php _e('Name:', 'xelite-repost-engine'); ?></strong> 
                               <?php echo esc_html($connection_status['user_info']['name']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="auth-actions">
                        <button type="button" class="button button-secondary test-x-connection" 
                                data-nonce="<?php echo wp_create_nonce('xelite_x_test_connection'); ?>">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Test Connection', 'xelite-repost-engine'); ?>
                        </button>
                        
                        <button type="button" class="button button-link-delete revoke-x-connection" 
                                data-nonce="<?php echo wp_create_nonce('xelite_x_revoke_connection'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Revoke Connection', 'xelite-repost-engine'); ?>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Manual Credentials Form -->
                <div class="x-api-manual-setup">
                    <p class="description"><?php echo esc_html($args['description']); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="x_api_consumer_key"><?php _e('API Key (Consumer Key)', 'xelite-repost-engine'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="x_api_consumer_key" name="x_api_consumer_key" 
                                       class="regular-text" value="<?php echo esc_attr($credentials ? $credentials['consumer_key'] : ''); ?>" />
                                <p class="description"><?php _e('Your X API consumer key from the X Developer Portal.', 'xelite-repost-engine'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="x_api_consumer_secret"><?php _e('API Secret (Consumer Secret)', 'xelite-repost-engine'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="x_api_consumer_secret" name="x_api_consumer_secret" 
                                       class="regular-text" value="<?php echo esc_attr($credentials ? $credentials['consumer_secret'] : ''); ?>" />
                                <p class="description"><?php _e('Your X API consumer secret from the X Developer Portal.', 'xelite-repost-engine'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="x_api_access_token"><?php _e('Access Token', 'xelite-repost-engine'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="x_api_access_token" name="x_api_access_token" 
                                       class="regular-text" value="<?php echo esc_attr($credentials ? $credentials['access_token'] : ''); ?>" />
                                <p class="description"><?php _e('Your X API access token from the X Developer Portal.', 'xelite-repost-engine'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="x_api_access_token_secret"><?php _e('Access Token Secret', 'xelite-repost-engine'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="x_api_access_token_secret" name="x_api_access_token_secret" 
                                       class="regular-text" value="<?php echo esc_attr($credentials ? $credentials['access_token_secret'] : ''); ?>" />
                                <p class="description"><?php _e('Your X API access token secret from the X Developer Portal.', 'xelite-repost-engine'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="auth-actions">
                        <button type="button" class="button button-primary save-x-credentials">
                            <span class="dashicons dashicons-saved"></span>
                            <?php _e('Save Credentials', 'xelite-repost-engine'); ?>
                        </button>
                        
                        <button type="button" class="button button-secondary test-x-connection" 
                                data-nonce="<?php echo wp_create_nonce('xelite_x_test_connection'); ?>">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Test Connection', 'xelite-repost-engine'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="x-api-help">
                <h4><?php _e('How to get X API credentials:', 'xelite-repost-engine'); ?></h4>
                <ol>
                    <li><?php _e('Go to the <a href="https://developer.twitter.com/" target="_blank">X Developer Portal</a>', 'xelite-repost-engine'); ?></li>
                    <li><?php _e('Create a new app or use an existing one', 'xelite-repost-engine'); ?></li>
                    <li><?php _e('Navigate to "Keys and tokens" section', 'xelite-repost-engine'); ?></li>
                    <li><?php _e('Copy your API Key, API Secret, Access Token, and Access Token Secret', 'xelite-repost-engine'); ?></li>
                    <li><?php _e('Paste them in the fields above and save', 'xelite-repost-engine'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for fetching posts
     */
    public function ajax_fetch_posts() {
        check_ajax_referer('xelite_fetch_posts', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $container = XeliteRepostEngine_Container::instance();
            $processor = $container->get('x_processor');
            
            $result = $processor->fetch_and_store_posts();
            
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
     * AJAX handler for analyzing data
     */
    public function ajax_analyze_data() {
        check_ajax_referer('xelite_analyze_data', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $container = XeliteRepostEngine_Container::instance();
            $processor = $container->get('x_processor');
            
            $processor->analyze_stored_data();
            wp_send_json_success('Data analysis completed successfully');
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * OpenAI API section callback
     */
    public function openai_api_section_callback() {
        echo '<p>' . __('Configure your OpenAI API settings for AI-powered content generation.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * OpenAI API key field callback
     */
    public function openai_api_key_field_callback() {
        $api_key = get_option('xelite_repost_engine_openai_api_key', '');
        ?>
        <input type="password" id="openai_api_key" name="xelite_repost_engine_openai_api_key" 
               class="regular-text" value="<?php echo esc_attr($api_key); ?>" />
        <button type="button" class="button button-secondary test-openai-connection" 
                data-nonce="<?php echo wp_create_nonce('xelite_openai_test_connection'); ?>">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Test Connection', 'xelite-repost-engine'); ?>
        </button>
        <p class="description"><?php _e('Your OpenAI API key for AI content generation. Keep this secure.', 'xelite-repost-engine'); ?></p>
        <?php
    }

    /**
     * OpenAI model field callback
     */
    public function openai_model_field_callback() {
        $current_model = get_option('xelite_repost_engine_openai_model', 'gpt-4');
        $available_models = array(
            'gpt-4' => 'GPT-4 (Most capable)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Fast & capable)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast & cost-effective)'
        );
        ?>
        <select id="openai_model" name="xelite_repost_engine_openai_model">
            <?php foreach ($available_models as $model => $description): ?>
                <option value="<?php echo esc_attr($model); ?>" <?php selected($current_model, $model); ?>>
                    <?php echo esc_html($description); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Default OpenAI model to use for content generation.', 'xelite-repost-engine'); ?></p>
        <?php
    }

    /**
     * OpenAI connection status field callback
     */
    public function openai_connection_status_field_callback() {
        $api_key = get_option('xelite_repost_engine_openai_api_key', '');
        ?>
        <div class="openai-connection-status">
            <?php if (empty($api_key)): ?>
                <div class="connection-status error">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('API Key not configured', 'xelite-repost-engine'); ?>
                </div>
            <?php else: ?>
                <div class="connection-status checking">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Checking connection...', 'xelite-repost-engine'); ?>
                </div>
                <div class="connection-details" style="display: none;">
                    <h4><?php _e('Available Models:', 'xelite-repost-engine'); ?></h4>
                    <ul class="available-models"></ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * OpenAI content section callback
     */
    public function openai_content_section_callback() {
        echo '<p>' . __('Configure parameters for AI content generation.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * OpenAI temperature field callback
     */
    public function openai_temperature_field_callback() {
        $temperature = get_option('xelite_repost_engine_openai_temperature', 0.7);
        ?>
        <input type="range" id="openai_temperature" name="xelite_repost_engine_openai_temperature" 
               min="0" max="2" step="0.1" value="<?php echo esc_attr($temperature); ?>" 
               oninput="document.getElementById('temperature_value').textContent = this.value;" />
        <span id="temperature_value"><?php echo esc_html($temperature); ?></span>
        <div class="temperature-labels">
            <span>Focused (0.0)</span>
            <span>Balanced (1.0)</span>
            <span>Creative (2.0)</span>
        </div>
        <p class="description"><?php _e('Controls creativity in content generation. Higher values = more creative, Lower values = more focused.', 'xelite-repost-engine'); ?></p>
        <?php
    }

    /**
     * OpenAI max tokens field callback
     */
    public function openai_max_tokens_field_callback() {
        $max_tokens = get_option('xelite_repost_engine_openai_max_tokens', 280);
        ?>
        <input type="number" id="openai_max_tokens" name="xelite_repost_engine_openai_max_tokens" 
               min="50" max="4000" step="10" value="<?php echo esc_attr($max_tokens); ?>" />
        <p class="description"><?php _e('Maximum number of tokens to generate per request.', 'xelite-repost-engine'); ?></p>
        <?php
    }

    /**
     * OpenAI content tone field callback
     */
    public function openai_content_tone_field_callback() {
        $content_tone = get_option('xelite_repost_engine_openai_content_tone', 'conversational');
        $available_tones = array(
            'conversational' => 'Conversational',
            'professional' => 'Professional',
            'casual' => 'Casual',
            'enthusiastic' => 'Enthusiastic',
            'informative' => 'Informative',
            'humorous' => 'Humorous',
            'authoritative' => 'Authoritative'
        );
        ?>
        <select id="openai_content_tone" name="xelite_repost_engine_openai_content_tone">
            <?php foreach ($available_tones as $tone => $label): ?>
                <option value="<?php echo esc_attr($tone); ?>" <?php selected($content_tone, $tone); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Default tone for generated content.', 'xelite-repost-engine'); ?></p>
        <?php
    }

    /**
     * OpenAI usage section callback
     */
    public function openai_usage_section_callback() {
        echo '<p>' . __('Monitor and control your OpenAI API usage and costs.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * OpenAI daily limit field callback
     */
    public function openai_daily_limit_field_callback() {
        $daily_limit = get_option('xelite_repost_engine_openai_daily_limit', 0);
        ?>
        <input type="number" id="openai_daily_limit" name="xelite_repost_engine_openai_daily_limit" 
               min="0" max="10000" step="1" value="<?php echo esc_attr($daily_limit); ?>" />
        <p class="description"><?php _e('Maximum number of API calls per day (0 = unlimited).', 'xelite-repost-engine'); ?></p>
        <?php
    }

    /**
     * OpenAI monthly budget field callback
     */
    public function openai_monthly_budget_field_callback() {
        $monthly_budget = get_option('xelite_repost_engine_openai_monthly_budget', 0);
        ?>
        <input type="number" id="openai_monthly_budget" name="xelite_repost_engine_openai_monthly_budget" 
               min="0" max="1000" step="0.01" value="<?php echo esc_attr($monthly_budget); ?>" />
        <p class="description"><?php _e('Maximum monthly spending on OpenAI API (0 = unlimited).', 'xelite-repost-engine'); ?></p>
        <?php
    }

    /**
     * OpenAI usage dashboard field callback
     */
    public function openai_usage_dashboard_field_callback() {
        ?>
        <div class="openai-usage-dashboard">
            <div class="usage-stats">
                <div class="stat-item">
                    <h4><?php _e('Today\'s Usage', 'xelite-repost-engine'); ?></h4>
                    <div class="stat-value" id="today-usage">Loading...</div>
                </div>
                <div class="stat-item">
                    <h4><?php _e('This Month', 'xelite-repost-engine'); ?></h4>
                    <div class="stat-value" id="month-usage">Loading...</div>
                </div>
                <div class="stat-item">
                    <h4><?php _e('Total Cost', 'xelite-repost-engine'); ?></h4>
                    <div class="stat-value" id="total-cost">Loading...</div>
                </div>
            </div>
            <button type="button" class="button button-secondary refresh-usage-stats" 
                    data-nonce="<?php echo wp_create_nonce('xelite_openai_refresh_usage'); ?>">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Refresh Stats', 'xelite-repost-engine'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * OpenAI testing section callback
     */
    public function openai_testing_section_callback() {
        echo '<p>' . __('Test content generation with your current settings.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * OpenAI test generation field callback
     */
    public function openai_test_generation_field_callback() {
        ?>
        <div class="openai-test-generation">
            <div class="test-inputs">
                <label for="test_user_context"><?php _e('Sample User Context:', 'xelite-repost-engine'); ?></label>
                <textarea id="test_user_context" rows="3" class="large-text" placeholder="<?php _e('Enter sample user context (writing style, audience, etc.)', 'xelite-repost-engine'); ?>"></textarea>
                
                <label for="test_patterns"><?php _e('Sample Patterns:', 'xelite-repost-engine'); ?></label>
                <textarea id="test_patterns" rows="3" class="large-text" placeholder="<?php _e('Enter sample repost patterns (JSON format)', 'xelite-repost-engine'); ?>"></textarea>
            </div>
            
            <button type="button" class="button button-primary test-content-generation" 
                    data-nonce="<?php echo wp_create_nonce('xelite_openai_test_generation'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('Generate Test Content', 'xelite-repost-engine'); ?>
            </button>
            
            <div class="test-results" style="display: none;">
                <h4><?php _e('Generated Content:', 'xelite-repost-engine'); ?></h4>
                <div id="generated-content"></div>
                <div class="generation-metrics">
                    <p><strong><?php _e('Tokens Used:', 'xelite-repost-engine'); ?></strong> <span id="tokens-used">-</span></p>
                    <p><strong><?php _e('Generation Time:', 'xelite-repost-engine'); ?></strong> <span id="generation-time">-</span></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for testing OpenAI connection
     */
    public function ajax_test_openai_connection() {
        check_ajax_referer('xelite_openai_test_connection', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $container = XeliteRepostEngine_Container::instance();
            $openai = $container->get('openai');
            
            $result = $openai->test_connection();
            
            if (isset($result['connected']) && $result['connected']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['error'] ?? 'Connection test failed');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for refreshing OpenAI usage stats
     */
    public function ajax_refresh_openai_usage() {
        check_ajax_referer('xelite_openai_refresh_usage', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $container = XeliteRepostEngine_Container::instance();
            $openai = $container->get('openai');
            
            $today_usage = $openai->get_usage(date('Y-m-d'));
            $month_usage = $openai->get_usage(date('Y-m'));
            
            $stats = array(
                'today' => $today_usage,
                'month' => $month_usage,
                'total_cost' => $this->calculate_total_cost($today_usage, $month_usage)
            );
            
            wp_send_json_success($stats);
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for testing content generation
     */
    public function ajax_test_content_generation() {
        check_ajax_referer('xelite_openai_test_generation', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            $user_context = json_decode(stripslashes($_POST['user_context'] ?? '{}'), true);
            $patterns = json_decode(stripslashes($_POST['patterns'] ?? '{}'), true);
            
            if (empty($user_context) && empty($patterns)) {
                wp_send_json_error('User context or patterns are required');
            }
            
            $container = XeliteRepostEngine_Container::instance();
            $openai = $container->get('openai');
            
            $start_time = microtime(true);
            $result = $openai->generate_content($user_context, $patterns);
            $end_time = microtime(true);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                $generation_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
                
                $response = array(
                    'content' => $result,
                    'generation_time' => $generation_time,
                    'tokens_used' => $result['usage']['total_tokens'] ?? 0
                );
                
                wp_send_json_success($response);
            }
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Calculate total cost from usage data
     *
     * @param array $today_usage Today's usage data
     * @param array $month_usage Month's usage data
     * @return float Total cost
     */
    private function calculate_total_cost($today_usage, $month_usage) {
        // Rough cost calculation (this would need to be more sophisticated in production)
        $cost_per_1k_tokens = 0.03; // Approximate cost for GPT-4
        
        $total_tokens = 0;
        if (isset($today_usage['total_usage'])) {
            $total_tokens += $today_usage['total_usage'];
        }
        if (isset($month_usage['total_usage'])) {
            $total_tokens += $month_usage['total_usage'];
        }
        
        return round(($total_tokens / 1000) * $cost_per_1k_tokens, 4);
    }
} 