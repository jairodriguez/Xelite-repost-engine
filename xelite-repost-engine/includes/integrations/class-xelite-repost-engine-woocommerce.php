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
    private function init() {
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
        // Subscription status change hooks
        add_action('woocommerce_subscription_status_updated', array($this, 'subscription_status_changed'), 10, 3);
        add_action('woocommerce_subscription_status_changed', array($this, 'subscription_status_changed'), 10, 3);
        
        // User registration and login hooks
        add_action('user_register', array($this, 'user_registered'));
        add_action('wp_login', array($this, 'user_logged_in'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'check_user_access'));
        add_action('wp_ajax_xelite_check_subscription', array($this, 'ajax_check_subscription'));
        add_action('wp_ajax_xelite_refresh_all_subscriptions', array($this, 'ajax_refresh_all_subscriptions'));
        add_action('wp_ajax_xelite_clear_all_caches', array($this, 'ajax_clear_all_caches'));
        
        // Feature access hooks
        add_action('xelite_before_feature_access', array($this, 'check_feature_access'));
        
        // Settings hooks
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
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