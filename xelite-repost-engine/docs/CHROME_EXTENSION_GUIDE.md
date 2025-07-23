# Chrome Extension User Guide

This guide provides detailed instructions for installing, configuring, and using the Xelite Repost Engine Chrome extension.

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Initial Setup](#initial-setup)
- [Usage](#usage)
- [Features](#features)
- [Settings](#settings)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

## Overview

The Xelite Repost Engine Chrome extension is a powerful tool that allows you to scrape data directly from X (Twitter) pages and sync it with your WordPress site. It serves as a fallback mechanism when API limits are reached and provides real-time data collection capabilities.

### Key Features

- **Direct Data Scraping**: Extract data directly from X (Twitter) pages
- **Real-time Sync**: Automatically sync data with WordPress
- **Rate Limiting**: Respectful scraping with built-in delays
- **Offline Support**: Store data locally when offline
- **User Authentication**: Secure connection with WordPress
- **Error Handling**: Robust error handling and retry logic

### System Requirements

- **Browser**: Chrome 88+, Edge 88+, Opera 74+, or Brave 1.20+
- **WordPress Site**: Must have the Xelite Repost Engine plugin installed
- **Internet Connection**: Required for syncing with WordPress
- **User Account**: WordPress user account with appropriate permissions

## Installation

### Method 1: Developer Mode Installation

#### Step 1: Download the Extension

1. **From Plugin Settings**
   - Go to your WordPress admin dashboard
   - Navigate to **Xelite Repost Engine** → **Chrome Extension**
   - Click **Download Extension**
   - Save the ZIP file to your computer

2. **Extract the Files**
   ```bash
   # Extract the ZIP file
   unzip xelite-repost-engine-extension.zip
   
   # The extension folder should contain:
   # - manifest.json
   # - background.js
   # - content.js
   # - popup.html
   # - popup.js
   # - icons/ (folder with icon files)
   ```

#### Step 2: Install in Chrome

1. **Open Chrome Extensions Page**
   - Open Google Chrome
   - Navigate to `chrome://extensions/`
   - Or go to **Menu** → **More Tools** → **Extensions**

2. **Enable Developer Mode**
   - Toggle the **Developer mode** switch in the top-right corner
   - This enables additional options for loading unpacked extensions

3. **Load the Extension**
   - Click **Load unpacked**
   - Select the extracted extension folder
   - Click **Select Folder**

4. **Verify Installation**
   - The extension should appear in your extensions list
   - You should see the Xelite Repost Engine icon in your browser toolbar

### Method 2: Chrome Web Store (Future)

When available, you'll be able to install directly from the Chrome Web Store:

1. Visit the Chrome Web Store
2. Search for "Xelite Repost Engine"
3. Click **Add to Chrome**
4. Confirm the installation

## Initial Setup

### Step 1: Authenticate with WordPress

1. **Click the Extension Icon**
   - Click the Xelite Repost Engine icon in your browser toolbar
   - The extension popup will open

2. **Enter WordPress Site Information**
   - **WordPress Site URL**: Enter your WordPress site URL (e.g., `https://yoursite.com`)
   - **Username**: Enter your WordPress username
   - **Password**: Enter your WordPress password

3. **Authenticate**
   - Click **Authenticate**
   - Wait for the authentication process to complete
   - You should see a success message

### Step 2: Configure Settings

1. **Access Settings**
   - In the extension popup, scroll down to the **Settings** section

2. **Configure Scraping Settings**
   - **Auto-scrape on page load**: Enable to automatically start scraping when visiting X (Twitter) pages
   - **Max posts per scrape**: Set the maximum number of posts to scrape (10-200)
   - **Rate limit delay**: Set delay between scrapes in milliseconds (500-5000)
   - **Enable WordPress sync**: Enable to automatically sync data with WordPress

3. **Save Settings**
   - Click **Save Settings**
   - Your preferences will be stored locally

### Step 3: Test the Extension

1. **Navigate to X (Twitter)**
   - Go to any X (Twitter) page (e.g., `https://twitter.com/elonmusk`)
   - The extension should detect the page automatically

2. **Start Scraping**
   - Click **Start Scraping** in the extension popup
   - Watch the progress bar and status updates
   - The extension will extract data from the page

3. **Check Sync Status**
   - After scraping completes, check the sync status
   - Data should automatically sync with your WordPress site

## Usage

### Basic Scraping

#### Manual Scraping

1. **Navigate to Target Page**
   - Go to any X (Twitter) page you want to analyze
   - This can be a user profile, hashtag page, or search results

2. **Start Scraping**
   - Click the extension icon
   - Click **Start Scraping**
   - Wait for the scraping process to complete

3. **Monitor Progress**
   - Watch the progress bar
   - Check the status messages
   - View real-time statistics

#### Automatic Scraping

1. **Enable Auto-scrape**
   - In extension settings, enable **Auto-scrape on page load**
   - The extension will automatically start scraping when you visit X (Twitter) pages

2. **Configure Triggers**
   - Set which pages should trigger automatic scraping
   - Configure scraping frequency and limits

### Data Management

#### View Scraped Data

1. **Check Extension Status**
   - Click the extension icon
   - View the **Stats** section for recent scraping results
   - See total posts found and new posts

2. **Access WordPress Dashboard**
   - Go to your WordPress admin dashboard
   - Navigate to **Xelite Repost Engine** → **Dashboard**
   - View scraped data in the **Extension Data** section

#### Sync with WordPress

1. **Automatic Sync**
   - Data automatically syncs after each scrape
   - Check the sync status in the extension popup

2. **Manual Sync**
   - Click **Sync with WordPress** in the extension popup
   - Force a manual sync of stored data

### Advanced Features

#### Batch Scraping

1. **Multiple Pages**
   - Visit multiple X (Twitter) pages in sequence
   - Each page will be scraped automatically (if enabled)
   - Data from all pages will be synced

2. **Scheduled Scraping**
   - Use the extension regularly to build a comprehensive dataset
   - Data accumulates over time for better pattern analysis

#### Data Export

1. **From WordPress Dashboard**
   - Go to **Xelite Repost Engine** → **Dashboard**
   - Use the export functionality to download data
   - Available formats: CSV, JSON, PDF

## Features

### Data Extraction

The extension extracts the following data from X (Twitter) pages:

#### Post Information
- **Text Content**: The main text of the post
- **Author Information**: Username and display name
- **Timestamp**: When the post was created
- **Post URL**: Direct link to the post
- **Post Type**: Tweet, retweet, reply, or quote

#### Engagement Metrics
- **Likes**: Number of likes
- **Retweets**: Number of retweets
- **Replies**: Number of replies
- **Views**: Number of views (if available)

#### Content Analysis
- **Hashtags**: Extracted hashtags from the post
- **Mentions**: User mentions in the post
- **Media**: Images and videos attached to the post
- **Links**: URLs shared in the post

### Smart Detection

#### Page Type Detection
- **User Profiles**: Detects user profile pages
- **Timeline Pages**: Detects home timeline and user timelines
- **Search Results**: Detects search result pages
- **Hashtag Pages**: Detects hashtag-specific pages

#### Content Filtering
- **Post Validation**: Ensures only valid posts are scraped
- **Duplicate Detection**: Prevents scraping the same post multiple times
- **Quality Filtering**: Filters out low-quality or irrelevant content

### Rate Limiting

#### Built-in Delays
- **Scraping Delays**: Configurable delays between scrapes
- **API Respect**: Respects X (Twitter) rate limits
- **User-Friendly**: Prevents overwhelming the target site

#### Error Handling
- **Retry Logic**: Automatically retries failed requests
- **Graceful Degradation**: Continues working even if some requests fail
- **Error Reporting**: Provides clear error messages

## Settings

### General Settings

#### Scraping Configuration
```javascript
// Example settings
{
  "autoScrape": true,
  "maxPostsPerScrape": 50,
  "rateLimitDelay": 1000,
  "enableWordPressSync": true,
  "syncInterval": 60000
}
```

#### WordPress Configuration
```javascript
// WordPress connection settings
{
  "siteUrl": "https://yoursite.com",
  "isAuthenticated": true,
  "extensionToken": "your_extension_token",
  "apiEndpoint": "https://yoursite.com/wp-json/repost-intelligence/v1/extension-data"
}
```

### Advanced Settings

#### Performance Options
- **Memory Management**: Configure memory usage limits
- **Cache Settings**: Control local data caching
- **Sync Frequency**: Set how often to sync with WordPress

#### Security Options
- **Token Refresh**: Automatic token refresh for security
- **Data Encryption**: Local data encryption (if available)
- **Privacy Controls**: Control what data is collected

## Troubleshooting

### Common Issues

#### 1. Extension Won't Install

**Symptoms:**
- Extension fails to load
- Manifest errors appear
- Extension doesn't appear in toolbar

**Solutions:**
```javascript
// Check manifest.json
{
  "manifest_version": 3,
  "name": "Xelite Repost Engine Scraper",
  "version": "1.0.0",
  "permissions": [
    "activeTab",
    "storage",
    "scripting",
    "alarms"
  ],
  "host_permissions": [
    "https://twitter.com/*",
    "https://x.com/*"
  ]
}
```

#### 2. Authentication Fails

**Symptoms:**
- Can't authenticate with WordPress
- Authentication errors
- Sync fails

**Solutions:**
```php
// Check WordPress site accessibility
// Verify user credentials
// Ensure HTTPS is enabled
// Check API endpoints are working
```

#### 3. Scraping Doesn't Work

**Symptoms:**
- No data is extracted
- Scraping fails immediately
- Empty results

**Solutions:**
```javascript
// Check if page is X (Twitter)
// Verify page has loaded completely
// Check for JavaScript errors
// Ensure permissions are granted
```

#### 4. Sync Issues

**Symptoms:**
- Data doesn't sync with WordPress
- Sync errors
- Duplicate data

**Solutions:**
```php
// Check WordPress site URL
// Verify extension token
// Check API endpoint accessibility
// Review error logs
```

### Debug Mode

Enable debug mode for detailed information:

1. **Open Developer Tools**
   - Right-click the extension icon
   - Select **Inspect popup**

2. **Check Console**
   - Look for error messages
   - Check network requests
   - Review JavaScript errors

3. **Extension Logs**
   - Check the extension's background page
   - Review console logs for debugging information

### Error Codes

| Error Code | Description | Solution |
|------------|-------------|----------|
| AUTH_FAILED | Authentication failed | Check credentials and site URL |
| SYNC_ERROR | Sync failed | Check WordPress site and API |
| SCRAPE_ERROR | Scraping failed | Check page content and permissions |
| RATE_LIMIT | Rate limit exceeded | Wait and try again |
| NETWORK_ERROR | Network connection failed | Check internet connection |

## FAQ

### General Questions

**Q: Is the extension free to use?**
A: Yes, the Chrome extension is free and included with the WordPress plugin.

**Q: Do I need a WordPress site to use the extension?**
A: Yes, the extension requires a WordPress site with the Xelite Repost Engine plugin installed.

**Q: Can I use the extension without the WordPress plugin?**
A: No, the extension is designed to work with the WordPress plugin and sync data to it.

**Q: Is my data secure?**
A: Yes, all data is transmitted securely using HTTPS and stored securely in your WordPress database.

### Technical Questions

**Q: What browsers are supported?**
A: Chrome 88+, Edge 88+, Opera 74+, and Brave 1.20+.

**Q: Does the extension work on mobile?**
A: The extension is designed for desktop browsers. Mobile browsers have limited extension support.

**Q: Can I scrape private accounts?**
A: No, the extension can only scrape public X (Twitter) content due to platform limitations.

**Q: How much data can I scrape?**
A: The extension respects rate limits and is designed for reasonable data collection. Excessive scraping may violate terms of service.

### Usage Questions

**Q: How often should I use the extension?**
A: Use the extension as needed for your analysis. The WordPress plugin will handle data processing and pattern analysis.

**Q: Can I export data from the extension?**
A: Data export is handled through the WordPress dashboard, not directly from the extension.

**Q: What happens if I lose my extension token?**
A: You can re-authenticate with WordPress to get a new token.

**Q: Can I use multiple WordPress sites?**
A: The extension is designed to work with one WordPress site at a time. You can change the site URL in settings.

### Privacy and Security

**Q: What data does the extension collect?**
A: The extension only collects public X (Twitter) data that you explicitly scrape. No personal data is collected.

**Q: Is my WordPress password stored securely?**
A: Passwords are only used for initial authentication and are not stored locally. The extension uses secure tokens for ongoing communication.

**Q: Can I control what data is synced?**
A: Yes, you can configure sync settings and choose what data to send to WordPress.

**Q: How do I revoke access?**
A: You can revoke access by deactivating the extension or changing your WordPress password.

## Support

### Getting Help

1. **Check Documentation**: Review this guide and the main plugin documentation
2. **Extension Help**: Click the help icon in the extension popup
3. **WordPress Support**: Contact support through your WordPress admin
4. **Community Forum**: Ask questions in our community forum
5. **Email Support**: Contact us at support@xelite.com

### Reporting Issues

When reporting issues, please include:

- Browser version and operating system
- Extension version
- WordPress plugin version
- Steps to reproduce the issue
- Error messages or screenshots
- Console logs (if available)

### Feature Requests

We welcome feature requests and suggestions:

- Submit through our support channels
- Include detailed descriptions and use cases
- Consider contributing to the project

For additional help, visit our [support page](https://support.xelite.com) or contact us at support@xelite.com. 