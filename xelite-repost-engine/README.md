# Xelite Repost Engine

A powerful WordPress plugin for intelligent content reposting and social media management, featuring AI-powered content analysis, pattern recognition, and automated reposting capabilities.

## 🚀 Features

### Core Plugin Features
- **AI-Powered Content Analysis**: Advanced pattern recognition and content intelligence
- **Automated Reposting**: Smart scheduling and content distribution
- **Dashboard Analytics**: Comprehensive insights and performance tracking
- **WooCommerce Integration**: Seamless e-commerce content management
- **Custom Post Types**: Dedicated content management for reposted items
- **REST API**: Full API access for external integrations
- **Cron Job Management**: Automated background processing
- **User Role Management**: Granular permissions and access control

### Chrome Extension (Fallback Scraping)
- **X (Twitter) Scraping**: Advanced content extraction from X platform
- **Real-time Data Sync**: Automatic synchronization with WordPress plugin
- **Rate Limiting**: Intelligent scraping to avoid detection
- **Dynamic Content Handling**: Support for infinite scroll and dynamic loading
- **WordPress Integration**: Secure API communication with the main plugin
- **Offline Storage**: Local data caching for reliability

## 📋 Requirements

### WordPress Plugin Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.7 or higher
- **Memory Limit**: 256MB minimum (512MB recommended)
- **Upload Limit**: 64MB minimum
- **cURL**: Enabled for API communications
- **JSON**: PHP JSON extension enabled

### Chrome Extension Requirements
- **Chrome Browser**: Version 88 or higher
- **Manifest V3**: Compatible with latest Chrome extension standards
- **Active Tab Permission**: For X (Twitter) scraping functionality
- **Storage Permission**: For local data caching
- **Network Access**: For WordPress API communication

### Optional Dependencies
- **WooCommerce**: 5.0+ for e-commerce integration
- **OpenAI API Key**: For advanced AI features
- **X (Twitter) API Access**: For primary data collection (fallback to Chrome extension)

## 🛠️ Installation

### WordPress Plugin Installation

#### Method 1: WordPress Admin (Recommended)
1. Download the plugin ZIP file from the releases page
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin** and select the ZIP file
4. Click **Install Now** and then **Activate Plugin**
5. Navigate to **Xelite Repost Engine** in the admin menu

#### Method 2: Manual Installation
1. Download and extract the plugin files
2. Upload the `xelite-repost-engine` folder to `/wp-content/plugins/`
3. Activate the plugin through **Plugins → Installed Plugins**
4. Configure the plugin settings

#### Method 3: Git Installation
```bash
cd wp-content/plugins
git clone https://github.com/your-repo/xelite-repost-engine.git
cd xelite-repost-engine
composer install
```

### Chrome Extension Installation

#### Method 1: Chrome Web Store (Recommended)
1. Visit the Chrome Web Store listing for "Xelite Repost Engine Scraper"
2. Click **Add to Chrome**
3. Confirm the installation
4. The extension will appear in your Chrome toolbar

#### Method 2: Manual Installation (Developer Mode)
1. Download the Chrome extension files
2. Open Chrome and go to `chrome://extensions/`
3. Enable **Developer mode** (toggle in top right)
4. Click **Load unpacked** and select the extension folder
5. The extension will be installed and ready to use

#### Method 3: From Source
```bash
git clone https://github.com/your-repo/xelite-repost-engine.git
cd xelite-repost-engine/chrome-extension
# Load as unpacked extension in Chrome
```

## ⚙️ Configuration

### WordPress Plugin Setup

#### 1. Initial Configuration
1. Go to **Xelite Repost Engine → Settings**
2. Configure your **OpenAI API Key** (optional but recommended)
3. Set up **X (Twitter) API credentials** (if available)
4. Configure **WooCommerce integration** (if applicable)

#### 2. Chrome Extension Integration
1. In the plugin settings, go to **Chrome Extension** tab
2. Note your **Site URL** and **Extension Token**
3. Use these credentials in the Chrome extension authentication

