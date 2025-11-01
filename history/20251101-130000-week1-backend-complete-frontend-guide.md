# Week 1 Backend Complete - Frontend Implementation Guide

**Date:** 2025-11-01 13:00:00  
**Task:** Comments System Backend Complete + Frontend Implementation Guide  
**Phase:** Week 1 of Phase 5 (Social Features)  
**Status:** Backend ✅ Complete | Frontend 📋 Pending

---

## Summary

Week 1 backend implementation is **100% complete**. The backend now supports:
- ✅ 11 new comment-related API endpoints (80 total endpoints)
- ✅ Full CRUD operations for nested comments
- ✅ Comment reactions with toggle behavior
- ✅ Notification integration
- ✅ Comprehensive validation and security

**Next Step:** Implement frontend components to interact with these APIs.

---

## Backend Accomplishments

### Files Created/Modified

**Created:**
- `src/Controllers/CommentController.php` (534 lines)
- `history/20251101-125455-comments-backend-implementation.md` (609 lines)

**Modified:**
- `src/Models/Reaction.php` (+114 lines)
- `public/api.php` (+27 lines)

**Total:** 3 files modified, 1,284 lines of new code

### API Endpoints Ready

#### Comment CRUD (7 endpoints)
```
GET    /api/v1/posts/{postId}/comments
POST   /api/v1/posts/{postId}/comments
GET    /api/v1/comments/{commentId}
POST   /api/v1/comments/{commentId}/replies
PATCH  /api/v1/comments/{commentId}
DELETE /api/v1/comments/{commentId}
GET    /api/v1/comments/{commentId}/reactions/users
```

#### Comment Reactions (4 endpoints)
```
POST   /api/v1/comments/{commentId}/reactions
DELETE /api/v1/comments/{commentId}/reactions
GET    /api/v1/comments/{commentId}/reactions
GET    /api/v1/comments/{commentId}/reactions/users
```

---

## Frontend Implementation Guide

### Components to Create

The frontend implementation requires creating three main components in the Vue.js application located at `C:\Projects\wall.cyka.lol\frontend/src/components/`:

#### 1. CommentSection.vue

**Location:** `frontend/src/components/comments/CommentSection.vue`

**Purpose:** Container component that displays all comments for a post

**Key Features:**
- Fetches comments from API on mount
- Displays nested comment tree structure
- Toggles between flat/threaded view
- Handles "Load More" pagination
- Shows comment count
- Sort options (newest, oldest, most reactions)
- Real-time updates via polling

**Props:**
```typescript
interface Props {
  postId: number
  initialComments?: Comment[]
  allowComments: boolean
}
```

**API Integration:**
```typescript
// Fetch comments
const fetchComments = async () => {
  const response = await api.get(`/posts/${postId}/comments`, {
    params: { sort: sortBy.value, limit: 50 }
  })
  comments.value = response.data.comments
}

// Poll for new comments every 10 seconds
const pollInterval = setInterval(fetchComments, 10000)
```

**Template Structure:**
```vue
<div class="comment-section">
  <div class="comment-section-header">
    <h3>Comments ({{ commentCount }})</h3>
    <div class="comment-controls">
      <select v-model="sortBy">
        <option value="created_asc">Oldest First</option>
        <option value="created_desc">Newest First</option>
        <option value="reactions">Most Reactions</option>
      </select>
      <button @click="toggleView">
        {{ viewMode === 'flat' ? 'Tree View' : 'Flat View' }}
      </button>
    </div>
  </div>
  
  <CommentForm 
    :post-id="postId"
    @comment-created="onCommentCreated"
  />
  
  <div class="comments-list">
    <CommentItem
      v-for="comment in displayedComments"
      :key="comment.comment_id"
      :comment="comment"
      :post-id="postId"
      @reply-created="onReplyCreated"
      @comment-updated="onCommentUpdated"
      @comment-deleted="onCommentDeleted"
    />
  </div>
  
  <button 
    v-if="hasMore"
    @click="loadMore"
    class="load-more-btn"
  >
    Load More Comments
  </button>
</div>
```

