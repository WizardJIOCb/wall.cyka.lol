<?php
/**
 * Wall Social Platform - Post Model
 * 
 * Handles post data operations
 */

class Post
{
    /**
     * Find post by ID
     */
    public static function findById($postId)
    {
        $sql = "SELECT p.*, u.username, u.display_name as author_name, u.avatar_url as author_avatar
                FROM posts p
                JOIN users u ON p.author_id = u.user_id
                WHERE p.post_id = ? AND p.is_deleted = FALSE";
        return Database::fetchOne($sql, [$postId]);
    }

    /**
     * Get wall posts
     */
    public static function getWallPosts($wallId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT p.*, u.username, u.display_name as author_name, u.avatar_url as author_avatar
                FROM posts p
                JOIN users u ON p.author_id = u.user_id
                WHERE p.wall_id = ? AND p.is_deleted = FALSE
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        return Database::fetchAll($sql, [$wallId, $limit, $offset]);
    }

    /**
     * Get user posts
     */
    public static function getUserPosts($userId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT p.*, u.username, u.display_name as author_name, u.avatar_url as author_avatar
                FROM posts p
                JOIN users u ON p.author_id = u.user_id
                WHERE p.author_id = ? AND p.is_deleted = FALSE
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        return Database::fetchAll($sql, [$userId, $limit, $offset]);
    }

    /**
     * Get feed posts for user
     * Returns posts from public walls that the user can view
     */
    public static function getFeedPosts($userId, $limit = 20, $offset = 0, $sortBy = 'recent')
    {
        // Determine sort order
        $orderBy = 'p.created_at DESC';
        if ($sortBy === 'popular') {
            $orderBy = 'p.reaction_count DESC, p.created_at DESC';
        } elseif ($sortBy === 'trending') {
            $orderBy = 'p.view_count DESC, p.created_at DESC';
        }

        $sql = "SELECT p.*, u.username, u.display_name as author_name, u.avatar_url as author_avatar,
                w.privacy_level
                FROM posts p
                JOIN users u ON p.author_id = u.user_id
                JOIN walls w ON p.wall_id = w.wall_id
                WHERE p.is_deleted = FALSE 
                AND w.privacy_level = 'public'
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";
        
        return Database::fetchAll($sql, [$limit, $offset]);
    }

    /**
     * Create new post
     */
    public static function create($data)
    {
        $sql = "INSERT INTO posts (
            wall_id, author_id, post_type, content_text, content_html,
            visibility, enable_comments, enable_reactions, enable_reposts,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['wall_id'],
            $data['author_id'],
            $data['post_type'] ?? 'text',
            $data['content_text'] ?? '',
            $data['content_html'] ?? '',
            $data['visibility'] ?? 'public',
            $data['enable_comments'] ?? true,
            $data['enable_reactions'] ?? true,
            $data['enable_reposts'] ?? true
        ];

        try {
            Database::beginTransaction();
            
            Database::query($sql, $params);
            $postId = Database::lastInsertId();
            
            // Increment user posts_count
            $updateSql = "UPDATE users SET posts_count = posts_count + 1 WHERE user_id = ?";
            Database::query($updateSql, [$data['author_id']]);
            
            // Increment wall posts_count
            $updateWallSql = "UPDATE walls SET posts_count = posts_count + 1 WHERE wall_id = ?";
            Database::query($updateWallSql, [$data['wall_id']]);
            
            Database::commit();
            
            return self::findById($postId);
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to create post: ' . $e->getMessage());
        }
    }

    /**
     * Update post
     */
    public static function update($postId, $data)
    {
        $fields = [];
        $params = [];

        $allowedFields = ['content_text', 'content_html', 'visibility', 'enable_comments', 'enable_reactions', 'enable_reposts'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return true;
        }

        // Set is_edited flag
        $fields[] = "is_edited = TRUE";

        $params[] = $postId;
        $sql = "UPDATE posts SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE post_id = ?";
        
        Database::query($sql, $params);
        return true;
    }

    /**
     * Delete post (soft delete)
     */
    public static function delete($postId)
    {
        try {
            Database::beginTransaction();
            
            // Get post info for decrementing counters
            $post = self::findById($postId);
            
            // Soft delete
            $sql = "UPDATE posts SET is_deleted = TRUE, updated_at = NOW() WHERE post_id = ?";
            Database::query($sql, [$postId]);
            
            // Decrement user posts_count
            $updateSql = "UPDATE users SET posts_count = posts_count - 1 WHERE user_id = ? AND posts_count > 0";
            Database::query($updateSql, [$post['author_id']]);
            
            // Decrement wall posts_count
            $updateWallSql = "UPDATE walls SET posts_count = posts_count - 1 WHERE wall_id = ? AND posts_count > 0";
            Database::query($updateWallSql, [$post['wall_id']]);
            
            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to delete post: ' . $e->getMessage());
        }
    }

    /**
     * Get post with media attachments
     */
    public static function getPostWithMedia($postId)
    {
        $post = self::findById($postId);
        
        if (!$post) {
            return null;
        }

        // Get media attachments
        $media = MediaAttachment::getPostMedia($postId);
        $post['media_attachments'] = $media;

        // Get location if exists
        if ($post['location_id']) {
            $location = Location::findById($post['location_id']);
            $post['location'] = $location;
        }

        return $post;
    }

    /**
     * Get post public data
     */
    public static function getPublicData($post)
    {
        if (!$post) return null;

        return [
            'post_id' => (int)$post['post_id'],
            'wall_id' => (int)$post['wall_id'],
            'author_id' => (int)$post['author_id'],
            'author_username' => $post['username'] ?? null,
            'author_name' => $post['author_name'] ?? null,
            'author_avatar' => $post['author_avatar'] ?? null,
            'post_type' => $post['post_type'],
            'content_text' => $post['content_text'],
            'content_html' => $post['content_html'],
            'visibility' => $post['visibility'],
            'enable_comments' => (bool)$post['enable_comments'],
            'enable_reactions' => (bool)$post['enable_reactions'],
            'enable_reposts' => (bool)$post['enable_reposts'],
            'reaction_count' => (int)($post['reaction_count'] ?? 0),
            'like_count' => (int)($post['like_count'] ?? 0),
            'dislike_count' => (int)($post['dislike_count'] ?? 0),
            'comment_count' => (int)($post['comment_count'] ?? 0),
            'repost_count' => (int)($post['repost_count'] ?? 0),
            'view_count' => (int)($post['view_count'] ?? 0),
            'is_edited' => (bool)$post['is_edited'],
            'is_pinned' => (bool)$post['is_pinned'],
            'media_attachments' => $post['media_attachments'] ?? [],
            'location' => $post['location'] ?? null,
            'created_at' => $post['created_at'],
            'updated_at' => $post['updated_at'],
        ];
    }

    /**
     * Increment view count
     */
    public static function incrementViewCount($postId)
    {
        $sql = "UPDATE posts SET view_count = view_count + 1 WHERE post_id = ?";
        Database::query($sql, [$postId]);
    }

    /**
     * Pin/unpin post
     */
    public static function togglePin($postId, $isPinned)
    {
        $sql = "UPDATE posts SET is_pinned = ?, updated_at = NOW() WHERE post_id = ?";
        Database::query($sql, [$isPinned, $postId]);
    }
}
