/**
 * Theme Service
 * Manage theme switching and persistence
 */

class ThemeService {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.themeStylesheet = document.getElementById('theme-stylesheet');
    }

    /**
     * Get theme names
     */
    getThemes() {
        return ['light', 'dark', 'green', 'cream', 'blue', 'high-contrast'];
    }

    /**
     * Get stored theme from localStorage
     */
    getStoredTheme() {
        return localStorage.getItem('theme');
    }

    /**
     * Set theme
     */
    setTheme(themeName) {
        if (!this.getThemes().includes(themeName)) {
            console.warn('Invalid theme:', themeName);
            return;
        }

        this.currentTheme = themeName;
        
        // Update HTML data attribute
        document.documentElement.setAttribute('data-theme', themeName);
        
        // Update stylesheet link
        if (this.themeStylesheet) {
            this.themeStylesheet.href = `/assets/css/themes/${themeName}.css`;
        }
        
        // Store in localStorage
        localStorage.setItem('theme', themeName);
        
        // Dispatch event
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: themeName } }));
    }

    /**
     * Get current theme
     */
    getCurrentTheme() {
        return this.currentTheme;
    }

    /**
     * Initialize theme
     */
    init() {
        this.setTheme(this.currentTheme);
    }

    /**
     * Toggle between light and dark
     */
    toggleDarkMode() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }
}

// Create singleton instance
const themeService = new ThemeService();

export default themeService;
