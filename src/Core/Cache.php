<?php

namespace App\Core;

/**
 * Simple file-based cache implementation
 */
class Cache
{
    private static $cacheDir = null;

    /**
     * Initialize cache directory
     */
    private static function init()
    {
        if (self::$cacheDir === null) {
            self::$cacheDir = sys_get_temp_dir() . '/wall_cache/';
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0777, true);
            }
        }
    }

    /**
     * Get cached value
     *
     * @param string $key Cache key
     * @return mixed|null Returns cached value or null if not found/expired
     */
    public static function get(string $key)
    {
        self::init();
        
        $filename = self::$cacheDir . md5($key);
        
        if (!file_exists($filename)) {
            return null;
        }

        $data = @file_get_contents($filename);
        if ($data === false) {
            return null;
        }

        $cached = @unserialize($data);
        if ($cached === false) {
            return null;
        }

        // Check expiration
        if (isset($cached['expires_at']) && $cached['expires_at'] < time()) {
            @unlink($filename);
            return null;
        }

        return $cached['value'] ?? null;
    }

    /**
     * Set cache value
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (default: 3600)
     * @return bool
     */
    public static function set(string $key, $value, int $ttl = 3600): bool
    {
        self::init();
        
        $filename = self::$cacheDir . md5($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];

        $serialized = serialize($data);
        
        return @file_put_contents($filename, $serialized, LOCK_EX) !== false;
    }

    /**
     * Delete cached value
     *
     * @param string $key Cache key
     * @return bool
     */
    public static function delete(string $key): bool
    {
        self::init();
        
        $filename = self::$cacheDir . md5($key);
        
        if (file_exists($filename)) {
            return @unlink($filename);
        }

        return true;
    }

    /**
     * Clear all cache
     *
     * @return bool
     */
    public static function clear(): bool
    {
        self::init();
        
        $files = glob(self::$cacheDir . '*');
        
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        return true;
    }

    /**
     * Check if key exists in cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }
}
