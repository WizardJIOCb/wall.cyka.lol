<?php
/**
 * Wall Social Platform - Wall Model
 * 
 * Handles wall data operations
 */

class Wall
{
    /**
     * Find wall by ID
     */
    public static function findById($wallId)
    {
        $sql = "SELECT * FROM walls WHERE wall_id = ?";
        return Database::fetchOne($sql, [$wallId]);
    }

    /**
     * Find wall by slug
     */
    public static function findBySlug($slug)
    {
        $sql = "SELECT * FROM walls WHERE wall_slug = ?";
        return Database::fetchOne($sql, [$slug]);
    }

    /**
     * Find wall by user ID
     */
    public static function findByUserId($userId)
    {
        $sql = "SELECT * FROM walls WHERE user_id = ?";
        return Database::fetchOne($sql, [$userId]);
    }

    /**
     * Get user's walls
     */
    public static function getUserWalls($userId)
    {
        $sql = "SELECT * FROM walls WHERE user_id = ? ORDER BY created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Create new wall
     */
    public static function create($data)
    {
        $sql = "INSERT INTO walls (
            user_id, wall_slug, display_name, description, 
            privacy_level, theme, enable_comments, enable_reactions, 
            enable_reposts, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['user_id'],
            $data['wall_slug'],
            $data['display_name'],
            $data['description'] ?? '',
            $data['privacy_level'] ?? 'public',
            $data['theme'] ?? 'default',
            $data['enable_comments'] ?? true,
            $data['enable_reactions'] ?? true,
            $data['enable_reposts'] ?? true
        ];

        try {
            Database::query($sql, $params);
            $wallId = Database::lastInsertId();
            return self::findById($wallId);
        } catch (Exception $e) {
            throw new Exception('Failed to create wall: ' . $e->getMessage());
        }
    }

    /**
     * Update wall
     */
    public static function update($wallId, $data)
    {
        $fields = [];
        $params = [];

        $allowedFields = [
            'wall_slug', 'display_name', 'description', 'avatar_url', 
            'cover_image_url', 'privacy_level', 'theme', 'enable_comments', 
            'enable_reactions', 'enable_reposts', 'custom_css'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return true;
        }

        $params[] = $wallId;
        $sql = "UPDATE walls SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE wall_id = ?";
        
        Database::query($sql, $params);
        return true;
    }

    /**
     * Delete wall
     */
    public static function delete($wallId)
    {
        $sql = "DELETE FROM walls WHERE wall_id = ?";
        Database::query($sql, [$wallId]);
        return true;
    }

    /**
     * Check if wall slug is available
     */
    public static function isSlugAvailable($slug, $excludeWallId = null)
    {
        if ($excludeWallId) {
            $sql = "SELECT COUNT(*) as count FROM walls WHERE wall_slug = ? AND wall_id != ?";
            $result = Database::fetchOne($sql, [$slug, $excludeWallId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM walls WHERE wall_slug = ?";
            $result = Database::fetchOne($sql, [$slug]);
        }
        
        return $result['count'] == 0;
    }

    /**
     * Get wall with owner info
     */
    public static function getWallWithOwner($wallId)
    {
        $sql = "SELECT w.*, 
                u.username, u.display_name as owner_name, u.avatar_url as owner_avatar
                FROM walls w
                JOIN users u ON w.user_id = u.user_id
                WHERE w.wall_id = ?";
        
        return Database::fetchOne($sql, [$wallId]);
    }

    /**
     * Get wall public data
     */
    public static function getPublicData($wall)
    {
        if (!$wall) return null;

        return [
            'wall_id' => (int)$wall['wall_id'],
            'user_id' => (int)$wall['user_id'],
            'wall_slug' => $wall['wall_slug'],
            'display_name' => $wall['display_name'],
            'description' => $wall['description'],
            'avatar_url' => $wall['avatar_url'],
            'cover_image_url' => $wall['cover_image_url'],
            'privacy_level' => $wall['privacy_level'],
            'theme' => $wall['theme'],
            'enable_comments' => (bool)$wall['enable_comments'],
            'enable_reactions' => (bool)$wall['enable_reactions'],
            'enable_reposts' => (bool)$wall['enable_reposts'],
            'posts_count' => (int)$wall['posts_count'],
            'subscribers_count' => (int)$wall['subscribers_count'],
            'created_at' => $wall['created_at'],
            'updated_at' => $wall['updated_at'],
        ];
    }

    /**
     * Check if user can view wall
     */
    public static function canView($wall, $userId = null)
    {
        if ($wall['privacy_level'] === 'public') {
            return true;
        }

        if (!$userId) {
            return false;
        }

        // Owner can always view
        if ($wall['user_id'] == $userId) {
            return true;
        }

        if ($wall['privacy_level'] === 'followers-only') {
            return self::isSubscriber($wall['wall_id'], $userId);
        }

        if ($wall['privacy_level'] === 'private') {
            // Check if user is friend
            return self::isFriend($wall['user_id'], $userId);
        }

        return false;
    }

    /**
     * Check if user is subscriber
     */
    private static function isSubscriber($wallId, $userId)
    {
        $sql = "SELECT COUNT(*) as count FROM subscriptions 
                WHERE wall_id = ? AND subscriber_id = ? AND is_active = TRUE";
        $result = Database::fetchOne($sql, [$wallId, $userId]);
        return $result['count'] > 0;
    }

    /**
     * Check if users are friends
     */
    private static function isFriend($userId1, $userId2)
    {
        $sql = "SELECT COUNT(*) as count FROM friendships 
                WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?))
                AND status = 'accepted'";
        $result = Database::fetchOne($sql, [$userId1, $userId2, $userId2, $userId1]);
        return $result['count'] > 0;
    }
}
