import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

/**
 * Authentication guard - redirects to login if user is not authenticated
 */
export const requireAuth = (
  to: RouteLocationNormalized,
  _from: RouteLocationNormalized,
  next: NavigationGuardNext
) => {
  const authStore = useAuthStore()

  if (!authStore.isAuthenticated) {
    next({
      name: 'login',
      query: { redirect: to.fullPath }
    })
  } else {
    next()
  }
}

/**
 * Guest guard - redirects to home if user is already authenticated
 */
export const requireGuest = (
  _to: RouteLocationNormalized,
  _from: RouteLocationNormalized,
  next: NavigationGuardNext
) => {
  const authStore = useAuthStore()

  if (authStore.isAuthenticated) {
    next({ name: 'home' })
  } else {
    next()
  }
}

/**
 * Global before each guard
 */
export const setupGlobalGuards = (router: any) => {
  router.beforeEach(async (_to: RouteLocationNormalized, _from: RouteLocationNormalized, next: NavigationGuardNext) => {
    const authStore = useAuthStore()

    // Initialize auth store on first navigation
    if (!authStore.initialized) {
      await authStore.init()
    }

    next()
  })

  // After each guard - scroll to top
  router.afterEach(() => {
    window.scrollTo(0, 0)
  })
}
