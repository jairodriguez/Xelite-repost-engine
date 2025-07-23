# Installation Guide

This guide provides detailed step-by-step instructions for installing and configuring the Xelite Repost Engine WordPress plugin and Chrome extension.

## Table of Contents

- [Prerequisites](#prerequisites)
- [WordPress Plugin Installation](#wordpress-plugin-installation)
- [Chrome Extension Installation](#chrome-extension-installation)
- [Initial Configuration](#initial-configuration)
- [Verification](#verification)
- [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements

Before installing the plugin, ensure your system meets these requirements:

#### WordPress Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.7 or higher (MariaDB 10.2+)
- **Memory Limit**: 256MB minimum (512MB recommended)
- **Upload Limit**: 64MB minimum
- **cURL**: Enabled for API communications
- **JSON**: PHP JSON extension enabled
- **SSL**: HTTPS recommended for security

#### Server Requirements
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP Extensions**: 
  - cURL
  - JSON
  - MySQLi or PDO
  - OpenSSL
  - mbstring
- **Cron Jobs**: Access to WordPress cron or system cron

#### Browser Requirements (Chrome Extension)
- **Chrome**: Version 88 or higher
- **Manifest V3**: Compatible with latest Chrome extension standards
- **Permissions**: Active tab, storage, and network access

### Pre-Installation Checklist

- [ ] WordPress site is accessible and functioning
- [ ] PHP version meets requirements
- [ ] Database has sufficient space (recommended: 100MB+)
- [ ] SSL certificate is installed (recommended)
- [ ] Backup of WordPress site is created
- [ ] Admin access to WordPress dashboard
- [ ] API keys ready (OpenAI, X/Twitter if available)

## WordPress Plugin Installation

### Method 1: WordPress Admin (Recommended)

#### Step 1: Download the Plugin
1. Go to your WordPress admin dashboard
2. Navigate to **Plugins** → **Add New**
3. Click **Upload Plugin** button
4. Choose the plugin ZIP file (`xelite-repost-engine.zip`)
5. Click **Install Now**

#### Step 2: Activate the Plugin
1. After installation, click **Activate Plugin**
2. Wait for activation to complete
3. Check for any error messages

#### Step 3: Verify Installation
1. Look for **Xelite Repost Engine** in the admin menu
2. Check that the plugin appears in **Plugins** → **Installed Plugins**
3. Verify no error messages in the admin area

### Method 2: Manual Installation

#### Step 1: Download and Extract
```bash
# Download the plugin
wget https://github.com/your-repo/xelite-repost-engine/archive/main.zip

# Extract the files
unzip main.zip

# Navigate to the plugin directory
cd xelite-repost-engine-main
```

#### Step 2: Upload to WordPress
```bash
# Copy to WordPress plugins directory
cp -r xelite-repost-engine /path/to/wordpress/wp-content/plugins/

# Set proper permissions
chmod -R 755 /path/to/wordpress/wp-content/plugins/xelite-repost-engine
chown -R www-data:www-data /path/to/wordpress/wp-content/plugins/xelite-repost-engine
```

#### Step 3: Activate via Admin
1. Go to **WordPress Admin** → **Plugins** → **Installed Plugins**
2. Find **Xelite Repost Engine**
3. Click **Activate**

### Method 3: Git Installation

#### Step 1: Clone Repository
```bash
cd /path/to/wordpress/wp-content/plugins
git clone https://github.com/your-repo/xelite-repost-engine.git
cd xelite-repost-engine
```

#### Step 2: Install Dependencies
```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Set proper permissions
chmod -R 755 .
chown -R www-data:www-data .
```

#### Step 3: Activate Plugin
1. Go to **WordPress Admin** → **Plugins** → **Installed Plugins**
2. Find **Xelite Repost Engine**
3. Click **Activate**

## Chrome Extension Installation

### Method 1: Chrome Web Store (Recommended)

#### Step 1: Install from Store
1. Open Chrome browser
2. Go to [Chrome Web Store - Xelite Repost Engine Scraper](https://chrome.google.com/webstore/detail/xelite-repost-engine-scraper)
3. Click **Add to Chrome**
4. Confirm the installation in the popup dialog

#### Step 2: Verify Installation
1. Look for the extension icon in the Chrome toolbar
2. Click the icon to open the extension popup
3. Verify the extension is working properly

### Method 2: Manual Installation (Developer Mode)

#### Step 1: Download Extension Files
```bash
# Clone the repository
git clone https://github.com/your-repo/xelite-repost-engine.git
cd xelite-repost-engine/chrome-extension
```

#### Step 2: Enable Developer Mode
1. Open Chrome and go to `chrome://extensions/`
2. Toggle **Developer mode** in the top right corner
3. Click **Load unpacked**
4. Select the `chrome-extension` folder from the repository

#### Step 3: Verify Installation
1. The extension should appear in the extensions list
2. Click the extension icon in the toolbar
3. Verify the popup opens correctly

### Method 3: From Source Code

#### Step 1: Build Extension
```bash
# Navigate to extension directory
cd xelite-repost-engine/chrome-extension

# Install dependencies (if using npm)
npm install

# Build the extension (if build process exists)
npm run build
```

#### Step 2: Load in Chrome
1. Open `chrome://extensions/`
2. Enable **Developer mode**
3. Click **Load unpacked**
4. Select the extension directory

## Initial Configuration

### WordPress Plugin Configuration

#### Step 1: Access Plugin Settings
1. Go to **WordPress Admin** → **Xelite Repost Engine** → **Settings**
2. You'll see the main configuration page

#### Step 2: Configure API Keys
1. **OpenAI API Key** (Optional but recommended):
   - Go to [OpenAI Platform](https://platform.openai.com/api-keys)
   - Create a new API key
   - Copy the key and paste it in the plugin settings
   - Click **Save Changes**

2. **X (Twitter) API Credentials** (If available):
   - Go to [Twitter Developer Portal](https://developer.twitter.com/)
   - Create a new app
   - Get your API keys and tokens
   - Enter them in the plugin settings

#### Step 3: Configure Database
The plugin will automatically create required tables:
- `wp_xelite_repost_data` - Main data storage
- `wp_xelite_patterns` - Pattern analysis data
- `wp_xelite_extension_data` - Chrome extension data
- `wp_xelite_user_meta` - User settings

#### Step 4: Set Up User Roles
1. Go to **Users** → **Roles**
2. Configure permissions for different user roles
3. Set up admin access for the plugin

### Chrome Extension Configuration

#### Step 1: Authenticate with WordPress
1. Click the extension icon in Chrome toolbar
2. Go to **Settings** tab
3. Enter your WordPress site information:
   - **Site URL**: `https://yoursite.com` (no trailing slash)
   - **Username**: Your WordPress admin username
   - **Password**: Your WordPress admin password
4. Click **Authenticate**

#### Step 2: Configure Scraping Settings
1. Go to **Settings** tab in the extension popup
2. Configure the following options:
   - **Auto Scrape**: Enable/disable automatic scraping
   - **Scrape Interval**: Set frequency (default: 5 minutes)
   - **Max Posts**: Limit posts per session (default: 50)
   - **Rate Limiting**: Delay between scrapes (default: 1 second)

#### Step 3: Test Connection
1. Click **Test Connection** to verify WordPress integration
2. Check that authentication is successful
3. Verify data can be sent to WordPress

## Verification

### WordPress Plugin Verification

#### Step 1: Check Plugin Status
1. Go to **Plugins** → **Installed Plugins**
2. Verify **Xelite Repost Engine** is active
3. Check for any error messages

#### Step 2: Test Dashboard Access
1. Go to **Xelite Repost Engine** → **Dashboard**
2. Verify the dashboard loads correctly
3. Check that all menu items are accessible

#### Step 3: Test API Endpoints
```bash
# Test the extension authentication endpoint
curl -X POST https://yoursite.com/wp-json/repost-intelligence/v1/extension-auth \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}'
```

#### Step 4: Check Database Tables
```sql
-- Check if tables were created
SHOW TABLES LIKE 'wp_xelite_%';

-- Verify table structure
DESCRIBE wp_xelite_repost_data;
DESCRIBE wp_xelite_extension_data;
```

### Chrome Extension Verification

#### Step 1: Test Basic Functionality
1. Navigate to any X (Twitter) page
2. Click the extension icon
3. Verify the popup opens and shows correct information

#### Step 2: Test Scraping
1. Go to a X (Twitter) page with posts
2. Click **Start Scraping** in the extension
3. Wait for completion notification
4. Check that data appears in WordPress dashboard

#### Step 3: Test WordPress Integration
1. Go to **Settings** in the extension
2. Click **Test Connection**
3. Verify authentication is successful
4. Check that data syncs to WordPress

#### Step 4: Check Browser Console
1. Open Chrome DevTools (F12)
2. Go to **Console** tab
3. Look for any error messages from the extension
4. Verify extension scripts are loading correctly

## Troubleshooting

### Common Installation Issues

#### Plugin Activation Errors
```
Fatal error: Uncaught Error: Class 'Xelite_Repost_Engine' not found
```
**Solutions:**
- Check PHP version (requires 7.4+)
- Verify all plugin files are uploaded correctly
- Check file permissions (755 for directories, 644 for files)
- Clear WordPress cache

#### Database Connection Issues
```
Error establishing a database connection
```
**Solutions:**
- Verify database credentials in `wp-config.php`
- Check database server is running
- Ensure database user has CREATE TABLE permissions
- Test database connection manually

#### API Key Issues
```
Error: Invalid API key
```
**Solutions:**
- Verify API keys are correct and active
- Check API key permissions and limits
- Ensure HTTPS is enabled for API calls
- Test API keys manually

### Chrome Extension Issues

#### Extension Not Loading
```
Extension could not be loaded
```
**Solutions:**
- Check Chrome version (requires 88+)
- Verify all extension files are present
- Check `manifest.json` syntax
- Clear browser cache and reload

#### Authentication Failures
```
WordPress authentication failed
```
**Solutions:**
- Verify site URL format (https://yoursite.com)
- Check username and password are correct
- Ensure WordPress site is accessible
- Check if plugin is activated and endpoints are working

#### Scraping Not Working
```
No posts found on page
```
**Solutions:**
- Ensure you're on a valid X (Twitter) page
- Wait for page to load completely
- Check if page has posts visible
- Try refreshing the page

### Performance Issues

#### Slow Loading
```
Plugin dashboard loads slowly
```
**Solutions:**
- Increase PHP memory limit to 512MB
- Enable WordPress caching
- Optimize database queries
- Check server resources

#### Extension Performance
```
Extension is slow or unresponsive
```
**Solutions:**
- Reduce scraping frequency
- Lower max posts per session
- Clear extension storage
- Check network connectivity

### Debug Information

#### Enable WordPress Debug
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('XELITE_DEBUG', true);
```

#### Check Error Logs
- **WordPress**: `/wp-content/debug.log`
- **PHP**: Check hosting provider's error logs
- **Chrome Extension**: Browser console (F12)

#### Plugin Status Check
Go to **Xelite Repost Engine** → **Settings** → **System Status** to view:
- PHP version and extensions
- WordPress version
- Database status
- API connectivity
- File permissions

## Next Steps

After successful installation and configuration:

1. **Read the User Guide**: Learn how to use the plugin effectively
2. **Configure Target Accounts**: Set up accounts to analyze
3. **Test Scraping**: Try the Chrome extension on X (Twitter)
4. **Explore Dashboard**: Familiarize yourself with the analytics
5. **Set Up Automation**: Configure cron jobs and scheduling
6. **Join Community**: Get help and share experiences

For additional support, visit our [documentation](https://docs.xelite-repost-engine.com) or [community forum](https://community.xelite-repost-engine.com). 