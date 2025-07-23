// Background service worker for Xelite Repost Engine Scraper
// Handles background tasks and communication with content scripts and WordPress API

// WordPress API configuration
const WORDPRESS_CONFIG = {
    apiEndpoint: null,
    extensionToken: null,
    isAuthenticated: false,
    retryAttempts: 3,
    retryDelay: 2000,
};

// Listen for extension installation
chrome.runtime.onInstalled.addListener((details) => {
    console.log('Xelite Repost Engine Scraper installed:', details.reason);
    
    // Initialize default settings
    chrome.storage.local.set({
        isEnabled: true,
        lastScrapeTime: null,
        scrapeCount: 0,
        wordpressConfig: {
            siteUrl: '',
            username: '',
            isAuthenticated: false,
            extensionToken: null,
            apiEndpoint: null,
        },
        settings: {
            autoScrape: false,
            scrapeInterval: 300000, // 5 minutes
            maxPostsPerScrape: 50,
            rateLimitDelay: 1000, // 1 second between scrapes
            maxRetries: 3,
            enableWordPressSync: true,
            syncInterval: 60000, // 1 minute
        }
    });
});

// Listen for messages from popup and content scripts
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    console.log('Background received message:', request);
  
    switch (request.action) {
        case 'startScraping':
            handleStartScraping(sendResponse);
            return true; // Keep message channel open for async response
            
        case 'stopScraping':
            handleStopScraping(sendResponse);
            return true;
            
        case 'getStatus':
            handleGetStatus(sendResponse);
            return true;
            
        case 'updateSettings':
            handleUpdateSettings(request.settings, sendResponse);
            return true;
            
        case 'scrapeData':
            handleScrapeData(request.data, sendResponse);
            return true;
            
        case 'authenticateWordPress':
            handleWordPressAuthentication(request.credentials, sendResponse);
            return true;
            
        case 'checkFallbackStatus':
            handleCheckFallbackStatus(sendResponse);
            return true;
            
        case 'syncWithWordPress':
            handleSyncWithWordPress(sendResponse);
            return true;
            
        default:
            sendResponse({ success: false, error: 'Unknown action' });
    }
});

// Handle start scraping request
async function handleStartScraping(sendResponse) {
    try {
        // Get current active tab
        const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
        
        if (!tab) {
            sendResponse({ success: false, error: 'No active tab found' });
            return;
        }
        
        // Check if we're on Twitter/X
        if (!tab.url.includes('twitter.com') && !tab.url.includes('x.com')) {
            sendResponse({ success: false, error: 'Not on Twitter/X. Please navigate to twitter.com or x.com' });
            return;
        }
        
        // Check rate limiting
        const data = await chrome.storage.local.get(['lastScrapeTime', 'settings']);
        const rateLimitDelay = data.settings?.rateLimitDelay || 1000;
        
        if (data.lastScrapeTime && (Date.now() - data.lastScrapeTime) < rateLimitDelay) {
            const waitTime = rateLimitDelay - (Date.now() - data.lastScrapeTime);
            sendResponse({ 
                success: false, 
                error: `Rate limited. Please wait ${Math.ceil(waitTime / 1000)} seconds before scraping again.` 
            });
            return;
        }
        
        // Inject content script to start scraping
        await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            function: () => {
                // This will be executed in the content script context
                window.postMessage({ 
                    type: 'XELITE_SCRAPE_REQUEST',
                    action: 'startScraping'
                }, '*');
            }
        });
        
        // Update last scrape time
        await chrome.storage.local.set({ 
            lastScrapeTime: Date.now() 
        });
        
        sendResponse({ success: true, message: 'Scraping started' });
        
    } catch (error) {
        console.error('Error starting scraping:', error);
        sendResponse({ success: false, error: error.message });
    }
}

// Handle stop scraping request
async function handleStopScraping(sendResponse) {
    try {
        // Get current active tab
        const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
        
        if (!tab) {
            sendResponse({ success: false, error: 'No active tab found' });
            return;
        }
        
        // Send stop message to content script
        await chrome.tabs.sendMessage(tab.id, { action: 'stopScraping' });
        
        sendResponse({ success: true, message: 'Scraping stopped' });
        
    } catch (error) {
        console.error('Error stopping scraping:', error);
        sendResponse({ success: false, error: error.message });
    }
}

