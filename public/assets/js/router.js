/**
 * Client-Side Router
 * Hash-based routing for SPA
 */

class Router {
    constructor() {
        this.routes = {};
        this.currentRoute = null;
        
        // Listen for hash changes
        window.addEventListener('hashchange', () => this.handleRoute());
        window.addEventListener('load', () => this.handleRoute());
    }

    /**
     * Register a route
     */
    on(path, handler) {
        this.routes[path] = handler;
        return this;
    }

    /**
     * Navigate to a route
     */
    navigate(path) {
        window.location.hash = path;
    }

    /**
     * Handle route change
     */
    async handleRoute() {
        const hash = window.location.hash.slice(1) || '/';
        const [path, ...params] = hash.split('/').filter(Boolean);
        const route = `/${path || ''}`;

        // Check if route exists
        const handler = this.routes[route] || this.routes['*'];

        if (handler) {
            this.currentRoute = route;
            
            // Extract route parameters
            const routeParams = {};
            if (params.length > 0) {
                routeParams.id = params[0];
                routeParams.params = params;
            }

            try {
                await handler(routeParams);
            } catch (error) {
                console.error('Route handler error:', error);
                this.navigate('/error');
            }
        } else {
            console.warn('No handler found for route:', route);
            this.navigate('/');
        }

        // Update active navigation
        this.updateActiveNav(route);
    }

    /**
     * Update active navigation item
     */
    updateActiveNav(route) {
        // Update sidebar navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            const href = item.getAttribute('href');
            const targetRoute = href.replace('#', '');
            if (targetRoute === route || (route === '/' && targetRoute === '/')) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Update bottom navigation
        document.querySelectorAll('.bottom-nav-item').forEach(item => {
            const href = item.getAttribute('href');
            if (href) {
                const targetRoute = href.replace('#', '');
                if (targetRoute === route || (route === '/' && targetRoute === '/')) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            }
        });
    }

    /**
     * Get current route
     */
    getCurrentRoute() {
        return this.currentRoute;
    }

    /**
     * Get route parameters from hash
     */
    getParams() {
        const hash = window.location.hash.slice(1);
        const parts = hash.split('/').filter(Boolean);
        return parts.slice(1);
    }
}

// Create singleton instance
const router = new Router();

export default router;
