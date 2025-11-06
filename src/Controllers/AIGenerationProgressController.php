<?php
/**
 * AI Generation Progress Controller
 * Provides real-time progress updates for AI generation jobs via Server-Sent Events (SSE)
 */

class AIGenerationProgressController {
    
    /**
     * Stream real-time generated content via SSE
     * GET /api/ai/generation/:jobId/content
     */
    public static function streamContent($jobId) {
        // Set headers for Server-Sent Events
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering
        
        // Prevent script timeout
        set_time_limit(0);
        
        // Verify job exists
        $job = Database::fetchOne(
            "SELECT j.*, a.user_prompt 
             FROM ai_generation_jobs j 
             JOIN ai_applications a ON j.app_id = a.app_id 
             WHERE j.job_id = ?",
            [$jobId]
        );
        
        if (!$job) {
            echo "event: error\n";
            echo "data: " . json_encode(['error' => 'Job not found']) . "\n\n";
            flush();
            return;
        }
        
        $previousContent = '';
        $previousLength = 0;
        $startPolling = time();
        $maxDuration = 600; // 10 minutes max
        
        // Stream content updates
        while (time() - $startPolling < $maxDuration) {
            // Fetch current generated content
            $current = Database::fetchOne(
                "SELECT 
                    a.status,
                    a.html_content,
                    a.generation_model,
                    j.status as job_status
                FROM ai_applications a
                JOIN ai_generation_jobs j ON a.app_id = j.app_id
                WHERE j.job_id = ?",
                [$jobId]
            );
            
            if (!$current) {
                echo "event: error\n";
                echo "data: " . json_encode(['error' => 'Content not found']) . "\n\n";
                flush();
                break;
            }
            
            $currentContent = $current['html_content'] ?? '';
            $currentLength = strlen($currentContent);
            
            // Send update if content changed
            if ($currentLength > $previousLength) {
                $data = [
                    'content' => $currentContent,
                    'length' => $currentLength,
                    'model' => $current['generation_model'],
                    'timestamp' => time(),
                ];
                
                echo "event: content\n";
                echo "data: " . json_encode($data) . "\n\n";
                flush();
                
                $previousContent = $currentContent;
                $previousLength = $currentLength;
            }
            
            // Check for completion or failure
            if ($current['job_status'] === 'completed') {
                echo "event: complete\n";
                echo "data: " . json_encode([
                    'status' => 'completed',
                    'content' => $currentContent,
                    'length' => $currentLength,
                ]) . "\n\n";
                flush();
                break;
            }
            
            if ($current['job_status'] === 'failed') {
                echo "event: error\n";
                echo "data: " . json_encode([
                    'status' => 'failed',
                    'error' => 'Generation failed',
                ]) . "\n\n";
                flush();
                break;
            }
            
            // Sleep before next poll (500ms for content updates)
            usleep(500000);
        }
        
        // Timeout reached
        if (time() - $startPolling >= $maxDuration) {
            echo "event: timeout\n";
            echo "data: " . json_encode(['error' => 'Stream timeout']) . "\n\n";
            flush();
        }
    }

