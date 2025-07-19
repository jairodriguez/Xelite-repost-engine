<?php
/**
 * Abstract base class for all plugin classes
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract base class for all plugin classes
 */
abstract class XeliteRepostEngine_Abstract_Base {
    
    /**
     * Plugin instance
     *
     * @var XeliteRepostEngine
     */
    protected $plugin;
    
    /**
     * Plugin version
     *
     * @var string
     */
    protected $version;
    
    /**
     * Plugin directory path
     *
     * @var string
     */
    protected $plugin_dir;
    
    /**
     * Plugin URL
     *
     * @var string
     */
    protected $plugin_url;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->plugin = xelite_repost_engine();
        $this->version = $this->plugin->version;
        $this->plugin_dir = $this->plugin->plugin_dir;
        $this->plugin_url = $this->plugin->plugin_url;
        
        $this->init();
    }
    
    /**
     * Initialize the class
     *
     * @return void
     */
    abstract protected function init();
    
    /**
     * Get plugin instance
     *
     * @return XeliteRepostEngine
     */
    protected function get_plugin() {
        return $this->plugin;
    }
    
    /**
     * Get plugin version
     *
     * @return string
     */
    protected function get_version() {
        return $this->version;
    }
    
    /**
     * Get plugin directory path
     *
     * @return string
     */
    protected function get_plugin_dir() {
        return $this->plugin_dir;
    }
    
    /**
     * Get plugin URL
     *
     * @return string
     */
    protected function get_plugin_url() {
        return $this->plugin_url;
    }
    
    /**
     * Log debug message
     *
     * @param string $message Message to log
     * @param array  $context Additional context
     * @return void
     */
    protected function log_debug($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Xelite Repost Engine Debug] %s %s',
                $message,
                !empty($context) ? json_encode($context) : ''
            ));
        }
    }
    
    /**
     * Log error message
     *
     * @param string $message Message to log
     * @param array  $context Additional context
     * @return void
     */
    protected function log_error($message, $context = array()) {
        error_log(sprintf(
            '[Xelite Repost Engine Error] %s %s',
            $message,
            !empty($context) ? json_encode($context) : ''
        ));
    }
    
    /**
     * Get option value
     *
     * @param string $option Option name
     * @param mixed  $default Default value
     * @return mixed
     */
    protected function get_option($option, $default = false) {
        return get_option('xelite_repost_engine_' . $option, $default);
    }
    
    /**
     * Update option value
     *
     * @param string $option Option name
     * @param mixed  $value  Option value
     * @return bool
     */
    protected function update_option($option, $value) {
        return update_option('xelite_repost_engine_' . $option, $value);
    }
    
    /**
     * Delete option
     *
     * @param string $option Option name
     * @return bool
     */
    protected function delete_option($option) {
        return delete_option('xelite_repost_engine_' . $option);
    }
    
    /**
     * Verify nonce
     *
     * @param string $nonce  Nonce value
     * @param string $action Nonce action
     * @return bool
     */
    protected function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Create nonce
     *
     * @param string $action Nonce action
     * @return string
     */
    protected function create_nonce($action) {
        return wp_create_nonce($action);
    }
    
    /**
     * Sanitize text
     *
     * @param string $text Text to sanitize
     * @return string
     */
    protected function sanitize_text($text) {
        return sanitize_text_field($text);
    }
    
    /**
     * Sanitize textarea
     *
     * @param string $text Text to sanitize
     * @return string
     */
    protected function sanitize_textarea($text) {
        return sanitize_textarea_field($text);
    }
    
    /**
     * Sanitize URL
     *
     * @param string $url URL to sanitize
     * @return string
     */
    protected function sanitize_url($url) {
        return esc_url_raw($url);
    }
    
    /**
     * Sanitize email
     *
     * @param string $email Email to sanitize
     * @return string
     */
    protected function sanitize_email($email) {
        return sanitize_email($email);
    }
} 