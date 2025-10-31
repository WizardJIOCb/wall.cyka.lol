<?php
/**
 * Wall Social Platform - Configuration
 * Database and system configuration
 */

return [
    'database' => [
        'host' => getenv('DB_HOST') ?: 'mysql',
        'port' => getenv('DB_PORT') ?: 3306,
        'name' => getenv('DB_NAME') ?: 'wall_platform',
        'user' => getenv('DB_USER') ?: 'wall_user',
        'password' => getenv('DB_PASSWORD') ?: 'wall_secure_password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: 'redis',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'database' => 0, // Default database for queue
        'session_database' => 1, // Separate database for sessions
        'prefix' => 'wall:',
    ],
    
    'ollama' => [
        'host' => getenv('OLLAMA_HOST') ?: 'ollama',
        'port' => getenv('OLLAMA_PORT') ?: 11434,
        'model' => getenv('OLLAMA_MODEL') ?: 'deepseek-coder',
        'timeout' => 300, // 5 minutes for generation
    ],
    
    'bricks' => [
        'tokens_per_brick' => 100,
        'daily_claim_amount' => 50,
        'starting_balance' => 100,
    ],
    
    'queue' => [
        'name' => 'ai_generation_queue',
        'retry_attempts' => 3,
        'retry_delay' => 60, // seconds
    ],
    
    'security' => [
        'session_lifetime' => 86400, // 24 hours
        'password_algorithm' => PASSWORD_ARGON2ID,
        'jwt_secret' => getenv('JWT_SECRET') ?: 'change-this-in-production',
        'jwt_lifetime' => 3600, // 1 hour
    ],
    
    'upload' => [
        'max_file_size' => 50 * 1024 * 1024, // 50MB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_video_types' => ['mp4', 'webm', 'mov'],
        'upload_path' => __DIR__ . '/../storage/uploads/',
    ],
    
    'app' => [
        'name' => 'Wall Social Platform',
        'url' => getenv('APP_URL') ?: 'http://localhost',
        'timezone' => 'UTC',
        'debug' => getenv('APP_DEBUG') === 'true',
    ],
];
