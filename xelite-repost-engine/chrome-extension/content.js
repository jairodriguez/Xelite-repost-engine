// Content script for Xelite Repost Engine Scraper
// Handles DOM manipulation and data extraction from X (Twitter) pages

class XeliteScraper {
  constructor() {
    this.isScraping = false;
    this.scrapedData = [];
    this.maxPosts = 50;
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
  }

  async startScraping() {
    if (this.isScraping) {
      console.log('Scraping already in progress');
      return;
    }

    this.isScraping = true;
    console.log('Starting X scraping...');

    try {
      // Wait for page to be fully loaded
      await this.waitForPageLoad();
      
      // Get settings from storage
      const data = await chrome.storage.local.get('settings');
      this.maxPosts = data.settings?.maxPostsPerScrape || 50;

      // Extract posts from the current page
      const posts = this.extractPosts();
      
      if (posts.length > 0) {
        this.scrapedData = posts;
        
        // Send data to background script
        chrome.runtime.sendMessage({
          action: 'scrapeData',
          data: {
            posts: posts,
            timestamp: Date.now(),
            url: window.location.href,
            userAgent: navigator.userAgent
          }
        }, (response) => {
          if (response && response.success) {
            console.log('Data sent successfully:', response);
            this.showNotification('Scraping completed!', 'success');
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
    } finally {
      this.isScraping = false;
    }
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
    
    // Common selectors for X (Twitter) posts
    const selectors = [
      'article[data-testid="tweet"]',
      '[data-testid="cellInnerDiv"]',
      '[data-testid="tweetText"]',
      '.css-1rynq56'
    ];

    let postElements = [];
    
    // Try different selectors to find posts
    for (const selector of selectors) {
      postElements = document.querySelectorAll(selector);
      if (postElements.length > 0) {
        console.log(`Found ${postElements.length} posts using selector: ${selector}`);
        break;
      }
    }

    // If no posts found with specific selectors, try a more general approach
    if (postElements.length === 0) {
      // Look for elements that might contain tweets
      const possiblePosts = document.querySelectorAll('[role="article"], .css-1dbjc4n');
      postElements = Array.from(possiblePosts).filter(el => {
        // Filter for elements that likely contain tweet content
        return el.textContent && el.textContent.length > 50;
      });
    }

    console.log(`Processing ${Math.min(postElements.length, this.maxPosts)} posts`);

    // Process each post element
    for (let i = 0; i < Math.min(postElements.length, this.maxPosts); i++) {
      const element = postElements[i];
      const postData = this.extractPostData(element);
      
      if (postData && postData.text) {
        posts.push(postData);
      }
    }

    return posts;
  }

  extractPostData(element) {
    try {
      const postData = {
        text: '',
        author: '',
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
        mentions: []
      };

      // Extract text content
      const textSelectors = [
        '[data-testid="tweetText"]',
        '.css-1rynq56',
        '[lang]'
      ];

      for (const selector of textSelectors) {
        const textElement = element.querySelector(selector);
        if (textElement && textElement.textContent) {
          postData.text = textElement.textContent.trim();
          break;
        }
      }

      // If no specific text element found, get all text content
      if (!postData.text) {
        postData.text = element.textContent.trim();
      }

      // Extract author information
      const authorSelectors = [
        '[data-testid="User-Name"]',
        '[role="link"]',
        'a[href*="/status/"]'
      ];

      for (const selector of authorSelectors) {
        const authorElement = element.querySelector(selector);
        if (authorElement && authorElement.textContent) {
          postData.author = authorElement.textContent.trim();
          break;
        }
      }

      // Extract timestamp
      const timeSelectors = [
        'time',
        '[datetime]',
        '[data-testid="tweetText"] + time'
      ];

      for (const selector of timeSelectors) {
        const timeElement = element.querySelector(selector);
        if (timeElement) {
          postData.timestamp = timeElement.getAttribute('datetime') || timeElement.textContent.trim();
          break;
        }
      }

      // Extract engagement metrics
      this.extractEngagementMetrics(element, postData);

      // Extract hashtags and mentions
      this.extractHashtagsAndMentions(postData);

      // Extract media
      this.extractMedia(element, postData);

      // Extract post URL
      const linkElement = element.querySelector('a[href*="/status/"]');
      if (linkElement) {
        postData.url = new URL(linkElement.href, window.location.origin).href;
      }

      return postData;

    } catch (error) {
      console.error('Error extracting post data:', error);
      return null;
    }
  }

  extractEngagementMetrics(element, postData) {
    // Common selectors for engagement metrics
    const metricSelectors = {
      likes: [
        '[data-testid="like"]',
        '[aria-label*="Like"]',
        '[aria-label*="likes"]'
      ],
      retweets: [
        '[data-testid="retweet"]',
        '[aria-label*="Retweet"]',
        '[aria-label*="retweets"]'
      ],
      replies: [
        '[data-testid="reply"]',
        '[aria-label*="Reply"]',
        '[aria-label*="replies"]'
      ],
      views: [
        '[data-testid="view"]',
        '[aria-label*="View"]',
        '[aria-label*="views"]'
      ]
    };

    for (const [metric, selectors] of Object.entries(metricSelectors)) {
      for (const selector of selectors) {
        const metricElement = element.querySelector(selector);
        if (metricElement) {
          const text = metricElement.textContent || metricElement.getAttribute('aria-label') || '';
          const number = this.extractNumberFromText(text);
          if (number !== null) {
            postData.engagement[metric] = number;
            break;
          }
        }
      }
    }
  }

  extractNumberFromText(text) {
    if (!text) return null;
    
    // Remove common text and extract numbers
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
    // Look for images
    const images = element.querySelectorAll('img[src*="pbs.twimg.com"], img[alt*="Image"]');
    images.forEach(img => {
      if (img.src && !img.src.includes('profile')) {
        postData.media.push({
          type: 'image',
          url: img.src,
          alt: img.alt || ''
        });
      }
    });

    // Look for videos
    const videos = element.querySelectorAll('video, [data-testid="videoPlayer"]');
    videos.forEach(video => {
      if (video.src) {
        postData.media.push({
          type: 'video',
          url: video.src
        });
      }
    });
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