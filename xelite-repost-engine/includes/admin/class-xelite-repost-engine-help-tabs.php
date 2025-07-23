<?php
/**
 * Xelite Repost Engine Help Tabs
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles contextual help tabs for the Xelite Repost Engine plugin
 */
class Xelite_Repost_Engine_Help_Tabs {

    /**
     * Initialize the help tabs
     */
    public function __construct() {
        add_action('admin_head', array($this, 'add_help_tabs'));
    }

    /**
     * Add help tabs to plugin admin pages
     */
    public function add_help_tabs() {
        $screen = get_current_screen();
        
        if (!$screen) {
            return;
        }

        // Check if we're on a plugin page
        if (strpos($screen->id, 'xelite-repost-engine') !== false || 
            strpos($screen->id, 'repost-intelligence') !== false) {
            
            $this->add_main_help_tabs($screen);
            $this->add_help_sidebar($screen);
        }
    }

    /**
     * Add main help tabs
     */
    private function add_main_help_tabs($screen) {
        // Overview tab
        $screen->add_help_tab(array(
            'id' => 'xelite-overview',
            'title' => __('Overview', 'xelite-repost-engine'),
            'content' => $this->get_overview_content()
        ));

        // Settings tab
        $screen->add_help_tab(array(
            'id' => 'xelite-settings',
            'title' => __('Settings', 'xelite-repost-engine'),
            'content' => $this->get_settings_content()
        ));

        // Chrome Extension tab
        $screen->add_help_tab(array(
            'id' => 'xelite-extension',
            'title' => __('Chrome Extension', 'xelite-repost-engine'),
            'content' => $this->get_extension_content()
        ));

        // Analytics tab
        $screen->add_help_tab(array(
            'id' => 'xelite-analytics',
            'title' => __('Analytics', 'xelite-repost-engine'),
            'content' => $this->get_analytics_content()
        ));

        // Troubleshooting tab
        $screen->add_help_tab(array(
            'id' => 'xelite-troubleshooting',
            'title' => __('Troubleshooting', 'xelite-repost-engine'),
            'content' => $this->get_troubleshooting_content()
        ));
    }

    /**
     * Add help sidebar
     */
    private function add_help_sidebar($screen) {
        $screen->set_help_sidebar($this->get_help_sidebar_content());
    }

    /**
     * Get overview help content
     */
    private function get_overview_content() {
        return '
        <h2>' . __('Xelite Repost Engine Overview', 'xelite-repost-engine') . '</h2>
        <p>' . __('The Xelite Repost Engine is a powerful WordPress plugin that analyzes X (Twitter) repost patterns and generates AI-powered content suggestions to help you create viral-worthy posts.', 'xelite-repost-engine') . '</p>
        
        <h3>' . __('Key Features', 'xelite-repost-engine') . '</h3>
        <ul>
            <li><strong>' . __('Pattern Analysis', 'xelite-repost-engine') . '</strong> - ' . __('Analyze repost patterns from target accounts', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('AI Content Generation', 'xelite-repost-engine') . '</strong> - ' . __('Generate content based on successful patterns', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Chrome Extension', 'xelite-repost-engine') . '</strong> - ' . __('Scrape data directly from X (Twitter) pages', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Dashboard Analytics', 'xelite-repost-engine') . '</strong> - ' . __('Visual insights and performance tracking', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('WooCommerce Integration', 'xelite-repost-engine') . '</strong> - ' . __('Seamless e-commerce content management', 'xelite-repost-engine') . '</li>
        </ul>
        
        <h3>' . __('Getting Started', 'xelite-repost-engine') . '</h3>
        <ol>
            <li>' . __('Configure your API keys in Settings', 'xelite-repost-engine') . '</li>
            <li>' . __('Add target accounts to analyze', 'xelite-repost-engine') . '</li>
            <li>' . __('Install the Chrome extension for data collection', 'xelite-repost-engine') . '</li>
            <li>' . __('View patterns and generate content suggestions', 'xelite-repost-engine') . '</li>
        </ol>';
    }

