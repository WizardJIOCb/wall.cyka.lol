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

// Load .env variables for non-Docker deployments
(function () {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        return;
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || $trimmed[0] === '#') {
            continue;
        }
        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        // Strip optional quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
})();

// Manual class autoloader (same as api.php)
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/Controllers/',
        __DIR__ . '/../src/Models/',
        __DIR__ . '/../src/Services/',
        __DIR__ . '/../src/Middleware/',
        __DIR__ . '/../src/Utils/',
        __DIR__ . '/../config/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

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
    $maxRetries = 5;
    $retryDelay = 2;
    $dbConnected = false;
    
    for ($i = 1; $i <= $maxRetries; $i++) {
        try {
            $db = Database::getConnection();
            echo "✓ Database connected\n";
            $dbConnected = true;
            break;
        } catch (Exception $e) {
            if ($i < $maxRetries) {
                echo "⚠ Database connection attempt $i failed, retrying in {$retryDelay}s...\n";
                sleep($retryDelay);
            } else {
                echo "✗ Database connection failed after $maxRetries attempts: {$e->getMessage()}\n";
                exit(1);
            }
        }
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
            "SELECT j.*, a.user_prompt, a.app_id, a.post_id, a.generation_model 
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
        echo "  Model: " . ($job['generation_model'] ?: $config['ollama_model']) . "\n";
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
        // Use user's prompt directly without forcing HTML generation
        $fullPrompt = $job['user_prompt'];
        
        // 4. Send request to Ollama API with streaming
        $selectedModel = $job['generation_model'] ?: $config['ollama_model'];
        $ollamaUrl = "http://{$config['ollama_host']}:{$config['ollama_port']}/api/generate";
        $requestData = [
            'model' => $selectedModel,
            'prompt' => $fullPrompt,
            'stream' => true, // Enable streaming for real-time updates
            'options' => [
                'temperature' => 0.7,
                'top_p' => 0.9,
            ]
        ];
        
        echo "  Sending streaming request to Ollama...\n";
        $startTime = microtime(true);
        
        // Initialize tracking variables
        $generatedCode = '';
        $promptTokens = 0;
        $completionTokens = 0;
        $totalTokens = 0;
        $lastUpdateTime = $startTime;
        $updateInterval = 0.5; // Update database every 0.5 seconds
        $responseChunkCount = 0; // Track response chunks as proxy for progress
        
        // Setup streaming request
        $ch = curl_init($ollamaUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 minutes timeout
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (
            &$generatedCode, &$promptTokens, &$completionTokens, &$totalTokens, 
            $startTime, &$lastUpdateTime, $updateInterval, $jobId, &$responseChunkCount, $job
        ) {
            $currentTime = microtime(true);
            $elapsedMs = round(($currentTime - $startTime) * 1000);
            
            // Parse streaming JSON response
            $lines = explode("\n", trim($data));
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                $chunk = json_decode($line, true);
                if (!$chunk) continue;
                
                // Accumulate generated content
                if (isset($chunk['response'])) {
                    $generatedCode .= $chunk['response'];
                    $responseChunkCount++; // Count chunks for progress indication
                }
                
                // Track token counts from Ollama
                if (isset($chunk['prompt_eval_count'])) {
                    $promptTokens = $chunk['prompt_eval_count'];
                }
                if (isset($chunk['eval_count'])) {
                    $completionTokens = $chunk['eval_count'];
                }
                
                // Calculate tokens from content length
                // Real-time estimation: ~4 characters per token on average
                $contentLength = strlen($generatedCode);
                $estimatedTokensFromContent = round($contentLength / 4);
                
                // Use the higher of: Ollama's count or our estimate
                if ($estimatedTokensFromContent > $completionTokens) {
                    $completionTokens = $estimatedTokensFromContent;
                }
                
                $totalTokens = $promptTokens + $completionTokens;
                
                // Update database periodically (not on every chunk to avoid overhead)
                if ($currentTime - $lastUpdateTime >= $updateInterval) {
                    $tokensPerSec = $completionTokens > 0 ? round($completionTokens / ($elapsedMs / 1000), 2) : 0;
                    $contentGenerationRate = $contentLength > 0 ? round($contentLength / ($elapsedMs / 1000), 2) : 0;
                    $estimatedRemainingMs = 0;
                    
                    // Estimate time remaining based on average speed
                    // Assume completion tokens will be ~2x prompt tokens (rough estimate)
                    if ($tokensPerSec > 0 && $promptTokens > 0) {
                        $estimatedTotal = $promptTokens * 2.5;
                        $tokensRemaining = max(0, $estimatedTotal - $completionTokens);
                        $estimatedRemainingMs = round(($tokensRemaining / $tokensPerSec) * 1000);
                    }
                    
                    // Calculate progress percentage (0-90% during generation, 90-100% for post-processing)
                    $progressPercent = 0;
                    
                    if ($contentLength > 0) {
                        // Use content length to estimate progress
                        // Average web app: 500-2000 tokens = 2000-8000 chars
                        // Conservative estimate: assume 3000 chars for typical output
                        $estimatedTotalChars = 3000;
                        $progressPercent = min(90, round(($contentLength / $estimatedTotalChars) * 90));
                    } elseif ($promptTokens > 0 && $completionTokens > 0) {
                        // Fallback: use token-based calculation if available
                        $progressPercent = min(90, round(($completionTokens / ($promptTokens * 2.5)) * 90));
                    }
                    
                    // Update job progress in database
                    try {
                        Database::query(
                            "UPDATE ai_generation_jobs SET 
                                current_tokens = ?,
                                tokens_per_second = ?,
                                elapsed_time = ?,
                                estimated_time_remaining = ?,
                                progress_percentage = ?,
                                partial_content_length = ?,
                                content_generation_rate = ?,
                                last_update_at = NOW(),
                                updated_at = NOW()
                            WHERE job_id = ?",
                            [$completionTokens, $tokensPerSec, $elapsedMs, $estimatedRemainingMs, $progressPercent, $contentLength, $contentGenerationRate, $jobId]
                        );
                        
                        // CRITICAL: Also update ai_applications with partial content for real-time streaming
                        Database::query(
                            "UPDATE ai_applications SET 
                                html_content = ?,
                                updated_at = NOW()
                            WHERE app_id = ?",
                            [$generatedCode, $job['app_id']]
                        );
                    } catch (Exception $e) {
                        // Ignore update errors, continue processing
                    }
                    
                    $lastUpdateTime = $currentTime;
                    
                    echo "  Progress: {$completionTokens} tokens ({$contentLength} chars) | {$tokensPerSec} tok/s | {$progressPercent}% | " .
                         round($elapsedMs / 1000, 1) . "s elapsed\n";
                }
            }
            
            return strlen($data);
        });
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $generationTime = round((microtime(true) - $startTime) * 1000);
        
        if ($httpCode !== 200) {
            throw new Exception("Ollama API error (HTTP {$httpCode}): " . ($curlError ?: 'Unknown error'));
        }
        
        if (empty($generatedCode)) {
            throw new Exception("No content generated from Ollama");
        }
        
        $generatedCode = trim($generatedCode);
        
        echo "  Generation completed in {$generationTime}ms\n";
        echo "  Tokens: {$totalTokens} (prompt: {$promptTokens}, completion: {$completionTokens})\n";
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
        // Note: Token counts are stored in ai_generation_jobs table, not here
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
            [$htmlContent, $cssContent, $jsContent, $selectedModel, $generationTime, $job['app_id']]
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
        $postContent = "<strong>Status:</strong> <span style='color: #10b981; font-weight: 600;'>✓ Completed</span><br><strong>Bricks spent:</strong> <span style='color: #f59e0b; font-weight: 600;'>{$bricksCost} 🧱</span>";
        Database::query(
            "UPDATE posts SET content_text = ?, content_html = ?, updated_at = NOW() WHERE post_id = ?",
            [$postContent, $postContent, $job['post_id']]
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

// Signal handling for graceful shutdown (if PCNTL extension available)
if (function_exists('pcntl_signal')) {
    declare(ticks = 1);
    pcntl_signal(SIGTERM, function() {
        echo "\nReceived SIGTERM. Shutting down gracefully...\n";
        exit(0);
    });
    pcntl_signal(SIGINT, function() {
        echo "\nReceived SIGINT. Shutting down gracefully...\n";
        exit(0);
    });
    echo "Signal handlers registered (PCNTL available)\n";
} else {
    echo "PCNTL extension not available - signal handling disabled\n";
}

// Start the worker
try {
    runWorker($workerConfig);
} catch (Exception $e) {
    echo "Fatal error: {$e->getMessage()}\n";
    exit(1);
}
