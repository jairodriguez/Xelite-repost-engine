// Injected script for Xelite Repost Engine Scraper
// This script can be injected into the page context for additional functionality

(function() {
  'use strict';

  // Prevent multiple injections
  if (window.xeliteScraperInjected) {
    return;
  }
  window.xeliteScraperInjected = true;

  console.log('Xelite Scraper injected script loaded');

  // Enhanced data extraction functions that can be called from content script
  window.XeliteScraperUtils = {
    
    // Extract all visible posts with enhanced selectors
    extractAllPosts: function() {
      const posts = [];
      
      // Multiple strategies to find posts
      const strategies = [
        // Strategy 1: Look for article elements with tweet data
        () => {
          return Array.from(document.querySelectorAll('article[data-testid="tweet"]'));
        },
        
        // Strategy 2: Look for tweet containers
        () => {
          return Array.from(document.querySelectorAll('[data-testid="cellInnerDiv"]'));
        },
        
        // Strategy 3: Look for elements with tweet text
        () => {
          const textElements = document.querySelectorAll('[data-testid="tweetText"]');
          return Array.from(textElements).map(el => el.closest('article') || el.parentElement);
        },
        
        // Strategy 4: Look for timeline items
        () => {
          return Array.from(document.querySelectorAll('[role="article"]'));
        }
      ];

      let foundPosts = [];
      
      // Try each strategy until we find posts
      for (const strategy of strategies) {
        try {
          foundPosts = strategy();
          if (foundPosts.length > 0) {
            console.log(`Found ${foundPosts.length} posts using strategy`);
            break;
          }
        } catch (error) {
          console.warn('Strategy failed:', error);
        }
      }

      // Process found posts
      foundPosts.forEach((element, index) => {
        if (element && element.textContent) {
          const postData = this.extractPostData(element);
          if (postData && postData.text) {
            posts.push(postData);
          }
        }
      });

      return posts;
    },

    // Extract detailed post data
    extractPostData: function(element) {
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
        isQuote: false
      };

      try {
        // Extract text content
        postData.text = this.extractText(element);
        
        // Extract author information
        const authorInfo = this.extractAuthorInfo(element);
        postData.author = authorInfo.name;
        postData.username = authorInfo.username;
        
        // Extract timestamp
        postData.timestamp = this.extractTimestamp(element);
        
        // Extract engagement metrics
        postData.engagement = this.extractEngagementMetrics(element);
        
        // Extract media
        postData.media = this.extractMedia(element);
        
        // Extract hashtags and mentions
        const extracted = this.extractHashtagsAndMentions(postData.text);
        postData.hashtags = extracted.hashtags;
        postData.mentions = extracted.mentions;
        
        // Determine post type
        postData.isRetweet = this.isRetweet(element);
        postData.isReply = this.isReply(element);
        postData.isQuote = this.isQuote(element);
        
        // Extract post URL
        postData.url = this.extractPostUrl(element);

      } catch (error) {
        console.error('Error extracting post data:', error);
      }

      return postData;
    },

    // Extract text content
    extractText: function(element) {
      const textSelectors = [
        '[data-testid="tweetText"]',
        '.css-1rynq56',
        '[lang]',
        '.tweet-text'
      ];

      for (const selector of textSelectors) {
        const textElement = element.querySelector(selector);
        if (textElement && textElement.textContent) {
          return textElement.textContent.trim();
        }
      }

      // Fallback: get all text content
      return element.textContent.trim();
    },

    // Extract author information
    extractAuthorInfo: function(element) {
      const authorInfo = {
        name: '',
        username: ''
      };

      // Try to find author name
      const nameSelectors = [
        '[data-testid="User-Name"] span',
        '[data-testid="User-Name"]',
        '.css-1rynq56 span',
        'a[href*="/status/"]'
      ];

      for (const selector of nameSelectors) {
        const nameElement = element.querySelector(selector);
        if (nameElement && nameElement.textContent) {
          authorInfo.name = nameElement.textContent.trim();
          break;
        }
      }

      // Try to find username
      const usernameSelectors = [
        '[data-testid="User-Name"] a[href*="/"]',
        'a[href*="/status/"]',
        '.css-1rynq56 a'
      ];

      for (const selector of usernameSelectors) {
        const usernameElement = element.querySelector(selector);
        if (usernameElement && usernameElement.href) {
          const match = usernameElement.href.match(/\/([^\/]+)\/?$/);
          if (match) {
            authorInfo.username = match[1];
            break;
          }
        }
      }

      return authorInfo;
    },

    // Extract timestamp
    extractTimestamp: function(element) {
      const timeSelectors = [
        'time[datetime]',
        'time',
        '[datetime]',
        '[data-testid="tweetText"] + time'
      ];

      for (const selector of timeSelectors) {
        const timeElement = element.querySelector(selector);
        if (timeElement) {
          return timeElement.getAttribute('datetime') || timeElement.textContent.trim();
        }
      }

      return '';
    },

    // Extract engagement metrics
    extractEngagementMetrics: function(element) {
      const metrics = {
        likes: 0,
        retweets: 0,
        replies: 0,
        views: 0
      };

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
            const number = this.parseNumber(text);
            if (number !== null) {
              metrics[metric] = number;
              break;
            }
          }
        }
      }

      return metrics;
    },

    // Parse number from text (handles K, M, B suffixes)
    parseNumber: function(text) {
      if (!text) return null;
      
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
    },

    // Extract media
    extractMedia: function(element) {
      const media = [];

      // Extract images
      const images = element.querySelectorAll('img[src*="pbs.twimg.com"], img[alt*="Image"]');
      images.forEach(img => {
        if (img.src && !img.src.includes('profile')) {
          media.push({
            type: 'image',
            url: img.src,
            alt: img.alt || ''
          });
        }
      });

      // Extract videos
      const videos = element.querySelectorAll('video, [data-testid="videoPlayer"]');
      videos.forEach(video => {
        if (video.src) {
          media.push({
            type: 'video',
            url: video.src
          });
        }
      });

      return media;
    },

    // Extract hashtags and mentions
    extractHashtagsAndMentions: function(text) {
      const result = {
        hashtags: [],
        mentions: []
      };

      if (!text) return result;

      // Extract hashtags
      const hashtagRegex = /#(\w+)/g;
      const hashtags = text.match(hashtagRegex);
      if (hashtags) {
        result.hashtags = hashtags.map(tag => tag.substring(1));
      }

      // Extract mentions
      const mentionRegex = /@(\w+)/g;
      const mentions = text.match(mentionRegex);
      if (mentions) {
        result.mentions = mentions.map(mention => mention.substring(1));
      }

      return result;
    },

    // Check if post is a retweet
    isRetweet: function(element) {
      const retweetIndicators = [
        '[data-testid="retweet"]',
        '[aria-label*="Retweeted"]',
        '.css-1rynq56:contains("Retweeted")'
      ];

      return retweetIndicators.some(selector => {
        try {
          return element.querySelector(selector) !== null;
        } catch (error) {
          return false;
        }
      });
    },

    // Check if post is a reply
    isReply: function(element) {
      const replyIndicators = [
        '[data-testid="reply"]',
        '[aria-label*="Replying to"]',
        '.css-1rynq56:contains("Replying to")'
      ];

      return replyIndicators.some(selector => {
        try {
          return element.querySelector(selector) !== null;
        } catch (error) {
          return false;
        }
      });
    },

    // Check if post is a quote
    isQuote: function(element) {
      const quoteIndicators = [
        '[data-testid="quote"]',
        '[aria-label*="Quote"]',
        '.css-1rynq56:contains("Quote")'
      ];

      return quoteIndicators.some(selector => {
        try {
          return element.querySelector(selector) !== null;
        } catch (error) {
          return false;
        }
      });
    },

    // Extract post URL
    extractPostUrl: function(element) {
      const linkElement = element.querySelector('a[href*="/status/"]');
      if (linkElement) {
        return new URL(linkElement.href, window.location.origin).href;
      }
      return '';
    },

    // Get current page information
    getPageInfo: function() {
      return {
        url: window.location.href,
        title: document.title,
        userAgent: navigator.userAgent,
        timestamp: Date.now()
      };
    }
  };

  console.log('Xelite Scraper utilities loaded');

})(); 