<?php
/**
 * Wall Social Platform - AI Generation Queue Worker
 * 
 * Background daemon that processes AI generation jobs from Redis queue.
 * Communicates with Ollama API to generate HTML/CSS/JavaScript applications.
 * 
 * Usage: php ai_generation_worker.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);

echo "===========================================\n";
echo "Wall Social Platform - AI Generation Worker\n";
echo "===========================================\n\n";

// Load configuration
require_once __DIR__ . '/../config/database.php';

// Worker configuration
$workerConfig = [
    'ollama_host' => getenv('OLLAMA_HOST') ?: 'ollama',
    'ollama_port' => getenv('OLLAMA_PORT') ?: 11434,
    'ollama_model' => getenv('OLLAMA_MODEL') ?: 'deepseek-coder:6.7b',
    'redis_host' => getenv('REDIS_HOST') ?: 'redis',
    'redis_port' => getenv('REDIS_PORT') ?: 6379,
    'queue_name' => 'ai_generation_queue',
    'bricks_per_token' => (int)(getenv('BRICKS_PER_TOKEN') ?: 100),
    'max_retries' => 3,
    'poll_interval' => 1, // seconds
];

echo "Configuration:\n";
echo "  Ollama: {$workerConfig['ollama_host']}:{$workerConfig['ollama_port']}\n";
echo "  Model: {$workerConfig['ollama_model']}\n";
echo "  Redis: {$workerConfig['redis_host']}:{$workerConfig['redis_port']}\n";
echo "  Queue: {$workerConfig['queue_name']}\n";
echo "\n";

/**
 * Connect to services
 */
function initializeWorker($config) {
    echo "Initializing worker...\n";
    
    // Connect to Redis
    try {
        $redis = RedisConnection::getConnection();
        echo "✓ Redis connected\n";
    } catch (Exception $e) {
        echo "✗ Redis connection failed: {$e->getMessage()}\n";
        exit(1);
    }
    
    // Connect to Database
    try {
        $db = Database::getConnection();
        echo "✓ Database connected\n";
    } catch (Exception $e) {
        echo "✗ Database connection failed: {$e->getMessage()}\n";
        exit(1);
    }
    
    // Check Ollama availability
    $ollamaUrl = "http://{$config['ollama_host']}:{$config['ollama_port']}/api/tags";
    $ch = curl_init($ollamaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✓ Ollama API accessible\n";
        
        // Check if model is available
        $models = json_decode($response, true);
        $modelFound = false;
        if (isset($models['models'])) {
            foreach ($models['models'] as $model) {
                if (strpos($model['name'], $config['ollama_model']) !== false) {
                    $modelFound = true;
                    break;
                }
            }
        }
        
        if ($modelFound) {
            echo "✓ Model '{$config['ollama_model']}' available\n";
        } else {
            echo "⚠ Model '{$config['ollama_model']}' not found\n";
            echo "  Run: docker exec -it wall_ollama ollama pull {$config['ollama_model']}\n";
        }
    } else {
        echo "⚠ Ollama API not responding (will retry when processing jobs)\n";
    }
    
    echo "\n";
    
    return ['redis' => $redis, 'db' => $db];
}

/**
 * Process a single generation job
 */
function processJob($jobId, $config, $connections) {
    echo "[" . date('Y-m-d H:i:s') . "] Processing job: {$jobId}\n";
    
    // TODO: Implement actual job processing
    // Steps:
    // 1. Fetch job details from database
    // 2. Update status to 'processing'
    // 3. Send prompt to Ollama API
    // 4. Stream response and update progress
    // 5. Save generated code
    // 6. Update job status to 'completed' or 'failed'
    // 7. Update user bricks balance
    // 8. Create transaction record
    // 9. Publish SSE event for real-time updates
    
    echo "  Status: Job processing not yet implemented\n";
    echo "  This is a placeholder worker for Phase 1\n\n";
    
    return false;
}

/**
 * Main worker loop
 */
function runWorker($config) {
    $connections = initializeWorker($config);
    $redis = $connections['redis'];
    $db = $connections['db'];
    
    echo "Worker started. Waiting for jobs...\n";
    echo "Press Ctrl+C to stop.\n\n";
    
    $jobsProcessed = 0;
    $lastCheck = time();
    
    while (true) {
        try {
            // Check for jobs in queue (blocking pop with timeout)
            $job = $redis->blPop([$config['queue_name']], $config['poll_interval']);
            
            if ($job) {
                $jobId = $job[1]; // blPop returns [queue_name, value]
                $jobsProcessed++;
                
                $success = processJob($jobId, $config, $connections);
                
                if ($success) {
                    echo "✓ Job {$jobId} completed successfully\n\n";
                } else {
                    echo "✗ Job {$jobId} failed\n\n";
                }
            }
            
            // Periodic status report (every 60 seconds)
            if (time() - $lastCheck >= 60) {
                echo "[" . date('Y-m-d H:i:s') . "] Status: " .
                     "Worker running | Jobs processed: {$jobsProcessed}\n";
                $lastCheck = time();
            }
            
            // Sleep briefly to prevent CPU spinning
            usleep(100000); // 0.1 seconds
            
        } catch (Exception $e) {
            echo "Error: {$e->getMessage()}\n";
            echo "Retrying in 5 seconds...\n\n";
            sleep(5);
        }
    }
}

// Signal handling for graceful shutdown
declare(ticks = 1);
pcntl_signal(SIGTERM, function() {
    echo "\nReceived SIGTERM. Shutting down gracefully...\n";
    exit(0);
});
pcntl_signal(SIGINT, function() {
    echo "\nReceived SIGINT. Shutting down gracefully...\n";
    exit(0);
});

// Start the worker
try {
    runWorker($workerConfig);
} catch (Exception $e) {
    echo "Fatal error: {$e->getMessage()}\n";
    exit(1);
}
