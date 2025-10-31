/**
 * Create Post Component
 * Post creation modal with rich text, media upload, and AI generation
 */

import postsAPI from '../api/posts.js';
import toast from './toast.js';

export class CreatePost {
    constructor() {
        this.modal = null;
        this.mediaFiles = [];
        this.maxFiles = 10;
        this.maxFileSize = 50 * 1024 * 1024; // 50MB
        this.allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        this.allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
    }

    /**
     * Open create post modal
     */
    open(options = {}) {
        const {
            wallId = null,
            visibility = 'public',
            editMode = false,
            existingPost = null
        } = options;

        this.wallId = wallId;
        this.editMode = editMode;
        this.existingPost = existingPost;

        this.createModal(visibility);
        this.attachEventListeners();
        
        if (editMode && existingPost) {
            this.populateExistingPost(existingPost);
        }
    }

    /**
     * Create modal HTML
     */
    createModal(defaultVisibility) {
        const modalHTML = `
            <div class="modal-overlay" id="create-post-modal">
                <div class="modal-container modal-lg">
                    <div class="modal-header">
                        <h2>${this.editMode ? 'Edit Post' : 'Create Post'}</h2>
                        <button class="btn-close" id="close-create-post" aria-label="Close">✕</button>
                    </div>

                    <div class="modal-body">
                        <form id="create-post-form">
                            <!-- Post Content -->
                            <div class="form-group">
                                <textarea id="post-content" 
                                          class="post-textarea" 
                                          placeholder="What's on your mind?"
                                          maxlength="5000"
                                          rows="6"></textarea>
                                <div class="character-count">
                                    <span id="char-count">0</span> / 5000
                                </div>
                            </div>

                            <!-- Media Preview -->
                            <div id="media-preview" class="media-preview"></div>

                            <!-- Post Options -->
                            <div class="post-options">
                                <div class="post-option">
                                    <label for="post-visibility">Visibility</label>
                                    <select id="post-visibility" class="form-control">
                                        <option value="public" ${defaultVisibility === 'public' ? 'selected' : ''}>🌍 Public</option>
                                        <option value="followers" ${defaultVisibility === 'followers' ? 'selected' : ''}>👥 Followers</option>
                                        <option value="friends" ${defaultVisibility === 'friends' ? 'selected' : ''}>🤝 Friends</option>
                                        <option value="private" ${defaultVisibility === 'private' ? 'selected' : ''}>🔒 Private</option>
                                    </select>
                                </div>

                                <div class="post-option">
                                    <label for="allow-comments">Comments</label>
                                    <select id="allow-comments" class="form-control">
                                        <option value="1">Enabled</option>
                                        <option value="0">Disabled</option>
                                    </select>
                                </div>

                                <div class="post-option">
                                    <label>
                                        <input type="checkbox" id="schedule-post" class="toggle-checkbox">
                                        Schedule Post
                                    </label>
                                    <input type="datetime-local" 
                                           id="scheduled-time" 
                                           class="form-control"
                                           style="display: none;">
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="post-actions">
                                <div class="media-buttons">
                                    <button type="button" class="btn-icon" id="add-image" title="Add image">
                                        <span>🖼️</span>
                                    </button>
                                    <button type="button" class="btn-icon" id="add-video" title="Add video">
                                        <span>🎥</span>
                                    </button>
                                    <button type="button" class="btn-icon" id="add-emoji" title="Add emoji">
                                        <span>😊</span>
                                    </button>
                                    <button type="button" class="btn-icon" id="add-poll" title="Create poll">
                                        <span>📊</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="generate-ai">
                                        <span>✨</span> AI Generate
                                    </button>
                                </div>

                                <div class="submit-buttons">
                                    <button type="button" class="btn btn-secondary" id="save-draft">
                                        Save Draft
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="submit-post">
                                        ${this.editMode ? 'Update' : 'Post'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <input type="file" id="image-upload" accept="image/*" multiple class="hidden">
                <input type="file" id="video-upload" accept="video/*" class="hidden">
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('create-post-modal');

        // Show modal with animation
        setTimeout(() => this.modal.classList.add('active'), 10);
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Close modal
        const closeBtn = document.getElementById('close-create-post');
        closeBtn.addEventListener('click', () => this.close());

        // Close on overlay click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Character count
        const textarea = document.getElementById('post-content');
        textarea.addEventListener('input', () => this.updateCharacterCount());

        // Add image
        const addImageBtn = document.getElementById('add-image');
        const imageUpload = document.getElementById('image-upload');
        addImageBtn.addEventListener('click', () => imageUpload.click());
        imageUpload.addEventListener('change', (e) => this.handleFileUpload(e, 'image'));

        // Add video
        const addVideoBtn = document.getElementById('add-video');
        const videoUpload = document.getElementById('video-upload');
        addVideoBtn.addEventListener('click', () => videoUpload.click());
        videoUpload.addEventListener('change', (e) => this.handleFileUpload(e, 'video'));

        // Add emoji
        const addEmojiBtn = document.getElementById('add-emoji');
        addEmojiBtn.addEventListener('click', () => this.showEmojiPicker());

        // Add poll
        const addPollBtn = document.getElementById('add-poll');
        addPollBtn.addEventListener('click', () => this.addPollSection());

        // AI generate
        const generateAIBtn = document.getElementById('generate-ai');
        generateAIBtn.addEventListener('click', () => this.openAIGenerate());

        // Schedule toggle
        const scheduleCheckbox = document.getElementById('schedule-post');
        const scheduledTime = document.getElementById('scheduled-time');
        scheduleCheckbox.addEventListener('change', (e) => {
            scheduledTime.style.display = e.target.checked ? 'block' : 'none';
        });

        // Save draft
        const saveDraftBtn = document.getElementById('save-draft');
        saveDraftBtn.addEventListener('click', () => this.saveDraft());

        // Submit form
        const form = document.getElementById('create-post-form');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });
    }

    /**
     * Update character count
     */
    updateCharacterCount() {
        const textarea = document.getElementById('post-content');
        const charCount = document.getElementById('char-count');
        const count = textarea.value.length;
        charCount.textContent = count;

        if (count > 4500) {
            charCount.style.color = 'var(--color-error)';
        } else if (count > 4000) {
            charCount.style.color = 'var(--color-warning)';
        } else {
            charCount.style.color = 'var(--color-text-secondary)';
        }
    }

    /**
     * Handle file upload
     */
    async handleFileUpload(event, type) {
        const files = Array.from(event.target.files);
        
        for (const file of files) {
            // Check file count
            if (this.mediaFiles.length >= this.maxFiles) {
                toast.error(`Maximum ${this.maxFiles} files allowed`);
                break;
            }

            // Check file size
            if (file.size > this.maxFileSize) {
                toast.error(`File ${file.name} is too large. Maximum size is 50MB`);
                continue;
            }

            // Check file type
            const allowedTypes = type === 'image' ? this.allowedImageTypes : this.allowedVideoTypes;
            if (!allowedTypes.includes(file.type)) {
                toast.error(`File ${file.name} has unsupported format`);
                continue;
            }

            // Add file
            this.mediaFiles.push({
                file,
                type,
                url: URL.createObjectURL(file),
                id: Date.now() + Math.random()
            });
        }

        this.renderMediaPreview();
        event.target.value = ''; // Reset input
    }

    /**
     * Render media preview
     */
    renderMediaPreview() {
        const previewContainer = document.getElementById('media-preview');
        
        if (this.mediaFiles.length === 0) {
            previewContainer.innerHTML = '';
            previewContainer.style.display = 'none';
            return;
        }

        previewContainer.style.display = 'grid';
        previewContainer.innerHTML = this.mediaFiles.map(media => `
            <div class="media-item" data-id="${media.id}">
                ${media.type === 'image' 
                    ? `<img src="${media.url}" alt="Preview">`
                    : `<video src="${media.url}" controls></video>`
                }
                <button type="button" 
                        class="btn-remove-media" 
                        onclick="window.createPostComponent.removeMedia('${media.id}')"
                        aria-label="Remove">
                    ✕
                </button>
            </div>
        `).join('');
    }

    /**
     * Remove media file
     */
    removeMedia(id) {
        const index = this.mediaFiles.findIndex(m => m.id == id);
        if (index !== -1) {
            URL.revokeObjectURL(this.mediaFiles[index].url);
            this.mediaFiles.splice(index, 1);
            this.renderMediaPreview();
        }
    }

    /**
     * Show emoji picker
     */
    showEmojiPicker() {
        // Simple emoji picker
        const emojis = ['😀', '😂', '😍', '🥰', '😎', '🤔', '😢', '😡', '👍', '👎', '❤️', '🎉', '🔥', '✨', '💯', '🙌'];
        const textarea = document.getElementById('post-content');
        
        const emoji = prompt('Choose an emoji:\n' + emojis.join(' '));
        if (emoji && emojis.includes(emoji)) {
            const cursorPos = textarea.selectionStart;
            const textBefore = textarea.value.substring(0, cursorPos);
            const textAfter = textarea.value.substring(cursorPos);
            textarea.value = textBefore + emoji + textAfter;
            textarea.focus();
            textarea.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
            this.updateCharacterCount();
        }
    }

    /**
     * Add poll section
     */
    addPollSection() {
        toast.info('Poll feature coming soon!');
    }

    /**
     * Open AI generation dialog
     */
    openAIGenerate() {
        window.location.hash = '/ai';
        this.close();
    }

    /**
     * Save as draft
     */
    async saveDraft() {
        const content = document.getElementById('post-content').value.trim();
        
        if (!content && this.mediaFiles.length === 0) {
            toast.error('Cannot save empty draft');
            return;
        }

        const postData = this.collectFormData();
        postData.status = 'draft';

        try {
            const response = await postsAPI.createPost(postData);
            if (response.success) {
                toast.success('Draft saved successfully');
                this.close();
            } else {
                toast.error(response.message || 'Failed to save draft');
            }
        } catch (error) {
            console.error('Save draft error:', error);
            toast.error('An error occurred while saving draft');
        }
    }

    /**
     * Handle form submission
     */
    async handleSubmit() {
        const content = document.getElementById('post-content').value.trim();
        
        if (!content && this.mediaFiles.length === 0) {
            toast.error('Post content cannot be empty');
            return;
        }

        const submitBtn = document.getElementById('submit-post');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="btn-loader"></span> Posting...';

        try {
            const postData = this.collectFormData();
            
            let response;
            if (this.editMode && this.existingPost) {
                response = await postsAPI.updatePost(this.existingPost.post_id, postData);
            } else {
                response = await postsAPI.createPost(postData);
            }

            if (response.success) {
                toast.success(this.editMode ? 'Post updated successfully' : 'Post created successfully');
                this.close();
                
                // Refresh page or emit event
                if (window.currentPage && window.currentPage.refresh) {
                    window.currentPage.refresh();
                } else {
                    window.location.reload();
                }
            } else {
                toast.error(response.message || 'Failed to create post');
                submitBtn.disabled = false;
                submitBtn.textContent = this.editMode ? 'Update' : 'Post';
            }
        } catch (error) {
            console.error('Submit post error:', error);
            toast.error('An error occurred while creating post');
            submitBtn.disabled = false;
            submitBtn.textContent = this.editMode ? 'Update' : 'Post';
        }
    }

    /**
     * Collect form data
     */
    collectFormData() {
        const content = document.getElementById('post-content').value.trim();
        const visibility = document.getElementById('post-visibility').value;
        const allowComments = document.getElementById('allow-comments').value === '1';
        const schedulePost = document.getElementById('schedule-post').checked;
        const scheduledTime = document.getElementById('scheduled-time').value;

        const data = {
            content,
            visibility,
            allow_comments: allowComments,
            media_urls: [], // TODO: Upload media files first
            status: 'published'
        };

        if (this.wallId) {
            data.wall_id = this.wallId;
        }

        if (schedulePost && scheduledTime) {
            data.scheduled_at = new Date(scheduledTime).toISOString();
            data.status = 'scheduled';
        }

        // TODO: Implement actual file upload
        if (this.mediaFiles.length > 0) {
            toast.info('Media upload will be implemented soon. Creating text-only post.');
        }

        return data;
    }

    /**
     * Populate existing post for editing
     */
    populateExistingPost(post) {
        document.getElementById('post-content').value = post.content || '';
        document.getElementById('post-visibility').value = post.visibility || 'public';
        document.getElementById('allow-comments').value = post.allow_comments ? '1' : '0';
        this.updateCharacterCount();
    }

    /**
     * Close modal
     */
    close() {
        this.modal.classList.remove('active');
        
        setTimeout(() => {
            // Revoke object URLs
            this.mediaFiles.forEach(media => URL.revokeObjectURL(media.url));
            this.mediaFiles = [];
            
            this.modal.remove();
            this.modal = null;
        }, 300);
    }
}

// Global instance for easy access
window.createPostComponent = new CreatePost();

export default CreatePost;
