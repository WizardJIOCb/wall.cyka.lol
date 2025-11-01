# Wall Social Platform - Continued Development Design

## Overview

This design document outlines the implementation plan for continuing the Wall Social Platform development, addressing critical issues and implementing remaining phases.

## Critical Issues to Resolve

### 1. Wall Route 404 Error

**Problem**: Accessing `http://localhost:8080/wall` returns 404 error

**Root Cause Analysis**:
- Frontend router expects route pattern `/wall/:wallId` (requires wall ID parameter)
- Backend has no root `/wall` endpoint without ID parameter
- Nginx configuration routes non-API requests to Vue SPA correctly
- User attempting to access `/wall` without wall ID parameter

**Resolution Strategy**:
The route requires a wall ID parameter. The application should:
- Redirect users from home feed to specific wall pages
- Provide wall discovery mechanism
- Allow users to access their own wall via `/wall/me` or profile link
- Update navigation to link to user's wall using their wall slug or ID

## Phase Implementation Roadmap

### Phase 4: Wall View Implementation (Priority: High)

**Objective**: Complete wall view functionality allowing users to view and interact with walls

**Components to Implement**:

#### Frontend - WallView.vue
- Display wall header with owner information, display name, description
- Show wall statistics (post count, follower count)
- Render wall posts in chronological or pinned-first order
- Implement infinite scroll for post loading
- Display media attachments in posts
- Show reactions and comments count per post
- Privacy-based visibility controls
- Wall following/unfollowing capability
- Theme application based on wall settings

#### Backend - Already Implemented
- Wall retrieval endpoints exist
- Post retrieval by wall ID implemented
- Privacy checks in place

#### API Integration
- GET `/api/v1/walls/:wallId` - Retrieve wall details
- GET `/api/v1/walls/:wallId/posts` - Retrieve wall posts with pagination
- POST `/api/v1/walls/:wallId/follow` - Follow wall (requires implementation)
- DELETE `/api/v1/walls/:wallId/follow` - Unfollow wall (requires implementation)

**Data Flow**:
1. User navigates to `/wall/:wallId`
2. Component loads wall metadata via API
3. Check privacy settings and user permissions
4. Load initial batch of posts (20 posts)
5. Implement infinite scroll to load more posts
6. Display reactions, comments, media for each post

### Phase 5: Profile, Discover, and Notifications (Priority: High)

#### Profile View Implementation

**Objective**: Allow users to view and edit their own profile and view other users' profiles

**Frontend Components**:
- ProfileView.vue structure:
  - Profile header section (avatar, cover image, username, bio)
  - Social links display with icons
  - Statistics panel (wall count, post count, follower count, following count)
  - User's walls listing
  - Recent posts tab
  - Edit profile modal (for own profile)
  - Follow/unfollow button (for other profiles)
  
**Backend Requirements**:
- GET `/api/v1/users/:userId` - Already implemented
- GET `/api/v1/users/:userId/posts` - Already implemented
- GET `/api/v1/users/:userId/wall` - Already implemented
- POST `/api/v1/users/:userId/follow` - Needs implementation
- DELETE `/api/v1/users/:userId/follow` - Needs implementation
- GET `/api/v1/users/:userId/followers` - Needs implementation
- GET `/api/v1/users/:userId/following` - Needs implementation

#### Discover View Implementation

**Objective**: Help users discover new walls, posts, and users

**Features**:
- Trending walls based on activity metrics
- Popular posts from last 24 hours/week
- Suggested users to follow based on interests
- Search functionality (walls, users, posts)
- Category/tag filtering
- Infinite scroll pagination

**Backend Requirements**:
- GET `/api/v1/discover/trending-walls` - Needs implementation
- GET `/api/v1/discover/popular-posts` - Needs implementation
- GET `/api/v1/discover/suggested-users` - Needs implementation
- GET `/api/v1/search?q=query&type=wall|user|post` - Needs implementation

**Ranking Algorithm Considerations**:
- Trending walls: Post frequency + reaction count + view count (weighted by time decay)
- Popular posts: Reaction count + comment count + share count (last 7 days)
- Suggested users: Mutual connections + shared interests + activity level

#### Notifications View Implementation

**Objective**: Display real-time notifications for user interactions

**Notification Types**:
- New follower
- Reaction on post
- Comment on post
- Reply to comment
- Wall mention
- User mention
- Bricks transaction
- AI application status change

**Frontend Components**:
- Notification list with infinite scroll
- Notification item rendering by type
- Mark as read functionality
- Mark all as read action
- Filter by notification type
- Real-time updates via polling or WebSocket

**Backend Requirements**:
- Notifications table in database (needs schema design)
- GET `/api/v1/notifications` - Needs implementation
- PATCH `/api/v1/notifications/:notificationId/read` - Needs implementation
- POST `/api/v1/notifications/mark-all-read` - Needs implementation
- GET `/api/v1/notifications/unread-count` - Needs implementation

**Database Schema Requirements**:

Table: notifications
- notification_id (PRIMARY KEY, AUTO_INCREMENT)
- user_id (recipient, FOREIGN KEY)
- actor_id (who triggered notification, FOREIGN KEY, nullable)
- notification_type (ENUM)
- target_type (wall, post, comment, user, etc.)
- target_id (reference to related entity)
- content (JSON with notification details)
- is_read (BOOLEAN, default FALSE)
- created_at (TIMESTAMP)

