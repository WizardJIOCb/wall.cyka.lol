<?php
/**
 * Wall Social Platform - Comment Controller
 * 
 * Handles all comment-related operations including nested replies and reactions
 */

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Wall;
use App\Models\Reaction;
use App\Services\NotificationService;
use Exception;

class CommentController
{
    /**
     * Get comments for a post
     * GET /api/v1/posts/{postId}/comments
     */
    public static function getPostComments($params)
    {
        try {
            $userId = AuthMiddleware::getUserId(false);
            $postId = (int)$params['postId'];
            
            // Verify post exists and user has access
            $post = Post::findById($postId);
            if (!$post) {
                jsonResponse(false, null, 'Post not found', 404);
            }
            
            // Get query parameters
            $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : null;
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
            $sortBy = $_GET['sort'] ?? 'created_asc'; // created_asc, created_desc, reactions
            
            // Fetch comments
            $comments = Comment::getPostComments($postId, $parentId, $limit);
            
            // Sort if needed
            if ($sortBy === 'created_desc') {
                $comments = array_reverse($comments);
            } elseif ($sortBy === 'reactions') {
                usort($comments, function($a, $b) {
                    return $b['reaction_count'] <=> $a['reaction_count'];
                });
            }
            
            // Get user reactions if authenticated
            if ($userId) {
                $commentIds = array_map(function($c) { return $c['comment_id']; }, $comments);
                if (!empty($commentIds)) {
                    $userReactions = Reaction::getUserReactions($userId, 'comment', $commentIds);
                    $reactionMap = [];
                    foreach ($userReactions as $r) {
                        $reactionMap[$r['target_id']] = $r['reaction_type'];
                    }
                    
                    foreach ($comments as &$comment) {
                        $comment['user_reaction'] = $reactionMap[$comment['comment_id']] ?? null;
                    }
                }
            }
            
            // Format response
            $formattedComments = array_map([Comment::class, 'getPublicData'], $comments);
            
            jsonResponse(true, [
                'comments' => $formattedComments,
                'count' => count($formattedComments),
                'has_more' => count($formattedComments) === $limit
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getPostComments: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to fetch comments', 500);
        }
    }
    
    /**
     * Create a comment on a post
     * POST /api/v1/posts/{postId}/comments
     */
    public static function createComment($params)
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $postId = (int)$params['postId'];
            
            // Verify post exists
            $post = Post::findById($postId);
            if (!$post) {
                jsonResponse(false, null, 'Post not found', 404);
            }
            
            // Check if comments are allowed on this post/wall
            $wall = Wall::findById($post['wall_id']);
            if ($wall && !$wall['allow_comments']) {
                jsonResponse(false, null, 'Comments are not allowed on this wall', 403);
            }
            
            // Get request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate content
            if (empty($input['content'])) {
                jsonResponse(false, null, 'Comment content is required', 400);
            }
            
            $content = trim($input['content']);
            if (strlen($content) > 2000) {
                jsonResponse(false, null, 'Comment cannot exceed 2000 characters', 400);
            }
            
            if (strlen($content) < 1) {
                jsonResponse(false, null, 'Comment cannot be empty', 400);
            }
            
            // Sanitize content
            $contentHtml = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            
            // Prepare comment data
            $commentData = [
                'post_id' => $postId,
                'author_id' => $userId,
                'parent_comment_id' => !empty($input['parent_comment_id']) ? (int)$input['parent_comment_id'] : null,
                'content_text' => $content,
                'content_html' => $contentHtml
            ];
            
            // Verify parent comment exists if provided
            if ($commentData['parent_comment_id']) {
                $parentComment = Comment::findById($commentData['parent_comment_id']);
                if (!$parentComment) {
                    jsonResponse(false, null, 'Parent comment not found', 404);
                }
                if ($parentComment['post_id'] != $postId) {
                    jsonResponse(false, null, 'Parent comment belongs to different post', 400);
                }
                // Limit nesting depth to 5 levels
                if ($parentComment['depth_level'] >= 4) {
                    jsonResponse(false, null, 'Maximum comment nesting depth reached', 400);
                }
            }
            
            // Create comment
            $comment = Comment::create($commentData);
            
            if (!$comment) {
                jsonResponse(false, null, 'Failed to create comment', 500);
            }
            
            // Create notification for post author (if not commenting on own post)
            if ($post['author_id'] != $userId) {
                NotificationService::createNotification(
                    $post['author_id'],
                    'new_comment',
                    $userId,
                    'post',
                    $postId,
                    'commented on your post'
                );
            }
            
            // Create notification for parent comment author (if replying)
            if ($commentData['parent_comment_id']) {
                $parentComment = Comment::findById($commentData['parent_comment_id']);
                if ($parentComment && $parentComment['author_id'] != $userId && $parentComment['author_id'] != $post['author_id']) {
                    NotificationService::createNotification(
                        $parentComment['author_id'],
                        'new_comment',
                        $userId,
                        'comment',
                        $comment['comment_id'],
                        'replied to your comment'
                    );
                }
            }
            
            jsonResponse(true, Comment::getPublicData($comment), 'Comment created successfully', 201);
            
        } catch (Exception $e) {
            error_log("Error in createComment: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to create comment', 500);
        }
    }
    
    /**
     * Create a reply to a comment
     * POST /api/v1/comments/{commentId}/replies
     */
    public static function createReply($params)
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $parentCommentId = (int)$params['commentId'];
            
            // Get parent comment
            $parentComment = Comment::findById($parentCommentId);
            if (!$parentComment) {
                jsonResponse(false, null, 'Parent comment not found', 404);
            }
            
            // Get request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate content
            if (empty($input['content'])) {
                jsonResponse(false, null, 'Reply content is required', 400);
            }
            
            $content = trim($input['content']);
            if (strlen($content) > 2000) {
                jsonResponse(false, null, 'Reply cannot exceed 2000 characters', 400);
            }
            
            // Limit nesting depth
            if ($parentComment['depth_level'] >= 4) {
                jsonResponse(false, null, 'Maximum comment nesting depth reached', 400);
            }
            
            // Sanitize content
            $contentHtml = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            
            // Prepare reply data
            $replyData = [
                'post_id' => $parentComment['post_id'],
                'author_id' => $userId,
                'parent_comment_id' => $parentCommentId,
                'content_text' => $content,
                'content_html' => $contentHtml
            ];
            
            // Create reply
            $reply = Comment::create($replyData);
            
            if (!$reply) {
                jsonResponse(false, null, 'Failed to create reply', 500);
            }
            
            // Create notification for parent comment author
            if ($parentComment['author_id'] != $userId) {
                NotificationService::createNotification(
                    $parentComment['author_id'],
                    'new_comment',
                    $userId,
                    'comment',
                    $reply['comment_id'],
                    'replied to your comment'
                );
            }
            
            jsonResponse(true, Comment::getPublicData($reply), 'Reply created successfully', 201);
            
        } catch (Exception $e) {
            error_log("Error in createReply: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to create reply', 500);
        }
    }
    