    /**
     * Get settings help content
     */
    private function get_settings_content() {
        return '
        <h2>' . __('Settings Configuration', 'xelite-repost-engine') . '</h2>
        
        <h3>' . __('API Configuration', 'xelite-repost-engine') . '</h3>
        <p><strong>' . __('OpenAI API Key', 'xelite-repost-engine') . '</strong></p>
        <ul>
            <li>' . __('Required for AI content generation', 'xelite-repost-engine') . '</li>
            <li>' . __('Get your key from', 'xelite-repost-engine') . ' <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></li>
            <li>' . __('Keep your API key secure and never share it', 'xelite-repost-engine') . '</li>
        </ul>
        
        <p><strong>' . __('X (Twitter) API Credentials', 'xelite-repost-engine') . '</strong></p>
        <ul>
            <li>' . __('Optional - used for primary data collection', 'xelite-repost-engine') . '</li>
            <li>' . __('Apply at', 'xelite-repost-engine') . ' <a href="https://developer.twitter.com/" target="_blank">developer.twitter.com</a></li>
            <li>' . __('Chrome extension serves as fallback when API limits are reached', 'xelite-repost-engine') . '</li>
        </ul>
        
        <h3>' . __('Target Accounts', 'xelite-repost-engine') . '</h3>
        <p>' . __('Add X (Twitter) handles of accounts you want to analyze. The plugin will track their repost patterns and use them to generate content suggestions.', 'xelite-repost-engine') . '</p>
        
        <h3>' . __('Analysis Settings', 'xelite-repost-engine') . '</h3>
        <ul>
            <li><strong>' . __('Analysis Frequency', 'xelite-repost-engine') . '</strong> - ' . __('How often to analyze target accounts', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Max Posts per Analysis', 'xelite-repost-engine') . '</strong> - ' . __('Limit posts analyzed per session', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Include Replies/Retweets', 'xelite-repost-engine') . '</strong> - ' . __('Control what content types to analyze', 'xelite-repost-engine') . '</li>
        </ul>';
    }