### Phase 6: AI Generation View (Priority: Medium)

**Objective**: Provide UI for AI-powered web application generation

**Frontend Components**:
- AIGenerateView.vue structure:
  - Prompt input textarea with character counter
  - Model selection dropdown
  - Priority selection (normal, high)
  - Advanced options panel (creativity, length, features)
  - Cost calculator display (bricks required)
  - Generation history list
  - Job status tracking with progress indicator
  - Generated application preview iframe
  - Actions: Remix, Fork, Download, Share

**Real-time Status Updates**:
- Poll job status endpoint every 2-3 seconds during generation
- Display queue position and estimated time
- Show generation progress percentage
- Display error messages if generation fails

**Backend - Already Implemented**:
- POST `/api/v1/ai/generate`
- GET `/api/v1/ai/jobs/:jobId`
- GET `/api/v1/ai/apps/:appId`
- POST `/api/v1/ai/apps/:appId/remix`
- POST `/api/v1/ai/apps/:appId/fork`

**User Experience Flow**:
1. User enters prompt describing desired application
2. System calculates brick cost based on complexity
3. User confirms and submits generation request
4. Job added to queue, user redirected to status page
5. Real-time status updates display queue position
6. Upon completion, preview generated application
7. User can publish, remix, fork, or discard

### Phase 7: Messaging View (Priority: Medium)

**Objective**: Enable private messaging between users

**Frontend Components**:
- MessagesView.vue structure:
  - Two-panel layout (conversation list + active conversation)
  - Conversation list with search/filter
  - Message thread display
  - Message input with emoji picker
  - Typing indicators
  - Read receipts
  - Media attachment support
  - Real-time message updates

**Backend Requirements**:
- Database schema for conversations and messages
- GET `/api/v1/conversations` - List user conversations
- POST `/api/v1/conversations` - Start new conversation
- GET `/api/v1/conversations/:conversationId/messages` - Get messages
- POST `/api/v1/conversations/:conversationId/messages` - Send message
- PATCH `/api/v1/conversations/:conversationId/read` - Mark as read
- DELETE `/api/v1/conversations/:conversationId` - Delete conversation
- GET `/api/v1/conversations/:conversationId/typing` - Typing status

**Database Schema Requirements**:

Table: conversations
- conversation_id (PRIMARY KEY, AUTO_INCREMENT)
- conversation_type (direct, group)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

Table: conversation_participants
- participant_id (PRIMARY KEY, AUTO_INCREMENT)
- conversation_id (FOREIGN KEY)
- user_id (FOREIGN KEY)
- joined_at (TIMESTAMP)
- last_read_at (TIMESTAMP)

Table: messages
- message_id (PRIMARY KEY, AUTO_INCREMENT)
- conversation_id (FOREIGN KEY)
- sender_id (FOREIGN KEY)
- message_text (TEXT)
- message_type (text, image, file)
- attachment_url (VARCHAR, nullable)
- is_deleted (BOOLEAN, default FALSE)
- created_at (TIMESTAMP)
- edited_at (TIMESTAMP, nullable)

**Real-time Implementation**:
- Use polling (every 3-5 seconds) for message updates
- Alternative: Implement Server-Sent Events (SSE) for better performance
- Display typing indicators with debounce

### Phase 8: Settings View (Priority: High)

**Objective**: Provide comprehensive user settings and preferences management

**Frontend Components**:
- SettingsView.vue with tabbed navigation:

**Tab 1: Account Settings**
- Username change (with availability check)
- Email management
- Password change
- Account deletion (with confirmation)
- OAuth connections management (Google, Yandex, Telegram)

**Tab 2: Profile Settings**
- Bio editing
- Social links management
- Location settings
- Avatar upload
- Cover image upload

**Tab 3: Privacy Settings**
- Default wall privacy
- Who can follow user
- Who can message user
- Activity visibility
- Data sharing preferences

**Tab 4: Notification Preferences**
- Email notifications toggle by type
- Push notification preferences
- Notification frequency settings
- Quiet hours configuration

**Tab 5: Appearance Settings**
- Theme selection (light, dark, green, cream, blue, high-contrast)
- Language selection (English, Russian)
- Font size preferences
- Accessibility options

**Tab 6: Bricks & Billing**
- Current balance display
- Transaction history
- Daily claim button
- Transfer bricks feature
- Purchase options (future)

**Backend Requirements**:
- PATCH `/api/v1/users/me/settings` - Update user settings
- GET `/api/v1/users/me/settings` - Retrieve current settings
- POST `/api/v1/users/me/change-password` - Change password
- POST `/api/v1/users/me/upload-avatar` - Upload avatar
- POST `/api/v1/users/me/upload-cover` - Upload cover image
- DELETE `/api/v1/users/me/account` - Delete account

## Multi-Language Support (i18n) Implementation

### Objective
Implement internationalization for English (default) and Russian languages across entire frontend application.

### Technology Selection
**Recommended**: Vue I18n library for Vue 3

**Rationale**:
- Native Vue 3 support with Composition API
- Type-safe translation keys
- Lazy loading for language files
- Pluralization and formatting support
- Easy integration with existing project structure

### Implementation Architecture

