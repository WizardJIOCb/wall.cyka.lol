/**
 * Settings Page
 * User profile and preferences settings
 */

import usersAPI from '../api/users.js';
import authService from '../services/auth-service.js';
import themeService from '../services/theme-service.js';
import toast from '../components/toast.js';

const SettingsPage = {
    currentUser: null,
    themes: ['light', 'dark', 'green', 'cream', 'blue', 'high-contrast'],

    async render(container) {
        this.currentUser = authService.getCurrentUser();

        container.innerHTML = `
            <div class="settings-page">
                <div class="settings-header">
                    <h1>Settings</h1>
                    <p class="text-secondary">Manage your account and preferences</p>
                </div>

                <div class="settings-container">
                    <div class="settings-tabs">
                        <button class="settings-tab active" data-tab="profile">Profile</button>
                        <button class="settings-tab" data-tab="appearance">Appearance</button>
                        <button class="settings-tab" data-tab="privacy">Privacy</button>
                        <button class="settings-tab" data-tab="notifications">Notifications</button>
                        <button class="settings-tab" data-tab="account">Account</button>
                    </div>

                    <div class="settings-content">
                        <div id="settings-tab-profile" class="settings-panel active">
                            ${this.renderProfileSettings()}
                        </div>

                        <div id="settings-tab-appearance" class="settings-panel">
                            ${this.renderAppearanceSettings()}
                        </div>

                        <div id="settings-tab-privacy" class="settings-panel">
                            ${this.renderPrivacySettings()}
                        </div>

                        <div id="settings-tab-notifications" class="settings-panel">
                            ${this.renderNotificationSettings()}
                        </div>

                        <div id="settings-tab-account" class="settings-panel">
                            ${this.renderAccountSettings()}
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.attachEventListeners();
    },

    renderProfileSettings() {
        return `
            <div class="settings-section">
                <h2>Profile Information</h2>
                <p class="text-secondary">Update your personal information and profile details</p>

                <form id="profile-form" class="settings-form">
                    <div class="form-group">
                        <label for="display-name">Display Name</label>
                        <input type="text" id="display-name" name="display_name" 
                               value="${this.currentUser.display_name || ''}" 
                               class="form-control" maxlength="50">
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" 
                               value="${this.currentUser.username}" 
                               class="form-control" maxlength="30" readonly>
                        <small class="form-text">Username cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="${this.currentUser.email || ''}" 
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" class="form-control" 
                                  rows="4" maxlength="500">${this.currentUser.bio || ''}</textarea>
                        <small class="form-text">Brief description about yourself</small>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" 
                               value="${this.currentUser.location || ''}" 
                               class="form-control" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" 
                               value="${this.currentUser.website || ''}" 
                               class="form-control">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" id="cancel-profile">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <h2>Profile Images</h2>
                <p class="text-secondary">Update your avatar and cover image</p>

                <div class="image-upload-section">
                    <div class="image-upload-item">
                        <label>Avatar</label>
                        <div class="avatar-preview">
                            <img src="${this.currentUser.avatar_url || '/assets/images/default-avatar.svg'}" 
                                 alt="Avatar" class="avatar avatar-xl">
                        </div>
                        <input type="file" id="avatar-upload" accept="image/*" class="hidden">
                        <button type="button" class="btn btn-secondary" id="upload-avatar">Upload Avatar</button>
                    </div>

                    <div class="image-upload-item">
                        <label>Cover Image</label>
                        <div class="cover-preview">
                            <div class="cover-image" style="background-image: url('${this.currentUser.cover_url || ''}');"></div>
                        </div>
                        <input type="file" id="cover-upload" accept="image/*" class="hidden">
                        <button type="button" class="btn btn-secondary" id="upload-cover">Upload Cover</button>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2>Social Links</h2>
                <p class="text-secondary">Add links to your social media profiles</p>

                <div id="social-links-container">
                    ${this.renderSocialLinksForm()}
                </div>
                <button type="button" class="btn btn-secondary" id="add-social-link">+ Add Social Link</button>
            </div>
        `;
    },

    renderSocialLinksForm() {
        const links = this.currentUser.social_links || [];
        if (links.length === 0) {
            return '<p class="text-secondary">No social links added yet</p>';
        }

        return links.map((link, index) => `
            <div class="social-link-item" data-index="${index}">
                <input type="text" placeholder="Label (e.g., Twitter)" 
                       value="${link.label}" class="form-control social-link-label">
                <input type="url" placeholder="URL" 
                       value="${link.url}" class="form-control social-link-url">
                <button type="button" class="btn btn-icon remove-social-link" data-index="${index}">
                    <span>🗑️</span>
                </button>
            </div>
        `).join('');
    },

    renderAppearanceSettings() {
        const currentTheme = themeService.getCurrentTheme();

        return `
            <div class="settings-section">
                <h2>Theme</h2>
                <p class="text-secondary">Choose your preferred color theme</p>

                <div class="theme-selector">
                    ${this.themes.map(theme => `
                        <button type="button" 
                                class="theme-option ${currentTheme === theme ? 'active' : ''}" 
                                data-theme="${theme}">
                            <div class="theme-preview theme-preview-${theme}"></div>
                            <span class="theme-name">${this.capitalizeFirst(theme)}</span>
                        </button>
                    `).join('')}
                </div>
            </div>

            <div class="settings-section">
                <h2>Display Preferences</h2>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <label for="font-size">Font Size</label>
                        <p class="text-secondary">Adjust the default font size</p>
                    </div>
                    <select id="font-size" class="form-control">
                        <option value="small">Small</option>
                        <option value="medium" selected>Medium</option>
                        <option value="large">Large</option>
                    </select>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="reduce-motion">Reduce Motion</label>
                        <p class="text-secondary">Minimize animations and transitions</p>
                    </div>
                    <input type="checkbox" id="reduce-motion" class="toggle">
                </div>
            </div>
        `;
    },

    renderPrivacySettings() {
        return `
            <div class="settings-section">
                <h2>Privacy Settings</h2>
                <p class="text-secondary">Control who can see your content and interact with you</p>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="profile-visibility">Profile Visibility</label>
                        <p class="text-secondary">Who can see your profile</p>
                    </div>
                    <select id="profile-visibility" class="form-control">
                        <option value="public">Everyone</option>
                        <option value="followers">Followers Only</option>
                        <option value="friends">Friends Only</option>
                        <option value="private">Only Me</option>
                    </select>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="post-visibility">Default Post Visibility</label>
                        <p class="text-secondary">Default visibility for new posts</p>
                    </div>
                    <select id="post-visibility" class="form-control">
                        <option value="public">Public</option>
                        <option value="followers">Followers</option>
                        <option value="friends">Friends</option>
                        <option value="private">Private</option>
                    </select>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="allow-comments">Who Can Comment</label>
                        <p class="text-secondary">Control who can comment on your posts</p>
                    </div>
                    <select id="allow-comments" class="form-control">
                        <option value="everyone">Everyone</option>
                        <option value="followers">Followers</option>
                        <option value="friends">Friends Only</option>
                        <option value="none">No One</option>
                    </select>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="allow-messages">Who Can Message You</label>
                        <p class="text-secondary">Control who can send you direct messages</p>
                    </div>
                    <select id="allow-messages" class="form-control">
                        <option value="everyone">Everyone</option>
                        <option value="followers">Followers</option>
                        <option value="friends">Friends Only</option>
                        <option value="none">No One</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-primary" id="save-privacy">Save Privacy Settings</button>
                </div>
            </div>
        `;
    },

    renderNotificationSettings() {
        return `
            <div class="settings-section">
                <h2>Notification Preferences</h2>
                <p class="text-secondary">Choose what notifications you want to receive</p>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="notify-likes">Likes</label>
                        <p class="text-secondary">When someone likes your post</p>
                    </div>
                    <input type="checkbox" id="notify-likes" class="toggle" checked>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="notify-comments">Comments</label>
                        <p class="text-secondary">When someone comments on your post</p>
                    </div>
                    <input type="checkbox" id="notify-comments" class="toggle" checked>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="notify-follows">New Followers</label>
                        <p class="text-secondary">When someone follows you</p>
                    </div>
                    <input type="checkbox" id="notify-follows" class="toggle" checked>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="notify-messages">Messages</label>
                        <p class="text-secondary">When you receive a new message</p>
                    </div>
                    <input type="checkbox" id="notify-messages" class="toggle" checked>
                </div>

                <div class="setting-item">
                    <div class="setting-info">
                        <label for="notify-friend-requests">Friend Requests</label>
                        <p class="text-secondary">When someone sends you a friend request</p>
                    </div>
                    <input type="checkbox" id="notify-friend-requests" class="toggle" checked>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-primary" id="save-notifications">Save Notification Settings</button>
                </div>
            </div>
        `;
    },

    renderAccountSettings() {
        return `
            <div class="settings-section">
                <h2>Password</h2>
                <p class="text-secondary">Change your password</p>

                <form id="password-form" class="settings-form">
                    <div class="form-group">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" id="confirm-password" class="form-control" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>

            <div class="settings-section danger-zone">
                <h2>Danger Zone</h2>
                <p class="text-secondary">Irreversible account actions</p>

                <div class="danger-actions">
                    <button type="button" class="btn btn-danger" id="delete-account">Delete Account</button>
                </div>
            </div>
        `;
    },

    attachEventListeners() {
        // Tab switching
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const tabName = e.target.dataset.tab;
                this.switchTab(tabName);
            });
        });

        // Profile form
        const profileForm = document.getElementById('profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleProfileUpdate(e.target);
            });
        }

        // Theme selector
        document.querySelectorAll('.theme-option').forEach(option => {
            option.addEventListener('click', (e) => {
                const theme = e.currentTarget.dataset.theme;
                this.handleThemeChange(theme);
            });
        });

        // Upload buttons
        const uploadAvatar = document.getElementById('upload-avatar');
        const avatarInput = document.getElementById('avatar-upload');
        if (uploadAvatar && avatarInput) {
            uploadAvatar.addEventListener('click', () => avatarInput.click());
            avatarInput.addEventListener('change', (e) => this.handleAvatarUpload(e));
        }

        const uploadCover = document.getElementById('upload-cover');
        const coverInput = document.getElementById('cover-upload');
        if (uploadCover && coverInput) {
            uploadCover.addEventListener('click', () => coverInput.click());
            coverInput.addEventListener('change', (e) => this.handleCoverUpload(e));
        }

        // Social links
        const addLinkBtn = document.getElementById('add-social-link');
        if (addLinkBtn) {
            addLinkBtn.addEventListener('click', () => this.addSocialLinkField());
        }

        // Save buttons
        const savePrivacy = document.getElementById('save-privacy');
        if (savePrivacy) {
            savePrivacy.addEventListener('click', () => this.handlePrivacyUpdate());
        }

        const saveNotifications = document.getElementById('save-notifications');
        if (saveNotifications) {
            saveNotifications.addEventListener('click', () => this.handleNotificationUpdate());
        }

        // Password form
        const passwordForm = document.getElementById('password-form');
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handlePasswordChange(e.target);
            });
        }

        // Delete account
        const deleteAccount = document.getElementById('delete-account');
        if (deleteAccount) {
            deleteAccount.addEventListener('click', () => this.handleDeleteAccount());
        }
    },

    switchTab(tabName) {
        // Update tabs
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === tabName);
        });

        // Update panels
        document.querySelectorAll('.settings-panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === `settings-tab-${tabName}`);
        });
    },

    async handleProfileUpdate(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await usersAPI.updateProfile(data);
            if (response.success) {
                toast.success('Profile updated successfully');
                authService.updateCurrentUser(response.data.user);
            } else {
                toast.error(response.message || 'Failed to update profile');
            }
        } catch (error) {
            console.error('Profile update error:', error);
            toast.error('An error occurred while updating profile');
        }
    },

    handleThemeChange(theme) {
        themeService.setTheme(theme);
        
        // Update active state
        document.querySelectorAll('.theme-option').forEach(option => {
            option.classList.toggle('active', option.dataset.theme === theme);
        });

        toast.success(`Theme changed to ${this.capitalizeFirst(theme)}`);
    },

    async handleAvatarUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // TODO: Implement file upload
        toast.info('Avatar upload feature coming soon');
    },

    async handleCoverUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // TODO: Implement file upload
        toast.info('Cover upload feature coming soon');
    },

    addSocialLinkField() {
        toast.info('Social link management coming soon');
    },

    async handlePrivacyUpdate() {
        toast.info('Privacy settings will be implemented soon');
    },

    async handleNotificationUpdate() {
        toast.info('Notification settings will be implemented soon');
    },

    async handlePasswordChange(form) {
        const formData = new FormData(form);
        const newPassword = formData.get('new-password');
        const confirmPassword = formData.get('confirm-password');

        if (newPassword !== confirmPassword) {
            toast.error('Passwords do not match');
            return;
        }

        toast.info('Password change feature coming soon');
    },

    async handleDeleteAccount() {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            toast.info('Account deletion feature coming soon');
        }
    },

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
};

export default SettingsPage;