    /**
     * Get Chrome extension help content
     */
    private function get_extension_content() {
        return '
        <h2>' . __('Chrome Extension Setup', 'xelite-repost-engine') . '</h2>
        
        <h3>' . __('Installation', 'xelite-repost-engine') . '</h3>
        <ol>
            <li>' . __('Download the extension from the plugin settings', 'xelite-repost-engine') . '</li>
            <li>' . __('Open Chrome and go to', 'xelite-repost-engine') . ' <code>chrome://extensions/</code></li>
            <li>' . __('Enable "Developer mode"', 'xelite-repost-engine') . '</li>
            <li>' . __('Click "Load unpacked" and select the extension folder', 'xelite-repost-engine') . '</li>
        </ol>
        
        <h3>' . __('Authentication', 'xelite-repost-engine') . '</h3>
        <ol>
            <li>' . __('Click the extension icon in Chrome toolbar', 'xelite-repost-engine') . '</li>
            <li>' . __('Go to Settings tab', 'xelite-repost-engine') . '</li>
            <li>' . __('Enter your WordPress site URL (no trailing slash)', 'xelite-repost-engine') . '</li>
            <li>' . __('Enter your WordPress username and password', 'xelite-repost-engine') . '</li>
            <li>' . __('Click "Authenticate"', 'xelite-repost-engine') . '</li>
        </ol>
        
        <h3>' . __('Usage', 'xelite-repost-engine') . '</h3>
        <ul>
            <li>' . __('Navigate to any X (Twitter) page', 'xelite-repost-engine') . '</li>
            <li>' . __('Click "Start Scraping" in the extension popup', 'xelite-repost-engine') . '</li>
            <li>' . __('Data automatically syncs to WordPress', 'xelite-repost-engine') . '</li>
            <li>' . __('View scraped data in the plugin dashboard', 'xelite-repost-engine') . '</li>
        </ul>
        
        <h3>' . __('Features', 'xelite-repost-engine') . '</h3>
        <ul>
            <li><strong>' . __('Rate Limiting', 'xelite-repost-engine') . '</strong> - ' . __('Respectful scraping with built-in delays', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Dynamic Content', 'xelite-repost-engine') . '</strong> - ' . __('Handles infinite scroll and dynamic loading', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Offline Storage', 'xelite-repost-engine') . '</strong> - ' . __('Data cached locally for reliability', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Error Recovery', 'xelite-repost-engine') . '</strong> - ' . __('Automatic retry on failures', 'xelite-repost-engine') . '</li>
        </ul>';
    }

    /**
     * Get analytics help content
     */
    private function get_analytics_content() {
        return '
        <h2>' . __('Analytics Dashboard', 'xelite-repost-engine') . '</h2>
        
        <h3>' . __('Pattern Analysis', 'xelite-repost-engine') . '</h3>
        <p>' . __('The pattern analysis shows you what makes content successful on X (Twitter) based on your target accounts.', 'xelite-repost-engine') . '</p>
        
        <ul>
            <li><strong>' . __('Content Length', 'xelite-repost-engine') . '</strong> - ' . __('Optimal post length for maximum engagement', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Tone Analysis', 'xelite-repost-engine') . '</strong> - ' . __('Most effective tone for your audience', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Hashtag Usage', 'xelite-repost-engine') . '</strong> - ' . __('Trending hashtags and their performance', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Posting Times', 'xelite-repost-engine') . '</strong> - ' . __('Best times to post for maximum reach', 'xelite-repost-engine') . '</li>
        </ul>
        
        <h3>' . __('Content Suggestions', 'xelite-repost-engine') . '</h3>
        <p>' . __('AI-generated content suggestions based on successful patterns from your target accounts.', 'xelite-repost-engine') . '</p>
        
        <ul>
            <li><strong>' . __('Multiple Variations', 'xelite-repost-engine') . '</strong> - ' . __('Generate different versions of your content', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Tone Customization', 'xelite-repost-engine') . '</strong> - ' . __('Adapt content tone for your brand', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Hashtag Recommendations', 'xelite-repost-engine') . '</strong> - ' . __('Suggested hashtags based on trends', 'xelite-repost-engine') . '</li>
        </ul>
        
        <h3>' . __('Performance Tracking', 'xelite-repost-engine') . '</h3>
        <ul>
            <li><strong>' . __('Engagement Metrics', 'xelite-repost-engine') . '</strong> - ' . __('Track likes, retweets, replies, and views', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Trend Analysis', 'xelite-repost-engine') . '</strong> - ' . __('Identify emerging trends and patterns', 'xelite-repost-engine') . '</li>
            <li><strong>' . __('Competitor Analysis', 'xelite-repost-engine') . '</strong> - ' . __('Compare performance with target accounts', 'xelite-repost-engine') . '</li>
        </ul>';
    }

    /**
     * Get troubleshooting help content
     */
    private function get_troubleshooting_content() {
        return '
        <h2>' . __('Troubleshooting Guide', 'xelite-repost-engine') . '</h2>
        
        <h3>' . __('Common Issues', 'xelite-repost-engine') . '</h3>
        
        <h4>' . __('Plugin Not Activating', 'xelite-repost-engine') . '</h4>
        <ul>
            <li>' . __('Check PHP version (requires 7.4+)', 'xelite-repost-engine') . '</li>
            <li>' . __('Verify WordPress version (requires 5.0+)', 'xelite-repost-engine') . '</li>
            <li>' . __('Check file permissions (755 for directories, 644 for files)', 'xelite-repost-engine') . '</li>
            <li>' . __('Review error logs for specific issues', 'xelite-repost-engine') . '</li>
        </ul>
        
        <h4>' . __('API Connection Issues', 'xelite-repost-engine') . '</h4>
        <ul>
            <li>' . __('Verify API keys are correct and active', 'xelite-repost-engine') . '</li>
            <li>' . __('Check API rate limits and quotas', 'xelite-repost-engine') . '</li>
            <li>' . __('Ensure HTTPS is enabled for API calls', 'xelite-repost-engine') . '</li>
            <li>' . __('Test API connectivity manually', 'xelite-repost-engine') . '</li>
        </ul>
        
        <h4>' . __('Chrome Extension Not Working', 'xelite-repost-engine') . '</h4>
        <ul>
            <li>' . __('Check Chrome version (requires 88+)', 'xelite-repost-engine') . '</li>
            <li>' . __('Verify extension is enabled in chrome://extensions/', 'xelite-repost-engine') . '</li>
            <li>' . __('Check WordPress site URL format (https://yoursite.com)', 'xelite-repost-engine') . '</li>
            <li>' . __('Ensure WordPress site is accessible', 'xelite-repost-engine') . '</li>
        </ul>
        
        <h4>' . __('No Data Found', 'xelite-repost-engine') . '</h4>
        <ul>
            <li>' . __('Verify target accounts are public', 'xelite-repost-engine') . '</li>
            <li>' . __('Check if accounts have recent activity', 'xelite-repost-engine') . '</li>
            <li>' . __('Ensure scraping is working correctly', 'xelite-repost-engine') . '</li>
            <li>' . __('Review analysis settings and frequency', 'xelite-repost-engine') . '</li>
        </ul>
        
        <h3>' . __('Debug Mode', 'xelite-repost-engine') . '</h3>
        <p>' . __('Enable debug mode for detailed error information:', 'xelite-repost-engine') . '</p>
        <pre><code>define(\'WP_DEBUG\', true);
define(\'WP_DEBUG_LOG\', true);
define(\'WP_DEBUG_DISPLAY\', false);
define(\'XELITE_DEBUG\', true);</code></pre>
        
        <h3>' . __('Getting Help', 'xelite-repost-engine') . '</h3>
        <ul>
            <li>' . __('Check the plugin documentation', 'xelite-repost-engine') . '</li>
            <li>' . __('Review error logs in /wp-content/debug.log', 'xelite-repost-engine') . '</li>
            <li>' . __('Contact support with detailed error information', 'xelite-repost-engine') . '</li>
            <li>' . __('Include system information and error messages', 'xelite-repost-engine') . '</li>
        </ul>';
    }

    /**
     * Get help sidebar content
     */
    private function get_help_sidebar_content() {
        return '
        <p><strong>' . __('For more information:', 'xelite-repost-engine') . '</strong></p>
        <ul>
            <li><a href="https://docs.xelite-repost-engine.com" target="_blank">' . __('Documentation', 'xelite-repost-engine') . '</a></li>
            <li><a href="https://github.com/your-repo/xelite-repost-engine/issues" target="_blank">' . __('Report Issues', 'xelite-repost-engine') . '</a></li>
            <li><a href="https://community.xelite-repost-engine.com" target="_blank">' . __('Community Forum', 'xelite-repost-engine') . '</a></li>
        </ul>
        
        <p><strong>' . __('Quick Links:', 'xelite-repost-engine') . '</strong></p>
        <ul>
            <li><a href="' . admin_url('admin.php?page=xelite-repost-engine-settings') . '">' . __('Plugin Settings', 'xelite-repost-engine') . '</a></li>
            <li><a href="' . admin_url('admin.php?page=xelite-repost-engine-dashboard') . '">' . __('Dashboard', 'xelite-repost-engine') . '</a></li>
            <li><a href="' . admin_url('admin.php?page=xelite-repost-engine-analytics') . '">' . __('Analytics', 'xelite-repost-engine') . '</a></li>
        </ul>
        
        <p><strong>' . __('Support:', 'xelite-repost-engine') . '</strong></p>
        <p>' . __('Need help? Contact our support team at', 'xelite-repost-engine') . ' <a href="mailto:support@xelite-repost-engine.com">support@xelite-repost-engine.com</a></p>';
    }
} 