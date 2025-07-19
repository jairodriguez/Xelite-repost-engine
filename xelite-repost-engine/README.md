# Xelite Repost Engine

A WordPress plugin designed to help digital creators improve their chances of being reposted on X (formerly Twitter). The plugin analyzes repost patterns and uses AI to generate personalized, on-brand content for the user.

## Features

- **Repost Pattern Analysis**: Analyzes what content gets reposted by target accounts
- **AI Content Generation**: Creates personalized content based on user context and patterns
- **WooCommerce Integration**: Controls feature access through subscription tiers
- **User Dashboard**: WordPress admin interface for managing repost insights
- **X API Integration**: Fetches and stores repost data from specified accounts

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce (recommended for subscription management)

## Installation

1. Upload the `xelite-repost-engine` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings in the admin area

## Configuration

1. Go to **Settings > Xelite Repost Engine** in your WordPress admin
2. Enter your X (Twitter) API credentials
3. Add target accounts to monitor for repost patterns
4. Configure OpenAI API settings for content generation

## Usage

1. Users complete their personal context fields
2. Visit the Repost Engine Dashboard to view patterns
3. Generate AI-assisted content based on insights
4. Schedule or post content directly to X (optional)

## Development

This plugin follows WordPress coding standards and best practices:

- Object-oriented architecture
- Proper security measures
- Internationalization support
- WooCommerce integration
- REST API endpoints

## File Structure

```
xelite-repost-engine/
├── xelite-repost-engine.php      # Main plugin file
├── includes/                     # Core functionality classes
├── admin/                        # Admin interface classes
├── public/                       # Public-facing functionality
├── assets/                       # CSS, JS, and images
└── languages/                    # Translation files
```

## License

GPL v2 or later

## Support

For support and documentation, visit [https://xelite.com/repost-engine](https://xelite.com/repost-engine) 