**Styling Requirements:**
- Responsive layout for mobile/desktop
- Clear visual hierarchy for nested comments
- Loading states for API calls
- Empty state when no comments
- Smooth transitions for new comments

---

#### 2. CommentItem.vue

**Location:** `frontend/src/components/comments/CommentItem.vue`

**Purpose:** Individual comment display with actions

**Key Features:**
- Displays comment content with author info
- Shows reaction counts and user's reaction
- Edit/delete actions for comment owner
- Reply button to create nested replies
- Collapsible nested comment threads
- Relative timestamp display
- Reaction picker integration

**Props:**
```typescript
interface Props {
  comment: Comment
  postId: number
  depth?: number
  maxDepth?: number
}
```

**Template Structure:**
```vue
<div 
  class="comment-item" 
  :class="`depth-${depth}`"
  :style="{ marginLeft: `${depth * 24}px` }"
>
  <div class="comment-header">
    <img :src="comment.author_avatar" class="avatar" />
    <div class="comment-meta">
      <router-link :to="`/users/${comment.author_id}`">
        {{ comment.author_name }}
      </router-link>
      <span class="timestamp">{{ formatTime(comment.created_at) }}</span>
      <span v-if="comment.is_edited" class="edited-badge">edited</span>
    </div>
    
    <div class="comment-actions">
      <button 
        v-if="isOwner"
        @click="startEdit"
        :disabled="!canEdit"
      >
        Edit
      </button>
      <button 
        v-if="isOwner"
        @click="deleteComment"
      >
        Delete
      </button>
    </div>
  </div>
  
  <div class="comment-content">
    <p v-if="!isEditing">{{ comment.content_text }}</p>
    <CommentForm
      v-else
      :initial-content="comment.content_text"
      :comment-id="comment.comment_id"
      @comment-updated="onUpdated"
      @cancel="isEditing = false"
    />
  </div>
  
  <div class="comment-footer">
    <button 
      class="reaction-btn"
      @click="toggleReactionPicker"
    >
      <span v-if="comment.user_reaction">
        {{ getReactionEmoji(comment.user_reaction) }}
      </span>
      <span v-else>👍</span>
      <span v-if="comment.reaction_count > 0">
        {{ comment.reaction_count }}
      </span>
    </button>
    
    <button 
      v-if="depth < maxDepth"
      @click="toggleReply"
      class="reply-btn"
    >
      Reply
      <span v-if="comment.reply_count > 0">
        ({{ comment.reply_count }})
      </span>
    </button>
    
    <button 
      v-if="comment.reply_count > 0"
      @click="toggleReplies"
      class="collapse-btn"
    >
      {{ repliesExpanded ? 'Hide' : 'Show' }} Replies
    </button>
  </div>
  
  <!-- Reaction Picker -->
  <ReactionPicker
    v-if="showReactionPicker"
    :current-reaction="comment.user_reaction"
    @select="reactToComment"
    @close="showReactionPicker = false"
  />
  
  <!-- Reply Form -->
  <CommentForm
    v-if="showReplyForm"
    :post-id="postId"
    :parent-comment-id="comment.comment_id"
    placeholder="Write a reply..."
    @comment-created="onReplyCreated"
    @cancel="showReplyForm = false"
  />
  
  <!-- Nested Replies -->
  <div v-if="repliesExpanded && comment.replies" class="replies">
    <CommentItem
      v-for="reply in comment.replies"
      :key="reply.comment_id"
      :comment="reply"
      :post-id="postId"
      :depth="depth + 1"
      :max-depth="maxDepth"
    />
  </div>
</div>
```

