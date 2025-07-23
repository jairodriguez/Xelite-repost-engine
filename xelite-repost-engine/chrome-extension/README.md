# Xelite Repost Engine Chrome Extension

A powerful Chrome extension that scrapes X (Twitter) data and seamlessly integrates with the Xelite Repost Engine WordPress plugin. This extension serves as a fallback mechanism when API limits are reached, providing reliable data collection directly from the browser.

## 🚀 Features

### Core Functionality
- **X (Twitter) Scraping**: Extract post data directly from X platform
- **WordPress Integration**: Automatic synchronization with your WordPress site
- **Rate Limiting**: Intelligent scraping to avoid detection
- **Dynamic Content**: Support for infinite scroll and dynamic loading
- **Offline Storage**: Local data caching for reliability
- **Real-time Sync**: Automatic data transmission to WordPress

### Advanced Features
- **Mutation Observer**: Detects new content as it loads
- **Retry Logic**: Automatic retry on failures
- **Error Recovery**: Robust error handling and recovery
- **Data Validation**: Ensures data quality before syncing
- **Performance Optimization**: Efficient memory and CPU usage

## 📋 Requirements

### Browser Requirements
- **Chrome**: Version 88 or higher
- **Manifest V3**: Compatible with latest Chrome extension standards
- **JavaScript**: Enabled for extension functionality

### Permissions Required
- **Active Tab**: Access to current tab for scraping
- **Storage**: Local data storage for caching
- **Scripting**: Execute scripts in content context
- **Alarms**: Periodic synchronization with WordPress

### WordPress Requirements
- **Xelite Repost Engine Plugin**: Must be installed and activated
- **REST API**: WordPress REST API must be enabled
- **HTTPS**: Secure connection required for API communication
- **User Authentication**: Valid WordPress user credentials

## 🛠️ Installation

### Method 1: Chrome Web Store (Recommended)

