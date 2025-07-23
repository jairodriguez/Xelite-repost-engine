# Xelite Repost Engine Chrome Extension

A Chrome extension that provides a fallback scraping mechanism for the Xelite Repost Engine WordPress plugin. This extension can extract data from X (Twitter) pages when the primary API methods are unavailable.

## Features

- **Manual Scraping**: Trigger data extraction manually from any X (Twitter) page
- **Auto-scraping**: Automatically scrape data when visiting X (Twitter) pages
- **Comprehensive Data Extraction**: Extracts posts, engagement metrics, media, hashtags, and mentions
- **Settings Management**: Configure scraping behavior and limits
- **Status Tracking**: Monitor scraping history and statistics
- **Manifest V3 Compliant**: Uses the latest Chrome extension standards

## Installation

### Development Installation

1. Open Chrome and navigate to `chrome://extensions/`
2. Enable "Developer mode" in the top right corner
3. Click "Load unpacked" and select the `chrome-extension` folder
4. The extension should now appear in your extensions list

### Production Installation

1. Package the extension using Chrome's developer tools
2. Upload to the Chrome Web Store (when ready for distribution)
3. Install from the Chrome Web Store

## Usage

### Basic Usage

1. Navigate to any X (Twitter) page (twitter.com or x.com)
2. Click the Xelite Repost Engine extension icon in your browser toolbar
3. Click "Start Scraping" to begin data extraction
4. The extension will extract all visible posts and store the data

### Settings

- **Auto-scrape on page load**: Automatically scrape data when visiting X (Twitter) pages
- **Max posts per scrape**: Limit the number of posts extracted per session (1-100)

### Data Extracted

For each post, the extension extracts:

- **Text content**: The main post text
- **Author information**: Name and username
- **Timestamp**: When the post was published
- **Engagement metrics**: Likes, retweets, replies, and views
- **Media**: Images and videos
- **Hashtags**: All hashtags mentioned in the post
- **Mentions**: All user mentions in the post
- **Post type**: Whether it's a retweet, reply, or quote
- **Post URL**: Direct link to the post

## File Structure

```
chrome-extension/
├── manifest.json          # Extension configuration
├── background.js          # Service worker for background tasks
├── content.js            # Content script for DOM manipulation
├── popup.html            # Extension popup UI
├── popup.js              # Popup functionality
├── injected.js           # Page-injected utilities
├── icons/                # Extension icons (placeholder)
└── README.md             # This file
```

## Technical Details

### Permissions

- **activeTab**: Access to the currently active tab
- **storage**: Store settings and scraped data
- **scripting**: Inject scripts into pages
- **Host permissions**: Access to twitter.com and x.com

### Architecture

1. **Background Script**: Manages extension state, handles messages, and coordinates scraping
2. **Content Script**: Runs on X (Twitter) pages, extracts data from the DOM
3. **Popup**: Provides user interface for manual control and settings
4. **Injected Script**: Provides enhanced utilities for data extraction

### Data Storage

Scraped data is stored in Chrome's local storage and includes:
- Scraping statistics (count, last scrape time)
- Extension settings
- Most recently scraped data

## Development

### Testing

1. Load the extension in developer mode
2. Navigate to twitter.com or x.com
3. Open the browser console to see debug messages
4. Use the popup to test scraping functionality

### Debugging

- Check the browser console for extension messages
- Use Chrome's extension developer tools
- Monitor the background script console in `chrome://extensions/`

### Building

To prepare for distribution:

1. Create icon files in the `icons/` directory (16x16, 32x32, 48x48, 128x128)
2. Update the manifest.json version number
3. Package the extension using Chrome's developer tools

## Integration with Xelite Repost Engine

This extension is designed to work as a fallback mechanism for the main Xelite Repost Engine WordPress plugin. The scraped data can be:

1. Exported and imported into the WordPress plugin
2. Used to supplement API data when rate limits are reached
3. Provide backup data collection when the primary methods fail

## Security Considerations

- The extension only requests minimal permissions needed for scraping
- Data is stored locally and not transmitted to external servers
- No personal information is collected beyond what's publicly visible on X (Twitter)
- The extension follows Chrome's security best practices

## Troubleshooting

### Common Issues

1. **No posts found**: Try refreshing the page or scrolling to load more content
2. **Extension not working**: Check that you're on a valid X (Twitter) page
3. **Permission errors**: Ensure the extension has the necessary permissions

### Support

For issues related to this extension, check:
1. Browser console for error messages
2. Extension permissions in `chrome://extensions/`
3. Network connectivity and X (Twitter) availability

## Version History

- **v1.0.0**: Initial release with basic scraping functionality

## License

This extension is part of the Xelite Repost Engine project and follows the same licensing terms. 