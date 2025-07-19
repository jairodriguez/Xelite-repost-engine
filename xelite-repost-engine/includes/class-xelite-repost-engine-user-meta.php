<?php
/**
 * User Meta Integration Class
 *
 * Handles WordPress user meta data access and management for personalization.
 * Provides methods to retrieve, validate, and manage user context data.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Meta Access Class
 *
 * Manages user meta data for personalization features including context data,
 * completeness validation, and caching for performance optimization.
 */
class XeliteRepostEngine_User_Meta extends XeliteRepostEngine_Abstract_Base {
    
    /**
     * Meta field definitions
     *
     * @var array
     */
    private $meta_fields = array(
        'personal-context' => array(
            'label' => 'Personal Context',
            'description' => 'Your personal background and context',
            'required' => false,
            'type' => 'textarea'
        ),
        'dream-client' => array(
            'label' => 'Dream Client',
            'description' => 'Description of your ideal client',
            'required' => true,
            'type' => 'text'
        ),
        'writing-style' => array(
            'label' => 'Writing Style',
            'description' => 'Your preferred writing style and tone',
            'required' => true,
            'type' => 'text'
        ),
        'irresistible-offer' => array(
            'label' => 'Irresistible Offer',
            'description' => 'Your main offer or value proposition',
            'required' => false,
            'type' => 'textarea'
        ),
        'dream-client-pain-points' => array(
            'label' => 'Dream Client Pain Points',
            'description' => 'Key pain points of your target audience',
            'required' => true,
            'type' => 'textarea'
        ),
        'ikigai' => array(
            'label' => 'Ikigai',
            'description' => 'Your purpose and passion (what you love, what you\'re good at, what the world needs, what you can be paid for)',
            'required' => false,
            'type' => 'textarea'
        ),
        'topic' => array(
            'label' => 'Topic',
            'description' => 'Your main topic or niche',
            'required' => false,
            'type' => 'text'
        )
    );
    
    /**
     * Cache group for user meta
     *
     * @var string
     */
    private $cache_group = 'xelite_user_meta';
    
    /**
     * Initialize the class
     */
    protected function init() {
        // Add hooks for user meta management
        add_action('show_user_profile', array($this, 'add_user_meta_fields'));
        add_action('edit_user_profile', array($this, 'add_user_meta_fields'));
        add_action('personal_options_update', array($this, 'save_user_meta_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_meta_fields'));
        
        // Clear cache when user meta is updated
        add_action('updated_user_meta', array($this, 'clear_user_cache'), 10, 4);
        add_action('added_user_meta', array($this, 'clear_user_cache'), 10, 4);
        add_action('deleted_user_meta', array($this, 'clear_user_cache'), 10, 4);
        
        $this->log_debug('User Meta class initialized');
    }
    
    /**
     * Get all user context data
     *
     * @param int|null $user_id User ID, defaults to current user
     * @return array|false User context data or false on failure
     */
    public function get_user_context($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            $this->log_error('No user ID provided for get_user_context');
            return false;
        }
        
        // Check cache first
        $cache_key = 'user_context_' . $user_id;
        $cached_data = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $user_context = array();
        $meta_keys = array_keys($this->meta_fields);
        
        foreach ($meta_keys as $key) {
            $user_context[$key] = get_user_meta($user_id, $key, true);
        }
        
        // Add user info
        $user = get_userdata($user_id);
        if ($user) {
            $user_context['user_info'] = array(
                'id' => $user_id,
                'display_name' => $user->display_name,
                'user_email' => $user->user_email,
                'user_login' => $user->user_login
            );
        }
        
        // Cache the result
        wp_cache_set($cache_key, $user_context, $this->cache_group, 3600); // Cache for 1 hour
        