    /**
     * Get a single comment with replies
     * GET /api/v1/comments/{commentId}
     */
    public static function getComment($params)
    {
        try {
            $userId = AuthMiddleware::getUserId(false);
            $commentId = (int)$params['commentId'];
            
            $comment = Comment::findById($commentId);
            if (!$comment) {
                jsonResponse(false, null, 'Comment not found', 404);
            }
            
            // Get replies
            $maxDepth = isset($_GET['max_depth']) ? min((int)$_GET['max_depth'], 5) : 3;
            $commentWithReplies = Comment::getCommentWithReplies($commentId, $maxDepth);
            
            jsonResponse(true, Comment::getPublicData($commentWithReplies));
            
        } catch (Exception $e) {
            error_log("Error in getComment: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to fetch comment', 500);
        }
    }
    
    /**
     * Update a comment
     * PATCH /api/v1/comments/{commentId}
     */
    public static function updateComment($params)
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $commentId = (int)$params['commentId'];
            
            // Get comment
            $comment = Comment::findById($commentId);
            if (!$comment) {
                jsonResponse(false, null, 'Comment not found', 404);
            }
            
            // Verify ownership
            if ($comment['author_id'] != $userId) {
                jsonResponse(false, null, 'You can only edit your own comments', 403);
            }
            
            // Check edit time limit (15 minutes)
            $createdTime = strtotime($comment['created_at']);
            $currentTime = time();
            if (($currentTime - $createdTime) > 900) { // 15 minutes = 900 seconds
                jsonResponse(false, null, 'Comments can only be edited within 15 minutes of creation', 403);
            }
            
            // Get request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate content
            if (empty($input['content'])) {
                jsonResponse(false, null, 'Comment content is required', 400);
            }
            
            $content = trim($input['content']);
            if (strlen($content) > 2000) {
                jsonResponse(false, null, 'Comment cannot exceed 2000 characters', 400);
            }
            
            // Sanitize content
            $contentHtml = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            
            // Update comment
            $updateData = [
                'content_text' => $content,
                'content_html' => $contentHtml
            ];
            
            Comment::update($commentId, $updateData);
            
            // Fetch updated comment
            $updatedComment = Comment::findById($commentId);
            
            jsonResponse(true, Comment::getPublicData($updatedComment), 'Comment updated successfully');
            
        } catch (Exception $e) {
            error_log("Error in updateComment: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to update comment', 500);
        }
    }
    
    /**
     * Delete a comment
     * DELETE /api/v1/comments/{commentId}
     */
    public static function deleteComment($params)
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $commentId = (int)$params['commentId'];
            
