<?php
/**
 * Wall Social Platform - Social Controller
 * 
 * Handles social features: reactions, comments, reposts
 */

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Reaction;
use App\Models\Comment;
use App\Utils\Validator;
use Exception;

class SocialController
{
    /**
     * Add reaction
     * POST /api/v1/reactions
     */
    public static function addReaction()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['reactable_type']) || empty($data['reactable_id']) || empty($data['reaction_type'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'reactable_type, reactable_id, and reaction_type are required', 400);
        }

        $validTypes = ['like', 'dislike', 'love', 'haha', 'wow', 'sad', 'angry'];
        if (!in_array($data['reaction_type'], $validTypes)) {
            self::jsonResponse(false, ['code' => 'INVALID_REACTION_TYPE'], 'Invalid reaction type', 400);
        }

        try {
            $data['user_id'] = $user['user_id'];
            Reaction::addReaction($data);

            self::jsonResponse(true, [
                'message' => 'Reaction added successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'REACTION_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Remove reaction
     * DELETE /api/v1/reactions/{reactableType}/{reactableId}
     */
    public static function removeReaction($params)
    {
        $user = AuthMiddleware::requireAuth();
        $reactableType = $params['reactableType'] ?? null;
        $reactableId = $params['reactableId'] ?? null;

        if (!$reactableType || !$reactableId) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'reactable_type and reactable_id are required', 400);
        }

        try {
            Reaction::removeReaction($user['user_id'], $reactableType, $reactableId);

            self::jsonResponse(true, [
                'message' => 'Reaction removed successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'REMOVE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Get reactions
     * GET /api/v1/reactions/{reactableType}/{reactableId}
     */
    public static function getReactions($params)
    {
        $reactableType = $params['reactableType'] ?? null;
        $reactableId = $params['reactableId'] ?? null;

        if (!$reactableType || !$reactableId) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'reactable_type and reactable_id are required', 400);
        }

        $reactions = Reaction::getReactions($reactableType, $reactableId);
        $stats = Reaction::getReactionStats($reactableType, $reactableId);

        self::jsonResponse(true, [
            'reactions' => $reactions,
            'stats' => $stats
        ]);
    }

    /**
     * Create comment
     * POST /api/v1/comments
     */
    public static function createComment()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['post_id']) || empty($data['content_text'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'post_id and content_text are required', 400);
        }

        try {
            $data['author_id'] = $user['user_id'];
            $data['content_text'] = Validator::sanitize($data['content_text']);
            
            if (!empty($data['content_html'])) {
                $data['content_html'] = self::sanitizeHtml($data['content_html']);
            }

            $comment = Comment::create($data);

            self::jsonResponse(true, [
                'comment' => Comment::getPublicData($comment),
                'message' => 'Comment created successfully'
            ], 201);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'COMMENT_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Get post comments
     * GET /api/v1/posts/{postId}/comments
     */
    public static function getPostComments($params)
    {
        $postId = $params['postId'] ?? null;
        $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : null;

        if (!$postId) {
            self::jsonResponse(false, ['code' => 'INVALID_POST_ID'], 'Post ID is required', 400);
        }

        $comments = Comment::getPostComments($postId, $parentId);
        
        $publicComments = array_map(function($comment) {
            return Comment::getPublicData($comment);
        }, $comments);

        self::jsonResponse(true, [
            'comments' => $publicComments,
            'count' => count($publicComments)
        ]);
    }

    /**
     * Get comment
     * GET /api/v1/comments/{commentId}
     */
    public static function getComment($params)
    {
        $commentId = $params['commentId'] ?? null;

        if (!$commentId) {
            self::jsonResponse(false, ['code' => 'INVALID_COMMENT_ID'], 'Comment ID is required', 400);
        }

        $comment = Comment::getCommentWithReplies($commentId);

        if (!$comment) {
            self::jsonResponse(false, ['code' => 'COMMENT_NOT_FOUND'], 'Comment not found', 404);
        }

        self::jsonResponse(true, [
            'comment' => Comment::getPublicData($comment)
        ]);
    }

    /**
     * Update comment
     * PATCH /api/v1/comments/{commentId}
     */
    public static function updateComment($params)
    {
        $user = AuthMiddleware::requireAuth();
        $commentId = $params['commentId'] ?? null;
        $data = self::getRequestData();

        if (!$commentId) {
            self::jsonResponse(false, ['code' => 'INVALID_COMMENT_ID'], 'Comment ID is required', 400);
        }

        $comment = Comment::findById($commentId);

        if (!$comment) {
            self::jsonResponse(false, ['code' => 'COMMENT_NOT_FOUND'], 'Comment not found', 404);
        }

        if ($comment['author_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not own this comment', 403);
        }

        try {
            if (isset($data['content_text'])) {
                $data['content_text'] = Validator::sanitize($data['content_text']);
            }
            if (isset($data['content_html'])) {
                $data['content_html'] = self::sanitizeHtml($data['content_html']);
            }

            Comment::update($commentId, $data);
            
            $updatedComment = Comment::findById($commentId);

            self::jsonResponse(true, [
                'comment' => Comment::getPublicData($updatedComment),
                'message' => 'Comment updated successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'UPDATE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Delete comment
     * DELETE /api/v1/comments/{commentId}
     */
    public static function deleteComment($params)
    {
        $user = AuthMiddleware::requireAuth();
        $commentId = $params['commentId'] ?? null;

        if (!$commentId) {
            self::jsonResponse(false, ['code' => 'INVALID_COMMENT_ID'], 'Comment ID is required', 400);
        }

        $comment = Comment::findById($commentId);

        if (!$comment) {
            self::jsonResponse(false, ['code' => 'COMMENT_NOT_FOUND'], 'Comment not found', 404);
        }

        if ($comment['author_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not own this comment', 403);
        }

        try {
            Comment::delete($commentId);
            self::jsonResponse(true, ['message' => 'Comment deleted successfully']);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'DELETE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Sanitize HTML content
     */
    private static function sanitizeHtml($html)
    {
        $allowedTags = '<p><br><strong><em><u><a>';
        $cleaned = strip_tags($html, $allowedTags);
        $cleaned = preg_replace('/<(\w+)[^>]*\s(on\w+|javascript:|vbscript:)[^>]*>/i', '<$1>', $cleaned);
        return $cleaned;
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