#### 3. Database Setup
The plugin will automatically create required database tables on activation:
- `wp_xelite_repost_data` - Main repost data storage
- `wp_xelite_patterns` - Pattern analysis data
- `wp_xelite_extension_data` - Chrome extension data
- `wp_xelite_user_meta` - User-specific settings

### Chrome Extension Setup

#### 1. Authentication
1. Click the extension icon in Chrome toolbar
2. Go to **Settings** tab
3. Enter your WordPress site URL, username, and password
4. Click **Authenticate** to establish connection

#### 2. Configuration
- **Auto Scrape**: Enable automatic scraping on X pages
- **Scrape Interval**: Set frequency for automatic scraping (default: 5 minutes)
- **Max Posts**: Limit posts per scraping session (default: 50)
- **Rate Limiting**: Delay between scrapes (default: 1 second)

#### 3. Usage
1. Navigate to any X (Twitter) page
2. Click the extension icon and select **Start Scraping**
3. The extension will extract post data and sync with WordPress
4. View scraped data in the WordPress admin dashboard

## 📖 Usage Guide

### WordPress Dashboard

#### Main Dashboard
- **Overview**: Quick statistics and recent activity
- **Analytics**: Detailed performance metrics and charts
- **Content Generator**: AI-powered content creation tools
- **Patterns**: Pattern analysis and recognition features
- **Settings**: Plugin configuration and management

#### Content Management
1. **View Scraped Content**: Go to **Xelite Repost Engine → Content**
2. **Analyze Patterns**: Use the pattern analyzer for insights
3. **Generate Content**: Create new content using AI tools
4. **Schedule Posts**: Set up automated reposting schedules

### Chrome Extension Usage

#### Basic Scraping
1. Navigate to X (Twitter) page
2. Click extension icon → **Start Scraping**
3. Wait for completion notification
4. Data automatically syncs to WordPress

#### Advanced Features
- **Dynamic Content**: Extension handles infinite scroll automatically
- **Rate Limiting**: Built-in protection against detection
- **Error Recovery**: Automatic retry on failures
- **Offline Support**: Data cached locally if WordPress unavailable

## 🔧 Troubleshooting

### Common WordPress Issues

#### Plugin Activation Errors
```
Error: Plugin could not be activated due to a fatal error
```
**Solution**: Check PHP version (7.4+) and memory limit (256MB+)

#### Database Connection Issues
```
Error: Unable to create database tables
```
**Solution**: Verify database permissions and WordPress database access

#### API Connection Problems
```
Error: OpenAI API connection failed
```
**Solution**: Verify API key and internet connectivity

### Common Chrome Extension Issues

