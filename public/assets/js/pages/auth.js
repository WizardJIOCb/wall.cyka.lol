/**
 * Authentication Page Script
 * Handle login and registration
 */

import authAPI from '../api/auth.js';
import toast from '../components/toast.js';

class AuthPage {
    constructor() {
        this.loginForm = document.getElementById('login-form');
        this.registerForm = document.getElementById('register-form');
        this.loginTab = document.getElementById('login-tab');
        this.registerTab = document.getElementById('register-tab');
        this.loginPanel = document.getElementById('login-panel');
        this.registerPanel = document.getElementById('register-panel');
        
        this.init();
    }

    init() {
        // Setup tab switching
        this.setupTabs();
        
        // Setup form submission
        this.setupForms();
        
        // Setup password toggles
        this.setupPasswordToggles();
        
        // Setup password strength
        this.setupPasswordStrength();
        
        // Setup OAuth buttons
        this.setupOAuth();
    }

    /**
     * Setup tab switching
     */
    setupTabs() {
        this.loginTab.addEventListener('click', () => {
            this.switchTab('login');
        });

        this.registerTab.addEventListener('click', () => {
            this.switchTab('register');
        });
    }

    /**
     * Switch between login and register tabs
     */
    switchTab(tab) {
        if (tab === 'login') {
            this.loginTab.classList.add('active');
            this.registerTab.classList.remove('active');
            this.loginPanel.classList.add('active');
            this.registerPanel.classList.add('active');
            this.loginPanel.removeAttribute('hidden');
            this.registerPanel.setAttribute('hidden', '');
            this.loginTab.setAttribute('aria-selected', 'true');
            this.registerTab.setAttribute('aria-selected', 'false');
        } else {
            this.registerTab.classList.add('active');
            this.loginTab.classList.remove('active');
            this.registerPanel.classList.add('active');
            this.loginPanel.classList.add('active');
            this.registerPanel.removeAttribute('hidden');
            this.loginPanel.setAttribute('hidden', '');
            this.registerTab.setAttribute('aria-selected', 'true');
            this.loginTab.setAttribute('aria-selected', 'false');
        }
    }

    /**
     * Setup form submission handlers
     */
    setupForms() {
        // Login form
        this.loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleLogin();
        });

        // Register form
        this.registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleRegister();
        });
    }

    /**
     * Handle login submission
     */
    async handleLogin() {
        const formData = new FormData(this.loginForm);
        const data = {
            identifier: formData.get('identifier'),
            password: formData.get('password')
        };

        // Validate
        if (!data.identifier || !data.password) {
            toast.error('Please fill in all fields');
            return;
        }

        // Show loading state
        const submitBtn = this.loginForm.querySelector('button[type="submit"]');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline-block';

        try {
            const response = await authAPI.login(data);
            
            if (response.success) {
                // Store token
                localStorage.setItem('session_token', response.data.session_token);
                
                // Show success message
                toast.success('Login successful! Redirecting...');
                
                // Redirect to app
                setTimeout(() => {
                    window.location.href = '/app.html';
                }, 1000);
            } else {
                toast.error(response.message || 'Login failed');
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
            }
        } catch (error) {
            toast.error(error.message || 'Login failed');
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
        }
    }

    /**
     * Handle registration submission
     */
    async handleRegister() {
        const formData = new FormData(this.registerForm);
        const data = {
            username: formData.get('username'),
            email: formData.get('email'),
            password: formData.get('password'),
            password_confirm: formData.get('password_confirm'),
            display_name: formData.get('display_name')
        };

        // Validate
        if (!data.username || !data.email || !data.password) {
            toast.error('Please fill in all required fields');
            return;
        }

        if (!formData.get('terms')) {
            toast.error('Please accept the Terms of Service');
            return;
        }

        // Show loading state
        const submitBtn = this.registerForm.querySelector('button[type="submit"]');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline-block';

        try {
            const response = await authAPI.register(data);
            
            if (response.success) {
                // Store token
                localStorage.setItem('session_token', response.data.session_token);
                
                // Show success message
                toast.success('Registration successful! Redirecting...');
                
                // Redirect to app
                setTimeout(() => {
                    window.location.href = '/app.html';
                }, 1000);
            } else {
                toast.error(response.message || 'Registration failed');
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
            }
        } catch (error) {
            toast.error(error.message || 'Registration failed');
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
        }
    }

    /**
     * Setup password visibility toggles
     */
    setupPasswordToggles() {
        document.querySelectorAll('.password-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                
                if (input) {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    btn.innerHTML = type === 'password' ? '<span class="icon">👁️</span>' : '<span class="icon">👁️‍🗨️</span>';
                }
            });
        });
    }

    /**
     * Setup password strength indicator
     */
    setupPasswordStrength() {
        const passwordInput = document.getElementById('register-password');
        const strengthFill = document.querySelector('.strength-fill');
        const strengthText = document.querySelector('.strength-text');

        if (passwordInput && strengthFill && strengthText) {
            passwordInput.addEventListener('input', () => {
                const password = passwordInput.value;
                const strength = this.calculatePasswordStrength(password);
                
                strengthFill.setAttribute('data-strength', strength);
                
                const labels = ['Weak', 'Fair', 'Good', 'Strong'];
                strengthText.textContent = labels[strength - 1] || 'Weak';
            });
        }
    }

    /**
     * Calculate password strength (1-4)
     */
    calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z\d]/.test(password)) strength++;
        
        return Math.min(Math.ceil(strength / 1.5), 4);
    }

    /**
     * Setup OAuth buttons
     */
    setupOAuth() {
        // Google OAuth
        const googleBtns = document.querySelectorAll('#google-login, #google-signup');
        googleBtns.forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    const response = await authAPI.getGoogleAuthUrl();
                    if (response.success && response.data.url) {
                        window.location.href = response.data.url;
                    }
                } catch (error) {
                    toast.error('Google OAuth not configured');
                }
            });
        });

        // Yandex OAuth
        const yandexBtns = document.querySelectorAll('#yandex-login, #yandex-signup');
        yandexBtns.forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    const response = await authAPI.getYandexAuthUrl();
                    if (response.success && response.data.url) {
                        window.location.href = response.data.url;
                    }
                } catch (error) {
                    toast.error('Yandex OAuth not configured');
                }
            });
        });

        // Telegram (not implemented yet)
        const telegramBtns = document.querySelectorAll('#telegram-login, #telegram-signup');
        telegramBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                toast.info('Telegram authentication coming soon!');
            });
        });
    }
}

// Initialize auth page
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new AuthPage();
    });
} else {
    new AuthPage();
}
