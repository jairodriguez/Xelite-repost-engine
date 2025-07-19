<?php
/**
 * Test X API Authentication
 *
 * This file contains comprehensive tests for the X API authentication
 * functionality to ensure secure credential storage and connection testing.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for X API authentication
 */
class XeliteRepostEngine_X_Auth_Test {
    
    /**
     * Run all tests
     */
    public static function run_all_tests() {
        echo "<h2>X API Authentication Tests</h2>\n";
        
        self::test_credential_storage();
        self::test_credential_retrieval();
        self::test_credential_validation();
        self::test_credential_deletion();
        self::test_encryption_decryption();
        self::test_connection_testing();
        self::test_admin_integration();
        self::test_security_measures();
        
        echo "<h3>All X API authentication tests completed!</h3>\n";
    }
    
    /**
     * Test credential storage
     */
    public static function test_credential_storage() {
        echo "<h3>Test: Credential Storage</h3>\n";
        
        $x_auth = xelite_repost_engine()->container->get('x_auth');
        
        // Test valid credentials
        $valid_credentials = array(
            'consumer_key' => 'test_consumer_key_123',
            'consumer_secret' => 'test_consumer_secret_456',
            'access_token' => 'test_access_token_789',
            'access_token_secret' => 'test_access_token_secret_012'
        );
        
        $result = $x_auth->store_credentials($valid_credentials);
        
        echo "<p><strong>Valid credentials storage:</strong> " . ($result ? 'PASS' : 'FAIL') . "</p>\n";
        
        // Test invalid credentials (missing fields)
        $invalid_credentials = array(
            'consumer_key' => 'test_key',
            'consumer_secret' => 'test_secret'
            // Missing access_token and access_token_secret
        );
        
        $result = $x_auth->store_credentials($invalid_credentials);
        
        echo "<p><strong>Invalid credentials storage:</strong> " . (!$result ? 'PASS' : 'FAIL') . "</p>\n";
        
        // Test empty credentials
        $result = $x_auth->store_credentials(array());
        
        echo "<p><strong>Empty credentials storage:</strong> " . (!$result ? 'PASS' : 'FAIL') . "</p>\n";
        
        echo "<hr>\n";
    }
    
    /**
     * Test credential retrieval
     */
    public static function test_credential_retrieval() {
        echo "<h3>Test: Credential Retrieval</h3>\n";
        
        $x_auth = xelite_repost_engine()->container->get('x_auth');
        
        // Store test credentials first
        $test_credentials = array(
            'consumer_key' => 'retrieve_test_key',
            'consumer_secret' => 'retrieve_test_secret',
            'access_token' => 'retrieve_test_token',
            'access_token_secret' => 'retrieve_test_token_secret'
        );
        
        $x_auth->store_credentials($test_credentials);
        
        // Test retrieval
        $retrieved_credentials = $x_auth->get_credentials();
        
        if ($retrieved_credentials) {
            $match = true;
            foreach ($test_credentials as $key => $value) {
                if ($retrieved_credentials[$key] !== $value) {
                    $match = false;
                    break;
                }
            }
            echo "<p><strong>Credential retrieval:</strong> " . ($match ? 'PASS' : 'FAIL') . "</p>\n";
        } else {
            echo "<p><strong>Credential retrieval:</strong> FAIL</p>\n";
        }
        
        // Test has_credentials
        $has_credentials = $x_auth->has_credentials();
        echo "<p><strong>Has credentials check:</strong> " . ($has_credentials ? 'PASS' : 'FAIL') . "</p>\n";
        
        echo "<hr>\n";
    }
    
    /**
     * Test credential validation
     */
    public static function test_credential_validation() {
        echo "<h3>Test: Credential Validation</h3>\n";
        
        $x_auth = xelite_repost_engine()->container->get('x_auth');
        
        // Test various validation scenarios
        $test_cases = array(
            'valid_credentials' => array(
                'consumer_key' => 'valid_key',
                'consumer_secret' => 'valid_secret',
                'access_token' => 'valid_token',
                'access_token_secret' => 'valid_token_secret'
            ),
            'missing_consumer_key' => array(
                'consumer_secret' => 'test_secret',
                'access_token' => 'test_token',
                'access_token_secret' => 'test_token_secret'
            ),
            'missing_consumer_secret' => array(
                'consumer_key' => 'test_key',
                'access_token' => 'test_token',
                'access_token_secret' => 'test_token_secret'
            ),
            'missing_access_token' => array(
                'consumer_key' => 'test_key',
                'consumer_secret' => 'test_secret',
                'access_token_secret' => 'test_token_secret'
            ),
            'missing_access_token_secret' => array(
                'consumer_key' => 'test_key',
                'consumer_secret' => 'test_secret',
                'access_token' => 'test_token'
            ),
            'empty_values' => array(
                'consumer_key' => '',
                'consumer_secret' => '',
                'access_token' => '',
                'access_token_secret' => ''
            )
        );
        
        foreach ($test_cases as $case_name => $credentials) {
            $result = $x_auth->store_credentials($credentials);
            $expected = ($case_name === 'valid_credentials');
            echo "<p><strong>{$case_name}:</strong> " . ($result === $expected ? 'PASS' : 'FAIL') . "</p>\n";
        }
        
        echo "<hr>\n";
    }
    
