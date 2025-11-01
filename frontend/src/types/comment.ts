export interface Comment {
  comment_id: number
  post_id: number
  author_id: number
  author_username: string
  author_name: string
  author_avatar: string | null
  parent_comment_id: number | null
  content_text: string
  content_html: string
  depth_level: number
  reply_count: number
  reaction_count: number
  like_count: number
  dislike_count: number
  is_edited: boolean
  is_hidden: boolean
  replies: Comment[]
  created_at: string
  updated_at: string
  user_reaction?: string | null
}

export interface CommentReaction {
  type: string
  count: number
}

export interface CommentReactionUser {
  user_id: number
  username: string
  display_name: string
  avatar_url: string | null
  reaction_type: string
  created_at: string
}

export interface CreateCommentData {
  content: string
  parent_comment_id?: number
}

export interface UpdateCommentData {
  content: string
}

export interface CommentListResponse {
  comments: Comment[]
  count: number
  has_more: boolean
}

export interface CommentReactionResponse {
  action: 'created' | 'updated' | 'removed'
  reaction_type: string
  reaction_count: number
  like_count: number
  dislike_count: number
}
