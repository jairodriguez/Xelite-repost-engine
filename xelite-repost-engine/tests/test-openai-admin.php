<?php
/**
 * Test OpenAI Admin Settings
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

/**
 * Test OpenAI Admin Settings Class
 */
class TestXeliteRepostEngine_OpenAI_Admin extends TestCase {

    /**
     * Admin settings instance
     *
     * @var XeliteRepostEngine_Admin_Settings
     */
    private $admin_settings;

    /**
     * Mock container
     *
     * @var PHPUnit\Framework\MockObject\MockObject
     */
    private $mock_container;

    /**
     * Mock OpenAI service
     *
     * @var PHPUnit\Framework\MockObject\MockObject
     */
    private $mock_openai;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void {
        parent::setUp();

        // Create mock container
        $this->mock_container = $this->createMock('XeliteRepostEngine_Container');

        // Create mock OpenAI service
        $this->mock_openai = $this->createMock('XeliteRepostEngine_OpenAI');

        // Create admin settings instance
        $this->admin_settings = new XeliteRepostEngine_Admin_Settings();
    }

    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf('XeliteRepostEngine_Admin_Settings', $this->admin_settings);
    }

    /**
     * Test OpenAI tab initialization
     */
    public function test_openai_tab_initialization() {
        $reflection = new ReflectionClass($this->admin_settings);
        $method = $reflection->getMethod('init_tabs');
        $method->setAccessible(true);

        $method->invoke($this->admin_settings);

        $property = $reflection->getProperty('tabs');
        $property->setAccessible(true);
        $tabs = $property->getValue($this->admin_settings);

        $this->assertArrayHasKey('openai', $tabs);
        $this->assertEquals('OpenAI Integration', $tabs['openai']['title']);
        $this->assertStringContainsString('OpenAI API settings', $tabs['openai']['description']);
    }

    /**
     * Test OpenAI settings registration
     */
    public function test_openai_settings_registration() {
        $reflection = new ReflectionClass($this->admin_settings);
        $method = $reflection->getMethod('register_openai_settings');
        $method->setAccessible(true);

        // Mock WordPress functions
        global $wp_settings_sections, $wp_settings_fields;
        $wp_settings_sections = array();
        $wp_settings_fields = array();

        $method->invoke($this->admin_settings);

        // Check that sections were registered
        $this->assertArrayHasKey('xelite-repost-engine', $wp_settings_sections);
        $this->assertArrayHasKey('openai_api_section', $wp_settings_sections['xelite-repost-engine']);
        $this->assertArrayHasKey('openai_content_section', $wp_settings_sections['xelite-repost-engine']);
        $this->assertArrayHasKey('openai_usage_section', $wp_settings_sections['xelite-repost-engine']);
        $this->assertArrayHasKey('openai_testing_section', $wp_settings_sections['xelite-repost-engine']);
    }

    /**
     * Test OpenAI API key field callback
     */
    public function test_openai_api_key_field_callback() {
        // Set up test data
        update_option('xelite_repost_engine_openai_api_key', 'test_api_key_123');

        // Capture output
        ob_start();
        $this->admin_settings->openai_api_key_field_callback();
        $output = ob_get_clean();

        // Check output
        $this->assertStringContainsString('test_api_key_123', $output);
        $this->assertStringContainsString('openai_api_key', $output);
        $this->assertStringContainsString('Test Connection', $output);
        $this->assertStringContainsString('xelite_openai_test_connection', $output);

        // Clean up
        delete_option('xelite_repost_engine_openai_api_key');
    }

    /**
     * Test OpenAI model field callback
     */
    public function test_openai_model_field_callback() {
        // Set up test data
        update_option('xelite_repost_engine_openai_model', 'gpt-4-turbo');

        // Capture output
        ob_start();
        $this->admin_settings->openai_model_field_callback();
        $output = ob_get_clean();

        // Check output
        $this->assertStringContainsString('gpt-4', $output);
        $this->assertStringContainsString('gpt-4-turbo', $output);
        $this->assertStringContainsString('gpt-3.5-turbo', $output);
        $this->assertStringContainsString('selected', $output);

        // Clean up
        delete_option('xelite_repost_engine_openai_model');
    }

    /**
     * Test OpenAI connection status field callback
     */
    public function test_openai_connection_status_field_callback() {
        // Test with no API key
        ob_start();
        $this->admin_settings->openai_connection_status_field_callback();
        $output = ob_get_clean();

        $this->assertStringContainsString('API Key not configured', $output);
        $this->assertStringContainsString('dashicons-warning', $output);

        // Test with API key
        update_option('xelite_repost_engine_openai_api_key', 'test_key');
        
        ob_start();
        $this->admin_settings->openai_connection_status_field_callback();
        $output = ob_get_clean();

        $this->assertStringContainsString('Checking connection', $output);
        $this->assertStringContainsString('dashicons-update', $output);

        // Clean up
        delete_option('xelite_repost_engine_openai_api_key');
    }

    /**
     * Test OpenAI temperature field callback
     */
    public function test_openai_temperature_field_callback() {
        // Set up test data
        update_option('xelite_repost_engine_openai_temperature', 0.8);

        // Capture output
        ob_start();
        $this->admin_settings->openai_temperature_field_callback();
        $output = ob_get_clean();

        // Check output
        $this->assertStringContainsString('type="range"', $output);
        $this->assertStringContainsString('min="0"', $output);
        $this->assertStringContainsString('max="2"', $output);
        $this->assertStringContainsString('step="0.1"', $output);
        $this->assertStringContainsString('value="0.8"', $output);
        $this->assertStringContainsString('Focused (0.0)', $output);
        $this->assertStringContainsString('Creative (2.0)', $output);

        // Clean up
        delete_option('xelite_repost_engine_openai_temperature');
    }

    /**
     * Test OpenAI max tokens field callback
     */
    public function test_openai_max_tokens_field_callback() {
        // Set up test data
        update_option('xelite_repost_engine_openai_max_tokens', 500);

        // Capture output
        ob_start();
        $this->admin_settings->openai_max_tokens_field_callback();
        $output = ob_get_clean();

        // Check output
        $this->assertStringContainsString('type="number"', $output);
        $this->assertStringContainsString('min="50"', $output);
        $this->assertStringContainsString('max="4000"', $output);
        $this->assertStringContainsString('value="500"', $output);

        // Clean up
        delete_option('xelite_repost_engine_openai_max_tokens');
    }

    /**
     * Test OpenAI content tone field callback
     */
    public function test_openai_content_tone_field_callback() {
        // Set up test data
        update_option('xelite_repost_engine_openai_content_tone', 'professional');

        // Capture output
        ob_start();
        $this->admin_settings->openai_content_tone_field_callback();
        $output = ob_get_clean();

        // Check output
        $this->assertStringContainsString('conversational', $output);
        $this->assertStringContainsString('professional', $output);
        $this->assertStringContainsString('casual', $output);
        $this->assertStringContainsString('enthusiastic', $output);
        $this->assertStringContainsString('informative', $output);
        $this->assertStringContainsString('humorous', $output);
        $this->assertStringContainsString('authoritative', $output);
        $this->assertStringContainsString('selected', $output);

        // Clean up
        delete_option('xelite_repost_engine_openai_content_tone');
    }

    /**
     * Test OpenAI daily limit field callback
     */
    public function test_openai_daily_limit_field_callback() {
        // Set up test data
        update_option('xelite_repost_engine_openai_daily_limit', 100);

        // Capture output
        ob_start();
        $this->admin_settings->openai_daily_limit_field_callback();
        $output = ob_get_clean();

        // Check output
        $this->assertStringContainsString('type="number"', $output);
        $this->assertStringContainsString('min="0"', $output);
        $this->assertStringContainsString('max="10000"', $output);
        $this->assertStringContainsString('value="100"', $output);

        // Clean up
        delete_option('xelite_repost_engine_openai_daily_limit');
    }

    /**
     * Test OpenAI monthly budget field callback
     */
    public function test_openai_monthly_budget_field_callback() {
        // Set up test data
        update_option('xelite_repost_engine_openai_monthly_budget', 50.00);

        // Capture output
        ob_start();
        $this->admin_settings->openai_monthly_budget_field_callback();
        $output = ob_get_clean();

        // Check output
        $this->assertStringContainsString('type="number"', $output);
        $this->assertStringContainsString('min="0"', $output);
        $this->assertStringContainsString('max="1000"', $output);
        $this->assertStringContainsString('step="0.01"', $output);
        $this->assertStringContainsString('value="50"', $output);

        // Clean up
        delete_option('xelite_repost_engine_openai_monthly_budget');
    }

    /**
     * Test OpenAI usage dashboard field callback
     */
    public function test_openai_usage_dashboard_field_callback() {
        // Capture output
        ob_start();
        $this->admin_settings->openai_usage_dashboard_field_callback();
        $output = ob_get_clean();

        // Check output
        $this->assertStringContainsString('openai-usage-dashboard', $output);
        $this->assertStringContainsString('usage-stats', $output);
        $this->assertStringContainsString('Today\'s Usage', $output);
        $this->assertStringContainsString('This Month', $output);
        $this->assertStringContainsString('Total Cost', $output);
        $this->assertStringContainsString('Refresh Stats', $output);
        $this->assertStringContainsString('xelite_openai_refresh_usage', $output);
    }

    /**
     * Test OpenAI test generation field callback
     */
    public function test_openai_test_generation_field_callback() {
        // Capture output
        ob_start();
        $this->admin_settings->openai_test_generation_field_callback();
        $output = ob_get_clean();

        // Check output
        $this->assertStringContainsString('openai-test-generation', $output);
        $this->assertStringContainsString('test-inputs', $output);
        $this->assertStringContainsString('test_user_context', $output);
        $this->assertStringContainsString('test_patterns', $output);
        $this->assertStringContainsString('Generate Test Content', $output);
        $this->assertStringContainsString('xelite_openai_test_generation', $output);
        $this->assertStringContainsString('test-results', $output);
        $this->assertStringContainsString('generated-content', $output);
        $this->assertStringContainsString('tokens-used', $output);
        $this->assertStringContainsString('generation-time', $output);
    }

    /**
     * Test AJAX handler for testing OpenAI connection
     */
    public function test_ajax_test_openai_connection() {
        // Mock container and OpenAI service
        $mock_container = $this->createMock('XeliteRepostEngine_Container');
        $mock_openai = $this->createMock('XeliteRepostEngine_OpenAI');

        // Mock successful connection test
        $mock_openai->method('test_connection')
            ->willReturn(array(
                'connected' => true,
                'models' => array('gpt-4', 'gpt-3.5-turbo')
            ));

        $mock_container->method('get')
            ->with('openai')
            ->willReturn($mock_openai);

        // Mock the container instance
        $reflection = new ReflectionClass('XeliteRepostEngine_Container');
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, $mock_container);

        // Mock nonce verification
        add_filter('wp_verify_nonce', function() { return true; });

        // Mock current user capabilities
        add_filter('current_user_can', function() { return true; });

        // Test successful connection
        $_POST['nonce'] = 'test_nonce';
        
        ob_start();
        $this->admin_settings->ajax_test_openai_connection();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertTrue($response['success']);
        $this->assertTrue($response['data']['connected']);

        // Clean up
        remove_all_filters('wp_verify_nonce');
        remove_all_filters('current_user_can');
        unset($_POST['nonce']);
    }

    /**
     * Test AJAX handler for testing OpenAI connection with error
     */
    public function test_ajax_test_openai_connection_error() {
        // Mock container and OpenAI service
        $mock_container = $this->createMock('XeliteRepostEngine_Container');
        $mock_openai = $this->createMock('XeliteRepostEngine_OpenAI');

        // Mock failed connection test
        $mock_openai->method('test_connection')
            ->willReturn(array(
                'connected' => false,
                'error' => 'Invalid API key'
            ));

        $mock_container->method('get')
            ->with('openai')
            ->willReturn($mock_openai);

        // Mock the container instance
        $reflection = new ReflectionClass('XeliteRepostEngine_Container');
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, $mock_container);

        // Mock nonce verification
        add_filter('wp_verify_nonce', function() { return true; });

        // Mock current user capabilities
        add_filter('current_user_can', function() { return true; });

        // Test failed connection
        $_POST['nonce'] = 'test_nonce';
        
        ob_start();
        $this->admin_settings->ajax_test_openai_connection();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertFalse($response['success']);
        $this->assertEquals('Invalid API key', $response['data']);

        // Clean up
        remove_all_filters('wp_verify_nonce');
        remove_all_filters('current_user_can');
        unset($_POST['nonce']);
    }

    /**
     * Test AJAX handler for refreshing OpenAI usage stats
     */
    public function test_ajax_refresh_openai_usage() {
        // Mock container and OpenAI service
        $mock_container = $this->createMock('XeliteRepostEngine_Container');
        $mock_openai = $this->createMock('XeliteRepostEngine_OpenAI');

        // Mock usage data
        $mock_openai->method('get_usage')
            ->willReturnMap(array(
                array(date('Y-m-d'), array('total_usage' => 1000)),
                array(date('Y-m'), array('total_usage' => 5000))
            ));

        $mock_container->method('get')
            ->with('openai')
            ->willReturn($mock_openai);

        // Mock the container instance
        $reflection = new ReflectionClass('XeliteRepostEngine_Container');
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, $mock_container);

        // Mock nonce verification
        add_filter('wp_verify_nonce', function() { return true; });

        // Mock current user capabilities
        add_filter('current_user_can', function() { return true; });

        // Test usage refresh
        $_POST['nonce'] = 'test_nonce';
        
        ob_start();
        $this->admin_settings->ajax_refresh_openai_usage();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('today', $response['data']);
        $this->assertArrayHasKey('month', $response['data']);
        $this->assertArrayHasKey('total_cost', $response['data']);

        // Clean up
        remove_all_filters('wp_verify_nonce');
        remove_all_filters('current_user_can');
        unset($_POST['nonce']);
    }

    /**
     * Test AJAX handler for testing content generation
     */
    public function test_ajax_test_content_generation() {
        // Mock container and OpenAI service
        $mock_container = $this->createMock('XeliteRepostEngine_Container');
        $mock_openai = $this->createMock('XeliteRepostEngine_OpenAI');

        // Mock content generation
        $mock_openai->method('generate_content')
            ->willReturn(array(
                'choices' => array(array('text' => 'Generated test content')),
                'usage' => array('total_tokens' => 150)
            ));

        $mock_container->method('get')
            ->with('openai')
            ->willReturn($mock_openai);

        // Mock the container instance
        $reflection = new ReflectionClass('XeliteRepostEngine_Container');
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, $mock_container);

        // Mock nonce verification
        add_filter('wp_verify_nonce', function() { return true; });

        // Mock current user capabilities
        add_filter('current_user_can', function() { return true; });

        // Test content generation
        $_POST['nonce'] = 'test_nonce';
        $_POST['user_context'] = json_encode(array('writing_style' => 'Conversational'));
        $_POST['patterns'] = json_encode(array('length_patterns' => array()));
        
        ob_start();
        $this->admin_settings->ajax_test_content_generation();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('content', $response['data']);
        $this->assertArrayHasKey('generation_time', $response['data']);
        $this->assertArrayHasKey('tokens_used', $response['data']);

        // Clean up
        remove_all_filters('wp_verify_nonce');
        remove_all_filters('current_user_can');
        unset($_POST['nonce']);
        unset($_POST['user_context']);
        unset($_POST['patterns']);
    }

    /**
     * Test AJAX handler for testing content generation with error
     */
    public function test_ajax_test_content_generation_error() {
        // Mock container and OpenAI service
        $mock_container = $this->createMock('XeliteRepostEngine_Container');
        $mock_openai = $this->createMock('XeliteRepostEngine_OpenAI');

        // Mock content generation error
        $mock_openai->method('generate_content')
            ->willReturn(new WP_Error('api_error', 'API request failed'));

        $mock_container->method('get')
            ->with('openai')
            ->willReturn($mock_openai);

        // Mock the container instance
        $reflection = new ReflectionClass('XeliteRepostEngine_Container');
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, $mock_container);

        // Mock nonce verification
        add_filter('wp_verify_nonce', function() { return true; });

        // Mock current user capabilities
        add_filter('current_user_can', function() { return true; });

        // Test content generation error
        $_POST['nonce'] = 'test_nonce';
        $_POST['user_context'] = json_encode(array('writing_style' => 'Conversational'));
        $_POST['patterns'] = json_encode(array('length_patterns' => array()));
        
        ob_start();
        $this->admin_settings->ajax_test_content_generation();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertFalse($response['success']);
        $this->assertEquals('API request failed', $response['data']);

        // Clean up
        remove_all_filters('wp_verify_nonce');
        remove_all_filters('current_user_can');
        unset($_POST['nonce']);
        unset($_POST['user_context']);
        unset($_POST['patterns']);
    }

    /**
     * Test calculate total cost method
     */
    public function test_calculate_total_cost() {
        $reflection = new ReflectionClass($this->admin_settings);
        $method = $reflection->getMethod('calculate_total_cost');
        $method->setAccessible(true);

        $today_usage = array('total_usage' => 1000);
        $month_usage = array('total_usage' => 5000);

        $total_cost = $method->invoke($this->admin_settings, $today_usage, $month_usage);

        // Expected cost: (6000 tokens / 1000) * $0.03 = $0.18
        $this->assertEquals(0.18, $total_cost, '', 0.01);
    }

    /**
     * Test calculate total cost with empty usage
     */
    public function test_calculate_total_cost_empty_usage() {
        $reflection = new ReflectionClass($this->admin_settings);
        $method = $reflection->getMethod('calculate_total_cost');
        $method->setAccessible(true);

        $total_cost = $method->invoke($this->admin_settings, array(), array());

        $this->assertEquals(0, $total_cost);
    }

    /**
     * Test AJAX handlers exist
     */
    public function test_ajax_handlers_exist() {
        $reflection = new ReflectionClass($this->admin_settings);
        
        $this->assertTrue($reflection->hasMethod('ajax_test_openai_connection'));
        $this->assertTrue($reflection->hasMethod('ajax_refresh_openai_usage'));
        $this->assertTrue($reflection->hasMethod('ajax_test_content_generation'));
    }

    /**
     * Test field callbacks exist
     */
    public function test_field_callbacks_exist() {
        $reflection = new ReflectionClass($this->admin_settings);
        
        $this->assertTrue($reflection->hasMethod('openai_api_section_callback'));
        $this->assertTrue($reflection->hasMethod('openai_api_key_field_callback'));
        $this->assertTrue($reflection->hasMethod('openai_model_field_callback'));
        $this->assertTrue($reflection->hasMethod('openai_connection_status_field_callback'));
        $this->assertTrue($reflection->hasMethod('openai_content_section_callback'));
        $this->assertTrue($reflection->hasMethod('openai_temperature_field_callback'));
        $this->assertTrue($reflection->hasMethod('openai_max_tokens_field_callback'));
        $this->assertTrue($reflection->hasMethod('openai_content_tone_field_callback'));
        $this->assertTrue($reflection->hasMethod('openai_usage_section_callback'));
        $this->assertTrue($reflection->hasMethod('openai_daily_limit_field_callback'));
        $this->assertTrue($reflection->hasMethod('openai_monthly_budget_field_callback'));
        $this->assertTrue($reflection->hasMethod('openai_usage_dashboard_field_callback'));
        $this->assertTrue($reflection->hasMethod('openai_testing_section_callback'));
        $this->assertTrue($reflection->hasMethod('openai_test_generation_field_callback'));
    }

    /**
     * Test settings registration method exists
     */
    public function test_register_openai_settings_exists() {
        $reflection = new ReflectionClass($this->admin_settings);
        $this->assertTrue($reflection->hasMethod('register_openai_settings'));
    }

    /**
     * Test edge cases
     */
    public function test_edge_cases() {
        // Test with invalid nonce
        add_filter('wp_verify_nonce', function() { return false; });
        add_filter('current_user_can', function() { return true; });

        $_POST['nonce'] = 'invalid_nonce';
        
        ob_start();
        $this->admin_settings->ajax_test_openai_connection();
        $output = ob_get_clean();

        $this->assertEmpty($output); // Should die() with invalid nonce

        // Test with insufficient permissions
        add_filter('wp_verify_nonce', function() { return true; });
        add_filter('current_user_can', function() { return false; });

        ob_start();
        $this->admin_settings->ajax_test_openai_connection();
        $output = ob_get_clean();

        $this->assertEmpty($output); // Should die() with insufficient permissions

        // Clean up
        remove_all_filters('wp_verify_nonce');
        remove_all_filters('current_user_can');
        unset($_POST['nonce']);
    }

    /**
     * Test settings sanitization
     */
    public function test_settings_sanitization() {
        $reflection = new ReflectionClass($this->admin_settings);
        $method = $reflection->getMethod('sanitize_settings');
        $method->setAccessible(true);

        $input = array(
            'openai_api_key' => 'test_key_123<script>alert("xss")</script>',
            'openai_model' => 'gpt-4',
            'openai_temperature' => '0.8',
            'openai_max_tokens' => '500',
            'openai_content_tone' => 'conversational',
            'openai_daily_limit' => '100',
            'openai_monthly_budget' => '50.00'
        );

        $sanitized = $method->invoke($this->admin_settings, $input);

        $this->assertEquals('test_key_123', $sanitized['openai_api_key']);
        $this->assertEquals('gpt-4', $sanitized['openai_model']);
        $this->assertEquals(0.8, $sanitized['openai_temperature']);
        $this->assertEquals(500, $sanitized['openai_max_tokens']);
        $this->assertEquals('conversational', $sanitized['openai_content_tone']);
        $this->assertEquals(100, $sanitized['openai_daily_limit']);
        $this->assertEquals(50.00, $sanitized['openai_monthly_budget']);
    }
} 