<?php
/**
 * Wall Social Platform - Follow Controller
 * 
 * Handles user follow/unfollow operations
 */

class FollowController
{
    /**
     * Follow a user
     * POST /api/v1/users/:userId/follow
     */
    public static function followUser($params)
    {
        $currentUser = AuthMiddleware::requireAuth();
        $targetUserId = $params['userId'] ?? null;
        
        if (!$targetUserId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }
        
        // Prevent self-follow
        if ($currentUser['user_id'] == $targetUserId) {
            self::jsonResponse(false, ['code' => 'SELF_FOLLOW'], 'Cannot follow yourself', 400);
        }
        
        // Check if target user exists
        $sql = "SELECT user_id, username FROM users WHERE user_id = ?";
        $targetUser = Database::fetchOne($sql, [$targetUserId]);
        
        if (!$targetUser) {
            self::jsonResponse(false, ['code' => 'USER_NOT_FOUND'], 'User not found', 404);
        }
        
        // Check if already following
        $sql = "SELECT follow_id FROM user_follows 
                WHERE follower_id = ? AND following_id = ?";
        $existing = Database::fetchOne($sql, [$currentUser['user_id'], $targetUserId]);
        
        if ($existing) {
            self::jsonResponse(false, ['code' => 'ALREADY_FOLLOWING'], 'Already following this user', 400);
        }
        
        try {
            // Create follow relationship
            $sql = "INSERT INTO user_follows (follower_id, following_id, created_at) 
                    VALUES (?, ?, NOW())";
            Database::query($sql, [$currentUser['user_id'], $targetUserId]);
            
            // Update follower counts
            $sql = "UPDATE users SET following_count = following_count + 1 WHERE user_id = ?";
            Database::query($sql, [$currentUser['user_id']]);
            
            $sql = "UPDATE users SET followers_count = followers_count + 1 WHERE user_id = ?";
            Database::query($sql, [$targetUserId]);
            
            // Create notification
            NotificationService::createFollowNotification($currentUser['user_id'], $targetUserId);
            
            // Get updated follower count
            $sql = "SELECT followers_count FROM users WHERE user_id = ?";
            $result = Database::fetchOne($sql, [$targetUserId]);
            
            self::jsonResponse(true, [
                'message' => 'User followed successfully',
                'followers_count' => (int)$result['followers_count']
            ]);
            
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'FOLLOW_FAILED'], $e->getMessage(), 500);
        }
    }
    
    /**
     * Unfollow a user
     * DELETE /api/v1/users/:userId/follow
     */
    public static function unfollowUser($params)
    {
        $currentUser = AuthMiddleware::requireAuth();
        $targetUserId = $params['userId'] ?? null;
        
        if (!$targetUserId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }
        
        // Check if following
        $sql = "SELECT follow_id FROM user_follows 
                WHERE follower_id = ? AND following_id = ?";
        $existing = Database::fetchOne($sql, [$currentUser['user_id'], $targetUserId]);
        
        if (!$existing) {
            self::jsonResponse(false, ['code' => 'NOT_FOLLOWING'], 'Not following this user', 400);
        }
        
        try {
            // Delete follow relationship
            $sql = "DELETE FROM user_follows 
                    WHERE follower_id = ? AND following_id = ?";
            Database::query($sql, [$currentUser['user_id'], $targetUserId]);
            
            // Update follower counts
            $sql = "UPDATE users SET following_count = following_count - 1 WHERE user_id = ?";
            Database::query($sql, [$currentUser['user_id']]);
            
            $sql = "UPDATE users SET followers_count = followers_count - 1 WHERE user_id = ?";
            Database::query($sql, [$targetUserId]);
            
            // Get updated follower count
            $sql = "SELECT followers_count FROM users WHERE user_id = ?";
            $result = Database::fetchOne($sql, [$targetUserId]);
            
            self::jsonResponse(true, [
                'message' => 'User unfollowed successfully',
                'followers_count' => (int)$result['followers_count']
            ]);
            
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'UNFOLLOW_FAILED'], $e->getMessage(), 500);
        }
    }
    
    /**
     * Get user's followers
     * GET /api/v1/users/:userId/followers
     */
    public static function getFollowers($params)
    {
        $userId = $params['userId'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        if (!$userId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }
        
        $sql = "SELECT u.user_id, u.username, u.display_name, u.avatar_url, u.bio, 
                uf.created_at as followed_at
                FROM user_follows uf
                JOIN users u ON uf.follower_id = u.user_id
                WHERE uf.following_id = ?
                ORDER BY uf.created_at DESC
                LIMIT ? OFFSET ?";
        
        $followers = Database::fetchAll($sql, [$userId, $limit, $offset]);
        
        self::jsonResponse(true, [
            'followers' => $followers,
            'count' => count($followers),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    /**
     * Get users being followed
     * GET /api/v1/users/:userId/following
     */
    public static function getFollowing($params)
    {
        $userId = $params['userId'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        if (!$userId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }
        
        $sql = "SELECT u.user_id, u.username, u.display_name, u.avatar_url, u.bio,
                uf.created_at as followed_at
                FROM user_follows uf
                JOIN users u ON uf.following_id = u.user_id
                WHERE uf.follower_id = ?
                ORDER BY uf.created_at DESC
                LIMIT ? OFFSET ?";
        
        $following = Database::fetchAll($sql, [$userId, $limit, $offset]);
        
        self::jsonResponse(true, [
            'following' => $following,
            'count' => count($following),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    /**
     * Get follow status between users
     * GET /api/v1/users/:userId/follow-status
     */
    public static function getFollowStatus($params)
    {
        $currentUser = AuthMiddleware::optionalAuth();
        $targetUserId = $params['userId'] ?? null;
        
        if (!$targetUserId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }
        
        if (!$currentUser) {
            self::jsonResponse(true, [
                'is_following' => false,
                'follows_you' => false,
                'is_mutual' => false
            ]);
            return;
        }
        
        // Check if current user follows target
        $sql = "SELECT follow_id FROM user_follows 
                WHERE follower_id = ? AND following_id = ?";
        $isFollowing = Database::fetchOne($sql, [$currentUser['user_id'], $targetUserId]);
        
        // Check if target follows current user
        $sql = "SELECT follow_id FROM user_follows 
                WHERE follower_id = ? AND following_id = ?";
        $followsYou = Database::fetchOne($sql, [$targetUserId, $currentUser['user_id']]);
        
        $isFollowingBool = (bool)$isFollowing;
        $followsYouBool = (bool)$followsYou;
        
        self::jsonResponse(true, [
            'is_following' => $isFollowingBool,
            'follows_you' => $followsYouBool,
            'is_mutual' => $isFollowingBool && $followsYouBool
        ]);
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
