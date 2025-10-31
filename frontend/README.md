# Wall Social Platform - Vue.js Frontend

Modern Vue.js 3 frontend for the Wall Social Platform, built with TypeScript, Vite, and Composition API.

## Project Status

### ✅ Phase 1: Foundation Setup (COMPLETE)
- Vue 3 + TypeScript + Vite project initialized
- Package.json with all dependencies configured
- TypeScript configuration (tsconfig.json)
- ESLint and Prettier setup
- Vitest configuration for unit testing
- Complete directory structure created
- Environment configuration files (.env.development, .env.production)
- Vite configuration with proxy and build optimization

### ✅ Phase 2: Core Infrastructure (IN PROGRESS)
- ✅ TypeScript type definitions (models, API, components)
- ✅ Vue Router with route definitions and navigation guards
- ✅ Utility functions (validation, formatting, helpers, constants)
- ⏳ Layout components (pending)
- ⏳ Common UI components (pending)
- ⏳ Theme system migration (pending)
- ⏳ App.vue and main.ts (pending)

### ⏳ Phase 3: Authentication (PENDING)
- API client service with Axios
- Pinia auth store
- useAuth composable
- Login and Register views
- OAuth integration

### ⏳ Remaining Phases (PENDING)
- Phase 4: Posts & Feed
- Phase 5: Social Features
- Phase 6: AI Generation
- Phase 7: Messaging
- Phase 8: Settings & Polish
- Phase 9: Testing & Optimization
- Phase 10: Deployment

## Quick Start

### Prerequisites
- Node.js 18+ installed
- npm or pnpm package manager

### Installation

```bash
# Navigate to frontend directory
cd frontend

# Install dependencies
npm install
# or
pnpm install

# Start development server
npm run dev
```

The development server will start at http://localhost:3000 and proxy API requests to http://localhost:8080.

### Available Scripts

```bash
npm run dev          # Start development server
npm run build        # Build for production
npm run preview      # Preview production build
npm run type-check   # Run TypeScript type checking
npm run lint         # Lint and fix code
npm run format       # Format code with Prettier
npm run test:unit    # Run unit tests
npm run test:e2e     # Run E2E tests
```

## Project Structure

```
frontend/
├── public/                 # Static assets
├── src/
│   ├── assets/            # Images, styles
│   │   └── styles/
│   │       ├── base/      # Reset, typography
│   │       └── themes/    # 6 theme CSS files
│   ├── components/        # Vue components
│   │   ├── common/        # Reusable UI components
│   │   ├── layout/        # Layout components
│   │   ├── posts/         # Post-related components
│   │   ├── comments/      # Comment components
│   │   ├── ai/            # AI generation components
│   │   └── messaging/     # Messaging components
│   ├── composables/       # Vue composables
│   ├── layouts/           # Page layouts
│   ├── views/             # Page components
│   ├── router/            # Vue Router configuration
│   ├── stores/            # Pinia stores
│   ├── services/          # API services
│   │   └── api/           # API endpoint modules
│   ├── types/             # TypeScript type definitions
│   ├── utils/             # Utility functions
│   ├── App.vue            # Root component
│   ├── main.ts            # Application entry point
│   └── env.d.ts           # TypeScript env declarations
├── tests/
│   ├── unit/              # Unit tests
│   └── e2e/               # E2E tests
├── index.html             # HTML entry point
├── vite.config.ts         # Vite configuration
├── tsconfig.json          # TypeScript configuration
├── package.json           # Dependencies and scripts
└── README.md              # This file
```

## Technology Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| Vue.js | 3.4+ | Progressive JavaScript framework |
| TypeScript | 5.0+ | Type-safe JavaScript |
| Vite | 5.0+ | Build tool and dev server |
| Vue Router | 4.0+ | Client-side routing |
| Pinia | 2.1+ | State management |
| Axios | 1.6+ | HTTP client |
| VueUse | 10.9+ | Composition utilities |
| Day.js | 1.11+ | Date manipulation |
| Vitest | 1.4+ | Unit testing |
| Cypress | 13.7+ | E2E testing |

