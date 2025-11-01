<?php
/**
 * Wall Social Platform - Notification Controller
 * 
 * Handles notification operations
 */

class NotificationController
{
    /**
     * Get user notifications
     * GET /api/v1/notifications
     */
    public static function getNotifications()
    {
        $user = AuthMiddleware::requireAuth();
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        
        $sql = "SELECT n.*, 
                u.username as actor_username,
                u.display_name as actor_display_name,
                u.avatar_url as actor_avatar
                FROM notifications n
                LEFT JOIN users u ON n.actor_id = u.user_id
                WHERE n.user_id = ?";
        
        $params = [$user['user_id']];
        
        if ($filter === 'unread') {
            $sql .= " AND n.is_read = FALSE";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $notifications = Database::fetchAll($sql, $params);
        
        // Parse JSON content
        foreach ($notifications as &$notification) {
            if ($notification['content']) {
                $notification['content'] = json_decode($notification['content'], true);
            }
        }
        
        self::jsonResponse(true, [
            'notifications' => $notifications,
            'count' => count($notifications),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    /**
     * Get unread count
     * GET /api/v1/notifications/unread-count
     */
    public static function getUnreadCount()
    {
        $user = AuthMiddleware::requireAuth();
        
        $count = NotificationService::getUnreadCount($user['user_id']);
        
        self::jsonResponse(true, [
            'unread_count' => $count
        ]);
    }
    
    /**
     * Mark notification as read
     * PATCH /api/v1/notifications/:notificationId/read
     */
    public static function markAsRead($params)
    {
        $user = AuthMiddleware::requireAuth();
        $notificationId = $params['notificationId'] ?? null;
        
        if (!$notificationId) {
            self::jsonResponse(false, ['code' => 'INVALID_NOTIFICATION_ID'], 'Notification ID is required', 400);
        }
        
        // Verify ownership
        $sql = "SELECT user_id FROM notifications WHERE notification_id = ?";
        $notification = Database::fetchOne($sql, [$notificationId]);
        
        if (!$notification) {
            self::jsonResponse(false, ['code' => 'NOT_FOUND'], 'Notification not found', 404);
        }
        
        if ($notification['user_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'Not your notification', 403);
        }
        
        NotificationService::markAsRead($notificationId, $user['user_id']);
        
        self::jsonResponse(true, [
            'message' => 'Notification marked as read'
        ]);
    }
    
    /**
     * Mark all notifications as read
     * POST /api/v1/notifications/mark-all-read
     */
    public static function markAllAsRead()
    {
        $user = AuthMiddleware::requireAuth();
        
        $count = NotificationService::markAllAsRead($user['user_id']);
        
        self::jsonResponse(true, [
            'updated_count' => $count,
            'message' => 'All notifications marked as read'
        ]);
    }
    
    /**
     * Send JSON response
     */
    private static function jsonResponse($success, $data = [], $message = '', $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'data' => $data
        ];

        if ($message) {
            $response['message'] = $message;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