#### Directory Structure
```
frontend/src/
  ├── i18n/
  │   ├── index.ts              # i18n configuration
  │   ├── locales/
  │   │   ├── en.json           # English translations
  │   │   ├── ru.json           # Russian translations
  │   │   └── index.ts          # Locale exports
  │   └── helpers.ts            # Translation utilities
```

#### Translation File Structure

Organize translations by feature domain:
- common (buttons, actions, labels)
- auth (login, register, password reset)
- navigation (menu items, links)
- walls (wall-related terms)
- posts (post creation, editing)
- profile (profile fields, bio)
- settings (all settings sections)
- messages (messaging interface)
- notifications (notification types)
- errors (error messages, validation)
- success (success messages)
- time (time formatting, dates)

#### Locale Storage Strategy
- Store selected language in browser localStorage
- Key: `wall_app_locale`
- Fallback to browser language detection
- Default to English if unsupported language

#### Language Switcher Component
- Dropdown in settings view
- Quick toggle in navigation bar (optional)
- Persist selection immediately on change
- Reload translations without page refresh

#### Translation Key Naming Convention
- Use dot notation for nested keys
- Format: `domain.section.key`
- Examples:
  - `auth.login.title` → "Login" / "Войти"
  - `common.buttons.submit` → "Submit" / "Отправить"
  - `errors.validation.required` → "This field is required" / "Это поле обязательно"

#### Special Formatting Considerations

**Pluralization**:
English vs Russian have different pluralization rules
- English: 1 post, 2 posts
- Russian: 1 пост, 2 поста, 5 постов

**Date/Time Formatting**:
- Use locale-specific date formats
- English: MM/DD/YYYY
- Russian: DD.MM.YYYY

**Number Formatting**:
- Decimal separator differences
- English: 1,234.56
- Russian: 1 234,56

#### Components to Localize

**Priority 1 (High)**: 
- Authentication forms (Login, Register)
- Navigation menu
- Settings interface
- Error messages
- Success messages

**Priority 2 (Medium)**:
- Profile view
- Wall view
- Post composer
- Comments section

**Priority 3 (Low)**:
- Discover view
- AI generation view
- Messaging interface
- Notifications

#### Backend Integration
- API responses remain in English (technical data)
- User-generated content remains in original language
- System messages and notifications use user's language preference
- Store user language preference in database (users.preferred_language)

#### Implementation Steps

**Step 1**: Install and configure Vue I18n
- Add dependency to package.json
- Create i18n configuration file
- Integrate with Vue app initialization

**Step 2**: Create initial translation files
- Define English baseline translations
- Create Russian translations
- Organize by feature domains

**Step 3**: Update existing components
- Replace hardcoded strings with translation keys
- Update template syntax to use $t() function
- Add pluralization where needed

**Step 4**: Implement language switcher
- Add language selection to settings
- Create language toggle component
- Persist selection in localStorage and database

**Step 5**: Test language switching
- Verify all strings translate correctly
- Test pluralization rules
- Validate date/time formatting
- Check RTL vs LTR layout (future consideration)

**Step 6**: Add language to user preferences
- Update user model to include preferred_language field
- Save language selection to backend
- Load user language on authentication

### Translation Coverage

**Estimated Translation Keys**: 300-500 keys for initial implementation

**Key Categories**:
- Navigation: ~20 keys
- Authentication: ~40 keys
- Walls: ~50 keys
- Posts: ~60 keys
- Profile: ~40 keys
- Settings: ~80 keys
- Messages: ~50 keys
- Notifications: ~40 keys
- Common/Shared: ~30 keys
- Errors/Validation: ~50 keys

## Backend API Additions Required

### Follow System

**Endpoint**: POST `/api/v1/users/:userId/follow`
**Purpose**: Follow a user
**Authentication**: Required
**Response**: Success confirmation with updated follower count

**Endpoint**: DELETE `/api/v1/users/:userId/follow`
**Purpose**: Unfollow a user
**Authentication**: Required
**Response**: Success confirmation

**Endpoint**: GET `/api/v1/users/:userId/followers`
**Purpose**: Get list of user's followers
**Authentication**: Optional (depends on privacy)
**Response**: Paginated list of user profiles

**Endpoint**: GET `/api/v1/users/:userId/following`
**Purpose**: Get list of users being followed
**Authentication**: Optional (depends on privacy)
**Response**: Paginated list of user profiles

**Database Schema**:
Table: user_follows
- follow_id (PRIMARY KEY, AUTO_INCREMENT)
- follower_id (FOREIGN KEY → users.user_id)
- following_id (FOREIGN KEY → users.user_id)
- created_at (TIMESTAMP)
- UNIQUE constraint on (follower_id, following_id)

### Discover System

**Endpoint**: GET `/api/v1/discover/trending-walls`
**Purpose**: Retrieve trending walls based on activity
**Query Parameters**: limit, offset, timeframe (24h, 7d, 30d)
**Response**: Array of wall objects with activity metrics

**Endpoint**: GET `/api/v1/discover/popular-posts`
**Purpose**: Retrieve popular posts across platform
**Query Parameters**: limit, offset, timeframe
**Response**: Array of post objects with engagement metrics

