<template>
  <div class="auth-form-container">
    <!-- Tab Switcher -->
    <div class="auth-tabs">
      <button
        class="auth-tab"
        :class="{ active: activeTab === 'login' }"
        @click="activeTab = 'login'"
      >
        {{ $t('auth.login.loginButton') }}
      </button>
      <button
        class="auth-tab"
        :class="{ active: activeTab === 'register' }"
        @click="activeTab = 'register'"
      >
        {{ $t('common.actions.register') }}
      </button>
    </div>

    <!-- Login Form -->
    <form v-if="activeTab === 'login'" class="auth-form" @submit.prevent="handleLogin">
      <h2 class="form-title">{{ $t('auth.login.subtitle') }}</h2>

      <AppInput
        v-model="loginForm.identifier"
        :label="$t('auth.login.username')"
        type="text"
        autocomplete="username"
        required
        :error="loginErrors.identifier"
      />

      <AppInput
        v-model="loginForm.password"
        :label="$t('auth.login.password')"
        type="password"
        autocomplete="current-password"
        required
        :error="loginErrors.password"
      />

      <div class="form-checkbox">
        <input v-model="loginForm.remember" type="checkbox" id="remember-me" />
        <label for="remember-me">{{ $t('auth.login.rememberMe') }}</label>
      </div>

      <AppButton type="submit" variant="primary" size="lg" block :loading="loading">
        {{ loading ? $t('auth.login.loggingIn') : $t('auth.login.loginButton') }}
      </AppButton>

      <div class="form-footer">
        <a href="#/forgot-password" class="link-secondary">{{ $t('auth.login.forgotPassword') }}</a>
      </div>
    </form>

    <!-- Register Form -->
    <form v-else class="auth-form" @submit.prevent="handleRegister">
      <h2 class="form-title">{{ $t('auth.register.title') }}</h2>

      <AppInput
        v-model="registerForm.username"
        :label="$t('auth.register.username')"
        type="text"
        autocomplete="username"
        required
        :error="registerErrors.username"
        help="3-50 characters, letters, numbers, underscore only"
      />

      <AppInput
        v-model="registerForm.email"
        :label="$t('auth.register.email')"
        type="email"
        autocomplete="email"
        required
        :error="registerErrors.email"
      />

      <AppInput
        v-model="registerForm.password"
        :label="$t('auth.register.password')"
        type="password"
        autocomplete="new-password"
        required
        :error="registerErrors.password"
        help="Minimum 8 characters"
      />

      <div v-if="registerForm.password" class="password-strength">
        <div class="strength-bar">
          <div
            class="strength-fill"
            :style="{ width: `${passwordStrength * 20}%` }"
            :data-strength="passwordStrength"
          ></div>
        </div>
        <span class="strength-text">{{ passwordStrengthText }}</span>
      </div>

      <AppInput
        v-model="registerForm.password_confirm"
        :label="$t('auth.register.confirmPassword')"
        type="password"
        autocomplete="new-password"
        required
        :error="registerErrors.password_confirm"
      />

      <AppInput
        v-model="registerForm.display_name"
        label="Display Name (Optional)"
        type="text"
        autocomplete="name"
        :error="registerErrors.display_name"
      />

      <div class="form-checkbox">
        <input v-model="registerForm.terms" type="checkbox" id="terms-accept" required />
        <label for="terms-accept">
          {{ $t('auth.register.terms') }} <a href="/terms" target="_blank">{{ $t('auth.register.termsLink') }}</a>
        </label>
        <span v-if="registerErrors.terms" class="form-error">{{ registerErrors.terms }}</span>
      </div>

      <AppButton type="submit" variant="primary" size="lg" block :loading="loading">
        {{ loading ? $t('auth.register.registering') : $t('auth.register.registerButton') }}
      </AppButton>
    </form>

    <!-- OAuth Options -->
    <div class="oauth-section">
      <div class="divider">
        <span>Or continue with</span>
      </div>

      <div class="oauth-buttons">
        <button class="btn-oauth btn-google" type="button" @click="handleOAuth('google')">
          <span class="icon">G</span>
          <span>{{ $t('auth.oauth.googleLogin').replace('Sign in with ', '') }}</span>
        </button>
        <button class="btn-oauth btn-yandex" type="button" @click="handleOAuth('yandex')">
          <span class="icon">Я</span>
          <span>{{ $t('auth.oauth.yandexLogin').replace('Sign in with ', '').replace('Войти через ', '') }}</span>
        </button>
        <button class="btn-oauth btn-telegram" type="button" @click="handleOAuth('telegram')">
          <span class="icon">✈️</span>
          <span>{{ $t('auth.oauth.telegramLogin').replace('Sign in with ', '').replace('Войти через ', '') }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useAuth } from '@/composables/useAuth'
import AppButton from '@/components/common/AppButton.vue'
import AppInput from '@/components/common/AppInput.vue'
import { validateEmail, validateUsername, validatePassword } from '@/utils/validation'
import authAPI from '@/services/api/auth'

const { login, register } = useAuth()

