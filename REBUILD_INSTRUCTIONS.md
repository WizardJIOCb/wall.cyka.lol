# Rebuild Instructions

## Summary of Changes Made

1. **Fixed Reaction Functionality**
   - Updated PostCard.vue to use ReactionPicker component instead of simple buttons
   - Properly integrated reaction tracking with the backend

2. **Fixed Comment Functionality**
   - Verified CommentSection component is properly integrated in PostCard.vue
   - Ensured comments can be toggled and displayed correctly

3. **Implemented View Counting**
   - Added `view_count` column to the `posts` table via migration
   - Added `incrementViewCount` method to Post model
   - Created API endpoint `/api/v1/posts/{postId}/view` to increment view counts
   - Added `incrementViewCount` method to PostController

4. **Implemented Repost Functionality**
   - Created `useRepost` composable for handling repost operations
   - Updated PostCard.vue to use the repost functionality
   - Connected repost button to backend API endpoint

## Database Migration

Run the following migration to add the view_count column to the posts table:

```sql
-- File: database/migrations/019_add_view_count_to_posts.sql
ALTER TABLE posts ADD COLUMN view_count INT DEFAULT 0 NOT NULL AFTER is_deleted;
ALTER TABLE posts ADD INDEX idx_view_count (view_count);
```

## Rebuild Steps

1. **Apply Database Migrations**
   ```bash
   # Run the migration script to add view_count column
   php database/run_migrations.php
   ```

2. **Rebuild Frontend**
   ```bash
   # Navigate to frontend directory
   cd frontend
   
   # Clean previous build artifacts
   rm -rf node_modules/.vite .vite dist ../public/assets/WallView-*.js ../public/assets/WallView-*.css
   
   # Install dependencies (if needed)
   npm install
   
   # Build the frontend
   npm run build
   ```

3. **Restart Services**
   - Restart the web server (Apache/Nginx)
   - If using Docker, restart containers:
     ```bash
     docker-compose restart
     ```

4. **Clear Browser Cache**
   - Instruct users to perform a hard refresh (Ctrl+Shift+R) or clear browser cache
   - This ensures the new frontend assets are loaded

## Testing

After rebuild, verify the following functionality works:

1. **Reactions**: Clicking the reaction button should open the reaction picker and allow selecting reactions
2. **Comments**: Clicking the comment button should toggle the comment section
3. **View Counting**: Posts should increment their view count when viewed
4. **Reposting**: Clicking the repost button should create a repost of the original post

## Troubleshooting

1. **If reactions don't work**:
   - Check browser console for JavaScript errors
   - Verify API endpoints are accessible

2. **If comments don't load**:
   - Check network tab for failed API requests
   - Verify database connection

3. **If view counts aren't incrementing**:
   - Check that the database migration was applied successfully
   - Verify the API endpoint `/api/v1/posts/{postId}/view` is working

4. **If reposting fails**:
   - Check that the original post allows reposts
   - Verify the user has permission to repost