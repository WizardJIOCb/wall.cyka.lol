<?php
/**
 * Wall Social Platform - Database Configuration
 * 
 * Provides database connection using PDO with connection pooling
 * and error handling.
 */

namespace App\Utils;

use PDO;
use PDOException;

class Database {
    private static ?PDO $connection = null;
    
    // Database credentials from environment variables
    private static string $host = 'mysql';
    private static string $port = '3306';
    private static string $database = 'wall_social_platform';
    private static string $username = 'wall_user';
    private static string $password = 'wall_secure_password_123';
    private static string $charset = 'utf8mb4';
    
    /**
     * Get database connection (singleton pattern)
     * 
     * @return PDO Database connection
     * @throws PDOException If connection fails
     */
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            self::connect();
        }
        
        return self::$connection;
    }
    
    /**
     * Establish database connection
     * 
     * @throws PDOException If connection fails
     */
    private static function connect(): void {
        // Load from environment variables if available
        self::$host = getenv('DB_HOST') ?: self::$host;
        self::$port = getenv('DB_PORT') ?: self::$port;
        self::$database = getenv('DB_NAME') ?: self::$database;
        self::$username = getenv('DB_USER') ?: self::$username;
        self::$password = getenv('DB_PASSWORD') ?: self::$password;
        
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            self::$host,
            self::$port,
            self::$database,
            self::$charset
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true, // Connection pooling
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];
        
        try {
            self::$connection = new PDO($dsn, self::$username, self::$password, $options);
            
            // Verify charset is set correctly
            $charset = self::$connection->query("SELECT @@character_set_client, @@character_set_connection, @@character_set_results")->fetch();
            error_log("[Database] Charset verification: " . json_encode($charset));
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new PDOException('Database connection failed. Please try again later.');
        }
    }
    
    /**
     * Close database connection
     */
    public static function disconnect(): void {
        self::$connection = null;
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit(): bool {
        return self::getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback(): bool {
        return self::getConnection()->rollBack();
    }
    
    /**
     * Get last inserted ID
     */
    public static function lastInsertId(): string {
        return self::getConnection()->lastInsertId();
    }
    
    /**
     * Execute a query with parameters
     */
    public static function query($sql, $params = []) {
        $conn = self::getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch single row
     */
    public static function fetchOne($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public static function fetchAll($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
}
