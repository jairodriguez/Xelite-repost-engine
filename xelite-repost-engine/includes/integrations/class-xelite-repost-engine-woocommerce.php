<?php
/**
 * WooCommerce Integration for Repost Intelligence
 *
 * Handles WooCommerce subscription integration and feature access control
 * based on subscription tiers.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Integration Class
 */
class XeliteRepostEngine_WooCommerce extends XeliteRepostEngine_Abstract_Base {

    /**
     * Database instance
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;

    /**
     * User meta instance
     *
     * @var XeliteRepostEngine_User_Meta
     */
    private $user_meta;

    /**
     * Logger instance
     *
     * @var XeliteRepostEngine_Logger
     */
    private $logger;

    /**
     * Integration status
     *
     * @var bool
     */
    private $is_active = false;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Database $database Database service.
     * @param XeliteRepostEngine_User_Meta $user_meta User meta service.
     * @param XeliteRepostEngine_Logger $logger Logger service.
     */
    public function __construct($database, $user_meta, $logger = null) {
        $this->database = $database;
        $this->user_meta = $user_meta;
        $this->logger = $logger;
        
        $this->init();
    }

    /**
     * Initialize the integration
     */
    protected function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            $this->log('warning', 'WooCommerce or WooCommerce Subscriptions not active');
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        $this->is_active = true;
        $this->log('info', 'WooCommerce integration activated');

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Subscription lifecycle event hooks
        add_action('woocommerce_subscription_status_updated', array($this, 'subscription_status_changed'), 10, 3);
        add_action('woocommerce_subscription_status_changed', array($this, 'subscription_status_changed'), 10, 3);
        add_action('woocommerce_subscription_created', array($this, 'subscription_created'), 10, 2);
        add_action('woocommerce_subscription_cancelled', array($this, 'subscription_cancelled'), 10, 1);
        add_action('woocommerce_subscription_expired', array($this, 'subscription_expired'), 10, 1);
        add_action('woocommerce_subscription_renewed', array($this, 'subscription_renewed'), 10, 2);
        add_action('woocommerce_subscription_payment_complete', array($this, 'subscription_payment_complete'), 10, 1);
        add_action('woocommerce_subscription_payment_failed', array($this, 'subscription_payment_failed'), 10, 1);
        
        // User registration and login hooks
        add_action('user_register', array($this, 'user_registered'));
        add_action('wp_login', array($this, 'user_logged_in'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'check_user_access'));
        add_action('wp_ajax_xelite_check_subscription', array($this, 'ajax_check_subscription'));
        add_action('wp_ajax_xelite_refresh_all_subscriptions', array($this, 'ajax_refresh_all_subscriptions'));
        add_action('wp_ajax_xelite_clear_all_caches', array($this, 'ajax_clear_all_caches'));
        add_action('wp_ajax_xelite_get_subscription_history', array($this, 'ajax_get_subscription_history'));
        add_action('wp_ajax_xelite_send_subscription_email', array($this, 'ajax_send_subscription_email'));
        
        // Feature access hooks
        add_action('xelite_before_feature_access', array($this, 'check_feature_access'));
        
        // Settings hooks
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        // Webhook endpoints
        add_action('rest_api_init', array($this, 'register_webhook_endpoints'));
        
        // Email hooks
        add_action('xelite_subscription_status_changed', array($this, 'send_subscription_status_email'), 10, 4);
        