// Handle get status request
async function handleGetStatus(sendResponse) {
    try {
        const data = await chrome.storage.local.get([
            'isEnabled', 
            'lastScrapeTime', 
            'scrapeCount', 
            'settings',
            'lastScrapedData',
            'wordpressConfig'
        ]);
        
        // Calculate time since last scrape
        let timeSinceLastScrape = null;
        if (data.lastScrapeTime) {
            timeSinceLastScrape = Date.now() - data.lastScrapeTime;
        }
        
        sendResponse({
            success: true,
            data: {
                isEnabled: data.isEnabled || false,
                lastScrapeTime: data.lastScrapeTime,
                timeSinceLastScrape: timeSinceLastScrape,
                scrapeCount: data.scrapeCount || 0,
                settings: data.settings || {},
                lastScrapedData: data.lastScrapedData || null,
                wordpressConfig: data.wordpressConfig || {}
            }
        });
        
    } catch (error) {
        console.error('Error getting status:', error);
        sendResponse({ success: false, error: error.message });
    }
}

// Handle update settings request
async function handleUpdateSettings(settings, sendResponse) {
    try {
        const currentData = await chrome.storage.local.get('settings');
        const updatedSettings = { ...currentData.settings, ...settings };
        
        await chrome.storage.local.set({ settings: updatedSettings });
        
        sendResponse({ success: true, message: 'Settings updated' });
        
    } catch (error) {
        console.error('Error updating settings:', error);
        sendResponse({ success: false, error: error.message });
    }
}

// Handle scrape data from content script
async function handleScrapeData(data, sendResponse) {
    try {
        // Increment scrape count
        const currentData = await chrome.storage.local.get('scrapeCount');
        const newCount = (currentData.scrapeCount || 0) + 1;
        
        // Prepare enhanced data storage
        const enhancedData = {
            ...data,
            scrapeId: Date.now(),
            extensionVersion: chrome.runtime.getManifest().version,
            processingTime: Date.now() - (data.timestamp || Date.now())
        };
        
        await chrome.storage.local.set({ 
            scrapeCount: newCount,
            lastScrapeTime: Date.now(),
            lastScrapedData: enhancedData
        });
        
        console.log('Scraped data stored:', enhancedData);
        
        // Send data to WordPress if authenticated
        const wordpressConfig = await chrome.storage.local.get('wordpressConfig');
        if (wordpressConfig.wordpressConfig?.isAuthenticated && wordpressConfig.wordpressConfig?.enableWordPressSync) {
            await sendDataToWordPress(enhancedData);
        }
        
        // Send success response with enhanced information
        sendResponse({ 
            success: true, 
            message: 'Data scraped successfully', 
            count: newCount,
            totalPosts: data.posts?.length || 0,
            newPosts: data.newPostsFound || 0
        });
        
    } catch (error) {
        console.error('Error handling scraped data:', error);
        sendResponse({ success: false, error: error.message });
    }
}

// Handle WordPress authentication
async function handleWordPressAuthentication(credentials, sendResponse) {
    try {
        const { siteUrl, username, password } = credentials;
        
        if (!siteUrl || !username || !password) {
            sendResponse({ success: false, error: 'Missing required credentials' });
            return;
        }
        
        // Normalize site URL
        const normalizedUrl = siteUrl.replace(/\/$/, '');
        const authEndpoint = `${normalizedUrl}/wp-json/repost-intelligence/v1/extension-auth`;
        
        const response = await fetch(authEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username,
                password: password
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Store WordPress configuration
            const wordpressConfig = {
                siteUrl: normalizedUrl,
                username: username,
                isAuthenticated: true,
                extensionToken: result.extension_token,
                apiEndpoint: result.api_endpoint,
                userId: result.user_id
            };
            
            await chrome.storage.local.set({ wordpressConfig });
            
            sendResponse({ 
                success: true, 
                message: 'WordPress authentication successful',
                config: wordpressConfig
            });
        } else {
            sendResponse({ success: false, error: result.error || 'Authentication failed' });
        }
        
    } catch (error) {
        console.error('WordPress authentication error:', error);
        sendResponse({ success: false, error: 'Authentication failed: ' + error.message });
    }
}

