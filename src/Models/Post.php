<?php
/**
 * Wall Social Platform - Post Model
 * 
 * Handles post data operations
 */

namespace App\Models;

use App\Utils\Database;
use Exception;

class Post
{
    /**
     * Find post by ID
     */
    public static function findById($postId)
    {
        $sql = "SELECT p.*, p.view_count, u.username, u.display_name as author_name, u.avatar_url as author_avatar
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
        // Get token counts from ai_generation_jobs table (source of truth) not ai_applications
        $sql = "SELECT p.*, u.username, u.display_name as author_name, u.avatar_url as author_avatar,
                ai.status as ai_status, ai.app_id, ai.job_id, ai.queue_position, ai.user_prompt,
                ai.html_content, ai.css_content, ai.js_content, ai.generation_model,
                ai.generation_time,
                job.actual_bricks_cost,
                job.prompt_tokens as input_tokens,
                job.completion_tokens as output_tokens,
                job.total_tokens,
                job.tokens_per_second,
                job.elapsed_time
                FROM posts p
                JOIN users u ON p.author_id = u.user_id
                LEFT JOIN ai_applications ai ON p.post_id = ai.post_id
                LEFT JOIN ai_generation_jobs job ON ai.job_id = job.job_id
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
            is_repost, original_post_id, repost_commentary,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['wall_id'],
            $data['author_id'],
            $data['post_type'] ?? 'text',
            $data['content_text'] ?? '',
            $data['content_html'] ?? '',
            isset($data['is_repost']) ? (int)$data['is_repost'] : 0,
            $data['original_post_id'] ?? null,
            $data['repost_commentary'] ?? null
        ];

        try {
            Database::beginTransaction();
            
            Database::query($sql, $params);
            $postId = Database::lastInsertId();
            
            // Increment user posts_count
            $updateSql = "UPDATE users SET posts_count = posts_count + 1 WHERE user_id = ?";
            Database::query($updateSql, [$data['author_id']]);
            
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
     * Increment view count for a post
     */
    public static function incrementViewCount($postId)
    {
        $sql = "UPDATE posts SET view_count = view_count + 1 WHERE post_id = ?";
        Database::query($sql, [$postId]);
    }

    /**
     * Get post public data
     */
    public static function getPublicData($post)
    {
        if (!$post) return null;

        $publicData = [
            'post_id' => (int)$post['post_id'],
            'wall_id' => (int)$post['wall_id'],
            'author_id' => (int)$post['author_id'],
            'author_username' => $post['username'] ?? null,
            'author_name' => $post['author_name'] ?? null,
            'author_avatar' => $post['author_avatar'] ?? null,
            'post_type' => $post['post_type'],
            'content_text' => $post['content_text'],
            'content_html' => $post['content_html'],
            'is_repost' => (bool)($post['is_repost'] ?? false),
            'original_post_id' => $post['original_post_id'] ?? null,
            'repost_commentary' => $post['repost_commentary'] ?? null,
            'reaction_count' => (int)($post['reaction_count'] ?? 0),
            'comments_count' => (int)($post['comment_count'] ?? 0),
            'share_count' => (int)($post['share_count'] ?? 0),
            'view_count' => (int)($post['view_count'] ?? 0),
            'is_pinned' => (bool)($post['is_pinned'] ?? false),
            'ai_status' => $post['ai_status'] ?? null,
            'ai_app_id' => $post['app_id'] ?? null,
            'ai_job_id' => $post['job_id'] ?? null,
            'ai_model' => $post['generation_model'] ?? null,
            'ai_queue_position' => $post['queue_position'] ?? null,
            'media_attachments' => $post['media_attachments'] ?? [],
            'location' => $post['location'] ?? null,
            'created_at' => $post['created_at'],
            'updated_at' => $post['updated_at'],
        ];

        // Include AI content if it's an AI application
        if ($post['post_type'] === 'ai_app') {
            if (isset($post['user_prompt']) || isset($post['html_content'])) {
                $publicData['ai_content'] = [
                    'user_prompt' => $post['user_prompt'] ?? null,
                    'html_content' => $post['html_content'] ?? null,
                    'css_content' => $post['css_content'] ?? null,
                    'js_content' => $post['js_content'] ?? null,
                    'generation_model' => $post['generation_model'] ?? null,
                    'generation_time' => isset($post['generation_time']) ? (int)$post['generation_time'] : null,
                    'input_tokens' => isset($post['input_tokens']) ? (int)$post['input_tokens'] : 0,
                    'output_tokens' => isset($post['output_tokens']) ? (int)$post['output_tokens'] : 0,
                    'total_tokens' => isset($post['total_tokens']) ? (int)$post['total_tokens'] : 0,
                    'bricks_cost' => isset($post['actual_bricks_cost']) ? (int)$post['actual_bricks_cost'] : 0,
                    'tokens_per_second' => isset($post['tokens_per_second']) ? (float)$post['tokens_per_second'] : null,
                    'elapsed_time' => isset($post['elapsed_time']) ? (int)$post['elapsed_time'] : null,
                ];
            }
        }

        return $publicData;
    }

}
