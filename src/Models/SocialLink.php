<?php
/**
 * Wall Social Platform - Social Link Model
 * 
 * Handles user social links
 */

class SocialLink
{
    /**
     * Get user's social links
     */
    public static function getUserLinks($userId, $includeHidden = false)
    {
        $sql = "SELECT * FROM user_social_links 
                WHERE user_id = ?";
        
        if (!$includeHidden) {
            $sql .= " AND is_visible = TRUE";
        }
        
        $sql .= " ORDER BY display_order ASC, created_at ASC";
        
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Find link by ID
     */
    public static function findById($linkId)
    {
        $sql = "SELECT * FROM user_social_links WHERE link_id = ?";
        return Database::fetchOne($sql, [$linkId]);
    }

    /**
     * Create new social link
     */
    public static function create($data)
    {
        // Get next display order
        $sql = "SELECT COALESCE(MAX(display_order), -1) + 1 as next_order 
                FROM user_social_links WHERE user_id = ?";
        $result = Database::fetchOne($sql, [$data['user_id']]);
        $displayOrder = $result['next_order'];

        $sql = "INSERT INTO user_social_links (
            user_id, link_type, link_url, link_label, 
            icon_url, display_order, is_visible, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['user_id'],
            $data['link_type'] ?? 'custom',
            $data['link_url'],
            $data['link_label'] ?? '',
            $data['icon_url'] ?? null,
            $displayOrder,
            $data['is_visible'] ?? true
        ];

        try {
            Database::query($sql, $params);
            $linkId = Database::lastInsertId();
            return self::findById($linkId);
        } catch (Exception $e) {
            throw new Exception('Failed to create social link: ' . $e->getMessage());
        }
    }

    /**
     * Update social link
     */
    public static function update($linkId, $data)
    {
        $fields = [];
        $params = [];

        $allowedFields = ['link_type', 'link_url', 'link_label', 'icon_url', 'is_visible', 'is_verified'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return true;
        }

        $params[] = $linkId;
        $sql = "UPDATE user_social_links SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE link_id = ?";
        
        Database::query($sql, $params);
        return true;
    }

    /**
     * Delete social link
     */
    public static function delete($linkId)
    {
        $sql = "DELETE FROM user_social_links WHERE link_id = ?";
        Database::query($sql, [$linkId]);
        return true;
    }

    /**
     * Reorder links
     */
    public static function reorder($userId, $linkOrder)
    {
        try {
            Database::beginTransaction();

            foreach ($linkOrder as $order => $linkId) {
                $sql = "UPDATE user_social_links 
                        SET display_order = ?, updated_at = NOW() 
                        WHERE link_id = ? AND user_id = ?";
                Database::query($sql, [$order, $linkId, $userId]);
            }

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw new Exception('Failed to reorder links: ' . $e->getMessage());
        }
    }

    /**
     * Detect link type from URL
     */
    public static function detectLinkType($url)
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $domain = strtolower(str_replace('www.', '', $domain));

        $typeMap = [
            'github.com' => 'github',
            'twitter.com' => 'twitter',
            'x.com' => 'twitter',
            'linkedin.com' => 'linkedin',
            'facebook.com' => 'facebook',
            'instagram.com' => 'instagram',
            'youtube.com' => 'youtube',
            'tiktok.com' => 'tiktok',
            'twitch.tv' => 'twitch',
            'behance.net' => 'behance',
            'dribbble.com' => 'dribbble',
            'medium.com' => 'medium',
            'dev.to' => 'devto',
            'stackoverflow.com' => 'stackoverflow',
            'reddit.com' => 'reddit',
            'discord.gg' => 'discord',
            'discord.com' => 'discord',
            'telegram.me' => 'telegram',
            't.me' => 'telegram',
            'vk.com' => 'vk',
        ];

        return $typeMap[$domain] ?? 'website';
    }

    /**
     * Get link public data
     */
    public static function getPublicData($links)
    {
        if (!is_array($links)) {
            return null;
        }

        return array_map(function($link) {
            return [
                'link_id' => (int)$link['link_id'],
                'link_type' => $link['link_type'],
                'link_url' => $link['link_url'],
                'link_label' => $link['link_label'],
                'icon_url' => $link['icon_url'],
                'display_order' => (int)$link['display_order'],
                'is_visible' => (bool)$link['is_visible'],
                'is_verified' => (bool)$link['is_verified'],
                'created_at' => $link['created_at'],
            ];
        }, $links);
    }
}
