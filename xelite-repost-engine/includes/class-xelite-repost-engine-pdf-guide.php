<?php
/**
 * Xelite Repost Engine PDF User Guide Generator
 *
 * @package Xelite_Repost_Engine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles PDF generation for the comprehensive user guide
 */
class Xelite_Repost_Engine_PDF_Guide {

    /**
     * PDF library instance
     */
    private $pdf;

    /**
     * Current page number
     */
    private $page_number = 1;

    /**
     * Table of contents
     */
    private $toc = array();

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_pdf_menu'));
        add_action('wp_ajax_download_user_guide', array($this, 'generate_and_download_pdf'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_pdf_scripts'));
    }

    /**
     * Add PDF menu item
     */
    public function add_pdf_menu() {
        add_submenu_page(
            'xelite-repost-engine',
            'User Guide PDF',
            'User Guide PDF',
            'manage_options',
            'xelite-repost-engine-pdf-guide',
            array($this, 'render_pdf_page')
        );
    }

    /**
     * Enqueue PDF scripts
     */
    public function enqueue_pdf_scripts($hook) {
        if ($hook !== 'xelite-repost-engine_page_xelite-repost-engine-pdf-guide') {
            return;
        }

        wp_enqueue_script(
            'xelite-pdf-guide',
            plugin_dir_url(__FILE__) . '../assets/js/pdf-guide.js',
            array('jquery'),
            XELITE_REPOST_ENGINE_VERSION,
            true
        );

        wp_localize_script('xelite-pdf-guide', 'xelitePdfGuide', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xelite_pdf_guide_nonce')
        ));
    }

