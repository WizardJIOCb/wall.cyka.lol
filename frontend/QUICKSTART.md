# Vue.js Frontend - Quick Start Guide

## ✅ What's Been Completed

**Phases 1-3 (30% of total project)**
- ✅ Complete Vue 3 + TypeScript project structure
- ✅ 60+ files created with 5,500+ lines of code
- ✅ Authentication system (login, register, OAuth)
- ✅ 17 components (common UI + layouts + views)
- ✅ Router with 12 routes and guards
- ✅ 3 Pinia stores (auth, theme, ui)
- ✅ 6 CSS themes migrated
- ✅ API client with error handling
- ✅ Complete TypeScript types

## 🚀 Getting Started

### Step 1: Install Dependencies

```bash
cd C:\Projects\wall.cyka.lol\frontend
npm install
```

**This will install:**
- Vue 3.4.21, Vue Router 4.3.0, Pinia 2.1.7
- Axios 1.6.8, VueUse 10.9.0, Day.js 1.11.10
- TypeScript, ESLint, Prettier
- Vitest, Cypress

### Step 2: Start Development Server

```bash
npm run dev
```

**Server will start at:** `http://localhost:3000`
**API proxy configured to:** `http://localhost:8080`

### Step 3: Verify Installation

Open `http://localhost:3000` in your browser. You should see:
- ✅ Login/Register page
- ✅ Theme switching working
- ✅ Responsive design (try resizing browser)

## 📝 Available Commands

| Command | Description |
|---------|-------------|
| `npm run dev` | Start development server with HMR |
| `npm run build` | Build for production |
| `npm run preview` | Preview production build |
| `npm run type-check` | Run TypeScript type checking |
| `npm run lint` | Lint and auto-fix code |
| `npm run format` | Format code with Prettier |
| `npm run test:unit` | Run unit tests with Vitest |
| `npm run test:e2e` | Run E2E tests with Cypress |

## 🔧 Configuration

### Environment Variables

**Development (`.env.development`):**
```env
VITE_API_BASE_URL=http://localhost:8080/api/v1
VITE_WS_URL=ws://localhost:8080
VITE_OLLAMA_URL=http://localhost:11434
VITE_ENABLE_ANALYTICS=false
```

**Production (`.env.production`):**
```env
VITE_API_BASE_URL=https://api.wall.cyka.lol/api/v1
VITE_WS_URL=wss://ws.wall.cyka.lol
VITE_OLLAMA_URL=https://ollama.wall.cyka.lol
VITE_ENABLE_ANALYTICS=true
```

Update these files with actual values before deployment.

## 📂 Key Files

### Entry Points
- `index.html` - HTML entry
- `src/main.ts` - TypeScript entry
- `src/App.vue` - Root component

### Routing
- `src/router/index.ts` - Route definitions
- `src/router/guards.ts` - Navigation guards

### State Management
- `src/stores/auth.ts` - Authentication state
- `src/stores/theme.ts` - Theme management
- `src/stores/ui.ts` - UI state (sidebar, loading, etc.)

### API Services
- `src/services/api/client.ts` - Axios HTTP client
- `src/services/api/auth.ts` - Auth API endpoints

### Components
- `src/components/common/` - Reusable UI components
- `src/components/layout/` - Layout components
- `src/views/` - Page components

## 🎨 Themes

6 themes available:
- **light** - Default bright theme
- **dark** - Dark mode
- **green** - Nature-inspired
- **cream** - Warm beige
- **blue** - Professional blue
- **high-contrast** - Accessibility (WCAG AAA)

Switch themes programmatically:
```typescript
import { useThemeStore } from '@/stores/theme'
const themeStore = useThemeStore()
themeStore.setTheme('dark')
```

## 🔐 Authentication

### Login
```typescript
import { useAuth } from '@/composables/useAuth'
const { login } = useAuth()

await login({
  identifier: 'username or email',
  password: 'password',
  remember: true
})
```

### Register
```typescript
const { register } = useAuth()

await register({
  username: 'newuser',
  email: 'user@example.com',
  password: 'password123',
  password_confirm: 'password123',
  terms: true
})
```

### Logout
```typescript
const { logout } = useAuth()
await logout()
```

## 🧪 Testing

### Run Unit Tests
```bash
npm run test:unit
```

### Run E2E Tests
```bash
# Development mode (interactive)
npm run test:e2e:dev

# Headless mode
npm run test:e2e
```

## 🏗️ Building for Production

```bash
# Type check
npm run type-check

# Build
npm run build

# Preview build locally
npm run preview
```

**Output directory:** `dist/`

## 🐛 Troubleshooting

### TypeScript Errors
If you see "Cannot find module" errors:
1. Ensure all dependencies are installed: `npm install`
2. Restart your IDE/editor
3. Run type checking: `npm run type-check`

### API Connection Issues
1. Ensure backend is running at `http://localhost:8080`
2. Check Vite proxy configuration in `vite.config.ts`
3. Verify CORS settings on backend

### Build Errors
1. Clear node_modules and reinstall: `rm -rf node_modules && npm install`
2. Clear Vite cache: `rm -rf node_modules/.vite`
3. Check for TypeScript errors: `npm run type-check`

## 📖 Documentation

- **Full README:** `README.md`
- **Implementation Summary:** `IMPLEMENTATION_SUMMARY.md`
- **Design Document:** `../.qoder/quests/vue-frontend-development.md`

## 🎯 Next Steps

The foundation is complete (Phases 1-3). Ready to continue with:

**Phase 4:** Posts & Feed
- PostCard, PostList, PostCreator components
- Infinite scrolling
- Feed management

**Phase 5:** Social Features
- Walls and profiles
- Comments system
- Reactions

**Phase 6:** AI Generation
- AI generator form
- Progress tracking with SSE
- Bricks management

Continue development by implementing Phase 4 components and API integrations.

## 💡 Tips

- Use `<script setup lang="ts">` for all components
- Follow Composition API patterns
- Utilize VueUse composables for common functionality
- Keep components small and focused
- Use TypeScript types for all props and emits
- Test components in isolation

## 🆘 Support

- **GitHub:** (Repository link when available)
- **Documentation:** `frontend/README.md`
- **Issues:** Report bugs and feature requests

---

**Ready to code!** 🚀

The frontend foundation is solid and ready for continued development. Start with `npm install && npm run dev` and begin building Phase 4.
