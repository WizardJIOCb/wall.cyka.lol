<<<<<<< Local
<<<<<<< Local
<?php
/**
 * Wall Social Platform - Authentication Service
 * 
 * Handles user authentication logic
 */

namespace App\Services;

use App\Models\User;
use App\Utils\Validator;
use Exception;

class AuthService
{
    /**
     * Register new user
     */
    public static function register($data)
    {
        // Validate input
        $validator = new Validator($data);
        $isValid = $validator->validate([
            'username' => ['required', 'alpha_num', 'min:3', 'max:50', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
            'password_confirm' => ['required', 'match:password']
        ]);

        if (!$isValid) {
            throw new Exception($validator->getFirstError());
        }

        // Create user
        $userData = [
            'username' => Validator::sanitize($data['username']),
            'email' => Validator::sanitizeEmail($data['email']),
            'password_hash' => User::hashPassword($data['password']),
            'display_name' => Validator::sanitize($data['display_name'] ?? $data['username']),
            'name' => Validator::sanitize($data['name'] ?? $data['username']), // Add name field with fallback to username
            'email_verified' => 0
        ];

        $user = User::create($userData);

        // Log activity
        self::logActivity($user['user_id'], 'user_registered');

        return $user;
    }

    /**
     * Login user
     */
    public static function login($identifier, $password)
    {
        // Find user by username or email
        $user = null;
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = User::findByEmail($identifier);
        } else {
            $user = User::findByUsername($identifier);
        }

        if (!$user) {
            throw new Exception('Invalid credentials');
        }

        // Verify password
        if (!User::verifyPassword($password, $user['password_hash'])) {
            throw new Exception('Invalid credentials');
        }

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in');

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * Logout user
     */
    public static function logout($sessionId)
    {
        $userId = SessionManager::getUserId($sessionId);
        
        if ($userId) {
            // Log activity
            self::logActivity($userId, 'user_logged_out');
        }

        SessionManager::destroySession($sessionId);
        return true;
    }

    /**
     * Get current authenticated user
     */
    public static function getCurrentUser($sessionId)
    {
        if (empty($sessionId)) {
            return null;
        }

        $session = SessionManager::getSession($sessionId);
        
        if (!$session) {
            return null;
        }

        $user = User::findById($session['user_id']);
        return $user ? User::getPublicData($user) : null;
    }

    /**
     * Verify session token
     */
    public static function verifySession($sessionId)
    {
        return SessionManager::getSession($sessionId) !== null;
    }

    /**
     * OAuth Login - Google
     */
    public static function oauthGoogle($code)
    {
        $config = require __DIR__ . '/../../config/config.php';
        
        // Exchange code for token
        $tokenData = self::exchangeGoogleCode($code);
        
        // Get user info
        $userInfo = self::getGoogleUserInfo($tokenData['access_token']);
        
        // Create or update user
        $user = User::createOrUpdateOAuthUser(
            'google',
            $userInfo['sub'],
            $userInfo['email'],
            [
                'name' => $userInfo['name'],
                'picture' => $userInfo['picture'],
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => time() + ($tokenData['expires_in'] ?? 3600)
            ]
        );

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in', ['provider' => 'google']);

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * OAuth Login - Yandex
     */
    public static function oauthYandex($code)
    {
        // Exchange code for token
        $tokenData = self::exchangeYandexCode($code);
        
        // Get user info
        $userInfo = self::getYandexUserInfo($tokenData['access_token']);
        
        // Create or update user
        $user = User::createOrUpdateOAuthUser(
            'yandex',
            $userInfo['id'],
            $userInfo['default_email'],
            [
                'name' => $userInfo['display_name'],
                'picture' => $userInfo['default_avatar_id'] ? 
                    "https://avatars.yandex.net/get-yapic/{$userInfo['default_avatar_id']}/islands-200" : null,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => time() + ($tokenData['expires_in'] ?? 3600)
            ]
        );

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in', ['provider' => 'yandex']);

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * OAuth Login - Telegram
     */
    public static function oauthTelegram($telegramData)
    {
        // Verify Telegram auth data
        if (!self::verifyTelegramAuth($telegramData)) {
            throw new Exception('Invalid Telegram authentication data');
        }
        
        // Create email from Telegram ID
        $email = "telegram_{$telegramData['id']}@wall.cyka.lol";
        
        // Create or update user
        $user = User::createOrUpdateOAuthUser(
            'telegram',
            $telegramData['id'],
            $email,
            [
                'name' => trim(($telegramData['first_name'] ?? '') . ' ' . ($telegramData['last_name'] ?? '')),
                'picture' => $telegramData['photo_url'] ?? null,
                'username' => $telegramData['username'] ?? null
            ]
        );

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in', ['provider' => 'telegram']);

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * Exchange Google authorization code for token
     */
    private static function exchangeGoogleCode($code)
    {
        $clientId = getenv('GOOGLE_CLIENT_ID');
        $clientSecret = getenv('GOOGLE_CLIENT_SECRET');
        $redirectUri = getenv('APP_URL') . '/auth/google/callback';

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get Google user info
     */
    private static function getGoogleUserInfo($accessToken)
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Exchange Yandex authorization code for token
     */
    private static function exchangeYandexCode($code)
    {
        $clientId = getenv('YANDEX_CLIENT_ID');
        $clientSecret = getenv('YANDEX_CLIENT_SECRET');

        $ch = curl_init('https://oauth.yandex.ru/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get Yandex user info
     */
    private static function getYandexUserInfo($accessToken)
    {
        $ch = curl_init('https://login.yandex.ru/info?format=json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: OAuth $accessToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Verify Telegram authentication data
     */
    private static function verifyTelegramAuth($data)
    {
        $botToken = getenv('TELEGRAM_BOT_TOKEN');
        
        if (!$botToken) {
            return false;
        }

        $checkHash = $data['hash'] ?? '';
        unset($data['hash']);

        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }
        sort($dataCheckArr);

        $dataCheckString = implode("\n", $dataCheckArr);
        $secretKey = hash('sha256', $botToken, true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($hash, $checkHash);
    }

    /**
     * Log user activity
     */
    private static function logActivity($userId, $activityType, $metadata = [])
    {
        $sql = "INSERT INTO user_activity_log (
            user_id, activity_type, target_type, target_id, metadata, created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())";

        $params = [
            $userId,
            $activityType,
            $metadata['target_type'] ?? null,
            $metadata['target_id'] ?? null,
            json_encode($metadata)
        ];

        try {
            Database::query($sql, $params);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
=======
<?php
/**
 * Wall Social Platform - Authentication Service
 * 
 * Handles user authentication logic
 */

namespace App\Services;

use App\Models\User;
use App\Utils\Validator;
use Exception;

class AuthService
{
    /**
     * Register new user
     */
    public static function register($data)
    {
        // Validate input
        $validator = new Validator($data);
        $isValid = $validator->validate([
            'username' => ['required', 'alpha_num', 'min:3', 'max:50', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
            'password_confirm' => ['required', 'match:password']
        ]);

        if (!$isValid) {
            throw new Exception($validator->getFirstError());
        }

        // Create user
        $userData = [
            'username' => Validator::sanitize($data['username']),
            'email' => Validator::sanitizeEmail($data['email']),
            'password_hash' => User::hashPassword($data['password']),
            'display_name' => Validator::sanitize($data['display_name'] ?? $data['username']),
            'email_verified' => 0
        ];

        $user = User::create($userData);

        // Log activity
        self::logActivity($user['user_id'], 'user_registered');

        return $user;
    }

    /**
     * Login user
     */
    public static function login($identifier, $password)
    {
        // Find user by username or email
        $user = null;
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = User::findByEmail($identifier);
        } else {
            $user = User::findByUsername($identifier);
        }

        if (!$user) {
            throw new Exception('Invalid credentials');
        }

        // Verify password
        if (!User::verifyPassword($password, $user['password_hash'])) {
            throw new Exception('Invalid credentials');
        }

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in');

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * Logout user
     */
    public static function logout($sessionId)
    {
        $userId = SessionManager::getUserId($sessionId);
        
        if ($userId) {
            // Log activity
            self::logActivity($userId, 'user_logged_out');
        }

        SessionManager::destroySession($sessionId);
        return true;
    }

    /**
     * Get current authenticated user
     */
    public static function getCurrentUser($sessionId)
    {
        if (empty($sessionId)) {
            return null;
        }

        $session = SessionManager::getSession($sessionId);
        
        if (!$session) {
            return null;
        }

        $user = User::findById($session['user_id']);
        return $user ? User::getPublicData($user) : null;
    }

    /**
     * Verify session token
     */
    public static function verifySession($sessionId)
    {
        return SessionManager::getSession($sessionId) !== null;
    }

    /**
     * OAuth Login - Google
     */
    public static function oauthGoogle($code)
    {
        $config = require __DIR__ . '/../../config/config.php';
        
        // Exchange code for token
        $tokenData = self::exchangeGoogleCode($code);
        
        // Get user info
        $userInfo = self::getGoogleUserInfo($tokenData['access_token']);
        
        // Create or update user
        $user = User::createOrUpdateOAuthUser(
            'google',
            $userInfo['sub'],
            $userInfo['email'],
            [
                'name' => $userInfo['name'],
                'picture' => $userInfo['picture'],
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => time() + ($tokenData['expires_in'] ?? 3600)
            ]
        );

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in', ['provider' => 'google']);

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * OAuth Login - Yandex
     */
    public static function oauthYandex($code)
    {
        // Exchange code for token
        $tokenData = self::exchangeYandexCode($code);
        
        // Get user info
        $userInfo = self::getYandexUserInfo($tokenData['access_token']);
        
        // Create or update user
        $user = User::createOrUpdateOAuthUser(
            'yandex',
            $userInfo['id'],
            $userInfo['default_email'],
            [
                'name' => $userInfo['display_name'],
                'picture' => $userInfo['default_avatar_id'] ? 
                    "https://avatars.yandex.net/get-yapic/{$userInfo['default_avatar_id']}/islands-200" : null,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => time() + ($tokenData['expires_in'] ?? 3600)
            ]
        );

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in', ['provider' => 'yandex']);

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * OAuth Login - Telegram
     */
    public static function oauthTelegram($telegramData)
    {
        // Verify Telegram auth data
        if (!self::verifyTelegramAuth($telegramData)) {
            throw new Exception('Invalid Telegram authentication data');
        }
        
        // Create email from Telegram ID
        $email = "telegram_{$telegramData['id']}@wall.cyka.lol";
        
        // Create or update user
        $user = User::createOrUpdateOAuthUser(
            'telegram',
            $telegramData['id'],
            $email,
            [
                'name' => trim(($telegramData['first_name'] ?? '') . ' ' . ($telegramData['last_name'] ?? '')),
                'picture' => $telegramData['photo_url'] ?? null,
                'username' => $telegramData['username'] ?? null
            ]
        );

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in', ['provider' => 'telegram']);

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * Exchange Google authorization code for token
     */
    private static function exchangeGoogleCode($code)
    {
        $clientId = getenv('GOOGLE_CLIENT_ID');
        $clientSecret = getenv('GOOGLE_CLIENT_SECRET');
        $redirectUri = getenv('APP_URL') . '/auth/google/callback';

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get Google user info
     */
    private static function getGoogleUserInfo($accessToken)
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Exchange Yandex authorization code for token
     */
    private static function exchangeYandexCode($code)
    {
        $clientId = getenv('YANDEX_CLIENT_ID');
        $clientSecret = getenv('YANDEX_CLIENT_SECRET');

        $ch = curl_init('https://oauth.yandex.ru/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get Yandex user info
     */
    private static function getYandexUserInfo($accessToken)
    {
        $ch = curl_init('https://login.yandex.ru/info?format=json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: OAuth $accessToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Verify Telegram authentication data
     */
    private static function verifyTelegramAuth($data)
    {
        $botToken = getenv('TELEGRAM_BOT_TOKEN');
        
        if (!$botToken) {
            return false;
        }

        $checkHash = $data['hash'] ?? '';
        unset($data['hash']);

        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }
        sort($dataCheckArr);

        $dataCheckString = implode("\n", $dataCheckArr);
        $secretKey = hash('sha256', $botToken, true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($hash, $checkHash);
    }

    /**
     * Log user activity
     */
    private static function logActivity($userId, $activityType, $metadata = [])
    {
        $sql = "INSERT INTO user_activity_log (
            user_id, activity_type, target_type, target_id, metadata, created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())";

        $params = [
            $userId,
            $activityType,
            $metadata['target_type'] ?? null,
            $metadata['target_id'] ?? null,
            json_encode($metadata)
        ];

        try {
            Database::query($sql, $params);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
>>>>>>> Remote
=======
<?php
/**
 * Wall Social Platform - Authentication Service
 * 
 * Handles user authentication logic
 */

namespace App\Services;

use App\Models\User;
use App\Utils\Validator;
use Exception;

class AuthService
{
    /**
     * Register new user
     */
    public static function register($data)
    {
        // Validate input
        $validator = new Validator($data);
        $isValid = $validator->validate([
            'username' => ['required', 'alpha_num', 'min:3', 'max:50', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
            'password_confirm' => ['required', 'match:password']
        ]);

        if (!$isValid) {
            throw new Exception($validator->getFirstError());
        }

        // Create user
        $userData = [
            'username' => Validator::sanitize($data['username']),
            'email' => Validator::sanitizeEmail($data['email']),
            'password_hash' => User::hashPassword($data['password']),
            'display_name' => Validator::sanitize($data['display_name'] ?? $data['username']),
            'email_verified' => 0
        ];

        $user = User::create($userData);

        // Log activity
        self::logActivity($user['user_id'], 'user_registered');

        return $user;
    }

    /**
     * Login user
     */
    public static function login($identifier, $password)
    {
        // Find user by username or email
        $user = null;
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = User::findByEmail($identifier);
        } else {
            $user = User::findByUsername($identifier);
        }

        if (!$user) {
            throw new Exception('Invalid credentials');
        }

        // Verify password
        if (!User::verifyPassword($password, $user['password_hash'])) {
            throw new Exception('Invalid credentials');
        }

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in');

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * Logout user
     */
    public static function logout($sessionId)
    {
        $userId = SessionManager::getUserId($sessionId);
        
        if ($userId) {
            // Log activity
            self::logActivity($userId, 'user_logged_out');
        }

        SessionManager::destroySession($sessionId);
        return true;
    }

    /**
     * Get current authenticated user
     */
    public static function getCurrentUser($sessionId)
    {
        if (empty($sessionId)) {
            return null;
        }

        $session = SessionManager::getSession($sessionId);
        
        if (!$session) {
            return null;
        }

        $user = User::findById($session['user_id']);
        return $user ? User::getPublicData($user) : null;
    }

    /**
     * Verify session token
     */
    public static function verifySession($sessionId)
    {
        return SessionManager::getSession($sessionId) !== null;
    }

    /**
     * OAuth Login - Google
     */
    public static function oauthGoogle($code)
    {
        $config = require __DIR__ . '/../../config/config.php';
        
        // Exchange code for token
        $tokenData = self::exchangeGoogleCode($code);
        
        // Get user info
        $userInfo = self::getGoogleUserInfo($tokenData['access_token']);
        
        // Create or update user
        $user = User::createOrUpdateOAuthUser(
            'google',
            $userInfo['sub'],
            $userInfo['email'],
            [
                'name' => $userInfo['name'],
                'picture' => $userInfo['picture'],
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => time() + ($tokenData['expires_in'] ?? 3600)
            ]
        );

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in', ['provider' => 'google']);

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * OAuth Login - Yandex
     */
    public static function oauthYandex($code)
    {
        // Exchange code for token
        $tokenData = self::exchangeYandexCode($code);
        
        // Get user info
        $userInfo = self::getYandexUserInfo($tokenData['access_token']);
        
        // Create or update user
        $user = User::createOrUpdateOAuthUser(
            'yandex',
            $userInfo['id'],
            $userInfo['default_email'],
            [
                'name' => $userInfo['display_name'],
                'picture' => $userInfo['default_avatar_id'] ? 
                    "https://avatars.yandex.net/get-yapic/{$userInfo['default_avatar_id']}/islands-200" : null,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => time() + ($tokenData['expires_in'] ?? 3600)
            ]
        );

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in', ['provider' => 'yandex']);

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * OAuth Login - Telegram
     */
    public static function oauthTelegram($telegramData)
    {
        // Verify Telegram auth data
        if (!self::verifyTelegramAuth($telegramData)) {
            throw new Exception('Invalid Telegram authentication data');
        }
        
        // Create email from Telegram ID
        $email = "telegram_{$telegramData['id']}@wall.cyka.lol";
        
        // Create or update user
        $user = User::createOrUpdateOAuthUser(
            'telegram',
            $telegramData['id'],
            $email,
            [
                'name' => trim(($telegramData['first_name'] ?? '') . ' ' . ($telegramData['last_name'] ?? '')),
                'picture' => $telegramData['photo_url'] ?? null,
                'username' => $telegramData['username'] ?? null
            ]
        );

        // Update last login
        User::updateLastLogin($user['user_id']);

        // Create session
        $sessionId = SessionManager::createSession($user['user_id']);

        // Log activity
        self::logActivity($user['user_id'], 'user_logged_in', ['provider' => 'telegram']);

        return [
            'user' => User::getPublicData($user),
            'session_token' => $sessionId
        ];
    }

    /**
     * Exchange Google authorization code for token
     */
    private static function exchangeGoogleCode($code)
    {
        $clientId = getenv('GOOGLE_CLIENT_ID');
        $clientSecret = getenv('GOOGLE_CLIENT_SECRET');
        $redirectUri = getenv('APP_URL') . '/auth/google/callback';

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get Google user info
     */
    private static function getGoogleUserInfo($accessToken)
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Exchange Yandex authorization code for token
     */
    private static function exchangeYandexCode($code)
    {
        $clientId = getenv('YANDEX_CLIENT_ID');
        $clientSecret = getenv('YANDEX_CLIENT_SECRET');

        $ch = curl_init('https://oauth.yandex.ru/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get Yandex user info
     */
    private static function getYandexUserInfo($accessToken)
    {
        $ch = curl_init('https://login.yandex.ru/info?format=json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: OAuth $accessToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Verify Telegram authentication data
     */
    private static function verifyTelegramAuth($data)
    {
        $botToken = getenv('TELEGRAM_BOT_TOKEN');
        
        if (!$botToken) {
            return false;
        }

        $checkHash = $data['hash'] ?? '';
        unset($data['hash']);

        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }
        sort($dataCheckArr);

        $dataCheckString = implode("\n", $dataCheckArr);
        $secretKey = hash('sha256', $botToken, true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($hash, $checkHash);
    }

    /**
     * Log user activity
     */
    private static function logActivity($userId, $activityType, $metadata = [])
    {
        $sql = "INSERT INTO user_activity_log (
            user_id, activity_type, target_type, target_id, metadata, created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())";

        $params = [
            $userId,
            $activityType,
            $metadata['target_type'] ?? null,
            $metadata['target_id'] ?? null,
            json_encode($metadata)
        ];

        try {
            Database::query($sql, $params);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
>>>>>>> Remote
