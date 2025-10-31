/**
 * Main Application Entry Point
 * Initialize app, setup routes, and handle global events
 */

import router from './router.js';
import authService from './services/auth-service.js';
import themeService from './services/theme-service.js';
import toast from './components/toast.js';

class App {
    constructor() {
        this.isInitialized = false;
    }

    /**
     * Initialize application
     */
    async init() {
        if (this.isInitialized) return;

        console.log('Initializing Wall Social Platform...');

        // Initialize theme
        themeService.init();

        // Initialize authentication
        const isAuthenticated = await authService.init();
        
        if (!isAuthenticated) {
            // Redirect to login if not authenticated
            window.location.href = '/login.html';
            return;
        }

        // Setup routes
        this.setupRoutes();

        // Setup global event listeners
        this.setupEventListeners();

        // Initialize UI components
        this.initializeUI();

        this.isInitialized = true;
        console.log('App initialized successfully');
    }

    /**
     * Setup application routes
     */
    setupRoutes() {
        // Home feed
        router.on('/', async () => {
            await this.loadPage('home');
        });

        // My wall
        router.on('/wall', async () => {
            await this.loadPage('wall');
        });

        // AI generate
        router.on('/ai', async () => {
            await this.loadPage('ai-generate');
        });

        // Messages
        router.on('/messages', async () => {
            await this.loadPage('messages');
        });

        // Discover
        router.on('/discover', async () => {
            await this.loadPage('discover');
        });

        // Notifications
        router.on('/notifications', async () => {
            await this.loadPage('notifications');
        });

        // Settings
        router.on('/settings', async () => {
            await this.loadPage('settings');
        });

        // Profile
        router.on('/profile', async () => {
            await this.loadPage('profile');
        });

        // 404 - Not found
        router.on('*', async () => {
            await this.loadPage('404');
        });
    }

    /**
     * Load page content
     */
    async loadPage(pageName, params = {}) {
        const content = document.getElementById('content');
        
        // Show loading state
        content.innerHTML = `
            <div class="loading-container">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>
        `;

        try {
            // Dynamically import page module
            const module = await import(`./pages/${pageName}.js`);
            
            if (module.default && typeof module.default.render === 'function') {
                await module.default.render(content, params);
            } else {
                throw new Error(`Page module ${pageName} does not export a render function`);
            }
        } catch (error) {
            console.error(`Failed to load page: ${pageName}`, error);
            content.innerHTML = `
                <div class="error-container">
                    <h2>Page Not Found</h2>
                    <p>The page you're looking for doesn't exist yet.</p>
                    <p class="text-secondary">Page: ${pageName}</p>
                    <button class="btn btn-primary" onclick="window.location.hash = '/'">
                        Go to Home
                    </button>
                </div>
            `;
        }
    }

    /**
     * Setup global event listeners
     */
    setupEventListeners() {
        // Mobile menu toggle
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', (e) => {
                if (sidebar.classList.contains('open') && 
                    !sidebar.contains(e.target) && 
                    !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            });
        }

        // User menu dropdown
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userDropdown = document.getElementById('user-dropdown');
        
        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isHidden = userDropdown.hasAttribute('hidden');
                userDropdown.toggleAttribute('hidden', !isHidden);
                userMenuBtn.setAttribute('aria-expanded', isHidden);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', () => {
                if (!userDropdown.hasAttribute('hidden')) {
                    userDropdown.setAttribute('hidden', '');
                    userMenuBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Logout button
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async () => {
                await authService.logout();
            });
        }

        // Mobile create button
        const mobileCreateBtn = document.getElementById('mobile-create-btn');
        const createPostBtn = document.getElementById('create-post-btn');
        
        const handleCreate = () => {
            const createPost = new CreatePost();
            createPost.open();
        };

        if (mobileCreateBtn) {
            mobileCreateBtn.addEventListener('click', handleCreate);
        }
        if (createPostBtn) {
            createPostBtn.addEventListener('click', handleCreate);
        }
    }

    /**
     * Initialize UI components
     */
    initializeUI() {
        // Update user info in header
        const user = authService.getCurrentUser();
        if (user) {
            const userNameEl = document.getElementById('user-name');
            const userAvatarEl = document.getElementById('user-avatar');
            
            if (userNameEl) {
                userNameEl.textContent = user.display_name || user.username;
            }
            
            if (userAvatarEl && user.avatar_url) {
                userAvatarEl.src = user.avatar_url;
            }
        }

        // Show welcome message
        toast.success('Welcome back!', 3000);
    }
}

// Initialize app when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', async () => {
        const app = new App();
        await app.init();
    });
} else {
    const app = new App();
    await app.init();
}

export default App;
