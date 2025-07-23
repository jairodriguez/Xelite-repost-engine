# Installation Guide

This guide provides detailed instructions for installing and setting up the Xelite Repost Engine plugin.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation Methods](#installation-methods)
- [Post-Installation Setup](#post-installation-setup)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)

## Prerequisites

Before installing the plugin, ensure your system meets the following requirements:

### System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP | 7.4 | 8.0+ |
| WordPress | 5.0 | 6.0+ |
| MySQL | 5.6 | 8.0+ |
| Memory Limit | 256MB | 512MB+ |
| Upload Limit | 10MB | 50MB+ |

### Server Requirements

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SSL Certificate**: Required for secure API communications
- **Cron Jobs**: Recommended for automated scraping
- **cURL**: Enabled for API communications
- **JSON**: PHP JSON extension enabled

### Browser Requirements (Chrome Extension)

- **Chrome**: 88 or higher
- **Edge**: 88 or higher (Chromium-based)
- **Opera**: 74 or higher
- **Brave**: 1.20 or higher

## Installation Methods

### Method 1: WordPress Admin (Recommended)

This is the easiest method for most users.

#### Step 1: Download the Plugin

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins** → **Add New**
3. Click the **Upload Plugin** button
4. Choose the plugin ZIP file
5. Click **Install Now**

#### Step 2: Activate the Plugin

1. After installation, click **Activate Plugin**
2. You'll see a success message confirming activation
3. The plugin menu will appear in your admin sidebar

#### Step 3: Initial Setup

1. Go to **Xelite Repost Engine** → **Settings**
2. Follow the setup wizard to configure basic settings
3. Add your API keys and target accounts

### Method 2: Manual Installation

Use this method if you prefer to install manually or if the admin method fails.

#### Step 1: Download and Extract

```bash
# Download the plugin
wget https://github.com/xelite/repost-engine/archive/main.zip

# Extract the files
unzip main.zip

# Rename the directory
mv repost-engine-main xelite-repost-engine
```

#### Step 2: Upload to WordPress

1. Connect to your server via FTP/SFTP
2. Navigate to `/wp-content/plugins/`
3. Upload the `xelite-repost-engine` folder
4. Ensure proper file permissions (755 for directories, 644 for files)

#### Step 3: Activate via Admin

1. Go to **Plugins** → **Installed Plugins**
2. Find "Xelite Repost Engine"
3. Click **Activate**

### Method 3: Composer Installation

For developers using Composer.

#### Step 1: Add to Composer

```bash
# Add to your project
composer require xelite/repost-engine

# Or add to composer.json
{
    "require": {
        "xelite/repost-engine": "^1.0"
    }
}
```

#### Step 2: Install Dependencies

```bash
composer install
```

#### Step 3: Activate Plugin

1. Go to **Plugins** → **Installed Plugins**
2. Find "Xelite Repost Engine"
3. Click **Activate**

### Method 4: Git Installation

For developers who want to track changes.

#### Step 1: Clone Repository

```bash
# Clone the repository
git clone https://github.com/xelite/repost-engine.git

# Move to plugins directory
mv repost-engine /wp-content/plugins/xelite-repost-engine

# Navigate to plugin directory
cd /wp-content/plugins/xelite-repost-engine
```

#### Step 2: Install Dependencies

```bash
# Install Composer dependencies
composer install

# Install npm dependencies (if any)
npm install
```

#### Step 3: Activate Plugin

1. Go to **Plugins** → **Installed Plugins**
2. Find "Xelite Repost Engine"
3. Click **Activate**

## Post-Installation Setup

### Database Setup

The plugin will automatically create necessary database tables during activation. If you encounter database errors:

#### Manual Database Creation

```sql
-- Check if tables exist
SHOW TABLES LIKE 'wp_xelite_%';

-- If tables don't exist, run the plugin activation hook
-- This will be done automatically, but you can trigger it manually:
```

#### Verify Database Tables

The following tables should be created:

- `wp_xelite_reposts` - Stores repost data
- `wp_xelite_pattern_analysis` - Stores pattern analysis results
- `wp_xelite_pattern_performance` - Stores performance metrics
- `wp_xelite_extension_data` - Stores Chrome extension data
- `wp_xelite_extension_posts` - Stores scraped posts

### File Permissions

Ensure proper file permissions:

```bash
# Set directory permissions
find /wp-content/plugins/xelite-repost-engine -type d -exec chmod 755 {} \;

# Set file permissions
find /wp-content/plugins/xelite-repost-engine -type f -exec chmod 644 {} \;

# Make sure upload directory is writable
chmod 755 /wp-content/plugins/xelite-repost-engine/uploads
```

### Cron Job Setup

For automated scraping, set up a cron job:

```bash
# Add to crontab (run every hour)
0 * * * * wget -q -O /dev/null "https://yoursite.com/wp-cron.php?doing_wp_cron"

# Or use WordPress cron
wp cron event schedule xelite_repost_engine_scraper_cron hourly
```

## Configuration

### API Configuration

#### X (Twitter) API Setup

1. **Create Developer Account**
   - Visit [developer.twitter.com](https://developer.twitter.com)
   - Sign up for a developer account
   - Complete the application process

2. **Create App**
   - Create a new app in the developer portal
   - Note down the API keys and secrets

3. **Configure in WordPress**
   ```php
   // Add to wp-config.php
   define('XELITE_TWITTER_API_KEY', 'your_api_key');
   define('XELITE_TWITTER_API_SECRET', 'your_api_secret');
   define('XELITE_TWITTER_ACCESS_TOKEN', 'your_access_token');
   define('XELITE_TWITTER_ACCESS_SECRET', 'your_access_secret');
   ```

#### OpenAI API Setup

1. **Get API Key**
   - Sign up at [platform.openai.com](https://platform.openai.com)
   - Generate an API key

2. **Configure in WordPress**
   ```php
   // Add to wp-config.php
   define('XELITE_OPENAI_API_KEY', 'your_openai_api_key');
   ```

### Plugin Settings

#### Basic Configuration

1. **Access Settings**
   - Go to **Xelite Repost Engine** → **Settings**

2. **General Settings**
   - **Plugin Status**: Enable/disable the plugin
   - **Debug Mode**: Enable for troubleshooting
   - **Log Level**: Set logging verbosity

3. **API Settings**
   - Enter your API keys
   - Test API connections
   - Configure rate limits

#### Target Account Configuration

1. **Add Target Accounts**
   - Enter X (Twitter) handles to analyze
   - Set analysis frequency
   - Configure content preferences

2. **Account Settings**
   ```php
   // Example configuration
   $target_accounts = array(
       'elonmusk' => array(
           'frequency' => 'hourly',
           'max_tweets' => 100,
           'include_replies' => false,
           'include_retweets' => true
       )
   );
   ```

### User Configuration

#### User Profile Setup

1. **Access User Profile**
   - Go to **Users** → **Your Profile**

2. **Repost Engine Settings**
   - **Content Preferences**: Set preferred tone and style
   - **Analysis Frequency**: Choose how often to analyze
   - **Notification Settings**: Configure email alerts

#### User Meta Configuration

```php
// Example user meta configuration
update_user_meta($user_id, 'xelite_content_tone', 'professional');
update_user_meta($user_id, 'xelite_analysis_frequency', 'daily');
update_user_meta($user_id, 'xelite_notifications', true);
```

## Troubleshooting

### Common Installation Issues

#### 1. Plugin Won't Activate

**Symptoms:**
- Plugin activation fails
- Database errors appear
- White screen of death

**Solutions:**
```php
// Enable debug mode
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check error logs
tail -f /wp-content/debug.log
```

#### 2. Database Connection Issues

**Symptoms:**
- Database table creation fails
- SQL errors in logs

**Solutions:**
```sql
-- Check database permissions
SHOW GRANTS FOR 'wordpress_user'@'localhost';

-- Verify database exists
SHOW DATABASES LIKE 'wordpress_db';

-- Check table creation
SHOW TABLES LIKE 'wp_xelite_%';
```

#### 3. File Permission Issues

**Symptoms:**
- Plugin can't write to uploads
- Cache creation fails

**Solutions:**
```bash
# Fix file permissions
chown -R www-data:www-data /wp-content/plugins/xelite-repost-engine
chmod -R 755 /wp-content/plugins/xelite-repost-engine
chmod -R 777 /wp-content/plugins/xelite-repost-engine/uploads
```

#### 4. Memory Limit Issues

**Symptoms:**
- Plugin times out during activation
- Memory exhausted errors

**Solutions:**
```php
// Increase memory limit in wp-config.php
define('WP_MEMORY_LIMIT', '512M');

// Or in php.ini
memory_limit = 512M
```

### API Configuration Issues

#### 1. X (Twitter) API Issues

**Symptoms:**
- Cannot connect to X (Twitter) API
- Rate limit errors
- Authentication failures

**Solutions:**
```php
// Test API connection
$response = wp_remote_get('https://api.twitter.com/2/users/by/username/elonmusk', array(
    'headers' => array(
        'Authorization' => 'Bearer ' . XELITE_TWITTER_API_KEY
    )
));

// Check response
if (is_wp_error($response)) {
    error_log('Twitter API Error: ' . $response->get_error_message());
}
```

#### 2. OpenAI API Issues

**Symptoms:**
- Content generation fails
- API key errors
- Rate limit exceeded

**Solutions:**
```php
// Test OpenAI connection
$response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
    'headers' => array(
        'Authorization' => 'Bearer ' . XELITE_OPENAI_API_KEY,
        'Content-Type' => 'application/json'
    ),
    'body' => json_encode(array(
        'model' => 'gpt-3.5-turbo',
        'messages' => array(
            array('role' => 'user', 'content' => 'Hello')
        )
    ))
));
```

### Performance Issues

#### 1. Slow Loading

**Symptoms:**
- Dashboard loads slowly
- Timeout errors
- High server load

**Solutions:**
```php
// Optimize database queries
// Add indexes to frequently queried columns
ALTER TABLE wp_xelite_reposts ADD INDEX idx_source_handle (source_handle);
ALTER TABLE wp_xelite_reposts ADD INDEX idx_created_at (created_at);

// Enable caching
define('WP_CACHE', true);
```

#### 2. Memory Issues

**Symptoms:**
- Memory exhausted errors
- Plugin crashes

**Solutions:**
```php
// Increase memory limits
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '1024M');

// Optimize queries
// Use pagination for large datasets
// Implement lazy loading
```

### Chrome Extension Issues

#### 1. Extension Won't Install

**Symptoms:**
- Extension fails to load
- Manifest errors

**Solutions:**
```javascript
// Check manifest.json
{
  "manifest_version": 3,
  "name": "Xelite Repost Engine Scraper",
  "version": "1.0.0",
  // ... rest of manifest
}

// Verify file structure
chrome-extension/
├── manifest.json
├── background.js
├── content.js
├── popup.html
├── popup.js
└── icons/
```

#### 2. Authentication Issues

**Symptoms:**
- Extension can't authenticate with WordPress
- Sync fails

**Solutions:**
```php
// Check WordPress site URL
// Ensure HTTPS is enabled
// Verify user credentials
// Check API endpoints are accessible
```

## Next Steps

After successful installation:

1. **Configure API Keys**: Set up X (Twitter) and OpenAI APIs
2. **Add Target Accounts**: Specify accounts to analyze
3. **Run First Analysis**: Test the plugin functionality
4. **Install Chrome Extension**: Set up the browser extension
5. **Configure Notifications**: Set up email alerts
6. **Test Export Features**: Verify data export functionality

## Support

If you encounter issues during installation:

1. **Check Documentation**: Review this guide and the main README
2. **Enable Debug Mode**: Get detailed error information
3. **Check Error Logs**: Review WordPress and server error logs
4. **Contact Support**: Reach out to our support team
5. **Community Forum**: Ask questions in our community forum

For additional help, visit our [support page](https://support.xelite.com) or contact us at support@xelite.com. 