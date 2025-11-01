<?php
/**
 * Wall Social Platform - Post Controller
 * 
 * Handles post operations
 */

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
