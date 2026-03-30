<?php
include __DIR__ . '/includes/env.php';
load_project_env(__DIR__ . '/.env');

$newsApiKey = getenv('NEWS_API_KEY') ?: '';
?>
<?php include __DIR__ . '/includes/cookie_consent.php'; ?>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News | EcoHabits</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        /* Rest of the original news page styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            background-color: #f8faf8;
            background-image: 
                radial-gradient(#1F8D49 0.5px, transparent 0.5px),
                radial-gradient(#1F8D49 0.5px, #f8faf8 0.5px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            background-attachment: fixed;
        }
        
        .sdg-banner {
            background: linear-gradient(135deg, #1F8D49 0%, #2E7D32 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }

        .sdg-banner h3 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }

        .sdg-banner p {
            margin: 0;
            opacity: 0.9;
        }
        
        .news {
            margin-top: 30px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .news h2 {
            color: #1F8D49;
            border-bottom: 2px solid #1F8D49;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .news-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }

        .news-stats .stat {
            text-align: center;
        }

        .news-stats .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1F8D49;
        }

        .news-stats .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .news-article {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: #fafafa;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards;
            transition: all 0.3s ease;
        }

        .news-article:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .news-article h3 {
            margin: 0 0 15px 0;
            font-size: 20px;
            line-height: 1.4;
        }
        
        .news-article a {
            color: #1F8D49;
            text-decoration: none;
        }
        
        .news-article a:hover {
            text-decoration: underline;
        }
        
        .news-article p {
            margin: 10px 0;
            color: #555;
        }
        
        .news-article small {
            color: #888;
            font-size: 14px;
        }
        
        .news-article img {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 6px;
            margin: 15px 0;
        }

        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .source-badge {
            background-color: #1F8D49;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .sdg-tag {
            background-color: #E8F5E9;
            color: #1F8D49;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        #loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        #error-message {
            padding: 20px;
            text-align: center;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        #load-more {
            display: block;
            margin: 30px auto;
            padding: 15px 30px;
            background: linear-gradient(135deg, #1F8D49 0%, #2E7D32 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(31, 141, 73, 0.3);
        }
        
        #load-more:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(31, 141, 73, 0.4);
        }

        #load-more:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1F8D49;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .no-more-articles {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }

        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #1F8D49;
            background: white;
            color: #1F8D49;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: #1F8D49;
            color: white;
        }

        .filter-btn:hover {
            background: #1F8D49;
            color: white;
        }

        .refresh-btn {
            padding: 8px 16px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .refresh-btn:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>

    <div class="sdg-banner">
        <h3>🌍 SDG 13: Climate Action</h3>
        <p>Take urgent action to combat climate change and its impacts</p>
    </div>

    <div class="news">
        <h2>Climate Action News Feed</h2>
        
        <div class="news-stats" id="news-stats" style="display: none;">
            <div class="stat">
                <div class="stat-number" id="total-articles">0</div>
                <div class="stat-label">Total Articles</div>
            </div>
            <div class="stat">
                <div class="stat-number" id="displayed-articles">0</div>
                <div class="stat-label">Displayed</div>
            </div>
            <div class="stat">
                <div class="stat-number" id="current-page">1</div>
                <div class="stat-label">Page</div>
            </div>
        </div>

        <div class="filters">
            <button class="filter-btn active" data-filter="all">All SDG 13 News</button>
            <button class="refresh-btn" onclick="refreshNews()"> Refresh</button>
        </div>

        <div id="news-container">
            <p id="loading">Loading SDG 13 climate news...</p>
            <p id="error-message" style="display: none;"></p>
        </div>
        
        <button id="load-more" style="display: none;">Load More Climate News</button>
        <div id="no-more" class="no-more-articles" style="display: none;">
            No more articles available. Try refreshing for new content!
        </div>
    </div>

    <script>
        // Configuration
        const config = {
            articlesPerPage: 6,
            maxRetries: 3,
            timeout: 10000,
            newsApiKey: <?php echo json_encode($newsApiKey, JSON_UNESCAPED_SLASHES); ?>
        };

        // Multiple news sources for unlimited feed
        const newsSources = [
            {
                name: 'NewsAPI',
                apiKey: config.newsApiKey,
                getUrl: (page) => `https://newsapi.org/v2/everything?q="climate change" OR "global warming" OR "carbon emissions" OR "renewable energy" OR "climate policy" OR "climate adaptation" OR "SDG 13"&language=en&sortBy=publishedAt&pageSize=${config.articlesPerPage}&page=${page}&apiKey=${config.newsApiKey}`
            },
            {
                name: 'Guardian',
                getUrl: (page) => `https://content.guardianapis.com/search?q=climate%20change&page=${page}&page-size=${config.articlesPerPage}&show-fields=thumbnail,trailText&api-key=test`
            }
        ];

        // SDG 13 related keywords for filtering
        const sdg13Keywords = [
            'climate change', 'global warming', 'carbon emissions', 'greenhouse gas',
            'renewable energy', 'solar power', 'wind power', 'clean energy',
            'climate policy', 'paris agreement', 'carbon neutral', 'net zero',
            'climate adaptation', 'climate resilience', 'sea level rise',
            'extreme weather', 'carbon footprint', 'sustainability',
            'climate action', 'climate emergency', 'carbon tax',
            'green technology', 'climate finance', 'climate mitigation'
        ];

        // State management
        let state = {
            articles: [],
            displayedArticles: 0,
            currentPage: 1,
            isLoading: false,
            hasMoreArticles: true,
            currentFilter: 'all',
            totalFetched: 0
        };

        // DOM elements
        const elements = {
            loading: document.getElementById('loading'),
            error: document.getElementById('error-message'),
            container: document.getElementById('news-container'),
            loadMore: document.getElementById('load-more'),
            noMore: document.getElementById('no-more'),
            stats: document.getElementById('news-stats'),
            totalArticles: document.getElementById('total-articles'),
            displayedArticles: document.getElementById('displayed-articles'),
            currentPage: document.getElementById('current-page')
        };

        // Initialize the application
        async function init() {
            setupEventListeners();
            await loadNews();
        }

        // Main news loading function
        async function loadNews() {
            if (state.isLoading || !state.hasMoreArticles) return;
            
            state.isLoading = true;
            showLoading();
            hideError();
            
            try {
                const newArticles = await fetchNewsFromMultipleSources();
                
                if (newArticles.length > 0) {
                    // Filter for SDG 13 relevance
                    const sdgArticles = filterSDG13Articles(newArticles);
                    
                    if (sdgArticles.length > 0) {
                        state.articles = [...state.articles, ...sdgArticles];
                        state.totalFetched += sdgArticles.length;
                        displayNewArticles(sdgArticles);
                        updateStats();
                    }
                    
                    state.currentPage++;
                    
                    // If we got fewer articles than requested, we might be reaching the end
                    if (newArticles.length < config.articlesPerPage) {
                        state.hasMoreArticles = false;
                        showNoMore();
                    }
                } else {
                    state.hasMoreArticles = false;
                    showNoMore();
                }
                
            } catch (error) {
                console.error('News loading failed:', error);
                showError('Failed to load news. Please try again.');
            } finally {
                state.isLoading = false;
                hideLoading();
                updateLoadMoreButton();
            }
        }

        // Fetch news from multiple sources
        async function fetchNewsFromMultipleSources() {
            let allArticles = [];
            
            for (const source of newsSources) {
                try {
                    const articles = await fetchFromSource(source);
                    allArticles = [...allArticles, ...articles];
                } catch (error) {
                    console.error(`Failed to fetch from ${source.name}:`, error);
                }
            }
            
            // Remove duplicates and sort by date
            const uniqueArticles = removeDuplicates(allArticles);
            return uniqueArticles.sort((a, b) => new Date(b.publishedAt) - new Date(a.publishedAt));
        }

        // Fetch from a single source
        async function fetchFromSource(source) {
            const url = source.getUrl(state.currentPage);
            
            try {
                const response = await fetchWithTimeout(url, config.timeout);
                
                if (!response.ok) {
                    throw new Error(`HTTP error: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (source.name === 'NewsAPI') {
                    return data.articles || [];
                } else if (source.name === 'Guardian') {
                    return data.response?.results?.map(article => ({
                        title: article.webTitle,
                        description: article.fields?.trailText || '',
                        url: article.webUrl,
                        urlToImage: article.fields?.thumbnail || '',
                        publishedAt: article.webPublicationDate,
                        source: { name: 'The Guardian' }
                    })) || [];
                }
                
                return [];
                
            } catch (error) {
                console.error(`Error fetching from ${source.name}:`, error);
                return [];
            }
        }

        // Filter articles for SDG 13 relevance
        function filterSDG13Articles(articles) {
            return articles.filter(article => {
                const text = `${article.title} ${article.description}`.toLowerCase();
                return sdg13Keywords.some(keyword => text.includes(keyword.toLowerCase()));
            });
        }

        // Remove duplicate articles
        function removeDuplicates(articles) {
            const seen = new Set();
            return articles.filter(article => {
                const key = article.title + article.url;
                if (seen.has(key)) return false;
                seen.add(key);
                return true;
            });
        }

        // Display new articles
        function displayNewArticles(articles) {
            articles.forEach((article, index) => {
                const articleElement = createArticleElement(article);
                articleElement.style.animationDelay = `${index * 0.1}s`;
                elements.container.appendChild(articleElement);
                state.displayedArticles++;
            });
        }

        // Create article element
        function createArticleElement(article) {
            const articleDiv = document.createElement('div');
            articleDiv.className = 'news-article';
            
            const publishedAt = new Date(article.publishedAt);
            const formattedDate = publishedAt.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Determine SDG 13 relevance category
            const category = getSDGCategory(article);
            
            articleDiv.innerHTML = `
                <div class="article-meta">
                    <span class="source-badge">${article.source.name}</span>
                    <span class="sdg-tag">${category}</span>
                </div>
                <h3><a href="${article.url}" target="_blank" rel="noopener noreferrer">${article.title}</a></h3>
                <small>Published: ${formattedDate}</small>
                ${article.urlToImage ? `<img src="${article.urlToImage}" alt="${article.title}" onerror="this.style.display='none'">` : ''}
                <p>${article.description || 'No description available'}</p>
            `;
            
            return articleDiv;
        }

        // Determine SDG category
        function getSDGCategory(article) {
            const text = `${article.title} ${article.description}`.toLowerCase();
            
            if (text.includes('renewable') || text.includes('solar') || text.includes('wind')) {
                return 'Renewable Energy';
            } else if (text.includes('policy') || text.includes('agreement') || text.includes('government')) {
                return 'Climate Policy';
            } else if (text.includes('adaptation') || text.includes('resilience') || text.includes('sea level')) {
                return 'Adaptation';
            } else {
                return 'Climate Action';
            }
        }

        // Update statistics
        function updateStats() {
            elements.totalArticles.textContent = state.totalFetched;
            elements.displayedArticles.textContent = state.displayedArticles;
            elements.currentPage.textContent = state.currentPage;
            elements.stats.style.display = 'flex';
        }

        // Update load more button
        function updateLoadMoreButton() {
            if (state.hasMoreArticles && !state.isLoading) {
                elements.loadMore.style.display = 'block';
                elements.loadMore.disabled = false;
                elements.loadMore.textContent = 'Load More Climate News';
            } else {
                elements.loadMore.style.display = 'none';
            }
        }

        // Refresh news
        function refreshNews() {
            // Reset state
            state = {
                articles: [],
                displayedArticles: 0,
                currentPage: 1,
                isLoading: false,
                hasMoreArticles: true,
                currentFilter: 'all',
                totalFetched: 0
            };
            
            // Clear container
            elements.container.innerHTML = '';
            elements.stats.style.display = 'none';
            elements.noMore.style.display = 'none';
            
            // Load fresh news
            loadNews();
        }

        // Helper functions
        function fetchWithTimeout(url, timeout) {
            return Promise.race([
                fetch(url),
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Request timeout')), timeout)
                )
            ]);
        }

        function showLoading() {
            if (state.displayedArticles === 0) {
                elements.loading.style.display = 'block';
                elements.loading.innerHTML = '<span class="loading-spinner"></span>Loading SDG 13 climate news...';
            } else {
                elements.loadMore.innerHTML = '<span class="loading-spinner"></span>Loading more...';
                elements.loadMore.disabled = true;
            }
        }

        function hideLoading() {
            elements.loading.style.display = 'none';
        }

        function showError(message) {
            elements.error.style.color = '#d32f2f';
            elements.error.style.backgroundColor = '#ffebee';
            elements.error.textContent = message;
            elements.error.style.display = 'block';
        }

        function hideError() {
            elements.error.style.display = 'none';
        }

        function showNoMore() {
            elements.noMore.style.display = 'block';
            elements.loadMore.style.display = 'none';
        }

        function setupEventListeners() {
            elements.loadMore.addEventListener('click', loadNews);
            
            // Filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    // Update active filter
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    e.target.classList.add('active');
                    
                    // Apply filter (for future implementation)
                    state.currentFilter = e.target.dataset.filter;
                });
            });
            
            // Infinite scroll
            window.addEventListener('scroll', () => {
                if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 1000 && 
                    !state.isLoading && state.hasMoreArticles) {
                    loadNews();
                }
            });
        }

        // Start the application
        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>