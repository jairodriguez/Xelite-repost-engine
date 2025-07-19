<?php
/**
 * Asset management class
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Asset management class
 */
class XeliteRepostEngine_Assets extends XeliteRepostEngine_Abstract_Base {
    
    /**
     * Registered styles
     *
     * @var array
     */
    private $styles = array();
    
    /**
     * Registered scripts
     *
     * @var array
     */
    private $scripts = array();
    
    /**
     * Asset version
     *
     * @var string
     */
    private $asset_version;
    
    /**
     * Initialize the class
     */
    protected function init() {
        $this->asset_version = $this->get_asset_version();
        $this->register_default_assets();
        $this->setup_hooks();
        
        $this->log_debug('Assets class initialized');
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Public assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        
        // Login page assets
        add_action('login_enqueue_scripts', array($this, 'enqueue_login_assets'));
        
        // Customizer assets
        add_action('customize_controls_enqueue_scripts', array($this, 'enqueue_customizer_assets'));
    }
    
    /**
     * Get asset version for cache busting
     *
     * @return string
     */
    private function get_asset_version() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return time();
        }
        
        return $this->get_version();
    }
    
    /**
     * Register default assets
     */
    private function register_default_assets() {
        // Admin styles
        $this->register_style(
            'xelite-repost-engine-admin',
            'assets/css/xelite-repost-engine-admin.css',
            array(),
            'admin'
        );
        
        // Admin scripts
        $this->register_script(
            'xelite-repost-engine-admin',
            'assets/js/xelite-repost-engine-admin.js',
            array('jquery'),
            'admin'
        );
        
        // Public styles
        $this->register_style(
            'xelite-repost-engine-public',
            'assets/css/public.css',
            array(),
            'public'
        );
        
        // Public scripts
        $this->register_script(
            'xelite-repost-engine-public',
            'assets/js/public.js',
            array('jquery'),
            'public'
        );
        
        // Dashboard styles
        $this->register_style(
            'xelite-repost-engine-dashboard',
            'assets/css/dashboard.css',
            array('xelite-repost-engine-admin'),
            'dashboard'
        );
        
        // Dashboard scripts
        $this->register_script(
            'xelite-repost-engine-dashboard',
            'assets/js/dashboard.js',
            array('jquery', 'xelite-repost-engine-admin'),
            'dashboard'
        );
    }
    
    /**
     * Register a style
     *
     * @param string $handle Style handle
     * @param string $src    Style source file
     * @param array  $deps   Dependencies
     * @param string $context Context (admin, public, etc.)
     * @param array  $args   Additional arguments
     */
    public function register_style($handle, $src, $deps = array(), $context = 'public', $args = array()) {
        $this->styles[$handle] = array(
            'src' => $src,
            'deps' => $deps,
            'context' => $context,
            'args' => $args,
        );
        
        $this->log_debug('Style registered', array(
            'handle' => $handle,
            'src' => $src,
            'context' => $context
        ));
    }
    
    /**
     * Register a script
     *
     * @param string $handle Script handle
     * @param string $src    Script source file
     * @param array  $deps   Dependencies
     * @param string $context Context (admin, public, etc.)
     * @param array  $args   Additional arguments
     */
    public function register_script($handle, $src, $deps = array(), $context = 'public', $args = array()) {
        $this->scripts[$handle] = array(
            'src' => $src,
            'deps' => $deps,
            'context' => $context,
            'args' => $args,
        );
        
        $this->log_debug('Script registered', array(
            'handle' => $handle,
            'src' => $src,
            'context' => $context
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        $screen = get_current_screen();
        
        // Enqueue admin assets
        $this->enqueue_assets_by_context('admin');
        
        // Enqueue dashboard assets on plugin pages
        if ($screen && strpos($screen->id, 'xelite-repost-engine') !== false) {
            $this->enqueue_assets_by_context('dashboard');
        }
        
        // Add inline styles for admin
        $this->add_admin_inline_styles();
        
        // Add inline scripts for admin
        $this->add_admin_inline_scripts();
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // Only enqueue if user is logged in and has access
        if (!is_user_logged_in()) {
            return;
        }
        
        // Check if user has access to the plugin
        if (!$this->user_has_access()) {
            return;
        }
        
        $this->enqueue_assets_by_context('public');
        
        // Add inline styles for public
        $this->add_public_inline_styles();
        
        // Add inline scripts for public
        $this->add_public_inline_scripts();
    }
    
    /**
     * Enqueue login assets
     */
    public function enqueue_login_assets() {
        // Login page specific assets if needed
        $this->enqueue_assets_by_context('login');
    }
    
    /**
     * Enqueue customizer assets
     */
    public function enqueue_customizer_assets() {
        // Customizer specific assets if needed
        $this->enqueue_assets_by_context('customizer');
    }
    
    /**
     * Enqueue assets by context
     *
     * @param string $context Context to enqueue
     */
    private function enqueue_assets_by_context($context) {
        // Enqueue styles
        foreach ($this->styles as $handle => $style) {
            if ($style['context'] === $context) {
                $this->enqueue_style($handle, $style);
            }
        }
        
        // Enqueue scripts
        foreach ($this->scripts as $handle => $script) {
            if ($script['context'] === $context) {
                $this->enqueue_script($handle, $script);
            }
        }
    }
    
    /**
     * Enqueue a style
     *
     * @param string $handle Style handle
     * @param array  $style  Style data
     */
    private function enqueue_style($handle, $style) {
        $src = $this->get_asset_url($style['src']);
        
        if (!$this->asset_exists($style['src'])) {
            $this->log_error('Style file not found', array(
                'handle' => $handle,
                'src' => $style['src']
            ));
            return;
        }
        
        wp_enqueue_style(
            $handle,
            $src,
            $style['deps'],
            $this->asset_version
        );
        
        $this->log_debug('Style enqueued', array(
            'handle' => $handle,
            'src' => $src
        ));
    }
    
    /**
     * Enqueue a script
     *
     * @param string $handle Script handle
     * @param array  $script Script data
     */
    private function enqueue_script($handle, $script) {
        $src = $this->get_asset_url($script['src']);
        
        if (!$this->asset_exists($script['src'])) {
            $this->log_error('Script file not found', array(
                'handle' => $handle,
                'src' => $script['src']
            ));
            return;
        }
        
        wp_enqueue_script(
            $handle,
            $src,
            $script['deps'],
            $this->asset_version,
            true
        );
        
        // Localize script with data
        $this->localize_script($handle);
        
        $this->log_debug('Script enqueued', array(
            'handle' => $handle,
            'src' => $src
        ));
    }
    
    /**
     * Get asset URL
     *
     * @param string $src Asset source
     * @return string
     */
    private function get_asset_url($src) {
        return $this->get_plugin_url() . $src;
    }
    
    /**
     * Check if asset exists
     *
     * @param string $src Asset source
     * @return bool
     */
    private function asset_exists($src) {
        $file_path = $this->get_plugin_dir() . $src;
        return file_exists($file_path);
    }
    
    /**
     * Localize script with data
     *
     * @param string $handle Script handle
     */
    private function localize_script($handle) {
        $data = array();
        
        switch ($handle) {
            case 'xelite-repost-engine-admin':
                $data = array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('xelite_repost_engine_admin_nonce'),
                    'strings' => array(
                        'confirm_delete' => __('Are you sure you want to delete this item?', 'xelite-repost-engine'),
                        'saving' => __('Saving...', 'xelite-repost-engine'),
                        'saved' => __('Saved successfully!', 'xelite-repost-engine'),
                        'error' => __('An error occurred. Please try again.', 'xelite-repost-engine'),
                    ),
                );
                break;
                
            case 'xelite-repost-engine-public':
                $data = array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('xelite_repost_engine_public_nonce'),
                    'user_id' => get_current_user_id(),
                    'strings' => array(
                        'generating' => __('Generating content...', 'xelite-repost-engine'),
                        'generated' => __('Content generated successfully!', 'xelite-repost-engine'),
                        'error' => __('An error occurred. Please try again.', 'xelite-repost-engine'),
                    ),
                );
                break;
                
            case 'xelite-repost-engine-dashboard':
                $data = array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('xelite_repost_engine_dashboard_nonce'),
                    'rest_url' => rest_url('xelite-repost-engine/v1/'),
                    'rest_nonce' => wp_create_nonce('wp_rest'),
                    'strings' => array(
                        'loading' => __('Loading...', 'xelite-repost-engine'),
                        'no_data' => __('No data available.', 'xelite-repost-engine'),
                        'error' => __('An error occurred. Please try again.', 'xelite-repost-engine'),
                    ),
                );
                break;
        }
        
        if (!empty($data)) {
            wp_localize_script($handle, 'xeliteRepostEngine', $data);
        }
    }
    
    /**
     * Add admin inline styles
     */
    private function add_admin_inline_styles() {
        $css = "
            .xelite-repost-engine-wrap {
                margin: 20px 0;
            }
            .xelite-repost-engine-wrap .notice {
                margin: 15px 0;
            }
            .xelite-repost-engine-wrap .form-table th {
                width: 200px;
            }
        ";
        
        wp_add_inline_style('xelite-repost-engine-admin', $css);
    }
    
    /**
     * Add admin inline scripts
     */
    private function add_admin_inline_scripts() {
        $js = "
            jQuery(document).ready(function($) {
                // Admin-specific JavaScript
                console.log('Xelite Repost Engine Admin loaded');
            });
        ";
        
        wp_add_inline_script('xelite-repost-engine-admin', $js);
    }
    
    /**
     * Add public inline styles
     */
    private function add_public_inline_styles() {
        $css = "
            .xelite-repost-engine-public {
                margin: 20px 0;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 5px;
            }
        ";
        
        wp_add_inline_style('xelite-repost-engine-public', $css);
    }
    
    /**
     * Add public inline scripts
     */
    private function add_public_inline_scripts() {
        $js = "
            jQuery(document).ready(function($) {
                // Public-specific JavaScript
                console.log('Xelite Repost Engine Public loaded');
            });
        ";
        
        wp_add_inline_script('xelite-repost-engine-public', $js);
    }
    
    /**
     * Check if user has access to the plugin
     *
     * @return bool
     */
    private function user_has_access() {
        // Check if WooCommerce is active and user has subscription
        if (class_exists('WooCommerce')) {
            // WooCommerce subscription check will be implemented in future tasks
            return true;
        }
        
        // For now, allow all logged-in users
        return true;
    }
    
    /**
     * Get registered styles
     *
     * @return array
     */
    public function get_registered_styles() {
        return $this->styles;
    }
    
    /**
     * Get registered scripts
     *
     * @return array
     */
    public function get_registered_scripts() {
        return $this->scripts;
    }
    
    /**
     * Deregister an asset
     *
     * @param string $handle Asset handle
     * @param string $type   Asset type (style or script)
     */
    public function deregister_asset($handle, $type = 'script') {
        if ($type === 'style') {
            unset($this->styles[$handle]);
            wp_deregister_style($handle);
        } else {
            unset($this->scripts[$handle]);
            wp_deregister_script($handle);
        }
        
        $this->log_debug('Asset deregistered', array(
            'handle' => $handle,
            'type' => $type
        ));
    }
    
    /**
     * Enqueue asset conditionally
     *
     * @param string $handle Asset handle
     * @param string $type   Asset type (style or script)
     * @param callable $condition Condition function
     */
    public function enqueue_asset_conditionally($handle, $type = 'script', $condition = null) {
        if ($condition && !call_user_func($condition)) {
            return;
        }
        
        if ($type === 'style') {
            if (isset($this->styles[$handle])) {
                $this->enqueue_style($handle, $this->styles[$handle]);
            }
        } else {
            if (isset($this->scripts[$handle])) {
                $this->enqueue_script($handle, $this->scripts[$handle]);
            }
        }
    }
} 