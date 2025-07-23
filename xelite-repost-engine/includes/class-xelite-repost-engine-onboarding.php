<?php
/**
 * Xelite Repost Engine Onboarding Wizard
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the onboarding wizard for new plugin users
 */
class Xelite_Repost_Engine_Onboarding {

    /**
     * Wizard steps
     */
    private $steps = array(
        'welcome' => array(
            'title' => 'Welcome to Xelite Repost Engine',
            'description' => 'Let\'s get you started with the plugin in just a few steps.',
            'icon' => '🎯'
        ),
        'api_config' => array(
            'title' => 'API Configuration',
            'description' => 'Configure your API keys for X (Twitter) and OpenAI.',
            'icon' => '🔑'
        ),
        'user_context' => array(
            'title' => 'Your Content Context',
            'description' => 'Tell us about your content and target audience.',
            'icon' => '👤'
        ),
        'target_accounts' => array(
            'title' => 'Target Accounts',
            'description' => 'Select accounts to monitor for repost patterns.',
            'icon' => '📊'
        ),
        'features' => array(
            'title' => 'Features Overview',
            'description' => 'Learn about the key features available to you.',
            'icon' => '✨'
        ),
        'completion' => array(
            'title' => 'Setup Complete!',
            'description' => 'You\'re all set to start improving your repost chances.',
            'icon' => '🎉'
        )
    );

    /**
     * Current step
     */
    private $current_step = 'welcome';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_onboarding_page'));
        add_action('admin_init', array($this, 'handle_onboarding_redirect'));
        add_action('wp_ajax_save_onboarding_step', array($this, 'save_onboarding_step'));
        add_action('wp_ajax_skip_onboarding', array($this, 'skip_onboarding'));
        add_action('wp_ajax_complete_onboarding', array($this, 'complete_onboarding'));
        add_action('wp_ajax_test_x_api', array($this, 'test_x_api'));
        add_action('wp_ajax_test_openai_api', array($this, 'test_openai_api'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_onboarding_scripts'));
    }

    /**
     * Add onboarding page to admin menu
     */
    public function add_onboarding_page() {
        add_submenu_page(
            'repost-intelligence',
            'Setup Wizard',
            'Setup Wizard',
            'manage_options',
            'repost-intelligence-onboarding',
            array($this, 'render_onboarding_page')
        );
    }

    /**
     * Handle onboarding redirect after activation
     */
    public function handle_onboarding_redirect() {
        if (get_option('xelite_repost_engine_show_onboarding', false)) {
            delete_option('xelite_repost_engine_show_onboarding');
            
            if (!isset($_GET['activate-multi']) && !wp_doing_ajax()) {
                wp_redirect(admin_url('admin.php?page=repost-intelligence-onboarding'));
                exit;
            }
        }
    }

    /**
     * Enqueue onboarding scripts and styles
     */
    public function enqueue_onboarding_scripts($hook) {
        if ($hook !== 'repost-intelligence_page_repost-intelligence-onboarding') {
            return;
        }

        wp_enqueue_style(
            'xelite-onboarding',
            plugin_dir_url(__FILE__) . '../assets/css/onboarding.css',
            array(),
            XELITE_REPOST_ENGINE_VERSION
        );

        wp_enqueue_script(
            'xelite-onboarding',
            plugin_dir_url(__FILE__) . '../assets/js/onboarding.js',
            array('jquery'),
            XELITE_REPOST_ENGINE_VERSION,
            true
        );

        wp_localize_script('xelite-onboarding', 'xeliteOnboarding', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xelite_onboarding_nonce'),
            'currentStep' => $this->get_current_step(),
            'steps' => $this->steps
        ));
    }

