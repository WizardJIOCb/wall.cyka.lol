<?php
/**
 * Wall Social Platform - Authentication Controller
 * 
 * Handles authentication endpoints
 */

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\SessionManager;
use App\Models\User;
use Exception;

class AuthController
{
    /**
     * Register new user
     * POST /api/v1/auth/register
     */
    public static function register()
    {
        try {
            $data = self::getRequestData();

            $user = AuthService::register($data);

            // Create session automatically after registration
            $sessionId = SessionManager::createSession($user['user_id']);

            self::jsonResponse(true, [
                'user' => User::getPublicData($user),
                'session_token' => $sessionId,
                'message' => 'Registration successful'
            ], 201);

        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'REGISTRATION_FAILED'
            ], $e->getMessage(), 400);
        }
    }

    /**
     * Login user
     * POST /api/v1/auth/login
     */
    public static function login()
    {
        try {
            $data = self::getRequestData();

            if (empty($data['identifier']) || empty($data['password'])) {
                throw new Exception('Username/email and password are required');
            }

            $result = AuthService::login($data['identifier'], $data['password']);

            self::jsonResponse(true, [
                'user' => $result['user'],
                'session_token' => $result['session_token'],
                'message' => 'Login successful'
            ]);

        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'LOGIN_FAILED'
            ], $e->getMessage(), 401);
        }
    }

    /**
     * Logout user
     * POST /api/v1/auth/logout
     */
    public static function logout()
    {
        try {
            $sessionToken = self::getSessionToken();

            if (empty($sessionToken)) {
                throw new Exception('No session token provided');
            }

            AuthService::logout($sessionToken);

            self::jsonResponse(true, [
                'message' => 'Logout successful'
            ]);

        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'LOGOUT_FAILED'
            ], $e->getMessage(), 400);
        }
    }

    /**
     * Get current user
     * GET /api/v1/auth/me
     */
    public static function getCurrentUser()
    {
        try {
            $sessionToken = self::getSessionToken();

            if (empty($sessionToken)) {
                throw new Exception('No session token provided');
            }

            $user = AuthService::getCurrentUser($sessionToken);

            if (!$user) {
                throw new Exception('Invalid session');
            }

            self::jsonResponse(true, [
                'user' => $user
            ]);

        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'UNAUTHORIZED'
            ], $e->getMessage(), 401);
        }
    }

    /**
     * Verify session
     * GET /api/v1/auth/verify
     */
    public static function verifySession()
    {
        try {
            $sessionToken = self::getSessionToken();

            if (empty($sessionToken)) {
                throw new Exception('No session token provided');
            }

            $isValid = AuthService::verifySession($sessionToken);

            self::jsonResponse(true, [
                'valid' => $isValid
            ]);

        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'VERIFICATION_FAILED'
            ], $e->getMessage(), 400);
        }
    }

    /**
     * OAuth - Google Callback
     * GET /api/v1/auth/google/callback
     */
    public static function googleCallback()
    {
        try {
            $code = $_GET['code'] ?? null;

            if (empty($code)) {
                throw new Exception('Authorization code not provided');
            }

            $result = AuthService::oauthGoogle($code);

            self::jsonResponse(true, [
                'user' => $result['user'],
                'session_token' => $result['session_token'],
                'message' => 'Google authentication successful'
            ]);

        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'OAUTH_FAILED'
            ], $e->getMessage(), 400);
        }
    }

    /**
     * OAuth - Yandex Callback
     * GET /api/v1/auth/yandex/callback
     */
    public static function yandexCallback()
    {
        try {
            $code = $_GET['code'] ?? null;

            if (empty($code)) {
                throw new Exception('Authorization code not provided');
            }

            $result = AuthService::oauthYandex($code);

            self::jsonResponse(true, [
                'user' => $result['user'],
                'session_token' => $result['session_token'],
                'message' => 'Yandex authentication successful'
            ]);

        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'OAUTH_FAILED'
            ], $e->getMessage(), 400);
        }
    }

    /**
     * OAuth - Telegram Auth
     * POST /api/v1/auth/telegram
     */
    public static function telegramAuth()
    {
        try {
            $data = self::getRequestData();

            if (empty($data['id']) || empty($data['hash'])) {
                throw new Exception('Invalid Telegram authentication data');
            }

            $result = AuthService::oauthTelegram($data);

            self::jsonResponse(true, [
                'user' => $result['user'],
                'session_token' => $result['session_token'],
                'message' => 'Telegram authentication successful'
            ]);

        } catch (Exception $e) {
            self::jsonResponse(false, [
                'code' => 'OAUTH_FAILED'
            ], $e->getMessage(), 400);
        }
    }

    /**
     * Get OAuth authorization URL - Google
     * GET /api/v1/auth/google/url
     */
    public static function getGoogleAuthUrl()
    {
        $clientId = getenv('GOOGLE_CLIENT_ID');
        $redirectUri = getenv('APP_URL') . '/auth/google/callback';
        
        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

        self::jsonResponse(true, [
            'url' => $url
        ]);
    }

    /**
     * Get OAuth authorization URL - Yandex
     * GET /api/v1/auth/yandex/url
     */
    public static function getYandexAuthUrl()
    {
        $clientId = getenv('YANDEX_CLIENT_ID');
        
        $params = [
            'response_type' => 'code',
            'client_id' => $clientId
        ];

        $url = 'https://oauth.yandex.ru/authorize?' . http_build_query($params);

        self::jsonResponse(true, [
            'url' => $url
        ]);
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