// Handle check fallback status
async function handleCheckFallbackStatus(sendResponse) {
    try {
        const wordpressConfig = await chrome.storage.local.get('wordpressConfig');
        
        if (!wordpressConfig.wordpressConfig?.isAuthenticated) {
            sendResponse({ 
                success: false, 
                error: 'WordPress not authenticated',
                shouldUseFallback: false
            });
            return;
        }
        
        const { siteUrl, extensionToken } = wordpressConfig.wordpressConfig;
        const fallbackEndpoint = `${siteUrl}/wp-json/repost-intelligence/v1/fallback-status`;
        
        const response = await fetch(fallbackEndpoint, {
            method: 'GET',
            headers: {
                'X-Extension-Token': extensionToken,
                'Content-Type': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            sendResponse({ 
                success: true, 
                shouldUseFallback: result.should_use_fallback,
                reason: result.reason,
                apiLimits: result.api_limits
            });
        } else {
            sendResponse({ 
                success: false, 
                error: result.error,
                shouldUseFallback: false
            });
        }
        
    } catch (error) {
        console.error('Fallback status check error:', error);
        sendResponse({ 
            success: false, 
            error: 'Failed to check fallback status',
            shouldUseFallback: false
        });
    }
}

// Handle sync with WordPress
async function handleSyncWithWordPress(sendResponse) {
    try {
        const data = await chrome.storage.local.get(['lastScrapedData', 'wordpressConfig']);
        
        if (!data.lastScrapedData) {
            sendResponse({ success: false, error: 'No data to sync' });
            return;
        }
        
        if (!data.wordpressConfig?.isAuthenticated) {
            sendResponse({ success: false, error: 'WordPress not authenticated' });
            return;
        }
        
        const result = await sendDataToWordPress(data.lastScrapedData);
        
        if (result.success) {
            sendResponse({ 
                success: true, 
                message: 'Data synced successfully',
                postsProcessed: result.posts_processed
            });
        } else {
            sendResponse({ success: false, error: result.error });
        }
        
    } catch (error) {
        console.error('WordPress sync error:', error);
        sendResponse({ success: false, error: 'Sync failed: ' + error.message });
    }
}

// Send data to WordPress API
async function sendDataToWordPress(data) {
    try {
        const wordpressConfig = await chrome.storage.local.get('wordpressConfig');
        
        if (!wordpressConfig.wordpressConfig?.isAuthenticated) {
            return { success: false, error: 'WordPress not authenticated' };
        }
        
        const { siteUrl, extensionToken } = wordpressConfig.wordpressConfig;
        const apiEndpoint = `${siteUrl}/wp-json/repost-intelligence/v1/extension-data`;
        
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Extension-Token': extensionToken,
            },
            body: JSON.stringify({
                extension_token: extensionToken,
                data: {
                    posts: data.posts || [],
                    timestamp: data.timestamp,
                    url: data.url,
                    userAgent: data.userAgent,
                    totalPostsFound: data.totalPostsFound,
                    newPostsFound: data.newPostsFound
                },
                timestamp: data.timestamp,
                url: data.url
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Data sent to WordPress successfully:', result);
            return result;
        } else {
            console.error('WordPress API error:', result);
            return { success: false, error: result.error };
        }
        
    } catch (error) {
        console.error('Error sending data to WordPress:', error);
        return { success: false, error: error.message };
    }
}

// Handle tab updates to check if we should auto-scrape
chrome.tabs.onUpdated.addListener(async (tabId, changeInfo, tab) => {
    if (changeInfo.status === 'complete' && tab.url) {
        // Check if we're on Twitter/X and auto-scrape is enabled
        if ((tab.url.includes('twitter.com') || tab.url.includes('x.com'))) {
            const data = await chrome.storage.local.get(['settings', 'lastScrapeTime']);
            
            if (data.settings && data.settings.autoScrape) {
                // Check rate limiting for auto-scrape
                const rateLimitDelay = data.settings.rateLimitDelay || 1000;
                const canScrape = !data.lastScrapeTime || (Date.now() - data.lastScrapeTime) >= rateLimitDelay;
                
                if (canScrape) {
                    // Wait a bit for the page to fully load
                    setTimeout(() => {
                        chrome.tabs.sendMessage(tabId, { action: 'autoScrape' });
                    }, 2000);
                }
            }
        }
    }
});

// Handle tab activation to check for ongoing scraping
chrome.tabs.onActivated.addListener(async (activeInfo) => {
    try {
        const tab = await chrome.tabs.get(activeInfo.tabId);
        
        if (tab.url && (tab.url.includes('twitter.com') || tab.url.includes('x.com'))) {
            // Check if there's ongoing scraping data
            const data = await chrome.storage.local.get('lastScrapedData');
            if (data.lastScrapedData && data.lastScrapedData.url === tab.url) {
                console.log('Found previous scraping data for this tab');
            }
        }
    } catch (error) {
        console.error('Error handling tab activation:', error);
    }
});

// Handle extension startup
chrome.runtime.onStartup.addListener(() => {
    console.log('Xelite Repost Engine Scraper started');
    
    // Reset any ongoing scraping state
    chrome.storage.local.set({
        isScraping: false,
        lastScrapeTime: null
    });
});

// Periodic sync with WordPress
chrome.alarms.create('wordpressSync', { periodInMinutes: 1 });

chrome.alarms.onAlarm.addListener(async (alarm) => {
    if (alarm.name === 'wordpressSync') {
        try {
            const data = await chrome.storage.local.get(['settings', 'wordpressConfig', 'lastScrapedData']);
            
            if (data.settings?.enableWordPressSync && 
                data.wordpressConfig?.isAuthenticated && 
                data.lastScrapedData) {
                
                await sendDataToWordPress(data.lastScrapedData);
            }
        } catch (error) {
            console.error('Periodic WordPress sync error:', error);
        }
    }
}); 