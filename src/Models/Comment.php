<?php
/**
 * Wall Social Platform - Comment Model
 * 
 * Handles threaded comments with reactions
 */

class Comment
{
    /**
     * Find comment by ID
     */
    public static function findById($commentId)
    {
        $sql = "SELECT c.*, u.username, u.display_name, u.avatar_url
                FROM comments c
                JOIN users u ON c.author_id = u.user_id
                WHERE c.comment_id = ? AND c.is_deleted = FALSE";
        return Database::fetchOne($sql, [$commentId]);
    }

    /**
     * Get post comments
     */
    public static function getPostComments($postId, $parentId = null, $limit = 50)
    {
        $sql = "SELECT c.*, u.username, u.display_name, u.avatar_url
                FROM comments c
                JOIN users u ON c.author_id = u.user_id
                WHERE c.post_id = ? AND c.is_deleted = FALSE";
        
        if ($parentId === null) {
            $sql .= " AND c.parent_comment_id IS NULL";
        } else {
            $sql .= " AND c.parent_comment_id = ?";
        }
        
        $sql .= " ORDER BY c.created_at ASC LIMIT ?";
        
        $params = $parentId === null ? [$postId, $limit] : [$postId, $parentId, $limit];
        
        return Database::fetchAll($sql, $params);
    }

    /**
     * Create comment
     */
    public static function create($data)
    {
        $sql = "INSERT INTO comments (
            post_id, author_id, parent_comment_id, content_text, content_html,
            depth_level, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $depthLevel = 0;
        if (!empty($data['parent_comment_id'])) {
            $parent = self::findById($data['parent_comment_id']);
            $depthLevel = $parent ? ($parent['depth_level'] + 1) : 0;
        }

        $params = [
            $data['post_id'],
            $data['author_id'],
            $data['parent_comment_id'] ?? null,
            $data['content_text'] ?? '',
            $data['content_html'] ?? '',
            $depthLevel
        ];

        try {
            Database::beginTransaction();
            
            Database::query($sql, $params);
            $commentId = Database::lastInsertId();
            
            // Update post comment count
            $postSql = "UPDATE posts SET comment_count = comment_count + 1, updated_at = NOW() WHERE post_id = ?";
            Database::query($postSql, [$data['post_id']]);
            
            // Update parent comment reply count
            if (!empty($data['parent_comment_id'])) {
                $parentSql = "UPDATE comments SET reply_count = reply_count + 1, updated_at = NOW() WHERE comment_id = ?";
                Database::query($parentSql, [$data['parent_comment_id']]);
            }
            
            // Update user comment count
            $userSql = "UPDATE users SET comments_count = comments_count + 1 WHERE user_id = ?";
            Database::query($userSql, [$data['author_id']]);
            
            Database::commit();
            
            return self::findById($commentId);
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to create comment: ' . $e->getMessage());
        }
    }

    /**
     * Update comment
     */
    public static function update($commentId, $data)
    {
        $fields = [];
        $params = [];

        $allowedFields = ['content_text', 'content_html'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return true;
        }

        $fields[] = "is_edited = TRUE";
        $params[] = $commentId;
        
        $sql = "UPDATE comments SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE comment_id = ?";
        Database::query($sql, $params);
        
        return true;
    }

    /**
     * Delete comment (soft delete)
     */
    public static function delete($commentId)
    {
        try {
            Database::beginTransaction();
            
            $comment = self::findById($commentId);
            if (!$comment) {
                throw new Exception('Comment not found');
            }
            
            // Soft delete
            $sql = "UPDATE comments SET is_deleted = TRUE, updated_at = NOW() WHERE comment_id = ?";
            Database::query($sql, [$commentId]);
            
            // Update post comment count
            $postSql = "UPDATE posts SET comment_count = comment_count - 1 WHERE post_id = ? AND comment_count > 0";
            Database::query($postSql, [$comment['post_id']]);
            
            // Update parent comment reply count
            if ($comment['parent_comment_id']) {
                $parentSql = "UPDATE comments SET reply_count = reply_count - 1 WHERE comment_id = ? AND reply_count > 0";
                Database::query($parentSql, [$comment['parent_comment_id']]);
            }
            
            // Update user comment count
            $userSql = "UPDATE users SET comments_count = comments_count - 1 WHERE user_id = ? AND comments_count > 0";
            Database::query($userSql, [$comment['author_id']]);
            
            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to delete comment: ' . $e->getMessage());
        }
    }

    /**
     * Get comment with replies
     */
    public static function getCommentWithReplies($commentId, $maxDepth = 3)
    {
        $comment = self::findById($commentId);
        if (!$comment) {
            return null;
        }

        if ($comment['depth_level'] < $maxDepth) {
            $comment['replies'] = self::getPostComments($comment['post_id'], $commentId, 10);
        }

        return $comment;
    }

    /**
     * Get public comment data
     */
    public static function getPublicData($comment)
    {
        if (!$comment) return null;

        return [
            'comment_id' => (int)$comment['comment_id'],
            'post_id' => (int)$comment['post_id'],
            'author_id' => (int)$comment['author_id'],
            'author_username' => $comment['username'] ?? null,
            'author_name' => $comment['display_name'] ?? null,
            'author_avatar' => $comment['avatar_url'] ?? null,
            'parent_comment_id' => $comment['parent_comment_id'] ? (int)$comment['parent_comment_id'] : null,
            'content_text' => $comment['content_text'],
            'content_html' => $comment['content_html'],
            'depth_level' => (int)$comment['depth_level'],
            'reply_count' => (int)$comment['reply_count'],
            'reaction_count' => (int)$comment['reaction_count'],
            'like_count' => (int)$comment['like_count'],
            'dislike_count' => (int)$comment['dislike_count'],
            'is_edited' => (bool)$comment['is_edited'],
            'is_hidden' => (bool)$comment['is_hidden'],
            'replies' => isset($comment['replies']) ? array_map([self::class, 'getPublicData'], $comment['replies']) : [],
            'created_at' => $comment['created_at'],
            'updated_at' => $comment['updated_at'],
        ];
    }
}
