/**
 * Notifications Page
 * Display user notifications and activities
 */

import toast from '../components/toast.js';
import authService from '../services/auth-service.js';

const NotificationsPage = {
    notifications: [],
    filter: 'all',

    async render(container) {
        container.innerHTML = `
            <div class="notifications-page">
                <div class="page-header">
                    <h1>🔔 Notifications</h1>
                    <div class="header-actions">
                        <button class="btn btn-secondary btn-sm" id="mark-all-read">
                            Mark all as read
                        </button>
                        <button class="btn btn-icon" id="notifications-settings" title="Settings">
                            ⚙️
                        </button>
                    </div>
                </div>

                <div class="notifications-filters">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="likes">
                        <span class="icon">❤️</span>
                        <span>Likes</span>
                    </button>
                    <button class="filter-btn" data-filter="comments">
                        <span class="icon">💬</span>
                        <span>Comments</span>
                    </button>
                    <button class="filter-btn" data-filter="follows">
                        <span class="icon">👤</span>
                        <span>Follows</span>
                    </button>
                    <button class="filter-btn" data-filter="mentions">
                        <span class="icon">@</span>
                        <span>Mentions</span>
                    </button>
                </div>

                <div id="notifications-list" class="notifications-list">
                    <div class="loading-container">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        `;

        this.setupEventListeners();
        await this.loadNotifications();
    },

    setupEventListeners() {
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                e.target.closest('.filter-btn').classList.add('active');
                this.filter = e.target.closest('.filter-btn').dataset.filter;
                this.loadNotifications();
            });
        });

        // Mark all as read
        const markAllReadBtn = document.getElementById('mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }

        // Settings button
        const settingsBtn = document.getElementById('notifications-settings');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', () => {
                window.location.hash = '/settings#notifications';
            });
        }
    },

    async loadNotifications() {
        const container = document.getElementById('notifications-list');
        
        container.innerHTML = '<div class="loading-container"><div class="spinner"></div></div>';

        // Simulate loading
        setTimeout(() => {
            const sampleNotifications = this.getSampleNotifications();
            
            if (sampleNotifications.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">🔔</div>
                        <h2>No notifications yet</h2>
                        <p class="text-secondary">When you get notifications, they'll show up here</p>
                    </div>
                `;
            } else {
                container.innerHTML = sampleNotifications.map(notif => this.renderNotification(notif)).join('');
            }
        }, 500);
    },

    getSampleNotifications() {
        // Return empty for now - will be populated when backend is connected
        return [];
    },

    renderNotification(notification) {
        const iconMap = {
            like: '❤️',
            comment: '💬',
            follow: '👤',
            mention: '@',
            friend_request: '👥',
            ai_generated: '🤖'
        };

        return `
            <div class="notification-item ${notification.read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-icon">
                    ${iconMap[notification.type] || '🔔'}
                </div>
                <div class="notification-content">
                    <div class="notification-text">${notification.text}</div>
                    <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                </div>
                <div class="notification-actions">
                    ${!notification.read ? '<button class="btn btn-icon btn-sm mark-read-btn" title="Mark as read">✓</button>' : ''}
                    <button class="btn btn-icon btn-sm delete-btn" title="Delete">🗑️</button>
                </div>
            </div>
        `;
    },

    formatTime(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000);

        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
        
        return time.toLocaleDateString();
    },

    async markAllAsRead() {
        toast.success('All notifications marked as read');
        await this.loadNotifications();
    }
};

export default NotificationsPage;
