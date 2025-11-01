<?php
/**
 * Wall Social Platform - Wall Controller
 * 
 * Handles wall management operations
 */

class WallController
{
    /**
     * Get wall by ID or slug
     * GET /api/v1/walls/{wallIdOrSlug}
     */
    public static function getWall($params)
    {
        $identifier = $params['wallIdOrSlug'] ?? null;

        if (!$identifier) {
            self::jsonResponse(false, ['code' => 'INVALID_IDENTIFIER'], 'Wall ID or slug is required', 400);
        }

        // Try to find by ID first, then by slug, then by username
        if (is_numeric($identifier)) {
            $wall = Wall::findById($identifier);
        } else {
            // Try slug first
            $wall = Wall::findBySlug($identifier);
            
            // If not found, try username
            if (!$wall) {
                $userSql = "SELECT user_id FROM users WHERE username = ?";
                $user = Database::fetchOne($userSql, [$identifier]);
                if ($user) {
                    $wall = Wall::findByUserId($user['user_id']);
                }
            }
        }

        if (!$wall) {
            self::jsonResponse(false, ['code' => 'WALL_NOT_FOUND'], 'Wall not found', 404);
        }

        // Check if user can view
        $currentUser = AuthMiddleware::optionalAuth();
        $userId = $currentUser ? $currentUser['user_id'] : null;

        if (!Wall::canView($wall, $userId)) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not have permission to view this wall', 403);
        }

        $wallData = Wall::getWallWithOwner($wall['wall_id']);
        
        self::jsonResponse(true, [
            'wall' => Wall::getPublicData($wallData)
        ]);
    }

    /**
     * Get user's wall
     * GET /api/v1/users/{userId}/wall
     */
    public static function getUserWall($params)
    {
        $userId = $params['userId'] ?? null;

        if (!$userId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }

        $wall = Wall::findByUserId($userId);

        if (!$wall) {
            self::jsonResponse(false, ['code' => 'WALL_NOT_FOUND'], 'Wall not found', 404);
        }

        // Check if user can view
        $currentUser = AuthMiddleware::optionalAuth();
        $currentUserId = $currentUser ? $currentUser['user_id'] : null;

        if (!Wall::canView($wall, $currentUserId)) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not have permission to view this wall', 403);
        }

        $wallData = Wall::getWallWithOwner($wall['wall_id']);
        
        self::jsonResponse(true, [
            'wall' => Wall::getPublicData($wallData)
        ]);
    }

    /**
     * Get current user's wall
     * GET /api/v1/walls/me
     */
    public static function getMyWall()
    {
        $user = AuthMiddleware::requireAuth();
        
        $wall = Wall::findByUserId($user['user_id']);

        if (!$wall) {
            self::jsonResponse(false, ['code' => 'WALL_NOT_FOUND'], 'Wall not found', 404);
        }

        $wallData = Wall::getWallWithOwner($wall['wall_id']);
        
        self::jsonResponse(true, [
            'wall' => Wall::getPublicData($wallData)
        ]);
    }

    /**
     * Update wall
     * PATCH /api/v1/walls/{wallId}
     */
    public static function updateWall($params)
    {
        $user = AuthMiddleware::requireAuth();
        $wallId = $params['wallId'] ?? null;
        $data = self::getRequestData();

        if (!$wallId) {
            self::jsonResponse(false, ['code' => 'INVALID_WALL_ID'], 'Wall ID is required', 400);
        }

        $wall = Wall::findById($wallId);

        if (!$wall) {
            self::jsonResponse(false, ['code' => 'WALL_NOT_FOUND'], 'Wall not found', 404);
        }

        // Check ownership
        if ($wall['user_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not own this wall', 403);
        }

        try {
            // Validate wall slug if provided
            if (isset($data['wall_slug'])) {
                if (!self::isValidSlug($data['wall_slug'])) {
                    throw new Exception('Invalid wall slug format. Use only letters, numbers, hyphens, and underscores.');
                }

                if (!Wall::isSlugAvailable($data['wall_slug'], $wallId)) {
                    throw new Exception('Wall slug is already taken');
                }
            }

            Wall::update($wallId, $data);
            $updatedWall = Wall::getWallWithOwner($wallId);

            self::jsonResponse(true, [
                'wall' => Wall::getPublicData($updatedWall),
                'message' => 'Wall updated successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'UPDATE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Create new wall
     * POST /api/v1/walls
     */
    public static function createWall()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['wall_slug']) || empty($data['display_name'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'wall_slug and display_name are required', 400);
        }

        try {
            // Validate wall slug
            if (!self::isValidSlug($data['wall_slug'])) {
                throw new Exception('Invalid wall slug format. Use only letters, numbers, hyphens, and underscores.');
            }

            if (!Wall::isSlugAvailable($data['wall_slug'])) {
                throw new Exception('Wall slug is already taken');
            }

            $data['user_id'] = $user['user_id'];
            $wall = Wall::create($data);

            self::jsonResponse(true, [
                'wall' => Wall::getPublicData($wall),
                'message' => 'Wall created successfully'
            ], 201);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'CREATE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Delete wall
     * DELETE /api/v1/walls/{wallId}
     */
    public static function deleteWall($params)
    {
        $user = AuthMiddleware::requireAuth();
        $wallId = $params['wallId'] ?? null;

        if (!$wallId) {
            self::jsonResponse(false, ['code' => 'INVALID_WALL_ID'], 'Wall ID is required', 400);
        }

        $wall = Wall::findById($wallId);

        if (!$wall) {
            self::jsonResponse(false, ['code' => 'WALL_NOT_FOUND'], 'Wall not found', 404);
        }

        // Check ownership
        if ($wall['user_id'] != $user['user_id']) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not own this wall', 403);
        }

        try {
            Wall::delete($wallId);
            self::jsonResponse(true, ['message' => 'Wall deleted successfully']);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'DELETE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Check if wall settings are valid
     * GET /api/v1/walls/check-slug/{slug}
     */
    public static function checkSlug($params)
    {
        $slug = $params['slug'] ?? null;

        if (!$slug) {
            self::jsonResponse(false, ['code' => 'INVALID_SLUG'], 'Slug is required', 400);
        }

        $isValid = self::isValidSlug($slug);
        $isAvailable = $isValid ? Wall::isSlugAvailable($slug) : false;

        self::jsonResponse(true, [
            'slug' => $slug,
            'is_valid' => $isValid,
            'is_available' => $isAvailable
        ]);
    }

    /**
     * Validate slug format
     */
    private static function isValidSlug($slug)
    {
        // Slug must be 3-50 characters, only letters, numbers, hyphens, and underscores
        return preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $slug);
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
