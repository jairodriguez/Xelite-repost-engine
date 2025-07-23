// Popup script for Xelite Repost Engine Scraper
// Handles UI interactions and communicates with background script

class PopupManager {
    constructor() {
        this.isLoading = false;
        this.isScraping = false;
        this.progressInterval = null;
        this.init();
    }

    init() {
        console.log('Popup initialized');
        this.loadStatus();
        this.loadSettings();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Scraping buttons
        const scrapeButton = document.getElementById('scrape-button');
        scrapeButton.addEventListener('click', () => this.startScraping());

        const stopButton = document.getElementById('stop-button');
        stopButton.addEventListener('click', () => this.stopScraping());

        // Settings button
        const settingsButton = document.getElementById('settings-button');
        settingsButton.addEventListener('click', () => this.saveSettings());

        // WordPress authentication
        const authButton = document.getElementById('auth-button');
        authButton.addEventListener('click', () => this.authenticateWordPress());

        const syncButton = document.getElementById('sync-button');
        syncButton.addEventListener('click', () => this.syncWithWordPress());

        // Toggle switches
        const autoScrapeToggle = document.getElementById('auto-scrape-toggle');
        autoScrapeToggle.addEventListener('click', () => this.toggleAutoScrape());

        const wordpressSyncToggle = document.getElementById('wordpress-sync-toggle');
        wordpressSyncToggle.addEventListener('click', () => this.toggleWordPressSync());

        // Input validation
        const maxPostsInput = document.getElementById('max-posts-input');
        maxPostsInput.addEventListener('change', () => this.validateMaxPosts());

        const rateLimitInput = document.getElementById('rate-limit-input');
        rateLimitInput.addEventListener('change', () => this.validateRateLimit());

        // WordPress form inputs
        const siteUrlInput = document.getElementById('site-url');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        // Auto-hide auth form when authenticated
        this.updateWordPressAuthDisplay();
    }

