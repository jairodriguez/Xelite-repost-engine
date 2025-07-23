// Popup script for Xelite Repost Engine Scraper
// Handles UI interactions and communication with background script

class PopupManager {
  constructor() {
    this.isLoading = false;
    this.init();
  }

  init() {
    console.log('Popup initialized');
    
    // Load initial data
    this.loadStatus();
    this.loadSettings();
    
    // Set up event listeners
    this.setupEventListeners();
  }

  setupEventListeners() {
    // Scrape button
    const scrapeButton = document.getElementById('scrape-button');
    scrapeButton.addEventListener('click', () => this.startScraping());

    // Settings button
    const settingsButton = document.getElementById('settings-button');
    settingsButton.addEventListener('click', () => this.saveSettings());

    // Auto-scrape toggle
    const autoScrapeToggle = document.getElementById('auto-scrape-toggle');
    autoScrapeToggle.addEventListener('click', () => this.toggleAutoScrape());

    // Max posts input
    const maxPostsInput = document.getElementById('max-posts-input');
    maxPostsInput.addEventListener('change', () => this.validateMaxPosts());
  }

  async loadStatus() {
    try {
      const response = await this.sendMessage({ action: 'getStatus' });
      
      if (response.success) {
        this.updateStatusDisplay(response.data);
      } else {
        this.showMessage('Failed to load status: ' + response.error, 'error');
      }
    } catch (error) {
      console.error('Error loading status:', error);
      this.showMessage('Failed to load status', 'error');
    }
  }

  async loadSettings() {
    try {
      const response = await this.sendMessage({ action: 'getStatus' });
      
      if (response.success && response.data.settings) {
        this.updateSettingsDisplay(response.data.settings);
      }
    } catch (error) {
      console.error('Error loading settings:', error);
    }
  }

  updateStatusDisplay(data) {
    // Update status value
    const statusValue = document.getElementById('status-value');
    if (data.isEnabled) {
      statusValue.textContent = 'Ready';
      statusValue.className = 'status-value success';
    } else {
      statusValue.textContent = 'Disabled';
      statusValue.className = 'status-value warning';
    }

    // Update last scrape time
    const lastScrape = document.getElementById('last-scrape');
    if (data.lastScrapeTime) {
      const date = new Date(data.lastScrapeTime);
      lastScrape.textContent = date.toLocaleString();
    } else {
      lastScrape.textContent = 'Never';
    }

    // Update scrape count
    const scrapeCount = document.getElementById('scrape-count');
    scrapeCount.textContent = data.scrapeCount || 0;
  }

  updateSettingsDisplay(settings) {
    // Update auto-scrape toggle
    const autoScrapeToggle = document.getElementById('auto-scrape-toggle');
    if (settings.autoScrape) {
      autoScrapeToggle.classList.add('active');
    } else {
      autoScrapeToggle.classList.remove('active');
    }

    // Update max posts input
    const maxPostsInput = document.getElementById('max-posts-input');
    maxPostsInput.value = settings.maxPostsPerScrape || 50;
  }

  async startScraping() {
    if (this.isLoading) {
      return;
    }

    this.setLoadingState(true);
    this.showMessage('Starting scraping...', 'info');

    try {
      const response = await this.sendMessage({ action: 'startScraping' });
      
      if (response.success) {
        this.showMessage('Scraping started successfully!', 'success');
        // Reload status after a short delay
        setTimeout(() => this.loadStatus(), 2000);
      } else {
        this.showMessage('Failed to start scraping: ' + response.error, 'error');
      }
    } catch (error) {
      console.error('Error starting scraping:', error);
      this.showMessage('Failed to start scraping', 'error');
    } finally {
      this.setLoadingState(false);
    }
  }

  async saveSettings() {
    const autoScrapeToggle = document.getElementById('auto-scrape-toggle');
    const maxPostsInput = document.getElementById('max-posts-input');

    const settings = {
      autoScrape: autoScrapeToggle.classList.contains('active'),
      maxPostsPerScrape: parseInt(maxPostsInput.value) || 50
    };

    try {
      const response = await this.sendMessage({
        action: 'updateSettings',
        settings: settings
      });

      if (response.success) {
        this.showMessage('Settings saved successfully!', 'success');
      } else {
        this.showMessage('Failed to save settings: ' + response.error, 'error');
      }
    } catch (error) {
      console.error('Error saving settings:', error);
      this.showMessage('Failed to save settings', 'error');
    }
  }

  toggleAutoScrape() {
    const toggle = document.getElementById('auto-scrape-toggle');
    toggle.classList.toggle('active');
  }

  validateMaxPosts() {
    const input = document.getElementById('max-posts-input');
    let value = parseInt(input.value);
    
    if (isNaN(value) || value < 1) {
      value = 1;
    } else if (value > 100) {
      value = 100;
    }
    
    input.value = value;
  }

  setLoadingState(loading) {
    this.isLoading = loading;
    
    const scrapeButton = document.getElementById('scrape-button');
    const scrapeButtonText = document.getElementById('scrape-button-text');
    const scrapeButtonLoading = document.getElementById('scrape-button-loading');
    
    if (loading) {
      scrapeButton.disabled = true;
      scrapeButtonText.style.display = 'none';
      scrapeButtonLoading.style.display = 'inline-block';
    } else {
      scrapeButton.disabled = false;
      scrapeButtonText.style.display = 'inline';
      scrapeButtonLoading.style.display = 'none';
    }
  }

  showMessage(message, type = 'info') {
    const messageContainer = document.getElementById('message-container');
    
    // Remove existing messages
    const existingMessages = messageContainer.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageElement = document.createElement('div');
    messageElement.className = `message ${type}`;
    messageElement.textContent = message;
    
    messageContainer.appendChild(messageElement);
    
    // Auto-remove message after 5 seconds
    setTimeout(() => {
      if (messageElement.parentNode) {
        messageElement.parentNode.removeChild(messageElement);
      }
    }, 5000);
  }

  sendMessage(message) {
    return new Promise((resolve, reject) => {
      chrome.runtime.sendMessage(message, (response) => {
        if (chrome.runtime.lastError) {
          reject(new Error(chrome.runtime.lastError.message));
        } else {
          resolve(response);
        }
      });
    });
  }
}

// Initialize popup when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new PopupManager();
}); 