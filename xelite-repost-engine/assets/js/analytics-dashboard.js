/**
 * Analytics Dashboard JavaScript
 * 
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Analytics Dashboard namespace
    var XeliteAnalytics = {
        
        // Chart instances
        charts: {},
        
        // Current filters
        filters: {
            dateRange: '30',
            startDate: '',
            endDate: '',
            contentType: '',
            engagementRange: ''
        },
        
        // Initialize the analytics dashboard
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.initUserPreferences();
            this.loadInitialData();
        },
        
        // Bind event listeners
        bindEvents: function() {
            var self = this;
            
            // Tab navigation
            $('.xelite-analytics-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();
                var targetTab = $(this).data('tab');
                self.switchTab(targetTab);
            });
            
            // Contextual help toggle
            $('.xelite-help-toggle').on('click', function(e) {
                e.preventDefault();
                self.toggleHelp();
            });
            
            // Date range filter
            $('#date_range').on('change', function() {
                var value = $(this).val();
                if (value === 'custom') {
                    $('.custom-date-range').show();
                } else {
                    $('.custom-date-range').hide();
                    self.filters.dateRange = value;
                    self.filters.startDate = '';
                    self.filters.endDate = '';
                    self.refreshData();
                }
            });
            
            // Custom date range inputs
            $('#start_date, #end_date').on('change', function() {
                self.filters.startDate = $('#start_date').val();
                self.filters.endDate = $('#end_date').val();
            });
            
            // Content type filter
            $('#content_type_filter').on('change', function() {
                self.filters.contentType = $(this).val();
                self.refreshData();
            });
            
            // Engagement range filter
            $('#engagement_filter').on('change', function() {
                self.filters.engagementRange = $(this).val();
                self.refreshData();
            });
            
            // Apply filters button
            $('#apply_filters').on('click', function() {
                self.refreshData();
            });
            
            // Reset filters button
            $('#reset_filters').on('click', function() {
                self.resetFilters();
            });
            
            // Export buttons
            $('#export_csv').on('click', function() {
                self.exportData('csv');
            });
            
            $('#export_json').on('click', function() {
                self.exportData('json');
            });
            
            $('#export_pdf').on('click', function() {
                self.exportData('pdf');
            });
            
            // Real-time refresh (every 5 minutes)
            setInterval(function() {
                self.refreshData();
            }, 300000);
        },
        
        // Switch between analytics tabs
        switchTab: function(tabName) {
            // Update tab navigation
            $('.xelite-analytics-tabs .nav-tab').removeClass('nav-tab-active');
            $('.xelite-analytics-tabs .nav-tab[data-tab="' + tabName + '"]').addClass('nav-tab-active');
            
            // Update tab content
            $('.xelite-tab-panel').removeClass('active');
            $('#' + tabName + '-tab').addClass('active');
            
            // Store current tab in user preferences
            this.saveUserPreference('current_tab', tabName);
            
            // Load tab-specific data if needed
            this.loadTabData(tabName);
        },
        
        // Toggle contextual help visibility
        toggleHelp: function() {
            var helpContent = $('.xelite-analytics-help-content');
            var toggleButton = $('.xelite-help-toggle');
            
            if (helpContent.is(':visible')) {
                helpContent.slideUp(300);
                toggleButton.find('.dashicons').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                toggleButton.find('span').text('Show Help');
                this.saveUserPreference('help_visible', 'false');
            } else {
                helpContent.slideDown(300);
                toggleButton.find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                toggleButton.find('span').text('Hide Help');
                this.saveUserPreference('help_visible', 'true');
            }
        },
        
        // Load tab-specific data
        loadTabData: function(tabName) {
            switch(tabName) {
                case 'content-performance':
                    this.loadContentPerformanceData();
                    break;
                case 'repost-patterns':
                    this.loadRepostPatternsData();
                    break;
                case 'predictions':
                    this.loadPredictionsData();
                    break;
                default:
                    // Overview tab data is loaded by default
                    break;
            }
        },
        
        // Load content performance data
        loadContentPerformanceData: function() {
            // This will be implemented when we add content performance features
            console.log('Loading content performance data...');
        },
        
        // Load repost patterns data
        loadRepostPatternsData: function() {
            // This will be implemented when we add repost patterns features
            console.log('Loading repost patterns data...');
        },
        
        // Load predictions data
        loadPredictionsData: function() {
            // This will be implemented when we add predictions features
            console.log('Loading predictions data...');
        },
        
        // Save user preference
        saveUserPreference: function(key, value) {
            // Save to localStorage for client-side persistence
            localStorage.setItem('xelite_analytics_' + key, value);
            
            // Optionally save to server via AJAX
            // This could be implemented later for cross-device sync
        },
        
        // Load user preference
        loadUserPreference: function(key, defaultValue) {
            return localStorage.getItem('xelite_analytics_' + key) || defaultValue;
        },
        
        // Initialize user preferences
        initUserPreferences: function() {
            // Restore last active tab
            var lastTab = this.loadUserPreference('current_tab', 'overview');
            if (lastTab !== 'overview') {
                this.switchTab(lastTab);
            }
            
            // Restore help visibility
            var helpVisible = this.loadUserPreference('help_visible', 'false');
            if (helpVisible === 'true') {
                this.toggleHelp();
            }
        },
        
        // Initialize Chart.js charts
        initCharts: function() {
            // Engagement Trends Chart
            this.initEngagementTrendsChart();
            
            // Content Type Performance Chart
            this.initContentTypeChart();
            
            // Posting Time Analysis Chart
            this.initPostingTimeChart();
            
            // Hashtag Performance Chart
            this.initHashtagChart();
        },
        
        // Initialize Engagement Trends Chart
        initEngagementTrendsChart: function() {
            var ctx = document.getElementById('engagement_trends_chart');
            if (!ctx) return;
            
            this.charts.engagementTrends = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Engagement Rate',
                        data: [],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Repost Rate',
                        data: [],
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        },
        
        // Initialize Content Type Performance Chart
        initContentTypeChart: function() {
            var ctx = document.getElementById('content_type_chart');
            if (!ctx) return;
            
            this.charts.contentType = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#0073aa',
                            '#00a32a',
                            '#dba617',
                            '#dc3232',
                            '#826eb4',
                            '#f56e28'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    return label + ': ' + value + '%';
                                }
                            }
                        }
                    }
                }
            });
        },
        
        // Initialize Posting Time Analysis Chart
        initPostingTimeChart: function() {
            var ctx = document.getElementById('posting_time_chart');
            if (!ctx) return;
            
            this.charts.postingTime = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['12 AM', '3 AM', '6 AM', '9 AM', '12 PM', '3 PM', '6 PM', '9 PM'],
                    datasets: [{
                        label: 'Average Engagement',
                        data: [],
                        backgroundColor: 'rgba(0, 115, 170, 0.8)',
                        borderColor: '#0073aa',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Avg. Engagement: ' + context.parsed.y + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        },
        
        // Initialize Hashtag Performance Chart
        initHashtagChart: function() {
            var ctx = document.getElementById('hashtag_chart');
            if (!ctx) return;
            
            this.charts.hashtag = new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Engagement Rate',
                        data: [],
                        backgroundColor: 'rgba(0, 115, 170, 0.8)',
                        borderColor: '#0073aa',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Engagement: ' + context.parsed.x + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        },
        
        // Load initial data
        loadInitialData: function() {
            this.refreshData();
        },
        
        // Refresh data based on current filters
        refreshData: function() {
            var self = this;
            
            // Show loading state
            this.showLoading();
            
            // Prepare filter data
            var filterData = {
                action: 'xelite_get_analytics_data',
                nonce: xeliteAnalyticsNonce,
                date_range: this.filters.dateRange,
                start_date: this.filters.startDate,
                end_date: this.filters.endDate,
                content_type: this.filters.contentType,
                engagement_range: this.filters.engagementRange
            };
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: filterData,
                success: function(response) {
                    if (response.success) {
                        self.updateDashboard(response.data);
                    } else {
                        self.showError(response.data.message || 'Failed to load analytics data');
                    }
                },
                error: function() {
                    self.showError('Network error occurred while loading data');
                },
                complete: function() {
                    self.hideLoading();
                }
            });
        },
        
        // Update dashboard with new data
        updateDashboard: function(data) {
            // Update metrics
            this.updateMetrics(data.metrics);
            
            // Update charts
            this.updateCharts(data.charts);
            
            // Update content list
            this.updateContentList(data.top_content);
            
            // Update insights
            this.updateInsights(data.insights);
        },
        
        // Update metrics overview
        updateMetrics: function(metrics) {
            $('#total_reposts').text(this.formatNumber(metrics.total_reposts));
            $('#avg_engagement').text(this.formatNumber(metrics.avg_engagement_rate, 2) + '%');
            $('#best_time').text(metrics.best_posting_time);
            $('#top_content_type').text(metrics.top_content_type);
            
            // Update change indicators
            this.updateMetricChange('total_reposts', metrics.reposts_change);
            this.updateMetricChange('avg_engagement', metrics.engagement_change);
        },
        
        // Update metric change indicator
        updateMetricChange: function(metricId, change) {
            var element = $('#' + metricId).siblings('.xelite-metric-change');
            var isPositive = change >= 0;
            
            element.removeClass('positive negative')
                   .addClass(isPositive ? 'positive' : 'negative')
                   .text((isPositive ? '+' : '') + this.formatNumber(change, 1) + '%');
        },
        
        // Update charts with new data
        updateCharts: function(chartData) {
            // Update Engagement Trends Chart
            if (this.charts.engagementTrends && chartData.engagement_trends) {
                this.charts.engagementTrends.data.labels = chartData.engagement_trends.labels;
                this.charts.engagementTrends.data.datasets[0].data = chartData.engagement_trends.engagement;
                this.charts.engagementTrends.data.datasets[1].data = chartData.engagement_trends.reposts;
                this.charts.engagementTrends.update();
            }
            
            // Update Content Type Chart
            if (this.charts.contentType && chartData.content_types) {
                this.charts.contentType.data.labels = chartData.content_types.labels;
                this.charts.contentType.data.datasets[0].data = chartData.content_types.data;
                this.charts.contentType.update();
            }
            
            // Update Posting Time Chart
            if (this.charts.postingTime && chartData.posting_times) {
                this.charts.postingTime.data.datasets[0].data = chartData.posting_times.data;
                this.charts.postingTime.update();
            }
            
            // Update Hashtag Chart
            if (this.charts.hashtag && chartData.hashtags) {
                this.charts.hashtag.data.labels = chartData.hashtags.labels;
                this.charts.hashtag.data.datasets[0].data = chartData.hashtags.data;
                this.charts.hashtag.update();
            }
        },
        
        // Update content list
        updateContentList: function(content) {
            var container = $('#top_content_list');
            container.empty();
            
            if (content && content.length > 0) {
                content.forEach(function(item) {
                    var contentHtml = self.renderContentItem(item);
                    container.append(contentHtml);
                });
            } else {
                container.html('<p class="xelite-no-data">No content data available for the selected period.</p>');
            }
        },
        
        // Render content item HTML
        renderContentItem: function(item) {
            return `
                <div class="xelite-content-item">
                    <div class="xelite-content-meta">
                        <span class="xelite-content-type">${item.content_type}</span>
                        <span class="xelite-content-date">${item.posted_date}</span>
                    </div>
                    <div class="xelite-content-text">
                        ${this.escapeHtml(item.content)}
                    </div>
                    <div class="xelite-content-stats">
                        <span class="xelite-stat">
                            <i class="dashicons dashicons-heart"></i>
                            ${this.formatNumber(item.likes)}
                        </span>
                        <span class="xelite-stat">
                            <i class="dashicons dashicons-update"></i>
                            ${this.formatNumber(item.retweets)}
                        </span>
                        <span class="xelite-stat">
                            <i class="dashicons dashicons-admin-comments"></i>
                            ${this.formatNumber(item.replies)}
                        </span>
                        <span class="xelite-stat engagement-rate">
                            ${this.formatNumber(item.engagement_rate, 2)}% engagement
                        </span>
                    </div>
                </div>
            `;
        },
        
        // Update insights panel
        updateInsights: function(insights) {
            var container = $('#insights_content');
            container.empty();
            
            if (insights && insights.length > 0) {
                insights.forEach(function(insight) {
                    var insightHtml = self.renderInsightItem(insight);
                    container.append(insightHtml);
                });
            } else {
                container.html('<p class="xelite-no-data">No insights available. Continue posting to generate personalized recommendations.</p>');
            }
        },
        
        // Render insight item HTML
        renderInsightItem: function(insight) {
            var recommendationHtml = '';
            if (insight.recommendation) {
                recommendationHtml = `
                    <div class="xelite-insight-recommendation">
                        <strong>Recommendation:</strong>
                        ${this.escapeHtml(insight.recommendation)}
                    </div>
                `;
            }
            
            return `
                <div class="xelite-insight-item">
                    <div class="xelite-insight-icon">
                        <i class="dashicons ${insight.icon}"></i>
                    </div>
                    <div class="xelite-insight-text">
                        <h4>${this.escapeHtml(insight.title)}</h4>
                        <p>${this.escapeHtml(insight.description)}</p>
                        ${recommendationHtml}
                    </div>
                </div>
            `;
        },
        
        // Reset filters to default
        resetFilters: function() {
            $('#date_range').val('30');
            $('#content_type_filter').val('');
            $('#engagement_filter').val('');
            $('.custom-date-range').hide();
            
            this.filters = {
                dateRange: '30',
                startDate: '',
                endDate: '',
                contentType: '',
                engagementRange: ''
            };
            
            this.refreshData();
        },
        
        // Export data in specified format
        exportData: function(format) {
            var self = this;
            
            var exportData = {
                action: 'xelite_export_analytics',
                nonce: xeliteAnalyticsNonce,
                format: format,
                date_range: this.filters.dateRange,
                start_date: this.filters.startDate,
                end_date: this.filters.endDate,
                content_type: this.filters.contentType,
                engagement_range: this.filters.engagementRange
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: exportData,
                success: function(response) {
                    if (response.success) {
                        if (format === 'pdf') {
                            // Open PDF in new window
                            window.open(response.data.download_url, '_blank');
                        } else {
                            // Download file
                            self.downloadFile(response.data.download_url, 'xelite-analytics-' + format + '.txt');
                        }
                        self.showMessage('Export completed successfully!', 'success');
                    } else {
                        self.showError(response.data.message || 'Export failed');
                    }
                },
                error: function() {
                    self.showError('Export failed due to network error');
                }
            });
        },
        
        // Download file helper
        downloadFile: function(url, filename) {
            var link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        // Show loading state
        showLoading: function() {
            $('.xelite-analytics-dashboard').addClass('loading');
            $('.xelite-chart-wrapper').each(function() {
                $(this).append('<div class="chart-loading">Loading...</div>');
            });
        },
        
        // Hide loading state
        hideLoading: function() {
            $('.xelite-analytics-dashboard').removeClass('loading');
            $('.chart-loading').remove();
        },
        
        // Show error message
        showError: function(message) {
            this.showMessage(message, 'error');
        },
        
        // Show success message
        showSuccess: function(message) {
            this.showMessage(message, 'success');
        },
        
        // Show message
        showMessage: function(message, type) {
            var messageHtml = `
                <div class="xelite-message ${type}">
                    <span class="dashicons dashicons-${type === 'success' ? 'yes' : 'warning'}"></span>
                    ${this.escapeHtml(message)}
                </div>
            `;
            
            $('.xelite-analytics-dashboard').prepend(messageHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $('.xelite-message').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        // Format number with commas
        formatNumber: function(num, decimals) {
            if (decimals !== undefined) {
                return parseFloat(num).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
        
        // Escape HTML to prevent XSS
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Check if we're on the analytics dashboard page
        if ($('.xelite-analytics-dashboard').length > 0) {
            XeliteAnalytics.init();
        }
    });
    
    // Make XeliteAnalytics available globally
    window.XeliteAnalytics = XeliteAnalytics;
    
})(jQuery); 