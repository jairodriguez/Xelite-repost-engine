<?php
/**
 * Plugin Name: Xelite Repost Engine
 * Plugin URI: https://xelite.com/repost-engine
 * Description: A WordPress plugin designed to help digital creators improve their chances of being reposted on X (formerly Twitter). The plugin analyzes repost patterns and uses AI to generate personalized, on-brand content for the user.
 * Version: 1.0.0
 * Author: Xelite
 * Author URI: https://xelite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: xelite-repost-engine
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package XeliteRepostEngine
 * @version 1.0.0
 * @author Xelite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('XELITE_REPOST_ENGINE_VERSION', '1.0.0');
define('XELITE_REPOST_ENGINE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('XELITE_REPOST_ENGINE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('XELITE_REPOST_ENGINE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Version compatibility check
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo 'Xelite Repost Engine requires PHP 7.4 or higher. Please upgrade your PHP version.';
        echo '</p></div>';
    });
    return;
}

if (version_compare(get_bloginfo('version'), '5.8', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo 'Xelite Repost Engine requires WordPress 5.8 or higher. Please upgrade WordPress.';
        echo '</p></div>';
    });
    return;
}

/**
 * Main plugin class
 */
class XeliteRepostEngine {
    
    /**
     * Plugin instance
     *
     * @var XeliteRepostEngine
     */
    private static $instance = null;
    
    /**
     * Plugin version
     *
     * @var string
     */
    public $version;
    
    /**
     * Plugin directory path
     *
     * @var string
     */
    public $plugin_dir;
    
    /**
     * Plugin URL
     *
     * @var string
     */
    public $plugin_url;
    
    /**
     * Service container
     *
     * @var XeliteRepostEngine_Container
     */
    public $container;
    
    /**
     * Get plugin instance
     *
     * @return XeliteRepostEngine
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->version = XELITE_REPOST_ENGINE_VERSION;
        $this->plugin_dir = XELITE_REPOST_ENGINE_PLUGIN_DIR;
        $this->plugin_url = XELITE_REPOST_ENGINE_PLUGIN_URL;
        
        $this->init();
    }
    
    /**
     * Initialize the plugin
     */
    private function init() {
        $this->load_dependencies();
        $this->setup_hooks();
        $this->init_container();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load abstract base class first
        require_once $this->plugin_dir . 'includes/abstracts/class-xelite-repost-engine-abstract-base.php';
        
        // Load interfaces
        require_once $this->plugin_dir . 'includes/interfaces/interface-xelite-repost-engine-api-interface.php';
        require_once $this->plugin_dir . 'includes/interfaces/interface-xelite-repost-engine-database-interface.php';
        require_once $this->plugin_dir . 'includes/interfaces/interface-xelite-repost-engine-user-meta-interface.php';
        
        // Load service container
        require_once $this->plugin_dir . 'includes/class-xelite-repost-engine-container.php';
        
        // Load core classes
        require_once $this->plugin_dir . 'includes/class-xelite-repost-engine-loader.php';
        require_once $this->plugin_dir . 'includes/class-xelite-repost-engine-database.php';
        require_once $this->plugin_dir . 'includes/class-xelite-repost-engine-api.php';
        require_once $this->plugin_dir . 'includes/class-xelite-repost-engine-user-meta.php';
        require_once $this->plugin_dir . 'includes/class-xelite-repost-engine-assets.php';
        
        // Load API classes
        require_once $this->plugin_dir . 'includes/api/class-xelite-repost-engine-x-auth.php';
        require_once $this->plugin_dir . 'includes/api/class-xelite-repost-engine-x-api.php';
        require_once $this->plugin_dir . 'includes/api/class-xelite-repost-engine-x-processor.php';
        
        // Load scraper class
        require_once $this->plugin_dir . 'includes/class-xelite-repost-engine-scraper.php';
        
        // Load admin and public classes
        require_once $this->plugin_dir . 'includes/admin/class-xelite-repost-engine-admin-fields.php';
        require_once $this->plugin_dir . 'includes/admin/class-xelite-repost-engine-admin-settings.php';
        require_once $this->plugin_dir . 'public/class-xelite-repost-engine-public.php';
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin after WordPress is loaded
        add_action('plugins_loaded', array($this, 'init_plugin'));
        
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));
    }
    
    /**
     * Initialize service container
     */
    private function init_container() {
        $this->container = XeliteRepostEngine_Container::instance();
        $this->container->register_default_services();
    }
    
    /**
     * Initialize plugin
     */
    public function init_plugin() {
        // Initialize the loader
        new XeliteRepostEngine_Loader();
        
        // Initialize admin settings
        if (is_admin()) {
            $this->container->get('admin_settings');
        }
        
        // Register cron hooks
        add_action('xelite_scraper_cron', array($this, 'run_scheduled_scraping'));
    }
    
    /**
     * Run scheduled scraping job
     *
     * @param array $accounts Array of account handles
     */
    public function run_scheduled_scraping($accounts) {
        $scraper = $this->container->get('scraper');
        $scraper->run_scheduled_scraping($accounts);
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'xelite-repost-engine',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $database = new XeliteRepostEngine_Database();
        $database->create_tables();
        
        // Upgrade database if needed
        $database->upgrade_database();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'x_api_key' => '',
            'x_api_secret' => '',
            'x_bearer_token' => '',
            'openai_api_key' => '',
            'target_accounts' => array(),
            'scraping_frequency' => 'daily',
            'ai_generation_limit' => 10,
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option('xelite_repost_engine_' . $option) === false) {
                update_option('xelite_repost_engine_' . $option, $value);
            }
        }
    }
}

/**
 * Get plugin instance
 *
 * @return XeliteRepostEngine
 */
function xelite_repost_engine() {
    return XeliteRepostEngine::instance();
}

// Initialize the plugin
xelite_repost_engine(); 