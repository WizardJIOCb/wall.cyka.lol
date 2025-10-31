/**
 * Messages Page
 * Direct messaging interface
 */

import toast from '../components/toast.js';
import authService from '../services/auth-service.js';

const MessagesPage = {
    conversations: [],
    currentConversation: null,
    messages: [],

    async render(container, params) {
        container.innerHTML = `
            <div class="messages-page">
                <div class="messages-container">
                    <div class="conversations-sidebar">
                        <div class="conversations-header">
                            <h2>Messages</h2>
                            <button class="btn btn-icon" id="new-message-btn" title="New Message">
                                ✉️
                            </button>
                        </div>

                        <div class="search-box">
                            <input 
                                type="text" 
                                class="form-control" 
                                placeholder="Search conversations..."
                                id="conversation-search"
                            >
                        </div>

                        <div id="conversations-list" class="conversations-list">
                            <div class="loading-container">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>

                    <div class="messages-main">
                        <div id="messages-content" class="messages-content">
                            <div class="empty-state">
                                <div style="font-size: 4rem; margin-bottom: 1rem;">💬</div>
                                <h2>Your Messages</h2>
                                <p class="text-secondary">Select a conversation from the list or start a new one</p>
                                <button class="btn btn-primary" id="start-conversation-btn">
                                    <span class="icon">✉️</span>
                                    <span>New Message</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.setupEventListeners();
        await this.loadConversations();

        if (params.id) {
            await this.loadConversation(params.id);
        }
    },

    setupEventListeners() {
        const newMessageBtn = document.getElementById('new-message-btn');
        const startConversationBtn = document.getElementById('start-conversation-btn');

        if (newMessageBtn) {
            newMessageBtn.addEventListener('click', () => this.showNewMessageDialog());
        }

        if (startConversationBtn) {
            startConversationBtn.addEventListener('click', () => this.showNewMessageDialog());
        }

        // Search conversations
        const searchInput = document.getElementById('conversation-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterConversations(e.target.value);
            });
        }
    },

    async loadConversations() {
        const container = document.getElementById('conversations-list');
        
        // Simulate loading
        setTimeout(() => {
            container.innerHTML = `
                <div class="empty-state">
                    <p class="text-secondary">No conversations yet</p>
                </div>
            `;
        }, 500);
    },

    async loadConversation(conversationId) {
        const container = document.getElementById('messages-content');
        
        container.innerHTML = `
            <div class="conversation-header">
                <div class="conversation-user-info">
                    <img src="/assets/images/default-avatar.svg" alt="User" class="avatar avatar-md">
                    <div>
                        <div class="conversation-user-name">User Name</div>
                        <div class="conversation-user-status">Online</div>
                    </div>
                </div>
                <div class="conversation-actions">
                    <button class="btn btn-icon" title="Call">📞</button>
                    <button class="btn btn-icon" title="More">⋯</button>
                </div>
            </div>

            <div class="messages-list" id="messages-list">
                <div class="message message-received">
                    <img src="/assets/images/default-avatar.svg" alt="User" class="message-avatar">
                    <div class="message-content">
                        <div class="message-text">Coming soon! 💬</div>
                        <div class="message-time">Just now</div>
                    </div>
                </div>
            </div>

            <div class="message-input-container">
                <button class="btn btn-icon" title="Attach">📎</button>
                <input 
                    type="text" 
                    class="message-input" 
                    placeholder="Type a message..."
                    id="message-input"
                >
                <button class="btn btn-icon" title="Emoji">😊</button>
                <button class="btn btn-primary btn-icon" title="Send" id="send-message-btn">
                    ➤
                </button>
            </div>
        `;
    },

    showNewMessageDialog() {
        toast.info('Direct messaging coming in next phase!');
    },

    filterConversations(query) {
        // TODO: Implement conversation filtering
    }
};

export default MessagesPage;
