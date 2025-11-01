<?php
/**
 * Wall Social Platform - Settings Controller
 * 
 * Handles user settings and preferences
 */

class SettingsController
{
    /**
     * Get user settings
     * GET /api/v1/users/me/settings
     */
    public static function getSettings()
    {
        $user = AuthMiddleware::requireAuth();

        // Get or create user preferences
        $sql = "SELECT * FROM user_preferences WHERE user_id = ?";
        $preferences = Database::fetchOne($sql, [$user['user_id']]);

        if (!$preferences) {
            // Create default preferences
            $sql = "INSERT INTO user_preferences (user_id) VALUES (?)";
            Database::query($sql, [$user['user_id']]);
            $preferences = Database::fetchOne("SELECT * FROM user_preferences WHERE user_id = ?", [$user['user_id']]);
        }

        // Merge with user data
        $settings = [
            'theme' => $preferences['theme'],
            'language' => $user['preferred_language'] ?? 'en',
            'email_notifications' => (bool)$preferences['email_notifications'],
            'push_notifications' => (bool)$preferences['push_notifications'],
            'notification_frequency' => $preferences['notification_frequency'],
            'privacy_default_wall' => $preferences['privacy_default_wall'],
            'privacy_can_follow' => $preferences['privacy_can_follow'],
            'privacy_can_message' => $preferences['privacy_can_message'],
            'accessibility_font_size' => $preferences['accessibility_font_size']
        ];

        self::jsonResponse(true, ['settings' => $settings]);
    }

    /**
     * Update user settings
     * PATCH /api/v1/users/me/settings
     */
    public static function updateSettings()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        try {
            // Get or create preferences
            $sql = "SELECT * FROM user_preferences WHERE user_id = ?";
            $preferences = Database::fetchOne($sql, [$user['user_id']]);

            if (!$preferences) {
                $sql = "INSERT INTO user_preferences (user_id) VALUES (?)";
                Database::query($sql, [$user['user_id']]);
            }

            // Update preferences
            $allowedFields = [
                'theme', 'language', 'email_notifications', 'push_notifications',
                'notification_frequency', 'privacy_default_wall', 'privacy_can_follow',
                'privacy_can_message', 'accessibility_font_size'
            ];

            $updates = [];
            $params = [];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $updates[] = "$key = ?";
                    $params[] = $value;
                }
            }

            if (!empty($updates)) {
                $params[] = $user['user_id'];
                $sql = "UPDATE user_preferences SET " . implode(', ', $updates) . " WHERE user_id = ?";
                Database::query($sql, $params);
            }

            // Update language in users table if provided
            if (isset($data['language'])) {
                $sql = "UPDATE users SET preferred_language = ? WHERE user_id = ?";
                Database::query($sql, [$data['language'], $user['user_id']]);
            }

            // Get updated settings
            $sql = "SELECT * FROM user_preferences WHERE user_id = ?";
            $preferences = Database::fetchOne($sql, [$user['user_id']]);

            $updatedUser = User::findById($user['user_id']);

            $settings = [
                'theme' => $preferences['theme'],
                'language' => $updatedUser['preferred_language'] ?? 'en',
                'email_notifications' => (bool)$preferences['email_notifications'],
                'push_notifications' => (bool)$preferences['push_notifications'],
                'notification_frequency' => $preferences['notification_frequency'],
                'privacy_default_wall' => $preferences['privacy_default_wall'],
                'privacy_can_follow' => $preferences['privacy_can_follow'],
                'privacy_can_message' => $preferences['privacy_can_message'],
                'accessibility_font_size' => $preferences['accessibility_font_size']
            ];

            self::jsonResponse(true, [
                'settings' => $settings,
                'message' => 'Settings updated successfully'
            ]);

        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'UPDATE_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Change password
     * POST /api/v1/users/me/change-password
     */
    public static function changePassword()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['current_password']) || empty($data['new_password'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'Current password and new password are required', 400);
        }

        // Verify current password
        if (!password_verify($data['current_password'], $user['password_hash'])) {
            self::jsonResponse(false, ['code' => 'INVALID_PASSWORD'], 'Current password is incorrect', 400);
        }

        // Validate new password
        if (strlen($data['new_password']) < 8) {
            self::jsonResponse(false, ['code' => 'WEAK_PASSWORD'], 'Password must be at least 8 characters', 400);
        }

        try {
            $newHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?";
            Database::query($sql, [$newHash, $user['user_id']]);

            // Invalidate all other sessions except current
            $currentSessionToken = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $currentSessionToken = str_replace('Bearer ', '', $currentSessionToken);

            $sql = "DELETE FROM sessions WHERE user_id = ? AND session_token != ?";
            Database::query($sql, [$user['user_id'], $currentSessionToken]);

            self::jsonResponse(true, ['message' => 'Password changed successfully']);

        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'UPDATE_FAILED'], $e->getMessage(), 500);
        }
    }

    /**
     * Delete account (soft delete)
     * DELETE /api/v1/users/me/account
     */
    public static function deleteAccount()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['password'])) {
            self::jsonResponse(false, ['code' => 'PASSWORD_REQUIRED'], 'Password is required to delete account', 400);
        }

        // Verify password
        if (!password_verify($data['password'], $user['password_hash'])) {
            self::jsonResponse(false, ['code' => 'INVALID_PASSWORD'], 'Password is incorrect', 400);
        }

        try {
            // Soft delete: deactivate account
            $sql = "UPDATE users SET is_active = FALSE, updated_at = NOW() WHERE user_id = ?";
            Database::query($sql, [$user['user_id']]);

            // Delete all sessions
            $sql = "DELETE FROM sessions WHERE user_id = ?";
            Database::query($sql, [$user['user_id']]);

            self::jsonResponse(true, ['message' => 'Account deleted successfully']);

        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'DELETE_FAILED'], $e->getMessage(), 500);
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