#### Authentication Failures
```
Error: WordPress authentication failed
```
**Solutions**:
- Verify site URL format (https://yoursite.com)
- Check username/password credentials
- Ensure plugin is activated and extension endpoint is accessible

#### Scraping Not Working
```
Error: No posts found on page
```
**Solutions**:
- Ensure you're on a valid X (Twitter) page
- Check if page has loaded completely
- Try refreshing the page and scraping again

#### Sync Issues
```
Error: Failed to sync with WordPress
```
**Solutions**:
- Check WordPress site accessibility
- Verify extension token is valid
- Check browser console for detailed error messages

### Performance Optimization

#### WordPress Performance
- **Database Optimization**: Regular cleanup of old data
- **Caching**: Enable WordPress caching plugins
- **Memory Management**: Monitor memory usage in dashboard

#### Chrome Extension Performance
- **Rate Limiting**: Adjust scraping intervals to avoid overload
- **Data Management**: Clear old cached data periodically
- **Network Optimization**: Ensure stable internet connection

## 🔒 Security

### WordPress Security
- **API Authentication**: Secure token-based authentication
- **Data Sanitization**: All user input is sanitized and validated
- **Role-based Access**: Granular permissions for different user roles
- **Database Security**: Prepared statements and SQL injection protection

### Chrome Extension Security
- **Local Storage**: Sensitive data stored locally only
- **HTTPS Only**: All API communications use HTTPS
- **Token-based Auth**: Secure authentication with WordPress
- **No Data Collection**: Extension doesn't collect personal data

## 📊 API Documentation

### WordPress REST API Endpoints

#### Authentication
```
POST /wp-json/repost-intelligence/v1/extension-auth
Content-Type: application/json

{
  "username": "your_username",
  "password": "your_password"
}
```

#### Data Submission
```
POST /wp-json/repost-intelligence/v1/extension-data
Headers: X-Extension-Token: your_token
Content-Type: application/json

{
  "extension_token": "your_token",
  "data": {
    "posts": [...],
    "timestamp": 1234567890,
    "url": "https://twitter.com/...",
    "userAgent": "..."
  }
}
```

#### Fallback Status Check
```
GET /wp-json/repost-intelligence/v1/fallback-status
Headers: X-Extension-Token: your_token
```

### Chrome Extension API

#### Background Script Communication
```javascript
// Start scraping
chrome.runtime.sendMessage({ action: 'startScraping' });

// Get status
chrome.runtime.sendMessage({ action: 'getStatus' }, (response) => {
  console.log(response);
});

// Authenticate with WordPress
chrome.runtime.sendMessage({
  action: 'authenticateWordPress',
  credentials: { siteUrl, username, password }
});
```

## 🧪 Testing

### WordPress Plugin Testing
```bash
cd xelite-repost-engine
composer install
./vendor/bin/phpunit tests/
```

### Chrome Extension Testing
1. Load extension in developer mode
2. Open Chrome DevTools
3. Navigate to X (Twitter) page
4. Test scraping functionality
5. Check console for errors

### Integration Testing
1. Set up WordPress plugin
2. Install Chrome extension
3. Authenticate extension with WordPress
4. Test end-to-end data flow
5. Verify data appears in WordPress dashboard

## 📝 Changelog

### Version 1.0.0 (Current)
- **Initial Release**: Complete WordPress plugin with Chrome extension
- **Core Features**: AI analysis, automated reposting, dashboard
- **Chrome Extension**: X (Twitter) scraping with WordPress integration
- **WooCommerce Integration**: E-commerce content management
- **REST API**: Full API access for external integrations

### Upcoming Features
- **Multi-platform Support**: Instagram, LinkedIn, Facebook scraping
- **Advanced AI**: Enhanced pattern recognition and content generation
- **Analytics Dashboard**: More detailed performance metrics
- **Mobile App**: Companion mobile application
- **Team Collaboration**: Multi-user support and permissions

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Make your changes and test thoroughly
4. Submit a pull request with detailed description

### Code Standards
- **PHP**: PSR-12 coding standards
- **JavaScript**: ESLint configuration
- **CSS**: BEM methodology
- **Documentation**: Inline comments and PHPDoc blocks

### Testing Requirements
- **Unit Tests**: 80%+ code coverage
- **Integration Tests**: End-to-end functionality
- **Browser Testing**: Chrome, Firefox, Safari compatibility
- **WordPress Compatibility**: 5.0+ versions

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- **Plugin Documentation**: [docs/plugin/](docs/plugin/)
- **Extension Documentation**: [docs/extension/](docs/extension/)
- **API Reference**: [docs/api/](docs/api/)

### Community Support
- **GitHub Issues**: [Report bugs and feature requests](https://github.com/your-repo/xelite-repost-engine/issues)
- **Discussions**: [Community forum](https://github.com/your-repo/xelite-repost-engine/discussions)
- **Wiki**: [User guides and tutorials](https://github.com/your-repo/xelite-repost-engine/wiki)

### Professional Support
- **Email**: support@xelite-repost-engine.com
- **Priority Support**: Available for premium users
- **Custom Development**: Contact for custom integrations

## 🙏 Acknowledgments

- **WordPress Community**: For the amazing platform and ecosystem
- **OpenAI**: For providing the AI capabilities
- **Chrome Extension Community**: For development resources and best practices
- **Contributors**: All the developers who contributed to this project

---

**Made with ❤️ for the WordPress community**

For more information, visit [xelite-repost-engine.com](https://xelite-repost-engine.com) 