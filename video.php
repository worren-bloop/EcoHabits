<?php
include __DIR__ . '/includes/env.php';
load_project_env(__DIR__ . '/.env');

$youtubeApiKey = getenv('YOUTUBE_API_KEY') ?: '';
?>
<?php include __DIR__ . '/includes/cookie_consent.php'; ?>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video | EcoHabits</title>
    <link rel="icon" type="image/png" href="assets/images/EcoHabits_logo.png">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
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

.video-section {
    margin-top: 30px;
    background-color: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}

.video-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, #1F8D49, #34A853, #1F8D49);
}

.video-section h2 {
    color: #1F8D49;
    border-bottom: 2px solid #1F8D49;
    padding-bottom: 12px;
    margin-bottom: 25px;
    font-size: 24px;
    position: relative;
}

.video-section h2::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 60px;
    height: 2px;
    background-color: #34A853;
}

.search-container {
    display: flex;
    margin-bottom: 25px;
    gap: 12px;
}

#search-input {
    flex: 1;
    padding: 12px 18px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
}

#search-input:focus {
    outline: none;
    border-color: #1F8D49;
    box-shadow: 0 0 0 3px rgba(31, 141, 73, 0.1);
}

#search-button {
    padding: 12px 24px;
    background-color: #1F8D49;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

#search-button::before {
    content: '🔍';
}

#search-button:hover {
    background-color: #177a3b;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(23, 122, 59, 0.2);
}

.video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 25px;
}

.video-card {
    border: 1px solid #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    background-color: white;
    position: relative;
}

.video-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(31, 141, 73, 0.03);
    z-index: -1;
    transition: all 0.4s ease;
}

.video-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.video-thumbnail {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    height: 0;
    overflow: hidden;
    cursor: pointer;
}

.video-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.video-card:hover .video-thumbnail img {
    transform: scale(1.05);
}

.video-thumbnail::after {
    content: '▶';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.8);
    width: 50px;
    height: 50px;
    background-color: rgba(0,0,0,0.7);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.3s ease;
}

.video-card:hover .video-thumbnail::after {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.video-info {
    padding: 18px;
}

.video-title {
    font-weight: 600;
    margin-bottom: 10px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: #2c3e50;
    transition: color 0.3s ease;
}

.video-card:hover .video-title {
    color: #1F8D49;
}

.video-channel {
    color: #666;
    font-size: 14px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
}

.video-channel::before {
    content: '📺';
    margin-right: 6px;
    font-size: 12px;
}

.video-published {
    color: #888;
    font-size: 13px;
    display: flex;
    align-items: center;
}

.video-published::before {
    content: '📅';
    margin-right: 6px;
    font-size: 11px;
}

#loading {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-size: 18px;
    background-color: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 20px;
}

#loading::after {
    content: '';
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(31, 141, 73, 0.3);
    border-radius: 50%;
    border-top-color: #1F8D49;
    animation: spin 1s ease-in-out infinite;
    margin-left: 10px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

#error-message {
    color: #d32f2f;
    padding: 20px;
    text-align: center;
    background-color: #ffebee;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #d32f2f;
}

.load-more {
    text-align: center;
    margin-top: 35px;
}

#load-more-btn {
    padding: 12px 24px;
    background-color: #1F8D49;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

#load-more-btn::after {
    content: '↓';
}

#load-more-btn:hover {
    background-color: #177a3b;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(23, 122, 59, 0.2);
}

#load-more-btn:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

#load-more-btn:disabled::after {
    animation: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    
    .video-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .video-section {
        padding: 20px;
    }
}
    </style>
</head>
<body>

    <!-- Video Section -->
    <div class="video-section">
        <h2>Climate Action Videos</h2>
        
        <div id="loading">Loading climate videos...</div>
        <div id="error-message" style="display: none;"></div>
        
        <div class="video-grid" id="video-container">
            <!-- Videos will be inserted here -->
        </div>
        
        <div class="load-more">
            <button id="load-more-btn">Load More Videos</button>
        </div>
    </div>

    <script>
    // YouTube API implementation with proper pagination

    const config = {
        apiKey: <?php echo json_encode($youtubeApiKey, JSON_UNESCAPED_SLASHES); ?>,
        maxResults: 6,
        defaultQuery: 'climate action and climate change'
    };

    const state = {
        videos: [],
        nextPageToken: null,
        isLoading: false,
        currentQuery: config.defaultQuery
    };

    // Get DOM elements
    const elements = {
        container: document.getElementById('video-container'),
        loadMoreBtn: document.getElementById('load-more-btn'),
        loading: document.getElementById('loading'),
        errorMessage: document.getElementById('error-message')
    };

    function updateLoadMoreButton() {
        elements.loadMoreBtn.disabled = state.isLoading || !state.nextPageToken;
    }

    function showError(message) {
        elements.errorMessage.textContent = message;
        elements.errorMessage.style.display = 'block';
        elements.loading.style.display = 'none';
    }

    function hideError() {
        elements.errorMessage.style.display = 'none';
    }

    function createVideoCard(video) {
        const card = document.createElement('div');
        card.className = 'video-card';
        
        card.innerHTML = `
            <div class="video-thumbnail">
                <img src="${video.snippet.thumbnails.medium.url}" alt="${video.snippet.title}">
            </div>
            <div class="video-info">
                <div class="video-title">${video.snippet.title}</div>
                <div class="video-channel">${video.snippet.channelTitle}</div>
                <div class="video-published">${new Date(video.snippet.publishedAt).toLocaleDateString()}</div>
            </div>
        `;
        
        card.addEventListener('click', () => {
            window.open(`https://www.youtube.com/watch?v=${video.id.videoId}`, '_blank');
        });
        
        return card;
    }

    async function fetchVideos(pageToken = '') {
        if (state.isLoading) return;
        
        state.isLoading = true;
        elements.loading.style.display = 'block';
        hideError();
        updateLoadMoreButton();
        
        try {
            const url = `https://www.googleapis.com/youtube/v3/search?key=${config.apiKey}&part=snippet&type=video&maxResults=${config.maxResults}&q=${encodeURIComponent(state.currentQuery)}${pageToken ? `&pageToken=${pageToken}` : ''}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.items && data.items.length > 0) {
                state.videos = [...state.videos, ...data.items];
                state.nextPageToken = data.nextPageToken || null;
                displayVideos();
            } else {
                throw new Error('No videos found.');
            }
        } catch (error) {
            console.error('Error:', error);
            showError(error.message);
        } finally {
            state.isLoading = false;
            elements.loading.style.display = 'none';
            updateLoadMoreButton();
        }
    }

    function displayVideos() {
        // Clear container only on initial load
        if (!state.nextPageToken) {
            elements.container.innerHTML = '';
        }
        
        state.videos.forEach(video => {
            // Check if video already exists in DOM
            const existing = Array.from(elements.container.children).find(
                el => el.querySelector('.video-title').textContent === video.snippet.title
            );
            
            if (!existing) {
                elements.container.appendChild(createVideoCard(video));
            }
        });
    }

    // Event listeners
    elements.loadMoreBtn.addEventListener('click', () => {
        if (state.nextPageToken && !state.isLoading) {
            fetchVideos(state.nextPageToken);
        }
    });

    // Initialize with first page
    fetchVideos();
    </script>
</body>
</html>