    /**
     * Test credential deletion
     */
    public static function test_credential_deletion() {
        echo "<h3>Test: Credential Deletion</h3>\n";
        
        $x_auth = xelite_repost_engine()->container->get('x_auth');
        
        // Store credentials first
        $test_credentials = array(
            'consumer_key' => 'delete_test_key',
            'consumer_secret' => 'delete_test_secret',
            'access_token' => 'delete_test_token',
            'access_token_secret' => 'delete_test_token_secret'
        );
        
        $x_auth->store_credentials($test_credentials);
        
        // Verify credentials exist
        $has_credentials_before = $x_auth->has_credentials();
        
        // Delete credentials
        $delete_result = $x_auth->delete_credentials();
        
        // Verify credentials are gone
        $has_credentials_after = $x_auth->has_credentials();
        
        echo "<p><strong>Credentials existed before deletion:</strong> " . ($has_credentials_before ? 'PASS' : 'FAIL') . "</p>\n";
        echo "<p><strong>Deletion operation:</strong> " . ($delete_result ? 'PASS' : 'FAIL') . "</p>\n";
        echo "<p><strong>Credentials removed after deletion:</strong> " . (!$has_credentials_after ? 'PASS' : 'FAIL') . "</p>\n";
        
        echo "<hr>\n";
    }
    
    /**
     * Test encryption and decryption
     */
    public static function test_encryption_decryption() {
        echo "<h3>Test: Encryption and Decryption</h3>\n";
        
        $x_auth = xelite_repost_engine()->container->get('x_auth');
        
        // Test data
        $test_data = 'This is a test string with special characters: !@#$%^&*()_+-=[]{}|;:,.<>?';
        
        // Use reflection to access private methods
        $reflection = new ReflectionClass($x_auth);
        
        $encrypt_method = $reflection->getMethod('encrypt_data');
        $encrypt_method->setAccessible(true);
        
        $decrypt_method = $reflection->getMethod('decrypt_data');
        $decrypt_method->setAccessible(true);
        
        try {
            // Test encryption
            $encrypted = $encrypt_method->invoke($x_auth, $test_data);
            echo "<p><strong>Encryption:</strong> " . (!empty($encrypted) && $encrypted !== $test_data ? 'PASS' : 'FAIL') . "</p>\n";
            
            // Test decryption
            $decrypted = $decrypt_method->invoke($x_auth, $encrypted);
            echo "<p><strong>Decryption:</strong> " . ($decrypted === $test_data ? 'PASS' : 'FAIL') . "</p>\n";
            
            // Test with empty string
            $encrypted_empty = $encrypt_method->invoke($x_auth, '');
            $decrypted_empty = $decrypt_method->invoke($x_auth, $encrypted_empty);
            echo "<p><strong>Empty string encryption/decryption:</strong> " . ($decrypted_empty === '' ? 'PASS' : 'FAIL') . "</p>\n";
            
        } catch (Exception $e) {
            echo "<p><strong>Encryption/Decryption test:</strong> FAIL - " . $e->getMessage() . "</p>\n";
        }
        
        echo "<hr>\n";
    }
    
