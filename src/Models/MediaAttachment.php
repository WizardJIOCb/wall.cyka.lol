<?php
/**
 * Wall Social Platform - Media Attachment Model
 * 
 * Handles media attachments for posts
 */

class MediaAttachment
{
    /**
     * Find media by ID
     */
    public static function findById($mediaId)
    {
        $sql = "SELECT * FROM media_attachments WHERE media_id = ?";
        return Database::fetchOne($sql, [$mediaId]);
    }

    /**
     * Get post media attachments
     */
    public static function getPostMedia($postId)
    {
        $sql = "SELECT * FROM media_attachments 
                WHERE post_id = ? 
                ORDER BY display_order ASC, created_at ASC";
        return Database::fetchAll($sql, [$postId]);
    }

    /**
     * Create media attachment
     */
    public static function create($data)
    {
        $sql = "INSERT INTO media_attachments (
            post_id, media_type, file_url, thumbnail_url, file_name,
            file_size, mime_type, width, height, duration,
            alt_text, display_order, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['post_id'],
            $data['media_type'],
            $data['file_url'],
            $data['thumbnail_url'] ?? null,
            $data['file_name'] ?? null,
            $data['file_size'] ?? null,
            $data['mime_type'] ?? null,
            $data['width'] ?? null,
            $data['height'] ?? null,
            $data['duration'] ?? null,
            $data['alt_text'] ?? '',
            $data['display_order'] ?? 0
        ];

        try {
            Database::query($sql, $params);
            $mediaId = Database::lastInsertId();
            return self::findById($mediaId);
        } catch (Exception $e) {
            throw new Exception('Failed to create media attachment: ' . $e->getMessage());
        }
    }

    /**
     * Update media attachment
     */
    public static function update($mediaId, $data)
    {
        $fields = [];
        $params = [];

        $allowedFields = ['alt_text', 'display_order', 'thumbnail_url'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return true;
        }

        $params[] = $mediaId;
        $sql = "UPDATE media_attachments SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE media_id = ?";
        
        Database::query($sql, $params);
        return true;
    }

    /**
     * Delete media attachment
     */
    public static function delete($mediaId)
    {
        $sql = "DELETE FROM media_attachments WHERE media_id = ?";
        Database::query($sql, [$mediaId]);
        return true;
    }

    /**
     * Get public media data
     */
    public static function getPublicData($media)
    {
        if (!is_array($media)) {
            return null;
        }

        return array_map(function($item) {
            return [
                'media_id' => (int)$item['media_id'],
                'media_type' => $item['media_type'],
                'file_url' => $item['file_url'],
                'thumbnail_url' => $item['thumbnail_url'],
                'file_name' => $item['file_name'],
                'file_size' => (int)$item['file_size'],
                'mime_type' => $item['mime_type'],
                'width' => $item['width'] ? (int)$item['width'] : null,
                'height' => $item['height'] ? (int)$item['height'] : null,
                'duration' => $item['duration'] ? (int)$item['duration'] : null,
                'alt_text' => $item['alt_text'],
                'display_order' => (int)$item['display_order'],
                'created_at' => $item['created_at'],
            ];
        }, $media);
    }
}
