// API request and response types

export interface LoginRequest {
  identifier: string // username or email
  password: string
  remember?: boolean
}

export interface RegisterRequest {
  username: string
  email: string
  password: string
  password_confirm: string
  display_name?: string
  terms: boolean
}

export interface AuthResponse {
  success: boolean
  data: {
    user: import('./models').User
    session_token: string
  }
  message?: string
}

export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  message?: string
  errors?: Record<string, string[]>
}

export interface ApiError {
  status: number
  message: string
  errors?: Record<string, string[]>
}

export interface CreatePostRequest {
  wall_id: number
  content: string
  post_type?: 'text' | 'media' | 'ai_app'
  visibility?: 'public' | 'friends' | 'private'
  ai_application_id?: number
  media_files?: File[]
}

export interface CreateCommentRequest {
  post_id: number
  content: string
  parent_comment_id?: number
}

export interface AIGenerateRequest {
  prompt: string
  parent_app_id?: number
  fork_of_app_id?: number
}

export interface AIJobStatusResponse {
  job_id: string
  status: 'queued' | 'processing' | 'completed' | 'failed'
  queue_position?: number
  estimated_wait_time?: number
  progress?: number
  phase?: 'analyzing' | 'generating' | 'finalizing'
  tokens_used?: number
  bricks_cost?: number
  result?: {
    application_id: number
    code_html: string
    code_css: string
    code_javascript: string
  }
  error?: string
  created_at: string
  started_at?: string
  completed_at?: string
}

export interface SendMessageRequest {
  conversation_id: number
  content?: string
  message_type?: 'text' | 'media' | 'shared_post'
  shared_post_id?: number
  media_files?: File[]
}