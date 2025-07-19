<?php
/**
 * Main plugin loader class
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin loader class
 */
class XeliteRepostEngine_Loader {
    
    /**
     * Plugin instance
     *
     * @var XeliteRepostEngine
     */
    private $plugin;
    
    /**
     * Service container
     *
     * @var XeliteRepostEngine_Container
     */
    private $container;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin = xelite_repost_engine();
        $this->container = $this->plugin->container;
        $this->init();
    }
    
    /**
     * Initialize the loader
     */
    private function init() {
        // Load admin functionality
        if (is_admin()) {
            $this->load_admin();
        }
        
        // Load public functionality
        $this->load_public();
        
        // Load core functionality
        $this->load_core();
        
        // Setup hooks
        $this->setup_hooks();
    }
    
    /**
     * Load admin functionality
     */
    private function load_admin() {
        // Get admin service from container
        $admin = $this->container->get('admin');
        
        // Add admin menu
        add_action('admin_menu', array($admin, 'add_admin_menu'));
        
        // Admin scripts and styles are handled by the assets class
        
        // Add admin AJAX handlers
        add_action('wp_ajax_xelite_repost_engine_admin_action', array($admin, 'handle_admin_ajax'));
    }
    
    /**
     * Load public functionality
     */
    private function load_public() {
        // Get public service from container
        $public = $this->container->get('public');
        
        // Public scripts and styles are handled by the assets class
        
        // Add shortcodes
        add_action('init', array($public, 'register_shortcodes'));
        
        // Add public AJAX handlers
        add_action('wp_ajax_xelite_repost_engine_public_action', array($public, 'handle_public_ajax'));
        add_action('wp_ajax_nopriv_xelite_repost_engine_public_action', array($public, 'handle_public_ajax'));
    }
    
    /**
     * Load core functionality
     */
    private function load_core() {
        // Get core services from container
        $database = $this->container->get('database');
        $api = $this->container->get('api');
        $user_meta = $this->container->get('user_meta');
        $assets = $this->container->get('assets');
        
        // Setup cron jobs for scraping
        add_action('xelite_repost_engine_scraper_cron', array($this, 'run_scraper_cron'));
        
        // Register cron schedule
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
        
        // Setup REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Plugin activation/deactivation
        register_activation_hook(XELITE_REPOST_ENGINE_PLUGIN_BASENAME, array($this, 'activate'));
        register_deactivation_hook(XELITE_REPOST_ENGINE_PLUGIN_BASENAME, array($this, 'deactivate'));
        
        // WooCommerce integration
        add_action('woocommerce_loaded', array($this, 'init_woocommerce_integration'));
        
        // User profile integration
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
    }
    
    /**
     * Add cron schedules
     *
     * @param array $schedules Existing schedules
     * @return array
     */
    public function add_cron_schedules($schedules) {
        $schedules['xelite_repost_engine_hourly'] = array(
            'interval' => 3600,
            'display' => __('Every Hour (Xelite Repost Engine)', 'xelite-repost-engine'),
        );
        
        $schedules['xelite_repost_engine_daily'] = array(
            'interval' => 86400,
            'display' => __('Daily (Xelite Repost Engine)', 'xelite-repost-engine'),
        );
        
        return $schedules;
    }
    
    /**
     * Run scraper cron job
     */
    public function run_scraper_cron() {
        // Get API service
        $api = $this->container->get('api');
        $database = $this->container->get('database');
        
        // Get target accounts from options
        $target_accounts = $this->plugin->get_option('target_accounts', array());
        
        if (empty($target_accounts)) {
            return;
        }
        
        foreach ($target_accounts as $account) {
            try {
                // Get user tweets
                $tweets = $api->get_user_tweets($account, 50);
                
                if (is_wp_error($tweets)) {
                    continue;
                }
                
                // Process tweets and store repost data
                foreach ($tweets['data'] as $tweet) {
                    $this->process_tweet($tweet, $account);
                }
                
            } catch (Exception $e) {
                error_log('Xelite Repost Engine: Error processing account ' . $account . ': ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Process a tweet for repost data
     *
     * @param array  $tweet Tweet data
     * @param string $account Account handle
     */
    private function process_tweet($tweet, $account) {
        $database = $this->container->get('database');
        $api = $this->container->get('api');
        
        // Check if tweet already exists
        $existing = $database->get_row('reposts', array(
            'original_tweet_id' => $tweet['id'],
        ));
        
        if ($existing) {
            return;
        }
        
        // Get repost count
        $reposts = $api->get_tweet_reposts($tweet['id'], 100);
        $repost_count = 0;
        
        if (!is_wp_error($reposts) && isset($reposts['data'])) {
            $repost_count = count($reposts['data']);
        }
        
        // Store tweet data
        $database->insert('reposts', array(
            'source_handle' => $account,
            'original_tweet_id' => $tweet['id'],
            'original_text' => $tweet['text'],
            'repost_count' => $repost_count,
        ));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Register routes for API endpoints
        register_rest_route('xelite-repost-engine/v1', '/reposts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reposts_endpoint'),
            'permission_callback' => array($this, 'check_api_permissions'),
        ));
        
        register_rest_route('xelite-repost-engine/v1', '/generate-content', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_content_endpoint'),
            'permission_callback' => array($this, 'check_api_permissions'),
        ));
    }
    
    /**
     * Get reposts endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_reposts_endpoint($request) {
        $database = $this->container->get('database');
        
        $params = $request->get_params();
        $limit = isset($params['limit']) ? (int) $params['limit'] : 10;
        $offset = isset($params['offset']) ? (int) $params['offset'] : 0;
        
        $reposts = $database->get('reposts', array(), array('created_at' => 'DESC'), $limit, $offset);
        
        return new WP_REST_Response($reposts, 200);
    }
    
    /**
     * Generate content endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function generate_content_endpoint($request) {
        $api = $this->container->get('api');
        $user_meta = $this->container->get('user_meta');
        
        $params = $request->get_params();
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_REST_Response(array('error' => 'User not authenticated'), 401);
        }
        
        // Get user context
        $context = $user_meta->get_user_context($user_id);
        
        if (!$context['profile_complete']) {
            return new WP_REST_Response(array('error' => 'User profile incomplete'), 400);
        }
        
        // Generate content
        $prompt = $this->build_content_prompt($context, $params);
        $result = $api->generate_content($prompt, array(
            'max_tokens' => 150,
            'temperature' => 0.7,
        ));
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array('error' => $result->get_error_message()), 500);
        }
        
        return new WP_REST_Response(array(
            'content' => $result['choices'][0]['message']['content'],
        ), 200);
    }
    
    /**
     * Check API permissions
     *
     * @return bool
     */
    public function check_api_permissions() {
        return is_user_logged_in();
    }
    
    /**
     * Build content generation prompt
     *
     * @param array $context User context
     * @param array $params Request parameters
     * @return string
     */
    private function build_content_prompt($context, $params) {
        $prompt = "You are a content creator with the following profile:\n\n";
        $prompt .= "Personal Context: {$context['personal-context']}\n";
        $prompt .= "Dream Client: {$context['dream-client']}\n";
        $prompt .= "Writing Style: {$context['writing-style']}\n";
        $prompt .= "Irresistible Offer: {$context['irresistible-offer']}\n";
        $prompt .= "Dream Client Pain Points: {$context['dream-client-pain-points']}\n";
        $prompt .= "Ikigai: {$context['ikigai']}\n";
        $prompt .= "Topic: {$context['topic']}\n\n";
        
        $prompt .= "Generate a tweet that is likely to be reposted by big accounts in your niche. ";
        $prompt .= "The tweet should be engaging, valuable, and aligned with your brand voice. ";
        $prompt .= "Keep it under 280 characters and make it shareable.\n\n";
        
        if (!empty($params['topic'])) {
            $prompt .= "Focus on this specific topic: {$params['topic']}\n\n";
        }
        
        $prompt .= "Tweet:";
        
        return $prompt;
    }
    
    /**
     * Initialize WooCommerce integration
     */
    public function init_woocommerce_integration() {
        // WooCommerce integration will be implemented in future tasks
    }
    
    /**
     * Add user profile fields
     *
     * @param WP_User $user User object
     */
    public function add_user_profile_fields($user) {
        $user_meta = $this->container->get('user_meta');
        $fields = $user_meta->get_required_fields();
        $labels = $user_meta->get_field_labels();
        $descriptions = $user_meta->get_field_descriptions();
        
        echo '<h2>' . __('Xelite Repost Engine Profile', 'xelite-repost-engine') . '</h2>';
        echo '<table class="form-table">';
        
        foreach ($fields as $field) {
            $value = $user_meta->get_user_meta($user->ID, $field);
            $label = $labels[$field];
            $description = $descriptions[$field];
            
            echo '<tr>';
            echo '<th><label for="' . esc_attr($field) . '">' . esc_html($label) . '</label></th>';
            echo '<td>';
            
            if (in_array($field, array('personal-context', 'dream-client-pain-points'))) {
                echo '<textarea name="' . esc_attr($field) . '" id="' . esc_attr($field) . '" rows="4" cols="50">' . esc_textarea($value) . '</textarea>';
            } else {
                echo '<input type="text" name="' . esc_attr($field) . '" id="' . esc_attr($field) . '" value="' . esc_attr($value) . '" class="regular-text" />';
            }
            
            echo '<p class="description">' . esc_html($description) . '</p>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Save user profile fields
     *
     * @param int $user_id User ID
     */
    public function save_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        $user_meta = $this->container->get('user_meta');
        $fields = $user_meta->get_required_fields();
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                $user_meta->update_user_meta($user_id, $field, $value);
            }
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Schedule cron job
        if (!wp_next_scheduled('xelite_repost_engine_scraper_cron')) {
            wp_schedule_event(time(), 'xelite_repost_engine_daily', 'xelite_repost_engine_scraper_cron');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron job
        wp_clear_scheduled_hook('xelite_repost_engine_scraper_cron');
    }
} 