**Methods:**
```typescript
// React to comment
const reactToComment = async (reactionType: string) => {
  try {
    const response = await api.post(
      `/comments/${comment.comment_id}/reactions`,
      { reaction_type: reactionType }
    )
    
    // Update local state
    comment.reaction_count = response.data.reaction_count
    comment.user_reaction = response.data.action === 'removed' 
      ? null 
      : reactionType
      
    showReactionPicker.value = false
  } catch (error) {
    console.error('Failed to react:', error)
  }
}

// Delete comment
const deleteComment = async () => {
  if (!confirm('Delete this comment?')) return
  
  try {
    await api.delete(`/comments/${comment.comment_id}`)
    emit('comment-deleted', comment.comment_id)
  } catch (error) {
    console.error('Failed to delete:', error)
  }
}

// Check if user can edit (15 minute window)
const canEdit = computed(() => {
  const createdTime = new Date(comment.created_at).getTime()
  const now = Date.now()
  const minutesPassed = (now - createdTime) / 1000 / 60
  return minutesPassed < 15
})
```

**Styling:**
- Indentation for nested comments (max 5 levels)
- Hover effects for actions
- Clear visual separation between comments
- Responsive for mobile
- Accessibility (keyboard navigation)

---

#### 3. CommentForm.vue

**Location:** `frontend/src/components/comments/CommentForm.vue`

**Purpose:** Form for creating/editing comments

**Key Features:**
- Text input with character counter
- Submit and cancel buttons
- Inline validation
- Optimistic UI updates
- Auto-resize textarea
- @mention autocomplete (future)
- Emoji picker (future)

**Props:**
```typescript
interface Props {
  postId?: number
  parentCommentId?: number
  commentId?: number  // For editing
  initialContent?: string
  placeholder?: string
}
```

**Template:**
```vue
<form @submit.prevent="submitComment" class="comment-form">
  <div class="form-group">
    <textarea
      v-model="content"
      :placeholder="placeholder || 'Write a comment...'"
      :maxlength="2000"
      rows="3"
      @input="autoResize"
      ref="textareaRef"
    ></textarea>
    
    <div class="form-footer">
      <span class="char-count" :class="{ warning: charCount > 1800 }">
        {{ charCount }} / 2000
      </span>
      
      <div class="form-actions">
        <button 
          type="button"
          @click="cancel"
          class="btn-secondary"
        >
          Cancel
        </button>
        <button 
          type="submit"
          :disabled="!canSubmit || isSubmitting"
          class="btn-primary"
        >
          {{ isSubmitting ? 'Posting...' : (commentId ? 'Update' : 'Comment') }}
        </button>
      </div>
    </div>
  </div>
  
  <div v-if="error" class="error-message">
    {{ error }}
  </div>
</form>
```

**Methods:**
```typescript
const submitComment = async () => {
  if (!canSubmit.value) return
  
  isSubmitting.value = true
  error.value = null
  
  try {
    let response
    
    if (props.commentId) {
      // Edit existing comment
      response = await api.patch(`/comments/${props.commentId}`, {
        content: content.value
      })
      emit('comment-updated', response.data)
    } else if (props.parentCommentId) {
      // Create reply
      response = await api.post(
        `/comments/${props.parentCommentId}/replies`,
        { content: content.value }
      )
      emit('comment-created', response.data)
    } else {
      // Create top-level comment
      response = await api.post(`/posts/${props.postId}/comments`, {
        content: content.value
      })
      emit('comment-created', response.data)
    }
    
    // Clear form
    content.value = ''
    
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to post comment'
  } finally {
    isSubmitting.value = false
  }
}

const autoResize = (event: Event) => {
  const textarea = event.target as HTMLTextAreaElement
  textarea.style.height = 'auto'
  textarea.style.height = textarea.scrollHeight + 'px'
}

const canSubmit = computed(() => {
  return content.value.trim().length > 0 && 
         content.value.length <= 2000
})

const charCount = computed(() => content.value.length)
```

---

### Pinia Store Integration

**Location:** `frontend/src/stores/comments.ts`