**Endpoint**: GET `/api/v1/discover/suggested-users`
**Purpose**: Get personalized user suggestions
**Authentication**: Optional
**Response**: Array of user profiles with follow status

**Endpoint**: GET `/api/v1/search`
**Purpose**: Global search across walls, users, posts
**Query Parameters**: q (query), type (wall|user|post|all), limit, offset
**Response**: Categorized search results

### Notifications System

**Endpoint**: GET `/api/v1/notifications`
**Purpose**: Retrieve user notifications
**Query Parameters**: limit, offset, filter (unread|all)
**Response**: Paginated notification list with metadata

**Endpoint**: PATCH `/api/v1/notifications/:notificationId/read`
**Purpose**: Mark single notification as read
**Response**: Success confirmation

**Endpoint**: POST `/api/v1/notifications/mark-all-read`
**Purpose**: Mark all notifications as read
**Response**: Success confirmation with count

**Endpoint**: GET `/api/v1/notifications/unread-count`
**Purpose**: Get count of unread notifications
**Response**: Integer count

### Settings Management

**Endpoint**: GET `/api/v1/users/me/settings`
**Purpose**: Retrieve all user settings
**Response**: Settings object with all preferences

**Endpoint**: PATCH `/api/v1/users/me/settings`
**Purpose**: Update user settings (partial update)
**Request Body**: Settings fields to update
**Response**: Updated settings object

**Endpoint**: POST `/api/v1/users/me/change-password`
**Purpose**: Change user password
**Request Body**: current_password, new_password
**Response**: Success confirmation

**Endpoint**: POST `/api/v1/users/me/upload-avatar`
**Purpose**: Upload user avatar image
**Request**: Multipart form data with image file
**Response**: Avatar URL and success message

**Endpoint**: DELETE `/api/v1/users/me/account`
**Purpose**: Permanently delete user account
**Request Body**: password (confirmation)
**Response**: Success message

## Database Schema Extensions

