<?php
/**
 * Wall Social Platform - Redis Connection Manager
 * 
 * Handles Redis connections for caching, sessions, and queue
 */

class RedisConnection
{
    private static $connections = [];
    private static $config = null;

    /**
     * Get Redis connection (singleton pattern per database)
     */
    public static function getConnection($database = 0)
    {
        if (!isset(self::$connections[$database])) {
            self::connect($database);
        }
        return self::$connections[$database];
    }

    /**
     * Establish Redis connection
     */
    private static function connect($database)
    {
        // Check if Redis extension is available
        if (!class_exists('Redis')) {
            throw new Exception('Redis PHP extension is not installed. Please rebuild the Docker image with: docker-compose build --no-cache php');
        }

        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/config.php';
        }

        $redisConfig = self::$config['redis'];

        try {
            $redis = new Redis();
            $redis->connect($redisConfig['host'], $redisConfig['port']);
            $redis->select($database);
            $redis->setOption(Redis::OPT_PREFIX, $redisConfig['prefix']);
            
            self::$connections[$database] = $redis;
        } catch (Exception $e) {
            throw new Exception('Redis connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get Redis connection for sessions
     */
    public static function getSessionConnection()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/config.php';
        }
        return self::getConnection(self::$config['redis']['session_database']);
    }

    /**
     * Get Redis connection for queue
     */
    public static function getQueueConnection()
    {
        // Check if Redis extension is available
        if (!class_exists('Redis')) {
            throw new Exception('Redis PHP extension is not installed. Please rebuild the Docker image with: docker-compose build --no-cache php');
        }

        // Don't use the same connection with prefix - queue needs its own without prefix
        $database = 0;
        $key = 'queue_' . $database;
        
        if (!isset(self::$connections[$key])) {
            if (self::$config === null) {
                self::$config = require __DIR__ . '/../../config/config.php';
            }
            
            $redisConfig = self::$config['redis'];
            
            try {
                $redis = new Redis();
                $redis->connect($redisConfig['host'], $redisConfig['port']);
                $redis->select($database);
                // NO PREFIX for queue connection
                
                self::$connections[$key] = $redis;
            } catch (Exception $e) {
                throw new Exception('Redis queue connection failed: ' . $e->getMessage());
            }
        }
        
        return self::$connections[$key];
    }
}
