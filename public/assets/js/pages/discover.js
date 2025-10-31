/**
 * Discover Page
 * Explore walls, users, and trending content
 */

import toast from '../components/toast.js';
import wallsAPI from '../api/walls.js';

const DiscoverPage = {
    async render(container) {
        container.innerHTML = `
            <div class="discover-page">
                <div class="page-header">
                    <h1>🔍 Discover</h1>
                    <p class="text-secondary">Find new walls, users, and trending content</p>
                </div>

                <div class="discover-search">
                    <div class="search-box">
                        <input 
                            type="text" 
                            class="form-control search-input" 
                            placeholder="Search for walls, users, or topics..."
                            id="discover-search"
                        >
                        <button class="btn btn-primary">Search</button>
                    </div>

                    <div class="search-filters">
                        <button class="filter-btn active" data-filter="all">All</button>
                        <button class="filter-btn" data-filter="walls">Walls</button>
                        <button class="filter-btn" data-filter="users">Users</button>
                        <button class="filter-btn" data-filter="posts">Posts</button>
                        <button class="filter-btn" data-filter="topics">Topics</button>
                    </div>
                </div>

                <div class="discover-content">
                    <section class="discover-section">
                        <h2>🔥 Trending Walls</h2>
                        <div id="trending-walls" class="discover-grid">
                            <div class="loading-container">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </section>

                    <section class="discover-section">
                        <h2>⭐ Popular Users</h2>
                        <div id="popular-users" class="discover-grid">
                            <div class="loading-container">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </section>

                    <section class="discover-section">
                        <h2>📌 Suggested Topics</h2>
                        <div id="suggested-topics" class="topics-grid">
                            <div class="loading-container">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        `;

        this.setupEventListeners();
        await this.loadDiscoverContent();
    },

    setupEventListeners() {
        // Search input
        const searchInput = document.getElementById('discover-search');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.handleSearch(e.target.value);
                }
            });
        }

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.handleFilterChange(e.target.dataset.filter);
            });
        });
    },

    async loadDiscoverContent() {
        await Promise.all([
            this.loadTrendingWalls(),
            this.loadPopularUsers(),
            this.loadSuggestedTopics()
        ]);
    },

    async loadTrendingWalls() {
        const container = document.getElementById('trending-walls');
        
        setTimeout(() => {
            container.innerHTML = `
                <div class="empty-state">
                    <p class="text-secondary">No trending walls yet</p>
                    <p class="text-secondary">Check back later!</p>
                </div>
            `;
        }, 500);
    },

    async loadPopularUsers() {
        const container = document.getElementById('popular-users');
        
        setTimeout(() => {
            container.innerHTML = `
                <div class="empty-state">
                    <p class="text-secondary">No users to discover yet</p>
                </div>
            `;
        }, 500);
    },

    async loadSuggestedTopics() {
        const container = document.getElementById('suggested-topics');
        
        const topics = [
            { name: 'Technology', icon: '💻', count: 0 },
            { name: 'Art', icon: '🎨', count: 0 },
            { name: 'Music', icon: '🎵', count: 0 },
            { name: 'Gaming', icon: '🎮', count: 0 },
            { name: 'Food', icon: '🍕', count: 0 },
            { name: 'Travel', icon: '✈️', count: 0 },
            { name: 'Sports', icon: '⚽', count: 0 },
            { name: 'Science', icon: '🔬', count: 0 }
        ];

        container.innerHTML = topics.map(topic => `
            <div class="topic-card">
                <div class="topic-icon">${topic.icon}</div>
                <div class="topic-name">${topic.name}</div>
                <div class="topic-count">${topic.count} posts</div>
            </div>
        `).join('');
    },

    handleSearch(query) {
        if (!query.trim()) {
            toast.error('Please enter a search query');
            return;
        }
        toast.info(`Searching for: ${query}`);
        // TODO: Implement search
    },

    handleFilterChange(filter) {
        toast.info(`Filter changed to: ${filter}`);
        // TODO: Implement filtering
    }
};

export default DiscoverPage;