    async loadStatus() {
        try {
            const response = await this.sendMessage({ action: 'getStatus' });
            if (response.success) {
                this.updateStatusDisplay(response.data);
                this.updateWordPressAuthDisplay(response.data.wordpressConfig);
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
        const statusValue = document.getElementById('status-value');
        if (data.isEnabled) {
            statusValue.textContent = 'Ready';
            statusValue.className = 'status-value success';
        } else {
            statusValue.textContent = 'Disabled';
            statusValue.className = 'status-value warning';
        }

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

        const scrapeCount = document.getElementById('scrape-count');
        scrapeCount.textContent = data.scrapeCount || 0;

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
        const autoScrapeToggle = document.getElementById('auto-scrape-toggle');
        if (settings.autoScrape) {
            autoScrapeToggle.classList.add('active');
        } else {
            autoScrapeToggle.classList.remove('active');
        }

        const maxPostsInput = document.getElementById('max-posts-input');
        maxPostsInput.value = settings.maxPostsPerScrape || 50;

        const rateLimitInput = document.getElementById('rate-limit-input');
        rateLimitInput.value = settings.rateLimitDelay || 1000;

        const wordpressSyncToggle = document.getElementById('wordpress-sync-toggle');
        if (settings.enableWordPressSync) {
            wordpressSyncToggle.classList.add('active');
        } else {
            wordpressSyncToggle.classList.remove('active');
        }
    }

    updateWordPressAuthDisplay(wordpressConfig = null) {
        const authStatus = document.getElementById('auth-status');
        const authIcon = document.getElementById('auth-icon');
        const authText = document.getElementById('auth-text');
        const authForm = document.getElementById('auth-form');
        const syncControls = document.getElementById('sync-controls');

        if (!wordpressConfig) {
            // Load from storage
            chrome.storage.local.get('wordpressConfig', (data) => {
                this.updateWordPressAuthDisplay(data.wordpressConfig);
            });
            return;
        }

        if (wordpressConfig.isAuthenticated) {
            authStatus.className = 'auth-status authenticated';
            authIcon.textContent = '✅';
            authText.textContent = `Connected to ${wordpressConfig.siteUrl}`;
            authForm.classList.remove('active');
            syncControls.style.display = 'block';
        } else {
            authStatus.className = 'auth-status not-authenticated';
            authIcon.textContent = '⚠️';
            authText.textContent = 'Not authenticated';
            authForm.classList.add('active');
            syncControls.style.display = 'none';
        }
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

    async authenticateWordPress() {
        const siteUrl = document.getElementById('site-url').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!siteUrl || !username || !password) {
            this.showMessage('Please fill in all fields', 'error');
            return;
        }

        this.setAuthLoadingState(true);
        this.showMessage('Authenticating with WordPress...', 'info');

        try {
            const response = await this.sendMessage({
                action: 'authenticateWordPress',
                credentials: { siteUrl, username, password }
            });

            if (response.success) {
                this.showMessage('WordPress authentication successful!', 'success');
                this.updateWordPressAuthDisplay(response.config);
                this.clearAuthForm();
            } else {
                this.showMessage('Authentication failed: ' + response.error, 'error');
            }
        } catch (error) {
            console.error('WordPress authentication error:', error);
            this.showMessage('Authentication failed', 'error');
        } finally {
            this.setAuthLoadingState(false);
        }
    }

    async syncWithWordPress() {
        this.setSyncLoadingState(true);
        this.showMessage('Syncing with WordPress...', 'info');

        try {
            const response = await this.sendMessage({ action: 'syncWithWordPress' });
            if (response.success) {
                this.showMessage(`Sync successful! ${response.postsProcessed} posts processed.`, 'success');
                this.updateSyncStatus();
            } else {
                this.showMessage('Sync failed: ' + response.error, 'error');
            }
        } catch (error) {
            console.error('WordPress sync error:', error);
            this.showMessage('Sync failed', 'error');
        } finally {
            this.setSyncLoadingState(false);
        }
    }

    async saveSettings() {
        const autoScrapeToggle = document.getElementById('auto-scrape-toggle');
        const maxPostsInput = document.getElementById('max-posts-input');
        const rateLimitInput = document.getElementById('rate-limit-input');
        const wordpressSyncToggle = document.getElementById('wordpress-sync-toggle');

        const settings = {
            autoScrape: autoScrapeToggle.classList.contains('active'),
            maxPostsPerScrape: parseInt(maxPostsInput.value) || 50,
            rateLimitDelay: parseInt(rateLimitInput.value) || 1000,
            enableWordPressSync: wordpressSyncToggle.classList.contains('active')
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

    toggleWordPressSync() {
        const toggle = document.getElementById('wordpress-sync-toggle');
        toggle.classList.toggle('active');
    }

    validateMaxPosts() {
        const input = document.getElementById('max-posts-input');
        const value = parseInt(input.value);
        if (value < 10) input.value = 10;
        if (value > 200) input.value = 200;
    }

    validateRateLimit() {
        const input = document.getElementById('rate-limit-input');
        const value = parseInt(input.value);
        if (value < 500) input.value = 500;
        if (value > 5000) input.value = 5000;
    }

    setLoadingState(loading) {
        this.isLoading = loading;
        const button = document.getElementById('scrape-button');
        const buttonText = document.getElementById('scrape-button-text');
        const buttonLoading = document.getElementById('scrape-button-loading');

        if (loading) {
            button.disabled = true;
            buttonText.style.display = 'none';
            buttonLoading.style.display = 'inline-block';
        } else {
            button.disabled = false;
            buttonText.style.display = 'inline';
            buttonLoading.style.display = 'none';
        }
    }

    setAuthLoadingState(loading) {
        const button = document.getElementById('auth-button');
        const buttonText = document.getElementById('auth-button-text');
        const buttonLoading = document.getElementById('auth-button-loading');

        if (loading) {
            button.disabled = true;
            buttonText.style.display = 'none';
            buttonLoading.style.display = 'inline-block';
        } else {
            button.disabled = false;
            buttonText.style.display = 'inline';
            buttonLoading.style.display = 'none';
        }
    }

    setSyncLoadingState(loading) {
        const button = document.getElementById('sync-button');
        const buttonText = document.getElementById('sync-button-text');
        const buttonLoading = document.getElementById('sync-button-loading');

        if (loading) {
            button.disabled = true;
            buttonText.style.display = 'none';
            buttonLoading.style.display = 'inline-block';
        } else {
            button.disabled = false;
            buttonText.style.display = 'inline';
            buttonLoading.style.display = 'none';
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
        let progress = 0;
        
        this.progressInterval = setInterval(() => {
            progress += 2;
            if (progress > 90) progress = 90; // Don't go to 100% until actually complete
            progressFill.style.width = progress + '%';
        }, 100);
    }

    stopProgressAnimation() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
        
        const progressFill = document.getElementById('progress-fill');
        progressFill.style.width = '0%';
    }

    startStatusMonitoring() {
        // Monitor status every 2 seconds while scraping
        this.statusInterval = setInterval(async () => {
            try {
                const response = await this.sendMessage({ action: 'getStatus' });
                if (response.success) {
                    this.updateStatusDisplay(response.data);
                }
            } catch (error) {
                console.error('Status monitoring error:', error);
            }
        }, 2000);
    }

    stopStatusMonitoring() {
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
            this.statusInterval = null;
        }
    }

    updateSyncStatus() {
        const syncStatus = document.getElementById('sync-status');
        const now = new Date();
        syncStatus.textContent = `Last sync: ${now.toLocaleTimeString()}`;
    }

    clearAuthForm() {
        document.getElementById('site-url').value = '';
        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
    }

    showMessage(message, type = 'info') {
        const container = document.getElementById('message-container');
        const messageElement = document.createElement('div');
        messageElement.className = `message ${type}`;
        messageElement.textContent = message;
        
        container.appendChild(messageElement);
        messageElement.style.display = 'block';
        
        // Auto-remove after 5 seconds
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