    /**
     * Render the onboarding page
     */
    public function render_onboarding_page() {
        $current_step = $this->get_current_step();
        $total_steps = count($this->steps);
        $current_step_number = array_search($current_step, array_keys($this->steps)) + 1;
        
        ?>
        <div class="wrap xelite-onboarding">
            <div class="xelite-onboarding-header">
                <h1>Xelite Repost Engine Setup</h1>
                <p>Complete the setup to start improving your repost chances</p>
            </div>

            <div class="xelite-onboarding-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo ($current_step_number / $total_steps) * 100; ?>%"></div>
                </div>
                <div class="progress-text">
                    Step <?php echo $current_step_number; ?> of <?php echo $total_steps; ?>
                </div>
            </div>

            <div class="xelite-onboarding-content">
                <?php $this->render_step_content($current_step); ?>
            </div>

            <div class="xelite-onboarding-navigation">
                <?php if ($current_step_number > 1): ?>
                    <button type="button" class="button button-secondary" id="prev-step">Previous</button>
                <?php endif; ?>
                
                <button type="button" class="button button-secondary" id="skip-wizard">Skip Setup</button>
                
                <?php if ($current_step_number < $total_steps): ?>
                    <button type="button" class="button button-primary" id="next-step">Next</button>
                <?php else: ?>
                    <button type="button" class="button button-primary" id="complete-setup">Complete Setup</button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render step content
     */
    private function render_step_content($step) {
        switch ($step) {
            case 'welcome':
                $this->render_welcome_step();
                break;
            case 'api_config':
                $this->render_api_config_step();
                break;
            case 'user_context':
                $this->render_user_context_step();
                break;
            case 'target_accounts':
                $this->render_target_accounts_step();
                break;
            case 'features':
                $this->render_features_step();
                break;
            case 'completion':
                $this->render_completion_step();
                break;
        }
    }

    /**
     * Render welcome step
     */
    private function render_welcome_step() {
        ?>
        <div class="step-content welcome-step">
            <div class="step-icon">🎯</div>
            <h2>Welcome to Xelite Repost Engine</h2>
            <p>This powerful plugin helps digital creators improve their chances of being reposted on X (formerly Twitter) by analyzing successful content patterns and providing AI-powered insights.</p>
            
            <div class="feature-highlights">
                <div class="feature-item">
                    <span class="feature-icon">🤖</span>
                    <h3>AI-Powered Analysis</h3>
                    <p>Advanced pattern recognition to identify what makes content go viral</p>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📊</span>
                    <h3>Comprehensive Analytics</h3>
                    <p>Detailed insights into your content performance and repost patterns</p>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🔄</span>
                    <h3>Automated Reposting</h3>
                    <p>Smart scheduling and automated reposting with optimal timing</p>
                </div>
            </div>

            <div class="setup-time">
                <p><strong>Setup Time:</strong> Approximately 5-10 minutes</p>
                <p><strong>What you'll need:</strong></p>
                <ul>
                    <li>X (Twitter) API credentials</li>
                    <li>OpenAI API key (optional but recommended)</li>
                    <li>Target accounts to monitor</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render API configuration step
     */
    private function render_api_config_step() {
        $x_api_key = get_option('xelite_repost_engine_x_api_key', '');
        $openai_api_key = get_option('xelite_repost_engine_openai_api_key', '');
        ?>
        <div class="step-content api-config-step">
            <div class="step-icon">🔑</div>
            <h2>API Configuration</h2>
            <p>Configure your API keys to enable the plugin's core functionality.</p>

            <form id="api-config-form" class="onboarding-form">
                <div class="form-group">
                    <label for="x_api_key">X (Twitter) API Key *</label>
                    <input type="password" id="x_api_key" name="x_api_key" value="<?php echo esc_attr($x_api_key); ?>" required>
                    <p class="description">
                        Get your API key from the <a href="https://developer.twitter.com/" target="_blank">X Developer Portal</a>.
                        This is required for monitoring posts and analyzing patterns.
                    </p>
                </div>

                <div class="form-group">
                    <label for="openai_api_key">OpenAI API Key</label>
                    <input type="password" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>">
                    <p class="description">
                        Get your API key from <a href="https://platform.openai.com/" target="_blank">OpenAI Platform</a>.
                        This enables AI-powered content analysis and suggestions.
                    </p>
                </div>

                <div class="api-test-section">
                    <h3>Test Your API Keys</h3>
                    <button type="button" class="button button-secondary" id="test-x-api">Test X API</button>
                    <button type="button" class="button button-secondary" id="test-openai-api">Test OpenAI API</button>
                    <div id="api-test-results"></div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render user context step
     */
    private function render_user_context_step() {
        $content_type = get_option('xelite_repost_engine_content_type', '');
        $target_audience = get_option('xelite_repost_engine_target_audience', '');
        $niche = get_option('xelite_repost_engine_niche', '');
        ?>
        <div class="step-content user-context-step">
            <div class="step-icon">👤</div>
            <h2>Your Content Context</h2>
            <p>Help us understand your content and audience to provide better insights.</p>

            <form id="user-context-form" class="onboarding-form">
                <div class="form-group">
                    <label for="content_type">What type of content do you create?</label>
                    <select id="content_type" name="content_type" required>
                        <option value="">Select content type...</option>
                        <option value="tech" <?php selected($content_type, 'tech'); ?>>Technology</option>
                        <option value="business" <?php selected($content_type, 'business'); ?>>Business</option>
                        <option value="marketing" <?php selected($content_type, 'marketing'); ?>>Marketing</option>
                        <option value="personal" <?php selected($content_type, 'personal'); ?>>Personal Development</option>
                        <option value="entertainment" <?php selected($content_type, 'entertainment'); ?>>Entertainment</option>
                        <option value="news" <?php selected($content_type, 'news'); ?>>News & Politics</option>
                        <option value="lifestyle" <?php selected($content_type, 'lifestyle'); ?>>Lifestyle</option>
                        <option value="other" <?php selected($content_type, 'other'); ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="target_audience">Who is your target audience?</label>
                    <textarea id="target_audience" name="target_audience" rows="3" placeholder="Describe your ideal audience..."><?php echo esc_textarea($target_audience); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="niche">What's your specific niche or focus area?</label>
                    <input type="text" id="niche" name="niche" value="<?php echo esc_attr($niche); ?>" placeholder="e.g., SaaS marketing, AI technology, etc.">
                </div>

                <div class="form-group">
                    <label for="posting_frequency">How often do you post?</label>
                    <select id="posting_frequency" name="posting_frequency">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="irregular">Irregular</option>
                    </select>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render target accounts step
     */
    private function render_target_accounts_step() {
        $target_accounts = get_option('xelite_repost_engine_target_accounts', array());
        ?>
        <div class="step-content target-accounts-step">
            <div class="step-icon">📊</div>
            <h2>Target Accounts</h2>
            <p>Select accounts to monitor for repost patterns and insights.</p>

            <form id="target-accounts-form" class="onboarding-form">
                <div class="form-group">
                    <label for="target_accounts">X (Twitter) Accounts to Monitor</label>
                    <textarea id="target_accounts" name="target_accounts" rows="5" placeholder="Enter one username per line (without @ symbol)&#10;Example:&#10;elonmusk&#10;OpenAI&#10;WordPress"><?php echo esc_textarea(implode("\n", $target_accounts)); ?></textarea>
                    <p class="description">
                        Enter the usernames of accounts you want to monitor for repost patterns.
                        These should be accounts in your niche or industry that you admire.
                    </p>
                </div>

                <div class="suggested-accounts">
                    <h3>Suggested Accounts by Category</h3>
                    <div class="account-suggestions">
                        <div class="suggestion-category">
                            <h4>Technology</h4>
                            <button type="button" class="suggestion-btn" data-account="OpenAI">OpenAI</button>
                            <button type="button" class="suggestion-btn" data-account="Google">Google</button>
                            <button type="button" class="suggestion-btn" data-account="Microsoft">Microsoft</button>
                        </div>
                        <div class="suggestion-category">
                            <h4>Marketing</h4>
                            <button type="button" class="suggestion-btn" data-account="HubSpot">HubSpot</button>
                            <button type="button" class="suggestion-btn" data-account="Buffer">Buffer</button>
                            <button type="button" class="suggestion-btn" data-account="Mailchimp">Mailchimp</button>
                        </div>
                        <div class="suggestion-category">
                            <h4>Business</h4>
                            <button type="button" class="suggestion-btn" data-account="YCombinator">YCombinator</button>
                            <button type="button" class="suggestion-btn" data-account="TechCrunch">TechCrunch</button>
                            <button type="button" class="suggestion-btn" data-account="Forbes">Forbes</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render features overview step
     */
    private function render_features_step() {
        ?>
        <div class="step-content features-step">
            <div class="step-icon">✨</div>
            <h2>Features Overview</h2>
            <p>Here's what you can do with Xelite Repost Engine:</p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3>Pattern Analysis</h3>
                    <p>Analyze successful posts to identify patterns that lead to more reposts</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3>AI Content Suggestions</h3>
                    <p>Get AI-powered suggestions for improving your content based on successful patterns</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h3>Smart Scheduling</h3>
                    <p>Automatically schedule posts at optimal times based on engagement patterns</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Analytics Dashboard</h3>
                    <p>Track your performance and see detailed insights about your content</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3>Automated Reposting</h3>
                    <p>Set up automated reposting of your best content with smart timing</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Target Monitoring</h3>
                    <p>Monitor specific accounts and get alerts when they post content worth reposting</p>
                </div>
            </div>

            <div class="next-steps">
                <h3>What's Next?</h3>
                <ul>
                    <li>Complete the setup to start monitoring your target accounts</li>
                    <li>Visit the dashboard to see your first insights</li>
                    <li>Configure your posting schedule and automation rules</li>
                    <li>Start creating content with AI-powered suggestions</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render completion step
     */
    private function render_completion_step() {
        ?>
        <div class="step-content completion-step">
            <div class="step-icon">🎉</div>
            <h2>Setup Complete!</h2>
            <p>Congratulations! You're all set to start improving your repost chances with Xelite Repost Engine.</p>

            <div class="completion-summary">
                <h3>What's Been Configured:</h3>
                <ul>
                    <li>✅ API keys for X (Twitter) and OpenAI</li>
                    <li>✅ Your content context and target audience</li>
                    <li>✅ Target accounts for monitoring</li>
                    <li>✅ Basic plugin settings</li>
                </ul>
            </div>

            <div class="quick-actions">
                <h3>Quick Actions:</h3>
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=repost-intelligence'); ?>" class="button button-primary">Go to Dashboard</a>
                    <a href="<?php echo admin_url('admin.php?page=repost-intelligence-settings'); ?>" class="button button-secondary">Configure Settings</a>
                    <a href="<?php echo admin_url('admin.php?page=repost-intelligence-help'); ?>" class="button button-secondary">View Documentation</a>
                </div>
            </div>

            <div class="getting-started">
                <h3>Getting Started Tips:</h3>
                <ol>
                    <li>Visit the dashboard to see your first insights</li>
                    <li>Add more target accounts if needed</li>
                    <li>Configure your posting schedule</li>
                    <li>Start creating content with AI suggestions</li>
                    <li>Monitor your performance over time</li>
                </ol>
            </div>
        </div>
        <?php
    }

    /**
     * Save onboarding step data
     */
    public function save_onboarding_step() {
        check_ajax_referer('xelite_onboarding_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $step = sanitize_text_field($_POST['step']);
        $data = $_POST['data'];

        switch ($step) {
            case 'api_config':
                if (isset($data['x_api_key'])) {
                    update_option('xelite_repost_engine_x_api_key', sanitize_text_field($data['x_api_key']));
                }
                if (isset($data['openai_api_key'])) {
                    update_option('xelite_repost_engine_openai_api_key', sanitize_text_field($data['openai_api_key']));
                }
                break;

            case 'user_context':
                if (isset($data['content_type'])) {
                    update_option('xelite_repost_engine_content_type', sanitize_text_field($data['content_type']));
                }
                if (isset($data['target_audience'])) {
                    update_option('xelite_repost_engine_target_audience', sanitize_textarea_field($data['target_audience']));
                }
                if (isset($data['niche'])) {
                    update_option('xelite_repost_engine_niche', sanitize_text_field($data['niche']));
                }
                if (isset($data['posting_frequency'])) {
                    update_option('xelite_repost_engine_posting_frequency', sanitize_text_field($data['posting_frequency']));
                }
                break;

            case 'target_accounts':
                if (isset($data['target_accounts'])) {
                    $accounts = array_filter(array_map('trim', explode("\n", $data['target_accounts'])));
                    $accounts = array_map('sanitize_text_field', $accounts);
                    update_option('xelite_repost_engine_target_accounts', $accounts);
                }
                break;
        }

        // Update current step
        $this->set_current_step($step);

        wp_send_json_success(array(
            'message' => 'Step saved successfully',
            'next_step' => $this->get_next_step($step)
        ));
    }

    /**
     * Skip onboarding
     */
    public function skip_onboarding() {
        check_ajax_referer('xelite_onboarding_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        update_option('xelite_repost_engine_onboarding_completed', true);
        update_option('xelite_repost_engine_onboarding_skipped', true);

        wp_send_json_success(array(
            'redirect_url' => admin_url('admin.php?page=repost-intelligence')
        ));
    }

    /**
     * Complete onboarding
     */
    public function complete_onboarding() {
        check_ajax_referer('xelite_onboarding_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $this->mark_completed();

        wp_send_json_success(array(
            'redirect_url' => admin_url('admin.php?page=repost-intelligence')
        ));
    }

    /**
     * Test X API connection
     */
    public function test_x_api() {
        check_ajax_referer('xelite_onboarding_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $api_key = sanitize_text_field($_POST['api_key']);

        if (empty($api_key)) {
            wp_send_json_error('API key is required');
        }

        // Test the API key by making a simple request
        $response = wp_remote_get('https://api.twitter.com/2/users/by/username/twitter', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            wp_send_json_success('X API connection successful');
        } elseif ($status_code === 401) {
            wp_send_json_error('Invalid API key');
        } else {
            wp_send_json_error('API test failed with status code: ' . $status_code);
        }
    }

    /**
     * Test OpenAI API connection
     */
    public function test_openai_api() {
        check_ajax_referer('xelite_onboarding_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $api_key = sanitize_text_field($_POST['api_key']);

        if (empty($api_key)) {
            wp_send_json_error('API key is required');
        }

        // Test the API key by making a simple request
        $response = wp_remote_post('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            wp_send_json_success('OpenAI API connection successful');
        } elseif ($status_code === 401) {
            wp_send_json_error('Invalid API key');
        } else {
            wp_send_json_error('API test failed with status code: ' . $status_code);
        }
    }

    /**
     * Get current step
     */
    private function get_current_step() {
        return get_transient('xelite_onboarding_current_step') ?: 'welcome';
    }

    /**
     * Set current step
     */
    private function set_current_step($step) {
        set_transient('xelite_onboarding_current_step', $step, HOUR_IN_SECONDS);
    }

    /**
     * Get next step
     */
    private function get_next_step($current_step) {
        $steps = array_keys($this->steps);
        $current_index = array_search($current_step, $steps);
        
        if ($current_index !== false && $current_index < count($steps) - 1) {
            return $steps[$current_index + 1];
        }
        
        return 'completion';
    }

    /**
     * Mark onboarding as completed
     */
    public static function mark_completed() {
        update_option('xelite_repost_engine_onboarding_completed', true);
        delete_transient('xelite_onboarding_current_step');
    }

    /**
     * Check if onboarding is completed
     */
    public static function is_completed() {
        return get_option('xelite_repost_engine_onboarding_completed', false);
    }

    /**
     * Check if onboarding was skipped
     */
    public static function was_skipped() {
        return get_option('xelite_repost_engine_onboarding_skipped', false);
    }
} 