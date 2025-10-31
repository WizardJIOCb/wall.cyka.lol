/**
 * 404 Not Found Page
 */

const NotFoundPage = {
    async render(container) {
        container.innerHTML = `
            <div class="error-page">
                <div class="error-content">
                    <h1 class="error-code">404</h1>
                    <h2 class="error-title">Page Not Found</h2>
                    <p class="error-message">
                        The page you're looking for doesn't exist or has been moved.
                    </p>
                    <div class="error-actions">
                        <button class="btn btn-primary" onclick="window.location.hash = '/'">
                            <span class="icon">🏠</span>
                            <span>Go Home</span>
                        </button>
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <span class="icon">←</span>
                            <span>Go Back</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
};

export default NotFoundPage;
