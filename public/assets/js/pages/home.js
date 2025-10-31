/**
 * Home Feed Page
 * Display user's feed with posts from followed walls
 */

import postsAPI from '../api/posts.js';
import { PostCard } from '../components/post-card.js';
import toast from '../components/toast.js';
import authService from '../services/auth-service.js';

const HomePage = {
    posts: [],
    loading: false,
    page: 1,
    hasMore: true,

    async render(container) {
        container.innerHTML = `
            <div class="home-page">
                <div class="page-header">
                    <h1>Home Feed</h1>
                    <p class="text-secondary">See what's happening in your network</p>
                </div>

                <div class="feed-filters">
                    <button class="filter-btn active" data-filter="all">All Posts</button>
                    <button class="filter-btn" data-filter="following">Following</button>
                    <button class="filter-btn" data-filter="popular">Popular</button>
                </div>

                <div id="feed-container" class="feed-container">
                    <!-- Post cards will be loaded here -->
                </div>

                <div id="load-more-container" class="load-more-container" style="display: none;">
                    <button id="load-more-btn" class="btn btn-secondary">Load More</button>
                </div>
            </div>
        `;

        this.setupEventListeners();
        await this.loadPosts();
    },

    setupEventListeners() {
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.posts = [];
                this.page = 1;
                this.loadPosts();
            });
        });

        // Load more button
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                this.page++;
                this.loadPosts(true);
            });
        }

        // Infinite scroll
        window.addEventListener('scroll', () => {
            if (this.loading || !this.hasMore) return;
            
            const scrollPosition = window.innerHeight + window.scrollY;
            const threshold = document.documentElement.scrollHeight - 500;
            
            if (scrollPosition >= threshold) {
                this.page++;
                this.loadPosts(true);
            }
        });
    },

    async loadPosts(append = false) {
        if (this.loading) return;
        
        this.loading = true;
        const feedContainer = document.getElementById('feed-container');
        
        if (!append) {
            feedContainer.innerHTML = `
                <div class="loading-container">
                    <div class="spinner"></div>
                    <p>Loading posts...</p>
                </div>
            `;
        }

        try {
            const user = authService.getCurrentUser();
            if (!user) return;

            // Get user's wall posts as a demo (in production, this would be feed from followed walls)
            const response = await postsAPI.getUserPosts(user.user_id, {
                page: this.page,
                limit: 10
            });

            if (response.success && response.data.posts) {
                const posts = response.data.posts;
                
                if (posts.length === 0 && !append) {
                    this.showEmptyState();
                } else {
                    if (!append) {
                        feedContainer.innerHTML = '';
                    } else {
                        // Remove loading indicator
                        const loader = feedContainer.querySelector('.loading-container');
                        if (loader) loader.remove();
                    }

                    posts.forEach(postData => {
                        const postCard = new PostCard(postData, feedContainer);
                        feedContainer.appendChild(postCard.render());
                    });

                    this.posts = append ? [...this.posts, ...posts] : posts;
                    this.hasMore = posts.length >= 10;
                    
                    // Show/hide load more button
                    const loadMoreContainer = document.getElementById('load-more-container');
                    if (loadMoreContainer) {
                        loadMoreContainer.style.display = this.hasMore ? 'block' : 'none';
                    }
                }
            } else {
                if (!append) {
                    this.showEmptyState();
                }
            }
        } catch (error) {
            console.error('Load posts error:', error);
            if (!append) {
                feedContainer.innerHTML = `
                    <div class="error-state">
                        <h2>Oops!</h2>
                        <p class="text-secondary">Failed to load posts. Please try again.</p>
                        <button class="btn btn-primary" onclick="location.reload()">Retry</button>
                    </div>
                `;
            } else {
                toast.error('Failed to load more posts');
            }
        } finally {
            this.loading = false;
        }
    },

    showEmptyState() {
        const feedContainer = document.getElementById('feed-container');
        feedContainer.innerHTML = `
            <div class="empty-state">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🧱</div>
                <h2>Welcome to Wall!</h2>
                <p class="text-secondary">Your feed is empty. Start following walls or create your first post!</p>
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="window.location.hash = '#/ai'">
                        <span class="icon">🤖</span>
                        <span>Create AI App</span>
                    </button>
                    <button class="btn btn-secondary" onclick="window.location.hash = '#/discover'">
                        <span class="icon">🔍</span>
                        <span>Discover Walls</span>
                    </button>
                </div>
            </div>
        `;
    }
};

export default HomePage;