Create a Pinia store to manage comment state:

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useCommentsStore = defineStore('comments', () => {
  // State
  const commentsByPost = ref<Map<number, Comment[]>>(new Map())
  const loading = ref(false)
  const error = ref<string | null>(null)
  
  // Actions
  const fetchComments = async (postId: number, options = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(`/posts/${postId}/comments`, {
        params: options
      })
      
      commentsByPost.value.set(postId, response.data.comments)
      return response.data
    } catch (err: any) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }
  
  const createComment = async (postId: number, content: string, parentId?: number) => {
    try {
      const endpoint = parentId 
        ? `/comments/${parentId}/replies`
        : `/posts/${postId}/comments`
        
      const response = await api.post(endpoint, { content })
      
      // Add to local state
      const comments = commentsByPost.value.get(postId) || []
      commentsByPost.value.set(postId, [response.data, ...comments])
      
      return response.data
    } catch (err: any) {
      throw err
    }
  }
  
  const updateComment = async (commentId: number, content: string) => {
    const response = await api.patch(`/comments/${commentId}`, { content })
    
    // Update in local state
    for (const [postId, comments] of commentsByPost.value.entries()) {
      const index = comments.findIndex(c => c.comment_id === commentId)
      if (index !== -1) {
        comments[index] = response.data
        break
      }
    }
    
    return response.data
  }
  
  const deleteComment = async (commentId: number) => {
    await api.delete(`/comments/${commentId}`)
    
    // Remove from local state
    for (const [postId, comments] of commentsByPost.value.entries()) {
      const filtered = comments.filter(c => c.comment_id !== commentId)
      commentsByPost.value.set(postId, filtered)
    }
  }
  
  const reactToComment = async (commentId: number, reactionType: string) => {
    return await api.post(`/comments/${commentId}/reactions`, {
      reaction_type: reactionType
    })
  }
  
  // Getters
  const getCommentsByPost = computed(() => {
    return (postId: number) => commentsByPost.value.get(postId) || []
  })
  
  return {
    commentsByPost,
    loading,
    error,
    fetchComments,
    createComment,
    updateComment,
    deleteComment,
    reactToComment,
    getCommentsByPost
  }
})
```

---

### TypeScript Types

**Location:** `frontend/src/types/comment.ts`

```typescript
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
```

---

### Styling Guide

**Theme Integration:**

Use existing CSS variables from the theme system:

```css
.comment-section {
  background: var(--bg-primary);
  border-radius: var(--radius-md);
  padding: var(--spacing-md);
}

.comment-item {
  padding: var(--spacing-sm);
  border-left: 2px solid var(--border-color);
  margin-bottom: var(--spacing-sm);
}

.comment-item:hover {
  background: var(--bg-secondary);
}

.comment-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-xs);
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
}

.comment-content {
  color: var(--text-primary);
  line-height: 1.5;
  margin-bottom: var(--spacing-sm);
}

.comment-footer {
  display: flex;
  gap: var(--spacing-sm);
}

.reaction-btn,
.reply-btn {
  background: transparent;
  border: 1px solid var(--border-color);
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.2s;
}

.reaction-btn:hover,
.reply-btn:hover {
  background: var(--bg-secondary);
  border-color: var(--primary);
}

/* Nested comment indentation */
.depth-0 { margin-left: 0; }
.depth-1 { margin-left: 24px; }
.depth-2 { margin-left: 48px; }
.depth-3 { margin-left: 72px; }
.depth-4 { margin-left: 96px; }

/* Mobile responsiveness */
@media (max-width: 768px) {
  .depth-1 { margin-left: 12px; }
  .depth-2 { margin-left: 24px; }
  .depth-3 { margin-left: 36px; }
  .depth-4 { margin-left: 48px; }
}
```

---

## Integration Steps

### Step 1: Create Directory Structure

```bash
cd C:\Projects\wall.cyka.lol\frontend\src

# Create comments directory
mkdir components\comments

