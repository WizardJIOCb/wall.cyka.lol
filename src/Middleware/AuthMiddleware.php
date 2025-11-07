<?php
/**
 * Wall Social Platform - Authentication Middleware
 * 
 * Handles user authentication via session tokens
 */

namespace App\Middleware;

use App\Utils\Database;

class AuthMiddleware
{
    private static $current_user = null;
    
    /**
     * Require authentication - redirect to login if not authenticated
     */
    public static function requireAuth()
    {
        if (!self::getCurrentUser()) {
            self::unauthorized('Authentication required');
        }
        return self::$current_user;
    }
    
    /**
     * Optional authentication - set current user if token is valid
     */
    public static function optionalAuth()
    {
        if (!self::$current_user) {
            $token = self::getSessionToken();
            if ($token) {
                self::$current_user = self::validateToken($token);
            }
        }
        return self::$current_user;
    }
    
    /**
     * Get current user (if authenticated)
     */
    public static function getCurrentUser()
    {
        return self::$current_user;
    }
    
    /**
     * Get current user ID (if authenticated)
     */
    public static function getCurrentUserId()
    {
        return self::$current_user ? self::$current_user['user_id'] : null;
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
        $headers = self::getAllHeaders();
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
     * Get all headers - compatible with CLI and web environments
     */
    private static function getAllHeaders()
    {
        // If running in web environment, use getallheaders()
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        // If running in CLI or getallheaders() not available, manually parse headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
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
    
    /**
     * Validate session token and return user data
     */
    private static function validateToken($token)
    {
        if (!$token) {
            return null;
        }
        
        try {
            $stmt = Database::query(
                "SELECT u.*, s.session_id, s.expires_at 
                 FROM users u 
                 INNER JOIN sessions s ON u.user_id = s.user_id 
                 WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1",
                [$token]
            );
            
            $user = $stmt->fetch();
            if ($user) {
                // Update last activity
                Database::query(
                    "UPDATE sessions SET last_activity_at = NOW() WHERE session_id = ?",
                    [$user['session_id']]
                );
                
                return $user;
            }
        } catch (\Exception $e) {
            error_log("Auth validation error: " . $e->getMessage());
        }
        
        return null;
    }
}