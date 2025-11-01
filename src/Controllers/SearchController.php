<?php

namespace App\Controllers;

use App\Models\Post;
use App\Models\Wall;
use App\Models\User;
use App\Models\AIApplication;
use App\Middleware\AuthMiddleware;
use App\Core\Cache;

class SearchController
{
    /**
     * Unified search across all content types
     * GET /api/v1/search
     */
    public static function unifiedSearch()
    {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        $type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, post, wall, user, ai-app
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance'; // relevance, recent, popular
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        // Validate query
        if (empty($query) || strlen($query) < 2) {
            jsonResponse(false, null, 'Search query must be at least 2 characters', 400);
        }

        if (strlen($query) > 200) {
            jsonResponse(false, null, 'Search query too long (max 200 characters)', 400);
        }

        $currentUserId = AuthMiddleware::getUserIdOptional();

        // Try cache first
        $cacheKey = self::getCacheKey($query, $type, $sort, $page, $limit);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            jsonResponse(true, $cached, 'Search results retrieved from cache', 200);
        }

        try {
            $results = [];

            if ($type === 'all' || $type === 'post') {
                $results['posts'] = self::searchPosts($query, $sort, $limit, $offset, $currentUserId);
            }

            if ($type === 'all' || $type === 'wall') {
                $results['walls'] = self::searchWalls($query, $sort, $limit, $offset, $currentUserId);
            }

            if ($type === 'all' || $type === 'user') {
                $results['users'] = self::searchUsers($query, $sort, $limit, $offset, $currentUserId);
            }

            if ($type === 'all' || $type === 'ai-app') {
                $results['ai_apps'] = self::searchAIApps($query, $sort, $limit, $offset, $currentUserId);
            }

            // Calculate total counts for tab display
            $counts = [
                'total' => 0,
                'posts' => isset($results['posts']) ? $results['posts']['total'] : 0,
                'walls' => isset($results['walls']) ? $results['walls']['total'] : 0,
                'users' => isset($results['users']) ? $results['users']['total'] : 0,
                'ai_apps' => isset($results['ai_apps']) ? $results['ai_apps']['total'] : 0
            ];
            $counts['total'] = array_sum(array_values($counts));

            $response = [
                'query' => $query,
                'type' => $type,
                'sort' => $sort,
                'results' => $results,
                'counts' => $counts,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit
                ]
            ];

            // Cache results for 5 minutes
            Cache::set($cacheKey, $response, 300);

            // Track search query for analytics (async)
            self::trackSearch($query, $type, $currentUserId);

