# Post View Count Batch Processing and Open Count Implementation

## Changes Made

### 1. Database Migration
- Created migration `022_add_open_count_to_posts.sql` to add `open_count` column to posts table
- Added indexes for performance optimization

### 2. Backend (PHP)

#### Post Model (`src/Models/Post.php`)
- Added `open_count` to all SELECT queries to include the new column
- Added `incrementOpenCount()` method to increment open count for a single post
- Added `batchIncrementViewCounts()` method to increment view counts for multiple posts in a single query
- Added `open_count` to the public data returned by `getPublicData()`

#### Post Controller (`src/Controllers/PostController.php`)
- Added `batchIncrementViewCounts()` method to handle batch view count requests
- Added `incrementOpenCount()` method to handle open count requests

#### API Routes (`public/api.php`)
- Added POST `/api/v1/posts/batch-view` endpoint for batch view count processing
- Added POST `/api/v1/posts/{postId}/open` endpoint for incrementing open counts

### 3. Frontend (Vue.js)

#### Posts API Service (`frontend/src/services/api/posts.ts`)
- Added `batchIncrementViewCounts()` method
- Added `incrementOpenCount()` method

#### WallView Component (`frontend/src/views/WallView.vue`)
- Implemented batch processing for view counts instead of individual requests
- Added tracking for posts that have been batch processed to avoid duplicate processing
- Added open count increment when posts are fully opened (in modal)
- Replaced individual view count increments with batch processing in `loadPosts()` method

## Benefits

1. **Reduced Database Load**: Instead of making individual requests for each post view count, we now batch process multiple posts in a single database query
2. **Better Performance**: Fewer HTTP requests and database queries result in improved performance
3. **Open Count Tracking**: Added ability to track how many times posts are fully opened/viewed, separate from view counts
4. **Scalability**: The batch processing approach scales better as the number of posts increases

## Implementation Details

### Batch Processing Logic
- When posts are loaded, instead of incrementing view counts individually, we collect all unprocessed post IDs
- These IDs are sent in a single request to the batch endpoint
- The backend processes all view count increments in one database query
- If batch processing fails, it falls back to individual increments

### Open Count Logic
- When a user fully opens a post (clicks to view it in detail), the open count is incremented
- This provides separate metrics for post views vs. detailed views