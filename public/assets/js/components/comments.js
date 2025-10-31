/**
 * Comments Component
 * Display and manage comments on posts
 */

import socialAPI from '../api/social.js';
import toast from './toast.js';
import authService from '../services/auth-service.js';

export class CommentsSection {
    constructor(postId, initialCount = 0) {
        this.postId = postId;
        this.comments = [];
        this.commentCount = initialCount;
        this.loading = false;
        this.page = 1;
        this.hasMore = true;
    }

    /**
     * Render comments section
     */
    async render(container) {
        container.innerHTML = `
            <div class="comments-section" id="comments-${this.postId}">
                <!-- Add Comment Form -->
                <div class="add-comment-form">
                    <img src="${authService.getCurrentUser()?.avatar_url || '/assets/images/default-avatar.svg'}" 
                         class="avatar avatar-sm" alt="Your avatar">
                    <form class="comment-form" data-post-id="${this.postId}">
                        <input type="text" 
                               class="comment-input" 
                               placeholder="Write a comment..." 
                               maxlength="1000">
                        <button type="submit" class="btn-send-comment" aria-label="Send comment">
                            <span>➤</span>
                        </button>
                    </form>
                </div>

                <!-- Comments List -->
                <div class="comments-list" id="comments-list-${this.postId}">
                    ${this.commentCount > 0 
                        ? '<div class="loading-container"><div class="spinner"></div></div>'
                        : '<p class="empty-state-text">No comments yet. Be the first to comment!</p>'
                    }
                </div>

                <!-- Load More Button -->
                <div class="load-more-comments" id="load-more-${this.postId}" style="display: none;">
                    <button class="btn btn-secondary btn-sm">Load more comments</button>
                </div>
            </div>
        `;

        this.attachEventListeners();
        
        if (this.commentCount > 0) {
            await this.loadComments();
        }
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Submit comment
        const form = document.querySelector(`.comment-form[data-post-id="${this.postId}"]`);
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleCommentSubmit(e.target);
            });
        }

        // Load more comments
        const loadMoreBtn = document.getElementById(`load-more-${this.postId}`);
        if (loadMoreBtn) {
            loadMoreBtn.querySelector('button')?.addEventListener('click', () => {
                this.loadComments();
            });
        }
    }

    /**
     * Load comments from API
     */
    async loadComments() {
        if (this.loading || !this.hasMore) return;

        this.loading = true;
        const listContainer = document.getElementById(`comments-list-${this.postId}`);

        try {
            const response = await socialAPI.getComments('post', this.postId, {
                page: this.page,
                limit: 10,
                sort: 'created_at:desc'
            });

            if (response.success && response.data) {
                this.comments.push(...response.data.comments);
                this.hasMore = response.data.pagination.has_next;
                this.page++;

                this.renderComments(listContainer);
                
                // Show/hide load more button
                const loadMoreBtn = document.getElementById(`load-more-${this.postId}`);
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = this.hasMore ? 'block' : 'none';
                }
            }
        } catch (error) {
            console.error('Load comments error:', error);
            listContainer.innerHTML = '<p class="error-text">Failed to load comments</p>';
        } finally {
            this.loading = false;
        }
    }

    /**
     * Render comments list
     */
    renderComments(container) {
        if (this.comments.length === 0) {
            container.innerHTML = '<p class="empty-state-text">No comments yet. Be the first to comment!</p>';
            return;
        }

        container.innerHTML = this.comments.map(comment => this.renderComment(comment)).join('');
        this.attachCommentActions();
    }

    /**
     * Render single comment
     */
    renderComment(comment) {
        const currentUser = authService.getCurrentUser();
        const isOwn = comment.user_id === currentUser?.user_id;
        const likeCount = comment.reaction_counts?.like || 0;
        const hasLiked = comment.user_reacted === 'like';

        return `
            <div class="comment-item" data-comment-id="${comment.comment_id}">
                <img src="${comment.author?.avatar_url || '/assets/images/default-avatar.svg'}" 
                     class="avatar avatar-sm" 
                     alt="${comment.author?.display_name || 'User'}'s avatar">
                
                <div class="comment-content">
                    <div class="comment-header">
                        <a href="#/wall/${comment.author?.username}" class="comment-author">
                            ${comment.author?.display_name || comment.author?.username || 'User'}
                        </a>
                        <span class="comment-time">${this.formatTime(comment.created_at)}</span>
                    </div>

                    <p class="comment-text">${this.escapeHtml(comment.content)}</p>

                    <div class="comment-actions">
                        <button class="comment-action-btn ${hasLiked ? 'active' : ''}" 
                                data-action="like" 
                                data-comment-id="${comment.comment_id}">
                            <span class="icon">${hasLiked ? '❤️' : '🤍'}</span>
                            ${likeCount > 0 ? `<span class="count">${likeCount}</span>` : ''}
                        </button>

                        <button class="comment-action-btn" 
                                data-action="reply" 
                                data-comment-id="${comment.comment_id}">
                            <span class="icon">💬</span>
                            Reply
                        </button>

                        ${isOwn ? `
                            <button class="comment-action-btn" 
                                    data-action="edit" 
                                    data-comment-id="${comment.comment_id}">
                                <span class="icon">✏️</span>
                                Edit
                            </button>
                            <button class="comment-action-btn danger" 
                                    data-action="delete" 
                                    data-comment-id="${comment.comment_id}">
                                <span class="icon">🗑️</span>
                                Delete
                            </button>
                        ` : ''}
                    </div>

                    <!-- Nested replies container -->
                    <div class="comment-replies" id="replies-${comment.comment_id}">
                        ${comment.reply_count > 0 
                            ? `<button class="view-replies-btn" data-comment-id="${comment.comment_id}">
                                View ${comment.reply_count} ${comment.reply_count === 1 ? 'reply' : 'replies'}
                              </button>`
                            : ''
                        }
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Attach comment action listeners
     */
    attachCommentActions() {
        // Like/unlike comments
        document.querySelectorAll('.comment-action-btn[data-action="like"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const commentId = e.currentTarget.dataset.commentId;
                this.handleCommentLike(commentId);
            });
        });

        // Reply to comments
        document.querySelectorAll('.comment-action-btn[data-action="reply"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const commentId = e.currentTarget.dataset.commentId;
                this.handleCommentReply(commentId);
            });
        });

        // Edit comments
        document.querySelectorAll('.comment-action-btn[data-action="edit"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const commentId = e.currentTarget.dataset.commentId;
                this.handleCommentEdit(commentId);
            });
        });

        // Delete comments
        document.querySelectorAll('.comment-action-btn[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const commentId = e.currentTarget.dataset.commentId;
                this.handleCommentDelete(commentId);
            });
        });

        // View replies
        document.querySelectorAll('.view-replies-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const commentId = e.currentTarget.dataset.commentId;
                this.loadReplies(commentId);
            });
        });
    }

    /**
     * Handle comment submission
     */
    async handleCommentSubmit(form) {
        const input = form.querySelector('.comment-input');
        const content = input.value.trim();

        if (!content) {
            toast.error('Comment cannot be empty');
            return;
        }

        const submitBtn = form.querySelector('.btn-send-comment');
        submitBtn.disabled = true;

        try {
            const response = await socialAPI.addComment({
                commentable_type: 'post',
                commentable_id: this.postId,
                content
            });

            if (response.success && response.data) {
                // Add new comment to the beginning
                this.comments.unshift(response.data.comment);
                this.commentCount++;

                // Re-render comments
                const listContainer = document.getElementById(`comments-list-${this.postId}`);
                this.renderComments(listContainer);

                // Clear input
                input.value = '';
                toast.success('Comment added');
            } else {
                toast.error(response.message || 'Failed to add comment');
            }
        } catch (error) {
            console.error('Add comment error:', error);
            toast.error('An error occurred while adding comment');
        } finally {
            submitBtn.disabled = false;
        }
    }

    /**
     * Handle comment like/unlike
     */
    async handleCommentLike(commentId) {
        const comment = this.comments.find(c => c.comment_id === commentId);
        if (!comment) return;

        try {
            if (comment.user_reacted === 'like') {
                await socialAPI.removeReaction('comment', commentId);
                comment.user_reacted = null;
                if (!comment.reaction_counts) comment.reaction_counts = {};
                comment.reaction_counts.like = Math.max(0, (comment.reaction_counts.like || 0) - 1);
            } else {
                await socialAPI.addReaction({
                    reactable_type: 'comment',
                    reactable_id: commentId,
                    reaction_type: 'like'
                });
                comment.user_reacted = 'like';
                if (!comment.reaction_counts) comment.reaction_counts = {};
                comment.reaction_counts.like = (comment.reaction_counts.like || 0) + 1;
            }

            // Update UI
            const listContainer = document.getElementById(`comments-list-${this.postId}`);
            this.renderComments(listContainer);
        } catch (error) {
            console.error('Comment reaction error:', error);
        }
    }

    /**
     * Handle comment reply
     */
    async handleCommentReply(commentId) {
        toast.info('Reply feature coming soon');
    }

    /**
     * Handle comment edit
     */
    async handleCommentEdit(commentId) {
        const comment = this.comments.find(c => c.comment_id === commentId);
        if (!comment) return;

        const newContent = prompt('Edit comment:', comment.content);
        if (newContent === null || newContent.trim() === '') return;

        try {
            const response = await socialAPI.updateComment(commentId, {
                content: newContent.trim()
            });

            if (response.success) {
                comment.content = newContent.trim();
                const listContainer = document.getElementById(`comments-list-${this.postId}`);
                this.renderComments(listContainer);
                toast.success('Comment updated');
            } else {
                toast.error(response.message || 'Failed to update comment');
            }
        } catch (error) {
            console.error('Update comment error:', error);
            toast.error('An error occurred while updating comment');
        }
    }

    /**
     * Handle comment delete
     */
    async handleCommentDelete(commentId) {
        if (!confirm('Are you sure you want to delete this comment?')) return;

        try {
            const response = await socialAPI.deleteComment(commentId);

            if (response.success) {
                this.comments = this.comments.filter(c => c.comment_id !== commentId);
                this.commentCount--;
                const listContainer = document.getElementById(`comments-list-${this.postId}`);
                this.renderComments(listContainer);
                toast.success('Comment deleted');
            } else {
                toast.error(response.message || 'Failed to delete comment');
            }
        } catch (error) {
            console.error('Delete comment error:', error);
            toast.error('An error occurred while deleting comment');
        }
    }

    /**
     * Load replies for a comment
     */
    async loadReplies(commentId) {
        toast.info('Nested replies feature coming soon');
    }

    /**
     * Format relative time
     */
    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
        
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
}

export default CommentsSection;
