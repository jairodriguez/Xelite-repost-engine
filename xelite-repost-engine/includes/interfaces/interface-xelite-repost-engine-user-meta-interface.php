<?php
/**
 * User Meta Interface
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Meta Interface
 */
interface XeliteRepostEngine_User_Meta_Interface {
    
    /**
     * Get user meta value
     *
     * @param int    $user_id User ID
     * @param string $key     Meta key
     * @param mixed  $default Default value
     * @return mixed
     */
    public function get_user_meta($user_id, $key, $default = false);
    
    /**
     * Update user meta value
     *
     * @param int    $user_id User ID
     * @param string $key     Meta key
     * @param mixed  $value   Meta value
     * @return int|bool
     */
    public function update_user_meta($user_id, $key, $value);
    
    /**
     * Delete user meta value
     *
     * @param int    $user_id User ID
     * @param string $key     Meta key
     * @return bool
     */
    public function delete_user_meta($user_id, $key);
    
    /**
     * Get all user meta for a user
     *
     * @param int $user_id User ID
     * @return array
     */
    public function get_all_user_meta($user_id);
    
    /**
     * Check if user has complete profile
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function has_complete_profile($user_id);
    
    /**
     * Get user context data
     *
     * @param int $user_id User ID
     * @return array
     */
    public function get_user_context($user_id);
    
    /**
     * Validate user meta data
     *
     * @param array $data User meta data
     * @return array
     */
    public function validate_user_meta($data);
    
    /**
     * Sanitize user meta data
     *
     * @param array $data User meta data
     * @return array
     */
    public function sanitize_user_meta($data);
} 