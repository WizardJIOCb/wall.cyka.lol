/// <reference types="vite/client" />

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
  readonly VITE_WS_URL: string
  readonly VITE_OLLAMA_URL: string
  readonly VITE_ENABLE_ANALYTICS: string
  readonly VITE_SENTRY_DSN: string
  readonly VITE_OAUTH_GOOGLE_CLIENT_ID: string
  readonly VITE_OAUTH_YANDEX_CLIENT_ID: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