const activeTab = ref<'login' | 'register'>('login')
const loading = ref(false)

// Login form
const loginForm = reactive({
  identifier: '',
  password: '',
  remember: false
})

const loginErrors = reactive({
  identifier: '',
  password: ''
})

// Register form
const registerForm = reactive({
  username: '',
  email: '',
  password: '',
  password_confirm: '',
  display_name: '',
  terms: false
})

const registerErrors = reactive({
  username: '',
  email: '',
  password: '',
  password_confirm: '',
  display_name: '',
  terms: ''
})

// Password strength
const passwordStrength = computed(() => {
  const result = validatePassword(registerForm.password)
  return result.strength
})

const passwordStrengthText = computed(() => {
  const strength = passwordStrength.value
  if (strength === 0) return 'Very Weak'
  if (strength === 1) return 'Weak'
  if (strength === 2) return 'Fair'
  if (strength === 3) return 'Good'
  if (strength >= 4) return 'Strong'
  return ''
})

// Validate login form
const validateLoginForm = (): boolean => {
  loginErrors.identifier = ''
  loginErrors.password = ''

  if (!loginForm.identifier) {
    loginErrors.identifier = 'Username or email is required'
    return false
  }

  if (!loginForm.password) {
    loginErrors.password = 'Password is required'
    return false
  }

  return true
}

// Validate register form
const validateRegisterForm = (): boolean => {
  registerErrors.username = ''
  registerErrors.email = ''
  registerErrors.password = ''
  registerErrors.password_confirm = ''
  registerErrors.terms = ''

  if (!registerForm.username || !validateUsername(registerForm.username)) {
    registerErrors.username = 'Invalid username format'
    return false
  }

  if (!registerForm.email || !validateEmail(registerForm.email)) {
    registerErrors.email = 'Invalid email address'
    return false
  }

  const passwordValidation = validatePassword(registerForm.password)
  if (!passwordValidation.valid) {
    registerErrors.password = 'Password must be at least 8 characters'
    return false
  }

  if (registerForm.password !== registerForm.password_confirm) {
    registerErrors.password_confirm = 'Passwords do not match'
    return false
  }

  if (!registerForm.terms) {
    registerErrors.terms = 'You must agree to the terms'
    return false
  }

  return true
}

// Handle login
const handleLogin = async () => {
  if (!validateLoginForm()) return

  loading.value = true
  try {
    await login(loginForm)
  } catch (error: any) {
    loginErrors.password = error.message || 'Login failed'
  } finally {
    loading.value = false
  }
}

// Handle register
const handleRegister = async () => {
  if (!validateRegisterForm()) return

  loading.value = true
  try {
    await register(registerForm)
  } catch (error: any) {
    if (error.errors) {
      Object.assign(registerErrors, error.errors)
    } else {
      registerErrors.username = error.message || 'Registration failed'
    }
  } finally {
    loading.value = false
  }
}

// Handle OAuth
const handleOAuth = (provider: 'google' | 'yandex' | 'telegram') => {
  const oauthURL = authAPI.getOAuthURL(provider)
  window.location.href = oauthURL
}
</script>

<style scoped>
.auth-form-container {
  width: 100%;
}

.auth-tabs {
  display: flex;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-6);
}

.auth-tab {
  flex: 1;
  padding: var(--spacing-3);
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--text-secondary);
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.auth-tab.active {
  color: var(--primary);
  border-bottom-color: var(--primary);
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.form-title {
  margin: 0 0 var(--spacing-4);
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
}

.form-checkbox {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.form-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
}

.form-checkbox label {
  font-size: 0.9rem;
}

.form-footer {
  text-align: center;
  margin-top: var(--spacing-2);
}

.link-secondary {
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.9rem;
}

.link-secondary:hover {
  color: var(--primary);
}

.password-strength {
  margin-top: calc(-1 * var(--spacing-2));
}

.strength-bar {
  height: 4px;
  background: var(--surface-hover);
  border-radius: var(--radius-full);
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  transition: all 0.3s ease;
}

.strength-fill[data-strength="0"],
.strength-fill[data-strength="1"] {
  background: var(--danger);
}

.strength-fill[data-strength="2"] {
  background: var(--warning);
}

.strength-fill[data-strength="3"],
.strength-fill[data-strength="4"],
.strength-fill[data-strength="5"] {
  background: var(--success);
}

.strength-text {
  display: block;
  margin-top: var(--spacing-1);
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.oauth-section {
  margin-top: var(--spacing-6);
}

.divider {
  display: flex;
  align-items: center;
  margin-bottom: var(--spacing-4);
  text-align: center;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  border-bottom: 1px solid var(--border);
}

.divider span {
  padding: 0 var(--spacing-3);
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.oauth-buttons {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-3);
}

.btn-oauth {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn-oauth:hover {
  background: var(--surface-hover);
}

.btn-oauth .icon {
  font-size: 1.25rem;
}

@media (max-width: 640px) {
  .oauth-buttons {
    grid-template-columns: 1fr;
  }
}
</style>
