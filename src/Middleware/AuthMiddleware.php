<?php
/**
 * Wall Social Platform - Authentication Middleware
 * 
 * Validates user authentication for protected routes
 */

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware
{
    /**
     * Require authentication
     * Returns user data if authenticated, otherwise sends 401 response
     */
    public static function requireAuth()
    {
        $sessionToken = self::getSessionToken();

        if (empty($sessionToken)) {
            self::unauthorized('No authentication token provided');
        }

        $user = AuthService::getCurrentUser($sessionToken);

        if (!$user) {
            self::unauthorized('Invalid or expired session');
        }

        // Store user in global scope for access in controllers
        $GLOBALS['current_user'] = $user;
        
        return $user;
    }

    /**
     * Optional authentication
     * Returns user data if authenticated, null otherwise
     */
    public static function optionalAuth()
    {
        $sessionToken = self::getSessionToken();

        if (empty($sessionToken)) {
            return null;
        }

        $user = AuthService::getCurrentUser($sessionToken);

        if ($user) {
            $GLOBALS['current_user'] = $user;
        }

        return $user;
    }

    /**
     * Get current authenticated user
     */
    public static function getCurrentUser()
    {
        return $GLOBALS['current_user'] ?? null;
    }

    /**
     * Get current user ID
     */
    public static function getCurrentUserId()
    {
        $user = self::getCurrentUser();
        return $user ? $user['user_id'] : null;
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated()
    {
        return isset($GLOBALS['current_user']);
    }

    /**
     * Get session token from header or cookie
     */
    private static function getSessionToken()
    {
        // Check Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }

        // Check cookie
        return $_COOKIE['session_token'] ?? null;
    }

    /**
     * Send unauthorized response
     */
    private static function unauthorized($message = 'Unauthorized')
    {
        http_response_code(401);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => [
                'code' => 'UNAUTHORIZED'
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        exit;
    }
}