            // Get comment
            $comment = Comment::findById($commentId);
            if (!$comment) {
                jsonResponse(false, null, 'Comment not found', 404);
            }
            
            // Verify ownership (or moderator in future)
            if ($comment['author_id'] != $userId) {
                jsonResponse(false, null, 'You can only delete your own comments', 403);
            }
            
            // Delete comment
            Comment::delete($commentId);
            
            jsonResponse(true, null, 'Comment deleted successfully');
            
        } catch (Exception $e) {
            error_log("Error in deleteComment: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to delete comment', 500);
        }
    }
    
    /**
     * React to a comment
     * POST /api/v1/comments/{commentId}/reactions
     */
    public static function reactToComment($params)
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $commentId = (int)$params['commentId'];
            
            // Verify comment exists
            $comment = Comment::findById($commentId);
            if (!$comment) {
                jsonResponse(false, null, 'Comment not found', 404);
            }
            
            // Get request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate reaction type
            $validReactions = ['like', 'dislike', 'heart', 'laugh', 'wow', 'sad', 'angry'];
            $reactionType = $input['reaction_type'] ?? 'like';
            
            if (!in_array($reactionType, $validReactions)) {
                jsonResponse(false, null, 'Invalid reaction type', 400);
            }
            
            // Add or update reaction
            $result = Reaction::addOrUpdate($userId, 'comment', $commentId, $reactionType);
            
            // Create notification if new reaction
            if ($result['action'] === 'created' && $comment['author_id'] != $userId) {
                NotificationService::createNotification(
                    $comment['author_id'],
                    'reaction',
                    $userId,
                    'comment',
                    $commentId,
                    'reacted to your comment'
                );
            }
            
            // Get updated comment with reaction counts
            $updatedComment = Comment::findById($commentId);
            
            jsonResponse(true, [
                'action' => $result['action'],
                'reaction_type' => $reactionType,
                'reaction_count' => (int)$updatedComment['reaction_count'],
                'like_count' => (int)$updatedComment['like_count'],
                'dislike_count' => (int)$updatedComment['dislike_count']
            ], ucfirst($result['action']) . ' reaction');
            
        } catch (Exception $e) {
            error_log("Error in reactToComment: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to add reaction', 500);
        }
    }
    
    /**
     * Remove reaction from comment
     * DELETE /api/v1/comments/{commentId}/reactions
     */
    public static function removeCommentReaction($params)
    {
        try {
            $userId = AuthMiddleware::getUserId();
            $commentId = (int)$params['commentId'];
            
            // Verify comment exists
            $comment = Comment::findById($commentId);
            if (!$comment) {
                jsonResponse(false, null, 'Comment not found', 404);
            }
            
            // Remove reaction
            Reaction::remove($userId, 'comment', $commentId);
            
            // Get updated comment
            $updatedComment = Comment::findById($commentId);
            
            jsonResponse(true, [
                'reaction_count' => (int)$updatedComment['reaction_count'],
                'like_count' => (int)$updatedComment['like_count'],
                'dislike_count' => (int)$updatedComment['dislike_count']
            ], 'Reaction removed');
            
        } catch (Exception $e) {
            error_log("Error in removeCommentReaction: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to remove reaction', 500);
        }
    }
    
    /**
     * Get reactions for a comment
     * GET /api/v1/comments/{commentId}/reactions
     */
    public static function getCommentReactions($params)
    {
        try {
            $commentId = (int)$params['commentId'];
            
            // Verify comment exists
            $comment = Comment::findById($commentId);
            if (!$comment) {
                jsonResponse(false, null, 'Comment not found', 404);
            }
            
            // Get reaction summary
            $reactions = Reaction::getSummary('comment', $commentId);
            
            jsonResponse(true, [
                'total_count' => (int)$comment['reaction_count'],
                'reactions' => $reactions
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getCommentReactions: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to fetch reactions', 500);
        }
    }
    
    /**
     * Get users who reacted to a comment
     * GET /api/v1/comments/{commentId}/reactions/users
     */
    public static function getCommentReactionUsers($params)
    {
        try {
            $commentId = (int)$params['commentId'];
            
            // Verify comment exists
            $comment = Comment::findById($commentId);
            if (!$comment) {
                jsonResponse(false, null, 'Comment not found', 404);
            }
            
            // Get pagination parameters
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $reactionType = $_GET['reaction_type'] ?? null;
            
            // Get users who reacted
            $users = Reaction::getUsers('comment', $commentId, $reactionType, $limit, $offset);
            
            jsonResponse(true, [
                'users' => $users,
                'count' => count($users),
                'has_more' => count($users) === $limit
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getCommentReactionUsers: " . $e->getMessage());
            jsonResponse(false, null, 'Failed to fetch reaction users', 500);
        }
    }
}