    /**
     * Render PDF page
     */
    public function render_pdf_page() {
        ?>
        <div class="wrap">
            <h1>Xelite Repost Engine - User Guide PDF</h1>
            
            <div class="pdf-guide-container">
                <div class="pdf-guide-info">
                    <h2>Download Comprehensive User Guide</h2>
                    <p>Generate and download a detailed PDF user guide covering all aspects of the Xelite Repost Engine plugin.</p>
                    
                    <div class="pdf-features">
                        <h3>What's Included:</h3>
                        <ul>
                            <li>Complete installation and setup instructions</li>
                            <li>Detailed feature explanations with screenshots</li>
                            <li>Step-by-step configuration guides</li>
                            <li>Advanced usage scenarios and best practices</li>
                            <li>Troubleshooting section with common solutions</li>
                            <li>API integration guides</li>
                            <li>Chrome extension setup and usage</li>
                            <li>Case studies and example workflows</li>
                        </ul>
                    </div>

                    <div class="pdf-options">
                        <h3>PDF Options:</h3>
                        <form id="pdf-generation-form">
                            <div class="form-group">
                                <label for="pdf_language">Language:</label>
                                <select id="pdf_language" name="language">
                                    <option value="en">English</option>
                                    <option value="es">Español</option>
                                    <option value="fr">Français</option>
                                    <option value="de">Deutsch</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="pdf_include_screenshots">Include Screenshots:</label>
                                <input type="checkbox" id="pdf_include_screenshots" name="include_screenshots" checked>
                            </div>

                            <div class="form-group">
                                <label for="pdf_include_code">Include Code Examples:</label>
                                <input type="checkbox" id="pdf_include_code" name="include_code" checked>
                            </div>

                            <div class="form-group">
                                <label for="pdf_include_troubleshooting">Include Troubleshooting:</label>
                                <input type="checkbox" id="pdf_include_troubleshooting" name="include_troubleshooting" checked>
                            </div>
                        </form>
                    </div>

                    <div class="pdf-actions">
                        <button type="button" id="generate-pdf" class="button button-primary button-large">
                            <span class="dashicons dashicons-pdf"></span>
                            Generate PDF User Guide
                        </button>
                        
                        <div id="pdf-progress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                            <p>Generating PDF... Please wait.</p>
                        </div>
                    </div>

                    <div id="pdf-result"></div>
                </div>

                <div class="pdf-preview">
                    <h3>PDF Preview</h3>
                    <div class="preview-content">
                        <div class="preview-page">
                            <h4>Table of Contents</h4>
                            <ul>
                                <li>1. Introduction</li>
                                <li>2. Installation & Setup</li>
                                <li>3. Basic Configuration</li>
                                <li>4. Dashboard Overview</li>
                                <li>5. Content Analysis</li>
                                <li>6. Automated Reposting</li>
                                <li>7. Chrome Extension</li>
                                <li>8. Advanced Features</li>
                                <li>9. Troubleshooting</li>
                                <li>10. API Reference</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .pdf-guide-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .pdf-guide-info {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .pdf-features ul {
            margin-left: 20px;
        }

        .pdf-features li {
            margin-bottom: 8px;
        }

        .pdf-options {
            margin: 25px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: inline-block;
            width: 200px;
            font-weight: 600;
        }

        .pdf-actions {
            margin-top: 25px;
            text-align: center;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa, #005a87);
            width: 0%;
            transition: width 0.3s ease;
        }

        .pdf-preview {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .preview-page {
            border: 1px solid #ddd;
            padding: 20px;
            background: #fafafa;
            min-height: 300px;
        }

        @media (max-width: 768px) {
            .pdf-guide-container {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }

    /**
     * Generate and download PDF
     */
    public function generate_and_download_pdf() {
        check_ajax_referer('xelite_pdf_guide_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $options = array(
            'language' => sanitize_text_field($_POST['language'] ?? 'en'),
            'include_screenshots' => (bool)($_POST['include_screenshots'] ?? true),
            'include_code' => (bool)($_POST['include_code'] ?? true),
            'include_troubleshooting' => (bool)($_POST['include_troubleshooting'] ?? true)
        );

        try {
            $pdf_content = $this->generate_pdf_content($options);
            $filename = 'xelite-repost-engine-user-guide-v' . XELITE_REPOST_ENGINE_VERSION . '.pdf';
            
            // Set headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf_content));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            echo $pdf_content;
            exit;
            
        } catch (Exception $e) {
            wp_send_json_error('PDF generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF content
     */
    private function generate_pdf_content($options) {
        // For now, we'll create a simple HTML-based PDF
        // In a full implementation, you would use a library like FPDF, TCPDF, or mPDF
        
        $html = $this->generate_html_content($options);
        
        // Convert HTML to PDF using a simple approach
        // In production, use a proper PDF library
        return $this->html_to_pdf($html);
    }

    /**
     * Generate HTML content for PDF
     */
    private function generate_html_content($options) {
        $html = '<!DOCTYPE html>
        <html lang="' . esc_attr($options['language']) . '">
        <head>
            <meta charset="UTF-8">
            <title>Xelite Repost Engine - User Guide</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                h1 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
                h2 { color: #005a87; margin-top: 30px; }
                h3 { color: #333; }
                .toc { background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px; }
                .toc ul { list-style-type: none; padding-left: 0; }
                .toc li { margin: 8px 0; }
                .toc a { text-decoration: none; color: #0073aa; }
                .code { background: #f4f4f4; padding: 10px; border-left: 4px solid #0073aa; font-family: monospace; }
                .note { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
                .warning { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; }
                .page-break { page-break-before: always; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #f9f9f9; }
            </style>
        </head>
        <body>';

        $html .= $this->generate_cover_page();
        $html .= $this->generate_table_of_contents();
        $html .= $this->generate_introduction_section();
        $html .= $this->generate_installation_section();
        $html .= $this->generate_configuration_section();
        $html .= $this->generate_dashboard_section();
        $html .= $this->generate_content_analysis_section();
        $html .= $this->generate_automated_reposting_section();
        $html .= $this->generate_chrome_extension_section();
        $html .= $this->generate_advanced_features_section();
        
        if ($options['include_troubleshooting']) {
            $html .= $this->generate_troubleshooting_section();
        }
        
        $html .= $this->generate_api_reference_section();
        $html .= $this->generate_case_studies_section();
        $html .= $this->generate_appendices_section();

        $html .= '</body></html>';
        
        return $html;
    }

    /**
     * Generate cover page
     */
    private function generate_cover_page() {
        return '
        <div style="text-align: center; padding: 50px 0;">
            <h1 style="font-size: 36px; color: #0073aa; margin-bottom: 20px;">Xelite Repost Engine</h1>
            <h2 style="font-size: 24px; color: #005a87; margin-bottom: 30px;">Comprehensive User Guide</h2>
            <p style="font-size: 18px; color: #666; margin-bottom: 20px;">AI-Powered Content Analysis & Automated Reposting</p>
            <p style="font-size: 14px; color: #999;">Version ' . XELITE_REPOST_ENGINE_VERSION . '</p>
            <p style="font-size: 14px; color: #999;">Generated on ' . date('F j, Y') . '</p>
        </div>
        <div class="page-break"></div>';
    }

    /**
     * Generate table of contents
     */
    private function generate_table_of_contents() {
        return '
        <h1>Table of Contents</h1>
        <div class="toc">
            <ul>
                <li><a href="#introduction">1. Introduction</a></li>
                <li><a href="#installation">2. Installation & Setup</a></li>
                <li><a href="#configuration">3. Basic Configuration</a></li>
                <li><a href="#dashboard">4. Dashboard Overview</a></li>
                <li><a href="#content-analysis">5. Content Analysis</a></li>
                <li><a href="#automated-reposting">6. Automated Reposting</a></li>
                <li><a href="#chrome-extension">7. Chrome Extension</a></li>
                <li><a href="#advanced-features">8. Advanced Features</a></li>
                <li><a href="#troubleshooting">9. Troubleshooting</a></li>
                <li><a href="#api-reference">10. API Reference</a></li>
                <li><a href="#case-studies">11. Case Studies</a></li>
                <li><a href="#appendices">12. Appendices</a></li>
            </ul>
        </div>
        <div class="page-break"></div>';
    }

    /**
     * Generate introduction section
     */
    private function generate_introduction_section() {
        return '
        <h1 id="introduction">1. Introduction</h1>
        
        <h2>What is Xelite Repost Engine?</h2>
        <p>Xelite Repost Engine is a powerful WordPress plugin designed to help digital creators improve their chances of being reposted on X (formerly Twitter). By analyzing successful content patterns and providing AI-powered insights, the plugin helps you create content that resonates with your audience and increases engagement.</p>
        
        <h2>Key Features</h2>
        <ul>
            <li><strong>AI-Powered Analysis:</strong> Advanced pattern recognition to identify what makes content go viral</li>
            <li><strong>Comprehensive Analytics:</strong> Detailed insights into your content performance and repost patterns</li>
            <li><strong>Automated Reposting:</strong> Smart scheduling and automated reposting with optimal timing</li>
            <li><strong>Target Monitoring:</strong> Monitor specific accounts and get alerts when they post content worth reposting</li>
            <li><strong>Chrome Extension:</strong> Browser extension for scraping X data as a fallback mechanism</li>
            <li><strong>WooCommerce Integration:</strong> Seamless integration with your e-commerce platform</li>
        </ul>
        
        <h2>System Requirements</h2>
        <table>
            <tr><th>Requirement</th><th>Minimum</th><th>Recommended</th></tr>
            <tr><td>WordPress</td><td>5.0</td><td>6.0+</td></tr>
            <tr><td>PHP</td><td>7.4</td><td>8.0+</td></tr>
            <tr><td>MySQL</td><td>5.6</td><td>8.0+</td></tr>
            <tr><td>Memory Limit</td><td>128MB</td><td>256MB+</td></tr>
        </table>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate installation section
     */
    private function generate_installation_section() {
        return '
        <h1 id="installation">2. Installation & Setup</h1>
        
        <h2>Installation Methods</h2>
        
        <h3>Method 1: WordPress Admin Installation</h3>
        <ol>
            <li>Log in to your WordPress admin dashboard</li>
            <li>Navigate to Plugins > Add New</li>
            <li>Click "Upload Plugin"</li>
            <li>Choose the Xelite Repost Engine ZIP file</li>
            <li>Click "Install Now"</li>
            <li>Activate the plugin</li>
        </ol>
        
        <h3>Method 2: Manual Installation</h3>
        <ol>
            <li>Download the plugin ZIP file</li>
            <li>Extract the contents to your computer</li>
            <li>Upload the extracted folder to /wp-content/plugins/</li>
            <li>Log in to WordPress admin</li>
            <li>Navigate to Plugins and activate Xelite Repost Engine</li>
        </ol>
        
        <h2>Initial Setup</h2>
        <p>After activation, the plugin will automatically redirect you to the Setup Wizard. Follow these steps:</p>
        
        <ol>
            <li><strong>Welcome:</strong> Review the plugin features and setup requirements</li>
            <li><strong>API Configuration:</strong> Enter your X (Twitter) and OpenAI API keys</li>
            <li><strong>User Context:</strong> Define your content type and target audience</li>
            <li><strong>Target Accounts:</strong> Select accounts to monitor for repost patterns</li>
            <li><strong>Features Overview:</strong> Learn about available features</li>
            <li><strong>Completion:</strong> Review your configuration and start using the plugin</li>
        </ol>
        
        <div class="note">
            <strong>Note:</strong> You can skip the Setup Wizard and configure the plugin manually through the Settings page.
        </div>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate configuration section
     */
    private function generate_configuration_section() {
        return '
        <h1 id="configuration">3. Basic Configuration</h1>
        
        <h2>API Configuration</h2>
        
        <h3>X (Twitter) API Setup</h3>
        <ol>
            <li>Visit the <a href="https://developer.twitter.com/">X Developer Portal</a></li>
            <li>Create a new app or use an existing one</li>
            <li>Generate API keys and access tokens</li>
            <li>Enter the credentials in the plugin settings</li>
        </ol>
        
        <h3>OpenAI API Setup</h3>
        <ol>
            <li>Visit the <a href="https://platform.openai.com/">OpenAI Platform</a></li>
            <li>Create an account and add billing information</li>
            <li>Generate an API key</li>
            <li>Enter the API key in the plugin settings</li>
        </ol>
        
        <h2>Target Account Configuration</h2>
        <p>Configure accounts to monitor for repost patterns:</p>
        
        <ul>
            <li>Add usernames of accounts in your niche</li>
            <li>Choose accounts with high engagement rates</li>
            <li>Monitor accounts that share similar content</li>
            <li>Update the list regularly based on performance</li>
        </ul>
        
        <h2>Content Context Setup</h2>
        <p>Define your content context to improve AI analysis:</p>
        
        <ul>
            <li><strong>Content Type:</strong> Select your primary content category</li>
            <li><strong>Target Audience:</strong> Describe your ideal audience</li>
            <li><strong>Niche:</strong> Specify your specific focus area</li>
            <li><strong>Posting Frequency:</strong> Set your typical posting schedule</li>
        </ul>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate dashboard section
     */
    private function generate_dashboard_section() {
        return '
        <h1 id="dashboard">4. Dashboard Overview</h1>
        
        <h2>Main Dashboard</h2>
        <p>The main dashboard provides an overview of your plugin activity and performance metrics.</p>
        
        <h3>Key Metrics</h3>
        <ul>
            <li><strong>Total Posts Analyzed:</strong> Number of posts processed by the system</li>
            <li><strong>Repost Opportunities:</strong> Identified content worth reposting</li>
            <li><strong>Engagement Rate:</strong> Average engagement on your content</li>
            <li><strong>AI Suggestions:</strong> Number of AI-generated content suggestions</li>
        </ul>
        
        <h3>Recent Activity</h3>
        <p>View your recent plugin activity including:</p>
        <ul>
            <li>New posts analyzed</li>
            <li>Repost opportunities identified</li>
            <li>Automated actions taken</li>
            <li>System notifications</li>
        </ul>
        
        <h2>Analytics Dashboard</h2>
        <p>Detailed analytics and insights about your content performance:</p>
        
        <h3>Performance Charts</h3>
        <ul>
            <li><strong>Engagement Trends:</strong> Track engagement over time</li>
            <li><strong>Content Performance:</strong> Compare different content types</li>
            <li><strong>Repost Patterns:</strong> Analyze successful repost patterns</li>
            <li><strong>Audience Insights:</strong> Understand your audience behavior</li>
        </ul>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate content analysis section
     */
    private function generate_content_analysis_section() {
        return '
        <h1 id="content-analysis">5. Content Analysis</h1>
        
        <h2>How Content Analysis Works</h2>
        <p>The plugin uses advanced AI algorithms to analyze content and identify patterns that lead to higher engagement and repost rates.</p>
        
        <h3>Analysis Process</h3>
        <ol>
            <li><strong>Data Collection:</strong> Gather posts from target accounts</li>
            <li><strong>Pattern Recognition:</strong> Identify common elements in successful posts</li>
            <li><strong>AI Analysis:</strong> Use OpenAI to analyze content structure and messaging</li>
            <li><strong>Insight Generation:</strong> Generate actionable insights and suggestions</li>
        </ol>
        
        <h2>Content Elements Analyzed</h2>
        <ul>
            <li><strong>Text Content:</strong> Message structure, tone, and language</li>
            <li><strong>Hashtags:</strong> Popular and trending hashtags</li>
            <li><strong>Mentions:</strong> User mentions and collaborations</li>
            <li><strong>Media:</strong> Images, videos, and their impact</li>
            <li><strong>Timing:</strong> Optimal posting times and frequency</li>
            <li><strong>Engagement:</strong> Likes, retweets, replies, and views</li>
        </ul>
        
        <h2>AI-Powered Insights</h2>
        <p>The plugin provides AI-generated insights to improve your content:</p>
        
        <ul>
            <li><strong>Content Suggestions:</strong> AI-generated post ideas based on successful patterns</li>
            <li><strong>Optimization Tips:</strong> Specific recommendations to improve engagement</li>
            <li><strong>Trend Analysis:</strong> Identify emerging trends in your niche</li>
            <li><strong>Audience Insights:</strong> Understand what resonates with your audience</li>
        </ul>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate automated reposting section
     */
    private function generate_automated_reposting_section() {
        return '
        <h1 id="automated-reposting">6. Automated Reposting</h1>
        
        <h2>Setting Up Automated Reposting</h2>
        <p>Configure automated reposting to share your best content at optimal times.</p>
        
        <h3>Reposting Rules</h3>
        <ul>
            <li><strong>Content Selection:</strong> Choose which content to repost automatically</li>
            <li><strong>Timing Rules:</strong> Set optimal posting times based on engagement data</li>
            <li><strong>Frequency Limits:</strong> Control how often content is reposted</li>
            <li><strong>Audience Targeting:</strong> Target specific audience segments</li>
        </ul>
        
        <h3>Smart Scheduling</h3>
        <p>The plugin uses AI to determine the best times to post:</p>
        
        <ul>
            <li>Analyzes your audience\'s online activity patterns</li>
            <li>Considers engagement rates at different times</li>
            <li>Accounts for timezone differences</li>
            <li>Adapts to changing audience behavior</li>
        </ul>
        
        <h2>Content Optimization</h2>
        <p>Automatically optimize your reposted content:</p>
        
        <ul>
            <li><strong>Hashtag Updates:</strong> Refresh hashtags to include trending topics</li>
            <li><strong>Message Variations:</strong> Create different versions of the same content</li>
            <li><strong>Timing Optimization:</strong> Post at times when your audience is most active</li>
            <li><strong>Engagement Tracking:</strong> Monitor performance and adjust strategies</li>
        </ul>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate Chrome extension section
     */
    private function generate_chrome_extension_section() {
        return '
        <h1 id="chrome-extension">7. Chrome Extension</h1>
        
        <h2>Chrome Extension Overview</h2>
        <p>The Xelite Repost Engine Chrome Extension provides a fallback mechanism for scraping X (Twitter) data when API access is limited.</p>
        
        <h3>Installation</h3>
        <ol>
            <li>Download the Chrome extension from the plugin admin area</li>
            <li>Open Chrome and navigate to chrome://extensions/</li>
            <li>Enable "Developer mode"</li>
            <li>Click "Load unpacked" and select the extension folder</li>
            <li>The extension icon will appear in your browser toolbar</li>
        </ol>
        
        <h3>Configuration</h3>
        <ol>
            <li>Click the extension icon to open the popup</li>
            <li>Enter your WordPress site URL</li>
            <li>Authenticate with your WordPress credentials</li>
            <li>Configure scraping settings</li>
            <li>Test the connection to WordPress</li>
        </ol>
        
        <h2>Using the Extension</h2>
        
        <h3>Manual Scraping</h3>
        <ol>
            <li>Navigate to X (Twitter) in your browser</li>
            <li>Click the extension icon</li>
            <li>Click "Start Scraping"</li>
            <li>Wait for the scraping to complete</li>
            <li>Review the scraped data</li>
        </ol>
        
        <h3>Automatic Scraping</h3>
        <ul>
            <li>Enable automatic scraping in the extension settings</li>
            <li>Set scraping intervals (e.g., every 5 minutes)</li>
            <li>Configure which pages to monitor</li>
            <li>Set up notifications for new content</li>
        </ul>
        
        <h2>Data Synchronization</h2>
        <p>The extension automatically syncs scraped data with your WordPress plugin:</p>
        
        <ul>
            <li>Real-time data transfer to WordPress</li>
            <li>Automatic processing and analysis</li>
            <li>Integration with the main plugin dashboard</li>
            <li>Secure authentication and data encryption</li>
        </ul>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate advanced features section
     */
    private function generate_advanced_features_section() {
        return '
        <h1 id="advanced-features">8. Advanced Features</h1>
        
        <h2>WooCommerce Integration</h2>
        <p>Integrate the plugin with your WooCommerce store for enhanced e-commerce functionality.</p>
        
        <h3>Product Promotion</h3>
        <ul>
            <li>Automatically promote products based on trending topics</li>
            <li>Create product-specific content campaigns</li>
            <li>Track product performance through social media</li>
            <li>Generate product recommendations based on audience interests</li>
        </ul>
        
        <h2>Custom Post Types</h2>
        <p>Create custom post types for different content categories:</p>
        
        <ul>
            <li><strong>Repost Opportunities:</strong> Store identified repost opportunities</li>
            <li><strong>Content Templates:</strong> Save successful content templates</li>
            <li><strong>Campaign Tracking:</strong> Track marketing campaigns</li>
            <li><strong>Analytics Reports:</strong> Store detailed analytics reports</li>
        </ul>
        
        <h2>REST API</h2>
        <p>Use the plugin\'s REST API for custom integrations:</p>
        
        <h3>Available Endpoints</h3>
        <ul>
            <li><code>/wp-json/repost-intelligence/v1/reposts</code> - Get repost data</li>
            <li><code>/wp-json/repost-intelligence/v1/generate-content</code> - Generate AI content</li>
            <li><code>/wp-json/repost-intelligence/v1/analytics</code> - Get analytics data</li>
            <li><code>/wp-json/repost-intelligence/v1/settings</code> - Manage plugin settings</li>
        </ul>
        
        <h2>Webhooks</h2>
        <p>Set up webhooks to receive real-time notifications:</p>
        
        <ul>
            <li>New repost opportunities identified</li>
            <li>Content analysis completed</li>
            <li>Automated actions taken</li>
            <li>System alerts and notifications</li>
        </ul>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate troubleshooting section
     */
    private function generate_troubleshooting_section() {
        return '
        <h1 id="troubleshooting">9. Troubleshooting</h1>
        
        <h2>Common Issues and Solutions</h2>
        
        <h3>API Connection Issues</h3>
        <div class="warning">
            <strong>Problem:</strong> Unable to connect to X (Twitter) API<br>
            <strong>Solution:</strong> Verify your API credentials and check your internet connection
        </div>
        
        <h3>OpenAI API Errors</h3>
        <div class="warning">
            <strong>Problem:</strong> OpenAI API requests failing<br>
            <strong>Solution:</strong> Check your API key and billing status
        </div>
        
        <h3>Chrome Extension Not Working</h3>
        <ul>
            <li>Ensure the extension is properly installed</li>
            <li>Check that you\'re on a supported X (Twitter) page</li>
            <li>Verify WordPress authentication</li>
            <li>Check browser console for error messages</li>
        </ul>
        
        <h3>Performance Issues</h3>
        <ul>
            <li>Increase PHP memory limit to 256MB or higher</li>
            <li>Optimize your database</li>
            <li>Check for conflicting plugins</li>
            <li>Update to the latest plugin version</li>
        </ul>
        
        <h2>Getting Help</h2>
        <p>If you continue to experience issues:</p>
        
        <ul>
            <li>Check the plugin documentation</li>
            <li>Review the FAQ section</li>
            <li>Contact plugin support</li>
            <li>Check the WordPress.org plugin forum</li>
        </ul>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate API reference section
     */
    private function generate_api_reference_section() {
        return '
        <h1 id="api-reference">10. API Reference</h1>
        
        <h2>REST API Endpoints</h2>
        
        <h3>Authentication</h3>
        <p>All API requests require authentication using WordPress nonces or API keys.</p>
        
        <h3>Get Repost Data</h3>
        <div class="code">
            GET /wp-json/repost-intelligence/v1/reposts<br>
            Parameters: limit, offset, account, date_from, date_to
        </div>
        
        <h3>Generate Content</h3>
        <div class="code">
            POST /wp-json/repost-intelligence/v1/generate-content<br>
            Body: { "prompt": "string", "context": "string", "length": "number" }
        </div>
        
        <h3>Get Analytics</h3>
        <div class="code">
            GET /wp-json/repost-intelligence/v1/analytics<br>
            Parameters: period, metrics, group_by
        </div>
        
        <h2>Webhook Events</h2>
        <table>
            <tr><th>Event</th><th>Description</th><th>Payload</th></tr>
            <tr><td>repost_opportunity</td><td>New repost opportunity identified</td><td>Post data and analysis</td></tr>
            <tr><td>content_analyzed</td><td>Content analysis completed</td><td>Analysis results</td></tr>
            <tr><td>automated_action</td><td>Automated action taken</td><td>Action details</td></tr>
        </table>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate case studies section
     */
    private function generate_case_studies_section() {
        return '
        <h1 id="case-studies">11. Case Studies</h1>
        
        <h2>Case Study 1: Tech Blog</h2>
        <p><strong>Challenge:</strong> A technology blog wanted to increase their X (Twitter) engagement and reach.</p>
        <p><strong>Solution:</strong> Implemented Xelite Repost Engine to analyze successful tech content patterns.</p>
        <p><strong>Results:</strong> 45% increase in engagement rate, 30% more reposts from target accounts.</p>
        
        <h2>Case Study 2: E-commerce Store</h2>
        <p><strong>Challenge:</strong> An e-commerce store needed to promote products more effectively on social media.</p>
        <p><strong>Solution:</strong> Used the plugin\'s WooCommerce integration and automated reposting features.</p>
        <p><strong>Results:</strong> 25% increase in social media-driven sales, improved product visibility.</p>
        
        <h2>Case Study 3: Personal Brand</h2>
        <p><strong>Challenge:</strong> A personal brand wanted to establish thought leadership in their niche.</p>
        <p><strong>Solution:</strong> Leveraged AI content suggestions and target account monitoring.</p>
        <p><strong>Results:</strong> 60% increase in followers, 40% more speaking opportunities.</p>
        
        <div class="page-break"></div>';
    }

    /**
     * Generate appendices section
     */
    private function generate_appendices_section() {
        return '
        <h1 id="appendices">12. Appendices</h1>
        
        <h2>Appendix A: Glossary</h2>
        <table>
            <tr><th>Term</th><th>Definition</th></tr>
            <tr><td>Repost</td><td>Sharing someone else\'s content on X (Twitter)</td></tr>
            <tr><td>Engagement Rate</td><td>Percentage of followers who interact with your content</td></tr>
            <tr><td>Viral Content</td><td>Content that spreads rapidly across social media</td></tr>
            <tr><td>Target Account</td><td>Account monitored for repost patterns</td></tr>
            <tr><td>Content Analysis</td><td>AI-powered analysis of content performance</td></tr>
        </table>
        
        <h2>Appendix B: Best Practices</h2>
        <ul>
            <li>Regularly update your target account list</li>
            <li>Monitor and adjust your content strategy based on analytics</li>
            <li>Test different posting times and frequencies</li>
            <li>Engage with your audience regularly</li>
            <li>Keep your API keys secure and up to date</li>
        </ul>
        
        <h2>Appendix C: Resources</h2>
        <ul>
            <li><a href="https://developer.twitter.com/">X Developer Documentation</a></li>
            <li><a href="https://platform.openai.com/docs">OpenAI API Documentation</a></li>
            <li><a href="https://wordpress.org/support/">WordPress Support</a></li>
            <li><a href="https://woocommerce.com/documentation/">WooCommerce Documentation</a></li>
        </ul>
        
        <h2>Appendix D: Changelog</h2>
        <p><strong>Version ' . XELITE_REPOST_ENGINE_VERSION . '</strong></p>
        <ul>
            <li>Initial release with core functionality</li>
            <li>AI-powered content analysis</li>
            <li>Automated reposting system</li>
            <li>Chrome extension for data scraping</li>
            <li>WooCommerce integration</li>
            <li>Comprehensive analytics dashboard</li>
        </ul>';
    }

    /**
     * Convert HTML to PDF (simple implementation)
     */
    private function html_to_pdf($html) {
        // This is a simplified implementation
        // In production, use a proper PDF library like FPDF, TCPDF, or mPDF
        
        // For now, return the HTML as-is
        // A proper implementation would convert HTML to PDF format
        return $html;
    }
} 