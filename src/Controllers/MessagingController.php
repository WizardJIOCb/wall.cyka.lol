<?php
/**
 * Wall Social Platform - Messaging Controller
 * 
 * Handles private messaging between users
 */

class MessagingController
{
    /**
     * Get user's conversations
     * GET /api/v1/conversations
     */
    public static function getConversations()
    {
        $user = AuthMiddleware::requireAuth();
        
        $sql = "SELECT c.*, 
                (SELECT message_text FROM messages 
                 WHERE conversation_id = c.conversation_id 
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages 
                 WHERE conversation_id = c.conversation_id 
                 ORDER BY created_at DESC LIMIT 1) as last_message_at,
                (SELECT COUNT(*) FROM messages m
                 WHERE m.conversation_id = c.conversation_id
                 AND m.created_at > COALESCE(
                     (SELECT last_read_at FROM conversation_participants 
                      WHERE conversation_id = c.conversation_id AND user_id = ?), 
                     '1970-01-01'
                 )) as unread_count
                FROM conversations c
                JOIN conversation_participants cp ON c.conversation_id = cp.conversation_id
                WHERE cp.user_id = ?
                ORDER BY last_message_at DESC NULLS LAST";
        
        $conversations = Database::fetchAll($sql, [$user['user_id'], $user['user_id']]);
        
        // Get participants for each conversation
        foreach ($conversations as &$conv) {
            $sql = "SELECT u.user_id, u.username, u.display_name, u.avatar_url
                    FROM conversation_participants cp
                    JOIN users u ON cp.user_id = u.user_id
                    WHERE cp.conversation_id = ? AND cp.user_id != ?";
            
            $conv['participants'] = Database::fetchAll($sql, [$conv['conversation_id'], $user['user_id']]);
        }
        
        self::jsonResponse(true, [
            'conversations' => $conversations,
            'count' => count($conversations)
        ]);
    }
    
    /**
     * Create new conversation
     * POST /api/v1/conversations
     */
    public static function createConversation()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();
        