# Create types file
mkdir types
```

### Step 2: Create Components

Create the three component files:
1. `components/comments/CommentSection.vue`
2. `components/comments/CommentItem.vue`
3. `components/comments/CommentForm.vue`

### Step 3: Create Pinia Store

Create `stores/comments.ts` with the comment management logic

### Step 4: Create Types

Create `types/comment.ts` with TypeScript interfaces

### Step 5: Integrate into PostItem

Update existing PostItem component to include CommentSection:

```vue
<template>
  <div class="post-item">
    <!-- Existing post content -->
    
    <!-- Add Comments Section -->
    <CommentSection
      v-if="showComments"
      :post-id="post.post_id"
      :allow-comments="post.allow_comments"
    />
    
    <button @click="toggleComments">
      {{ showComments ? 'Hide' : 'Show' }} Comments ({{ post.comment_count }})
    </button>
  </div>
</template>

<script setup lang="ts">
import CommentSection from '@/components/comments/CommentSection.vue'
import { ref } from 'vue'

const showComments = ref(false)
const toggleComments = () => {
  showComments.value = !showComments.value
}
</script>
```

### Step 6: Update i18n Translations

Add comment-related translations to `i18n/locales/`:

**en.json:**
```json
{
  "comments": {
    "title": "Comments",
    "write": "Write a comment...",
    "reply": "Reply",
    "edit": "Edit",
    "delete": "Delete",
    "deleteConfirm": "Are you sure you want to delete this comment?",
    "edited": "edited",
    "loadMore": "Load more comments",
    "noComments": "No comments yet. Be the first to comment!",
    "sortNewest": "Newest First",
    "sortOldest": "Oldest First",
    "sortReactions": "Most Reactions",
    "viewFlat": "Flat View",
    "viewTree": "Tree View",
    "hideReplies": "Hide Replies",
    "showReplies": "Show Replies",
    "posting": "Posting...",
    "updateComment": "Update Comment",
    "cancel": "Cancel"
  }
}
```

**ru.json:**
```json
{
  "comments": {
    "title": "Комментарии",
    "write": "Написать комментарий...",
    "reply": "Ответить",
    "edit": "Редактировать",
    "delete": "Удалить",
    "deleteConfirm": "Вы уверены, что хотите удалить этот комментарий?",
    "edited": "изменено",
    "loadMore": "Загрузить больше комментариев",
    "noComments": "Пока нет комментариев. Будьте первым!",
    "sortNewest": "Сначала новые",
    "sortOldest": "Сначала старые",
    "sortReactions": "По реакциям",
    "viewFlat": "Плоский вид",
    "viewTree": "Древовидный вид",
    "hideReplies": "Скрыть ответы",
    "showReplies": "Показать ответы",
    "posting": "Отправка...",
    "updateComment": "Обновить комментарий",
    "cancel": "Отмена"
  }
}
```

---

## Testing Plan

### Manual Testing Checklist

**Comment Creation:**
- [ ] Create top-level comment on post
- [ ] Create reply to comment
- [ ] Create nested reply (2-3 levels deep)
- [ ] Try to create comment with empty content (should fail)
- [ ] Try to create comment with 2001+ characters (should fail)
- [ ] Verify comment appears immediately after creation
- [ ] Verify comment count increments

**Comment Editing:**
- [ ] Edit own comment within 15 minutes
- [ ] Try to edit after 15 minutes (should fail)
- [ ] Try to edit someone else's comment (should fail)
- [ ] Verify "edited" badge appears
- [ ] Cancel edit and verify no changes

**Comment Deletion:**
- [ ] Delete own comment
- [ ] Try to delete someone else's comment (should fail)
- [ ] Verify comment count decrements
- [ ] Verify deleted comment shows [deleted] placeholder

**Comment Reactions:**
- [ ] React to comment with different types
- [ ] Toggle reaction (click same reaction twice)
- [ ] View "who reacted" list
- [ ] Verify reaction counts update
- [ ] Verify user's reaction is highlighted

**Comment Display:**
- [ ] View comments in flat mode
- [ ] View comments in tree mode
- [ ] Sort by newest first
- [ ] Sort by oldest first
- [ ] Sort by most reactions
- [ ] Collapse/expand nested replies
- [ ] Load more comments (pagination)

**Responsive Design:**
- [ ] Test on desktop (1920x1080)
- [ ] Test on tablet (768x1024)
- [ ] Test on mobile (375x667)
- [ ] Verify indentation works on mobile
- [ ] Verify buttons are tappable on mobile

---

## Performance Considerations

### Optimization Strategies

**1. Virtual Scrolling for Long Comment Lists:**
```typescript
import { useVirtualList } from '@vueuse/core'

