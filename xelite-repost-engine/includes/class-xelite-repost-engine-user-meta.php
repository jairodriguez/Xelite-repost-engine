<?php
/**
 * User meta functionality class
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User meta functionality class
 */
class XeliteRepostEngine_User_Meta extends XeliteRepostEngine_Abstract_Base implements XeliteRepostEngine_User_Meta_Interface {
    
    /**
     * Required user meta fields
     *
     * @var array
     */
    private $required_fields = array(
        'personal-context',
        'dream-client',
        'writing-style',
        'irresistible-offer',
        'dream-client-pain-points',
        'ikigai',
        'topic',
    );
    
    /**
     * Initialize the class
     */
    protected function init() {
        $this->log_debug('User Meta class initialized');
    }
    
    /**
     * Get user meta value
     *
     * @param int    $user_id User ID
     * @param string $key     Meta key
     * @param mixed  $default Default value
     * @return mixed
     */
    public function get_user_meta($user_id, $key, $default = false) {
        $value = get_user_meta($user_id, $key, true);
        
        if (empty($value)) {
            return $default;
        }
        
        $this->log_debug('User meta retrieved', array(
            'user_id' => $user_id,
            'key' => $key,
            'has_value' => !empty($value)
        ));
        
        return $value;
    }
    
    /**
     * Update user meta value
     *
     * @param int    $user_id User ID
     * @param string $key     Meta key
     * @param mixed  $value   Meta value
     * @return int|bool
     */
    public function update_user_meta($user_id, $key, $value) {
        $result = update_user_meta($user_id, $key, $value);
        
        $this->log_debug('User meta updated', array(
            'user_id' => $user_id,
            'key' => $key,
            'success' => $result !== false
        ));
        
        return $result;
    }
    
    /**
     * Delete user meta value
     *
     * @param int    $user_id User ID
     * @param string $key     Meta key
     * @return bool
     */
    public function delete_user_meta($user_id, $key) {
        $result = delete_user_meta($user_id, $key);
        
        $this->log_debug('User meta deleted', array(
            'user_id' => $user_id,
            'key' => $key,
            'success' => $result
        ));
        
        return $result;
    }
    
    /**
     * Get all user meta for a user
     *
     * @param int $user_id User ID
     * @return array
     */
    public function get_all_user_meta($user_id) {
        $user_meta = get_user_meta($user_id);
        
        // Flatten the array (WordPress returns arrays for single values)
        $flattened = array();
        foreach ($user_meta as $key => $values) {
            $flattened[$key] = is_array($values) ? $values[0] : $values;
        }
        
        $this->log_debug('All user meta retrieved', array(
            'user_id' => $user_id,
            'count' => count($flattened)
        ));
        
        return $flattened;
    }
    
    /**
     * Check if user has complete profile
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function has_complete_profile($user_id) {
        foreach ($this->required_fields as $field) {
            $value = $this->get_user_meta($user_id, $field);
            if (empty($value)) {
                $this->log_debug('User profile incomplete', array(
                    'user_id' => $user_id,
                    'missing_field' => $field
                ));
                return false;
            }
        }
        
        $this->log_debug('User profile complete', array(
            'user_id' => $user_id
        ));
        
        return true;
    }
    
    /**
     * Get user context data
     *
     * @param int $user_id User ID
     * @return array
     */
    public function get_user_context($user_id) {
        $context = array();
        
        foreach ($this->required_fields as $field) {
            $context[$field] = $this->get_user_meta($user_id, $field, '');
        }
        
        // Add additional context fields
        $context['user_id'] = $user_id;
        $context['profile_complete'] = $this->has_complete_profile($user_id);
        
        $this->log_debug('User context retrieved', array(
            'user_id' => $user_id,
            'profile_complete' => $context['profile_complete']
        ));
        
        return $context;
    }
    
    /**
     * Validate user meta data
     *
     * @param array $data User meta data
     * @return array
     */
    public function validate_user_meta($data) {
        $errors = array();
        
        foreach ($this->required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = sprintf(
                    __('%s is required.', 'xelite-repost-engine'),
                    $this->get_field_label($field)
                );
            }
        }
        
        // Validate field lengths
        $max_lengths = array(
            'personal-context' => 500,
            'dream-client' => 200,
            'writing-style' => 200,
            'irresistible-offer' => 300,
            'dream-client-pain-points' => 500,
            'ikigai' => 200,
            'topic' => 100,
        );
        
        foreach ($max_lengths as $field => $max_length) {
            if (isset($data[$field]) && strlen($data[$field]) > $max_length) {
                $errors[$field] = sprintf(
                    __('%s must be %d characters or less.', 'xelite-repost-engine'),
                    $this->get_field_label($field),
                    $max_length
                );
            }
        }
        
        $this->log_debug('User meta validation completed', array(
            'errors_count' => count($errors)
        ));
        
        return $errors;
    }
    
    /**
     * Sanitize user meta data
     *
     * @param array $data User meta data
     * @return array
     */
    public function sanitize_user_meta($data) {
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'personal-context':
                case 'dream-client-pain-points':
                    $sanitized[$key] = $this->sanitize_textarea($value);
                    break;
                    
                case 'dream-client':
                case 'writing-style':
                case 'irresistible-offer':
                case 'ikigai':
                case 'topic':
                    $sanitized[$key] = $this->sanitize_text($value);
                    break;
                    
                default:
                    $sanitized[$key] = $this->sanitize_text($value);
                    break;
            }
        }
        
        $this->log_debug('User meta sanitized', array(
            'fields_count' => count($sanitized)
        ));
        
        return $sanitized;
    }
    
    /**
     * Get field label
     *
     * @param string $field Field name
     * @return string
     */
    private function get_field_label($field) {
        $labels = array(
            'personal-context' => __('Personal Context', 'xelite-repost-engine'),
            'dream-client' => __('Dream Client', 'xelite-repost-engine'),
            'writing-style' => __('Writing Style', 'xelite-repost-engine'),
            'irresistible-offer' => __('Irresistible Offer', 'xelite-repost-engine'),
            'dream-client-pain-points' => __('Dream Client Pain Points', 'xelite-repost-engine'),
            'ikigai' => __('Ikigai', 'xelite-repost-engine'),
            'topic' => __('Topic', 'xelite-repost-engine'),
        );
        
        return isset($labels[$field]) ? $labels[$field] : ucfirst(str_replace('-', ' ', $field));
    }
    
    /**
     * Get required fields
     *
     * @return array
     */
    public function get_required_fields() {
        return $this->required_fields;
    }
    
    /**
     * Get field labels
     *
     * @return array
     */
    public function get_field_labels() {
        $labels = array();
        foreach ($this->required_fields as $field) {
            $labels[$field] = $this->get_field_label($field);
        }
        return $labels;
    }
    
    /**
     * Get field descriptions
     *
     * @return array
     */
    public function get_field_descriptions() {
        return array(
            'personal-context' => __('Describe your background, expertise, and what makes you unique.', 'xelite-repost-engine'),
            'dream-client' => __('Who is your ideal client or audience?', 'xelite-repost-engine'),
            'writing-style' => __('How do you typically communicate? (e.g., casual, professional, humorous)', 'xelite-repost-engine'),
            'irresistible-offer' => __('What is your main value proposition or offer?', 'xelite-repost-engine'),
            'dream-client-pain-points' => __('What problems does your dream client face?', 'xelite-repost-engine'),
            'ikigai' => __('What drives you? Your purpose or passion.', 'xelite-repost-engine'),
            'topic' => __('What is your main topic or niche?', 'xelite-repost-engine'),
        );
    }
} 