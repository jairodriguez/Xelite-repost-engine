// Content script for Xelite Repost Engine Scraper
// Handles DOM manipulation and data extraction from X (Twitter) pages

class XeliteScraper {
  constructor() {
    this.isScraping = false;
    this.scrapedData = [];
    this.maxPosts = 50;
    this.rateLimitDelay = 1000; // 1 second between requests
    this.lastScrapeTime = 0;
    this.mutationObserver = null;
    this.retryAttempts = 0;
    this.maxRetries = 3;
    this.scrapedPostIds = new Set(); // Track already scraped posts
    this.init();
  }

  init() {
    console.log('Xelite Scraper initialized');
    
    // Listen for messages from background script
    chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
      console.log('Content script received message:', request);
      
      switch (request.action) {
        case 'autoScrape':
          this.startScraping();
          break;
        case 'stopScraping':
          this.stopScraping();
          break;
        default:
          console.log('Unknown action:', request.action);
      }
    });

    // Listen for window messages (from background script injection)
    window.addEventListener('message', (event) => {
      if (event.data && event.data.type === 'XELITE_SCRAPE_REQUEST') {
        if (event.data.action === 'startScraping') {
          this.startScraping();
        }
      }
    });

    // Set up mutation observer for dynamic content
    this.setupMutationObserver();
  }

  setupMutationObserver() {
    // Create mutation observer to watch for new content
    this.mutationObserver = new MutationObserver((mutations) => {
      if (this.isScraping) {
        // Check if new posts were added
        const hasNewPosts = mutations.some(mutation => {
          return Array.from(mutation.addedNodes).some(node => {
            if (node.nodeType === Node.ELEMENT_NODE) {
              return this.isPostElement(node) || node.querySelector('[data-testid="tweet"]');
            }
            return false;
          });
        });

        if (hasNewPosts) {
          console.log('New posts detected, continuing scraping...');
          // Throttle the scraping to avoid overwhelming the page
          setTimeout(() => {
            this.continueScraping();
          }, 500);
        }
      }
    });

    // Start observing the document body for changes
    this.mutationObserver.observe(document.body, {
      childList: true,
      subtree: true
    });

    console.log('Mutation observer set up for dynamic content');
  }

  isPostElement(element) {
    // Check if an element is likely a post
    const postSelectors = [
      'article[data-testid="tweet"]',
      '[data-testid="cellInnerDiv"]',
      '[role="article"]'
    ];

    return postSelectors.some(selector => {
      return element.matches(selector) || element.querySelector(selector);
    });
  }

  async startScraping() {
    if (this.isScraping) {
      console.log('Scraping already in progress');
      return;
    }

    // Check rate limiting
    const now = Date.now();
    if (now - this.lastScrapeTime < this.rateLimitDelay) {
      const waitTime = this.rateLimitDelay - (now - this.lastScrapeTime);
      console.log(`Rate limiting: waiting ${waitTime}ms before scraping`);
      await new Promise(resolve => setTimeout(resolve, waitTime));
    }

    this.isScraping = true;
    this.retryAttempts = 0;
    this.scrapedPostIds.clear();
    console.log('Starting X scraping...');

    try {
      // Wait for page to be fully loaded
      await this.waitForPageLoad();
      
      // Get settings from storage
      const data = await chrome.storage.local.get('settings');
      this.maxPosts = data.settings?.maxPostsPerScrape || 50;

      // Extract posts from the current page
      const posts = await this.extractPostsWithRetry();
      
      if (posts.length > 0) {
        this.scrapedData = posts;
        
        // Send data to background script
        chrome.runtime.sendMessage({
          action: 'scrapeData',
          data: {
            posts: posts,
            timestamp: Date.now(),
            url: window.location.href,
            userAgent: navigator.userAgent,
            totalPostsFound: posts.length
          }
        }, (response) => {
          if (response && response.success) {
            console.log('Data sent successfully:', response);
            this.showNotification(`Scraping completed! Found ${posts.length} posts`, 'success');
          } else {
            console.error('Failed to send data:', response);
            this.showNotification('Failed to save data', 'error');
          }
        });
      } else {
        console.log('No posts found on this page');
        this.showNotification('No posts found on this page', 'warning');
      }

    } catch (error) {
      console.error('Error during scraping:', error);
      this.showNotification('Scraping failed: ' + error.message, 'error');
      
      // Retry logic
      if (this.retryAttempts < this.maxRetries) {
        this.retryAttempts++;
        console.log(`Retrying scraping (attempt ${this.retryAttempts}/${this.maxRetries})`);
        setTimeout(() => this.startScraping(), 2000);
        return;
      }
    } finally {
      this.isScraping = false;
      this.lastScrapeTime = Date.now();
    }
  }

  async continueScraping() {
    if (!this.isScraping) return;

    try {
      const newPosts = await this.extractNewPosts();
      if (newPosts.length > 0) {
        this.scrapedData = [...this.scrapedData, ...newPosts];
        
        // Update background script with new data
        chrome.runtime.sendMessage({
          action: 'scrapeData',
          data: {
            posts: this.scrapedData,
            timestamp: Date.now(),
            url: window.location.href,
            userAgent: navigator.userAgent,
            totalPostsFound: this.scrapedData.length,
            newPostsFound: newPosts.length
          }
        });
      }
    } catch (error) {
      console.error('Error during continued scraping:', error);
    }
  }

  stopScraping() {
    this.isScraping = false;
    console.log('Scraping stopped');
    this.showNotification('Scraping stopped', 'info');
  }

  async extractPostsWithRetry() {
    let posts = [];
    let attempts = 0;
    const maxAttempts = 3;

    while (attempts < maxAttempts && posts.length === 0) {
      attempts++;
      console.log(`Extracting posts (attempt ${attempts}/${maxAttempts})`);
      
      posts = this.extractPosts();
      
      if (posts.length === 0) {
        console.log('No posts found, waiting for content to load...');
        await new Promise(resolve => setTimeout(resolve, 2000));
      }
    }

    return posts;
  }

  async extractNewPosts() {
    const allPosts = this.extractPosts();
    const newPosts = allPosts.filter(post => {
      const postId = this.generatePostId(post);
      if (this.scrapedPostIds.has(postId)) {
        return false;
      }
      this.scrapedPostIds.add(postId);
      return true;
    });

    return newPosts;
  }

  generatePostId(post) {
    // Generate a unique ID for a post based on its content and timestamp
    const content = post.text || '';
    const timestamp = post.timestamp || '';
    const author = post.author || '';
    return btoa(`${author}-${timestamp}-${content.substring(0, 50)}`).replace(/[^a-zA-Z0-9]/g, '');
  }

  async waitForPageLoad() {
    return new Promise((resolve) => {
      if (document.readyState === 'complete') {
        resolve();
      } else {
        window.addEventListener('load', resolve);
      }
    });
  }

  extractPosts() {
    const posts = [];
    
    // Enhanced selectors for X (Twitter) posts with fallback strategies
    const selectors = [
      'article[data-testid="tweet"]',
      '[data-testid="cellInnerDiv"]',
      '[data-testid="tweetText"]',
      '.css-1rynq56',
      '[role="article"]',
      '[data-testid="tweetButtonInline"]',
      '.css-1dbjc4n[data-testid="tweet"]'
    ];

    let postElements = [];
    
    // Try different selectors to find posts
    for (const selector of selectors) {
      try {
        postElements = document.querySelectorAll(selector);
        if (postElements.length > 0) {
          console.log(`Found ${postElements.length} posts using selector: ${selector}`);
          break;
        }
      } catch (error) {
        console.warn(`Selector failed: ${selector}`, error);
        continue;
      }
    }

    // If no posts found with specific selectors, try a more general approach
    if (postElements.length === 0) {
      console.log('Trying general approach to find posts...');
      
      // Look for elements that might contain tweets
      const possiblePosts = document.querySelectorAll('[role="article"], .css-1dbjc4n, [data-testid]');
      postElements = Array.from(possiblePosts).filter(el => {
        // Filter for elements that likely contain tweet content
        return el.textContent && 
               el.textContent.length > 50 && 
               !el.textContent.includes('Follow') &&
               !el.textContent.includes('Sign up') &&
               this.hasTweetIndicators(el);
      });
    }

    console.log(`Processing ${Math.min(postElements.length, this.maxPosts)} posts`);

    // Process each post element with error handling
    for (let i = 0; i < Math.min(postElements.length, this.maxPosts); i++) {
      try {
        const element = postElements[i];
        const postData = this.extractPostData(element);
        
        if (postData && postData.text && postData.text.trim().length > 0) {
          posts.push(postData);
        }
      } catch (error) {
        console.error(`Error processing post ${i}:`, error);
        continue;
      }
    }

    return posts;
  }

  hasTweetIndicators(element) {
    // Check if element has indicators that it's a tweet
    const indicators = [
      '[data-testid="like"]',
      '[data-testid="retweet"]',
      '[data-testid="reply"]',
      'time',
      'a[href*="/status/"]',
      '[aria-label*="Like"]',
      '[aria-label*="Retweet"]'
    ];

    return indicators.some(indicator => {
      try {
        return element.querySelector(indicator) !== null;
      } catch (error) {
        return false;
      }
    });
  }

  extractPostData(element) {
    try {
      const postData = {
        text: '',
        author: '',
        username: '',
        timestamp: '',
        url: '',
        engagement: {
          likes: 0,
          retweets: 0,
          replies: 0,
          views: 0
        },
        media: [],
        hashtags: [],
        mentions: [],
        isRetweet: false,
        isReply: false,
        isQuote: false,
        postType: 'tweet'
      };

      // Extract text content with enhanced selectors
      postData.text = this.extractTextContent(element);

      // Extract author information
      const authorInfo = this.extractAuthorInfo(element);
      postData.author = authorInfo.name;
      postData.username = authorInfo.username;

      // Extract timestamp
      postData.timestamp = this.extractTimestamp(element);

      // Extract engagement metrics
      this.extractEngagementMetrics(element, postData);

      // Extract hashtags and mentions
      this.extractHashtagsAndMentions(postData);

      // Extract media
      this.extractMedia(element, postData);

      // Extract post URL
      postData.url = this.extractPostUrl(element);

      // Determine post type
      this.determinePostType(element, postData);

      return postData;

    } catch (error) {
      console.error('Error extracting post data:', error);
      return null;
    }
  }

  extractTextContent(element) {
    // Enhanced text extraction with multiple strategies
    const textSelectors = [
      '[data-testid="tweetText"]',
      '.css-1rynq56',
      '[lang]',
      '.tweet-text',
      '[data-testid="tweetText"] span',
      '.css-1rynq56 span'
    ];

    for (const selector of textSelectors) {
      try {
        const textElement = element.querySelector(selector);
        if (textElement && textElement.textContent) {
          const text = textElement.textContent.trim();
          if (text.length > 0) {
            return text;
          }
        }
      } catch (error) {
        console.warn(`Text selector failed: ${selector}`, error);
        continue;
      }
    }

    // Fallback: get all text content and clean it
    const allText = element.textContent || '';
    return this.cleanTextContent(allText);
  }

  cleanTextContent(text) {
    // Clean up text content by removing common UI elements
    return text
      .replace(/\s+/g, ' ') // Normalize whitespace
      .replace(/Follow|Sign up|Log in|Subscribe/gi, '') // Remove common UI text
      .replace(/^\s+|\s+$/g, '') // Trim whitespace
      .substring(0, 1000); // Limit length
  }

  extractAuthorInfo(element) {
    const authorInfo = {
      name: '',
      username: ''
    };

    // Try to find author name
    const nameSelectors = [
      '[data-testid="User-Name"] span',
      '[data-testid="User-Name"]',
      '.css-1rynq56 span',
      'a[href*="/status/"]',
      '[role="link"]'
    ];

    for (const selector of nameSelectors) {
      try {
        const nameElement = element.querySelector(selector);
        if (nameElement && nameElement.textContent) {
          const text = nameElement.textContent.trim();
          if (text && !text.includes('@') && text.length < 50) {
            authorInfo.name = text;
            break;
          }
        }
      } catch (error) {
        continue;
      }
    }

    // Try to find username
    const usernameSelectors = [
      '[data-testid="User-Name"] a[href*="/"]',
      'a[href*="/status/"]',
      '.css-1rynq56 a'
    ];

    for (const selector of usernameSelectors) {
      try {
        const usernameElement = element.querySelector(selector);
        if (usernameElement && usernameElement.href) {
          const match = usernameElement.href.match(/\/([^\/]+)\/?$/);
          if (match && match[1] && !match[1].includes('status')) {
            authorInfo.username = match[1];
            break;
          }
        }
      } catch (error) {
        continue;
      }
    }

    return authorInfo;
  }

  extractTimestamp(element) {
    const timeSelectors = [
      'time[datetime]',
      'time',
      '[datetime]',
      '[data-testid="tweetText"] + time',
      'a[href*="/status/"] time'
    ];

    for (const selector of timeSelectors) {
      try {
        const timeElement = element.querySelector(selector);
        if (timeElement) {
          const datetime = timeElement.getAttribute('datetime');
          if (datetime) {
            return datetime;
          }
          const text = timeElement.textContent.trim();
          if (text) {
            return text;
          }
        }
      } catch (error) {
        continue;
      }
    }

    return '';
  }

  extractEngagementMetrics(element, postData) {
    // Enhanced engagement metrics extraction
    const metricSelectors = {
      likes: [
        '[data-testid="like"]',
        '[aria-label*="Like"]',
        '[aria-label*="likes"]',
        '[data-testid="like"] span',
        'span[aria-label*="Like"]'
      ],
      retweets: [
        '[data-testid="retweet"]',
        '[aria-label*="Retweet"]',
        '[aria-label*="retweets"]',
        '[data-testid="retweet"] span',
        'span[aria-label*="Retweet"]'
      ],
      replies: [
        '[data-testid="reply"]',
        '[aria-label*="Reply"]',
        '[aria-label*="replies"]',
        '[data-testid="reply"] span',
        'span[aria-label*="Reply"]'
      ],
      views: [
        '[data-testid="view"]',
        '[aria-label*="View"]',
        '[aria-label*="views"]',
        '[data-testid="view"] span',
        'span[aria-label*="View"]'
      ]
    };

    for (const [metric, selectors] of Object.entries(metricSelectors)) {
      for (const selector of selectors) {
        try {
          const metricElement = element.querySelector(selector);
          if (metricElement) {
            const text = metricElement.textContent || metricElement.getAttribute('aria-label') || '';
            const number = this.extractNumberFromText(text);
            if (number !== null) {
              postData.engagement[metric] = number;
              break;
            }
          }
        } catch (error) {
          continue;
        }
      }
    }
  }

  extractNumberFromText(text) {
    if (!text) return null;
    
    // Enhanced number extraction
    const cleanText = text.replace(/[^\d.,KMB]/g, '');
    
    if (cleanText.includes('K')) {
      return parseInt(cleanText.replace('K', '')) * 1000;
    } else if (cleanText.includes('M')) {
      return parseInt(cleanText.replace('M', '')) * 1000000;
    } else if (cleanText.includes('B')) {
      return parseInt(cleanText.replace('B', '')) * 1000000000;
    } else {
      const number = parseInt(cleanText.replace(/[^\d]/g, ''));
      return isNaN(number) ? null : number;
    }
  }

  extractHashtagsAndMentions(postData) {
    if (!postData.text) return;

    // Extract hashtags
    const hashtagRegex = /#(\w+)/g;
    const hashtags = postData.text.match(hashtagRegex);
    if (hashtags) {
      postData.hashtags = hashtags.map(tag => tag.substring(1));
    }

    // Extract mentions
    const mentionRegex = /@(\w+)/g;
    const mentions = postData.text.match(mentionRegex);
    if (mentions) {
      postData.mentions = mentions.map(mention => mention.substring(1));
    }
  }

  extractMedia(element, postData) {
    // Enhanced media extraction
    try {
      // Look for images
      const images = element.querySelectorAll('img[src*="pbs.twimg.com"], img[alt*="Image"], img[data-testid="tweetPhoto"]');
      images.forEach(img => {
        if (img.src && !img.src.includes('profile') && !img.src.includes('avatar')) {
          postData.media.push({
            type: 'image',
            url: img.src,
            alt: img.alt || ''
          });
        }
      });

      // Look for videos
      const videos = element.querySelectorAll('video, [data-testid="videoPlayer"], [data-testid="video"]');
      videos.forEach(video => {
        if (video.src) {
          postData.media.push({
            type: 'video',
            url: video.src
          });
        }
      });
    } catch (error) {
      console.warn('Error extracting media:', error);
    }
  }

  extractPostUrl(element) {
    try {
      const linkElement = element.querySelector('a[href*="/status/"]');
      if (linkElement) {
        return new URL(linkElement.href, window.location.origin).href;
      }
    } catch (error) {
      console.warn('Error extracting post URL:', error);
    }
    return '';
  }

  determinePostType(element, postData) {
    // Determine if this is a retweet, reply, or quote
    try {
      // Check for retweet indicators
      const retweetIndicators = [
        '[data-testid="retweet"]',
        '[aria-label*="Retweeted"]',
        '.css-1rynq56:contains("Retweeted")'
      ];

      if (retweetIndicators.some(selector => element.querySelector(selector))) {
        postData.isRetweet = true;
        postData.postType = 'retweet';
      }

      // Check for reply indicators
      const replyIndicators = [
        '[data-testid="reply"]',
        '[aria-label*="Replying to"]',
        '.css-1rynq56:contains("Replying to")'
      ];

      if (replyIndicators.some(selector => element.querySelector(selector))) {
        postData.isReply = true;
        postData.postType = 'reply';
      }

      // Check for quote indicators
      const quoteIndicators = [
        '[data-testid="quote"]',
        '[aria-label*="Quote"]',
        '.css-1rynq56:contains("Quote")'
      ];

      if (quoteIndicators.some(selector => element.querySelector(selector))) {
        postData.isQuote = true;
        postData.postType = 'quote';
      }
    } catch (error) {
      console.warn('Error determining post type:', error);
    }
  }

  showNotification(message, type = 'info') {
    // Create a simple notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 12px 20px;
      border-radius: 8px;
      color: white;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      font-weight: 500;
      z-index: 10000;
      max-width: 300px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transition: all 0.3s ease;
    `;

    // Set background color based on type
    switch (type) {
      case 'success':
        notification.style.backgroundColor = '#10b981';
        break;
      case 'error':
        notification.style.backgroundColor = '#ef4444';
        break;
      case 'warning':
        notification.style.backgroundColor = '#f59e0b';
        break;
      default:
        notification.style.backgroundColor = '#3b82f6';
    }

    notification.textContent = message;
    document.body.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
  }
}

// Initialize the scraper when the content script loads
const scraper = new XeliteScraper(); 