<?php
/**
 * Wall Social Platform - User Model
 * 
 * Handles user data operations
 */

namespace App\Models;

use App\Utils\Database;
use Exception;

class User
{
    /**
     * Find user by ID
     */
    public static function findById($userId)
    {
        $sql = "SELECT * FROM users WHERE user_id = ? AND is_active = TRUE";
        return Database::fetchOne($sql, [$userId]);
    }

    /**
     * Find user by username
     */
    public static function findByUsername($username)
    {
        $sql = "SELECT * FROM users WHERE username = ? AND is_active = TRUE";
        return Database::fetchOne($sql, [$username]);
    }

    /**
     * Search users by username (partial match)
     */
    public static function searchByUsername($query, $limit = 10)
    {
        $sql = "SELECT * FROM users WHERE username LIKE ? AND is_active = TRUE LIMIT ?";
        return Database::fetchAll($sql, ["%$query%", $limit]);
    }

    /**
     * Find user by email
     */
    public static function findByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = TRUE";
        return Database::fetchOne($sql, [$email]);
    }

    /**
     * Create new user
     */
    public static function create($data)
    {
        $config = require __DIR__ . '/../../config/config.php';
        
        $sql = "INSERT INTO users (
            username, email, password_hash, display_name, 
            bricks_balance, theme_preference, email_verified, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['display_name'] ?? $data['username'],
            $config['bricks']['starting_balance'],
            $data['theme_preference'] ?? 'light',
            isset($data['email_verified']) ? (int)$data['email_verified'] : 0
        ];

        try {
            Database::query($sql, $params);
            $userId = Database::lastInsertId();
            
            // Create default wall for user
            self::createDefaultWall($userId, $data['username']);
            
            return self::findById($userId);
        } catch (Exception $e) {
            throw new Exception('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Create default wall for new user
     */
    private static function createDefaultWall($userId, $username)
    {
        $sql = "INSERT INTO walls (
            user_id, wall_slug, display_name, description, 
            privacy_level, allow_comments, allow_reactions, allow_reposts, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $userId,
            $username,
            $username . "'s Wall",
            'Welcome to my wall!',
            'public',
            true,
            true,
            true
        ];

        Database::query($sql, $params);
    }

    /**
     * Update user's last login
     */
    public static function updateLastLogin($userId)
    {
        $sql = "UPDATE users SET 
            last_login_at = NOW(), 
            login_count = login_count + 1,
            updated_at = NOW()
        WHERE user_id = ?";
        
        Database::query($sql, [$userId]);
    }

    /**
     * Update user profile
     */
    public static function update($userId, $data)
    {
        $fields = [];
        $params = [];

        $allowedFields = ['display_name', 'bio', 'extended_bio', 'location', 'avatar_url', 'theme_preference'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return true;
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE user_id = ?";
        
        Database::query($sql, $params);
        return true;
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     */
    public static function hashPassword($password)
    {
        $config = require __DIR__ . '/../../config/config.php';
        return password_hash($password, $config['security']['password_algorithm']);
    }

    /**
     * Get user public data (without sensitive fields)
     */
    public static function getPublicData($user)
    {
        if (!$user) return null;

        return [
            'user_id' => (int)$user['user_id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'avatar_url' => $user['avatar_url'],
            'bio' => $user['bio'],
            'extended_bio' => $user['extended_bio'],
            'location' => $user['location'],
            'bricks_balance' => (int)$user['bricks_balance'],
            'theme_preference' => $user['theme_preference'],
            'posts_count' => (int)$user['posts_count'],
            'comments_count' => (int)$user['comments_count'],
            'reactions_given_count' => (int)$user['reactions_given_count'],
            'ai_generations_count' => (int)$user['ai_generations_count'],
            'created_at' => $user['created_at'],
            'last_login_at' => $user['last_login_at'],
        ];
    }

    /**
     * Create OAuth user or update existing
     */
    public static function createOrUpdateOAuthUser($provider, $providerUserId, $email, $userData)
    {
        // Check if OAuth connection exists
        $sql = "SELECT user_id FROM oauth_connections 
                WHERE provider = ? AND provider_user_id = ?";
        $existing = Database::fetchOne($sql, [$provider, $providerUserId]);

        if ($existing) {
            return self::findById($existing['user_id']);
        }

        // Check if user with this email exists
        $existingUser = self::findByEmail($email);
        
        if ($existingUser) {
            // Link OAuth to existing user
            self::linkOAuthAccount($existingUser['user_id'], $provider, $providerUserId, $userData);
            return $existingUser;
        }

        // Create new user
        $username = self::generateUniqueUsername($userData['name'] ?? $email);
        
        $newUser = self::create([
            'username' => $username,
            'email' => $email,
            'password_hash' => null, // OAuth users don't have password
            'display_name' => $userData['name'] ?? $username,
            'avatar_url' => $userData['picture'] ?? null,
            'email_verified' => 1 // OAuth emails are verified
        ]);

        // Link OAuth account
        self::linkOAuthAccount($newUser['user_id'], $provider, $providerUserId, $userData);

        return $newUser;
    }

    /**
     * Link OAuth account to user
     */
    public static function linkOAuthAccount($userId, $provider, $providerUserId, $userData)
    {
        $sql = "INSERT INTO oauth_connections (
            user_id, provider, provider_user_id, access_token, 
            refresh_token, token_expires_at, profile_data, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $userId,
            $provider,
            $providerUserId,
            $userData['access_token'] ?? null,
            $userData['refresh_token'] ?? null,
            $userData['expires_at'] ?? null,
            json_encode($userData)
        ];

        Database::query($sql, $params);
    }

    /**
     * Generate unique username
     */
    private static function generateUniqueUsername($baseName)
    {
        // Clean base name
        $username = preg_replace('/[^a-z0-9_]/i', '', strtolower($baseName));
        $username = substr($username, 0, 30);

        // Check if unique
        if (!self::findByUsername($username)) {
            return $username;
        }

        // Add random suffix
        $attempts = 0;
        while ($attempts < 10) {
            $suffix = rand(100, 9999);
            $testUsername = substr($username, 0, 30 - strlen($suffix)) . $suffix;
            
            if (!self::findByUsername($testUsername)) {
                return $testUsername;
            }
            $attempts++;
        }

        // Fallback: use timestamp
        return substr($username, 0, 20) . time();
    }

    /**
     * Generate a unique wall slug for a user
     */
    public static function generateUniqueWallSlug($username)
    {
        // Try username first
        $slug = $username;
        $attempts = 0;
        $maxAttempts = 10;
        
        do {
            $sql = "SELECT COUNT(*) as count FROM walls WHERE wall_slug = ?";
            $result = Database::fetchOne($sql, [$slug]);
            
            if ($result['count'] == 0) {
                return $slug;
            }
            
            // Append number if slug exists
            $attempts++;
            $slug = $username . '-' . $attempts;
        } while ($attempts < $maxAttempts);
        
        // Fallback: use timestamp
        return substr($username, 0, 20) . time();
    }
}