## Features

### Planned Features
- ✅ Modern Vue 3 Composition API
- ✅ TypeScript for type safety
- ✅ Responsive design (mobile-first)
- ✅ 6 theme system (light, dark, green, cream, blue, high-contrast)
- ⏳ Authentication (login, register, OAuth)
- ⏳ Real-time updates (SSE, WebSocket)
- ⏳ Infinite scroll feed
- ⏳ Post creation with media upload
- ⏳ AI application generation
- ⏳ Comments and reactions
- ⏳ Direct messaging
- ⏳ Notifications
- ⏳ User profiles and walls
- ⏳ Search and discovery

### Routes

| Route | Component | Access | Description |
|-------|-----------|--------|-------------|
| `/` | HomeView | Protected | Main feed |
| `/login` | LoginView | Guest only | User login |
| `/register` | RegisterView | Guest only | User registration |
| `/wall/:wallId` | WallView | Protected | Wall page |
| `/profile/:username?` | ProfileView | Protected | User profile |
| `/discover` | DiscoverView | Protected | Explore content |
| `/messages/:conversationId?` | MessagesView | Protected | Direct messages |
| `/notifications` | NotificationsView | Protected | Notifications |
| `/settings` | SettingsView | Protected | User settings |
| `/ai/:jobId?` | AIGenerateView | Protected | AI generation |

## Development Guidelines

### Code Style
- Use Composition API with `<script setup>`
- TypeScript strict mode enabled
- Follow ESLint and Prettier rules
- Component naming: PascalCase
- File naming: kebab-case
- Use composables for reusable logic

### State Management
- Use Pinia for global state
- Local state with `ref()` and `reactive()`
- Computed properties for derived state
- Actions for async operations

### Component Structure
```vue
<script setup lang="ts">
// Imports
// Props
// Composables
// State
// Computed
// Methods
// Lifecycle hooks
</script>

<template>
  <!-- Template -->
</template>

<style scoped>
/* Component styles */
</style>
```

## API Integration

Backend API runs at `http://localhost:8080` with 77 REST endpoints.

Base URL: `/api/v1`

### Key Endpoints
- `POST /auth/login` - User login
- `POST /auth/register` - User registration
- `GET /walls` - List walls
- `GET /walls/:id/posts` - Get wall posts
- `POST /walls/:id/posts` - Create post
- `POST /ai/generate` - Generate AI application
- `GET /ai/jobs/:id/status` - AI job status (SSE)

## Environment Variables

### Development (.env.development)
```env
VITE_API_BASE_URL=http://localhost:8080/api/v1
VITE_WS_URL=ws://localhost:8080
VITE_OLLAMA_URL=http://localhost:11434
VITE_ENABLE_ANALYTICS=false
```

### Production (.env.production)
```env
VITE_API_BASE_URL=https://api.wall.cyka.lol/api/v1
VITE_WS_URL=wss://ws.wall.cyka.lol
VITE_OLLAMA_URL=https://ollama.wall.cyka.lol
VITE_ENABLE_ANALYTICS=true
```

## Next Steps

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Continue Phase 2 Implementation**
   - Create layout components (Default, Auth, Minimal)
   - Build common UI components (Button, Input, Modal, Toast)
   - Migrate CSS themes from existing frontend
   - Create App.vue and main.ts

3. **Phase 3: Authentication**
   - Implement API client service
   - Create auth store and composable
   - Build login and register views

4. **Backend Integration**
   - Ensure backend is running at http://localhost:8080
   - Test API endpoints
   - Verify authentication flow

## Documentation

- [Vue.js Documentation](https://vuejs.org)
- [TypeScript Guide](https://www.typescriptlang.org/docs)
- [Vite Guide](https://vitejs.dev/guide/)
- [Pinia Documentation](https://pinia.vuejs.org)
- [Vue Router Guide](https://router.vuejs.org)

## License

MIT License - see LICENSE file for details

---

**Author:** Калимуллин Родион Данирович  
**Project:** Wall Social Platform Frontend  
**Created:** November 1, 2025  
**Status:** In Development - Phase 2
