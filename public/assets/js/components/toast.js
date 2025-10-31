/**
 * Toast Notification Component
 * Display temporary notification messages
 */

class Toast {
    constructor() {
        this.container = document.getElementById('toast-container');
        if (!this.container) {
            this.container = this.createContainer();
        }
    }

    /**
     * Create toast container
     */
    createContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
        return container;
    }

    /**
     * Show toast message
     */
    show(message, type = 'info', duration = 5000) {
        const toast = this.createToast(message, type);
        this.container.appendChild(toast);

        // Animate in
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => this.dismiss(toast), duration);
        }

        return toast;
    }

    /**
     * Create toast element
     */
    createToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };

        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <div class="toast-content">
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" aria-label="Close">×</button>
        `;

        // Add close handler
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => this.dismiss(toast));

        return toast;
    }

    /**
     * Dismiss toast
     */
    dismiss(toast) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    /**
     * Success toast
     */
    success(message, duration) {
        return this.show(message, 'success', duration);
    }

    /**
     * Error toast
     */
    error(message, duration) {
        return this.show(message, 'error', duration);
    }

    /**
     * Warning toast
     */
    warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    /**
     * Info toast
     */
    info(message, duration) {
        return this.show(message, 'info', duration);
    }
}

// Create singleton instance
const toast = new Toast();

export default toast;
