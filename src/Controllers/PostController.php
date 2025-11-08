<?php
/**
 * Wall Social Platform - Post Controller
 * 
 * Handles post operations
 */

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Post;
use App\Models\Wall;
use App\Models\MediaAttachment;
use App\Models\Location;
use App\Models\Reaction;
use App\Utils\Validator;
use Exception;

class PostController
{
    /**
     * Get feed posts (home feed)
     * GET /api/v1/posts/feed
     */
    public static function getFeed()
    {
        $user = AuthMiddleware::requireAuth();
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
        $sortBy = $_GET['sort_by'] ?? 'recent';

        $offset = ($page - 1) * $perPage;
        $limit = $perPage;

        try {
            // Get posts from user's own wall and followed users' walls
            // For now, just get recent posts from all public walls
            $posts = Post::getFeedPosts($user['user_id'], $limit, $offset, $sortBy);
            
            // Get media for each post
            $postsWithMedia = array_map(function($post) {
                $media = MediaAttachment::getPostMedia($post['post_id']);
                $post['media_attachments'] = $media;
                return Post::getPublicData($post);
            }, $posts);

            self::jsonResponse(true, [
                'posts' => $postsWithMedia,
                'count' => count($postsWithMedia),
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => count($postsWithMedia) === $perPage
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'FEED_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Create new post
     * POST /api/v1/posts
     */
    public static function createPost()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['wall_id'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'wall_id is required', 400);
        }

        // Verify wall exists and user has permission
        $wall = Wall::findById($data['wall_id']);
        if (!$wall) {
            self::jsonResponse(false, ['code' => 'WALL_NOT_FOUND'], 'Wall not found', 404);
        }

        if ($wall['user_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not have permission to post on this wall', 403);
        }

        try {
            // Sanitize content
            if (isset($data['content_text'])) {
                $data['content_text'] = Validator::sanitize($data['content_text']);
            }
            if (isset($data['content_html'])) {
                $data['content_html'] = self::sanitizeHtml($data['content_html']);
            }

            $data['author_id'] = $user['user_id'];
            
            // Create post
            $post = Post::create($data);
            
            // Handle media attachments if provided
            if (!empty($data['media_attachments']) && is_array($data['media_attachments'])) {
                foreach ($data['media_attachments'] as $order => $media) {
                    $media['post_id'] = $post['post_id'];
                    $media['display_order'] = $order;
                    MediaAttachment::create($media);
                }
            }

            // Handle location if provided
            if (!empty($data['location'])) {
                $location = Location::findOrCreate($data['location']);
                Post::update($post['post_id'], ['location_id' => $location['location_id']]);
            }

            // Get complete post with media
            $completePost = Post::getPostWithMedia($post['post_id']);
            
            self::jsonResponse(true, [
                'post' => Post::getPublicData($completePost),
                'message' => 'Post created successfully'
            ], 201);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'CREATE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Get post by ID
     * GET /api/v1/posts/{postId}
     */
    public static function getPost($params)
    {
        $postId = $params['postId'] ?? null;

        if (!$postId) {
            self::jsonResponse(false, ['code' => 'INVALID_POST_ID'], 'Post ID is required', 400);
        }

        $post = Post::getPostWithMedia($postId);

        if (!$post) {
            self::jsonResponse(false, ['code' => 'POST_NOT_FOUND'], 'Post not found', 404);
        }

        // Check if user can view post based on wall privacy
        $currentUser = AuthMiddleware::optionalAuth();
        $userId = $currentUser ? $currentUser['user_id'] : null;

        $wall = Wall::findById($post['wall_id']);
        if (!Wall::canView($wall, $userId)) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not have permission to view this post', 403);
        }

        // Get user reaction if authenticated
        if ($userId) {
            $userReaction = Reaction::getUserReactions($userId, 'post', [$postId]);
            if (!empty($userReaction)) {
                $post['user_reaction'] = $userReaction[0]['reaction_type'];
            }
        }

        // Increment view count
        Post::incrementViewCount($postId);

        self::jsonResponse(true, [
            'post' => Post::getPublicData($post)
        ]);
    }

    /**
     * Get wall posts
     * GET /api/v1/walls/{wallId}/posts
     */
    public static function getWallPosts($params)
    {
        $wallId = $params['wallId'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        if (!$wallId) {
            self::jsonResponse(false, ['code' => 'INVALID_WALL_ID'], 'Wall ID is required', 400);
        }

        // Check wall access
        $wall = Wall::findById($wallId);
        if (!$wall) {
            self::jsonResponse(false, ['code' => 'WALL_NOT_FOUND'], 'Wall not found', 404);
        }

        $currentUser = AuthMiddleware::optionalAuth();
        $userId = $currentUser ? $currentUser['user_id'] : null;

        if (!Wall::canView($wall, $userId)) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not have permission to view this wall', 403);
        }

        $posts = Post::getWallPosts($wallId, $limit, $offset);
        
        // Get user reactions if authenticated
        if ($userId) {
            $postIds = array_map(function($post) { return $post['post_id']; }, $posts);
            if (!empty($postIds)) {
                $userReactions = Reaction::getUserReactions($userId, 'post', $postIds);
                $reactionMap = [];
                foreach ($userReactions as $r) {
                    $reactionMap[$r['reactable_id']] = $r['reaction_type'];
                }
                
                foreach ($posts as &$post) {
                    $post['user_reaction'] = $reactionMap[$post['post_id']] ?? null;
                }
            }
        }
        
        // Get media for each post
        $postsWithMedia = array_map(function($post) {
            $media = MediaAttachment::getPostMedia($post['post_id']);
            $post['media_attachments'] = $media;
            return Post::getPublicData($post);
        }, $posts);

        self::jsonResponse(true, [
            'posts' => $postsWithMedia,
            'count' => count($postsWithMedia),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Get user posts
     * GET /api/v1/users/{userId}/posts
     */
    public static function getUserPosts($params)
    {
        $userId = $params['userId'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        if (!$userId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }

        $posts = Post::getUserPosts($userId, $limit, $offset);
        
        // Get user reactions if authenticated
        $currentUser = AuthMiddleware::optionalAuth();
        $currentUserId = $currentUser ? $currentUser['user_id'] : null;
        if ($currentUserId) {
            $postIds = array_map(function($post) { return $post['post_id']; }, $posts);
            if (!empty($postIds)) {
                $userReactions = Reaction::getUserReactions($currentUserId, 'post', $postIds);
                $reactionMap = [];
                foreach ($userReactions as $r) {
                    $reactionMap[$r['reactable_id']] = $r['reaction_type'];
                }
                
                foreach ($posts as &$post) {
                    $post['user_reaction'] = $reactionMap[$post['post_id']] ?? null;
                }
            }
        }
        
        // Get media for each post
        $postsWithMedia = array_map(function($post) {
            $media = MediaAttachment::getPostMedia($post['post_id']);
            $post['media_attachments'] = $media;
            return Post::getPublicData($post);
        }, $posts);

        self::jsonResponse(true, [
            'posts' => $postsWithMedia,
            'count' => count($postsWithMedia),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Update post
     * PATCH /api/v1/posts/{postId}
     */
    public static function updatePost($params)
    {
        $user = AuthMiddleware::requireAuth();
        $postId = $params['postId'] ?? null;
        $data = self::getRequestData();

        if (!$postId) {
            self::jsonResponse(false, ['code' => 'INVALID_POST_ID'], 'Post ID is required', 400);
        }

        $post = Post::findById($postId);

        if (!$post) {
            self::jsonResponse(false, ['code' => 'POST_NOT_FOUND'], 'Post not found', 404);
        }

        // Check ownership
        if ($post['author_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not own this post', 403);
        }

        try {
            // Sanitize content
            if (isset($data['content_text'])) {
                $data['content_text'] = Validator::sanitize($data['content_text']);
            }
            if (isset($data['content_html'])) {
                $data['content_html'] = self::sanitizeHtml($data['content_html']);
            }

            Post::update($postId, $data);
            
            $updatedPost = Post::getPostWithMedia($postId);

            self::jsonResponse(true, [
                'post' => Post::getPublicData($updatedPost),
                'message' => 'Post updated successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'UPDATE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Delete post
     * DELETE /api/v1/posts/{postId}
     */
    public static function deletePost($params)
    {
        $user = AuthMiddleware::requireAuth();
        $postId = $params['postId'] ?? null;

        if (!$postId) {
            self::jsonResponse(false, ['code' => 'INVALID_POST_ID'], 'Post ID is required', 400);
        }

        $post = Post::findById($postId);

        if (!$post) {
            self::jsonResponse(false, ['code' => 'POST_NOT_FOUND'], 'Post not found', 404);
        }

        // Check ownership
        if ($post['author_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not own this post', 403);
        }

        try {
            Post::delete($postId);
            self::jsonResponse(true, ['message' => 'Post deleted successfully']);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'DELETE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Toggle pin post
     * POST /api/v1/posts/{postId}/pin
     */
    public static function togglePin($params)
    {
        $user = AuthMiddleware::requireAuth();
        $postId = $params['postId'] ?? null;
        $data = self::getRequestData();

        if (!$postId) {
            self::jsonResponse(false, ['code' => 'INVALID_POST_ID'], 'Post ID is required', 400);
        }

        $post = Post::findById($postId);

        if (!$post) {
            self::jsonResponse(false, ['code' => 'POST_NOT_FOUND'], 'Post not found', 404);
        }

        // Check ownership
        if ($post['author_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not own this post', 403);
        }

        try {
            $isPinned = $data['is_pinned'] ?? !$post['is_pinned'];
            Post::togglePin($postId, $isPinned);
            
            self::jsonResponse(true, [
                'is_pinned' => (bool)$isPinned,
                'message' => $isPinned ? 'Post pinned' : 'Post unpinned'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'PIN_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Repost a post
     * POST /api/v1/posts/{postId}/repost
     */
    public static function repostPost($params)
    {
        $user = AuthMiddleware::requireAuth();
        $postId = $params['postId'] ?? null;
        $data = self::getRequestData();

        if (!$postId) {
            self::jsonResponse(false, ['code' => 'INVALID_POST_ID'], 'Post ID is required', 400);
        }

        // Get the original post
        $originalPost = Post::findById($postId);

        if (!$originalPost) {
            self::jsonResponse(false, ['code' => 'POST_NOT_FOUND'], 'Post not found', 404);
        }

        // Check if the original post allows reposts
        $originalWall = Wall::findById($originalPost['wall_id']);
        if (!$originalWall['allow_reposts']) {
            self::jsonResponse(false, ['code' => 'REPOSTS_DISABLED'], 'Reposting is disabled for this post', 403);
        }

        // Prevent circular reposts (reposting a repost)
        if ($originalPost['is_repost']) {
            self::jsonResponse(false, ['code' => 'CIRCULAR_REPOST'], 'Cannot repost a reposted post', 400);
        }

        try {
            // Create repost data
            $repost = [
                'wall_id' => $data['wall_id'] ?? $user['wall_id'], // Default to user's own wall
                'author_id' => $user['user_id'],
                'post_type' => $originalPost['post_type'],
                'content_text' => $originalPost['content_text'],
                'content_html' => $originalPost['content_html'],
                'is_repost' => true,
                'original_post_id' => $postId,
                'repost_commentary' => isset($data['commentary']) ? Validator::sanitize($data['commentary']) : null
            ];

            // Create the repost
            $newPost = Post::create($repost);

            // Copy media attachments from original post if any
            $originalMedia = MediaAttachment::getPostMedia($postId);
            if (!empty($originalMedia)) {
                foreach ($originalMedia as $media) {
                    $newMedia = [
                        'post_id' => $newPost['post_id'],
                        'media_type' => $media['media_type'],
                        'file_url' => $media['file_url'],
                        'thumbnail_url' => $media['thumbnail_url'],
                        'file_size' => $media['file_size'],
                        'mime_type' => $media['mime_type'],
                        'width' => $media['width'],
                        'height' => $media['height'],
                        'duration' => $media['duration'],
                        'display_order' => $media['display_order']
                    ];
                    MediaAttachment::create($newMedia);
                }
            }

            // Increment share count on original post
            $sql = "UPDATE posts SET share_count = share_count + 1 WHERE post_id = ?";
            Database::query($sql, [$postId]);

            // Get complete repost with media
            $completeRepost = Post::getPostWithMedia($newPost['post_id']);

            self::jsonResponse(true, [
                'post' => Post::getPublicData($completeRepost),
                'message' => 'Post reposted successfully'
            ], 201);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'REPOST_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Increment post view count
     * POST /api/v1/posts/{postId}/view
     */
    public static function incrementViewCount($params)
    {
        $postId = $params['postId'] ?? null;

        if (!$postId) {
            self::jsonResponse(false, ['code' => 'INVALID_POST_ID'], 'Post ID is required', 400);
        }

        try {
            Post::incrementViewCount($postId);
            self::jsonResponse(true, ['message' => 'View count incremented']);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'VIEW_COUNT_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Batch increment post view counts
     * POST /api/v1/posts/batch-view
     */
    public static function batchIncrementViewCounts()
    {
        $data = self::getRequestData();
        $postIds = $data['post_ids'] ?? [];

        if (empty($postIds) || !is_array($postIds)) {
            self::jsonResponse(false, ['code' => 'INVALID_POST_IDS'], 'Post IDs array is required', 400);
        }

        // Validate that all post IDs are integers
        foreach ($postIds as $postId) {
            if (!is_numeric($postId)) {
                self::jsonResponse(false, ['code' => 'INVALID_POST_ID'], 'All post IDs must be numeric', 400);
            }
        }

        try {
            Post::batchIncrementViewCounts($postIds);
            self::jsonResponse(true, ['message' => 'View counts incremented']);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'VIEW_COUNTS_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Increment post open count
     * POST /api/v1/posts/{postId}/open
     */
    public static function incrementOpenCount($params)
    {
        $postId = $params['postId'] ?? null;

        if (!$postId) {
            self::jsonResponse(false, ['code' => 'INVALID_POST_ID'], 'Post ID is required', 400);
        }

        try {
            Post::incrementOpenCount($postId);
            self::jsonResponse(true, ['message' => 'Open count incremented']);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'OPEN_COUNT_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Add reaction to post
     * POST /api/v1/posts/{postId}/reactions
     */
    public static function addReactionToPost($params)
    {
        try {
            $currentUser = AuthMiddleware::requireAuth();
            $userId = $currentUser['user_id'];
            $postId = (int)$params['postId'];
            
            // Verify post exists
            $post = Post::findById($postId);
            if (!$post) {
                self::jsonResponse(false, ['code' => 'POST_NOT_FOUND'], 'Post not found', 404);
            }
            
            // Check if reactions are allowed on this post/wall
            $wall = Wall::findById($post['wall_id']);
            if ($wall && !$wall['allow_reactions']) {
                self::jsonResponse(false, ['code' => 'REACTIONS_DISABLED'], 'Reactions are not allowed on this wall', 403);
            }
            
            // Get request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate reaction type
            if (empty($input['reaction_type'])) {
                self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'Reaction type is required', 400);
            }
            
            $reactionType = $input['reaction_type'];
            $allowedReactions = ['like', 'dislike', 'love', 'haha', 'wow', 'sad', 'angry'];
            
            if (!in_array($reactionType, $allowedReactions)) {
                self::jsonResponse(false, ['code' => 'INVALID_REACTION'], 'Invalid reaction type', 400);
            }
            
            // Add or update reaction
            $result = Reaction::addOrUpdate($userId, 'post', $postId, $reactionType);
            
            // Get updated post data
            $updatedPost = Post::findById($postId);
            
            self::jsonResponse(true, [
                'action' => $result['action'],
                'post' => Post::getPublicData($updatedPost),
                'message' => $result['action'] === 'removed' ? 'Reaction removed' : 
                            ($result['action'] === 'updated' ? 'Reaction updated' : 'Reaction added')
            ], 200);
            
        } catch (Exception $e) {
            error_log("Error in addReactionToPost: " . $e->getMessage());
            self::jsonResponse(false, ['code' => 'REACTION_FAILED'], 'Failed to add reaction', 500);
        }
    }
    
    /**
     * Remove reaction from post
     * DELETE /api/v1/posts/{postId}/reactions/{reactionType}
     */
    public static function removeReactionFromPost($params)
    {
        try {
            $currentUser = AuthMiddleware::requireAuth();
            $userId = $currentUser['user_id'];
            $postId = (int)$params['postId'];
            
            // Verify post exists
            $post = Post::findById($postId);
            if (!$post) {
                self::jsonResponse(false, ['code' => 'POST_NOT_FOUND'], 'Post not found', 404);
            }
            
            // Remove reaction
            Reaction::removeReaction($userId, 'post', $postId);
            
            // Get updated post data
            $updatedPost = Post::findById($postId);
            
            self::jsonResponse(true, [
                'post' => Post::getPublicData($updatedPost),
                'message' => 'Reaction removed'
            ]);
            
        } catch (Exception $e) {
            error_log("Error in removeReactionFromPost: " . $e->getMessage());
            self::jsonResponse(false, ['code' => 'REACTION_REMOVAL_FAILED'], 'Failed to remove reaction', 500);
        }
    }
    
    /**
     * Get post reactions
     * GET /api/v1/posts/{postId}/reactions
     */
    public static function getPostReactions($params)
    {
        try {
            $postId = (int)$params['postId'];
            
            // Verify post exists
            $post = Post::findById($postId);
            if (!$post) {
                self::jsonResponse(false, ['code' => 'POST_NOT_FOUND'], 'Post not found', 404);
            }
            
            // Get reactions
            $reactions = Reaction::getReactions('post', $postId, 100);
            
            // Format reactions
            $formattedReactions = array_map(function($reaction) {
                return [
                    'reaction_id' => $reaction['reaction_id'],
                    'user_id' => $reaction['user_id'],
                    'reaction_type' => $reaction['reaction_type'],
                    'created_at' => $reaction['created_at'],
                    'user' => [
                        'user_id' => $reaction['user_id'],
                        'username' => $reaction['username'],
                        'display_name' => $reaction['display_name'],
                        'avatar_url' => $reaction['avatar_url']
                    ]
                ];
            }, $reactions);
            
            self::jsonResponse(true, [
                'reactions' => $formattedReactions,
                'count' => count($formattedReactions)
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getPostReactions: " . $e->getMessage());
            self::jsonResponse(false, ['code' => 'REACTIONS_FETCH_FAILED'], 'Failed to fetch reactions', 500);
        }
    }

    /**
     * Sanitize HTML content
     */
    private static function sanitizeHtml($html)
    {
        $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><blockquote><code><pre>';
        $cleaned = strip_tags($html, $allowedTags);
        $cleaned = preg_replace('/<(\w+)[^>]*\s(on\w+|javascript:|vbscript:)[^>]*>/i', '<$1>', $cleaned);
        return $cleaned;
    }

    /**
     * Get request JSON data
     */
    private static function getRequestData()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * Send JSON response
     */
    private static function jsonResponse($success, $data = [], $message = '', $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'data' => $data
        ];

        if ($message) {
            $response['message'] = $message;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
