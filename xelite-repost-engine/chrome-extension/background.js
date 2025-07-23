// Background service worker for Xelite Repost Engine Scraper
// Handles background tasks and communication with content scripts

// Listen for extension installation
chrome.runtime.onInstalled.addListener((details) => {
  console.log('Xelite Repost Engine Scraper installed:', details.reason);
  
  // Initialize default settings
  chrome.storage.local.set({
    isEnabled: true,
    lastScrapeTime: null,
    scrapeCount: 0,
    settings: {
      autoScrape: false,
      scrapeInterval: 300000, // 5 minutes
      maxPostsPerScrape: 50,
      rateLimitDelay: 1000, // 1 second between scrapes
      maxRetries: 3
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
      'lastScrapedData'
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
        lastScrapedData: data.lastScrapedData || null
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