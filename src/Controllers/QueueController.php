<?php
/**
 * Wall Social Platform - Queue Controller
 * 
 * Handles queue monitoring and management
 */

class QueueController
{
    /**
     * Get queue statistics
     * GET /api/v1/queue/status
     */
    public static function getQueueStatus()
    {
        try {
            $stats = QueueManager::getQueueStats();
            
            self::jsonResponse(true, [
                'queue' => $stats
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'QUEUE_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Get active jobs
     * GET /api/v1/queue/jobs
     */
    public static function getActiveJobs()
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $jobs = QueueManager::getActiveJobs($limit);
            
            self::jsonResponse(true, [
                'jobs' => $jobs,
                'count' => count($jobs),
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'QUEUE_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Get job status
     * GET /api/v1/queue/jobs/{jobId}
     */
    public static function getJobStatus($params)
    {
        $jobId = $params['jobId'] ?? null;

        if (!$jobId) {
            self::jsonResponse(false, ['code' => 'INVALID_JOB_ID'], 'Job ID is required', 400);
        }

        try {
            $job = QueueManager::getJobStatus($jobId);
            
            if (!$job) {
                self::jsonResponse(false, ['code' => 'JOB_NOT_FOUND'], 'Job not found', 404);
            }

            self::jsonResponse(true, [
                'job' => $job
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'QUEUE_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Retry failed job
     * POST /api/v1/queue/jobs/{jobId}/retry
     */
    public static function retryJob($params)
    {
        $user = AuthMiddleware::requireAuth();
        $jobId = $params['jobId'] ?? null;

        // Only admins can retry jobs
        if (!self::isAdmin($user)) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'Admin access required', 403);
        }

        if (!$jobId) {
            self::jsonResponse(false, ['code' => 'INVALID_JOB_ID'], 'Job ID is required', 400);
        }

        try {
            $success = QueueManager::retryJob($jobId);
            
            if ($success) {
                self::jsonResponse(true, [
                    'message' => 'Job retried successfully'
                ]);
            } else {
                self::jsonResponse(false, ['code' => 'RETRY_FAILED'], 'Job could not be retried', 400);
            }
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'QUEUE_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Cancel job
     * POST /api/v1/queue/jobs/{jobId}/cancel
     */
    public static function cancelJob($params)
    {
        $user = AuthMiddleware::requireAuth();
        $jobId = $params['jobId'] ?? null;

        // Only admins or job owners can cancel jobs
        if (!$user) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'Authentication required', 401);
        }

        if (!$jobId) {
            self::jsonResponse(false, ['code' => 'INVALID_JOB_ID'], 'Job ID is required', 400);
        }

        try {
            // Check if user owns the job or is admin
            $job = QueueManager::getJobStatus($jobId);
            if (!$job) {
                self::jsonResponse(false, ['code' => 'JOB_NOT_FOUND'], 'Job not found', 404);
            }

            // Check ownership or admin
            $isOwner = isset($job['data']['user_id']) && $job['data']['user_id'] == $user['user_id'];
            $isAdmin = self::isAdmin($user);
            
            if (!$isOwner && !$isAdmin) {
                self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'You do not have permission to cancel this job', 403);
            }

            QueueManager::cancelJob($jobId);
            
            self::jsonResponse(true, [
                'message' => 'Job cancelled successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'QUEUE_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Clean old jobs
     * POST /api/v1/queue/clean
     */
    public static function cleanOldJobs()
    {
        $user = AuthMiddleware::requireAuth();

        // Only admins can clean jobs
        if (!self::isAdmin($user)) {
            self::jsonResponse(false, ['code' => 'ACCESS_DENIED'], 'Admin access required', 403);
        }

        try {
            $maxAge = isset($_GET['max_age']) ? (int)$_GET['max_age'] : 86400; // Default 24 hours
            QueueManager::cleanOldJobs($maxAge);
            
            self::jsonResponse(true, [
                'message' => 'Old jobs cleaned successfully'
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'QUEUE_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Check if user is admin
     */
    private static function isAdmin($user)
    {
        // For now, check if user_id is 1 (admin)
        // In production, this would check user roles/permissions
        return isset($user['user_id']) && $user['user_id'] == 1;
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
