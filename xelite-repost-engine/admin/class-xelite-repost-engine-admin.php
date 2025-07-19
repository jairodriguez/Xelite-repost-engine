<?php
/**
 * Admin functionality class
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin functionality class - Main entry point
 */
class XeliteRepostEngine_Admin extends XeliteRepostEngine_Abstract_Base {
    
    /**
     * Full admin implementation instance
     *
     * @var XeliteRepostEngine_Admin_Settings
     */
    private $admin_settings;
    
    /**
     * Initialize the class
     */
    protected function init() {
        // Initialize the full admin settings implementation
        $this->admin_settings = new XeliteRepostEngine_Admin_Settings();
        $this->log_debug('Admin class initialized with settings page');
    }
} 