/**
 * Profile Page
 * Display and edit user profile
 */

import usersAPI from '../api/users.js';
import postsAPI from '../api/posts.js';
import { PostCard } from '../components/post-card.js';
import toast from '../components/toast.js';
import authService from '../services/auth-service.js';

const ProfilePage = {
    currentUser: null,
    posts: [],
    loading: false,

    async render(container, params) {
        const userId = params.id || authService.getCurrentUser()?.user_id;
        
        container.innerHTML = `
            <div class="profile-page">
                <div id="profile-header" class="profile-header">
                    <div class="loading-container">
                        <div class="spinner"></div>
                    </div>
                </div>

                <div class="profile-content">
                    <div id="profile-posts" class="profile-posts">
                        <!-- Posts will be loaded here -->
                    </div>
                </div>
            </div>
        `;

        await this.loadProfile(userId);
    },

    async loadProfile(userId) {
        try {
            const response = await usersAPI.getUser(userId);

            if (response.success && response.data.user) {
                this.currentUser = response.data.user;
                this.renderProfileHeader();
                await this.loadPosts();
            }
        } catch (error) {
            console.error('Load profile error:', error);
            const headerContainer = document.getElementById('profile-header');
            if (headerContainer) {
                headerContainer.innerHTML = `
                    <div class="error-state">
                        <h2>Profile Not Found</h2>
                        <p class="text-secondary">This profile doesn't exist or you don't have permission to view it.</p>
                        <button class="btn btn-primary" onclick="window.location.hash = '/'">Go Home</button>
                    </div>
                `;
            }
        }
    },

    renderProfileHeader() {
        const headerContainer = document.getElementById('profile-header');
        const currentUserId = authService.getCurrentUser()?.user_id;
        const isOwnProfile = this.currentUser.user_id === currentUserId;

        headerContainer.innerHTML = `
            <div class="profile-cover" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
            </div>

            <div class="profile-info-section">
                <div class="profile-avatar-container">
                    <img src="${this.currentUser.avatar_url || '/assets/images/default-avatar.svg'}" 
                         alt="${this.currentUser.display_name || this.currentUser.username}'s avatar" 
                         class="profile-avatar">
                </div>

                <div class="profile-details">
                    <div class="profile-name-section">
                        <h1 class="profile-name">${this.currentUser.display_name || this.currentUser.username}</h1>
                        <span class="profile-username">@${this.currentUser.username}</span>
                    </div>

                    ${this.currentUser.bio ? `<p class="profile-bio">${this.escapeHtml(this.currentUser.bio)}</p>` : ''}

                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value">${this.currentUser.post_count || 0}</span>
                            <span class="stat-label">Posts</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">${this.currentUser.follower_count || 0}</span>
                            <span class="stat-label">Followers</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">${this.currentUser.following_count || 0}</span>
                            <span class="stat-label">Following</span>
                        </div>
                    </div>

                    <div class="profile-actions">
                        ${isOwnProfile 
                            ? '<button class="btn btn-secondary" onclick="window.location.hash = \'/settings\'">Edit Profile</button>' 
                            : '<button class="btn btn-primary">Follow</button>'}
                    </div>
                </div>
            </div>
        `;
    },

    async loadPosts() {
        const postsContainer = document.getElementById('profile-posts');
        postsContainer.innerHTML = '<div class="loading-container"><div class="spinner"></div></div>';

        try {
            const response = await postsAPI.getUserPosts(this.currentUser.user_id, {
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

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

export default ProfilePage;