        if (empty($data['participant_ids']) || !is_array($data['participant_ids'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'participant_ids array is required', 400);
        }
        
        $participantIds = $data['participant_ids'];
        $participantIds[] = $user['user_id']; // Add current user
        $participantIds = array_unique($participantIds);
        sort($participantIds);
        
        // For direct conversations, check if already exists
        if (count($participantIds) === 2) {
            $sql = "SELECT c.conversation_id
                    FROM conversations c
                    WHERE c.conversation_type = 'direct'
                    AND (
                        SELECT COUNT(DISTINCT cp.user_id)
                        FROM conversation_participants cp
                        WHERE cp.conversation_id = c.conversation_id
                        AND cp.user_id IN (?, ?)
                    ) = 2";
            
            $existing = Database::fetchOne($sql, $participantIds);
            
            if ($existing) {
                // Return existing conversation
                $sql = "SELECT * FROM conversations WHERE conversation_id = ?";
                $conv = Database::fetchOne($sql, [$existing['conversation_id']]);
                
                self::jsonResponse(true, [
                    'conversation' => $conv,
                    'message' => 'Conversation already exists'
                ]);
                return;
            }
        }
        
        try {
            // Create conversation
            $type = count($participantIds) === 2 ? 'direct' : 'group';
            $sql = "INSERT INTO conversations (conversation_type, created_at) VALUES (?, NOW())";
            Database::query($sql, [$type]);
            $conversationId = Database::lastInsertId();
            
            // Add participants
            foreach ($participantIds as $participantId) {
                $sql = "INSERT INTO conversation_participants (conversation_id, user_id, joined_at) 
                        VALUES (?, ?, NOW())";
                Database::query($sql, [$conversationId, $participantId]);
            }
            
            // Get created conversation
            $sql = "SELECT * FROM conversations WHERE conversation_id = ?";
            $conversation = Database::fetchOne($sql, [$conversationId]);
            
            self::jsonResponse(true, [
                'conversation' => $conversation,
                'message' => 'Conversation created successfully'
            ], 201);
            
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'CREATE_FAILED'], $e->getMessage(), 500);
        }
    }
    
    /**
     * Get conversation messages
     * GET /api/v1/conversations/:conversationId/messages
     */
    public static function getMessages($params)
    {
        $user = AuthMiddleware::requireAuth();
        $conversationId = $params['conversationId'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        if (!$conversationId) {
            self::jsonResponse(false, ['code' => 'INVALID_CONVERSATION_ID'], 'Conversation ID is required', 400);
        }
        
        // Verify user is participant
        $sql = "SELECT participant_id FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ?";
        $participant = Database::fetchOne($sql, [$conversationId, $user['user_id']]);
        
        if (!$participant) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'Not a participant of this conversation', 403);
        }
        
        // Get messages
        $sql = "SELECT m.*, u.username, u.display_name, u.avatar_url,
                (m.sender_id = ?) as is_own
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.conversation_id = ? AND m.is_deleted = FALSE
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?";
        
        $messages = Database::fetchAll($sql, [$user['user_id'], $conversationId, $limit, $offset]);
        
        self::jsonResponse(true, [
            'messages' => array_reverse($messages), // Return in chronological order
            'count' => count($messages),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    /**
     * Send message
     * POST /api/v1/conversations/:conversationId/messages
     */
    public static function sendMessage($params)
    {
        $user = AuthMiddleware::requireAuth();
        $conversationId = $params['conversationId'] ?? null;
        $data = self::getRequestData();
        
        if (!$conversationId) {
            self::jsonResponse(false, ['code' => 'INVALID_CONVERSATION_ID'], 'Conversation ID is required', 400);
        }
        
        if (empty($data['message_text'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'message_text is required', 400);
        }
        
        // Verify user is participant
        $sql = "SELECT participant_id FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ?";
        $participant = Database::fetchOne($sql, [$conversationId, $user['user_id']]);
        
        if (!$participant) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'Not a participant of this conversation', 403);
        }
        
        try {
            $messageType = $data['message_type'] ?? 'text';
            $attachmentUrl = $data['attachment_url'] ?? null;
            
            // Create message
            $sql = "INSERT INTO messages (
                conversation_id, sender_id, message_text, 
                message_type, attachment_url, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())";
            
            Database::query($sql, [
                $conversationId,
                $user['user_id'],
                $data['message_text'],
                $messageType,
                $attachmentUrl
            ]);
            
            $messageId = Database::lastInsertId();
            
            // Update conversation timestamp
            $sql = "UPDATE conversations SET updated_at = NOW() WHERE conversation_id = ?";
            Database::query($sql, [$conversationId]);
            
            // Get created message
            $sql = "SELECT m.*, u.username, u.display_name, u.avatar_url
                    FROM messages m
                    JOIN users u ON m.sender_id = u.user_id
                    WHERE m.message_id = ?";
            $message = Database::fetchOne($sql, [$messageId]);
            
            // Create notifications for other participants
            $sql = "SELECT user_id FROM conversation_participants 
                    WHERE conversation_id = ? AND user_id != ?";
            $otherParticipants = Database::fetchAll($sql, [$conversationId, $user['user_id']]);
            
            foreach ($otherParticipants as $participant) {
                NotificationService::createNotification(
                    $participant['user_id'],
                    $user['user_id'],
                    'message',
                    'conversation',
                    $conversationId,
                    ['preview' => substr($data['message_text'], 0, 50)]
                );
            }
            
            self::jsonResponse(true, [
                'message' => $message
            ], 201);
            
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'SEND_FAILED'], $e->getMessage(), 500);
        }
    }
    
    /**
     * Mark conversation as read
     * PATCH /api/v1/conversations/:conversationId/read
     */
    public static function markAsRead($params)
    {
        $user = AuthMiddleware::requireAuth();
        $conversationId = $params['conversationId'] ?? null;
        
        if (!$conversationId) {
            self::jsonResponse(false, ['code' => 'INVALID_CONVERSATION_ID'], 'Conversation ID is required', 400);
        }
        
        try {
            $sql = "UPDATE conversation_participants 
                    SET last_read_at = NOW()
                    WHERE conversation_id = ? AND user_id = ?";
            Database::query($sql, [$conversationId, $user['user_id']]);
            
            self::jsonResponse(true, [
                'message' => 'Marked as read'
            ]);
            
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'UPDATE_FAILED'], $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete conversation (remove user from participants)
     * DELETE /api/v1/conversations/:conversationId
     */
    public static function deleteConversation($params)
    {
        $user = AuthMiddleware::requireAuth();
        $conversationId = $params['conversationId'] ?? null;
        
        if (!$conversationId) {
            self::jsonResponse(false, ['code' => 'INVALID_CONVERSATION_ID'], 'Conversation ID is required', 400);
        }
        
        try {
            // Remove user from participants
            $sql = "DELETE FROM conversation_participants 
                    WHERE conversation_id = ? AND user_id = ?";
            Database::query($sql, [$conversationId, $user['user_id']]);
            
            // Check if conversation still has participants
            $sql = "SELECT COUNT(*) as count FROM conversation_participants 
                    WHERE conversation_id = ?";
            $result = Database::fetchOne($sql, [$conversationId]);
            
            // If no participants left, delete conversation and messages
            if ($result['count'] == 0) {
                $sql = "DELETE FROM messages WHERE conversation_id = ?";
                Database::query($sql, [$conversationId]);
                
                $sql = "DELETE FROM conversations WHERE conversation_id = ?";
                Database::query($sql, [$conversationId]);
            }
            
            self::jsonResponse(true, [
                'message' => 'Conversation deleted'
            ]);
            
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'DELETE_FAILED'], $e->getMessage(), 500);
        }
    }
    
    /**
     * Get typing indicators (using Redis)
     * GET /api/v1/conversations/:conversationId/typing
     */
    public static function getTypingIndicators($params)
    {
        $user = AuthMiddleware::requireAuth();
        $conversationId = $params['conversationId'] ?? null;
        
        if (!$conversationId) {
            self::jsonResponse(false, ['code' => 'INVALID_CONVERSATION_ID'], 'Conversation ID is required', 400);
        }
        
        try {
            $redis = RedisConnection::getConnection();
            $pattern = "typing:{$conversationId}:*";
            $keys = $redis->keys($pattern);
            
            $typingUsers = [];
            foreach ($keys as $key) {
                $userId = (int)substr($key, strrpos($key, ':') + 1);
                if ($userId != $user['user_id']) {
                    $typingUsers[] = $userId;
                }
            }
            
            self::jsonResponse(true, [
                'typing_users' => $typingUsers
            ]);
            
        } catch (Exception $e) {
            // Redis not available, return empty array
            self::jsonResponse(true, [
                'typing_users' => []
            ]);
        }
    }
    
    /**
     * Set typing indicator
     * POST /api/v1/conversations/:conversationId/typing
     */
    public static function setTypingIndicator($params)
    {
        $user = AuthMiddleware::requireAuth();
        $conversationId = $params['conversationId'] ?? null;
        
        if (!$conversationId) {
            self::jsonResponse(false, ['code' => 'INVALID_CONVERSATION_ID'], 'Conversation ID is required', 400);
        }
        
        try {
            $redis = RedisConnection::getConnection();
            $key = "typing:{$conversationId}:{$user['user_id']}";
            $redis->setex($key, 5, '1'); // Expire after 5 seconds
            
            self::jsonResponse(true, [
                'message' => 'Typing indicator set'
            ]);
            
        } catch (Exception $e) {
            // Redis not available, ignore
            self::jsonResponse(true, [
                'message' => 'Typing indicator not available'
            ]);
        }
    }
    
    /**
     * Get request JSON data
     */
    private static function getRequestData()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
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
