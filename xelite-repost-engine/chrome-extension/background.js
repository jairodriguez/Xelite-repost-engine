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
      maxPostsPerScrape: 50
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

// Handle get status request
async function handleGetStatus(sendResponse) {
  try {
    const data = await chrome.storage.local.get([
      'isEnabled', 
      'lastScrapeTime', 
      'scrapeCount', 
      'settings'
    ]);
    
    sendResponse({
      success: true,
      data: {
        isEnabled: data.isEnabled || false,
        lastScrapeTime: data.lastScrapeTime,
        scrapeCount: data.scrapeCount || 0,
        settings: data.settings || {}
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
    
    await chrome.storage.local.set({ 
      scrapeCount: newCount,
      lastScrapeTime: Date.now()
    });
    
    // Store scraped data temporarily
    await chrome.storage.local.set({ 
      lastScrapedData: data,
      lastScrapeTime: Date.now()
    });
    
    console.log('Scraped data stored:', data);
    sendResponse({ success: true, message: 'Data scraped successfully', count: newCount });
    
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
      const data = await chrome.storage.local.get('settings');
      if (data.settings && data.settings.autoScrape) {
        // Wait a bit for the page to fully load
        setTimeout(() => {
          chrome.tabs.sendMessage(tabId, { action: 'autoScrape' });
        }, 2000);
      }
    }
  }
}); 