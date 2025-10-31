<?php
/**
 * Wall Social Platform - Session Manager
 * 
 * Manages user sessions using Redis
 */

class SessionManager
{
    private static $redis = null;
    private static $config = null;

    /**
     * Initialize session manager
     */
    private static function init()
    {
        if (self::$redis === null) {
            self::$redis = RedisConnection::getSessionConnection();
            self::$config = require __DIR__ . '/../../config/config.php';
        }
    }

    /**
     * Create new session for user
     */
    public static function createSession($userId)
    {
        self::init();

        $sessionId = self::generateSessionId();
        $sessionKey = 'session:' . $sessionId;
        
        $sessionData = [
            'user_id' => $userId,
            'created_at' => time(),
            'last_activity' => time(),
            'ip_address' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        // Store in Redis with expiration
        self::$redis->setex(
            $sessionKey,
            self::$config['security']['session_lifetime'],
            json_encode($sessionData)
        );

        // Store in database for persistence
        self::storeSessionInDb($sessionId, $userId, $sessionData);

        return $sessionId;
    }

    /**
     * Get session data
     */
    public static function getSession($sessionId)
    {
        self::init();

        if (empty($sessionId)) {
            return null;
        }

        $sessionKey = 'session:' . $sessionId;
        $sessionData = self::$redis->get($sessionKey);

        if (!$sessionData) {
            return null;
        }

        $data = json_decode($sessionData, true);
        
        // Update last activity
        $data['last_activity'] = time();
        self::$redis->setex(
            $sessionKey,
            self::$config['security']['session_lifetime'],
            json_encode($data)
        );

        return $data;
    }

    /**
     * Destroy session
     */
    public static function destroySession($sessionId)
    {
        self::init();

        $sessionKey = 'session:' . $sessionId;
        self::$redis->del($sessionKey);

        // Mark as inactive in database
        $sql = "UPDATE sessions SET is_active = FALSE, updated_at = NOW() 
                WHERE session_token = ?";
        Database::query($sql, [$sessionId]);
    }

    /**
     * Get user ID from session
     */
    public static function getUserId($sessionId)
    {
        $session = self::getSession($sessionId);
        return $session ? $session['user_id'] : null;
    }

    /**
     * Store session in database
     */
    private static function storeSessionInDb($sessionId, $userId, $sessionData)
    {
        $sql = "INSERT INTO sessions (
            session_id, user_id, session_token, ip_address, user_agent,
            last_activity_at, created_at, expires_at
        ) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))";

        $params = [
            $sessionId,
            $userId,
            $sessionId,
            $sessionData['ip_address'],
            $sessionData['user_agent']
        ];

        Database::query($sql, $params);
    }

    /**
     * Generate secure session ID
     */
    private static function generateSessionId()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get client IP address
     */
    public static function getClientIp()
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get all active sessions for user
     */
    public static function getUserSessions($userId)
    {
        $sql = "SELECT session_token, ip_address, user_agent, 
                last_activity_at, created_at 
                FROM sessions 
                WHERE user_id = ? AND is_active = TRUE 
                ORDER BY last_activity_at DESC";
        
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Clean expired sessions from database
     */
    public static function cleanExpiredSessions()
    {
        self::init();
        
        $expiryTime = time() - self::$config['security']['session_lifetime'];
        
        $sql = "UPDATE sessions SET is_active = FALSE, updated_at = NOW() 
                WHERE last_activity_at < FROM_UNIXTIME(?) AND is_active = TRUE";
        
        Database::query($sql, [$expiryTime]);
    }
}