        // Database table creation
        add_action('init', array($this, 'create_subscription_log_table'));
    }

    /**
     * Check if WooCommerce and WooCommerce Subscriptions are active
     *
     * @return bool True if both plugins are active.
     */
    public function is_woocommerce_active() {
        return class_exists('WooCommerce') && class_exists('WC_Subscriptions');
    }

    /**
     * Display admin notice for missing WooCommerce dependencies
     */
    public function woocommerce_missing_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $message = '';
        
        if (!class_exists('WooCommerce')) {
            $message .= '<strong>WooCommerce</strong> plugin is required for Repost Intelligence to function properly. ';
        }
        
        if (!class_exists('WC_Subscriptions')) {
            $message .= '<strong>WooCommerce Subscriptions</strong> plugin is required for subscription-based feature access. ';
        }

        if ($message) {
            echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
        }
    }

    /**
     * Handle subscription status changes
     *
     * @param WC_Subscription $subscription Subscription object.
     * @param string $new_status New subscription status.
     * @param string $old_status Old subscription status.
     */
    public function subscription_status_changed($subscription, $new_status, $old_status) {
        $user_id = $subscription->get_user_id();
        
        $this->log('info', "Subscription status changed for user {$user_id}: {$old_status} -> {$new_status}");
        
        // Clear user cache immediately
        $this->clear_user_cache($user_id);
        
        if ($new_status === 'active' && $old_status !== 'active') {
            $this->activate_user_features($user_id, $subscription);
        } elseif ($new_status !== 'active' && $old_status === 'active') {
            $this->deactivate_user_features($user_id);
        }
        
        // Update user meta with current subscription status
        update_user_meta($user_id, 'xelite_subscription_status', $new_status);
        update_user_meta($user_id, 'xelite_subscription_updated', current_time('mysql'));
        
        // Log detailed subscription change
        $this->log_subscription_change($user_id, $subscription, $old_status, $new_status);
        
        // Trigger action for other plugins/themes
        do_action('xelite_subscription_status_changed', $user_id, $subscription, $old_status, $new_status);
    }

    /**
     * Log detailed subscription change information
     *
     * @param int $user_id User ID.
     * @param WC_Subscription $subscription Subscription object.
     * @param string $old_status Old status.
     * @param string $new_status New status.
     */
    private function log_subscription_change($user_id, $subscription, $old_status, $new_status) {
        $change_data = array(
            'subscription_id' => $subscription->get_id(),
            'old_status' => $old_status,
            'new_status' => $new_status,
            'subscription_total' => $subscription->get_total(),
            'next_payment_date' => $subscription->get_date('next_payment'),
            'created_date' => $subscription->get_date('date_created'),
            'product_ids' => array()
        );
        
        // Get product information
        foreach ($subscription->get_items() as $item) {
            $change_data['product_ids'][] = $item->get_product_id();
        }
        
        $this->log_user_activity($user_id, 'subscription_status_changed', $change_data);
    }

    /**
     * Activate user features based on subscription
     *
     * @param int $user_id User ID.
     * @param WC_Subscription $subscription Subscription object.
     */
    private function activate_user_features($user_id, $subscription) {
        $tier = $this->get_user_subscription_tier($user_id, $subscription);
        
        $this->log('info', "Activating features for user {$user_id} with tier: {$tier}");
        
        // Update user meta with tier information
        update_user_meta($user_id, 'xelite_subscription_tier', $tier);
        update_user_meta($user_id, 'xelite_features_activated', current_time('mysql'));
        
        // Set user capabilities based on tier
        $this->set_user_capabilities($user_id, $tier);
        
        // Log the activation
        $this->log_user_activity($user_id, 'subscription_activated', array(
            'tier' => $tier,
            'subscription_id' => $subscription->get_id()
        ));
    }

    /**
     * Deactivate user features
     *
     * @param int $user_id User ID.
     */
    private function deactivate_user_features($user_id) {
        $this->log('info', "Deactivating features for user {$user_id}");
        
        // Remove tier and capabilities
        update_user_meta($user_id, 'xelite_subscription_tier', 'none');
        update_user_meta($user_id, 'xelite_features_deactivated', current_time('mysql'));
        
        // Remove user capabilities
        $this->remove_user_capabilities($user_id);
        
        // Log the deactivation
        $this->log_user_activity($user_id, 'subscription_deactivated', array());
    }

    /**
     * Get user subscription tier with caching
     *
     * @param int $user_id User ID.
     * @param WC_Subscription|null $subscription Optional subscription object.
     * @param bool $force_refresh Force refresh cache.
     * @return string Subscription tier.
     */
    public function get_user_subscription_tier($user_id, $subscription = null, $force_refresh = false) {
        // Check cache first (unless force refresh is requested)
        if (!$force_refresh) {
            $cached_tier = wp_cache_get("xelite_user_tier_{$user_id}", 'xelite_subscriptions');
            if ($cached_tier !== false) {
                return $cached_tier;
            }
        }
        
        if (!$subscription) {
            $subscriptions = wcs_get_users_subscriptions($user_id);
            
            foreach ($subscriptions as $sub) {
                if ($sub->has_status('active')) {
                    $subscription = $sub;
                    break;
                }
            }
        }
        
        if (!$subscription || !$subscription->has_status('active')) {
            $tier = 'none';
        } else {
            $tier_mapping = $this->get_tier_mapping();
            $tier = 'none';
            
            foreach ($subscription->get_items() as $item) {
                $product_id = $item->get_product_id();
                
                if (isset($tier_mapping[$product_id])) {
                    $tier = $tier_mapping[$product_id];
                    break;
                }
            }
        }
        
        // Cache the result for 5 minutes
        wp_cache_set("xelite_user_tier_{$user_id}", $tier, 'xelite_subscriptions', 300);
        
        return $tier;
    }

    /**
     * Get tier mapping from settings
     *
     * @return array Tier mapping.
     */
    public function get_tier_mapping() {
        $mapping = array();
        
        // Get individual product ID settings
        $basic_id = get_option('xelite_basic_product_id', '');
        $premium_id = get_option('xelite_premium_product_id', '');
        $enterprise_id = get_option('xelite_enterprise_product_id', '');
        
        if (!empty($basic_id)) {
            $mapping[$basic_id] = 'basic';
        }
        if (!empty($premium_id)) {
            $mapping[$premium_id] = 'premium';
        }
        if (!empty($enterprise_id)) {
            $mapping[$enterprise_id] = 'enterprise';
        }
        
        // Fallback to legacy mapping if no individual settings
        if (empty($mapping)) {
            $legacy_mapping = get_option('xelite_woocommerce_tier_mapping', array());
            if (!empty($legacy_mapping)) {
                $mapping = $legacy_mapping;
            }
        }
        
        return $mapping;
    }

    /**
     * Get available subscription products
     *
     * @return array Array of subscription products.
     */
    public function get_subscription_products() {
        $products = array();
        
        if (!class_exists('WC_Product_Subscription')) {
            return $products;
        }
        
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_subscription',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                
                if ($product && $product->is_type('subscription')) {
                    $products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price' => $product->get_price_html(),
                        'type' => 'subscription'
                    );
                }
            }
        }
        
        wp_reset_postdata();
        
        return $products;
    }

    /**
     * Set user capabilities based on tier
     *
     * @param int $user_id User ID.
     * @param string $tier Subscription tier.
     */
    private function set_user_capabilities($user_id, $tier) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        // Remove existing capabilities first
        $this->remove_user_capabilities($user_id);
        
        // Define capabilities by tier
        $capabilities = $this->get_tier_capabilities($tier);
        
        foreach ($capabilities as $capability) {
            $user->add_cap($capability);
        }
        
        $this->log('info', "Set capabilities for user {$user_id} with tier {$tier}: " . implode(', ', $capabilities));
    }

    /**
     * Remove user capabilities
     *
     * @param int $user_id User ID.
     */
    private function remove_user_capabilities($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        $all_capabilities = array(
            'xelite_view_patterns',
            'xelite_generate_content_limited',
            'xelite_generate_content_unlimited',
            'xelite_scheduling',
            'xelite_multi_account',
            'xelite_analytics',
            'xelite_advanced_ai'
        );
        
        foreach ($all_capabilities as $capability) {
            $user->remove_cap($capability);
        }
        
        $this->log('info', "Removed capabilities for user {$user_id}");
    }

    /**
     * Get capabilities for a specific tier
     *
     * @param string $tier Subscription tier.
     * @return array Array of capabilities.
     */
    private function get_tier_capabilities($tier) {
        $capabilities = array(
            'basic' => array(
                'xelite_view_patterns',
                'xelite_generate_content_limited'
            ),
            'premium' => array(
                'xelite_view_patterns',
                'xelite_generate_content_unlimited',
                'xelite_scheduling',
                'xelite_analytics'
            ),
            'enterprise' => array(
                'xelite_view_patterns',
                'xelite_generate_content_unlimited',
                'xelite_scheduling',
                'xelite_multi_account',
                'xelite_analytics',
                'xelite_advanced_ai'
            )
        );
        
        return isset($capabilities[$tier]) ? $capabilities[$tier] : array();
    }

    /**
     * Check if user can access a specific feature
     *
     * @param string $feature Feature name.
     * @param int|null $user_id User ID (defaults to current user).
     * @return bool True if user can access the feature.
     */
    public function can_user_access_feature($feature, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Check if WooCommerce integration is active
        if (!$this->is_active) {
            return false;
        }
        
        // Check user capabilities
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Map features to capabilities
        $feature_capabilities = array(
            'view_patterns' => 'xelite_view_patterns',
            'generate_content' => 'xelite_generate_content_unlimited',
            'generate_content_limited' => 'xelite_generate_content_limited',
            'scheduling' => 'xelite_scheduling',
            'multi_account' => 'xelite_multi_account',
            'analytics' => 'xelite_analytics',
            'advanced_ai' => 'xelite_advanced_ai'
        );
        
        if (!isset($feature_capabilities[$feature])) {
            return false;
        }
        
        $capability = $feature_capabilities[$feature];
        
        // Check if user has the capability
        if (!$user->has_cap($capability)) {
            $this->log('info', "User {$user_id} denied access to feature: {$feature}");
            return false;
        }
        
        // Additional checks for limited features
        if ($feature === 'generate_content_limited') {
            return $this->check_content_generation_limit($user_id);
        }
        
        return true;
    }

    /**
     * Helper method for easy feature access checking
     *
     * @param string $feature_name Feature name to check.
     * @param int|null $user_id User ID (defaults to current user).
     * @return bool True if user can access the feature.
     */
    public function can_access_feature($feature_name, $user_id = null) {
        return $this->can_user_access_feature($feature_name, $user_id);
    }

    /**
     * Get user's current subscription status
     *
     * @param int|null $user_id User ID (defaults to current user).
     * @return array Subscription status information.
     */
    public function get_user_subscription_status($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array(
                'has_subscription' => false,
                'tier' => 'none',
                'status' => 'none',
                'expires_at' => null,
                'features' => array(),
                'limits' => array()
            );
        }
        
        $tier = $this->get_user_subscription_tier($user_id);
        $status = get_user_meta($user_id, 'xelite_subscription_status', true);
        
        // Get subscription expiration date
        $subscriptions = wcs_get_users_subscriptions($user_id);
        $expires_at = null;
        
        foreach ($subscriptions as $subscription) {
            if ($subscription->has_status('active')) {
                $expires_at = $subscription->get_date('next_payment');
                break;
            }
        }
        
        return array(
            'has_subscription' => $tier !== 'none',
            'tier' => $tier,
            'status' => $status,
            'expires_at' => $expires_at,
            'features' => $this->get_user_features($user_id),
            'limits' => $this->get_user_limits($user_id)
        );
    }

    /**
     * Check if user has active subscription
     *
     * @param int|null $user_id User ID (defaults to current user).
     * @return bool True if user has active subscription.
     */
    public function has_active_subscription($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $tier = $this->get_user_subscription_tier($user_id);
        return $tier !== 'none';
    }

    /**
     * Get user's subscription tier
     *
     * @param int|null $user_id User ID (defaults to current user).
     * @return string Subscription tier.
     */
    public function get_user_tier($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return 'none';
        }
        
        return $this->get_user_subscription_tier($user_id);
    }

    /**
     * Clear user subscription cache
     *
     * @param int $user_id User ID.
     */
    public function clear_user_cache($user_id) {
        wp_cache_delete("xelite_user_tier_{$user_id}", 'xelite_subscriptions');
        $this->log('info', "Cleared cache for user {$user_id}");
    }

    /**
     * Refresh user subscription data
     *
     * @param int $user_id User ID.
     */
    public function refresh_user_subscription($user_id) {
        $this->clear_user_cache($user_id);
        $tier = $this->get_user_subscription_tier($user_id, null, true);
        
        // Update user meta
        update_user_meta($user_id, 'xelite_subscription_tier', $tier);
        update_user_meta($user_id, 'xelite_subscription_updated', current_time('mysql'));
        
        // Update capabilities
        if ($tier === 'none') {
            $this->remove_user_capabilities($user_id);
        } else {
            $this->set_user_capabilities($user_id, $tier);
        }
        
        $this->log('info', "Refreshed subscription data for user {$user_id}: {$tier}");
    }

    /**
     * Check content generation limit for user tier
     *
     * @param int $user_id User ID.
     * @return bool True if user can generate more content.
     */
    private function check_content_generation_limit($user_id) {
        $tier = get_user_meta($user_id, 'xelite_subscription_tier', true);
        
        if ($tier === 'none') {
            return false;
        }
        
        // Get tier-specific limit
        $limit_option = "xelite_{$tier}_generation_limit";
        $monthly_limit = get_option($limit_option, $this->get_default_generation_limit($tier));
        
        // Unlimited for enterprise or -1 setting
        if ($monthly_limit === -1) {
            return true;
        }
        
        $current_month = date('Y-m');
        
        // Get current month's generation count
        $generation_count = get_user_meta($user_id, "xelite_generation_count_{$current_month}", true);
        $generation_count = intval($generation_count);
        
        return $generation_count < $monthly_limit;
    }

    /**
     * Get default generation limit for tier
     *
     * @param string $tier Subscription tier.
     * @return int Default generation limit.
     */
    private function get_default_generation_limit($tier) {
        $defaults = array(
            'basic' => 10,
            'premium' => 100,
            'enterprise' => -1 // Unlimited
        );
        
        return isset($defaults[$tier]) ? $defaults[$tier] : 10;
    }

    /**
     * Increment content generation count
     *
     * @param int $user_id User ID.
     */
    public function increment_generation_count($user_id) {
        $current_month = date('Y-m');
        $count_key = "xelite_generation_count_{$current_month}";
        
        $current_count = get_user_meta($user_id, $count_key, true);
        $current_count = intval($current_count);
        
        update_user_meta($user_id, $count_key, $current_count + 1);
        
        $new_count = $current_count + 1;
        $this->log('info', "Incremented generation count for user {$user_id}: {$new_count}");
    }

    /**
     * Handle user registration
     *
     * @param int $user_id User ID.
     */
    public function user_registered($user_id) {
        $this->log('info', "New user registered: {$user_id}");
        
        // Check if user has any active subscriptions
        $tier = $this->get_user_subscription_tier($user_id);
        if ($tier !== 'none') {
            $this->activate_user_features($user_id, null);
        }
    }

    /**
     * Handle user login
     *
     * @param string $user_login User login.
     * @param WP_User $user User object.
     */
    public function user_logged_in($user_login, $user) {
        $this->log('info', "User logged in: {$user->ID}");
        
        // Check subscription status on login
        $tier = $this->get_user_subscription_tier($user->ID);
        $current_tier = get_user_meta($user->ID, 'xelite_subscription_tier', true);
        
        if ($tier !== $current_tier) {
            if ($tier === 'none') {
                $this->deactivate_user_features($user->ID);
            } else {
                $this->activate_user_features($user->ID, null);
            }
        }
    }

    /**
     * Check user access on admin pages
     */
    public function check_user_access() {
        if (!is_admin()) {
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }
        
        // Check if user has any subscription access
        $tier = get_user_meta($user_id, 'xelite_subscription_tier', true);
        
        if (empty($tier) || $tier === 'none') {
            // Show upgrade notice
            add_action('admin_notices', array($this, 'upgrade_notice'));
        }
    }

    /**
     * Display upgrade notice for users without subscription
     */
    public function upgrade_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $message = 'To access Repost Intelligence features, please <a href="' . admin_url('admin.php?page=xelite-woocommerce-settings') . '">configure your subscription</a> or contact support.';
        
        echo '<div class="notice notice-warning"><p>' . $message . '</p></div>';
    }

    /**
     * AJAX check subscription status
     */
    public function ajax_check_subscription() {
        check_ajax_referer('xelite_woocommerce_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $tier = $this->get_user_subscription_tier($user_id);
        $status = get_user_meta($user_id, 'xelite_subscription_status', true);
        
        $response = array(
            'tier' => $tier,
            'status' => $status,
            'features' => $this->get_user_features($user_id),
            'limits' => $this->get_user_limits($user_id)
        );
        
        wp_send_json_success($response);
    }

    /**
     * AJAX refresh all user subscriptions
     */
    public function ajax_refresh_all_subscriptions() {
        check_ajax_referer('xelite_woocommerce_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $users = get_users(array('fields' => 'ID'));
        $refreshed_count = 0;
        
        foreach ($users as $user_id) {
            $this->refresh_user_subscription($user_id);
            $refreshed_count++;
        }
        
        wp_send_json_success(array('count' => $refreshed_count));
    }

    /**
     * AJAX clear all caches
     */
    public function ajax_clear_all_caches() {
        check_ajax_referer('xelite_woocommerce_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $users = get_users(array('fields' => 'ID'));
        $cleared_count = 0;
        
        foreach ($users as $user_id) {
            $this->clear_user_cache($user_id);
            $cleared_count++;
        }
        
        wp_send_json_success(array('count' => $cleared_count));
    }

    /**
     * AJAX get subscription history
     */
    public function ajax_get_subscription_history() {
        check_ajax_referer('xelite_woocommerce_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        $history = $this->get_subscription_history($user_id, $limit, $offset);
        
        wp_send_json_success($history);
    }

    /**
     * AJAX send subscription email
     */
    public function ajax_send_subscription_email() {
        check_ajax_referer('xelite_woocommerce_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $email_type = isset($_POST['email_type']) ? sanitize_text_field($_POST['email_type']) : '';
        
        if (!$user_id || !$email_type) {
            wp_send_json_error('Invalid parameters');
        }
        
        $result = $this->send_manual_subscription_email($user_id, $email_type);
        
        if ($result) {
            wp_send_json_success('Email sent successfully');
        } else {
            wp_send_json_error('Failed to send email');
        }
    }

    /**
     * Register webhook endpoints
     */
    public function register_webhook_endpoints() {
        register_rest_route('xelite/v1', '/subscription-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => array($this, 'verify_webhook_signature'),
            'args' => array(
                'event_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'subscription_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'data' => array(
                    'required' => false,
                    'type' => 'object'
                )
            )
        ));
    }

    /**
     * Handle webhook requests
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function handle_webhook($request) {
        $event_type = $request->get_param('event_type');
        $subscription_id = $request->get_param('subscription_id');
        $user_id = $request->get_param('user_id');
        $status = $request->get_param('status');
        $data = $request->get_param('data');
        
        $this->log('info', "Webhook received: {$event_type} for subscription {$subscription_id}");
        
        // Get subscription object
        $subscription = wcs_get_subscription($subscription_id);
        if (!$subscription) {
            return new WP_REST_Response(array('error' => 'Subscription not found'), 404);
        }
        
        // Handle different event types
        switch ($event_type) {
            case 'subscription_created':
                $this->subscription_created($subscription, $data ?: array());
                break;
            case 'subscription_cancelled':
                $this->subscription_cancelled($subscription);
                break;
            case 'subscription_expired':
                $this->subscription_expired($subscription);
                break;
            case 'subscription_renewed':
                $renewal_order = isset($data['renewal_order_id']) ? wc_get_order($data['renewal_order_id']) : null;
                $this->subscription_renewed($subscription, $renewal_order);
                break;
            case 'subscription_payment_complete':
                $this->subscription_payment_complete($subscription);
                break;
            case 'subscription_payment_failed':
                $this->subscription_payment_failed($subscription);
                break;
            case 'subscription_status_changed':
                $old_status = isset($data['old_status']) ? $data['old_status'] : null;
                $this->subscription_status_changed($subscription, $status, $old_status);
                break;
            default:
                return new WP_REST_Response(array('error' => 'Unknown event type'), 400);
        }
        
        return new WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Verify webhook signature
     *
     * @param WP_REST_Request $request Request object.
     * @return bool True if signature is valid.
     */
    public function verify_webhook_signature($request) {
        $webhook_secret = get_option('xelite_webhook_secret', '');
        
        if (empty($webhook_secret)) {
            return true; // Allow if no secret is set
        }
        
        $signature = $request->get_header('X-Xelite-Signature');
        if (empty($signature)) {
            return false;
        }
        
        $payload = $request->get_body();
        $expected_signature = hash_hmac('sha256', $payload, $webhook_secret);
        
        return hash_equals($expected_signature, $signature);
    }

    /**
     * Get user features based on tier
     *
     * @param int $user_id User ID.
     * @return array User features.
     */
    private function get_user_features($user_id) {
        $tier = get_user_meta($user_id, 'xelite_subscription_tier', true);
        
        $features = array(
            'basic' => array(
                'view_patterns' => true,
                'generate_content' => true,
                'scheduling' => false,
                'multi_account' => false,
                'analytics' => false,
                'advanced_ai' => false
            ),
            'premium' => array(
                'view_patterns' => true,
                'generate_content' => true,
                'scheduling' => true,
                'multi_account' => false,
                'analytics' => true,
                'advanced_ai' => false
            ),
            'enterprise' => array(
                'view_patterns' => true,
                'generate_content' => true,
                'scheduling' => true,
                'multi_account' => true,
                'analytics' => true,
                'advanced_ai' => true
            )
        );
        
        return isset($features[$tier]) ? $features[$tier] : array();
    }

    /**
     * Get user limits based on tier
     *
     * @param int $user_id User ID.
     * @return array User limits.
     */
    public function get_user_limits($user_id) {
        $tier = get_user_meta($user_id, 'xelite_subscription_tier', true);
        $current_month = date('Y-m');
        $current_generations = intval(get_user_meta($user_id, "xelite_generation_count_{$current_month}", true));
        
        if ($tier === 'none') {
            return array(
                'monthly_generations' => 0,
                'current_generations' => 0
            );
        }
        
        // Get tier-specific limit from settings
        $limit_option = "xelite_{$tier}_generation_limit";
        $monthly_limit = get_option($limit_option, $this->get_default_generation_limit($tier));
        
        return array(
            'monthly_generations' => $monthly_limit,
            'current_generations' => $current_generations,
            'remaining_generations' => $monthly_limit === -1 ? -1 : max(0, $monthly_limit - $current_generations)
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'xelite_woocommerce_settings',
            'xelite_basic_product_id',
            array($this, 'sanitize_product_id')
        );
        register_setting(
            'xelite_woocommerce_settings',
            'xelite_premium_product_id',
            array($this, 'sanitize_product_id')
        );
        register_setting(
            'xelite_woocommerce_settings',
            'xelite_enterprise_product_id',
            array($this, 'sanitize_product_id')
        );
        register_setting(
            'xelite_woocommerce_settings',
            'xelite_basic_generation_limit',
            array($this, 'sanitize_generation_limit')
        );
        register_setting(
            'xelite_woocommerce_settings',
            'xelite_premium_generation_limit',
            array($this, 'sanitize_generation_limit')
        );
        register_setting(
            'xelite_woocommerce_settings',
            'xelite_enterprise_generation_limit',
            array($this, 'sanitize_generation_limit')
        );
        register_setting(
            'xelite_woocommerce_settings',
            'xelite_woocommerce_tier_mapping',
            array($this, 'sanitize_tier_mapping')
        );
        register_setting(
            'xelite_woocommerce_settings',
            'xelite_webhook_secret',
            array($this, 'sanitize_webhook_secret')
        );
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'xelite-repost-engine',
            __('WooCommerce Settings', 'xelite-repost-engine'),
            __('WooCommerce', 'xelite-repost-engine'),
            'manage_options',
            'xelite-woocommerce-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $tier_mapping = get_option('xelite_woocommerce_tier_mapping', array());
        $subscription_products = $this->get_subscription_products();
        ?>
        <div class="wrap">
            <h1><?php _e('WooCommerce Integration Settings', 'xelite-repost-engine'); ?></h1>
            
            <?php if (!$this->is_woocommerce_active()): ?>
                <div class="notice notice-error">
                    <p><?php _e('WooCommerce and WooCommerce Subscriptions must be installed and activated for this integration to work.', 'xelite-repost-engine'); ?></p>
                </div>
            <?php else: ?>
                <div class="xelite-woocommerce-settings">
                    <div class="xelite-settings-section">
                        <h2><?php _e('Subscription Product Mapping', 'xelite-repost-engine'); ?></h2>
                        <p><?php _e('Map your WooCommerce subscription products to subscription tiers to control feature access:', 'xelite-repost-engine'); ?></p>
                        
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('xelite_woocommerce_settings');
                            do_settings_sections('xelite_woocommerce_settings');
                            ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Available Subscription Products', 'xelite-repost-engine'); ?></th>
                                    <td>
                                        <?php if (!empty($subscription_products)): ?>
                                            <ul class="xelite-product-list">
                                                <?php foreach ($subscription_products as $product): ?>
                                                    <li>
                                                        <strong><?php echo esc_html($product['name']); ?></strong>
                                                        (ID: <?php echo esc_html($product['id']); ?>)
                                                        - <?php echo esc_html($product['price']); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="description"><?php _e('No subscription products found. Please create subscription products in WooCommerce first.', 'xelite-repost-engine'); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Tier Mapping Configuration', 'xelite-repost-engine'); ?></th>
                                    <td>
                                        <div class="xelite-tier-mapping">
                                            <div class="xelite-tier-row">
                                                <label for="basic_product_id"><?php _e('Basic Tier Product ID:', 'xelite-repost-engine'); ?></label>
                                                <input type="number" id="basic_product_id" name="xelite_basic_product_id" value="<?php echo esc_attr($tier_mapping['basic_product_id'] ?? ''); ?>" class="regular-text">
                                                <p class="description"><?php _e('Product ID for Basic subscription tier', 'xelite-repost-engine'); ?></p>
                                            </div>
                                            
                                            <div class="xelite-tier-row">
                                                <label for="premium_product_id"><?php _e('Premium Tier Product ID:', 'xelite-repost-engine'); ?></label>
                                                <input type="number" id="premium_product_id" name="xelite_premium_product_id" value="<?php echo esc_attr($tier_mapping['premium_product_id'] ?? ''); ?>" class="regular-text">
                                                <p class="description"><?php _e('Product ID for Premium subscription tier', 'xelite-repost-engine'); ?></p>
                                            </div>
                                            
                                            <div class="xelite-tier-row">
                                                <label for="enterprise_product_id"><?php _e('Enterprise Tier Product ID:', 'xelite-repost-engine'); ?></label>
                                                <input type="number" id="enterprise_product_id" name="xelite_enterprise_product_id" value="<?php echo esc_attr($tier_mapping['enterprise_product_id'] ?? ''); ?>" class="regular-text">
                                                <p class="description"><?php _e('Product ID for Enterprise subscription tier', 'xelite-repost-engine'); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Feature Limits', 'xelite-repost-engine'); ?></th>
                                    <td>
                                        <div class="xelite-feature-limits">
                                            <h4><?php _e('Monthly Content Generation Limits', 'xelite-repost-engine'); ?></h4>
                                            <div class="xelite-limit-row">
                                                <label for="basic_limit"><?php _e('Basic Tier:', 'xelite-repost-engine'); ?></label>
                                                <input type="number" id="basic_limit" name="xelite_basic_generation_limit" value="<?php echo esc_attr(get_option('xelite_basic_generation_limit', 10)); ?>" min="1" class="small-text">
                                                <span><?php _e('generations per month', 'xelite-repost-engine'); ?></span>
                                            </div>
                                            <div class="xelite-limit-row">
                                                <label for="premium_limit"><?php _e('Premium Tier:', 'xelite-repost-engine'); ?></label>
                                                <input type="number" id="premium_limit" name="xelite_premium_generation_limit" value="<?php echo esc_attr(get_option('xelite_premium_generation_limit', 100)); ?>" min="1" class="small-text">
                                                <span><?php _e('generations per month', 'xelite-repost-engine'); ?></span>
                                            </div>
                                            <div class="xelite-limit-row">
                                                <label for="enterprise_limit"><?php _e('Enterprise Tier:', 'xelite-repost-engine'); ?></label>
                                                <input type="number" id="enterprise_limit" name="xelite_enterprise_generation_limit" value="<?php echo esc_attr(get_option('xelite_enterprise_generation_limit', -1)); ?>" min="-1" class="small-text">
                                                <span><?php _e('generations per month (-1 for unlimited)', 'xelite-repost-engine'); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php submit_button(__('Save Settings', 'xelite-repost-engine')); ?>
                        </form>
                    </div>
                    
                    <div class="xelite-settings-section">
                        <h2><?php _e('Subscription Management', 'xelite-repost-engine'); ?></h2>
                        <p><?php _e('Manage user subscriptions and clear caches:', 'xelite-repost-engine'); ?></p>
                        
                        <div class="xelite-admin-actions">
                            <button type="button" id="refresh-all-subscriptions" class="button button-secondary">
                                <?php _e('Refresh All User Subscriptions', 'xelite-repost-engine'); ?>
                            </button>
                            <button type="button" id="clear-all-caches" class="button button-secondary">
                                <?php _e('Clear All Caches', 'xelite-repost-engine'); ?>
                            </button>
                        </div>
                        
                        <div id="refresh-status" style="display: none;">
                            <p class="xelite-status-message"></p>
                        </div>
                    </div>

                    <div class="xelite-settings-section">
                        <h2><?php _e('Webhook Configuration', 'xelite-repost-engine'); ?></h2>
                        <p><?php _e('Configure webhook settings for external integrations:', 'xelite-repost-engine'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Webhook URL', 'xelite-repost-engine'); ?></th>
                                <td>
                                    <code><?php echo esc_url(rest_url('xelite/v1/subscription-webhook')); ?></code>
                                    <p class="description"><?php _e('Use this URL to receive subscription events from external systems.', 'xelite-repost-engine'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Webhook Secret', 'xelite-repost-engine'); ?></th>
                                <td>
                                    <input type="text" name="xelite_webhook_secret" value="<?php echo esc_attr(get_option('xelite_webhook_secret', '')); ?>" class="regular-text">
                                    <p class="description"><?php _e('Secret key for webhook signature verification. Leave empty to disable signature checking.', 'xelite-repost-engine'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="xelite-settings-section">
                        <h2><?php _e('Subscription History', 'xelite-repost-engine'); ?></h2>
                        <p><?php _e('View subscription events and history:', 'xelite-repost-engine'); ?></p>
                        
                        <div class="xelite-history-filters">
                            <select id="history-user-filter">
                                <option value="0"><?php _e('All Users', 'xelite-repost-engine'); ?></option>
                                <?php
                                $users = get_users(array('fields' => array('ID', 'display_name')));
                                foreach ($users as $user) {
                                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                                }
                                ?>
                            </select>
                            <button type="button" id="load-history" class="button button-secondary">
                                <?php _e('Load History', 'xelite-repost-engine'); ?>
                            </button>
                        </div>
                        
                        <div id="subscription-history-container">
                            <table class="wp-list-table widefat fixed striped" id="subscription-history-table" style="display: none;">
                                <thead>
                                    <tr>
                                        <th><?php _e('Date', 'xelite-repost-engine'); ?></th>
                                        <th><?php _e('User', 'xelite-repost-engine'); ?></th>
                                        <th><?php _e('Event', 'xelite-repost-engine'); ?></th>
                                        <th><?php _e('Subscription ID', 'xelite-repost-engine'); ?></th>
                                        <th><?php _e('Status Change', 'xelite-repost-engine'); ?></th>
                                        <th><?php _e('Actions', 'xelite-repost-engine'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="history-tbody">
                                </tbody>
                            </table>
                            <div id="history-loading" style="display: none;">
                                <p><?php _e('Loading history...', 'xelite-repost-engine'); ?></p>
                            </div>
                            <div id="history-empty" style="display: none;">
                                <p><?php _e('No subscription events found.', 'xelite-repost-engine'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                .xelite-woocommerce-settings .xelite-settings-section {
                    background: #fff;
                    padding: 20px;
                    margin: 20px 0;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                }
                .xelite-product-list {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }
                .xelite-product-list li {
                    padding: 8px 0;
                    border-bottom: 1px solid #f0f0f0;
                }
                .xelite-tier-row, .xelite-limit-row {
                    margin: 15px 0;
                    padding: 10px;
                    background: #f9f9f9;
                    border-radius: 4px;
                }
                .xelite-tier-row label, .xelite-limit-row label {
                    display: inline-block;
                    width: 200px;
                    font-weight: 600;
                }
                .xelite-admin-actions {
                    margin: 20px 0;
                }
                .xelite-admin-actions button {
                    margin-right: 10px;
                }
                .xelite-status-message {
                    padding: 10px;
                    border-radius: 4px;
                    margin: 10px 0;
                }
                .xelite-status-message.success {
                    background: #d4edda;
                    border: 1px solid #c3e6cb;
                    color: #155724;
                }
                .xelite-status-message.error {
                    background: #f8d7da;
                    border: 1px solid #f5c6cb;
                    color: #721c24;
                }
                </style>
                
                <script>
                jQuery(document).ready(function($) {
                    // Refresh all subscriptions
                    $('#refresh-all-subscriptions').on('click', function() {
                        var button = $(this);
                        var statusDiv = $('#refresh-status');
                        var statusMessage = $('.xelite-status-message');
                        
                        button.prop('disabled', true).text('Refreshing...');
                        statusDiv.show();
                        statusMessage.removeClass('success error').text('Refreshing user subscriptions...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'xelite_refresh_all_subscriptions',
                                nonce: '<?php echo wp_create_nonce('xelite_woocommerce_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    statusMessage.addClass('success').text('Successfully refreshed ' + response.data.count + ' user subscriptions.');
                                } else {
                                    statusMessage.addClass('error').text('Error: ' + response.data);
                                }
                            },
                            error: function() {
                                statusMessage.addClass('error').text('Error refreshing subscriptions. Please try again.');
                            },
                            complete: function() {
                                button.prop('disabled', false).text('Refresh All User Subscriptions');
                            }
                        });
                    });
                    
                    // Clear all caches
                    $('#clear-all-caches').on('click', function() {
                        var button = $(this);
                        var statusDiv = $('#refresh-status');
                        var statusMessage = $('.xelite-status-message');
                        
                        button.prop('disabled', true).text('Clearing...');
                        statusDiv.show();
                        statusMessage.removeClass('success error').text('Clearing all caches...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'xelite_clear_all_caches',
                                nonce: '<?php echo wp_create_nonce('xelite_woocommerce_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    statusMessage.addClass('success').text('Successfully cleared all caches.');
                                } else {
                                    statusMessage.addClass('error').text('Error: ' + response.data);
                                }
                            },
                            error: function() {
                                statusMessage.addClass('error').text('Error clearing caches. Please try again.');
                            },
                            complete: function() {
                                button.prop('disabled', false).text('Clear All Caches');
                            }
                        });
                    });

                    // Load subscription history
                    $('#load-history').on('click', function() {
                        var button = $(this);
                        var userFilter = $('#history-user-filter').val();
                        var historyTable = $('#subscription-history-table');
                        var historyTbody = $('#history-tbody');
                        var historyLoading = $('#history-loading');
                        var historyEmpty = $('#history-empty');
                        
                        button.prop('disabled', true).text('Loading...');
                        historyTable.hide();
                        historyEmpty.hide();
                        historyLoading.show();
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'xelite_get_subscription_history',
                                nonce: '<?php echo wp_create_nonce('xelite_woocommerce_nonce'); ?>',
                                user_id: userFilter,
                                limit: 50,
                                offset: 0
                            },
                            success: function(response) {
                                if (response.success) {
                                    var history = response.data;
                                    if (history.length > 0) {
                                        historyTbody.empty();
                                        $.each(history, function(index, event) {
                                            var row = '<tr>';
                                            row += '<td>' + formatDate(event.created_at) + '</td>';
                                            row += '<td>' + getUserName(event.user_id) + '</td>';
                                            row += '<td>' + formatEventType(event.event_type) + '</td>';
                                            row += '<td>#' + event.subscription_id + '</td>';
                                            row += '<td>' + formatStatusChange(event.old_status, event.new_status) + '</td>';
                                            row += '<td><button type="button" class="button button-small view-event-details" data-event-id="' + event.id + '">View Details</button></td>';
                                            row += '</tr>';
                                            historyTbody.append(row);
                                        });
                                        historyTable.show();
                                    } else {
                                        historyEmpty.show();
                                    }
                                } else {
                                    alert('Error loading history: ' + response.data);
                                }
                            },
                            error: function() {
                                alert('Error loading subscription history. Please try again.');
                            },
                            complete: function() {
                                button.prop('disabled', false).text('Load History');
                                historyLoading.hide();
                            }
                        });
                    });

                    // View event details
                    $(document).on('click', '.view-event-details', function() {
                        var eventId = $(this).data('event-id');
                        var eventData = getEventData(eventId);
                        
                        if (eventData) {
                            showEventDetailsModal(eventData);
                        }
                    });

                    // Helper functions
                    function formatDate(dateString) {
                        var date = new Date(dateString);
                        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                    }

                    function getUserName(userId) {
                        // This would need to be implemented with user data
                        return 'User #' + userId;
                    }

                    function formatEventType(eventType) {
                        var types = {
                            'created': 'Subscription Created',
                            'cancelled': 'Subscription Cancelled',
                            'expired': 'Subscription Expired',
                            'renewed': 'Subscription Renewed',
                            'payment_complete': 'Payment Complete',
                            'payment_failed': 'Payment Failed',
                            'subscription_status_changed': 'Status Changed'
                        };
                        return types[eventType] || eventType;
                    }

                    function formatStatusChange(oldStatus, newStatus) {
                        if (oldStatus && newStatus) {
                            return oldStatus + ' → ' + newStatus;
                        } else if (newStatus) {
                            return '→ ' + newStatus;
                        } else {
                            return '—';
                        }
                    }

                    function getEventData(eventId) {
                        // This would need to be implemented to fetch event details
                        return null;
                    }

                    function showEventDetailsModal(eventData) {
                        // This would need to be implemented to show event details
                        alert('Event details functionality would be implemented here.');
                    }
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Sanitize product ID
     *
     * @param mixed $input Raw input.
     * @return int Sanitized product ID.
     */
    public function sanitize_product_id($input) {
        $product_id = intval($input);
        return $product_id > 0 ? $product_id : '';
    }

    /**
     * Sanitize generation limit
     *
     * @param mixed $input Raw input.
     * @return int Sanitized generation limit.
     */
    public function sanitize_generation_limit($input) {
        $limit = intval($input);
        return $limit >= -1 ? $limit : 10; // Default to 10 if invalid
    }

    /**
     * Sanitize webhook secret
     *
     * @param string $input Raw input.
     * @return string Sanitized secret.
     */
    public function sanitize_webhook_secret($input) {
        return sanitize_text_field($input);
    }

    /**
     * Sanitize tier mapping
     *
     * @param string $input Raw input.
     * @return array Sanitized mapping.
     */
    public function sanitize_tier_mapping($input) {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded;
            }
        }
        
        if (!is_array($input)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($input as $product_id => $tier) {
            $product_id = sanitize_text_field($product_id);
            $tier = sanitize_text_field($tier);
            
            if (!empty($product_id) && !empty($tier)) {
                $sanitized[$product_id] = $tier;
            }
        }
        
        return $sanitized;
    }

    /**
     * Log user activity
     *
     * @param int $user_id User ID.
     * @param string $action Action performed.
     * @param array $data Additional data.
     */
    private function log_user_activity($user_id, $action, $data) {
        $activity = array(
            'user_id' => $user_id,
            'action' => $action,
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
        
        // Store in database
        $this->database->insert('user_activity', $activity);
        
        $this->log('info', "User activity logged: {$action} for user {$user_id}");
    }

    /**
     * Check feature access before performing actions
     *
     * @param string $feature Feature name.
     */
    public function check_feature_access($feature) {
        if (!$this->can_user_access_feature($feature)) {
            wp_die(__('You do not have access to this feature. Please upgrade your subscription.', 'xelite-repost-engine'));
        }
    }

    /**
     * Get integration status
     *
     * @return bool True if integration is active.
     */
    public function is_integration_active() {
        return $this->is_active;
    }

    /**
     * Create subscription log table
     */
    public function create_subscription_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xelite_subscription_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                subscription_id bigint(20) NOT NULL,
                event_type varchar(50) NOT NULL,
                old_status varchar(50) DEFAULT NULL,
                new_status varchar(50) DEFAULT NULL,
                event_data longtext DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY subscription_id (subscription_id),
                KEY event_type (event_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            $this->log('info', 'Created subscription log table');
        }
    }

    /**
     * Handle subscription creation
     *
     * @param WC_Subscription $subscription Subscription object.
     * @param array $args Additional arguments.
     */
    public function subscription_created($subscription, $args) {
        $user_id = $subscription->get_user_id();
        
        $this->log('info', "Subscription created for user {$user_id}");
        
        // Log the event
        $this->log_subscription_event($user_id, $subscription, 'created', null, 'active', array(
            'subscription_total' => $subscription->get_total(),
            'next_payment_date' => $subscription->get_date('next_payment'),
            'created_date' => $subscription->get_date('date_created')
        ));
        
        // Activate user features
        $this->activate_user_features($user_id, $subscription);
        
        // Send welcome email
        $this->send_subscription_welcome_email($user_id, $subscription);
        
        // Trigger action for other plugins/themes
        do_action('xelite_subscription_created', $user_id, $subscription, $args);
    }

    /**
     * Handle subscription cancellation
     *
     * @param WC_Subscription $subscription Subscription object.
     */
    public function subscription_cancelled($subscription) {
        $user_id = $subscription->get_user_id();
        
        $this->log('info', "Subscription cancelled for user {$user_id}");
        
        // Log the event
        $this->log_subscription_event($user_id, $subscription, 'cancelled', 'active', 'cancelled', array(
            'cancelled_date' => current_time('mysql')
        ));
        
        // Deactivate user features
        $this->deactivate_user_features($user_id);
        
        // Send cancellation email
        $this->send_subscription_cancellation_email($user_id, $subscription);
        
        // Trigger action for other plugins/themes
        do_action('xelite_subscription_cancelled', $user_id, $subscription);
    }

    /**
     * Handle subscription expiration
     *
     * @param WC_Subscription $subscription Subscription object.
     */
    public function subscription_expired($subscription) {
        $user_id = $subscription->get_user_id();
        
        $this->log('info', "Subscription expired for user {$user_id}");
        
        // Log the event
        $this->log_subscription_event($user_id, $subscription, 'expired', 'active', 'expired', array(
            'expired_date' => current_time('mysql')
        ));
        
        // Deactivate user features
        $this->deactivate_user_features($user_id);
        
        // Send expiration email
        $this->send_subscription_expiration_email($user_id, $subscription);
        
        // Trigger action for other plugins/themes
        do_action('xelite_subscription_expired', $user_id, $subscription);
    }

    /**
     * Handle subscription renewal
     *
     * @param WC_Subscription $subscription Subscription object.
     * @param WC_Order $renewal_order Renewal order object.
     */
    public function subscription_renewed($subscription, $renewal_order) {
        $user_id = $subscription->get_user_id();
        
        $this->log('info', "Subscription renewed for user {$user_id}");
        
        // Log the event
        $this->log_subscription_event($user_id, $subscription, 'renewed', 'active', 'active', array(
            'renewal_order_id' => $renewal_order->get_id(),
            'renewal_amount' => $renewal_order->get_total(),
            'next_payment_date' => $subscription->get_date('next_payment')
        ));
        
        // Refresh user features
        $this->refresh_user_subscription($user_id);
        
        // Send renewal email
        $this->send_subscription_renewal_email($user_id, $subscription, $renewal_order);
        
        // Trigger action for other plugins/themes
        do_action('xelite_subscription_renewed', $user_id, $subscription, $renewal_order);
    }

    /**
     * Handle subscription payment completion
     *
     * @param WC_Subscription $subscription Subscription object.
     */
    public function subscription_payment_complete($subscription) {
        $user_id = $subscription->get_user_id();
        
        $this->log('info', "Subscription payment completed for user {$user_id}");
        
        // Log the event
        $this->log_subscription_event($user_id, $subscription, 'payment_complete', null, null, array(
            'payment_date' => current_time('mysql'),
            'payment_amount' => $subscription->get_total()
        ));
        
        // Trigger action for other plugins/themes
        do_action('xelite_subscription_payment_complete', $user_id, $subscription);
    }

    /**
     * Handle subscription payment failure
     *
     * @param WC_Subscription $subscription Subscription object.
     */
    public function subscription_payment_failed($subscription) {
        $user_id = $subscription->get_user_id();
        
        $this->log('warning', "Subscription payment failed for user {$user_id}");
        
        // Log the event
        $this->log_subscription_event($user_id, $subscription, 'payment_failed', null, null, array(
            'failure_date' => current_time('mysql')
        ));
        
        // Send payment failure email
        $this->send_subscription_payment_failure_email($user_id, $subscription);
        
        // Trigger action for other plugins/themes
        do_action('xelite_subscription_payment_failed', $user_id, $subscription);
    }

    /**
     * Log subscription event to database
     *
     * @param int $user_id User ID.
     * @param WC_Subscription $subscription Subscription object.
     * @param string $event_type Event type.
     * @param string|null $old_status Old status.
     * @param string|null $new_status New status.
     * @param array $event_data Additional event data.
     */
    private function log_subscription_event($user_id, $subscription, $event_type, $old_status, $new_status, $event_data = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xelite_subscription_logs';
        
        $data = array(
            'user_id' => $user_id,
            'subscription_id' => $subscription->get_id(),
            'event_type' => $event_type,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'event_data' => json_encode($event_data),
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert($table_name, $data);
        
        $this->log('info', "Logged subscription event: {$event_type} for user {$user_id}");
    }

    /**
     * Send subscription status email
     *
     * @param int $user_id User ID.
     * @param WC_Subscription $subscription Subscription object.
     * @param string $old_status Old status.
     * @param string $new_status New status.
     */
    public function send_subscription_status_email($user_id, $subscription, $old_status, $new_status) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $tier = $this->get_user_subscription_tier($user_id, $subscription);
        $subject = '';
        $message = '';
        
        switch ($new_status) {
            case 'active':
                if ($old_status !== 'active') {
                    $subject = 'Your Repost Intelligence subscription is now active!';
                    $message = $this->get_welcome_email_content($user, $subscription, $tier);
                }
                break;
            case 'cancelled':
                $subject = 'Your Repost Intelligence subscription has been cancelled';
                $message = $this->get_cancellation_email_content($user, $subscription);
                break;
            case 'expired':
                $subject = 'Your Repost Intelligence subscription has expired';
                $message = $this->get_expiration_email_content($user, $subscription);
                break;
        }
        
        if ($subject && $message) {
            return $this->send_email($user->user_email, $subject, $message);
        }
        
        return false;
    }

    /**
     * Send subscription welcome email
     *
     * @param int $user_id User ID.
     * @param WC_Subscription $subscription Subscription object.
     */
    private function send_subscription_welcome_email($user_id, $subscription) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $tier = $this->get_user_subscription_tier($user_id, $subscription);
        $subject = 'Welcome to Repost Intelligence!';
        $message = $this->get_welcome_email_content($user, $subscription, $tier);
        
        return $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Send subscription cancellation email
     *
     * @param int $user_id User ID.
     * @param WC_Subscription $subscription Subscription object.
     */
    private function send_subscription_cancellation_email($user_id, $subscription) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $subject = 'Your Repost Intelligence subscription has been cancelled';
        $message = $this->get_cancellation_email_content($user, $subscription);
        
        return $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Send subscription expiration email
     *
     * @param int $user_id User ID.
     * @param WC_Subscription $subscription Subscription object.
     */
    private function send_subscription_expiration_email($user_id, $subscription) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $subject = 'Your Repost Intelligence subscription has expired';
        $message = $this->get_expiration_email_content($user, $subscription);
        
        return $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Send subscription renewal email
     *
     * @param int $user_id User ID.
     * @param WC_Subscription $subscription Subscription object.
     * @param WC_Order $renewal_order Renewal order object.
     */
    private function send_subscription_renewal_email($user_id, $subscription, $renewal_order) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $subject = 'Your Repost Intelligence subscription has been renewed';
        $message = $this->get_renewal_email_content($user, $subscription, $renewal_order);
        
        return $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Send subscription payment failure email
     *
     * @param int $user_id User ID.
     * @param WC_Subscription $subscription Subscription object.
     */
    private function send_subscription_payment_failure_email($user_id, $subscription) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $subject = 'Payment failed for your Repost Intelligence subscription';
        $message = $this->get_payment_failure_email_content($user, $subscription);
        
        return $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Send manual subscription email
     *
     * @param int $user_id User ID.
     * @param string $email_type Email type.
     * @return bool True if email was sent successfully.
     */
    public function send_manual_subscription_email($user_id, $email_type) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $subscriptions = wcs_get_users_subscriptions($user_id);
        $subscription = null;
        
        foreach ($subscriptions as $sub) {
            if ($sub->has_status('active')) {
                $subscription = $sub;
                break;
            }
        }
        
        if (!$subscription) {
            return false;
        }
        
        switch ($email_type) {
            case 'welcome':
                return $this->send_subscription_welcome_email($user_id, $subscription);
            case 'cancellation':
                return $this->send_subscription_cancellation_email($user_id, $subscription);
            case 'expiration':
                return $this->send_subscription_expiration_email($user_id, $subscription);
            case 'payment_failure':
                return $this->send_subscription_payment_failure_email($user_id, $subscription);
            default:
                return false;
        }
    }

    /**
     * Send email
     *
     * @param string $to Email address.
     * @param string $subject Email subject.
     * @param string $message Email message.
     * @return bool True if email was sent successfully.
     */
    private function send_email($to, $subject, $message) {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        );
        
        $result = wp_mail($to, $subject, $message, $headers);
        
        if ($result) {
            $this->log('info', "Email sent to {$to}: {$subject}");
        } else {
            $this->log('error', "Failed to send email to {$to}: {$subject}");
        }
        
        return $result;
    }

    /**
     * Get subscription history
     *
     * @param int $user_id User ID (0 for all users).
     * @param int $limit Number of records to return.
     * @param int $offset Offset for pagination.
     * @return array Subscription history.
     */
    public function get_subscription_history($user_id = 0, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xelite_subscription_logs';
        
        $where_clause = '';
        $where_values = array();
        
        if ($user_id > 0) {
            $where_clause = 'WHERE user_id = %d';
            $where_values[] = $user_id;
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($where_values, array($limit, $offset))
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Decode event data
        foreach ($results as &$result) {
            if (!empty($result['event_data'])) {
                $result['event_data'] = json_decode($result['event_data'], true);
            }
        }
        
        return $results;
    }

    /**
     * Get welcome email content
     *
     * @param WP_User $user User object.
     * @param WC_Subscription $subscription Subscription object.
     * @param string $tier Subscription tier.
     * @return string Email content.
     */
    private function get_welcome_email_content($user, $subscription, $tier) {
        $tier_name = ucfirst($tier);
        $next_payment = $subscription->get_date('next_payment');
        $features = $this->get_user_features($user->ID);
        
        $feature_list = '';
        foreach ($features as $feature => $enabled) {
            if ($enabled) {
                $feature_list .= '<li>' . esc_html(ucfirst(str_replace('_', ' ', $feature))) . '</li>';
            }
        }
        
        return "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <h2>Welcome to Repost Intelligence!</h2>
            <p>Hi {$user->display_name},</p>
            <p>Thank you for subscribing to Repost Intelligence! Your {$tier_name} subscription is now active.</p>
            
            <h3>Your Subscription Details:</h3>
            <ul>
                <li><strong>Tier:</strong> {$tier_name}</li>
                <li><strong>Next Payment:</strong> " . date('F j, Y', strtotime($next_payment)) . "</li>
                <li><strong>Amount:</strong> " . wc_price($subscription->get_total()) . "</li>
            </ul>
            
            <h3>Features You Now Have Access To:</h3>
            <ul>
                {$feature_list}
            </ul>
            
            <p>You can access your dashboard at: <a href='" . admin_url('admin.php?page=xelite-repost-engine') . "'>Repost Intelligence Dashboard</a></p>
            
            <p>If you have any questions, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br>The Repost Intelligence Team</p>
        </div>";
    }

    /**
     * Get cancellation email content
     *
     * @param WP_User $user User object.
     * @param WC_Subscription $subscription Subscription object.
     * @return string Email content.
     */
    private function get_cancellation_email_content($user, $subscription) {
        return "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <h2>Subscription Cancelled</h2>
            <p>Hi {$user->display_name},</p>
            <p>Your Repost Intelligence subscription has been cancelled as requested.</p>
            
            <p>You will continue to have access to your features until the end of your current billing period.</p>
            
            <p>If you change your mind, you can reactivate your subscription at any time from your account dashboard.</p>
            
            <p>Thank you for using Repost Intelligence!</p>
            
            <p>Best regards,<br>The Repost Intelligence Team</p>
        </div>";
    }

    /**
     * Get expiration email content
     *
     * @param WP_User $user User object.
     * @param WC_Subscription $subscription Subscription object.
     * @return string Email content.
     */
    private function get_expiration_email_content($user, $subscription) {
        return "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <h2>Subscription Expired</h2>
            <p>Hi {$user->display_name},</p>
            <p>Your Repost Intelligence subscription has expired.</p>
            
            <p>To continue enjoying our features, please renew your subscription from your account dashboard.</p>
            
            <p>If you have any questions about your subscription, please contact our support team.</p>
            
            <p>Best regards,<br>The Repost Intelligence Team</p>
        </div>";
    }

    /**
     * Get renewal email content
     *
     * @param WP_User $user User object.
     * @param WC_Subscription $subscription Subscription object.
     * @param WC_Order $renewal_order Renewal order object.
     * @return string Email content.
     */
    private function get_renewal_email_content($user, $subscription, $renewal_order) {
        $next_payment = $subscription->get_date('next_payment');
        
        return "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <h2>Subscription Renewed</h2>
            <p>Hi {$user->display_name},</p>
            <p>Your Repost Intelligence subscription has been successfully renewed!</p>
            
            <h3>Renewal Details:</h3>
            <ul>
                <li><strong>Order ID:</strong> #{$renewal_order->get_id()}</li>
                <li><strong>Amount:</strong> " . wc_price($renewal_order->get_total()) . "</li>
                <li><strong>Next Payment:</strong> " . date('F j, Y', strtotime($next_payment)) . "</li>
            </ul>
            
            <p>Thank you for continuing with Repost Intelligence!</p>
            
            <p>Best regards,<br>The Repost Intelligence Team</p>
        </div>";
    }

    /**
     * Get payment failure email content
     *
     * @param WP_User $user User object.
     * @param WC_Subscription $subscription Subscription object.
     * @return string Email content.
     */
    private function get_payment_failure_email_content($user, $subscription) {
        return "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <h2>Payment Failed</h2>
            <p>Hi {$user->display_name},</p>
            <p>We were unable to process the payment for your Repost Intelligence subscription.</p>
            
            <p>Please update your payment method in your account dashboard to avoid any interruption to your service.</p>
            
            <p>If you need assistance, please contact our support team.</p>
            
            <p>Best regards,<br>The Repost Intelligence Team</p>
        </div>";
    }

    /**
     * Log message
     *
     * @param string $level Log level.
     * @param string $message Log message.
     * @param array $context Additional context.
     */
    private function log($level, $message, $context = array()) {
        if ($this->logger) {
            $this->logger->log($level, "[WooCommerce] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine WooCommerce] {$message}");
        }
    }
} 