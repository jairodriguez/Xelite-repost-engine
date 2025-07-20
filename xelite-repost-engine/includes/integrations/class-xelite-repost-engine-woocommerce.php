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
        
        if ($new_status === 'active' && $old_status !== 'active') {
            $this->activate_user_features($user_id, $subscription);
        } elseif ($new_status !== 'active' && $old_status === 'active') {
            $this->deactivate_user_features($user_id);
        }
        
        // Update user meta with current subscription status
        update_user_meta($user_id, 'xelite_subscription_status', $new_status);
        update_user_meta($user_id, 'xelite_subscription_updated', current_time('mysql'));
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
     * Get user subscription tier
     *
     * @param int $user_id User ID.
     * @param WC_Subscription|null $subscription Optional subscription object.
     * @return string Subscription tier.
     */
    public function get_user_subscription_tier($user_id, $subscription = null) {
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
            return 'none';
        }
        
        $tier_mapping = $this->get_tier_mapping();
        
        foreach ($subscription->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            if (isset($tier_mapping[$product_id])) {
                return $tier_mapping[$product_id];
            }
        }
        
        return 'none';
    }

    /**
     * Get tier mapping from settings
     *
     * @return array Tier mapping.
     */
    private function get_tier_mapping() {
        $mapping = get_option('xelite_woocommerce_tier_mapping', array());
        
        // Default mapping if none is set
        if (empty($mapping)) {
            $mapping = array(
                // Example product IDs - should be configured in admin
                'basic_product_id' => 'basic',
                'premium_product_id' => 'premium',
                'enterprise_product_id' => 'enterprise'
            );
        }
        
        return $mapping;
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
     * Check content generation limit for basic tier
     *
     * @param int $user_id User ID.
     * @return bool True if user can generate more content.
     */
    private function check_content_generation_limit($user_id) {
        $tier = get_user_meta($user_id, 'xelite_subscription_tier', true);
        
        if ($tier !== 'basic') {
            return true; // No limit for other tiers
        }
        
        $monthly_limit = 10; // Basic tier limit
        $current_month = date('Y-m');
        
        // Get current month's generation count
        $generation_count = get_user_meta($user_id, "xelite_generation_count_{$current_month}", true);
        $generation_count = intval($generation_count);
        
        return $generation_count < $monthly_limit;
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
    private function get_user_limits($user_id) {
        $tier = get_user_meta($user_id, 'xelite_subscription_tier', true);
        $current_month = date('Y-m');
        
        $limits = array(
            'basic' => array(
                'monthly_generations' => 10,
                'current_generations' => intval(get_user_meta($user_id, "xelite_generation_count_{$current_month}", true))
            ),
            'premium' => array(
                'monthly_generations' => 100,
                'current_generations' => intval(get_user_meta($user_id, "xelite_generation_count_{$current_month}", true))
            ),
            'enterprise' => array(
                'monthly_generations' => -1, // Unlimited
                'current_generations' => intval(get_user_meta($user_id, "xelite_generation_count_{$current_month}", true))
            )
        );
        
        return isset($limits[$tier]) ? $limits[$tier] : array();
    }

    /**
     * Register settings
     */
    public function register_settings() {
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
        ?>
        <div class="wrap">
            <h1><?php _e('WooCommerce Integration Settings', 'xelite-repost-engine'); ?></h1>
            
            <?php if (!$this->is_woocommerce_active()): ?>
                <div class="notice notice-error">
                    <p><?php _e('WooCommerce and WooCommerce Subscriptions must be installed and activated for this integration to work.', 'xelite-repost-engine'); ?></p>
                </div>
            <?php else: ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('xelite_woocommerce_settings');
                    do_settings_sections('xelite_woocommerce_settings');
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Product ID to Tier Mapping', 'xelite-repost-engine'); ?></th>
                            <td>
                                <p><?php _e('Map your WooCommerce subscription product IDs to subscription tiers:', 'xelite-repost-engine'); ?></p>
                                <textarea name="xelite_woocommerce_tier_mapping" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(json_encode($tier_mapping, JSON_PRETTY_PRINT)); ?></textarea>
                                <p class="description"><?php _e('Enter as JSON format: {"product_id": "tier_name"}', 'xelite-repost-engine'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
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