const { list, containerProps, wrapperProps } = useVirtualList(
  comments,
  { itemHeight: 100 }
)
```

**2. Debounced Auto-resize:**
```typescript
import { useDebounceFn } from '@vueuse/core'

const debouncedResize = useDebounceFn(autoResize, 100)
```

**3. Lazy Load Nested Replies:**
```typescript
const loadReplies = async (commentId: number) => {
  if (!repliesLoaded.value) {
    const response = await api.get(`/comments/${commentId}`)
    comment.value.replies = response.data.replies
    repliesLoaded.value = true
  }
}
```

**4. Optimistic Updates:**
```typescript
const createComment = async (content: string) => {
  // Add optimistic comment
  const optimisticComment = {
    comment_id: Date.now(), // Temporary ID
    content_text: content,
    author_name: currentUser.value.name,
    created_at: new Date().toISOString(),
    _optimistic: true
  }
  
  comments.value.unshift(optimisticComment)
  
  try {
    const response = await api.post(...)
    // Replace optimistic with real comment
    const index = comments.value.findIndex(c => c._optimistic)
    comments.value[index] = response.data
  } catch (error) {
    // Remove optimistic comment on error
    comments.value = comments.value.filter(c => !c._optimistic)
  }
}
```

---

## Next Immediate Steps

### For Developer Implementing Frontend

1. **Set up workspace:**
   ```bash
   cd C:\Projects\wall.cyka.lol\frontend
   npm install
   npm run dev
   ```

2. **Create components in order:**
   - Start with `CommentForm.vue` (simplest)
   - Then `CommentItem.vue`
   - Finally `CommentSection.vue` (integrates both)

3. **Create Pinia store:**
   - Set up `stores/comments.ts`
   - Test API integration

4. **Add to existing views:**
   - Integrate into `PostItem.vue`
   - Test on Wall view and Feed view

5. **Test thoroughly:**
   - Follow testing checklist
   - Fix any bugs
   - Optimize performance

6. **Document:**
   - Create progress report
   - Update token usage
   - Note any issues or improvements

---

## Estimated Time to Complete

**Frontend Implementation:**
- CommentForm component: 2-3 hours
- CommentItem component: 3-4 hours
- CommentSection component: 2-3 hours
- Pinia store: 1-2 hours
- Integration & styling: 2-3 hours
- Testing & bug fixes: 2-3 hours

**Total:** 12-18 hours (1.5-2 days)

---

## Success Criteria

✅ **Component Creation:**
- All 3 components created and functional
- Pinia store managing state
- TypeScript types defined

✅ **API Integration:**
- All 11 endpoints working
- Error handling implemented
- Loading states displayed

✅ **User Experience:**
- Comments load on demand
- Nested replies display correctly
- Reactions toggle smoothly
- Forms validate input
- Responsive on all devices

✅ **Code Quality:**
- TypeScript strict mode passes
- No console errors
- Follows Vue 3 best practices
- Accessible (keyboard navigation)

---

## Resources

**API Documentation:**
- See `history/20251101-125455-comments-backend-implementation.md`

**Existing Components to Reference:**
- `components/posts/PostItem.vue` - Similar structure
- `components/posts/PostForm.vue` - Form patterns
- `components/social/FollowButton.vue` - API integration example

**Vue 3 Documentation:**
- Composition API: https://vuejs.org/api/composition-api-setup.html
- Pinia: https://pinia.vuejs.org/
- TypeScript: https://vuejs.org/guide/typescript/overview.html

---

**Status:** 📋 Ready for Frontend Development  
**Backend:** ✅ 100% Complete  
**Frontend:** 🚧 0% Complete (Awaiting Implementation)

---

*End of Guide*
