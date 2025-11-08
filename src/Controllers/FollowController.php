<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\UserFollow;
use App\Services\NotificationService;
use App\Middleware\AuthMiddleware;

class FollowController
{
    /**
     * Follow a user
     * POST /api/v1/users/{userId}/follow
     */
    public static function followUser($params)
    {
        $currentUser = AuthMiddleware::requireAuth();
        $currentUserId = $currentUser['user_id'];
        $targetUserId = (int)$params['userId'];

        // Validate target user exists
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            jsonResponse(false, null, 'User not found', 404);
        }

        // Cannot follow yourself
        if ($currentUserId === $targetUserId) {
            jsonResponse(false, null, 'Cannot follow yourself', 400);
        }

        // Check if already following
        $existingFollow = UserFollow::getFollow($currentUserId, $targetUserId);
        if ($existingFollow) {
            jsonResponse(false, null, 'Already following this user', 409);
        }

        try {
            Database::beginTransaction();

            // Create follow relationship
            $follow = UserFollow::create([
                'follower_id' => $currentUserId,
                'following_id' => $targetUserId,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Increment follower count on target user
            User::incrementFollowerCount($targetUserId);

            // Increment following count on current user
            User::incrementFollowingCount($currentUserId);

            // Create notification for target user
            NotificationService::createNotification([
                'user_id' => $targetUserId,
                'type' => 'new_follower',
                'title' => 'New Follower',
                'message' => User::getDisplayName($currentUserId) . ' started following you',
                'action_url' => '/users/' . $currentUserId,
                'action_user_id' => $currentUserId
            ]);

            Database::commit();

            jsonResponse(true, [
                'follow_id' => $follow['id'],
                'follower_id' => $currentUserId,
                'following_id' => $targetUserId,
                'created_at' => $follow['created_at'],
                'is_following' => true
            ], 'Successfully followed user', 201);

        } catch (\Exception $e) {
            Database::rollback();
            error_log("Follow user error: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to follow user', 500);
        }
    }

    /**
     * Unfollow a user
     * DELETE /api/v1/users/{userId}/unfollow
     */
    public static function unfollowUser($params)
    {
        $currentUser = AuthMiddleware::requireAuth();
        $currentUserId = $currentUser['user_id'];
        $targetUserId = (int)$params['userId'];

        // Validate target user exists
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            jsonResponse(false, null, 'User not found', 404);
        }

        // Check if following relationship exists
        $existingFollow = UserFollow::getFollow($currentUserId, $targetUserId);
        if (!$existingFollow) {
            jsonResponse(false, null, 'Not following this user', 404);
        }

        try {
            Database::beginTransaction();

            // Delete follow relationship
            UserFollow::delete($currentUserId, $targetUserId);

            // Decrement follower count on target user
            User::decrementFollowerCount($targetUserId);

            // Decrement following count on current user
            User::decrementFollowingCount($currentUserId);

            Database::commit();

            jsonResponse(true, [
                'follower_id' => $currentUserId,
                'following_id' => $targetUserId,
                'is_following' => false
            ], 'Successfully unfollowed user', 200);

        } catch (\Exception $e) {
            Database::rollback();
            error_log("Unfollow user error: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to unfollow user', 500);
        }
    }

    /**
     * Get user's followers
     * GET /api/v1/users/{userId}/followers
     */
    public static function getFollowers($params)
    {
        $userId = (int)$params['userId'];
        $currentUser = AuthMiddleware::optionalAuth();
        $currentUserId = $currentUser ? $currentUser['user_id'] : null;

        // Validate user exists
        $user = User::find($userId);
        if (!$user) {
            jsonResponse(false, null, 'User not found', 404);
        }

        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        // Get followers
        $followers = UserFollow::getFollowers($userId, $limit, $offset);
        $totalCount = UserFollow::getFollowerCount($userId);

        // Enrich with current user's follow status
        if ($currentUserId) {
            foreach ($followers as &$follower) {
                $follower['is_followed_by_you'] = UserFollow::isFollowing($currentUserId, $follower['user_id']);
                $follower['is_mutual'] = UserFollow::isFollowing($follower['user_id'], $currentUserId);
            }
        }

        jsonResponse(true, [
            'followers' => $followers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'has_more' => ($offset + count($followers)) < $totalCount
            ]
        ], 'Followers retrieved successfully', 200);
    }

    /**
     * Get users that a user is following
     * GET /api/v1/users/{userId}/following
     */
    public static function getFollowing($params)
    {
        $userId = (int)$params['userId'];
        $currentUser = AuthMiddleware::optionalAuth();
        $currentUserId = $currentUser ? $currentUser['user_id'] : null;

        // Validate user exists
        $user = User::find($userId);
        if (!$user) {
            jsonResponse(false, null, 'User not found', 404);
        }

        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        // Get following
        $following = UserFollow::getFollowing($userId, $limit, $offset);
        $totalCount = UserFollow::getFollowingCount($userId);

        // Enrich with current user's follow status
        if ($currentUserId) {
            foreach ($following as &$user) {
                $user['is_followed_by_you'] = UserFollow::isFollowing($currentUserId, $user['user_id']);
                $user['is_mutual'] = UserFollow::isFollowing($user['user_id'], $currentUserId);
            }
        }

        jsonResponse(true, [
            'following' => $following,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'has_more' => ($offset + count($following)) < $totalCount
            ]
        ], 'Following list retrieved successfully', 200);
    }

    /**
     * Check follow status between users
     * GET /api/v1/users/{userId}/follow-status
     */
    public static function getFollowStatus($params)
    {
        $currentUser = AuthMiddleware::requireAuth();
        $currentUserId = $currentUser['user_id'];
        $targetUserId = (int)$params['userId'];

        // Validate target user exists
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            jsonResponse(false, null, 'User not found', 404);
        }

        $isFollowing = UserFollow::isFollowing($currentUserId, $targetUserId);
        $isFollowedBy = UserFollow::isFollowing($targetUserId, $currentUserId);
        $isMutual = $isFollowing && $isFollowedBy;

        jsonResponse(true, [
            'user_id' => $targetUserId,
            'is_following' => $isFollowing,
            'is_followed_by' => $isFollowedBy,
            'is_mutual' => $isMutual,
            'follower_count' => $targetUser['followers_count'] ?? 0,
            'following_count' => $targetUser['following_count'] ?? 0
        ], 'Follow status retrieved successfully', 200);
    }

    /**
     * Get mutual followers between two users
     * GET /api/v1/users/{userId}/mutual-followers
     */
    public static function getMutualFollowers($params)
    {
        $currentUser = AuthMiddleware::requireAuth();
        $currentUserId = $currentUser['user_id'];
        $targetUserId = (int)$params['userId'];

        // Validate target user exists
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            jsonResponse(false, null, 'User not found', 404);
        }

        // Pagination
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;

        $mutualFollowers = UserFollow::getMutualFollowers($currentUserId, $targetUserId, $limit);

        jsonResponse(true, [
            'mutual_followers' => $mutualFollowers,
            'count' => count($mutualFollowers)
        ], 'Mutual followers retrieved successfully', 200);
    }
}
