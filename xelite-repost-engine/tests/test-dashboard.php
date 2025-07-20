<?php
/**
 * Dashboard Tests
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Test Class
 */
class Test_Repost_Intelligence_Dashboard extends WP_UnitTestCase {

    /**
     * Dashboard instance
     *
     * @var Repost_Intelligence_Dashboard
     */
    private $dashboard;

    /**
     * Test user ID
     *
     * @var int
     */
    private $user_id;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create test user
        $this->user_id = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        
        // Set current user
        wp_set_current_user($this->user_id);
        
        // Initialize dashboard
        $this->dashboard = new Repost_Intelligence_Dashboard();
    }

    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        // Clean up
        wp_delete_user($this->user_id);
        
        parent::tearDown();
    }

    /**
     * Test dashboard initialization
     */
    public function test_dashboard_initialization() {
        $this->assertInstanceOf('Repost_Intelligence_Dashboard', $this->dashboard);
        $this->assertEquals($this->user_id, $this->dashboard->user_id);
    }

    /**
     * Test user access check
     */
    public function test_user_has_access() {
        // Test with valid user
        $this->assertTrue($this->dashboard->user_has_access());
        
        // Test with no user
        wp_set_current_user(0);
        $this->assertFalse($this->dashboard->user_has_access());
        
        // Reset current user
        wp_set_current_user($this->user_id);
    }

    /**
     * Test tab initialization
     */
    public function test_tab_initialization() {
        $reflection = new ReflectionClass($this->dashboard);
        $tabs_property = $reflection->getProperty('tabs');
        $tabs_property->setAccessible(true);
        
        $tabs = $tabs_property->getValue($this->dashboard);
        
        $this->assertIsArray($tabs);
        $this->assertArrayHasKey('overview', $tabs);
        $this->assertArrayHasKey('content-generator', $tabs);
        $this->assertArrayHasKey('patterns', $tabs);
        $this->assertArrayHasKey('analytics', $tabs);
        $this->assertArrayHasKey('settings', $tabs);
        
        // Check tab structure
        foreach ($tabs as $tab) {
            $this->assertArrayHasKey('title', $tab);
            $this->assertArrayHasKey('icon', $tab);
            $this->assertArrayHasKey('description', $tab);
            $this->assertArrayHasKey('callback', $tab);
            $this->assertIsCallable($tab['callback']);
        }
    }

    /**
     * Test dashboard URL generation
     */
    public function test_get_dashboard_url() {
        $url = $this->dashboard->get_dashboard_url();
        $this->assertStringContainsString('admin.php?page=repost-intelligence-dashboard', $url);
        
        $url_with_tab = $this->dashboard->get_dashboard_url('overview');
        $this->assertStringContainsString('&tab=overview', $url_with_tab);
    }

    /**
     * Test dashboard page detection
     */
    public function test_is_dashboard_page() {
        // Mock global variables
        global $pagenow;
        $pagenow = 'admin.php';
        
        $_GET['page'] = 'repost-intelligence-dashboard';
        $this->assertTrue($this->dashboard->is_dashboard_page());
        
        $_GET['page'] = 'other-page';
        $this->assertFalse($this->dashboard->is_dashboard_page());
        
        // Clean up
        unset($_GET['page']);
    }

    /**
     * Test user context retrieval
     */
    public function test_get_user_context() {
        // Set up user meta
        update_user_meta($this->user_id, 'writing_style', 'Professional');
        update_user_meta($this->user_id, 'audience', 'Entrepreneurs');
        update_user_meta($this->user_id, 'topic', 'Business Growth');
        update_user_meta($this->user_id, 'offer', 'Consulting Services');
        
        $context = $this->dashboard->get_user_context();
        
        $this->assertIsArray($context);
        $this->assertEquals('Professional', $context['writing_style']);
        $this->assertEquals('Entrepreneurs', $context['audience']);
        $this->assertEquals('Business Growth', $context['topic']);
        $this->assertEquals('Consulting Services', $context['offer']);
    }

    /**
     * Test generation stats retrieval
     */
    public function test_get_generation_stats() {
        // Set up test stats
        $test_stats = array(
            'total_generated' => 25,
            'total_saved' => 15,
            'total_posted' => 10,
            'last_generated' => '2024-01-15 10:30:00'
        );
        update_user_meta($this->user_id, 'xelite_generation_stats', $test_stats);
        
        $stats = $this->dashboard->get_generation_stats();
        
        $this->assertIsArray($stats);
        $this->assertEquals(25, $stats['total_generated']);
        $this->assertEquals(15, $stats['total_saved']);
        $this->assertEquals(10, $stats['total_posted']);
        $this->assertEquals('2024-01-15 10:30:00', $stats['last_generated']);
    }

    /**
     * Test user settings retrieval
     */
    public function test_get_user_settings() {
        // Set up test settings
        $test_settings = array(
            'default_tone' => 'conversational',
            'max_tokens' => 280,
            'temperature' => 0.7,
            'auto_save' => true,
            'notifications' => true
        );
        update_user_meta($this->user_id, 'xelite_user_settings', $test_settings);
        
        $settings = $this->dashboard->get_user_settings();
        
        $this->assertIsArray($settings);
        $this->assertEquals('conversational', $settings['default_tone']);
        $this->assertEquals(280, $settings['max_tokens']);
        $this->assertEquals(0.7, $settings['temperature']);
        $this->assertTrue($settings['auto_save']);
        $this->assertTrue($settings['notifications']);
    }

    /**
     * Test AJAX dashboard data retrieval
     */
    public function test_ajax_get_dashboard_data() {
        // Mock AJAX request
        $_POST['nonce'] = wp_create_nonce('xelite_dashboard_nonce');
        
        // Capture output
        ob_start();
        $this->dashboard->ajax_get_dashboard_data();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('user_context', $response['data']);
        $this->assertArrayHasKey('recent_patterns', $response['data']);
        $this->assertArrayHasKey('generation_stats', $response['data']);
        $this->assertArrayHasKey('account_stats', $response['data']);
    }

    /**
     * Test AJAX content generation
     */
    public function test_ajax_generate_content() {
        // Mock AJAX request data
        $_POST['nonce'] = wp_create_nonce('xelite_dashboard_nonce');
        $_POST['user_context'] = json_encode(array(
            'writing_style' => 'Professional',
            'audience' => 'Entrepreneurs'
        ));
        $_POST['patterns'] = json_encode(array());
        $_POST['options'] = json_encode(array(
            'topic' => 'Business Growth',
            'tone' => 'conversational',
            'length' => 'medium'
        ));
        
        // Mock OpenAI service
        $mock_openai = $this->createMock('XeliteRepostEngine_OpenAI');
        $mock_openai->method('generate_content')
            ->willReturn(array(
                'text' => 'Test generated content',
                'tokens' => 50,
                'repost_score' => 85
            ));
        
        // Replace OpenAI service in container
        $container = XeliteRepostEngine_Container::instance();
        $container->remove('openai');
        $container->register('openai', function() use ($mock_openai) {
            return $mock_openai;
        }, true);
        
        // Capture output
        ob_start();
        $this->dashboard->ajax_generate_content();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('Test generated content', $response['data']['text']);
    }

    /**
     * Test AJAX content saving
     */
    public function test_ajax_save_content() {
        // Mock AJAX request data
        $_POST['nonce'] = wp_create_nonce('xelite_dashboard_nonce');
        $_POST['content'] = 'Test content to save';
        $_POST['title'] = 'Test Content';
        
        // Capture output
        ob_start();
        $this->dashboard->ajax_save_content();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        
        // Check if content was saved
        $saved_content = get_user_meta($this->user_id, 'xelite_saved_content', true);
        $this->assertIsArray($saved_content);
        $this->assertNotEmpty($saved_content);
        $this->assertEquals('Test content to save', $saved_content[0]['content']);
        $this->assertEquals('Test Content', $saved_content[0]['title']);
    }

    /**
     * Test AJAX settings update
     */
    public function test_ajax_update_settings() {
        // Mock AJAX request data
        $_POST['nonce'] = wp_create_nonce('xelite_dashboard_nonce');
        $_POST['settings'] = json_encode(array(
            'default_tone' => 'professional',
            'max_tokens' => 300,
            'temperature' => 0.8,
            'auto_save' => false,
            'notifications' => true
        ));
        
        // Capture output
        ob_start();
        $this->dashboard->ajax_update_settings();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        
        // Check if settings were updated
        $updated_settings = get_user_meta($this->user_id, 'xelite_user_settings', true);
        $this->assertIsArray($updated_settings);
        $this->assertEquals('professional', $updated_settings['default_tone']);
        $this->assertEquals(300, $updated_settings['max_tokens']);
        $this->assertEquals(0.8, $updated_settings['temperature']);
        $this->assertFalse($updated_settings['auto_save']);
        $this->assertTrue($updated_settings['notifications']);
    }

    /**
     * Test AJAX patterns retrieval
     */
    public function test_ajax_get_patterns() {
        // Mock AJAX request
        $_POST['nonce'] = wp_create_nonce('xelite_dashboard_nonce');
        
        // Capture output
        ob_start();
        $this->dashboard->ajax_get_patterns();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    /**
     * Test nonce validation failure
     */
    public function test_ajax_nonce_validation_failure() {
        // Mock AJAX request with invalid nonce
        $_POST['nonce'] = 'invalid_nonce';
        
        // Capture output
        ob_start();
        $this->dashboard->ajax_get_dashboard_data();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertEquals('Unauthorized', $response['data']);
    }

    /**
     * Test unauthorized access
     */
    public function test_ajax_unauthorized_access() {
        // Set no current user
        wp_set_current_user(0);
        
        // Mock AJAX request
        $_POST['nonce'] = wp_create_nonce('xelite_dashboard_nonce');
        
        // Capture output
        ob_start();
        $this->dashboard->ajax_get_dashboard_data();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertEquals('Unauthorized', $response['data']);
        
        // Reset current user
        wp_set_current_user($this->user_id);
    }

    /**
     * Test dashboard data structure
     */
    public function test_dashboard_data_structure() {
        $data = $this->dashboard->get_dashboard_data();
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('user_context', $data);
        $this->assertArrayHasKey('recent_patterns', $data);
        $this->assertArrayHasKey('generation_stats', $data);
        $this->assertArrayHasKey('account_stats', $data);
        
        // Check user context structure
        $this->assertIsArray($data['user_context']);
        
        // Check generation stats structure
        $this->assertIsArray($data['generation_stats']);
        $this->assertArrayHasKey('total_generated', $data['generation_stats']);
        $this->assertArrayHasKey('total_saved', $data['generation_stats']);
        $this->assertArrayHasKey('total_posted', $data['generation_stats']);
        $this->assertArrayHasKey('last_generated', $data['generation_stats']);
        
        // Check account stats structure
        $this->assertIsArray($data['account_stats']);
    }

    /**
     * Test pattern data retrieval
     */
    public function test_get_pattern_data() {
        $data = $this->dashboard->get_pattern_data();
        
        $this->assertIsArray($data);
        // Pattern data structure depends on the pattern visualizer implementation
    }

    /**
     * Test analytics data retrieval
     */
    public function test_get_analytics_data() {
        $data = $this->dashboard->get_analytics_data();
        
        $this->assertIsArray($data);
        // Analytics data structure depends on the pattern visualizer implementation
    }

    /**
     * Test available patterns retrieval
     */
    public function test_get_available_patterns() {
        $patterns = $this->dashboard->get_available_patterns();
        
        $this->assertIsArray($patterns);
        // Patterns structure depends on the pattern analyzer implementation
    }

    /**
     * Test recent patterns retrieval
     */
    public function test_get_recent_patterns() {
        $patterns = $this->dashboard->get_recent_patterns();
        
        $this->assertIsArray($patterns);
        // Recent patterns structure depends on the pattern analyzer implementation
    }

    /**
     * Test account statistics retrieval
     */
    public function test_get_account_stats() {
        $stats = $this->dashboard->get_account_stats();
        
        $this->assertIsArray($stats);
        // Account stats structure depends on the database implementation
    }

    /**
     * Test dashboard template rendering
     */
    public function test_dashboard_template_rendering() {
        // Mock template variables
        $tabs = array(
            'overview' => array(
                'title' => 'Overview',
                'icon' => 'dashicons-chart-area',
                'description' => 'Dashboard overview',
                'callback' => array($this->dashboard, 'render_overview_tab')
            )
        );
        $current_tab = 'overview';
        
        // Capture output
        ob_start();
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/main.php';
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('xelite-dashboard', $output);
        $this->assertStringContainsString('Repost Intelligence Dashboard', $output);
        $this->assertStringContainsString('xelite-nav-tabs', $output);
    }

    /**
     * Test overview tab rendering
     */
    public function test_overview_tab_rendering() {
        // Mock dashboard data
        $dashboard_data = array(
            'user_context' => array(
                'writing_style' => 'Professional',
                'audience' => 'Entrepreneurs'
            ),
            'recent_patterns' => array(),
            'generation_stats' => array(
                'total_generated' => 0,
                'total_saved' => 0,
                'total_posted' => 0,
                'last_generated' => null
            ),
            'account_stats' => array(
                'target_accounts' => array()
            )
        );
        
        // Capture output
        ob_start();
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/overview.php';
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('xelite-overview', $output);
        $this->assertStringContainsString('Welcome to Repost Intelligence', $output);
        $this->assertStringContainsString('Your Content Generation Stats', $output);
    }

    /**
     * Test content generator tab rendering
     */
    public function test_content_generator_tab_rendering() {
        // Mock data
        $user_context = array(
            'writing_style' => 'Professional',
            'topic' => 'Business Growth'
        );
        $patterns = array();
        
        // Capture output
        ob_start();
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/content-generator.php';
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('xelite-content-generator', $output);
        $this->assertStringContainsString('AI Content Generator', $output);
        $this->assertStringContainsString('content-topic', $output);
        $this->assertStringContainsString('content-tone', $output);
    }

    /**
     * Test patterns tab rendering
     */
    public function test_patterns_tab_rendering() {
        // Mock patterns data
        $patterns = array(
            array(
                'id' => 1,
                'source_handle' => 'test_account',
                'text' => 'Test pattern text',
                'repost_count' => 5,
                'avg_engagement' => 100,
                'created_at' => '2024-01-15 10:30:00'
            )
        );
        
        // Capture output
        ob_start();
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/patterns.php';
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('xelite-patterns', $output);
        $this->assertStringContainsString('Repost Patterns Analysis', $output);
        $this->assertStringContainsString('Filter Patterns', $output);
    }

    /**
     * Test analytics tab rendering
     */
    public function test_analytics_tab_rendering() {
        // Mock analytics data
        $analytics_data = array(
            'summary' => array(
                'total_reposts' => 0,
                'avg_engagement_per_repost' => 0,
                'repost_rate' => 0,
                'best_performing_length' => 0
            ),
            'charts' => array(),
            'top_patterns' => array(),
            'recommendations' => array()
        );
        
        // Capture output
        ob_start();
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/analytics.php';
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('xelite-analytics', $output);
        $this->assertStringContainsString('Content Analytics & Insights', $output);
        $this->assertStringContainsString('performance-chart', $output);
    }

    /**
     * Test settings tab rendering
     */
    public function test_settings_tab_rendering() {
        // Mock user settings
        $user_settings = array(
            'default_tone' => 'conversational',
            'max_tokens' => 280,
            'temperature' => 0.7,
            'auto_save' => true,
            'notifications' => true
        );
        
        // Capture output
        ob_start();
        include XELITE_REPOST_ENGINE_PLUGIN_DIR . 'templates/dashboard/settings.php';
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('xelite-settings', $output);
        $this->assertStringContainsString('Personal Settings', $output);
        $this->assertStringContainsString('user-settings-form', $output);
    }

    /**
     * Test dashboard menu registration
     */
    public function test_dashboard_menu_registration() {
        // Test that menu is registered
        $this->assertTrue(has_action('admin_menu', array($this->dashboard, 'add_dashboard_menu')));
    }

    /**
     * Test dashboard assets enqueuing
     */
    public function test_dashboard_assets_enqueuing() {
        // Test that assets are enqueued
        $this->assertTrue(has_action('admin_enqueue_scripts', array($this->dashboard, 'enqueue_dashboard_assets')));
    }

    /**
     * Test AJAX handlers registration
     */
    public function test_ajax_handlers_registration() {
        // Test that AJAX handlers are registered
        $this->assertTrue(has_action('wp_ajax_xelite_dashboard_data', array($this->dashboard, 'ajax_get_dashboard_data')));
        $this->assertTrue(has_action('wp_ajax_xelite_dashboard_generate_content', array($this->dashboard, 'ajax_generate_content')));
        $this->assertTrue(has_action('wp_ajax_xelite_dashboard_save_content', array($this->dashboard, 'ajax_save_content')));
        $this->assertTrue(has_action('wp_ajax_xelite_dashboard_get_patterns', array($this->dashboard, 'ajax_get_patterns')));
        $this->assertTrue(has_action('wp_ajax_xelite_dashboard_update_settings', array($this->dashboard, 'ajax_update_settings')));
    }
} 