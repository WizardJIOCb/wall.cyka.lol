<?php

namespace App\Models;

use App\Utils\Database;

/**
 * User Model - Extensions for Follow System
 * 
 * These methods should be added to the existing User model
 * to support the social connections functionality
 */
class UserFollowExtensions
{
    /**
     * Increment follower count for a user
     */
    public static function incrementFollowerCount($userId)
    {
        $sql = "UPDATE users SET followers_count = followers_count + 1 WHERE id = :user_id";
        return Database::query($sql, [':user_id' => $userId]);
    }

    /**
     * Decrement follower count for a user
     */
    public static function decrementFollowerCount($userId)
    {
        $sql = "UPDATE users 
                SET followers_count = GREATEST(0, followers_count - 1) 
                WHERE id = :user_id";
        return Database::query($sql, [':user_id' => $userId]);
    }

    /**
     * Increment following count for a user
     */
    public static function incrementFollowingCount($userId)
    {
        $sql = "UPDATE users SET following_count = following_count + 1 WHERE id = :user_id";
        return Database::query($sql, [':user_id' => $userId]);
    }

    /**
     * Decrement following count for a user
     */
    public static function decrementFollowingCount($userId)
    {
        $sql = "UPDATE users 
                SET following_count = GREATEST(0, following_count - 1) 
                WHERE id = :user_id";
        return Database::query($sql, [':user_id' => $userId]);
    }

    /**
     * Get display name for a user (fallback to username if not set)
     */
    public static function getDisplayName($userId)
    {
        $sql = "SELECT display_name, username FROM users WHERE id = :user_id";
        $result = Database::query($sql, [':user_id' => $userId]);
        
        if (!$result) {
            return 'Unknown User';
        }

        $user = $result[0];
        return !empty($user['display_name']) ? $user['display_name'] : $user['username'];
    }

    /**
     * Recalculate follower and following counts for a user
     * Useful for data consistency maintenance
     */
    public static function recalculateFollowCounts($userId)
    {
        // Get actual follower count
        $followerSql = "SELECT COUNT(*) as count FROM user_follows WHERE following_id = :user_id";
        $followerResult = Database::query($followerSql, [':user_id' => $userId]);
        $followerCount = $followerResult ? (int)$followerResult[0]['count'] : 0;

        // Get actual following count
        $followingSql = "SELECT COUNT(*) as count FROM user_follows WHERE follower_id = :user_id";
        $followingResult = Database::query($followingSql, [':user_id' => $userId]);
        $followingCount = $followingResult ? (int)$followingResult[0]['count'] : 0;

        // Update user record
        $updateSql = "UPDATE users 
                      SET followers_count = :followers_count,
                          following_count = :following_count
                      WHERE id = :user_id";
        
        Database::query($updateSql, [
            ':followers_count' => $followerCount,
            ':following_count' => $followingCount,
            ':user_id' => $userId
        ]);

        return [
            'followers_count' => $followerCount,
            'following_count' => $followingCount
        ];
    }

    /**
     * Recalculate follow counts for all users
     * Should be run as a maintenance task
     */
    public static function recalculateAllFollowCounts()
    {
        $sql = "UPDATE users u
                LEFT JOIN (
                    SELECT following_id, COUNT(*) as count 
                    FROM user_follows 
                    GROUP BY following_id
                ) f ON u.id = f.following_id
                LEFT JOIN (
                    SELECT follower_id, COUNT(*) as count 
                    FROM user_follows 
                    GROUP BY follower_id
                ) g ON u.id = g.follower_id
                SET u.followers_count = COALESCE(f.count, 0),
                    u.following_count = COALESCE(g.count, 0)";
        
        return Database::query($sql);
    }

    /**
     * Get user's public profile data with follow stats
     */
    public static function getPublicProfileWithStats($userId, $viewerUserId = null)
    {
        $sql = "SELECT 
                    id,
                    username,
                    display_name,
                    avatar_url,
                    bio,
                    followers_count,
                    following_count,
                    created_at
                FROM users 
                WHERE id = :user_id";
        
        $result = Database::query($sql, [':user_id' => $userId]);
        
        if (!$result) {
            return null;
        }

        $user = $result[0];

        // Add follow status if viewer is provided
        if ($viewerUserId && $viewerUserId !== $userId) {
            $user['is_followed_by_you'] = UserFollow::isFollowing($viewerUserId, $userId);
            $user['is_following_you'] = UserFollow::isFollowing($userId, $viewerUserId);
            $user['is_mutual'] = $user['is_followed_by_you'] && $user['is_following_you'];
        }

        return $user;
    }
}

/**
 * INTEGRATION INSTRUCTIONS:
 * 
 * Add these methods to the existing User model class (src/Models/User.php):
 * 
 * 1. Copy incrementFollowerCount() method
 * 2. Copy decrementFollowerCount() method
 * 3. Copy incrementFollowingCount() method
 * 4. Copy decrementFollowingCount() method
 * 5. Copy getDisplayName() method
 * 6. Copy recalculateFollowCounts() method
 * 7. Copy recalculateAllFollowCounts() method (optional, for maintenance)
 * 8. Copy getPublicProfileWithStats() method
 * 
 * Or if User model doesn't exist yet, rename this class from UserFollowExtensions
 * to User and use as the main User model.
 */
