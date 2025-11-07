import { createRouter, createWebHistory } from 'vue-router'
import { setupGlobalGuards, requireAuth, requireGuest } from './guards'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Auth routes
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      beforeEnter: requireGuest,
      meta: { layout: 'auth', title: 'Login' }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      beforeEnter: requireGuest,
      meta: { layout: 'auth', title: 'Register' }
    },

    // Protected routes
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'Home' }
    },
    {
      path: '/wall',
      redirect: '/wall/me'
    },
    {
      path: '/wall/:wallId',
      name: 'wall',
      component: () => import('@/views/WallView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'Wall' },
      props: true
    },
    {
      path: '/profile/:username?',
      name: 'profile',
      component: () => import('@/views/ProfileView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'Profile' },
      props: true
    },
    {
      path: '/discover',
      name: 'discover',
      component: () => import('@/views/DiscoverView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'Discover' }
    },
    {
      path: '/messages/:conversationId?',
      name: 'messages',
      component: () => import('@/views/MessagesView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'Messages' },
      props: true
    },
    {
      path: '/notifications',
      name: 'notifications',
      component: () => import('@/views/NotificationsView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'Notifications' }
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('@/views/SettingsView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'Settings' }
    },
    {
      path: '/admin/bricks',
      name: 'admin-bricks',
      component: () => import('@/views/AdminBricksView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'Admin - Bricks Management', requireAdmin: true }
    },
    {
      path: '/ai/:jobId?',
      name: 'ai',
      component: () => import('@/views/AIGenerateView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'AI Generate' },
      props: true
    },
    {
      path: '/search',
      name: 'search',
      component: () => import('@/views/SearchView.vue'),
      beforeEnter: requireAuth,
      meta: { title: 'Search Results' },
      props: route => ({
        initialQuery: route.query.q || '',
        initialType: route.query.type || 'all',
        initialSort: route.query.sort || 'relevance'
      })
    },

    // 404 Not Found
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
      meta: { layout: 'minimal', title: 'Page Not Found' }
    }
  ]
})

// Setup global navigation guards
setupGlobalGuards(router)

// Update document title on route change
router.afterEach(to => {
  const baseTitle = 'Wall Social Platform'
  document.title = to.meta.title ? `${to.meta.title} | ${baseTitle}` : baseTitle
})

export default router