            jsonResponse(true, $response, 'Search completed successfully', 200);

        } catch (\Exception $e) {
            error_log("Search error: " . $e->getMessage());
            jsonResponse(false, null, 'Search failed', 500);
        }
    }

    /**
     * Search posts with FULLTEXT
     */
    private static function searchPosts($query, $sort, $limit, $offset, $userId = null)
    {
        $searchTerm = Database::escape($query);
        
        $sql = "SELECT 
                    p.id,
                    p.wall_id,
                    p.author_id,
                    p.title,
                    p.content,
                    p.content_type,
                    p.visibility,
                    p.reaction_count,
                    p.comment_count,
                    p.share_count,
                    p.view_count,
                    p.created_at,
                    p.updated_at,
                    u.username as author_username,
                    u.display_name as author_name,
                    u.avatar_url as author_avatar,
                    w.name as wall_name,
                    MATCH(p.title, p.content) AGAINST(:search IN NATURAL LANGUAGE MODE) as relevance
                FROM posts p
                INNER JOIN users u ON p.author_id = u.id
                INNER JOIN walls w ON p.wall_id = w.id
                WHERE MATCH(p.title, p.content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                  AND p.is_deleted = 0
                  AND p.visibility IN ('public', 'unlisted')";

        // Apply sorting
        switch ($sort) {
            case 'recent':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY (p.reaction_count * 3 + p.comment_count * 2 + p.share_count * 5) DESC";
                break;
            case 'relevance':
            default:
                $sql .= " ORDER BY relevance DESC, p.created_at DESC";
                break;
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $results = Database::query($sql, [
            ':search' => $searchTerm,
            ':limit' => $limit,
            ':offset' => $offset
        ]);

        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM posts p
                     WHERE MATCH(p.title, p.content) AGAINST(:search IN NATURAL LANGUAGE MODE)
                       AND p.is_deleted = 0
                       AND p.visibility IN ('public', 'unlisted')";
        
        $countResult = Database::query($countSql, [':search' => $searchTerm]);
        $total = $countResult ? (int)$countResult[0]['total'] : 0;

        return [
            'items' => $results ?: [],
            'total' => $total
        ];
    }

    /**
     * Search walls with FULLTEXT
     */
    private static function searchWalls($query, $sort, $limit, $offset, $userId = null)
    {
        $searchTerm = Database::escape($query);
        
        $sql = "SELECT 
                    w.id,
                    w.user_id,
                    w.name,
                    w.description,
                    w.theme,
                    w.privacy,
                    w.subscriber_count,
                    w.post_count,
                    w.created_at,
                    u.username as owner_username,
                    u.display_name as owner_name,
                    u.avatar_url as owner_avatar,
                    MATCH(w.name, w.description) AGAINST(:search IN NATURAL LANGUAGE MODE) as relevance
                FROM walls w
                INNER JOIN users u ON w.user_id = u.id
                WHERE MATCH(w.name, w.description) AGAINST(:search IN NATURAL LANGUAGE MODE)
                  AND w.privacy IN ('public', 'unlisted')";

        // Apply sorting
        switch ($sort) {
            case 'recent':
                $sql .= " ORDER BY w.created_at DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY w.subscriber_count DESC, w.post_count DESC";
                break;
            case 'relevance':
            default:
                $sql .= " ORDER BY relevance DESC, w.subscriber_count DESC";
                break;
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $results = Database::query($sql, [
            ':search' => $searchTerm,
            ':limit' => $limit,
            ':offset' => $offset
        ]);

        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM walls w
                     WHERE MATCH(w.name, w.description) AGAINST(:search IN NATURAL LANGUAGE MODE)
                       AND w.privacy IN ('public', 'unlisted')";
        
        $countResult = Database::query($countSql, [':search' => $searchTerm]);
        $total = $countResult ? (int)$countResult[0]['total'] : 0;

        return [
            'items' => $results ?: [],
            'total' => $total
        ];
    }

    /**
     * Search users with FULLTEXT
     */
    private static function searchUsers($query, $sort, $limit, $offset, $userId = null)
    {
        $searchTerm = Database::escape($query);
        
        $sql = "SELECT 
                    u.id,
                    u.username,
                    u.display_name,
                    u.avatar_url,
                    u.bio,
                    u.followers_count,
                    u.following_count,
                    u.created_at,
                    MATCH(u.display_name, u.bio, u.username) AGAINST(:search IN NATURAL LANGUAGE MODE) as relevance
                FROM users u
                WHERE MATCH(u.display_name, u.bio, u.username) AGAINST(:search IN NATURAL LANGUAGE MODE)";

        // Boost results from followed users
        if ($userId) {
            $sql .= " OR u.id IN (SELECT following_id FROM user_follows WHERE follower_id = :user_id)";
        }

        // Apply sorting
        switch ($sort) {
            case 'recent':
                $sql .= " ORDER BY u.created_at DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY u.followers_count DESC";
                break;
            case 'relevance':
            default:
                $sql .= " ORDER BY relevance DESC, u.followers_count DESC";
                break;
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $params = [':search' => $searchTerm, ':limit' => $limit, ':offset' => $offset];
        if ($userId) {
            $params[':user_id'] = $userId;
        }

        $results = Database::query($sql, $params);

        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM users u
                     WHERE MATCH(u.display_name, u.bio, u.username) AGAINST(:search IN NATURAL LANGUAGE MODE)";
        
        $countResult = Database::query($countSql, [':search' => $searchTerm]);
        $total = $countResult ? (int)$countResult[0]['total'] : 0;

        return [
            'items' => $results ?: [],
            'total' => $total
        ];
    }

    /**
     * Search AI applications
     */
    private static function searchAIApps($query, $sort, $limit, $offset, $userId = null)
    {
        $searchTerm = Database::escape($query);
        
        $sql = "SELECT 
                    a.id,
                    a.user_id,
                    a.title,
                    a.description,
                    a.prompt,
                    a.tags,
                    a.remix_count,
                    a.fork_count,
                    a.reaction_count,
                    a.view_count,
                    a.created_at,
                    u.username as author_username,
                    u.display_name as author_name,
                    u.avatar_url as author_avatar,
                    MATCH(a.title, a.description, a.tags) AGAINST(:search IN NATURAL LANGUAGE MODE) as relevance
                FROM ai_applications a
                INNER JOIN users u ON a.user_id = u.id
                WHERE MATCH(a.title, a.description, a.tags) AGAINST(:search IN NATURAL LANGUAGE MODE)
                  AND a.visibility = 'public'
                  AND a.status = 'completed'";

        // Apply sorting
        switch ($sort) {
            case 'recent':
                $sql .= " ORDER BY a.created_at DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY (a.remix_count * 3 + a.fork_count * 2 + a.reaction_count) DESC";
                break;
            case 'relevance':
            default:
                $sql .= " ORDER BY relevance DESC, a.view_count DESC";
                break;
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $results = Database::query($sql, [
            ':search' => $searchTerm,
            ':limit' => $limit,
            ':offset' => $offset
        ]);

        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM ai_applications a
                     WHERE MATCH(a.title, a.description, a.tags) AGAINST(:search IN NATURAL LANGUAGE MODE)
                       AND a.visibility = 'public'
                       AND a.status = 'completed'";
        
        $countResult = Database::query($countSql, [':search' => $searchTerm]);
        $total = $countResult ? (int)$countResult[0]['total'] : 0;

        return [
            'items' => $results ?: [],
            'total' => $total
        ];
    }

    /**
     * Generate cache key for search results
     */
    private static function getCacheKey($query, $type, $sort, $page, $limit)
    {
        return 'search:' . md5($query . $type . $sort . $page . $limit);
    }

    /**
     * Track search query for analytics
     */
    private static function trackSearch($query, $type, $userId)
    {
        // Async logging - don't block response
        try {
            $sql = "INSERT INTO search_logs (user_id, query, search_type, created_at)
                    VALUES (:user_id, :query, :search_type, NOW())";
            
            Database::query($sql, [
                ':user_id' => $userId,
                ':query' => $query,
                ':search_type' => $type
            ]);
        } catch (\Exception $e) {
            // Silent fail - don't interrupt search
            error_log("Search tracking error: " . $e->getMessage());
        }
    }

    /**
     * Get trending searches
     * GET /api/v1/search/trending
     */
    public static function getTrendingSearches()
    {
        $limit = isset($_GET['limit']) ? min(20, max(1, (int)$_GET['limit'])) : 10;
        
        // Try cache first
        $cached = Cache::get('search:trending');
        if ($cached) {
            jsonResponse(true, $cached, 'Trending searches retrieved', 200);
        }

        try {
            $sql = "SELECT 
                        query,
                        COUNT(*) as search_count,
                        MAX(created_at) as last_searched
                    FROM search_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY query
                    ORDER BY search_count DESC
                    LIMIT :limit";
            
            $results = Database::query($sql, [':limit' => $limit]);

            // Cache for 30 minutes
            Cache::set('search:trending', $results, 1800);

            jsonResponse(true, $results, 'Trending searches retrieved', 200);

        } catch (\Exception $e) {
            error_log("Get trending searches error: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to get trending searches', 500);
        }
    }
}
