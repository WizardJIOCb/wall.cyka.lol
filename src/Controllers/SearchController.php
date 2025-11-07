<?php

namespace App\Controllers;

use App\Models\Post;
use App\Models\Wall;
use App\Models\User;
use App\Models\AIApplication;
use App\Middleware\AuthMiddleware;
use App\Core\Cache;
use App\Utils\Database;

class SearchController
{
    /**
     * Unified search across all content types
     * GET /api/v1/search
     */
    public static function unifiedSearch()
    {
        // Support both direct params (?q=test) and nested params (?params[q]=test)
        $params = isset($_GET['params']) && is_array($_GET['params']) ? $_GET['params'] : $_GET;
        
        $query = isset($params['q']) ? trim($params['q']) : '';
        $type = isset($params['type']) ? $params['type'] : 'all'; // all, post, wall, user, ai-app
        $sort = isset($params['sort']) ? $params['sort'] : 'relevance'; // relevance, recent, popular
        $limit = isset($params['limit']) ? min(50, max(1, (int)$params['limit'])) : 20;
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $offset = ($page - 1) * $limit;

        // Validate query
        if (empty($query) || strlen($query) < 2) {
            jsonResponse(false, null, 'Search query must be at least 2 characters', 400);
        }

        if (strlen($query) > 200) {
            jsonResponse(false, null, 'Search query too long (max 200 characters)', 400);
        }

        // Optional authentication - get user ID if logged in
        AuthMiddleware::optionalAuth();
        $currentUserId = AuthMiddleware::getCurrentUserId();

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
            error_log("Search error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            jsonResponse(false, null, 'Search failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search posts with FULLTEXT
     */
    private static function searchPosts($query, $sort, $limit, $offset, $userId = null)
    {
        try {
            $sql = "SELECT 
                        p.post_id as id,
                        p.wall_id,
                        p.author_id,
                        p.content_text as title,
                        p.content_text as content,
                        p.post_type as content_type,
                        'public' as visibility,
                        0 as reaction_count,
                        0 as comment_count,
                        0 as share_count,
                        0 as view_count,
                        p.created_at,
                        p.updated_at,
                        u.username as author_username,
                        u.display_name as author_name,
                        u.avatar_url as author_avatar,
                        w.display_name as wall_name,
                        CASE 
                            WHEN MATCH(p.content_text) AGAINST(? IN NATURAL LANGUAGE MODE) THEN MATCH(p.content_text) AGAINST(? IN NATURAL LANGUAGE MODE)
                            ELSE 0
                        END as relevance
                    FROM posts p
                    INNER JOIN users u ON p.author_id = u.user_id
                    INNER JOIN walls w ON p.wall_id = w.wall_id
                    WHERE (MATCH(p.content_text) AGAINST(? IN NATURAL LANGUAGE MODE) OR ? = '')
                      AND p.is_deleted = 0";

            // Apply sorting
            switch ($sort) {
                case 'recent':
                    $sql .= " ORDER BY p.created_at DESC";
                    break;
                case 'popular':
                    $sql .= " ORDER BY (0 * 3 + 0 * 2 + 0 * 5) DESC";
                    break;
                case 'relevance':
                default:
                    $sql .= " ORDER BY relevance DESC, p.created_at DESC";
                    break;
            }

            $sql .= " LIMIT ? OFFSET ?";

            $stmt = Database::query($sql, [$query, $query, $query, $query, $limit, $offset]);
            $results = $stmt->fetchAll();

            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                         FROM posts p
                         WHERE (MATCH(p.content_text) AGAINST(? IN NATURAL LANGUAGE MODE) OR ? = '')
                           AND p.is_deleted = 0";
            
            $countStmt = Database::query($countSql, [$query, $query]);
            $countResult = $countStmt->fetch();
            $total = $countResult ? (int)$countResult['total'] : 0;

            return [
                'items' => $results ?: [],
                'total' => $total
            ];
        } catch (\Exception $e) {
            error_log("Search posts error: " . $e->getMessage());
            // Return empty results instead of failing completely
            return [
                'items' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Search walls with FULLTEXT
     */
    private static function searchWalls($query, $sort, $limit, $offset, $userId = null)
    {
        try {
            // First check if the name column exists
            $sql = "SELECT 
                        w.wall_id as id,
                        w.user_id,
                        COALESCE(w.name, w.display_name) as name,
                        w.description,
                        w.theme_settings as theme,
                        w.privacy_level as privacy,
                        0 as subscriber_count,
                        0 as post_count,
                        w.created_at,
                        u.username as owner_username,
                        u.display_name as owner_name,
                        u.avatar_url as owner_avatar,
                        CASE 
                            WHEN MATCH(COALESCE(w.name, w.display_name), w.description) AGAINST(? IN NATURAL LANGUAGE MODE) THEN MATCH(COALESCE(w.name, w.display_name), w.description) AGAINST(? IN NATURAL LANGUAGE MODE)
                            ELSE 0
                        END as relevance
                    FROM walls w
                    INNER JOIN users u ON w.user_id = u.user_id
                    WHERE (MATCH(COALESCE(w.name, w.display_name), w.description) AGAINST(? IN NATURAL LANGUAGE MODE) OR ? = '')
                      AND w.privacy_level IN ('public', 'unlisted')";

            // Apply sorting
            switch ($sort) {
                case 'recent':
                    $sql .= " ORDER BY w.created_at DESC";
                    break;
                case 'popular':
                    $sql .= " ORDER BY w.created_at DESC"; // Using created_at as fallback since we don't have the actual columns
                    break;
                case 'relevance':
                default:
                    $sql .= " ORDER BY relevance DESC, w.created_at DESC"; // Using created_at as fallback since we don't have the actual columns
                    break;
            }

            $sql .= " LIMIT ? OFFSET ?";

            $stmt = Database::query($sql, [$query, $query, $query, $query, $limit, $offset]);
            $results = $stmt->fetchAll();

            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                         FROM walls w
                         WHERE (MATCH(COALESCE(w.name, w.display_name), w.description) AGAINST(? IN NATURAL LANGUAGE MODE) OR ? = '')
                           AND w.privacy_level IN ('public', 'unlisted')";
            
            $countStmt = Database::query($countSql, [$query, $query]);
            $countResult = $countStmt->fetch();
            $total = $countResult ? (int)$countResult['total'] : 0;

            return [
                'items' => $results ?: [],
                'total' => $total
            ];
        } catch (\Exception $e) {
            error_log("Search walls error: " . $e->getMessage());
            // Return empty results instead of failing completely
            return [
                'items' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Search users with FULLTEXT
     */
    private static function searchUsers($query, $sort, $limit, $offset, $userId = null)
    {
        try {
            $sql = "SELECT 
                        u.user_id as id,
                        u.username,
                        u.display_name,
                        u.avatar_url,
                        u.bio,
                        0 as followers_count,
                        0 as following_count,
                        u.created_at,
                        CASE 
                            WHEN MATCH(u.display_name, u.bio, u.username) AGAINST(? IN NATURAL LANGUAGE MODE) THEN MATCH(u.display_name, u.bio, u.username) AGAINST(? IN NATURAL LANGUAGE MODE)
                            ELSE 0
                        END as relevance
                    FROM users u
                    WHERE (MATCH(u.display_name, u.bio, u.username) AGAINST(? IN NATURAL LANGUAGE MODE) OR ? = '')";

            $params = [$query, $query, $query, $query];

            // Apply sorting
            switch ($sort) {
                case 'recent':
                    $sql .= " ORDER BY u.created_at DESC";
                    break;
                case 'popular':
                    $sql .= " ORDER BY u.posts_count DESC";
                    break;
                case 'relevance':
                default:
                    $sql .= " ORDER BY relevance DESC, u.posts_count DESC";
                    break;
            }

            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = Database::query($sql, $params);
            $results = $stmt->fetchAll();

            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                         FROM users u
                         WHERE (MATCH(u.display_name, u.bio, u.username) AGAINST(? IN NATURAL LANGUAGE MODE) OR ? = '')";
            
            $countStmt = Database::query($countSql, [$query, $query]);
            $countResult = $countStmt->fetch();
            $total = $countResult ? (int)$countResult['total'] : 0;

            return [
                'items' => $results ?: [],
                'total' => $total
            ];
        } catch (\Exception $e) {
            error_log("Search users error: " . $e->getMessage());
            // Return empty results instead of failing completely
            return [
                'items' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Search AI applications
     */
    private static function searchAIApps($query, $sort, $limit, $offset, $userId = null)
    {
        try {
            $sql = "SELECT 
                        a.app_id as id,
                        a.post_id,
                        a.user_prompt as title,
                        a.user_prompt as description,
                        a.user_prompt as prompt,
                        '' as tags,
                        a.remix_count,
                        0 as fork_count,
                        0 as reaction_count,
                        0 as view_count,
                        a.created_at,
                        u.username as author_username,
                        u.display_name as author_name,
                        u.avatar_url as author_avatar,
                        CASE 
                            WHEN MATCH(a.user_prompt) AGAINST(? IN NATURAL LANGUAGE MODE) THEN MATCH(a.user_prompt) AGAINST(? IN NATURAL LANGUAGE MODE)
                            ELSE 0
                        END as relevance
                    FROM ai_applications a
                    INNER JOIN posts p ON a.post_id = p.post_id
                    INNER JOIN users u ON p.author_id = u.user_id
                    WHERE (MATCH(a.user_prompt) AGAINST(? IN NATURAL LANGUAGE MODE) OR ? = '')
                      AND a.status = 'completed'";

            // Apply sorting
            switch ($sort) {
                case 'recent':
                    $sql .= " ORDER BY a.created_at DESC";
                    break;
                case 'popular':
                    $sql .= " ORDER BY (a.remix_count * 3 + 0 * 2 + 0) DESC";
                    break;
                case 'relevance':
                default:
                    $sql .= " ORDER BY relevance DESC, 0 DESC";
                    break;
            }

            $sql .= " LIMIT ? OFFSET ?";

            $stmt = Database::query($sql, [$query, $query, $query, $query, $limit, $offset]);
            $results = $stmt->fetchAll();

            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                         FROM ai_applications a
                         WHERE (MATCH(a.user_prompt) AGAINST(? IN NATURAL LANGUAGE MODE) OR ? = '')
                           AND a.status = 'completed'";
            
            $countStmt = Database::query($countSql, [$query, $query]);
            $countResult = $countStmt->fetch();
            $total = $countResult ? (int)$countResult['total'] : 0;

            return [
                'items' => $results ?: [],
                'total' => $total
            ];
        } catch (\Exception $e) {
            error_log("Search AI apps error: " . $e->getMessage());
            // Return empty results instead of failing completely
            return [
                'items' => [],
                'total' => 0
            ];
        }
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
                    VALUES (?, ?, ?, NOW())";
            
            Database::query($sql, [$userId, $query, $type]);
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
        // Handle both direct params and nested params
        $params = $_GET['params'] ?? $_GET;
        $limit = isset($params['limit']) ? min(20, max(1, (int)$params['limit'])) : 10;
        
        // Try cache first
        $cached = Cache::get('search:trending');
        if ($cached) {
            jsonResponse(true, $cached, 'Trending searches retrieved', 200);
            return;
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
                    LIMIT ?";
            
            $stmt = Database::query($sql, [$limit]);
            $results = $stmt->fetchAll();

            // Cache for 30 minutes
            Cache::set('search:trending', $results, 1800);

            jsonResponse(true, $results, 'Trending searches retrieved', 200);

        } catch (\Exception $e) {
            error_log("Get trending searches error: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to get trending searches', 500);
        }
    }
}