### User Preferences Table
```
Table: user_preferences
- preference_id (PRIMARY KEY, AUTO_INCREMENT)
- user_id (FOREIGN KEY → users.user_id, UNIQUE)
- theme (VARCHAR, default 'light')
- language (VARCHAR, default 'en')
- email_notifications (BOOLEAN, default TRUE)
- push_notifications (BOOLEAN, default TRUE)
- notification_frequency (ENUM: instant, hourly, daily)
- privacy_default_wall (ENUM: public, private, followers)
- privacy_can_follow (ENUM: everyone, mutual, nobody)
- privacy_can_message (ENUM: everyone, followers, mutual)
- accessibility_font_size (ENUM: small, medium, large)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Additional Indexes for Performance
- Index on notifications.user_id for faster notification retrieval
- Index on notifications.is_read for filtering unread
- Composite index on (user_id, created_at DESC) for pagination
- Index on user_follows (follower_id, following_id) for relationship queries
- Full-text index on posts.content_text for search functionality
- Index on walls.wall_slug for slug-based lookups

## Testing Strategy

### Frontend Testing
- Unit tests for translation helper functions
- Component tests for language switching
- Integration tests for API calls with different locales
- E2E tests for critical user flows in both languages

### Backend Testing
- API endpoint testing for all new routes
- Database query performance testing
- Privacy and permission validation tests
- Pagination and filtering correctness

### User Acceptance Testing
- Test language switching across all views
- Verify translation accuracy and context
- Test notification creation and display
- Validate follow/unfollow functionality
- Test search functionality with various queries

## Performance Considerations

### Frontend Optimization
- Lazy load language files (only load selected language)
- Cache translations in memory after first load
- Implement virtual scrolling for long lists (notifications, messages)
- Optimize image loading with lazy loading and WebP format
- Minimize bundle size by code splitting per route

### Backend Optimization
- Implement Redis caching for frequently accessed data (trending walls, popular posts)
- Use database connection pooling
- Optimize SQL queries with proper indexes
- Implement rate limiting on expensive operations (search, AI generation)
- Cache user preferences in Redis after first load

### Database Optimization
- Regular index maintenance
- Query execution plan analysis
- Implement read replicas for heavy read operations
- Archive old notifications after 90 days
- Optimize full-text search with dedicated search engine (future: Elasticsearch)

## Security Considerations

### Authentication & Authorization
- Validate user permissions for all protected routes
- Implement CSRF protection for state-changing operations
- Rate limit authentication attempts
- Validate session tokens on every request

### Data Privacy
- Respect privacy settings for user data exposure
- Implement soft delete for user accounts (retain for 30 days)
- Encrypt sensitive user data in database
- Sanitize user input to prevent XSS attacks
- Validate and sanitize HTML content in posts

### API Security
- Implement request rate limiting per user/IP
- Validate all input data with strict schemas
- Use parameterized queries to prevent SQL injection
- Implement CORS properly for API endpoints
- Add request size limits to prevent DoS attacks

## Deployment Considerations

### Environment Variables
- Add language-related configuration
- Configure default locale per environment
- Set up translation file paths
- Configure notification service endpoints

### Migration Strategy
- Create database migration scripts for new tables
- Populate default preferences for existing users
- Create indexes in separate migration (avoid blocking)
- Add rollback procedures for failed migrations

### Rollout Plan
1. Deploy backend API additions first
2. Deploy frontend with feature flags disabled
3. Gradually enable features per user percentage
4. Monitor error rates and performance metrics
5. Full rollout after 7 days of stable operation

## Success Metrics

### User Engagement
- Track language preference distribution
- Monitor notification interaction rate
- Measure time spent on discover view
- Track follow/unfollow activity
- Measure search query success rate

### Performance Metrics
- API response time < 200ms for 95th percentile
- Translation loading time < 100ms
- Notification polling overhead < 50ms
- Search query execution time < 500ms
- Page load time < 2 seconds

### Quality Metrics
- Translation coverage: 100% for priority 1 components
- Test coverage: > 80% for new code
- Zero critical security vulnerabilities
- Error rate < 0.1% of requests

## Future Enhancements

### Phase 9 (Future)
- Real-time messaging with WebSocket
- Video/audio call support
- Advanced search with filters
- Content moderation tools
- Admin dashboard

### Phase 10 (Future)
- Mobile app (React Native or Flutter)
- Progressive Web App (PWA) features
- Offline mode support
- Advanced analytics dashboard
- Monetization features

### Additional Languages
- Spanish (es)
- German (de)
- French (fr)
- Chinese (zh)
- Japanese (ja)

## Conclusion

This design provides a comprehensive roadmap for continuing the Wall Social Platform development. Priority should be given to:
1. Fixing the wall route issue (immediate)
2. Implementing Phase 4 (Wall View) and Phase 8 (Settings with i18n)
3. Completing Phase 5 (Profile, Discover, Notifications)
4. Implementing remaining phases based on user demand

The multi-language support implementation will significantly improve user experience for Russian-speaking users while maintaining a scalable architecture for additional languages in the future.

**Objective**: Help users discover new walls, posts, and users

**Features**:
- Trending walls based on activity metrics
- Popular posts from last 24 hours/week
- Suggested users to follow based on interests
- Search functionality (walls, users, posts)
- Category/tag filtering
- Infinite scroll pagination

**Backend Requirements**:
- GET `/api/v1/discover/trending-walls` - Needs implementation
- GET `/api/v1/discover/popular-posts` - Needs implementation
- GET `/api/v1/discover/suggested-users` - Needs implementation
- GET `/api/v1/search?q=query&type=wall|user|post` - Needs implementation

**Ranking Algorithm Considerations**:
- Trending walls: Post frequency + reaction count + view count (weighted by time decay)
- Popular posts: Reaction count + comment count + share count (last 7 days)
- Suggested users: Mutual connections + shared interests + activity level

#### Notifications View Implementation

**Objective**: Display real-time notifications for user interactions

**Notification Types**:
- New follower
- Reaction on post
- Comment on post
- Reply to comment
- Wall mention
- User mention
- Bricks transaction
- AI application status change

**Frontend Components**:
- Notification list with infinite scroll
- Notification item rendering by type
- Mark as read functionality
- Mark all as read action
- Filter by notification type
- Real-time updates via polling or WebSocket

**Backend Requirements**:
- Notifications table in database (needs schema design)
- GET `/api/v1/notifications` - Needs implementation
- PATCH `/api/v1/notifications/:notificationId/read` - Needs implementation
- POST `/api/v1/notifications/mark-all-read` - Needs implementation
- GET `/api/v1/notifications/unread-count` - Needs implementation

**Database Schema Requirements**:

Table: notifications
- notification_id (PRIMARY KEY, AUTO_INCREMENT)
- user_id (recipient, FOREIGN KEY)
- actor_id (who triggered notification, FOREIGN KEY, nullable)
- notification_type (ENUM)
- target_type (wall, post, comment, user, etc.)
- target_id (reference to related entity)
- content (JSON with notification details)
- is_read (BOOLEAN, default FALSE)
- created_at (TIMESTAMP)

### Phase 6: AI Generation View (Priority: Medium)

**Objective**: Provide UI for AI-powered web application generation

**Frontend Components**:
- AIGenerateView.vue structure:
  - Prompt input textarea with character counter
  - Model selection dropdown
  - Priority selection (normal, high)
  - Advanced options panel (creativity, length, features)
  - Cost calculator display (bricks required)
  - Generation history list
  - Job status tracking with progress indicator
  - Generated application preview iframe
  - Actions: Remix, Fork, Download, Share

**Real-time Status Updates**:
- Poll job status endpoint every 2-3 seconds during generation
- Display queue position and estimated time
- Show generation progress percentage
- Display error messages if generation fails

**Backend - Already Implemented**:
- POST `/api/v1/ai/generate`
- GET `/api/v1/ai/jobs/:jobId`
- GET `/api/v1/ai/apps/:appId`
- POST `/api/v1/ai/apps/:appId/remix`
- POST `/api/v1/ai/apps/:appId/fork`

**User Experience Flow**:
1. User enters prompt describing desired application
2. System calculates brick cost based on complexity
3. User confirms and submits generation request
4. Job added to queue, user redirected to status page
5. Real-time status updates display queue position
6. Upon completion, preview generated application
7. User can publish, remix, fork, or discard

### Phase 7: Messaging View (Priority: Medium)

**Objective**: Enable private messaging between users

**Frontend Components**:
- MessagesView.vue structure:
  - Two-panel layout (conversation list + active conversation)
  - Conversation list with search/filter
  - Message thread display
  - Message input with emoji picker
  - Typing indicators
  - Read receipts
  - Media attachment support
  - Real-time message updates

**Backend Requirements**:
- Database schema for conversations and messages
- GET `/api/v1/conversations` - List user conversations
- POST `/api/v1/conversations` - Start new conversation
- GET `/api/v1/conversations/:conversationId/messages` - Get messages
- POST `/api/v1/conversations/:conversationId/messages` - Send message
- PATCH `/api/v1/conversations/:conversationId/read` - Mark as read
- DELETE `/api/v1/conversations/:conversationId` - Delete conversation
- GET `/api/v1/conversations/:conversationId/typing` - Typing status

**Database Schema Requirements**:

Table: conversations
- conversation_id (PRIMARY KEY, AUTO_INCREMENT)
- conversation_type (direct, group)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

Table: conversation_participants
- participant_id (PRIMARY KEY, AUTO_INCREMENT)
- conversation_id (FOREIGN KEY)
- user_id (FOREIGN KEY)
- joined_at (TIMESTAMP)
- last_read_at (TIMESTAMP)

Table: messages
- message_id (PRIMARY KEY, AUTO_INCREMENT)
- conversation_id (FOREIGN KEY)
- sender_id (FOREIGN KEY)
- message_text (TEXT)
- message_type (text, image, file)
- attachment_url (VARCHAR, nullable)
- is_deleted (BOOLEAN, default FALSE)
- created_at (TIMESTAMP)
- edited_at (TIMESTAMP, nullable)

**Real-time Implementation**:
- Use polling (every 3-5 seconds) for message updates
- Alternative: Implement Server-Sent Events (SSE) for better performance
- Display typing indicators with debounce

### Phase 8: Settings View (Priority: High)

**Objective**: Provide comprehensive user settings and preferences management

**Frontend Components**:
- SettingsView.vue with tabbed navigation:

**Tab 1: Account Settings**
- Username change (with availability check)
- Email management
- Password change
- Account deletion (with confirmation)
- OAuth connections management (Google, Yandex, Telegram)

**Tab 2: Profile Settings**
- Bio editing
- Social links management
- Location settings
- Avatar upload
- Cover image upload

**Tab 3: Privacy Settings**
- Default wall privacy
- Who can follow user
- Who can message user
- Activity visibility
- Data sharing preferences

**Tab 4: Notification Preferences**
- Email notifications toggle by type
- Push notification preferences
- Notification frequency settings
- Quiet hours configuration

**Tab 5: Appearance Settings**
- Theme selection (light, dark, green, cream, blue, high-contrast)
- Language selection (English, Russian)
- Font size preferences
- Accessibility options

**Tab 6: Bricks & Billing**
- Current balance display
- Transaction history
- Daily claim button
- Transfer bricks feature
- Purchase options (future)

**Backend Requirements**:
- PATCH `/api/v1/users/me/settings` - Update user settings
- GET `/api/v1/users/me/settings` - Retrieve current settings
- POST `/api/v1/users/me/change-password` - Change password
- POST `/api/v1/users/me/upload-avatar` - Upload avatar
- POST `/api/v1/users/me/upload-cover` - Upload cover image
- DELETE `/api/v1/users/me/account` - Delete account

## Multi-Language Support (i18n) Implementation

### Objective
Implement internationalization for English (default) and Russian languages across entire frontend application.

### Technology Selection
**Recommended**: Vue I18n library for Vue 3

**Rationale**:
- Native Vue 3 support with Composition API
- Type-safe translation keys
- Lazy loading for language files
- Pluralization and formatting support
- Easy integration with existing project structure

### Implementation Architecture

#### Directory Structure
```
frontend/src/
  ├── i18n/
  │   ├── index.ts              # i18n configuration
  │   ├── locales/
  │   │   ├── en.json           # English translations
  │   │   ├── ru.json           # Russian translations
  │   │   └── index.ts          # Locale exports
  │   └── helpers.ts            # Translation utilities
