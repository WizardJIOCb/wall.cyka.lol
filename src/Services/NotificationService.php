<?php
/**
 * Wall Social Platform - Notification Service
 * 
 * Handles notification creation and delivery
 */

class NotificationService
{
    /**
     * Create a notification
     */
    public static function createNotification($userId, $actorId, $type, $targetType, $targetId, $content = null)
    {
        $sql = "INSERT INTO notifications (
            user_id, actor_id, notification_type, 
            target_type, target_id, content, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $contentJson = $content ? json_encode($content) : null;
        
        Database::query($sql, [
            $userId,
            $actorId,
            $type,
            $targetType,
            $targetId,
            $contentJson
        ]);
        
        return Database::lastInsertId();
    }
    
    /**
     * Create follow notification
     */
    public static function createFollowNotification($followerId, $followingId)
    {
        return self::createNotification(
            $followingId,  // user being followed receives notification
            $followerId,   // follower is the actor
            'follow',
            'user',
            $followerId
        );
    }
    
    /**
     * Create reaction notification
     */
    public static function createReactionNotification($reactorId, $targetType, $targetId, $reactionType)
    {
        // Get the owner of the target (post or comment)
        if ($targetType === 'post') {
            $sql = "SELECT author_id FROM posts WHERE post_id = ?";
            $post = Database::fetchOne($sql, [$targetId]);
            if (!$post || $post['author_id'] == $reactorId) {
                return null; // Don't notify if user reacts to own content
            }
            $ownerId = $post['author_id'];
        } elseif ($targetType === 'comment') {
            $sql = "SELECT user_id FROM comments WHERE comment_id = ?";
            $comment = Database::fetchOne($sql, [$targetId]);
            if (!$comment || $comment['user_id'] == $reactorId) {
                return null;
            }
            $ownerId = $comment['user_id'];
        } else {
            return null;
        }
        
        return self::createNotification(
            $ownerId,
            $reactorId,
            'reaction',
            $targetType,
            $targetId,
            ['reaction_type' => $reactionType]
        );
    }
    
    /**
     * Create comment notification
     */
    public static function createCommentNotification($commenterId, $postId, $commentText)
    {
        $sql = "SELECT author_id FROM posts WHERE post_id = ?";
        $post = Database::fetchOne($sql, [$postId]);
        
        if (!$post || $post['author_id'] == $commenterId) {
            return null; // Don't notify if user comments on own post
        }
        
        return self::createNotification(
            $post['author_id'],
            $commenterId,
            'comment',
            'post',
            $postId,
            ['preview' => substr($commentText, 0, 100)]
        );
    }
    
    /**
     * Create comment reply notification
     */
    public static function createReplyNotification($replierId, $parentCommentId, $replyText)
    {
        $sql = "SELECT user_id FROM comments WHERE comment_id = ?";
        $parentComment = Database::fetchOne($sql, [$parentCommentId]);
        
        if (!$parentComment || $parentComment['user_id'] == $replierId) {
            return null;
        }
        
        return self::createNotification(
            $parentComment['user_id'],
            $replierId,
            'reply',
            'comment',
            $parentCommentId,
            ['preview' => substr($replyText, 0, 100)]
        );
    }
    
    /**
     * Create mention notification
     */
    public static function createMentionNotification($mentionerId, $mentionedUserId, $targetType, $targetId)
    {
        if ($mentionerId == $mentionedUserId) {
            return null;
        }
        
        return self::createNotification(
            $mentionedUserId,
            $mentionerId,
            'mention',
            $targetType,
            $targetId
        );
    }
    
    /**
     * Create bricks notification
     */
    public static function createBricksNotification($recipientId, $senderId, $amount, $reason)
    {
        return self::createNotification(
            $recipientId,
            $senderId,
            'bricks',
            'transaction',
            0,
            ['amount' => $amount, 'reason' => $reason]
        );
    }
    
    /**
     * Create AI generation complete notification
     */
    public static function createAICompleteNotification($userId, $jobId, $appId)
    {
        return self::createNotification(
            $userId,
            null,
            'ai_complete',
            'ai_app',
            $appId,
            ['job_id' => $jobId]
        );
    }
    
    /**
     * Get unread count for user
     */
    public static function getUnreadCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND is_read = FALSE";
        $result = Database::fetchOne($sql, [$userId]);
        return (int)$result['count'];
    }
    
    /**
     * Mark notification as read
     */
    public static function markAsRead($notificationId, $userId)
    {
        $sql = "UPDATE notifications SET is_read = TRUE 
                WHERE notification_id = ? AND user_id = ?";
        Database::query($sql, [$notificationId, $userId]);
        return true;
    }
    
    /**
     * Mark all notifications as read for user
     */
    public static function markAllAsRead($userId)
    {
        $sql = "UPDATE notifications SET is_read = TRUE 
                WHERE user_id = ? AND is_read = FALSE";
        Database::query($sql, [$userId]);
        
        // Get count of updated notifications
        $sql = "SELECT ROW_COUNT() as count";
        $result = Database::fetchOne($sql);
        return (int)$result['count'];
    }
}
