/**
 * Wall View Page
 * Display user's wall with posts and profile
 */

import wallsAPI from '../api/walls.js';
import postsAPI from '../api/posts.js';
import usersAPI from '../api/users.js';
import { PostCard } from '../components/post-card.js';
import toast from '../components/toast.js';
import authService from '../services/auth-service.js';

const WallPage = {
    currentWall: null,
    currentUser: null,
    posts: [],
    loading: false,

    async render(container, params) {
        const wallId = params.id || 'me';
        
        container.innerHTML = `
            <div class="wall-page">
                <div id="wall-header" class="wall-header">
                    <!-- Header will be loaded here -->
                    <div class="loading-container">
                        <div class="spinner"></div>
                    </div>
                </div>

                <div class="wall-content">
                    <div id="wall-posts" class="wall-posts">
                        <!-- Posts will be loaded here -->
                    </div>
                </div>
            </div>
        `;

        await this.loadWall(wallId);
    },

    async loadWall(wallId) {
        try {
            const response = wallId === 'me' 
                ? await wallsAPI.getMyWall()
                : await wallsAPI.getWall(wallId);

            if (response.success && response.data.wall) {
                this.currentWall = response.data.wall;
                this.currentUser = response.data.user;
                this.renderWallHeader();
                await this.loadPosts();
            }
        } catch (error) {
            console.error('Load wall error:', error);
            const headerContainer = document.getElementById('wall-header');
            if (headerContainer) {
                headerContainer.innerHTML = `
                    <div class="error-state">
                        <h2>Wall Not Found</h2>
                        <p class="text-secondary">This wall doesn't exist or you don't have permission to view it.</p>
                        <button class="btn btn-primary" onclick="window.location.hash = '/'">Go Home</button>
                    </div>
                `;
            }
        }
    },

    renderWallHeader() {
        const headerContainer = document.getElementById('wall-header');
        const currentUserId = authService.getCurrentUser()?.user_id;
        const isOwnWall = this.currentUser.user_id === currentUserId;

        headerContainer.innerHTML = `
            <div class="wall-cover" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
                ${isOwnWall ? '<button class="btn btn-secondary edit-cover-btn">Edit Cover</button>' : ''}
            </div>

            <div class="wall-profile">
                <div class="profile-avatar-container">
                    <img src="${this.currentUser.avatar_url || '/assets/images/default-avatar.svg'}" 
                         alt="${this.currentUser.display_name || this.currentUser.username}'s avatar" 
                         class="profile-avatar">
                    ${isOwnWall ? '<button class="btn-icon edit-avatar-btn" aria-label="Edit avatar">📷</button>' : ''}
                </div>

                <div class="profile-info">
                    <div class="profile-header">
                        <div class="profile-name-section">
                            <h1 class="profile-name">${this.currentUser.display_name || this.currentUser.username}</h1>
                            <span class="profile-username">@${this.currentUser.username}</span>
                        </div>
                        
                        <div class="profile-actions">
                            ${this.renderProfileActions(isOwnWall)}
                        </div>
                    </div>

                    ${this.currentUser.bio ? `<p class="profile-bio">${this.escapeHtml(this.currentUser.bio)}</p>` : ''}

                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value">${this.currentWall.post_count || 0}</span>
                            <span class="stat-label">Posts</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">${this.currentWall.follower_count || 0}</span>
                            <span class="stat-label">Followers</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">${this.currentWall.following_count || 0}</span>
                            <span class="stat-label">Following</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">${this.currentUser.friend_count || 0}</span>
                            <span class="stat-label">Friends</span>
                        </div>
                    </div>

                    ${this.renderSocialLinks()}
                </div>
            </div>

            <div class="wall-tabs">
                <button class="wall-tab active" data-tab="posts">Posts</button>
                <button class="wall-tab" data-tab="media">Media</button>
                <button class="wall-tab" data-tab="ai-apps">AI Apps</button>
                ${isOwnWall ? '<button class="wall-tab" data-tab="drafts">Drafts</button>' : ''}
            </div>
        `;

        this.attachHeaderListeners(isOwnWall);
    },

    renderProfileActions(isOwnWall) {
        if (isOwnWall) {
            return `
                <button class="btn btn-secondary" onclick="window.location.hash = '/settings'">
                    <span class="icon">⚙️</span>
                    <span>Edit Profile</span>
                </button>
            `;
        }

        return `
            <button class="btn btn-primary follow-btn" data-action="follow">
                <span class="icon">+</span>
                <span>Follow</span>
            </button>
            <button class="btn btn-secondary" data-action="message">
                <span class="icon">💬</span>
                <span>Message</span>
            </button>
            <button class="btn btn-secondary" data-action="friend">
                <span class="icon">👤</span>
                <span>Add Friend</span>
            </button>
        `;
    },

    renderSocialLinks() {
        if (!this.currentUser.social_links || this.currentUser.social_links.length === 0) {
            return '';
        }

        const links = this.currentUser.social_links.map(link => `
            <a href="${link.url}" target="_blank" rel="noopener noreferrer" class="social-link" title="${link.label}">
                <span class="social-icon">${this.getSocialIcon(link.url)}</span>
                <span class="social-label">${link.label}</span>
            </a>
        `).join('');

        return `<div class="social-links">${links}</div>`;
    },

    getSocialIcon(url) {
        if (url.includes('twitter.com') || url.includes('x.com')) return '𝕏';
        if (url.includes('github.com')) return '🐙';
        if (url.includes('linkedin.com')) return '💼';
        if (url.includes('instagram.com')) return '📷';
        if (url.includes('youtube.com')) return '▶️';
        if (url.includes('facebook.com')) return '👥';
        return '🔗';
    },

    attachHeaderListeners(isOwnWall) {
        // Follow button
        const followBtn = document.querySelector('[data-action="follow"]');
        if (followBtn) {
            followBtn.addEventListener('click', () => this.handleFollow());
        }

        // Message button
        const messageBtn = document.querySelector('[data-action="message"]');
        if (messageBtn) {
            messageBtn.addEventListener('click', () => {
                window.location.hash = `/messages/${this.currentUser.user_id}`;
            });
        }

        // Add friend button
        const friendBtn = document.querySelector('[data-action="friend"]');
        if (friendBtn) {
            friendBtn.addEventListener('click', () => this.handleAddFriend());
        }

        // Tab switching
        document.querySelectorAll('.wall-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                document.querySelectorAll('.wall-tab').forEach(t => t.classList.remove('active'));
                e.target.classList.add('active');
                this.loadPosts(e.target.dataset.tab);
            });
        });
    },

    async loadPosts(filter = 'posts') {
        const postsContainer = document.getElementById('wall-posts');
        postsContainer.innerHTML = '<div class="loading-container"><div class="spinner"></div></div>';

        try {
            const response = await postsAPI.getUserPosts(this.currentUser.user_id, {
                filter: filter,
                limit: 20
            });

            if (response.success && response.data.posts) {
                const posts = response.data.posts;
                
                if (posts.length === 0) {
                    postsContainer.innerHTML = `
                        <div class="empty-state">
                            <p class="text-secondary">No posts yet</p>
                        </div>
                    `;
                } else {
                    postsContainer.innerHTML = '';
                    posts.forEach(postData => {
                        const postCard = new PostCard(postData, postsContainer);
                        postsContainer.appendChild(postCard.render());
                    });
                }
            }
        } catch (error) {
            console.error('Load posts error:', error);
            postsContainer.innerHTML = `
                <div class="error-state">
                    <p class="text-error">Failed to load posts</p>
                </div>
            `;
        }
    },

    async handleFollow() {
        toast.info('Follow functionality coming in next phase!');
    },

    async handleAddFriend() {
        toast.info('Friend request functionality coming in next phase!');
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

export default WallPage;