    /**
     * Test connection testing
     */
    public static function test_connection_testing() {
        echo "<h3>Test: Connection Testing</h3>\n";
        
        $x_auth = xelite_repost_engine()->container->get('x_auth');
        
        // Test without credentials
        $result_no_credentials = $x_auth->test_connection();
        echo "<p><strong>Test without credentials:</strong> " . 
             (!$result_no_credentials['success'] && $result_no_credentials['error_code'] === 'no_credentials' ? 'PASS' : 'FAIL') . "</p>\n";
        
        // Test with invalid credentials
        $invalid_credentials = array(
            'consumer_key' => 'invalid_key',
            'consumer_secret' => 'invalid_secret',
            'access_token' => 'invalid_token',
            'access_token_secret' => 'invalid_token_secret'
        );
        
        $x_auth->store_credentials($invalid_credentials);
        $result_invalid = $x_auth->test_connection();
        
        echo "<p><strong>Test with invalid credentials:</strong> " . 
             (!$result_invalid['success'] ? 'PASS' : 'FAIL') . "</p>\n";
        
        // Test connection status caching
        $status_before = $x_auth->get_connection_status();
        $x_auth->test_connection();
        $status_after = $x_auth->get_connection_status();
        
        echo "<p><strong>Connection status caching:</strong> " . 
             (is_array($status_after) ? 'PASS' : 'FAIL') . "</p>\n";
        
        // Clean up
        $x_auth->delete_credentials();
        
        echo "<hr>\n";
    }
    
    /**
     * Test admin integration
     */
    public static function test_admin_integration() {
        echo "<h3>Test: Admin Integration</h3>\n";
        
        $x_auth = xelite_repost_engine()->container->get('x_auth');
        
        // Test admin settings fields generation
        $settings_fields = $x_auth->get_admin_settings_fields();
        
        $has_required_sections = isset($settings_fields['x_api_section']) && isset($settings_fields['x_api_connection']);
        $has_required_fields = isset($settings_fields['x_api_section']['fields']['x_api_consumer_key']);
        
        echo "<p><strong>Admin settings fields structure:</strong> " . ($has_required_sections ? 'PASS' : 'FAIL') . "</p>\n";
        echo "<p><strong>Required fields present:</strong> " . ($has_required_fields ? 'PASS' : 'FAIL') . "</p>\n";
        
        // Test field count
        $expected_fields = 4; // consumer_key, consumer_secret, access_token, access_token_secret
        $actual_fields = count($settings_fields['x_api_section']['fields']);
        echo "<p><strong>Correct number of fields:</strong> " . ($actual_fields === $expected_fields ? 'PASS' : 'FAIL') . "</p>\n";
        
        echo "<hr>\n";
    }
    
    /**
     * Test security measures
     */
    public static function test_security_measures() {
        echo "<h3>Test: Security Measures</h3>\n";
        
        $x_auth = xelite_repost_engine()->container->get('x_auth');
        
        // Test nonce verification (simulate AJAX request)
        $_POST['nonce'] = 'invalid_nonce';
        $_POST['action'] = 'xelite_test_x_connection';
        
        // Capture output to check for security errors
        ob_start();
        $x_auth->handle_test_connection();
        $output = ob_get_clean();
        
        // Check if security error was triggered
        $security_error = strpos($output, 'Security check failed') !== false || 
                         strpos($output, 'wp_send_json_error') !== false;
        
        echo "<p><strong>Nonce verification:</strong> " . ($security_error ? 'PASS' : 'FAIL') . "</p>\n";
        
        // Test capability checks
        // This would require mocking user capabilities, but we can test the method exists
        $reflection = new ReflectionClass($x_auth);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $has_security_methods = false;
        foreach ($methods as $method) {
            if (strpos($method->getName(), 'handle_') === 0) {
                $has_security_methods = true;
                break;
            }
        }
        
        echo "<p><strong>Security methods present:</strong> " . ($has_security_methods ? 'PASS' : 'FAIL') . "</p>\n";
        
        // Test credential isolation
        $test_credentials = array(
            'consumer_key' => 'security_test_key',
            'consumer_secret' => 'security_test_secret',
            'access_token' => 'security_test_token',
            'access_token_secret' => 'security_test_token_secret'
        );
        
        $x_auth->store_credentials($test_credentials);
        $retrieved = $x_auth->get_credentials();
        
        // Check that credentials are encrypted in database
        $raw_option = get_option('xelite_repost_engine_x_credentials');
        $is_encrypted = $raw_option !== json_encode($test_credentials);
        
        echo "<p><strong>Credential encryption in database:</strong> " . ($is_encrypted ? 'PASS' : 'FAIL') . "</p>\n";
        
        // Clean up
        $x_auth->delete_credentials();
        
        echo "<hr>\n";
    }
}

// Run tests if this file is accessed directly
if (defined('WP_CLI') && WP_CLI) {
    XeliteRepostEngine_X_Auth_Test::run_all_tests();
} elseif (isset($_GET['run_x_auth_tests']) && current_user_can('manage_options')) {
    XeliteRepostEngine_X_Auth_Test::run_all_tests();
} 