```

#### Translation File Structure

Organize translations by feature domain:
- common (buttons, actions, labels)
- auth (login, register, password reset)
- navigation (menu items, links)
- walls (wall-related terms)
- posts (post creation, editing)
- profile (profile fields, bio)
- settings (all settings sections)
- messages (messaging interface)
- notifications (notification types)
- errors (error messages, validation)
- success (success messages)
- time (time formatting, dates)

#### Locale Storage Strategy
- Store selected language in browser localStorage
- Key: `wall_app_locale`
- Fallback to browser language detection
- Default to English if unsupported language

#### Language Switcher Component
- Dropdown in settings view
- Quick toggle in navigation bar (optional)
- Persist selection immediately on change
- Reload translations without page refresh

#### Translation Key Naming Convention
- Use dot notation for nested keys
- Format: `domain.section.key`
- Examples:
  - `auth.login.title` → "Login" / "Войти"
  - `common.buttons.submit` → "Submit" / "Отправить"
  - `errors.validation.required` → "This field is required" / "Это поле обязательно"

#### Special Formatting Considerations

**Pluralization**:
English vs Russian have different pluralization rules
- English: 1 post, 2 posts
- Russian: 1 пост, 2 поста, 5 постов

**Date/Time Formatting**:
- Use locale-specific date formats
- English: MM/DD/YYYY
- Russian: DD.MM.YYYY

**Number Formatting**:
- Decimal separator differences
- English: 1,234.56
- Russian: 1 234,56

#### Components to Localize

**Priority 1 (High)**: 
- Authentication forms (Login, Register)
- Navigation menu
- Settings interface
- Error messages
- Success messages

**Priority 2 (Medium)**:
- Profile view
- Wall view
- Post composer
- Comments section

**Priority 3 (Low)**:
- Discover view
- AI generation view
- Messaging interface
- Notifications

#### Backend Integration
- API responses remain in English (technical data)
- User-generated content remains in original language
- System messages and notifications use user's language preference
- Store user language preference in database (users.preferred_language)

#### Implementation Steps

**Step 1**: Install and configure Vue I18n
- Add dependency to package.json
- Create i18n configuration file
- Integrate with Vue app initialization

**Step 2**: Create initial translation files
- Define English baseline translations
- Create Russian translations
- Organize by feature domains

**Step 3**: Update existing components
- Replace hardcoded strings with translation keys
- Update template syntax to use $t() function
- Add pluralization where needed

**Step 4**: Implement language switcher
- Add language selection to settings
- Create language toggle component
- Persist selection in localStorage and database

**Step 5**: Test language switching
- Verify all strings translate correctly
- Test pluralization rules
- Validate date/time formatting
- Check RTL vs LTR layout (future consideration)

**Step 6**: Add language to user preferences
- Update user model to include preferred_language field
- Save language selection to backend
- Load user language on authentication

### Translation Coverage

**Estimated Translation Keys**: 300-500 keys for initial implementation

**Key Categories**:
- Navigation: ~20 keys
- Authentication: ~40 keys
- Walls: ~50 keys
- Posts: ~60 keys
- Profile: ~40 keys
- Settings: ~80 keys
- Messages: ~50 keys
- Notifications: ~40 keys
- Common/Shared: ~30 keys
- Errors/Validation: ~50 keys

## Backend API Additions Required

### Follow System

**Endpoint**: POST `/api/v1/users/:userId/follow`
**Purpose**: Follow a user
**Authentication**: Required
**Response**: Success confirmation with updated follower count

**Endpoint**: DELETE `/api/v1/users/:userId/follow`
**Purpose**: Unfollow a user
**Authentication**: Required
**Response**: Success confirmation

**Endpoint**: GET `/api/v1/users/:userId/followers`
**Purpose**: Get list of user's followers
**Authentication**: Optional (depends on privacy)
**Response**: Paginated list of user profiles

**Endpoint**: GET `/api/v1/users/:userId/following`
**Purpose**: Get list of users being followed
**Authentication**: Optional (depends on privacy)
**Response**: Paginated list of user profiles

**Database Schema**:
Table: user_follows
- follow_id (PRIMARY KEY, AUTO_INCREMENT)
- follower_id (FOREIGN KEY → users.user_id)
- following_id (FOREIGN KEY → users.user_id)
- created_at (TIMESTAMP)
- UNIQUE constraint on (follower_id, following_id)

### Discover System

**Endpoint**: GET `/api/v1/discover/trending-walls`
**Purpose**: Retrieve trending walls based on activity
**Query Parameters**: limit, offset, timeframe (24h, 7d, 30d)
**Response**: Array of wall objects with activity metrics

**Endpoint**: GET `/api/v1/discover/popular-posts`
**Purpose**: Retrieve popular posts across platform
**Query Parameters**: limit, offset, timeframe
**Response**: Array of post objects with engagement metrics

**Endpoint**: GET `/api/v1/discover/suggested-users`
**Purpose**: Get personalized user suggestions
**Authentication**: Optional
**Response**: Array of user profiles with follow status

**Endpoint**: GET `/api/v1/search`
**Purpose**: Global search across walls, users, posts
**Query Parameters**: q (query), type (wall|user|post|all), limit, offset
**Response**: Categorized search results

### Notifications System

**Endpoint**: GET `/api/v1/notifications`
**Purpose**: Retrieve user notifications
**Query Parameters**: limit, offset, filter (unread|all)
**Response**: Paginated notification list with metadata

**Endpoint**: PATCH `/api/v1/notifications/:notificationId/read`
**Purpose**: Mark single notification as read
**Response**: Success confirmation

**Endpoint**: POST `/api/v1/notifications/mark-all-read`
**Purpose**: Mark all notifications as read
**Response**: Success confirmation with count

**Endpoint**: GET `/api/v1/notifications/unread-count`
**Purpose**: Get count of unread notifications
**Response**: Integer count

### Settings Management

**Endpoint**: GET `/api/v1/users/me/settings`
**Purpose**: Retrieve all user settings
**Response**: Settings object with all preferences

**Endpoint**: PATCH `/api/v1/users/me/settings`
**Purpose**: Update user settings (partial update)
**Request Body**: Settings fields to update
**Response**: Updated settings object

**Endpoint**: POST `/api/v1/users/me/change-password`
**Purpose**: Change user password
**Request Body**: current_password, new_password
**Response**: Success confirmation

**Endpoint**: POST `/api/v1/users/me/upload-avatar`
**Purpose**: Upload user avatar image
**Request**: Multipart form data with image file
**Response**: Avatar URL and success message

**Endpoint**: DELETE `/api/v1/users/me/account`
**Purpose**: Permanently delete user account
**Request Body**: password (confirmation)
**Response**: Success message

## Database Schema Extensions

### User Preferences Table
```
Table: user_preferences
- preference_id (PRIMARY KEY, AUTO_INCREMENT)
- user_id (FOREIGN KEY → users.user_id, UNIQUE)
- theme (VARCHAR, default 'light')
- language (VARCHAR, default 'en')
- email_notifications (BOOLEAN, default TRUE)
- push_notifications (BOOLEAN, default TRUE)
- notification_frequency (ENUM: instant, hourly, daily)
- privacy_default_wall (ENUM: public, private, followers)
- privacy_can_follow (ENUM: everyone, mutual, nobody)
- privacy_can_message (ENUM: everyone, followers, mutual)
- accessibility_font_size (ENUM: small, medium, large)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Additional Indexes for Performance
- Index on notifications.user_id for faster notification retrieval
- Index on notifications.is_read for filtering unread
- Composite index on (user_id, created_at DESC) for pagination
- Index on user_follows (follower_id, following_id) for relationship queries
- Full-text index on posts.content_text for search functionality
- Index on walls.wall_slug for slug-based lookups

