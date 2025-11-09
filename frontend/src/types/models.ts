// User related types
export interface User {
  id: number
  username: string
  email: string
  display_name?: string
  bio?: string
  avatar_url?: string
  cover_image_url?: string
  location?: string
  website?: string
  is_verified: boolean
  bricks_balance: number
  created_at: string
  updated_at: string
}

// Post related types
export interface Post {
  id: number
  wall_id: number
  user_id: number
  content: string
  content_html?: string
  post_type: 'text' | 'media' | 'ai_app' | 'shared'
  visibility: 'public' | 'friends' | 'private'
  ai_application_id?: number
  original_post_id?: number
  reactions_count: number
  comments_count: number
  shares_count: number
  created_at: string
  updated_at: string
  user?: User
  media?: MediaAttachment[]
  ai_application?: AIApplication
}

// Wall related types
export interface Wall {
  id: number
  user_id: number
  name: string
  description?: string
  cover_image_url?: string
  is_default: boolean
  visibility: 'public' | 'friends' | 'private'
  subscribers_count: number
  posts_count: number
  created_at: string
  updated_at: string
  user?: User
}

// Comment related types
export interface Comment {
  id: number
  post_id: number
  user_id: number
  parent_comment_id?: number
  content: string
  reactions_count: number
  replies_count: number
  created_at: string
  updated_at: string
  user?: User
  replies?: Comment[]
}

// Reaction related types
export type ReactionType = 'like' | 'dislike' | 'love' | 'haha' | 'wow' | 'sad' | 'angry'

export interface Reaction {
  id: number
  user_id: number
  entity_type: 'post' | 'comment'
  entity_id: number
  reaction_type: ReactionType
  created_at: string
  user?: User
}

// Media related types
export interface MediaAttachment {
  id: number
  post_id: number
  file_path: string
  file_type: 'image' | 'video' | 'audio' | 'document'
  file_size: number
  mime_type: string
  width?: number
  height?: number
  duration?: number
  thumbnail_path?: string
  created_at: string
}

// AI Application related types
export interface AIApplication {
  id: number
  user_id: number
  prompt: string
  code_html?: string
  code_css?: string
  code_javascript?: string
  preview_image_url?: string
  generation_status: 'pending' | 'processing' | 'completed' | 'failed'
  generation_error?: string
  tokens_used: number
  bricks_cost: number
  parent_app_id?: number
  fork_of_app_id?: number
  remixes_count: number
  views_count: number
  created_at: string
  updated_at: string
  user?: User
}

// Notification related types
export type NotificationType = 
  | 'post_reaction'
  | 'comment'
  | 'mention'
  | 'follow'
  | 'friend_request'
  | 'ai_completion'
  | 'message'
  | 'system'

export interface Notification {
  id: number
  user_id: number
  type: NotificationType
  title: string
  message: string
  data?: Record<string, any>
  is_read: boolean
  created_at: string
}

// Messaging related types
export interface Conversation {
  id: number
  type: 'private' | 'group'
  name?: string
  avatar_url?: string
  last_message?: Message
  unread_count: number
  created_at: string
  updated_at: string
  participants?: User[]
}

export interface Message {
  id: number
  conversation_id: number
  sender_id: number
  content?: string
  message_type: 'text' | 'media' | 'shared_post'
  shared_post_id?: number
  is_read: boolean
  created_at: string
  updated_at: string
  sender?: User
  media?: MediaAttachment[]
}

// Bricks transaction types
export interface BricksTransaction {
  id: number
  user_id: number
  amount: number
  transaction_type: 'purchase' | 'claim' | 'spend' | 'refund' | 'bonus'
  description?: string
  ai_application_id?: number
  created_at: string
}

// Pagination types
export interface PaginatedResponse<T> {
  data: T[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    total_pages: number
    has_more: boolean
  }
}

// Filter and sort types
export interface PostFilters {
  content_type?: 'all' | 'text' | 'media' | 'ai_apps'
  time_range?: 'today' | 'week' | 'month' | 'all'
  source?: 'following' | 'all' | number // wall_id
  sort_by?: 'recent' | 'popular' | 'trending'
}
