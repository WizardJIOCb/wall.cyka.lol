<?php
/**
 * Wall Social Platform - Reaction Model
 * 
 * Handles reactions (likes, dislikes, etc.) for posts and comments
 */

namespace App\Models;

use App\Utils\Database;
use Exception;

class Reaction
{
    /**
     * Add reaction
     */
    public static function addReaction($data)
    {
        $sql = "INSERT INTO reactions (
            user_id, target_type, target_id, reaction_type, created_at
        ) VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            reaction_type = VALUES(reaction_type),
            updated_at = NOW()";

        $params = [
            $data['user_id'],
            $data['target_type'], // 'post' or 'comment'
            $data['target_id'],
            $data['reaction_type'] // 'like', 'dislike', 'love', 'haha', 'wow', 'sad', 'angry'
        ];

        try {
            Database::query($sql, $params);
            
            // Update counter on target (failure here won't break the reaction)
            try {
                self::updateCounters($data['target_type'], $data['target_id']);
            } catch (Exception $e) {
                error_log("Warning: Failed to update counters: " . $e->getMessage());
            }
            
            // Update user counter
            try {
                $userSql = "UPDATE users SET reactions_given_count = reactions_given_count + 1 WHERE user_id = ?";
                Database::query($userSql, [$data['user_id']]);
            } catch (Exception $e) {
                error_log("Warning: Failed to update user counter: " . $e->getMessage());
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to add reaction: ' . $e->getMessage());
        }
    }

    /**
     * Remove reaction
     */
    public static function removeReaction($userId, $targetType, $targetId)
    {
        try {
            $sql = "DELETE FROM reactions WHERE user_id = ? AND target_type = ? AND target_id = ?";
            Database::query($sql, [$userId, $targetType, $targetId]);
            
            // Update counters
            self::updateCounters($targetType, $targetId);
            
            // Update user counter
            $userSql = "UPDATE users SET reactions_given_count = reactions_given_count - 1 WHERE user_id = ? AND reactions_given_count > 0";
            Database::query($userSql, [$userId]);
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to remove reaction: ' . $e->getMessage());
        }
    }
}
