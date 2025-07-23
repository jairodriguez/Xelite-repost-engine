<?php
/**
 * Bootstrap file for running tests outside of WordPress context
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Define WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

if (!defined('WP_PLUGIN_URL')) {
    define('WP_PLUGIN_URL', 'http://localhost/wp-content/plugins');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

// Mock WordPress functions
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        echo "WP_DIE: $message\n";
        exit(1);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true; // Mock for testing
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'test_nonce_' . $action;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        $filtered = trim(strip_tags($str));
        return $filtered;
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        $filtered = trim($str);
        return $filtered;
    }
}

if (!function_exists('sanitize_url')) {
    function sanitize_url($url, $protocols = null) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        return true;
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('wp_ajax')) {
    function wp_ajax($action) {
        return true;
    }
}

if (!function_exists('wp_ajax_nopriv')) {
    function wp_ajax_nopriv($action) {
        return true;
    }
}

if (!function_exists('wp_send_json')) {
    function wp_send_json($response, $status_code = null) {
        echo json_encode($response);
        exit;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {
        wp_send_json(array('success' => true, 'data' => $data), $status_code);
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {
        wp_send_json(array('success' => false, 'data' => $data), $status_code);
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key = '', $single = false) {
        return '';
    }
}

if (!function_exists('update_user_meta')) {
    function update_user_meta($user_id, $meta_key, $meta_value, $prev_value = '') {
        return true;
    }
}

if (!function_exists('delete_user_meta')) {
    function delete_user_meta($user_id, $meta_key, $meta_value = '') {
        return true;
    }
}

if (!function_exists('wp_insert_user')) {
    function wp_insert_user($userdata) {
        return 1;
    }
}

if (!function_exists('wp_delete_user')) {
    function wp_delete_user($id, $reassign = null) {
        return true;
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '', $filter = 'raw') {
        switch ($show) {
            case 'name':
                return 'Test Site';
            case 'url':
                return 'http://localhost';
            case 'version':
                return '6.0';
            default:
                return '';
        }
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        return true;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        return true;
    }
}

// Mock wpdb class
if (!class_exists('wpdb')) {
    class wpdb {
        public $prefix = 'wp_';
        public $last_error = '';
        public $last_query = '';
        public $last_result = array();
        public $num_rows = 0;
        
        public function get_charset_collate() {
            return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }
        
        public function query($query) {
            $this->last_query = $query;
            return true;
        }
        
        public function get_results($query = null, $output_type = OBJECT) {
            return array();
        }
        
        public function get_row($query = null, $output_type = OBJECT, $y = 0) {
            return null;
        }
        
        public function get_var($query = null, $x = 0, $y = 0) {
            return null;
        }
        
        public function insert($table, $data, $format = null) {
            return 1;
        }
        
        public function update($table, $data, $where, $format = null, $where_format = null) {
            return 1;
        }
        
        public function delete($table, $where, $where_format = null) {
            return 1;
        }
        
        public function prepare($query, ...$args) {
            return $query;
        }
    }
}

// Include plugin classes for testing
require_once dirname(__DIR__) . '/includes/class-xelite-repost-engine-pdf-guide.php';
require_once dirname(__DIR__) . '/includes/class-xelite-repost-engine-tutorials.php';

// Initialize global wpdb
global $wpdb;
$wpdb = new wpdb();

// Include the main plugin file
require_once dirname(__FILE__) . '/../xelite-repost-engine.php';

// Initialize the plugin
if (function_exists('xelite_repost_engine')) {
    $plugin = xelite_repost_engine();
} 