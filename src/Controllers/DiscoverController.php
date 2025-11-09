<?php
/**
 * Wall Social Platform - Discover Controller
 * 
 * Handles content discovery: trending walls, popular posts, suggested users, search
 */

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Utils\Database;

class DiscoverController
{
    /**
     * Get trending walls
     * GET /api/v1/discover/trending-walls
     */
    public static function getTrendingWalls()
    {
        $timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '7d';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
        
        // Calculate date based on timeframe
        $dateMap = [
            '24h' => '1 DAY',
            '7d' => '7 DAY',
            '30d' => '30 DAY'
        ];
        $interval = $dateMap[$timeframe] ?? '7 DAY';
        
        // Calculate trending score: post_count * 2
        $sql = "SELECT w.*, u.username, u.display_name, u.avatar_url,
                COUNT(DISTINCT p.post_id) as post_count,
                (COUNT(DISTINCT p.post_id) * 2) as trending_score
                FROM walls w
                JOIN users u ON w.user_id = u.user_id
                LEFT JOIN posts p ON w.wall_id = p.wall_id 
                    AND p.created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                    AND p.is_deleted = FALSE
                WHERE w.privacy_level = 'public'
                GROUP BY w.wall_id
                HAVING post_count > 0
                ORDER BY trending_score DESC, w.created_at DESC
                LIMIT ?";
        
        $walls = Database::fetchAll($sql, [$limit]);
        
        self::jsonResponse(true, [
            'walls' => $walls,
            'timeframe' => $timeframe,
            'count' => count($walls)
        ]);
    }
    
    /**
     * Get popular posts
     * GET /api/v1/discover/popular-posts
     */
    public static function getPopularPosts()
    {
        $timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '7d';
        $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'popularity';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
        
        $dateMap = [
            '24h' => '1 DAY',
            '7d' => '7 DAY',
            '30d' => '30 DAY'
        ];
        $interval = $dateMap[$timeframe] ?? '7 DAY';
        
        // Determine sort order
        $orderBy = '';
        switch ($sortBy) {
            case 'reactions':
                $orderBy = 'p.reaction_count DESC, p.created_at DESC';
                break;
            case 'comments':
                $orderBy = 'p.comment_count DESC, p.created_at DESC';
                break;
            case 'views':
                $orderBy = 'p.view_count DESC, p.created_at DESC';
                break;
            case 'popularity':
            default:
                // Popularity score: weighted combination of reactions, comments, and views
                $orderBy = '(COALESCE(p.reaction_count, 0) * 2 + COALESCE(p.comment_count, 0) * 3 + COALESCE(p.view_count, 0) * 0.5) DESC, p.created_at DESC';
                break;
        }
        
        $sql = "SELECT p.*, u.username, u.display_name, u.avatar_url,
                w.wall_slug, w.display_name as wall_display_name
                FROM posts p
                JOIN users u ON p.author_id = u.user_id
                JOIN walls w ON p.wall_id = w.wall_id
                WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL $interval)
                    AND p.is_deleted = FALSE
                    AND w.privacy_level = 'public'
                ORDER BY {$orderBy}
                LIMIT ?";
        
        $posts = Database::fetchAll($sql, [$limit]);
        
        // Get media for each post
        foreach ($posts as &$post) {
            $mediaSql = "SELECT * FROM media_attachments WHERE post_id = ? ORDER BY display_order";
            $post['media_attachments'] = Database::fetchAll($mediaSql, [$post['post_id']]);
        }
        
