<?php
/**
 * Wall Social Platform - Redis Queue Manager
 * 
 * Handles Redis-based job queue for AI generation
 */

namespace App\Services;

use App\Utils\RedisConnection;

class QueueManager
{
    private static $redis = null;
    private static $config = null;

    /**
     * Initialize queue manager
     */
    private static function init()
    {
        if (self::$redis === null) {
            self::$redis = RedisConnection::getQueueConnection();
            self::$config = require __DIR__ . '/../../config/config.php';
        }
    }

    /**
     * Add job to queue
     */
    public static function addJob($jobData)
    {
        self::init();

        $jobId = self::generateJobId();
        $queueName = self::$config['queue']['name'];

        $job = [
            'job_id' => $jobId,
            'status' => 'queued',
            'priority' => $jobData['priority'] ?? 'normal',
            'created_at' => time(),
            'data' => $jobData,
            'attempts' => 0,
            'max_attempts' => self::$config['queue']['retry_attempts'] ?? 3
        ];

        // Add to queue
        self::$redis->lpush($queueName, json_encode($job));

        // Store job details in Redis hash
        self::$redis->hset("job:$jobId", 'data', json_encode($job));
        self::$redis->expire("job:$jobId", 86400); // Expire in 24 hours

        // Add to active jobs set
        self::$redis->sadd('active_jobs', $jobId);

        return $jobId;
    }

    /**
     * Get next job from queue
     */
    public static function getNextJob()
    {
        self::init();

        $queueName = self::$config['queue']['name'];
        $jobJson = self::$redis->brpop([$queueName], 1); // Wait 1 second

        if (!$jobJson) {
            return null;
        }

        $job = json_decode($jobJson[1], true);

        // Update job status
        $job['status'] = 'processing';
        $job['started_at'] = time();
        
        self::$redis->hset('job:' . $job['job_id'], 'data', json_encode($job));

        return $job;
    }

    /**
     * Update job status
     */
    public static function updateJobStatus($jobId, $status, $additionalData = [])
    {
        self::init();

        $jobData = self::$redis->hget("job:$jobId", 'data');
        if (!$jobData) {
            return false;
        }

        $job = json_decode($jobData, true);
        $job['status'] = $status;
        $job['updated_at'] = time();

        if ($status === 'completed') {
            $job['completed_at'] = time();
            // Remove from active jobs
            self::$redis->srem('active_jobs', $jobId);
        } elseif ($status === 'failed') {
            $job['failed_at'] = time();
            $job['error_message'] = $additionalData['error'] ?? '';
            // Remove from active jobs
            self::$redis->srem('active_jobs', $jobId);
        }

        // Merge additional data
        if (!empty($additionalData)) {
            $job['data'] = array_merge($job['data'] ?? [], $additionalData);
        }

        self::$redis->hset("job:$jobId", 'data', json_encode($job));
        return true;
    }

    /**
     * Get job status
     */
    public static function getJobStatus($jobId)
    {
        self::init();

        $jobData = self::$redis->hget("job:$jobId", 'data');
        if (!$jobData) {
            return null;
        }

        return json_decode($jobData, true);
    }

    /**
     * Retry failed job
     */
    public static function retryJob($jobId)
    {
        self::init();

        $jobData = self::$redis->hget("job:$jobId", 'data');
        if (!$jobData) {
            return false;
        }

        $job = json_decode($jobData, true);
        
        // Check if can retry
        if ($job['attempts'] >= $job['max_attempts']) {
            return false;
        }

        // Update job
        $job['attempts']++;
        $job['status'] = 'queued';
        $job['retried_at'] = time();
        unset($job['error_message']);

        // Add back to queue
        $queueName = self::$config['queue']['name'];
        self::$redis->lpush($queueName, json_encode($job));
        self::$redis->hset("job:$jobId", 'data', json_encode($job));
        self::$redis->sadd('active_jobs', $jobId);

        return true;
    }

    /**
     * Get queue statistics
     */
    public static function getQueueStats()
    {
        self::init();

        $queueName = self::$config['queue']['name'];
        $queueLength = self::$redis->llen($queueName);
        $activeJobs = self::$redis->scard('active_jobs');

        // Get processing jobs
        $processingJobs = 0;
        $activeJobIds = self::$redis->smembers('active_jobs');
        foreach ($activeJobIds as $jobId) {
            $jobData = self::$redis->hget("job:$jobId", 'data');
            if ($jobData) {
                $job = json_decode($jobData, true);
                if ($job['status'] === 'processing') {
                    $processingJobs++;
                }
            }
        }

        return [
            'queue_length' => $queueLength,
            'active_jobs' => $activeJobs,
            'processing_jobs' => $processingJobs,
            'timestamp' => time()
        ];
    }

    /**
     * Get active jobs
     */
    public static function getActiveJobs($limit = 50)
    {
        self::init();

        $activeJobIds = self::$redis->smembers('active_jobs');
        $jobs = [];

        $count = 0;
        foreach ($activeJobIds as $jobId) {
            if ($count >= $limit) break;
            
            $jobData = self::$redis->hget("job:$jobId", 'data');
            if ($jobData) {
                $jobs[] = json_decode($jobData, true);
                $count++;
            }
        }

        // Sort by priority and creation time
        usort($jobs, function($a, $b) {
            $priorityOrder = ['high' => 0, 'normal' => 1, 'low' => 2];
            $aPriority = $priorityOrder[$a['priority'] ?? 'normal'];
            $bPriority = $priorityOrder[$b['priority'] ?? 'normal'];
            
            if ($aPriority !== $bPriority) {
                return $aPriority - $bPriority;
            }
            
            return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0);
        });

        return array_slice($jobs, 0, $limit);
    }

    /**
     * Cancel job
     */
    public static function cancelJob($jobId)
    {
        self::init();

        // Remove from active jobs
        self::$redis->srem('active_jobs', $jobId);

        // Update job status
        $jobData = self::$redis->hget("job:$jobId", 'data');
        if ($jobData) {
            $job = json_decode($jobData, true);
            $job['status'] = 'cancelled';
            $job['cancelled_at'] = time();
            self::$redis->hset("job:$jobId", 'data', json_encode($job));
        }

        return true;
    }

    /**
     * Generate unique job ID
     */
    private static function generateJobId()
    {
        return 'job_' . bin2hex(random_bytes(16));
    }

    /**
     * Clean old jobs
     */
    public static function cleanOldJobs($maxAge = 86400)
    {
        self::init();

        $cutoffTime = time() - $maxAge;
        $activeJobIds = self::$redis->smembers('active_jobs');
        
        foreach ($activeJobIds as $jobId) {
            $jobData = self::$redis->hget("job:$jobId", 'data');
            if ($jobData) {
                $job = json_decode($jobData, true);
                $createdAt = $job['created_at'] ?? 0;
                
                if ($createdAt < $cutoffTime) {
                    self::$redis->srem('active_jobs', $jobId);
                    self::$redis->del("job:$jobId");
                }
            }
        }
    }
}