## Testing Strategy

### Frontend Testing
- Unit tests for translation helper functions
- Component tests for language switching
- Integration tests for API calls with different locales
- E2E tests for critical user flows in both languages

### Backend Testing
- API endpoint testing for all new routes
- Database query performance testing
- Privacy and permission validation tests
- Pagination and filtering correctness

### User Acceptance Testing
- Test language switching across all views
- Verify translation accuracy and context
- Test notification creation and display
- Validate follow/unfollow functionality
- Test search functionality with various queries

## Performance Considerations

### Frontend Optimization
- Lazy load language files (only load selected language)
- Cache translations in memory after first load
- Implement virtual scrolling for long lists (notifications, messages)
- Optimize image loading with lazy loading and WebP format
- Minimize bundle size by code splitting per route

### Backend Optimization
- Implement Redis caching for frequently accessed data (trending walls, popular posts)
- Use database connection pooling
- Optimize SQL queries with proper indexes
- Implement rate limiting on expensive operations (search, AI generation)
- Cache user preferences in Redis after first load

### Database Optimization
- Regular index maintenance
- Query execution plan analysis
- Implement read replicas for heavy read operations
- Archive old notifications after 90 days
- Optimize full-text search with dedicated search engine (future: Elasticsearch)

## Security Considerations

