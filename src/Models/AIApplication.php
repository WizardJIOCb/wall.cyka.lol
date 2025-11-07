<?php
/**
 * Wall Social Platform - AI Application Model
 * 
 * Handles AI-generated web applications
 */

namespace App\Models;

use App\Utils\Database;
use Exception;

class AIApplication
{
    /**
     * Find application by ID
     */
    public static function findById($appId)
    {
        // Explicitly select columns to avoid conflicts with token columns
        // Token data from ai_generation_jobs is the source of truth
        $sql = "SELECT 
                a.app_id, a.post_id, a.job_id, a.user_prompt,
                a.html_content, a.css_content, a.js_content,
                a.preview_image_url, a.generation_model, a.generation_time,
                a.status, a.queue_position, a.error_message,
                a.original_app_id, a.remix_type, a.allow_remixing, a.remix_count,
                a.created_at, a.updated_at,
                job.actual_bricks_cost,
                job.prompt_tokens as input_tokens,
                job.completion_tokens as output_tokens,
                job.total_tokens
                FROM ai_applications a
                LEFT JOIN ai_generation_jobs job ON a.job_id = job.job_id
                WHERE a.app_id = ?";
        
        return Database::fetchOne($sql, [$appId]);
    }

    /**
     * Find application by job ID
     */
    public static function findByJobId($jobId)
    {
        $sql = "SELECT * FROM ai_applications WHERE job_id = ?";
        return Database::fetchOne($sql, [$jobId]);
    }

    /**
     * Create new AI application
     */
    public static function create($data)
    {
        $sql = "INSERT INTO ai_applications (
            post_id, job_id, user_prompt, html_content, css_content, js_content,
            preview_image_url, generation_model, generation_time, status,
            queue_position, error_message, original_app_id, remix_type,
            allow_remixing, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['post_id'] ?? null,
            $data['job_id'] ?? null,
            $data['user_prompt'] ?? '',
            $data['html_content'] ?? null,
            $data['css_content'] ?? null,
            $data['js_content'] ?? null,
            $data['preview_image_url'] ?? null,
            $data['generation_model'] ?? null,
            $data['generation_time'] ?? null,
            $data['status'] ?? 'queued',
            $data['queue_position'] ?? null,
            $data['error_message'] ?? null,
            $data['original_app_id'] ?? null,
            $data['remix_type'] ?? 'original',
            $data['allow_remixing'] ?? true
        ];

        try {
            Database::query($sql, $params);
            $appId = Database::lastInsertId();
            return self::findById($appId);
        } catch (Exception $e) {
            throw new Exception('Failed to create AI application: ' . $e->getMessage());
        }
    }

    /**
     * Update AI application
     */
    public static function update($appId, $data)
    {
        $fields = [];
        $params = [];

        $allowedFields = [
            'post_id', 'job_id', 'html_content', 'css_content', 'js_content',
            'preview_image_url', 'generation_model', 'generation_time', 'status',
            'queue_position', 'error_message', 'remix_count'
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

        $params[] = $appId;
        $sql = "UPDATE ai_applications SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE app_id = ?";
        
        Database::query($sql, $params);
        return true;
    }

    /**
     * Get user's AI applications
     */
    public static function getUserApplications($userId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT a.*, p.wall_id, p.author_id, u.username, u.display_name
                FROM ai_applications a
                JOIN posts p ON a.post_id = p.post_id
                JOIN users u ON p.author_id = u.user_id
                WHERE p.author_id = ? AND a.status = 'completed'
                ORDER BY a.created_at DESC
                LIMIT ? OFFSET ?";
        
        return Database::fetchAll($sql, [$userId, $limit, $offset]);
    }

    /**
     * Get popular applications
     */
    public static function getPopularApplications($limit = 20)
    {
        $sql = "SELECT a.*, p.wall_id, p.author_id, u.username, u.display_name
                FROM ai_applications a
                JOIN posts p ON a.post_id = p.post_id
                JOIN users u ON p.author_id = u.user_id
                WHERE a.status = 'completed' AND a.allow_remixing = TRUE
                ORDER BY a.remix_count DESC, a.created_at DESC
                LIMIT ?";
        
        return Database::fetchAll($sql, [$limit]);
    }

    /**
     * Get remixable applications
     */
    public static function getRemixableApplications($limit = 20)
    {
        $sql = "SELECT a.*, p.wall_id, p.author_id, u.username, u.display_name
                FROM ai_applications a
                JOIN posts p ON a.post_id = p.post_id
                JOIN users u ON p.author_id = u.user_id
                WHERE a.status = 'completed' AND a.allow_remixing = TRUE
                ORDER BY a.created_at DESC
                LIMIT ?";
        
        return Database::fetchAll($sql, [$limit]);
    }

    /**
     * Increment remix count
     */
    public static function incrementRemixCount($appId)
    {
        $sql = "UPDATE ai_applications SET remix_count = remix_count + 1, updated_at = NOW() WHERE app_id = ?";
        Database::query($sql, [$appId]);
    }

    /**
     * Get application public data
     */
    public static function getPublicData($app)
    {
        if (!$app) return null;

        return [
            'app_id' => (int)$app['app_id'],
            'post_id' => (int)$app['post_id'],
            'job_id' => $app['job_id'],
            'user_prompt' => $app['user_prompt'],
            'preview_image_url' => $app['preview_image_url'],
            'generation_model' => $app['generation_model'],
            'generation_time' => $app['generation_time'] ? (int)$app['generation_time'] : null,
            'input_tokens' => isset($app['input_tokens']) ? (int)$app['input_tokens'] : 0,
            'output_tokens' => isset($app['output_tokens']) ? (int)$app['output_tokens'] : 0,
            'total_tokens' => isset($app['total_tokens']) ? (int)$app['total_tokens'] : 0,
            'status' => $app['status'],
            'remix_type' => $app['remix_type'],
            'remix_count' => (int)$app['remix_count'],
            'allow_remixing' => (bool)$app['allow_remixing'],
            'author_id' => (int)($app['author_id'] ?? 0),
            'author_username' => $app['username'] ?? null,
            'author_name' => $app['display_name'] ?? null,
            'created_at' => $app['created_at'],
            'updated_at' => $app['updated_at'],
        ];
    }

    /**
     * Get application full data (with code)
     */
    public static function getFullData($app)
    {
        if (!$app) return null;

        $publicData = self::getPublicData($app);
        $publicData['html_content'] = $app['html_content'];
        $publicData['css_content'] = $app['css_content'];
        $publicData['js_content'] = $app['js_content'];
        $publicData['bricks_cost'] = isset($app['actual_bricks_cost']) ? (int)$app['actual_bricks_cost'] : 0;
        
        return $publicData;
    }
}