1. **Visit the Chrome Web Store**
   - Go to [Chrome Web Store - Xelite Repost Engine Scraper](https://chrome.google.com/webstore/detail/xelite-repost-engine-scraper)
   - Click **Add to Chrome**
   - Confirm the installation

2. **Verify Installation**
   - Look for the extension icon in your Chrome toolbar
   - Click the icon to open the extension popup
   - Verify the extension is working properly

### Method 2: Manual Installation (Developer Mode)

1. **Download Extension Files**
   ```bash
   git clone https://github.com/your-repo/xelite-repost-engine.git
   cd xelite-repost-engine/chrome-extension
   ```

2. **Enable Developer Mode**
   - Open Chrome and go to `chrome://extensions/`
   - Toggle **Developer mode** in the top right corner
   - Click **Load unpacked**
   - Select the `chrome-extension` folder

3. **Verify Installation**
   - The extension should appear in the extensions list
   - Click the extension icon in the toolbar
   - Verify the popup opens correctly

### Method 3: From Source Code

1. **Build Extension**
   ```bash
   cd xelite-repost-engine/chrome-extension
   npm install
   npm run build
   ```

2. **Load in Chrome**
   - Open `chrome://extensions/`
   - Enable **Developer mode**
   - Click **Load unpacked**
   - Select the extension directory

## ⚙️ Configuration

### Initial Setup

#### Step 1: WordPress Authentication
1. **Click Extension Icon**
   - Click the extension icon in your Chrome toolbar
   - Go to the **Settings** tab

2. **Enter WordPress Credentials**
   - **Site URL**: `https://yoursite.com` (no trailing slash)
   - **Username**: Your WordPress admin username
   - **Password**: Your WordPress admin password

3. **Authenticate**
   - Click **Authenticate** button
   - Wait for authentication to complete
   - Verify success message appears

#### Step 2: Configure Scraping Settings
1. **Auto Scrape Settings**
   - **Enable Auto Scrape**: Toggle automatic scraping on X pages
   - **Scrape Interval**: Set frequency (default: 5 minutes)
   - **Max Posts**: Limit posts per session (default: 50)

2. **Rate Limiting**
   - **Rate Limit Delay**: Delay between scrapes (default: 1 second)
   - **Max Retries**: Number of retry attempts (default: 3)

3. **WordPress Sync**
   - **Enable WordPress Sync**: Toggle automatic data syncing
   - **Sync Interval**: Frequency of sync operations (default: 1 minute)

### Advanced Configuration

#### Performance Settings
- **Memory Management**: Clear old data periodically
- **Network Optimization**: Adjust sync frequency based on connection
- **Error Handling**: Configure retry behavior

#### Data Settings
- **Data Retention**: How long to keep local data
- **Export Options**: Configure data export formats
- **Privacy Settings**: Control data collection preferences

## 📖 Usage Guide

### Basic Scraping

#### Step 1: Navigate to X (Twitter)
1. **Open X (Twitter)**
   - Go to any X (Twitter) page (timeline, profile, search results)
   - Ensure the page has loaded completely
   - Look for posts/tweets on the page

#### Step 2: Start Scraping
1. **Click Extension Icon**
   - Click the extension icon in your Chrome toolbar
   - The popup will show current page status

2. **Start Scraping**
   - Click **Start Scraping** button
   - Watch the progress indicator
   - Wait for completion notification

#### Step 3: Monitor Progress
1. **Check Status**
   - View scraping progress in the popup
   - Monitor posts found and processed
   - Check for any error messages

2. **Verify Data**
   - Check WordPress dashboard for new data
   - Verify posts appear in the plugin interface
   - Review scraped content quality

### Advanced Usage

#### Dynamic Content Scraping
1. **Infinite Scroll**
   - The extension automatically detects new content
   - Scroll down to load more posts
   - Extension will continue scraping new content

2. **Real-time Updates**
   - New posts are detected automatically
   - Data is synced in real-time
   - No manual intervention required

#### Batch Scraping
1. **Multiple Pages**
   - Navigate between different X pages
   - Extension maintains scraping state
   - Data is accumulated across sessions

2. **Scheduled Scraping**
   - Enable auto-scrape for continuous monitoring
   - Set appropriate intervals
   - Monitor performance and adjust settings

### WordPress Integration

#### Data Synchronization
1. **Automatic Sync**
   - Data is automatically sent to WordPress
   - Sync occurs every minute by default
   - Failed syncs are retried automatically

2. **Manual Sync**
   - Click **Sync Now** in extension settings
   - Force immediate data transmission
   - Check sync status and results

#### Dashboard Integration
1. **View Scraped Data**
   - Go to WordPress admin → Xelite Repost Engine
   - Check **Content** section for scraped posts
   - Review analytics and patterns

2. **Data Management**
   - Export data in various formats
   - Filter and search scraped content
   - Analyze patterns and trends

## 🔧 Troubleshooting

### Common Issues

#### Extension Not Working
**Symptoms**: Extension icon doesn't appear or popup doesn't open

**Solutions**:
- Check Chrome version (requires 88+)
- Verify extension is enabled in `chrome://extensions/`
- Try reloading the extension
- Clear browser cache and restart Chrome

#### Authentication Failures
**Symptoms**: "WordPress authentication failed" error

**Solutions**:
- Verify site URL format (https://yoursite.com)
- Check username and password are correct
- Ensure WordPress site is accessible
- Verify plugin is activated and endpoints work

#### Scraping Not Working
**Symptoms**: "No posts found on page" or empty results

**Solutions**:
- Ensure you're on a valid X (Twitter) page
- Wait for page to load completely
- Check if page has visible posts
- Try refreshing the page and scraping again

#### Sync Issues
**Symptoms**: Data not appearing in WordPress

**Solutions**:
- Check WordPress site accessibility
- Verify extension token is valid
- Check browser console for error messages
- Test manual sync from extension settings

### Performance Issues

#### Slow Scraping
**Symptoms**: Scraping takes too long or is unresponsive

**Solutions**:
- Reduce max posts per session
- Increase rate limit delay
- Check network connectivity
- Clear extension storage

#### High Memory Usage
**Symptoms**: Browser becomes slow or crashes

**Solutions**:
- Reduce scraping frequency
- Clear old cached data
- Restart browser periodically
- Monitor memory usage in task manager

### Debug Information

#### Enable Debug Mode
1. **Open Extension Settings**
   - Click extension icon → Settings
   - Enable debug mode if available
   - Check console for detailed logs

#### Check Browser Console
1. **Open DevTools**
   - Press F12 to open developer tools
   - Go to **Console** tab
   - Look for extension-related messages

#### Extension Logs
1. **View Extension Logs**
   - Go to `chrome://extensions/`
   - Find the extension
   - Click **Details** → **Errors** (if any)

## 🔒 Security & Privacy

### Data Security
- **Local Storage**: Sensitive data stored locally only
- **HTTPS Only**: All API communications use HTTPS
- **Token-based Auth**: Secure authentication with WordPress
- **No Data Collection**: Extension doesn't collect personal data

### Privacy Protection
- **No Tracking**: Extension doesn't track user behavior
- **Local Processing**: Data processing happens locally
- **User Control**: Users control what data is collected
- **Transparent**: All data flows are visible to users

### Best Practices
- **Regular Updates**: Keep extension updated
- **Secure Credentials**: Use strong passwords
- **HTTPS Sites**: Only use on secure WordPress sites
- **Monitor Usage**: Regularly check extension activity

## 📊 Data Format

### Scraped Post Structure
```json
{
  "text": "Post content text",
  "author": "Author display name",
  "username": "author_username",
  "timestamp": "2024-01-01T00:00:00Z",
  "url": "https://twitter.com/username/status/123456789",
  "engagement": {
    "likes": 1000,
    "retweets": 500,
    "replies": 100,
    "views": 5000
  },
  "media": [
    {
      "type": "image",
      "url": "https://example.com/image.jpg"
    }
  ],
  "hashtags": ["#hashtag1", "#hashtag2"],
  "mentions": ["@user1", "@user2"],
  "isRetweet": false,
  "isReply": false,
  "isQuote": false,
  "postType": "tweet"
}
```

### WordPress API Format
```json
{
  "extension_token": "your_token",
  "data": {
    "posts": [...],
    "timestamp": 1234567890,
    "url": "https://twitter.com/...",
    "userAgent": "...",
    "totalPostsFound": 50,
    "newPostsFound": 25
  }
}
```

## 🧪 Testing

### Manual Testing
1. **Basic Functionality**
   - Test extension installation
   - Verify popup opens correctly
   - Check settings page loads

2. **Scraping Tests**
   - Test on different X pages
   - Verify data extraction
   - Check error handling

3. **WordPress Integration**
   - Test authentication
   - Verify data transmission
   - Check dashboard integration

### Automated Testing
```bash
# Run extension tests (if available)
npm test

# Check extension build
npm run build

# Validate manifest
npm run validate
```

## 📝 Changelog

### Version 1.0.0
- **Initial Release**: Complete Chrome extension with WordPress integration
- **Core Features**: X scraping, WordPress sync, rate limiting
- **Advanced Features**: Dynamic content, retry logic, offline storage
- **Security**: Token-based authentication, HTTPS only
- **Performance**: Optimized memory usage, efficient scraping

### Upcoming Features
- **Multi-platform Support**: Instagram, LinkedIn scraping
- **Advanced Analytics**: Detailed scraping statistics
- **Custom Filters**: User-defined content filtering
- **Export Options**: Multiple export formats
- **Team Features**: Multi-user support

## 🆘 Support

### Documentation
- **User Guide**: This document
- **API Reference**: WordPress integration details
- **Troubleshooting**: Common issues and solutions

### Community Support
- **GitHub Issues**: [Report bugs](https://github.com/your-repo/xelite-repost-engine/issues)
- **Discussions**: [Community forum](https://github.com/your-repo/xelite-repost-engine/discussions)
- **Wiki**: [User guides](https://github.com/your-repo/xelite-repost-engine/wiki)

### Professional Support
- **Email**: support@xelite-repost-engine.com
- **Priority Support**: Available for premium users
- **Custom Development**: Contact for custom integrations

## 🤝 Contributing

### Development Setup
1. **Fork Repository**
   ```bash
   git clone https://github.com/your-repo/xelite-repost-engine.git
   cd xelite-repost-engine/chrome-extension
   ```

2. **Install Dependencies**
   ```bash
   npm install
   ```

3. **Development Mode**
   ```bash
   npm run dev
   ```

### Code Standards
- **JavaScript**: ES6+ with ESLint configuration
- **Manifest**: Manifest V3 compliance
- **Documentation**: Inline comments and JSDoc
- **Testing**: Unit tests for all functions

### Testing Requirements
- **Unit Tests**: 80%+ code coverage
- **Integration Tests**: WordPress API integration
- **Browser Testing**: Chrome 88+ compatibility
- **Performance Testing**: Memory and CPU usage

## 📄 License

This extension is licensed under the GPL v2 or later - see the [LICENSE](../LICENSE) file for details.

## 🙏 Acknowledgments

- **Chrome Extension Community**: For development resources and best practices
- **WordPress Community**: For the excellent platform and REST API
- **X (Twitter)**: For providing the platform for data collection
- **Contributors**: All developers who contributed to this project

---

**Made with ❤️ for the WordPress community**

For more information, visit [xelite-repost-engine.com](https://xelite-repost-engine.com) 