        return $user_context;
    }
    
    /**
     * Check if user context is complete
     *
     * @param int|null $user_id User ID, defaults to current user
     * @return array Array with 'complete' boolean and 'missing_fields' array
     */
    public function is_context_complete($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array(
                'complete' => false,
                'completeness_percentage' => 0,
                'missing_fields' => array(),
                'message' => __('No user ID provided.', 'xelite-repost-engine')
            );
        }
        
        $context = $this->get_user_context($user_id);
        
        if (!$context) {
            return array(
                'complete' => false,
                'completeness_percentage' => 0,
                'missing_fields' => array_keys($this->meta_fields),
                'message' => __('Unable to retrieve user context.', 'xelite-repost-engine')
            );
        }
        
        // Allow filtering of completeness criteria
        $completeness_criteria = apply_filters('xelite_repost_engine_completeness_criteria', array(
            'required_fields' => array('dream-client', 'writing-style', 'dream-client-pain-points'),
            'optional_fields' => array('topic', 'personal-context', 'irresistible-offer', 'ikigai'),
            'field_weights' => array(
                'dream-client' => 25,
                'writing-style' => 25,
                'dream-client-pain-points' => 25,
                'topic' => 10,
                'personal-context' => 10,
                'irresistible-offer' => 3,
                'ikigai' => 2
            )
        ));
        
        $missing_fields = array();
        $completed_fields = array();
        $total_score = 0;
        $max_score = 100;
        
        // Check required fields first
        foreach ($completeness_criteria['required_fields'] as $field_key) {
            if (isset($this->meta_fields[$field_key])) {
                $field_config = $this->meta_fields[$field_key];
                $field_weight = $completeness_criteria['field_weights'][$field_key] ?? 0;
                
                if (empty($context[$field_key])) {
                    $missing_fields[] = array(
                        'key' => $field_key,
                        'label' => $field_config['label'],
                        'description' => $field_config['description'],
                        'required' => true,
                        'weight' => $field_weight
                    );
                } else {
                    $completed_fields[] = array(
                        'key' => $field_key,
                        'label' => $field_config['label'],
                        'weight' => $field_weight
                    );
                    $total_score += $field_weight;
                }
            }
        }
        
        // Check optional fields
        foreach ($completeness_criteria['optional_fields'] as $field_key) {
            if (isset($this->meta_fields[$field_key])) {
                $field_config = $this->meta_fields[$field_key];
                $field_weight = $completeness_criteria['field_weights'][$field_key] ?? 0;
                
                if (!empty($context[$field_key])) {
                    $completed_fields[] = array(
                        'key' => $field_key,
                        'label' => $field_config['label'],
                        'weight' => $field_weight
                    );
                    $total_score += $field_weight;
                }
            }
        }
        
        $completeness_percentage = min(100, round($total_score));
        $is_complete = empty(array_filter($missing_fields, function($field) { return $field['required']; }));
        
        $result = array(
            'complete' => $is_complete,
            'completeness_percentage' => $completeness_percentage,
            'missing_fields' => $missing_fields,
            'completed_fields' => $completed_fields,
            'total_score' => $total_score,
            'max_score' => $max_score,
            'required_fields_count' => count($completeness_criteria['required_fields']),
            'optional_fields_count' => count($completeness_criteria['optional_fields']),
            'completed_required_fields' => count(array_filter($completed_fields, function($field) use ($completeness_criteria) {
                return in_array($field['key'], $completeness_criteria['required_fields']);
            })),
            'completed_optional_fields' => count(array_filter($completed_fields, function($field) use ($completeness_criteria) {
                return in_array($field['key'], $completeness_criteria['optional_fields']);
            })),
            'message' => $this->generate_completeness_message($is_complete, $completeness_percentage, $missing_fields)
        );
        
        // Allow filtering of the result
        return apply_filters('xelite_repost_engine_completeness_result', $result, $user_id, $context);
    }
    
    /**
     * Generate completeness message
     *
     * @param bool $is_complete Whether profile is complete
     * @param int $percentage Completeness percentage
     * @param array $missing_fields Missing fields
     * @return string Message
     */
    private function generate_completeness_message($is_complete, $percentage, $missing_fields) {
        if ($is_complete) {
            if ($percentage >= 90) {
                return __('Profile is excellent! All required fields completed with rich optional data.', 'xelite-repost-engine');
            } elseif ($percentage >= 75) {
                return __('Profile is very good! All required fields completed with some optional data.', 'xelite-repost-engine');
            } else {
                return __('Profile is complete! All required fields are filled.', 'xelite-repost-engine');
            }
        } else {
            $required_missing = count(array_filter($missing_fields, function($field) { return $field['required']; }));
            $optional_missing = count(array_filter($missing_fields, function($field) { return !$field['required']; }));
            
            if ($required_missing > 0) {
                return sprintf(
                    __('Profile is %s%% complete. Missing %d required field(s).', 'xelite-repost-engine'),
                    $percentage,
                    $required_missing
                );
            } else {
                return sprintf(
                    __('Profile is %s%% complete. All required fields filled. Consider adding optional data for better personalization.', 'xelite-repost-engine'),
                    $percentage
                );
            }
        }
    }
    
    /**
     * Get specific meta field
     *
     * @param string $field_key Meta field key
     * @param int|null $user_id User ID, defaults to current user
     * @return mixed Field value or false on failure
     */
    public function get_meta_field($field_key, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id || !isset($this->meta_fields[$field_key])) {
            return false;
        }
        
        return get_user_meta($user_id, $field_key, true);
    }
    
    /**
     * Update meta field
     *
     * @param string $field_key Meta field key
     * @param mixed $value Field value
     * @param int|null $user_id User ID, defaults to current user
     * @return bool Success status
     */
    public function update_meta_field($field_key, $value, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id || !isset($this->meta_fields[$field_key])) {
            return false;
        }
        
        // Sanitize the value based on field type
        $sanitized_value = $this->sanitize_field_value($field_key, $value);
        
        $result = update_user_meta($user_id, $field_key, $sanitized_value);
        
        if ($result) {
            // Clear cache for this user
            $this->clear_user_cache($user_id);
        }
        
        return $result;
    }
    
    /**
     * Get all meta field definitions
     *
     * @return array Meta field definitions
     */
    public function get_meta_fields() {
        return $this->meta_fields;
    }
    
    /**
     * Get required meta fields
     *
     * @return array Required meta field keys
     */
    public function get_required_fields() {
        $required_fields = array();
        
        foreach ($this->meta_fields as $key => $config) {
            if ($config['required']) {
                $required_fields[$key] = $config;
            }
        }
        
        return $required_fields;
    }
    
    /**
     * Get optional meta fields
     *
     * @return array Optional meta field keys
     */
    public function get_optional_fields() {
        $optional_fields = array();
        
        foreach ($this->meta_fields as $key => $config) {
            if (!$config['required']) {
                $optional_fields[$key] = $config;
            }
        }
        
        return $optional_fields;
    }
    
    /**
     * Sanitize field value based on field type
     *
     * @param string $field_key Field key
     * @param mixed $value Field value
     * @return mixed Sanitized value
     */
    private function sanitize_field_value($field_key, $value) {
        if (!isset($this->meta_fields[$field_key])) {
            return '';
        }
        
        $field_config = $this->meta_fields[$field_key];
        
        switch ($field_config['type']) {
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Clear user cache
     *
     * @param int $user_id User ID
     */
    public function clear_user_cache($user_id) {
        $cache_key = 'user_context_' . $user_id;
        wp_cache_delete($cache_key, $this->cache_group);
    }
    
    /**
     * Add user meta fields to profile page
     *
     * @param WP_User $user User object
     */
    public function add_user_meta_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        
        // Include the enhanced admin interface
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/user-meta-profile.php';
        xelite_repost_engine_render_user_meta_profile($user);
    }
    
    /**
     * Save user meta fields
     *
     * @param int $user_id User ID
     */
    public function save_user_meta_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
            return;
        }
        
        foreach ($this->meta_fields as $field_key => $field_config) {
            if (isset($_POST[$field_key])) {
                $this->update_meta_field($field_key, $_POST[$field_key], $user_id);
            }
        }
        
        // Add success notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Personalization settings updated successfully.', 'xelite-repost-engine') . 
                 '</p></div>';
        });
    }
    
    /**
     * Get user context summary for AI prompts
     *
     * @param int|null $user_id User ID, defaults to current user
     * @return string Formatted context summary
     */
    public function get_context_summary($user_id = null) {
        $context = $this->get_user_context($user_id);
        
        if (!$context) {
            return '';
        }
        
        $summary_parts = array();
        
        if (!empty($context['writing-style'])) {
            $summary_parts[] = "Writing Style: " . $context['writing-style'];
        }
        
        if (!empty($context['dream-client'])) {
            $summary_parts[] = "Dream Client: " . $context['dream-client'];
        }
        
        if (!empty($context['dream-client-pain-points'])) {
            $summary_parts[] = "Client Pain Points: " . $context['dream-client-pain-points'];
        }
        
        if (!empty($context['irresistible-offer'])) {
            $summary_parts[] = "Offer: " . $context['irresistible-offer'];
        }
        
        if (!empty($context['topic'])) {
            $summary_parts[] = "Topic: " . $context['topic'];
        }
        
        return implode("\n", $summary_parts);
    }
    
    /**
     * Get users with incomplete profiles
     *
     * @param int $limit Maximum number of users to return
     * @return array Users with incomplete profiles
     */
    public function get_users_with_incomplete_profiles($limit = 10) {
        $users = get_users(array(
            'number' => $limit,
            'orderby' => 'registered',
            'order' => 'DESC'
        ));
        
        $incomplete_users = array();
        
        foreach ($users as $user) {
            $completeness = $this->is_context_complete($user->ID);
            if (!$completeness['complete']) {
                $incomplete_users[] = array(
                    'user_id' => $user->ID,
                    'display_name' => $user->display_name,
                    'user_email' => $user->user_email,
                    'completeness' => $completeness
                );
            }
        }
        
        return $incomplete_users;
    }
    
    /**
     * Get completeness statistics
     *
     * @return array Completeness statistics
     */
    public function get_completeness_statistics() {
        $users = get_users(array('fields' => 'ID'));
        $total_users = count($users);
        
        if ($total_users === 0) {
            return array(
                'total_users' => 0,
                'complete_profiles' => 0,
                'incomplete_profiles' => 0,
                'average_completeness' => 0,
                'completeness_distribution' => array()
            );
        }
        
        $complete_profiles = 0;
        $total_completeness = 0;
        $completeness_distribution = array(
            '0-25' => 0,
            '26-50' => 0,
            '51-75' => 0,
            '76-99' => 0,
            '100' => 0
        );
        
        foreach ($users as $user_id) {
            $completeness = $this->is_context_complete($user_id);
            $total_completeness += $completeness['completeness_percentage'];
            
            if ($completeness['complete']) {
                $complete_profiles++;
                $completeness_distribution['100']++;
            } else {
                $percentage = $completeness['completeness_percentage'];
                if ($percentage <= 25) {
                    $completeness_distribution['0-25']++;
                } elseif ($percentage <= 50) {
                    $completeness_distribution['26-50']++;
                } elseif ($percentage <= 75) {
                    $completeness_distribution['51-75']++;
                } else {
                    $completeness_distribution['76-99']++;
                }
            }
        }
        
        return array(
            'total_users' => $total_users,
            'complete_profiles' => $complete_profiles,
            'incomplete_profiles' => $total_users - $complete_profiles,
            'average_completeness' => round($total_completeness / $total_users, 1),
            'completeness_distribution' => $completeness_distribution
        );
    }
    
    /**
     * Add admin notices for incomplete profiles
     */
    public function add_admin_notices() {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get current screen
        $screen = get_current_screen();
        
        // Show on dashboard and user-related pages
        if (!$screen || !in_array($screen->id, array('dashboard', 'users', 'user-edit', 'profile'))) {
            return;
        }
        
        $stats = $this->get_completeness_statistics();
        
        if ($stats['total_users'] === 0) {
            return;
        }
        
        $incomplete_percentage = round(($stats['incomplete_profiles'] / $stats['total_users']) * 100);
        
        if ($incomplete_percentage > 50) {
            $notice_type = 'error';
            $icon = 'dashicons-warning';
        } elseif ($incomplete_percentage > 25) {
            $notice_type = 'warning';
            $icon = 'dashicons-admin-users';
        } else {
            $notice_type = 'info';
            $icon = 'dashicons-yes-alt';
        }
        
        $message = sprintf(
            __('<strong>Repost Engine:</strong> %d%% of users (%d/%d) have incomplete profiles. <a href="%s">View user profiles</a> to help them complete their personalization data.', 'xelite-repost-engine'),
            $incomplete_percentage,
            $stats['incomplete_profiles'],
            $stats['total_users'],
            admin_url('users.php')
        );
        
        echo '<div class="notice notice-' . $notice_type . ' is-dismissible">';
        echo '<p><span class="dashicons ' . $icon . '"></span> ' . $message . '</p>';
        echo '</div>';
    }
    
    /**
     * Register dashboard widget
     */
    public function register_dashboard_widget() {
        wp_add_dashboard_widget(
            'xelite_repost_engine_completeness_widget',
            __('Repost Engine - Profile Completeness', 'xelite-repost-engine'),
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $stats = $this->get_completeness_statistics();
        
        if ($stats['total_users'] === 0) {
            echo '<p>' . __('No users found.', 'xelite-repost-engine') . '</p>';
            return;
        }
        
        $completeness_percentage = round(($stats['complete_profiles'] / $stats['total_users']) * 100);
        ?>
        <div class="xelite-dashboard-widget">
            <div class="completeness-overview">
                <div class="completeness-percentage">
                    <span class="percentage-number"><?php echo $completeness_percentage; ?>%</span>
                    <span class="percentage-label"><?php _e('Complete Profiles', 'xelite-repost-engine'); ?></span>
                </div>
                
                <div class="completeness-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['complete_profiles']; ?></span>
                        <span class="stat-label"><?php _e('Complete', 'xelite-repost-engine'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['incomplete_profiles']; ?></span>
                        <span class="stat-label"><?php _e('Incomplete', 'xelite-repost-engine'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['average_completeness']; ?>%</span>
                        <span class="stat-label"><?php _e('Avg. Score', 'xelite-repost-engine'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="completeness-distribution">
                <h4><?php _e('Completeness Distribution', 'xelite-repost-engine'); ?></h4>
                <div class="distribution-bars">
                    <?php foreach ($stats['completeness_distribution'] as $range => $count): ?>
                        <?php if ($count > 0): ?>
                            <div class="distribution-bar">
                                <span class="range-label"><?php echo $range; ?>%</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: <?php echo ($count / $stats['total_users']) * 100; ?>%"></div>
                                </div>
                                <span class="count-label"><?php echo $count; ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('users.php'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e('Manage Users', 'xelite-repost-engine'); ?>
                </a>
                
                <?php if ($stats['incomplete_profiles'] > 0): ?>
                    <a href="<?php echo admin_url('admin.php?page=xelite-repost-engine&tab=users'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('View Details', 'xelite-repost-engine'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
} 