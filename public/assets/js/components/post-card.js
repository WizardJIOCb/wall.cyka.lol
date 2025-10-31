/**
 * Post Card Component
 * Reusable post display component
 */

import socialAPI from '../api/social.js';
import toast from './toast.js';
import { CommentsSection } from './comments.js';

export class PostCard {
    constructor(postData, container) {
        this.post = postData;
        this.container = container;
        this.element = null;
        this.commentsLoaded = false;
    }

    /**
     * Render post card
     */
    render() {
        this.element = document.createElement('article');
        this.element.className = 'post-card';
        this.element.setAttribute('data-post-id', this.post.post_id);

        this.element.innerHTML = `
            <div class="post-header">
                <div class="post-author">
                    <a href="#/wall/${this.post.user_id}" class="post-author-link">
                        <img src="${this.post.author_avatar || '/assets/images/default-avatar.svg'}" 
                             alt="${this.post.author_name}'s avatar" 
                             class="avatar avatar-md">
                    </a>
                    <div class="post-author-info">
                        <a href="#/wall/${this.post.user_id}" class="post-author-name">
                            ${this.post.author_name || this.post.author_username}
                        </a>
                        <span class="post-author-username">@${this.post.author_username}</span>
                    </div>
                </div>
                <div class="post-meta">
                    <span class="post-timestamp" title="${new Date(this.post.created_at).toLocaleString()}">
                        ${this.formatTimestamp(this.post.created_at)}
                    </span>
                    ${this.renderOptionsMenu()}
                </div>
            </div>

            <div class="post-content">
                ${this.renderContent()}
            </div>

            ${this.renderMedia()}

            <div class="post-footer">
                <button class="post-action reaction-btn" data-action="react" data-type="like">
                    <span class="icon">${this.post.user_reacted === 'like' ? '❤️' : '🤍'}</span>
                    <span class="count">${this.post.reaction_counts?.like || 0}</span>
                </button>
                
                <button class="post-action" data-action="comment">
                    <span class="icon">💬</span>
                    <span class="count">${this.post.comment_count || 0}</span>
                </button>
                
                <button class="post-action" data-action="share">
                    <span class="icon">🔄</span>
                    <span class="count">${this.post.repost_count || 0}</span>
                </button>
                
                <button class="post-action" data-action="bookmark">
                    <span class="icon">🔖</span>
                </button>
            </div>

            <div class="post-comments" style="display: none;">
                <div class="comments-container">
                    <!-- Comments will be loaded here -->
                </div>
            </div>
        `;

        this.attachEventListeners();
        return this.element;
    }

    /**
     * Render post content
     */
    renderContent() {
        let content = this.escapeHtml(this.post.content || '');
        
        // Convert URLs to links
        content = this.linkifyUrls(content);
        
        // Convert newlines to <br>
        content = content.replace(/\n/g, '<br>');
        
        return `<div class="post-text">${content}</div>`;
    }

    /**
     * Render media attachments
     */
    renderMedia() {
        if (!this.post.media || this.post.media.length === 0) {
            return '';
        }

        const mediaItems = this.post.media.map(media => {
            if (media.media_type === 'image') {
                return `
                    <div class="media-item media-image">
                        <img src="${media.media_url}" alt="${media.alt_text || 'Post image'}" loading="lazy">
                    </div>
                `;
            } else if (media.media_type === 'video') {
                return `
                    <div class="media-item media-video">
                        <video controls poster="${media.thumbnail_url || ''}">
                            <source src="${media.media_url}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                `;
            }
            return '';
        }).join('');

        return `
            <div class="post-media ${this.post.media.length > 1 ? 'media-grid' : ''}">
                ${mediaItems}
            </div>
        `;
    }

    /**
     * Render options menu
     */
    renderOptionsMenu() {
        return `
            <div class="post-options">
                <button class="btn-icon" aria-label="Post options" data-action="options">
                    <span class="icon">⋮</span>
                </button>
            </div>
        `;
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Reaction button
        const reactionBtn = this.element.querySelector('[data-action="react"]');
        if (reactionBtn) {
            reactionBtn.addEventListener('click', () => this.handleReaction());
        }

        // Comment button
        const commentBtn = this.element.querySelector('[data-action="comment"]');
        if (commentBtn) {
            commentBtn.addEventListener('click', () => this.toggleComments());
        }

        // Share button
        const shareBtn = this.element.querySelector('[data-action="share"]');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => this.handleShare());
        }

        // Bookmark button
        const bookmarkBtn = this.element.querySelector('[data-action="bookmark"]');
        if (bookmarkBtn) {
            bookmarkBtn.addEventListener('click', () => this.handleBookmark());
        }
    }

    /**
     * Handle reaction (like/unlike)
     */
    async handleReaction() {
        try {
            const reactionBtn = this.element.querySelector('[data-action="react"]');
            const icon = reactionBtn.querySelector('.icon');
            const count = reactionBtn.querySelector('.count');
            
            if (this.post.user_reacted === 'like') {
                // Remove reaction
                await socialAPI.removeReaction('post', this.post.post_id);
                this.post.user_reacted = null;
                this.post.reaction_counts.like--;
                icon.textContent = '🤍';
            } else {
                // Add reaction
                await socialAPI.addReaction({
                    reactable_type: 'post',
                    reactable_id: this.post.post_id,
                    reaction_type: 'like'
                });
                this.post.user_reacted = 'like';
                this.post.reaction_counts = this.post.reaction_counts || {};
                this.post.reaction_counts.like = (this.post.reaction_counts.like || 0) + 1;
                icon.textContent = '❤️';
            }
            
            count.textContent = this.post.reaction_counts.like || 0;
        } catch (error) {
            console.error('Reaction error:', error);
            toast.error('Failed to update reaction');
        }
    }

    /**
     * Toggle comments section
     */
    async toggleComments() {
        const commentsSection = this.element.querySelector('.post-comments');
        const isVisible = commentsSection.style.display !== 'none';
        
        if (isVisible) {
            commentsSection.style.display = 'none';
        } else {
            commentsSection.style.display = 'block';
            if (!this.commentsLoaded) {
                await this.loadComments();
                this.commentsLoaded = true;
            }
        }
    }

    /**
     * Load comments using CommentsSection component
     */
    async loadComments() {
        const container = this.element.querySelector('.comments-container');
        const commentsSection = new CommentsSection(this.post.post_id, this.post.comment_count);
        await commentsSection.render(container);
    }

    /**
     * Handle share
     */
    handleShare() {
        toast.info('Share functionality coming soon!');
    }

    /**
     * Handle bookmark
     */
    handleBookmark() {
        toast.info('Bookmark functionality coming soon!');
    }

    /**
     * Format timestamp to relative time
     */
    formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        
        if (seconds < 60) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        
        return date.toLocaleDateString();
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Convert URLs to clickable links
     */
    linkifyUrls(text) {
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        return text.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
    }
}

export default PostCard;
