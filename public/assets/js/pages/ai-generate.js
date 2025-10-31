/**
 * AI Generation Page
 * Create AI-powered applications (bricks)
 */

import toast from '../components/toast.js';
import authService from '../services/auth-service.js';

const AIGeneratePage = {
    async render(container) {
        container.innerHTML = `
            <div class="ai-generate-page">
                <div class="page-header">
                    <h1>🤖 AI Application Generator</h1>
                    <p class="text-secondary">Create custom AI applications for your wall</p>
                </div>

                <div class="ai-generator-container">
                    <div class="generator-form-section">
                        <form id="ai-generate-form" class="ai-generate-form">
                            <div class="form-group">
                                <label for="app-title">Application Title</label>
                                <input 
                                    type="text" 
                                    id="app-title" 
                                    name="title" 
                                    class="form-control" 
                                    placeholder="e.g., Daily Motivation Generator"
                                    required
                                    maxlength="100"
                                >
                                <small class="form-text">Give your AI app a descriptive name</small>
                            </div>

                            <div class="form-group">
                                <label for="app-prompt">AI Prompt Template</label>
                                <textarea 
                                    id="app-prompt" 
                                    name="prompt_template" 
                                    class="form-control" 
                                    rows="6"
                                    placeholder="Write a detailed prompt for the AI...
Example: Generate a motivational quote about {topic} in a {style} style"
                                    required
                                ></textarea>
                                <small class="form-text">Use {placeholders} for dynamic content</small>
                            </div>

                            <div class="form-group">
                                <label for="app-schedule">Generation Schedule</label>
                                <select id="app-schedule" name="schedule" class="form-control">
                                    <option value="manual">Manual (On-demand)</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="custom">Custom Schedule</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="app-visibility">Post Visibility</label>
                                <select id="app-visibility" name="visibility" class="form-control">
                                    <option value="public">Public</option>
                                    <option value="followers">Followers Only</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="auto_post" id="auto-post">
                                    <span>Automatically post generated content</span>
                                </label>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <span class="icon">🚀</span>
                                    <span>Create AI App</span>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="window.location.hash = '/'">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="generator-preview-section">
                        <h3>💡 Example Templates</h3>
                        <div class="template-examples">
                            <div class="template-card" data-template="motivation">
                                <h4>Daily Motivation</h4>
                                <p>Generate inspiring quotes and messages</p>
                                <button class="btn btn-sm btn-secondary use-template-btn">Use Template</button>
                            </div>

                            <div class="template-card" data-template="facts">
                                <h4>Fun Facts</h4>
                                <p>Share interesting facts daily</p>
                                <button class="btn btn-sm btn-secondary use-template-btn">Use Template</button>
                            </div>

                            <div class="template-card" data-template="poetry">
                                <h4>AI Poetry</h4>
                                <p>Create beautiful poems on demand</p>
                                <button class="btn btn-sm btn-secondary use-template-btn">Use Template</button>
                            </div>

                            <div class="template-card" data-template="tips">
                                <h4>Daily Tips</h4>
                                <p>Programming tips and best practices</p>
                                <button class="btn btn-sm btn-secondary use-template-btn">Use Template</button>
                            </div>
                        </div>

                        <div class="info-box">
                            <h4>🎯 How it works</h4>
                            <ul>
                                <li>Create a prompt template with your instructions</li>
                                <li>Use {placeholders} for dynamic content</li>
                                <li>Choose when to generate content</li>
                                <li>AI will create posts based on your template</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="my-ai-apps-section">
                    <h2>My AI Applications</h2>
                    <div id="ai-apps-list" class="ai-apps-grid">
                        <div class="loading-container">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.setupEventListeners();
        await this.loadMyApps();
    },

    setupEventListeners() {
        // Form submission
        const form = document.getElementById('ai-generate-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCreateApp(form);
            });
        }

        // Template buttons
        document.querySelectorAll('.use-template-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const card = e.target.closest('.template-card');
                const template = card.dataset.template;
                this.loadTemplate(template);
            });
        });
    },

    loadTemplate(templateName) {
        const templates = {
            motivation: {
                title: 'Daily Motivation Generator',
                prompt: 'Generate an inspiring and motivational quote about personal growth, success, or overcoming challenges. Make it uplifting and actionable.',
                schedule: 'daily'
            },
            facts: {
                title: 'Fun Facts Generator',
                prompt: 'Share an interesting and surprising fact about science, history, or nature. Make it educational and engaging.',
                schedule: 'daily'
            },
            poetry: {
                title: 'AI Poetry Creator',
                prompt: 'Write a short, beautiful poem about {topic}. Use vivid imagery and emotional language. Style: {style}',
                schedule: 'manual'
            },
            tips: {
                title: 'Programming Tips',
                prompt: 'Share a useful programming tip or best practice for {language} developers. Include a practical example.',
                schedule: 'daily'
            }
        };

        const template = templates[templateName];
        if (template) {
            document.getElementById('app-title').value = template.title;
            document.getElementById('app-prompt').value = template.prompt;
            document.getElementById('app-schedule').value = template.schedule;
            toast.success(`Template loaded: ${template.title}`);
        }
    },

    async handleCreateApp(form) {
        const formData = new FormData(form);
        const data = {
            title: formData.get('title'),
            prompt_template: formData.get('prompt_template'),
            schedule: formData.get('schedule'),
            visibility: formData.get('visibility'),
            auto_post: formData.get('auto_post') === 'on'
        };

        toast.info('Creating AI application...');

        try {
            // TODO: Implement API call
            // const response = await aiAPI.createApp(data);
            
            // Simulate success for now
            setTimeout(() => {
                toast.success('AI application created successfully!');
                form.reset();
                this.loadMyApps();
            }, 1000);

        } catch (error) {
            console.error('Create AI app error:', error);
            toast.error('Failed to create AI application');
        }
    },

    async loadMyApps() {
        const container = document.getElementById('ai-apps-list');
        
        // Simulate loading
        setTimeout(() => {
            container.innerHTML = `
                <div class="empty-state">
                    <p class="text-secondary">You haven't created any AI applications yet</p>
                    <p class="text-secondary">Create your first AI app above to get started!</p>
                </div>
            `;
        }, 500);
    }
};

export default AIGeneratePage;
