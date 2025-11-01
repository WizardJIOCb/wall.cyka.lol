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

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

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
    
    // Connect to Redis (queue connection without prefix)
    try {
        $redis = RedisConnection::getQueueConnection();
        echo "✓ Redis queue connected\n";
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
    
    $redis = $connections['redis'];
    
    try {
        // 1. Fetch job details from database
        $job = Database::fetchOne(
            "SELECT j.*, a.user_prompt, a.app_id, a.post_id 
             FROM ai_generation_jobs j 
             JOIN ai_applications a ON j.app_id = a.app_id 
             WHERE j.job_id = ?",
            [$jobId]
        );
        
        if (!$job) {
            echo "  Error: Job not found in database\n";
            return false;
        }
        
        echo "  User ID: {$job['user_id']}\n";
        echo "  App ID: {$job['app_id']}\n";
        echo "  Model: " . ($config['ollama_model']) . "\n";
        echo "  Prompt: " . substr($job['user_prompt'], 0, 100) . "...\n";
        
        // 2. Update status to 'processing'
        Database::query(
            "UPDATE ai_generation_jobs SET status = 'processing', started_at = NOW(), updated_at = NOW() WHERE job_id = ?",
            [$jobId]
        );
        Database::query(
            "UPDATE ai_applications SET status = 'processing', updated_at = NOW() WHERE app_id = ?",
            [$job['app_id']]
        );
        
        echo "  Status updated to 'processing'\n";
        
        // 3. Prepare prompt for code generation
        $systemPrompt = "You are an expert web developer. Generate a complete, working HTML application with embedded CSS and JavaScript. " .
                       "The application should be self-contained in a single HTML file. " .
                       "Use modern HTML5, CSS3, and vanilla JavaScript. " .
                       "Make it visually appealing and fully functional. " .
                       "Return ONLY the HTML code, no explanations.";
        
        $fullPrompt = $systemPrompt . "\n\nUser Request: " . $job['user_prompt'];
        
        // 4. Send request to Ollama API
        $ollamaUrl = "http://{$config['ollama_host']}:{$config['ollama_port']}/api/generate";
        $requestData = [
            'model' => $config['ollama_model'],
            'prompt' => $fullPrompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.7,
                'top_p' => 0.9,
            ]
        ];
        
        echo "  Sending request to Ollama...\n";
        $startTime = microtime(true);
        
        $ch = curl_init($ollamaUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $generationTime = round((microtime(true) - $startTime) * 1000); // milliseconds
        
        if ($httpCode !== 200 || !$response) {
            throw new Exception("Ollama API error (HTTP {$httpCode}): " . ($curlError ?: 'Unknown error'));
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['response'])) {
            throw new Exception("Invalid Ollama API response: " . json_encode($result));
        }
        
        $generatedCode = trim($result['response']);
        $totalTokens = $result['eval_count'] ?? 0;
        $promptTokens = $result['prompt_eval_count'] ?? 0;
        
        echo "  Generation completed in {$generationTime}ms\n";
        echo "  Tokens: {$totalTokens} (prompt: {$promptTokens})\n";
        echo "  Code length: " . strlen($generatedCode) . " chars\n";
        
        // 5. Extract HTML, CSS, JS (if code contains style/script tags)
        $htmlContent = $generatedCode;
        $cssContent = null;
        $jsContent = null;
        
        // Try to extract inline styles
        if (preg_match('/<style[^>]*>(.+?)<\/style>/is', $generatedCode, $matches)) {
            $cssContent = trim($matches[1]);
        }
        
        // Try to extract inline scripts
        if (preg_match('/<script[^>]*>(.+?)<\/script>/is', $generatedCode, $matches)) {
            $jsContent = trim($matches[1]);
        }
        
        // 6. Calculate bricks cost
        $bricksCost = max(1, ceil($totalTokens / $config['bricks_per_token']));
        echo "  Bricks cost: {$bricksCost}\n";
        
        // 7. Update AI application with generated content
        Database::query(
            "UPDATE ai_applications SET 
                html_content = ?, 
                css_content = ?, 
                js_content = ?, 
                generation_model = ?,
                generation_time = ?,
                status = 'completed', 
                updated_at = NOW() 
             WHERE app_id = ?",
            [$htmlContent, $cssContent, $jsContent, $config['ollama_model'], $generationTime, $job['app_id']]
        );
        
        // 8. Update job status
        Database::query(
            "UPDATE ai_generation_jobs SET 
                status = 'completed', 
                actual_bricks_cost = ?,
                prompt_tokens = ?,
                completion_tokens = ?,
                total_tokens = ?,
                completed_at = NOW(),
                updated_at = NOW()
             WHERE job_id = ?",
            [$bricksCost, $promptTokens, $totalTokens - $promptTokens, $totalTokens, $jobId]
        );
        
        // 9. Deduct bricks from user and get new balance
        Database::query(
            "UPDATE users SET bricks_balance = bricks_balance - ? WHERE user_id = ? AND bricks_balance >= ?",
            [$bricksCost, $job['user_id'], $bricksCost]
        );
        
        // Get current balance after deduction
        $user = Database::fetchOne(
            "SELECT bricks_balance FROM users WHERE user_id = ?",
            [$job['user_id']]
        );
        $balanceAfter = $user['bricks_balance'] ?? 0;
        
        // 10. Create bricks transaction record
        Database::query(
            "INSERT INTO bricks_transactions (user_id, amount, transaction_type, source, balance_after, description, job_id, created_at) 
             VALUES (?, ?, 'spent', 'ai_generation', ?, ?, ?, NOW())",
            [$job['user_id'], -$bricksCost, $balanceAfter, "AI Generation - Job {$jobId}", $jobId]
        );
        
        // 11. Update post content with link to generated app
        $postContent = "AI Generated Application\n\nPrompt: {$job['user_prompt']}\n\nStatus: Completed";
        Database::query(
            "UPDATE posts SET content_text = ?, updated_at = NOW() WHERE post_id = ?",
            [$postContent, $job['post_id']]
        );
        
        echo "  Successfully saved generated content\n";
        return true;
        
    } catch (Exception $e) {
        echo "  Error: {$e->getMessage()}\n";
        
        // Update job status to failed
        try {
            Database::query(
                "UPDATE ai_generation_jobs SET 
                    status = 'failed', 
                    error_message = ?,
                    failed_at = NOW(),
                    updated_at = NOW()
                 WHERE job_id = ?",
                [$e->getMessage(), $jobId]
            );
            
            Database::query(
                "UPDATE ai_applications SET 
                    status = 'failed', 
                    error_message = ?,
                    updated_at = NOW() 
                 WHERE app_id = ?",
                [$e->getMessage(), $job['app_id']]
            );
        } catch (Exception $dbError) {
            echo "  Failed to update error status: {$dbError->getMessage()}\n";
        }
        
        return false;
    }
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
