// Popup script for Xelite Repost Engine Scraper
// Handles UI interactions and communication with background script

class PopupManager {
  constructor() {
    this.isLoading = false;
    this.isScraping = false;
    this.progressInterval = null;
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

    // Stop button
    const stopButton = document.getElementById('stop-button');
    stopButton.addEventListener('click', () => this.stopScraping());

    // Settings button
    const settingsButton = document.getElementById('settings-button');
    settingsButton.addEventListener('click', () => this.saveSettings());

    // Auto-scrape toggle
    const autoScrapeToggle = document.getElementById('auto-scrape-toggle');
    autoScrapeToggle.addEventListener('click', () => this.toggleAutoScrape());

    // Max posts input
    const maxPostsInput = document.getElementById('max-posts-input');
    maxPostsInput.addEventListener('change', () => this.validateMaxPosts());

    // Rate limit input
    const rateLimitInput = document.getElementById('rate-limit-input');
    rateLimitInput.addEventListener('change', () => this.validateRateLimit());
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
      const timeSince = data.timeSinceLastScrape;
      
      if (timeSince && timeSince < 60000) { // Less than 1 minute
        lastScrape.textContent = 'Just now';
      } else if (timeSince && timeSince < 3600000) { // Less than 1 hour
        const minutes = Math.floor(timeSince / 60000);
        lastScrape.textContent = `${minutes}m ago`;
      } else {
        lastScrape.textContent = date.toLocaleString();
      }
    } else {
      lastScrape.textContent = 'Never';
    }

    // Update scrape count
    const scrapeCount = document.getElementById('scrape-count');
    scrapeCount.textContent = data.scrapeCount || 0;

    // Update stats if available
    if (data.lastScrapedData) {
      this.updateStatsDisplay(data.lastScrapedData);
    }
  }

  updateStatsDisplay(data) {
    const statsGrid = document.getElementById('stats-grid');
    const postsFound = document.getElementById('posts-found');
    const newPosts = document.getElementById('new-posts');

    if (data.posts && data.posts.length > 0) {
      postsFound.textContent = data.posts.length;
      newPosts.textContent = data.newPostsFound || 0;
      statsGrid.style.display = 'grid';
    } else {
      statsGrid.style.display = 'none';
    }
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

    // Update rate limit input
    const rateLimitInput = document.getElementById('rate-limit-input');
    rateLimitInput.value = settings.rateLimitDelay || 1000;
  }

  async startScraping() {
    if (this.isLoading || this.isScraping) {
      return;
    }

    this.setLoadingState(true);
    this.setScrapingState(true);
    this.showMessage('Starting scraping...', 'info');
    this.startProgressAnimation();

    try {
      const response = await this.sendMessage({ action: 'startScraping' });
      
      if (response.success) {
        this.showMessage('Scraping started successfully!', 'success');
        // Start monitoring for updates
        this.startStatusMonitoring();
      } else {
        this.showMessage('Failed to start scraping: ' + response.error, 'error');
        this.setScrapingState(false);
        this.stopProgressAnimation();
      }
    } catch (error) {
      console.error('Error starting scraping:', error);
      this.showMessage('Failed to start scraping', 'error');
      this.setScrapingState(false);
      this.stopProgressAnimation();
    } finally {
      this.setLoadingState(false);
    }
  }

  async stopScraping() {
    try {
      const response = await this.sendMessage({ action: 'stopScraping' });
      
      if (response.success) {
        this.showMessage('Scraping stopped', 'info');
        this.setScrapingState(false);
        this.stopProgressAnimation();
        this.stopStatusMonitoring();
      } else {
        this.showMessage('Failed to stop scraping: ' + response.error, 'error');
      }
    } catch (error) {
      console.error('Error stopping scraping:', error);
      this.showMessage('Failed to stop scraping', 'error');
    }
  }

  async saveSettings() {
    const autoScrapeToggle = document.getElementById('auto-scrape-toggle');
    const maxPostsInput = document.getElementById('max-posts-input');
    const rateLimitInput = document.getElementById('rate-limit-input');

    const settings = {
      autoScrape: autoScrapeToggle.classList.contains('active'),
      maxPostsPerScrape: parseInt(maxPostsInput.value) || 50,
      rateLimitDelay: parseInt(rateLimitInput.value) || 1000
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

  validateRateLimit() {
    const input = document.getElementById('rate-limit-input');
    let value = parseInt(input.value);
    
    if (isNaN(value) || value < 500) {
      value = 500;
    } else if (value > 5000) {
      value = 5000;
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

  setScrapingState(scraping) {
    this.isScraping = scraping;
    
    const scrapeButton = document.getElementById('scrape-button');
    const stopButton = document.getElementById('stop-button');
    const progressContainer = document.getElementById('progress-container');
    
    if (scraping) {
      scrapeButton.style.display = 'none';
      stopButton.style.display = 'block';
      progressContainer.style.display = 'block';
    } else {
      scrapeButton.style.display = 'block';
      stopButton.style.display = 'none';
      progressContainer.style.display = 'none';
    }
  }

  startProgressAnimation() {
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');
    let progress = 0;
    
    this.progressInterval = setInterval(() => {
      progress += Math.random() * 15;
      if (progress > 90) progress = 90; // Don't go to 100% until complete
      
      progressFill.style.width = progress + '%';
      
      if (progress < 30) {
        progressText.textContent = 'Initializing scraper...';
      } else if (progress < 60) {
        progressText.textContent = 'Extracting posts...';
      } else {
        progressText.textContent = 'Processing data...';
      }
    }, 500);
  }

  stopProgressAnimation() {
    if (this.progressInterval) {
      clearInterval(this.progressInterval);
      this.progressInterval = null;
    }
    
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');
    
    progressFill.style.width = '100%';
    progressText.textContent = 'Scraping completed!';
    
    // Reset after a delay
    setTimeout(() => {
      progressFill.style.width = '0%';
      progressText.textContent = 'Scraping in progress...';
    }, 2000);
  }

  startStatusMonitoring() {
    // Monitor status every 2 seconds while scraping
    this.statusInterval = setInterval(async () => {
      if (!this.isScraping) {
        this.stopStatusMonitoring();
        return;
      }
      
      try {
        const response = await this.sendMessage({ action: 'getStatus' });
        if (response.success && response.data.lastScrapedData) {
          this.updateStatsDisplay(response.data.lastScrapedData);
        }
      } catch (error) {
        console.error('Error monitoring status:', error);
      }
    }, 2000);
  }

  stopStatusMonitoring() {
    if (this.statusInterval) {
      clearInterval(this.statusInterval);
      this.statusInterval = null;
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