### Authentication & Authorization
- Validate user permissions for all protected routes
- Implement CSRF protection for state-changing operations
- Rate limit authentication attempts
- Validate session tokens on every request

### Data Privacy
- Respect privacy settings for user data exposure
- Implement soft delete for user accounts (retain for 30 days)
- Encrypt sensitive user data in database
- Sanitize user input to prevent XSS attacks
- Validate and sanitize HTML content in posts

### API Security
- Implement request rate limiting per user/IP
- Validate all input data with strict schemas
- Use parameterized queries to prevent SQL injection
- Implement CORS properly for API endpoints
- Add request size limits to prevent DoS attacks

## Deployment Considerations

### Environment Variables
- Add language-related configuration
- Configure default locale per environment
- Set up translation file paths
- Configure notification service endpoints

### Migration Strategy
- Create database migration scripts for new tables
- Populate default preferences for existing users
- Create indexes in separate migration (avoid blocking)
- Add rollback procedures for failed migrations

### Rollout Plan
1. Deploy backend API additions first
2. Deploy frontend with feature flags disabled
3. Gradually enable features per user percentage
4. Monitor error rates and performance metrics
5. Full rollout after 7 days of stable operation

## Success Metrics

### User Engagement
- Track language preference distribution
- Monitor notification interaction rate
- Measure time spent on discover view
- Track follow/unfollow activity
- Measure search query success rate

### Performance Metrics
- API response time < 200ms for 95th percentile
- Translation loading time < 100ms
- Notification polling overhead < 50ms
- Search query execution time < 500ms
- Page load time < 2 seconds

### Quality Metrics
- Translation coverage: 100% for priority 1 components
- Test coverage: > 80% for new code
- Zero critical security vulnerabilities
- Error rate < 0.1% of requests

## Future Enhancements

### Phase 9 (Future)
- Real-time messaging with WebSocket
- Video/audio call support
- Advanced search with filters
- Content moderation tools
- Admin dashboard

### Phase 10 (Future)
- Mobile app (React Native or Flutter)
- Progressive Web App (PWA) features
- Offline mode support
- Advanced analytics dashboard
- Monetization features

### Additional Languages
- Spanish (es)
- German (de)
- French (fr)
- Chinese (zh)
- Japanese (ja)

## Conclusion

This design provides a comprehensive roadmap for continuing the Wall Social Platform development. Priority should be given to:
1. Fixing the wall route issue (immediate)
2. Implementing Phase 4 (Wall View) and Phase 8 (Settings with i18n)
3. Completing Phase 5 (Profile, Discover, Notifications)
4. Implementing remaining phases based on user demand

The multi-language support implementation will significantly improve user experience for Russian-speaking users while maintaining a scalable architecture for additional languages in the future.
#### Discover View Implementation