        self::jsonResponse(true, [
            'posts' => $posts,
            'timeframe' => $timeframe,
            'sort' => $sortBy,
            'count' => count($posts)
        ]);
    }
    
    /**
     * Get suggested users to follow
     * GET /api/v1/discover/suggested-users
     */
    public static function getSuggestedUsers()
    {
        $currentUser = AuthMiddleware::optionalAuth();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        if ($currentUser) {
            // Find users followed by people the current user follows (friends of friends)
            $sql = "SELECT DISTINCT u.user_id, u.username, u.display_name, 
                    u.avatar_url, u.bio, u.followers_count, u.posts_count
                    FROM users u
                    WHERE u.user_id IN (
                        SELECT uf2.following_id
                        FROM user_follows uf1
                        JOIN user_follows uf2 ON uf1.following_id = uf2.follower_id
                        WHERE uf1.follower_id = ?
                            AND uf2.following_id != ?
                            AND uf2.following_id NOT IN (
                                SELECT following_id FROM user_follows WHERE follower_id = ?
                            )
                    )
                    AND u.is_active = TRUE
                    ORDER BY u.followers_count DESC, u.posts_count DESC
                    LIMIT ?";
            
            $users = Database::fetchAll($sql, [
                $currentUser['user_id'],
                $currentUser['user_id'],
                $currentUser['user_id'],
                $limit
            ]);
            
            // If not enough suggestions, add most active users
            if (count($users) < $limit) {
                $remaining = $limit - count($users);
                $existingIds = array_column($users, 'user_id');
                $existingIds[] = $currentUser['user_id'];
                $placeholders = str_repeat('?,', count($existingIds) - 1) . '?';
                
                $sql = "SELECT user_id, username, display_name, avatar_url, bio, 
                        followers_count, posts_count
                        FROM users
                        WHERE user_id NOT IN ($placeholders) AND is_active = TRUE
                        ORDER BY posts_count DESC, followers_count DESC
                        LIMIT ?";
                
                $params = array_merge($existingIds, [$remaining]);
                $additionalUsers = Database::fetchAll($sql, $params);
                $users = array_merge($users, $additionalUsers);
            }
        } else {
            // For non-authenticated users, show most active users
            $sql = "SELECT user_id, username, display_name, avatar_url, bio,
                    followers_count, posts_count
                    FROM users
                    WHERE is_active = TRUE
                    ORDER BY posts_count DESC, followers_count DESC
                    LIMIT ?";
            
            $users = Database::fetchAll($sql, [$limit]);
        }
        
        self::jsonResponse(true, [
            'users' => $users,
            'count' => count($users)
        ]);
    }
    
    /**
     * Global search
     * GET /api/v1/search
     */
    public static function search()
    {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        $type = isset($_GET['type']) ? $_GET['type'] : 'all';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        
        if (strlen($query) < 2) {
            self::jsonResponse(false, ['code' => 'QUERY_TOO_SHORT'], 'Search query must be at least 2 characters', 400);
        }
        
        if (strlen($query) > 100) {
            self::jsonResponse(false, ['code' => 'QUERY_TOO_LONG'], 'Search query too long', 400);
        }
        
        $searchPattern = '%' . $query . '%';
        $results = [];
        
        // Search walls
        if ($type === 'all' || $type === 'wall') {
            $sql = "SELECT w.*, u.username, u.display_name as owner_display_name, u.avatar_url
                    FROM walls w
                    JOIN users u ON w.user_id = u.user_id
                    WHERE (w.display_name LIKE ? OR w.description LIKE ? OR w.wall_slug LIKE ?)
                        AND w.privacy_level = 'public'
                    ORDER BY w.display_name LIKE ? DESC, w.created_at DESC
                    LIMIT ?";
            
            $exactPattern = $query . '%';
            $results['walls'] = Database::fetchAll($sql, [
                $searchPattern, $searchPattern, $searchPattern, $exactPattern, $limit
            ]);
        }
        
        // Search users
        if ($type === 'all' || $type === 'user') {
            $sql = "SELECT user_id, username, display_name, avatar_url, bio, 
                    followers_count, posts_count
                    FROM users
                    WHERE (username LIKE ? OR display_name LIKE ? OR bio LIKE ?)
                        AND is_active = TRUE
                    ORDER BY username LIKE ? DESC, followers_count DESC
                    LIMIT ?";
            
            $exactPattern = $query . '%';
            $results['users'] = Database::fetchAll($sql, [
                $searchPattern, $searchPattern, $searchPattern, $exactPattern, $limit
            ]);
        }
        
        // Search posts
        if ($type === 'all' || $type === 'post') {
            $sql = "SELECT p.*, u.username, u.display_name, u.avatar_url,
                    w.wall_slug, w.display_name as wall_display_name
                    FROM posts p
                    JOIN users u ON p.author_id = u.user_id
                    JOIN walls w ON p.wall_id = w.wall_id
                    WHERE p.content_text LIKE ?
                        AND p.is_deleted = FALSE
                        AND w.privacy_level = 'public'
                    ORDER BY p.created_at DESC
                    LIMIT ?";
            
            $posts = Database::fetchAll($sql, [$searchPattern, $limit]);
            
            // Get media for posts
            foreach ($posts as &$post) {
                $mediaSql = "SELECT * FROM media_attachments WHERE post_id = ? ORDER BY display_order";
                $post['media_attachments'] = Database::fetchAll($mediaSql, [$post['post_id']]);
            }
            
            $results['posts'] = $posts;
        }
        
        self::jsonResponse(true, [
            'results' => $results,
            'query' => $query,
            'type' => $type
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
