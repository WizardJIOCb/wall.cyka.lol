<?php

namespace App\Models;

use App\Utils\Database;

class UserFollow
{
    /**
     * Create a follow relationship
     */
    public static function create($data)
    {
        $sql = "INSERT INTO user_follows (follower_id, following_id, created_at) 
                VALUES (:follower_id, :following_id, :created_at)";
        
        $params = [
            ':follower_id' => $data['follower_id'],
            ':following_id' => $data['following_id'],
            ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s')
        ];

        Database::query($sql, $params);
        
        return [
            'id' => Database::lastInsertId(),
            'follower_id' => $data['follower_id'],
            'following_id' => $data['following_id'],
            'created_at' => $params[':created_at']
        ];
    }

    /**
     * Delete a follow relationship
     */
    public static function delete($followerId, $followingId)
    {
        $sql = "DELETE FROM user_follows 
                WHERE follower_id = :follower_id AND following_id = :following_id";
        
        return Database::query($sql, [
            ':follower_id' => $followerId,
            ':following_id' => $followingId
        ]);
    }

    /**
     * Get a specific follow relationship
     */
    public static function getFollow($followerId, $followingId)
    {
        $sql = "SELECT * FROM user_follows 
                WHERE follower_id = :follower_id AND following_id = :following_id";
        
        $result = Database::query($sql, [
            ':follower_id' => $followerId,
            ':following_id' => $followingId
        ]);

        return $result ? $result[0] : null;
    }

    /**
     * Check if user A is following user B
     */
    public static function isFollowing($followerId, $followingId)
    {
        $follow = self::getFollow($followerId, $followingId);
        return !empty($follow);
    }

    /**
     * Get followers of a user (users who follow this user)
     */
    public static function getFollowers($userId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT 
                    u.id as user_id,
                    u.username,
                    u.display_name,
                    u.avatar_url,
                    u.bio,
                    u.followers_count,
                    u.following_count,
                    uf.created_at as followed_at
                FROM user_follows uf
                INNER JOIN users u ON uf.follower_id = u.id
                WHERE uf.following_id = :user_id
                ORDER BY uf.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        return Database::query($sql, [
            ':user_id' => $userId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }

    /**
     * Get users that a user is following
     */
    public static function getFollowing($userId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT 
                    u.id as user_id,
                    u.username,
                    u.display_name,
                    u.avatar_url,
                    u.bio,
                    u.followers_count,
                    u.following_count,
                    uf.created_at as followed_at
                FROM user_follows uf
                INNER JOIN users u ON uf.following_id = u.id
                WHERE uf.follower_id = :user_id
                ORDER BY uf.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        return Database::query($sql, [
            ':user_id' => $userId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }

    /**
     * Get follower count for a user
     */
    public static function getFollowerCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM user_follows WHERE following_id = :user_id";
        $result = Database::query($sql, [':user_id' => $userId]);
        return $result ? (int)$result[0]['count'] : 0;
    }

    /**
     * Get following count for a user
     */
    public static function getFollowingCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM user_follows WHERE follower_id = :user_id";
        $result = Database::query($sql, [':user_id' => $userId]);
        return $result ? (int)$result[0]['count'] : 0;
    }

    /**
     * Get mutual followers between two users
     */
    public static function getMutualFollowers($userId1, $userId2, $limit = 20)
    {
        $sql = "SELECT DISTINCT
                    u.id as user_id,
                    u.username,
                    u.display_name,
                    u.avatar_url,
                    u.bio,
                    u.followers_count,
                    u.following_count
                FROM users u
                INNER JOIN user_follows uf1 ON u.id = uf1.follower_id
                INNER JOIN user_follows uf2 ON u.id = uf2.follower_id
                WHERE uf1.following_id = :user_id1 
                  AND uf2.following_id = :user_id2
                ORDER BY u.followers_count DESC
                LIMIT :limit";
        
        return Database::query($sql, [
            ':user_id1' => $userId1,
            ':user_id2' => $userId2,
            ':limit' => $limit
        ]);
    }

    /**
     * Get bulk follow status for multiple users
     * Returns array of user IDs that current user is following
     */
    public static function getBulkFollowStatus($followerId, array $userIds)
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "SELECT following_id FROM user_follows 
                WHERE follower_id = ? AND following_id IN ($placeholders)";
        
        $params = array_merge([$followerId], $userIds);
        $results = Database::query($sql, $params);

        return array_column($results, 'following_id');
    }

    /**
     * Get suggested users to follow
     * Based on mutual connections and popularity
     */
    public static function getSuggestedUsers($userId, $limit = 10)
    {
        $sql = "SELECT DISTINCT
                    u.id as user_id,
                    u.username,
                    u.display_name,
                    u.avatar_url,
                    u.bio,
                    u.followers_count,
                    u.following_count,
                    COUNT(DISTINCT uf2.follower_id) as mutual_count
                FROM users u
                INNER JOIN user_follows uf1 ON u.id = uf1.following_id
                INNER JOIN user_follows uf2 ON uf1.follower_id = uf2.follower_id
                WHERE uf2.following_id = :user_id
                  AND u.id != :user_id
                  AND u.id NOT IN (
                      SELECT following_id FROM user_follows WHERE follower_id = :user_id
                  )
                GROUP BY u.id
                ORDER BY mutual_count DESC, u.followers_count DESC
                LIMIT :limit";
        
        return Database::query($sql, [
            ':user_id' => $userId,
            ':limit' => $limit
        ]);
    }

    /**
     * Get recent followers for a user
     */
    public static function getRecentFollowers($userId, $limit = 5)
    {
        return self::getFollowers($userId, $limit, 0);
    }

    /**
     * Get follow statistics for a user
     */
    public static function getFollowStats($userId)
    {
        $followerCount = self::getFollowerCount($userId);
        $followingCount = self::getFollowingCount($userId);
        
        // Calculate mutual follows
        $sql = "SELECT COUNT(DISTINCT uf1.follower_id) as mutual_count
                FROM user_follows uf1
                INNER JOIN user_follows uf2 
                    ON uf1.follower_id = uf2.following_id
                WHERE uf1.following_id = :user_id 
                  AND uf2.follower_id = :user_id";
        
        $result = Database::query($sql, [':user_id' => $userId]);
        $mutualCount = $result ? (int)$result[0]['mutual_count'] : 0;

        return [
            'follower_count' => $followerCount,
            'following_count' => $followingCount,
            'mutual_count' => $mutualCount,
            'follow_ratio' => $followingCount > 0 ? round($followerCount / $followingCount, 2) : 0
        ];
    }
}
