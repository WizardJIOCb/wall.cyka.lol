<template>
  <div class="auth-layout">
    <div class="auth-container">
      <!-- Logo and Header -->
      <div class="auth-header">
        <router-link to="/" class="logo-link">
          <span class="logo-icon">🧱</span>
          <h1 class="logo-text">Wall Social Platform</h1>
        </router-link>
        <p class="tagline">AI-Powered Social Network</p>
      </div>

      <!-- Content Slot -->
      <div class="auth-content">
        <slot />
      </div>

      <!-- Features Showcase -->
      <div class="auth-features">
        <div class="feature">
          <span class="feature-icon">🤖</span>
          <h3>AI Generation</h3>
          <p>Create web apps using AI prompts</p>
        </div>
        <div class="feature">
          <span class="feature-icon">🔄</span>
          <h3>Remix & Fork</h3>
          <p>Build upon others' creations</p>
        </div>
        <div class="feature">
          <span class="feature-icon">💬</span>
          <h3>Social Features</h3>
          <p>Comments, reactions, messaging</p>
        </div>
      </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container" aria-live="polite"></div>
  </div>
</template>

<script setup lang="ts">
// Auth layout for login/register pages
</script>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--background);
  padding: var(--spacing-4);
}

.auth-container {
  max-width: 480px;
  width: 100%;
}

.auth-header {
  text-align: center;
  margin-bottom: var(--spacing-6);
}

.logo-link {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-2);
  text-decoration: none;
  color: var(--text-primary);
}

.logo-icon {
  font-size: 2.5rem;
}

.logo-text {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
}

.tagline {
  margin-top: var(--spacing-2);
  color: var(--text-secondary);
  font-size: 0.95rem;
}

.auth-content {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-lg);
}

.auth-features {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-4);
  margin-top: var(--spacing-8);
}

.feature {
  text-align: center;
}

.feature-icon {
  font-size: 2rem;
  display: block;
  margin-bottom: var(--spacing-2);
}

.feature h3 {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0 0 var(--spacing-1);
  color: var(--text-primary);
}

.feature p {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin: 0;
}

@media (max-width: 640px) {
  .auth-features {
    grid-template-columns: 1fr;
  }
}
</style>
