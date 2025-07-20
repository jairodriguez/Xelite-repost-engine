<?php
/**
 * Service Container for dependency management
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service Container class
 */
class XeliteRepostEngine_Container {
    
    /**
     * Container instance
     *
     * @var XeliteRepostEngine_Container
     */
    private static $instance = null;
    
    /**
     * Registered services
     *
     * @var array
     */
    private $services = array();
    
    /**
     * Service instances
     *
     * @var array
     */
    private $instances = array();
    
    /**
     * Get container instance
     *
     * @return XeliteRepostEngine_Container
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register a service
     *
     * @param string   $name     Service name
     * @param callable $callback Service callback
     * @param bool     $shared   Whether the service is shared (singleton)
     * @return void
     */
    public function register($name, $callback, $shared = true) {
        $this->services[$name] = array(
            'callback' => $callback,
            'shared'   => $shared,
        );
    }
    
    /**
     * Get a service
     *
     * @param string $name Service name
     * @return mixed
     */
    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new Exception("Service '{$name}' not found");
        }
        
        $service = $this->services[$name];
        
        if ($service['shared']) {
            if (!isset($this->instances[$name])) {
                $this->instances[$name] = call_user_func($service['callback'], $this);
            }
            return $this->instances[$name];
        }
        
        return call_user_func($service['callback'], $this);
    }
    
    /**
     * Check if service exists
     *
     * @param string $name Service name
     * @return bool
     */
    public function has($name) {
        return isset($this->services[$name]);
    }
    
    /**
     * Remove a service
     *
     * @param string $name Service name
     * @return void
     */
    public function remove($name) {
        unset($this->services[$name]);
        unset($this->instances[$name]);
    }
    
    /**
     * Clear all services
     *
     * @return void
     */
    public function clear() {
        $this->services = array();
        $this->instances = array();
    }
    
    /**
     * Get all registered services
     *
     * @return array
     */
    public function get_services() {
        return array_keys($this->services);
    }
    
    /**
     * Register default services
     *
     * @return void
     */
    public function register_default_services() {
        // Register database service
        $this->register('database', function($container) {
            return new XeliteRepostEngine_Database();
        }, true);
        
        // Register API service
        $this->register('api', function($container) {
            return new XeliteRepostEngine_API();
        }, true);
        
        // Register user meta service
        $this->register('user_meta', function($container) {
            return new XeliteRepostEngine_User_Meta();
        }, true);
        
        // Register admin service
        $this->register('admin', function($container) {
            return new XeliteRepostEngine_Admin();
        }, true);
        
        // Register public service
        $this->register('public', function($container) {
            return new XeliteRepostEngine_Public();
        }, true);
        
        // Register assets service
        $this->register('assets', function($container) {
            return new XeliteRepostEngine_Assets();
        }, true);
        
        // Register X Auth service
        $this->register('x_auth', function($container) {
            return new XeliteRepostEngine_X_Auth();
        }, true);
        
        // Register X API service
        $this->register('x_api', function($container) {
            return new XeliteRepostEngine_X_API($container->get('x_auth'));
        }, true);
        
        // Register X Processor service
        $this->register('x_processor', function($container) {
            return new XeliteRepostEngine_X_Processor($container->get('database'), $container->get('x_api'));
        }, true);
        
        // Register Scraper service
        $this->register('scraper', function($container) {
            return new XeliteRepostEngine_Scraper($container->get('x_api'), $container->get('x_processor'), $container->get('database'));
        }, true);

        // Register Cron service
        $this->register('cron', function($container) {
            return new XeliteRepostEngine_Cron($container->get('scraper'), $container->get('database'), $container->get('logger'));
        }, true);

        // Register Cron Admin service
        $this->register('cron_admin', function($container) {
            return new XeliteRepostEngine_Cron_Admin($container->get('cron'));
        }, true);

        // Register WooCommerce service
        $this->register('woocommerce', function($container) {
            return new XeliteRepostEngine_WooCommerce($container->get('database'), $container->get('user_meta'), $container->get('logger'));
        }, true);
    }
} 