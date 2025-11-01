<?php
/**
 * Wall Social Platform - User Profile Controller
 * 
 * Handles user profile operations
 */

class UserController
{
    /**
     * Get current user profile
     * GET /api/v1/users/me
     */
    public static function getMyProfile()
    {
        $user = AuthMiddleware::requireAuth();

        // Get social links
        $links = SocialLink::getUserLinks($user['user_id'], true);

        $response = $user;
        $response['social_links'] = SocialLink::getPublicData($links);

        self::jsonResponse(true, ['user' => $response]);
    }

    /**
     * Update current user profile
     * PATCH /api/v1/users/me
     */
    public static function updateMyProfile()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        try {
            $updated = User::update($user['user_id'], $data);

            if ($updated) {
                $updatedUser = User::findById($user['user_id']);
                self::jsonResponse(true, [
                    'user' => User::getPublicData($updatedUser),
                    'message' => 'Profile updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update profile');
            }
        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'UPDATE_FAILED'
            ], $e->getMessage(), 400);
        }
    }

    /**
     * Update user bio
     * PATCH /api/v1/users/me/bio
     */
    public static function updateBio()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (!isset($data['bio']) && !isset($data['extended_bio'])) {
            self::jsonResponse(false, [
                'code' => 'INVALID_INPUT'
            ], 'Bio or extended_bio is required', 400);
        }

        try {
            $updateData = [];
            if (isset($data['bio'])) {
                $updateData['bio'] = Validator::sanitize($data['bio']);
            }
            if (isset($data['extended_bio'])) {
                // Allow HTML but sanitize
                $updateData['extended_bio'] = self::sanitizeHtml($data['extended_bio']);
            }

            User::update($user['user_id'], $updateData);
            
            self::jsonResponse(true, [
                'bio' => $updateData['bio'] ?? null,
                'extended_bio' => $updateData['extended_bio'] ?? null,
                'message' => 'Bio updated successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'UPDATE_FAILED'
            ], $e->getMessage(), 400);
        }
    }

    /**
     * Get user by ID (public profile)
     * GET /api/v1/users/{userId}
     */
    public static function getUserProfile($params)
    {
        $userId = $params['userId'] ?? null;

        if (!$userId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }

        $user = User::findById($userId);

        if (!$user) {
            self::jsonResponse(false, ['code' => 'USER_NOT_FOUND'], 'User not found', 404);
        }

        // Get social links (only visible ones for public)
        $links = SocialLink::getUserLinks($userId, false);

        $response = User::getPublicData($user);
        $response['social_links'] = SocialLink::getPublicData($links);

        self::jsonResponse(true, ['user' => $response]);
    }

    /**
     * Search users by username
     * GET /api/v1/users/search?q=username
     */
    public static function searchUsers()
    {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            self::jsonResponse(false, ['code' => 'INVALID_QUERY'], 'Search query is required', 400);
        }

        $users = User::searchByUsername($query);

        self::jsonResponse(true, [
            'users' => array_map(fn($u) => User::getPublicData($u), $users),
            'count' => count($users)
        ]);
    }

    /**
     * Get user social links
     * GET /api/v1/users/me/links
     */
    public static function getMyLinks()
    {
        $user = AuthMiddleware::requireAuth();
        $links = SocialLink::getUserLinks($user['user_id'], true);

        self::jsonResponse(true, [
            'links' => SocialLink::getPublicData($links)
        ]);
    }

    /**
     * Get user public social links
     * GET /api/v1/users/{userId}/links
     */
    public static function getUserLinks($params)
    {
        $userId = $params['userId'] ?? null;

        if (!$userId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }

        $links = SocialLink::getUserLinks($userId, false);

        self::jsonResponse(true, [
            'links' => SocialLink::getPublicData($links)
        ]);
    }

    /**
     * Add social link
     * POST /api/v1/users/me/links
     */
    public static function addLink()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['link_url'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'Link URL is required', 400);
        }

        // Validate URL
        if (!filter_var($data['link_url'], FILTER_VALIDATE_URL)) {
            self::jsonResponse(false, ['code' => 'INVALID_URL'], 'Invalid URL format', 400);
        }

        try {
            // Auto-detect link type if not provided
            if (empty($data['link_type'])) {
                $data['link_type'] = SocialLink::detectLinkType($data['link_url']);
            }

            $data['user_id'] = $user['user_id'];
            $link = SocialLink::create($data);

            self::jsonResponse(true, [
                'link' => SocialLink::getPublicData([$link])[0],
                'message' => 'Social link added successfully'
            ], 201);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'CREATE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Update social link
     * PATCH /api/v1/users/me/links/{linkId}
     */
    public static function updateLink($params)
    {
        $user = AuthMiddleware::requireAuth();
        $linkId = $params['linkId'] ?? null;
        $data = self::getRequestData();

        if (!$linkId) {
            self::jsonResponse(false, ['code' => 'INVALID_LINK_ID'], 'Link ID is required', 400);
        }

        $link = SocialLink::findById($linkId);

        if (!$link || $link['user_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'LINK_NOT_FOUND'], 'Link not found', 404);
        }

        try {
            SocialLink::update($linkId, $data);
            $updatedLink = SocialLink::findById($linkId);

            self::jsonResponse(true, [
                'link' => SocialLink::getPublicData([$updatedLink])[0],
                'message' => 'Link updated successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'UPDATE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Delete social link
     * DELETE /api/v1/users/me/links/{linkId}
     */
    public static function deleteLink($params)
    {
        $user = AuthMiddleware::requireAuth();
        $linkId = $params['linkId'] ?? null;

        if (!$linkId) {
            self::jsonResponse(false, ['code' => 'INVALID_LINK_ID'], 'Link ID is required', 400);
        }

        $link = SocialLink::findById($linkId);

        if (!$link || $link['user_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'LINK_NOT_FOUND'], 'Link not found', 404);
        }

        try {
            SocialLink::delete($linkId);
            self::jsonResponse(true, ['message' => 'Link deleted successfully']);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'DELETE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Reorder social links
     * POST /api/v1/users/me/links/reorder
     */
    public static function reorderLinks()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['link_order']) || !is_array($data['link_order'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'link_order array is required', 400);
        }

        try {
            SocialLink::reorder($user['user_id'], $data['link_order']);
            self::jsonResponse(true, ['message' => 'Link order updated successfully']);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'REORDER_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Sanitize HTML content
     */
    private static function sanitizeHtml($html)
    {
        // Basic HTML sanitization - allow only safe tags
        $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><blockquote><code><pre>';
        $cleaned = strip_tags($html, $allowedTags);
        
        // Remove dangerous attributes
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