    /**
     * Stream real-time generation progress via SSE
     * GET /api/ai/generation/:jobId/progress
     */
    public static function streamProgress($jobId) {
        // Set headers for Server-Sent Events
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering
        
        // Prevent script timeout
        set_time_limit(0);
        
        // Verify job exists and user has access
        $job = Database::fetchOne(
            "SELECT j.*, a.user_prompt 
             FROM ai_generation_jobs j 
             JOIN ai_applications a ON j.app_id = a.app_id 
             WHERE j.job_id = ?",
            [$jobId]
        );
        
        if (!$job) {
            echo "event: error\n";
            echo "data: " . json_encode(['error' => 'Job not found']) . "\n\n";
            flush();
            return;
        }
        
        // Optional: Verify user authentication
        // $currentUserId = AuthMiddleware::getCurrentUserId();
        // if ($job['user_id'] != $currentUserId) {
        //     echo "event: error\n";
        //     echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
        //     flush();
        //     return;
        // }
        
        $previousStatus = null;
        $previousTokens = 0;
        $startPolling = time();
        $maxDuration = 600; // 10 minutes max
        
        // Stream progress updates
        while (time() - $startPolling < $maxDuration) {
            // Fetch current job progress
            $current = Database::fetchOne(
                "SELECT 
                    status,
                    progress_percentage,
                    current_tokens,
                    tokens_per_second,
                    elapsed_time,
                    estimated_time_remaining,
                    prompt_tokens,
                    completion_tokens,
                    total_tokens,
                    partial_content_length,
                    content_generation_rate,
                    error_message,
                    last_update_at
                FROM ai_generation_jobs 
                WHERE job_id = ?",
                [$jobId]
            );
            
            if (!$current) {
                echo "event: error\n";
                echo "data: " . json_encode(['error' => 'Job not found']) . "\n\n";
                flush();
                break;
            }
            
            // If job already completed or failed before streaming started, send final event immediately
            if ($previousStatus === null && ($current['status'] === 'completed' || $current['status'] === 'failed')) {
                if ($current['status'] === 'completed') {
                    echo "event: complete\n";
                    echo "data: " . json_encode([
                        'status' => 'completed',
                        'total_tokens' => (int)$current['total_tokens'],
                        'elapsed_time' => (int)$current['elapsed_time'],
                    ]) . "\n\n";
                } else {
                    echo "event: error\n";
                    echo "data: " . json_encode([
                        'status' => 'failed',
                        'error' => $current['error_message'] ?? 'Unknown error',
                    ]) . "\n\n";
                }
                flush();
                break;
            }
            
            // Send update if status changed or tokens increased
            if ($current['status'] !== $previousStatus || 
                $current['current_tokens'] > $previousTokens ||
                $current['status'] === 'processing') {
                
                $data = [
                    'status' => $current['status'],
                    'progress' => (int)($current['progress_percentage'] ?? 0),
                    'current_tokens' => (int)($current['current_tokens'] ?? 0),
                    'tokens_per_second' => (float)($current['tokens_per_second'] ?? 0),
                    'elapsed_time' => (int)($current['elapsed_time'] ?? 0),
                    'estimated_remaining' => (int)($current['estimated_time_remaining'] ?? 0),
                    'prompt_tokens' => (int)($current['prompt_tokens'] ?? 0),
                    'completion_tokens' => (int)($current['completion_tokens'] ?? 0),
                    'total_tokens' => (int)($current['total_tokens'] ?? 0),
                    'content_length' => (int)($current['partial_content_length'] ?? 0),
                    'chars_per_second' => (float)($current['content_generation_rate'] ?? 0),
                    'timestamp' => time(),
                ];
                
                echo "event: progress\n";
                echo "data: " . json_encode($data) . "\n\n";
                flush();
                
                $previousStatus = $current['status'];
                $previousTokens = $current['current_tokens'];
            }
            
            // Check for completion or failure
            if ($current['status'] === 'completed') {
                echo "event: complete\n";
                echo "data: " . json_encode([
                    'status' => 'completed',
                    'total_tokens' => (int)$current['total_tokens'],
                    'elapsed_time' => (int)$current['elapsed_time'],
                ]) . "\n\n";
                flush();
                break;
            }
            
            if ($current['status'] === 'failed') {
                echo "event: error\n";
                echo "data: " . json_encode([
                    'status' => 'failed',
                    'error' => $current['error_message'] ?? 'Unknown error',
                ]) . "\n\n";
                flush();
                break;
            }
            
            // Sleep before next poll (200ms for smooth updates)
            usleep(200000);
        }
        
        // Timeout reached
        if (time() - $startPolling >= $maxDuration) {
            echo "event: timeout\n";
            echo "data: " . json_encode(['error' => 'Stream timeout']) . "\n\n";
            flush();
        }
    }
    
    /**
     * Get current progress snapshot (non-streaming)
     * GET /api/ai/generation/:jobId/status
     */
    public static function getStatus($jobId) {
        $job = Database::fetchOne(
            "SELECT 
                j.status,
                j.progress_percentage,
                j.current_tokens,
                j.tokens_per_second,
                j.elapsed_time,
                j.estimated_time_remaining,
                j.prompt_tokens,
                j.completion_tokens,
                j.total_tokens,
                j.error_message,
                j.created_at,
                j.started_at,
                j.completed_at,
                a.user_prompt,
                a.generation_model
            FROM ai_generation_jobs j 
            JOIN ai_applications a ON j.app_id = a.app_id 
            WHERE j.job_id = ?",
            [$jobId]
        );
        
        if (!$job) {
            http_response_code(404);
            echo json_encode(['error' => 'Job not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'job' => [
                'status' => $job['status'],
                'progress' => (int)($job['progress_percentage'] ?? 0),
                'current_tokens' => (int)($job['current_tokens'] ?? 0),
                'tokens_per_second' => (float)($job['tokens_per_second'] ?? 0),
                'elapsed_time' => (int)($job['elapsed_time'] ?? 0),
                'estimated_remaining' => (int)($job['estimated_time_remaining'] ?? 0),
                'prompt_tokens' => (int)($job['prompt_tokens'] ?? 0),
                'completion_tokens' => (int)($job['completion_tokens'] ?? 0),
                'total_tokens' => (int)($job['total_tokens'] ?? 0),
                'error_message' => $job['error_message'],
                'prompt' => $job['user_prompt'],
                'model' => $job['generation_model'],
                'created_at' => $job['created_at'],
                'started_at' => $job['started_at'],
                'completed_at' => $job['completed_at'],
            ]
        ]);
    }
}
