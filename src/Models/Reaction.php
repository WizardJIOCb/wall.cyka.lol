<?php
/**
 * Wall Social Platform - Reaction Model
 * 
 * Handles reactions (likes, dislikes, etc.) for posts and comments
 */

use App\Utils\Database;

class Reaction
{
    /**
     * Add reaction
     */
    public static function addReaction($data)
    {
        $sql = "INSERT INTO reactions (
            user_id, reactable_type, reactable_id, reaction_type, created_at
        ) VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            reaction_type = VALUES(reaction_type),
            updated_at = NOW()";

        $params = [
            $data['user_id'],
            $data['reactable_type'], // 'post' or 'comment'
            $data['reactable_id'],
            $data['reaction_type'] // 'like', 'dislike', 'love', 'haha', 'wow', 'sad', 'angry'
        ];

        try {
            Database::beginTransaction();
            
            Database::query($sql, $params);
            
            // Update counter on target
            self::updateCounters($data['reactable_type'], $data['reactable_id']);
            
            // Update user counter
            $userSql = "UPDATE users SET reactions_given_count = reactions_given_count + 1 WHERE user_id = ?";
            Database::query($userSql, [$data['user_id']]);
            
            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to add reaction: ' . $e->getMessage());
        }
    }

    /**
     * Remove reaction
     */
    public static function removeReaction($userId, $reactableType, $reactableId)
    {
        try {
            Database::beginTransaction();
            
            $sql = "DELETE FROM reactions WHERE user_id = ? AND reactable_type = ? AND reactable_id = ?";
            Database::query($sql, [$userId, $reactableType, $reactableId]);
            
            // Update counters
            self::updateCounters($reactableType, $reactableId);
            
            // Update user counter
            $userSql = "UPDATE users SET reactions_given_count = reactions_given_count - 1 WHERE user_id = ? AND reactions_given_count > 0";
            Database::query($userSql, [$userId]);
            
            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to remove reaction: ' . $e->getMessage());
        }
    }

    /**
     * Get reactions for item
     */
    public static function getReactions($reactableType, $reactableId, $limit = 100)
    {
        $sql = "SELECT r.*, u.username, u.display_name, u.avatar_url
                FROM reactions r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.reactable_type = ? AND r.reactable_id = ?
                ORDER BY r.created_at DESC
                LIMIT ?";
        
        return Database::fetchAll($sql, [$reactableType, $reactableId, $limit]);
    }

    /**
     * Get user's reaction
     */
    public static function getUserReaction($userId, $reactableType, $reactableId)
    {
        $sql = "SELECT * FROM reactions WHERE user_id = ? AND reactable_type = ? AND reactable_id = ?";
        return Database::fetchOne($sql, [$userId, $reactableType, $reactableId]);
    }

    /**
     * Update reaction counters
     */
    private static function updateCounters($reactableType, $reactableId)
    {
        $table = $reactableType === 'post' ? 'posts' : 'comments';
        
        // Get counts
        $countSql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN reaction_type = 'like' THEN 1 ELSE 0 END) as likes,
                        SUM(CASE WHEN reaction_type = 'dislike' THEN 1 ELSE 0 END) as dislikes
                     FROM reactions
                     WHERE reactable_type = ? AND reactable_id = ?";
        
        $counts = Database::fetchOne($countSql, [$reactableType, $reactableId]);
        
        // Update target table
        $idColumn = $reactableType === 'post' ? 'post_id' : 'comment_id';
        $updateSql = "UPDATE $table SET 
                      reaction_count = ?,
                      like_count = ?,
                      dislike_count = ?,
                      updated_at = NOW()
                      WHERE $idColumn = ?";
        
        Database::query($updateSql, [
            $counts['total'],
            $counts['likes'],
            $counts['dislikes'],
            $reactableId
        ]);
    }

    /**
     * Get reaction statistics
     */
    public static function getReactionStats($reactableType, $reactableId)
    {
        $sql = "SELECT reaction_type, COUNT(*) as count
                FROM reactions
                WHERE reactable_type = ? AND reactable_id = ?
                GROUP BY reaction_type";
        
        $stats = Database::fetchAll($sql, [$reactableType, $reactableId]);
        
        $result = [
            'like' => 0,
            'dislike' => 0,
            'love' => 0,
            'haha' => 0,
            'wow' => 0,
            'sad' => 0,
            'angry' => 0
        ];
        
        foreach ($stats as $stat) {
            $result[$stat['reaction_type']] = (int)$stat['count'];
        }
        
        return $result;
    }
    
    /**
     * Add or update reaction (toggle behavior)
     * Returns action: 'created', 'updated', or 'removed'
     */
    public static function addOrUpdate($userId, $targetType, $targetId, $reactionType)
    {
        try {
            Database::beginTransaction();
            
            // Check if reaction exists
            $existing = self::getUserReaction($userId, $targetType, $targetId);
            
            $action = 'created';
            
            if ($existing) {
                if ($existing['reaction_type'] === $reactionType) {
                    // Same reaction - remove it (toggle off)
                    self::removeReaction($userId, $targetType, $targetId);
                    Database::commit();
                    return ['action' => 'removed'];
                } else {
                    // Different reaction - update it
                    $action = 'updated';
                }
            }
            
            // Add or update reaction
            self::addReaction([
                'user_id' => $userId,
                'reactable_type' => $targetType,
                'reactable_id' => $targetId,
                'reaction_type' => $reactionType
            ]);
            
            Database::commit();
            return ['action' => $action];
            
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
    
    /**
     * Remove reaction (wrapper for consistency)
     */
    public static function remove($userId, $targetType, $targetId)
    {
        return self::removeReaction($userId, $targetType, $targetId);
    }
    
    /**
     * Get reaction summary grouped by type
     */
    public static function getSummary($targetType, $targetId)
    {
        $sql = "SELECT reaction_type, COUNT(*) as count
                FROM reactions
                WHERE reactable_type = ? AND reactable_id = ?
                GROUP BY reaction_type
                ORDER BY count DESC";
        
        $results = Database::fetchAll($sql, [$targetType, $targetId]);
        
        return array_map(function($row) {
            return [
                'type' => $row['reaction_type'],
                'count' => (int)$row['count']
            ];
        }, $results);
    }
    
    /**
     * Get users who reacted with pagination
     */
    public static function getUsers($targetType, $targetId, $reactionType = null, $limit = 20, $offset = 0)
    {
        $sql = "SELECT r.reaction_type, u.user_id, u.username, u.display_name, u.avatar_url, r.created_at
                FROM reactions r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.reactable_type = ? AND r.reactable_id = ?";
        
        $params = [$targetType, $targetId];
        
        if ($reactionType) {
            $sql .= " AND r.reaction_type = ?";
            $params[] = $reactionType;
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Get user reactions for multiple targets (bulk query)
     */
    public static function getUserReactions($userId, $targetType, array $targetIds)
    {
        if (empty($targetIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
        $sql = "SELECT * FROM reactions 
                WHERE user_id = ? AND reactable_type = ? AND reactable_id IN ($placeholders)";
        
        $params = array_merge([$userId, $targetType], $targetIds);
        
        return Database::fetchAll($sql, $params);
    }
}
