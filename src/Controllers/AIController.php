<?php
/**
 * Wall Social Platform - AI Controller
 * 
 * Handles AI generation requests and management
 */

class AIController
{
    /**
     * Generate AI application
     * POST /api/v1/ai/generate
     */
    public static function generateApp()
    {
        $user = AuthMiddleware::requireAuth();
        $data = self::getRequestData();

        if (empty($data['prompt'])) {
            self::jsonResponse(false, ['code' => 'INVALID_INPUT'], 'Prompt is required', 400);
        }

        try {
            // Get user's default wall
            $userWall = Wall::getUserDefaultWall($user['user_id']);
            if (!$userWall) {
                throw new Exception('User default wall not found');
            }

            // Create a post first (required for ai_applications.post_id foreign key)
            $postData = [
                'wall_id' => $userWall['wall_id'],
                'author_id' => $user['user_id'],
                'content_text' => 'AI Generation: ' . substr($data['prompt'], 0, 100) . '...',
                'post_type' => 'ai_app'
            ];
            $post = Post::create($postData);

            // Generate job ID
            $jobId = 'job_' . bin2hex(random_bytes(16));

            // Create AI application record
            $appData = [
                'post_id' => $post['post_id'],
                'job_id' => $jobId,
                'user_prompt' => $data['prompt'],
                'status' => 'queued',
                'queue_position' => 0
            ];
            $app = AIApplication::create($appData);

            // Convert priority string to integer (0=normal, 1=high, -1=low)
            $priorityMap = ['low' => -1, 'normal' => 0, 'high' => 1];
            $priority = $priorityMap[$data['priority'] ?? 'normal'] ?? 0;

            // Create job record in database
            Database::query(
                "INSERT INTO ai_generation_jobs (job_id, app_id, user_id, status, priority, created_at) 
                 VALUES (?, ?, ?, 'queued', ?, NOW())",
                [$jobId, $app['app_id'], $user['user_id'], $priority]
            );

            // Add job ID to Redis queue
            try {
                $redis = RedisConnection::getQueueConnection();
                if (!$redis) {
                    throw new Exception('Failed to get Redis queue connection');
                }
                
                $queueLenBefore = $redis->llen('ai_generation_queue');
                error_log("[DEBUG] Queue length BEFORE push: {$queueLenBefore}");
                
                $result = $redis->lpush('ai_generation_queue', $jobId);
                error_log("[DEBUG] LPUSH returned: {$result}");
                
                if ($result === false) {
                    throw new Exception('Redis LPUSH failed');
                }
                
                // Verify it was added
                $queueLenAfter = $redis->llen('ai_generation_queue');
                error_log("[DEBUG] Queue length AFTER push: {$queueLenAfter}");
                error_log("[DEBUG] Job {$jobId} added to queue successfully");
                
            } catch (Exception $redisEx) {
                // Log error but don't fail the request - job is in DB
                error_log("[ERROR] Failed to add job to Redis queue: " . $redisEx->getMessage());
                error_log("[ERROR] Stack trace: " . $redisEx->getTraceAsString());
                // Still return success since job is queued in database
            }

            self::jsonResponse(true, [
                'job' => [
                    'job_id' => $jobId,
                    'status' => 'queued',
                    'app_id' => $app['app_id'],
                    'post_id' => $post['post_id']
                ],
                'message' => 'AI generation queued successfully'
            ], 202);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'GENERATION_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Get job status
     * GET /api/v1/ai/jobs/{jobId}
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
                // Check if it's an AI application
                $app = AIApplication::findByJobId($jobId);
                if ($app) {
                    self::jsonResponse(true, [
                        'job' => [
                            'job_id' => $jobId,
                            'status' => $app['status'],
                            'app_id' => $app['app_id'],
                            'result' => $app['status'] === 'completed' ? [
                                'html_preview' => substr($app['html_content'] ?? '', 0, 500) . '...',
                                'preview_image_url' => $app['preview_image_url']
                            ] : null
                        ]
                    ]);
                    return;
                }
                
                self::jsonResponse(false, ['code' => 'JOB_NOT_FOUND'], 'Job not found', 404);
                return;
            }

            self::jsonResponse(true, [
                'job' => $job
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'STATUS_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Get AI application
     * GET /api/v1/ai/apps/{appId}
     */
    public static function getApplication($params)
    {
        $appId = $params['appId'] ?? null;

        if (!$appId) {
            self::jsonResponse(false, ['code' => 'INVALID_APP_ID'], 'Application ID is required', 400);
        }

        $app = AIApplication::findById($appId);

        if (!$app) {
            self::jsonResponse(false, ['code' => 'APP_NOT_FOUND'], 'Application not found', 404);
        }

        // Check if completed
        if ($app['status'] !== 'completed') {
            self::jsonResponse(false, ['code' => 'APP_NOT_READY'], 'Application not yet completed', 400);
        }

        self::jsonResponse(true, [
            'app' => AIApplication::getFullData($app)
        ]);
    }

    /**
     * Get user's AI applications
     * GET /api/v1/users/{userId}/ai-apps
     */
    public static function getUserApplications($params)
    {
        $userId = $params['userId'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        if (!$userId) {
            self::jsonResponse(false, ['code' => 'INVALID_USER_ID'], 'User ID is required', 400);
        }

        $apps = AIApplication::getUserApplications($userId, $limit, $offset);
        
        $publicApps = array_map(function($app) {
            return AIApplication::getPublicData($app);
        }, $apps);

        self::jsonResponse(true, [
            'apps' => $publicApps,
            'count' => count($publicApps),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Get popular AI applications
     * GET /api/v1/ai/apps/popular
     */
    public static function getPopularApplications()
    {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

        $apps = AIApplication::getPopularApplications($limit);
        
        $publicApps = array_map(function($app) {
            return AIApplication::getPublicData($app);
        }, $apps);

        self::jsonResponse(true, [
            'apps' => $publicApps,
            'count' => count($publicApps),
            'limit' => $limit
        ]);
    }

    /**
     * Get remixable AI applications
     * GET /api/v1/ai/apps/remixable
     */
    public static function getRemixableApplications()
    {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

        $apps = AIApplication::getRemixableApplications($limit);
        
        $publicApps = array_map(function($app) {
            return AIApplication::getPublicData($app);
        }, $apps);

        self::jsonResponse(true, [
            'apps' => $publicApps,
            'count' => count($publicApps),
            'limit' => $limit
        ]);
    }

    /**
     * Remix AI application
     * POST /api/v1/ai/apps/{appId}/remix
     */
    public static function remixApplication($params)
    {
        $user = AuthMiddleware::requireAuth();
        $appId = $params['appId'] ?? null;
        $data = self::getRequestData();

        if (!$appId) {
            self::jsonResponse(false, ['code' => 'INVALID_APP_ID'], 'Application ID is required', 400);
        }

        $originalApp = AIApplication::findById($appId);

        if (!$originalApp) {
            self::jsonResponse(false, ['code' => 'APP_NOT_FOUND'], 'Application not found', 404);
        }

        if ($originalApp['status'] !== 'completed') {
            self::jsonResponse(false, ['code' => 'APP_NOT_READY'], 'Application not yet completed', 400);
        }

        if (!$originalApp['allow_remixing']) {
            self::jsonResponse(false, ['code' => 'REMIX_NOT_ALLOWED'], 'Remixing not allowed for this application', 400);
        }

        try {
            // Get user's default wall
            $userWall = Wall::getUserDefaultWall($user['user_id']);
            if (!$userWall) {
                throw new Exception('User default wall not found');
            }

            // Create a post first
            $postData = [
                'wall_id' => $userWall['wall_id'],
                'author_id' => $user['user_id'],
                'content_text' => 'AI Remix: ' . substr($data['prompt'] ?? $originalApp['user_prompt'], 0, 100) . '...',
                'post_type' => 'ai_app'
            ];
            $post = Post::create($postData);

            // Add remix job to queue
            $jobData = [
                'user_id' => $user['user_id'],
                'prompt' => Validator::sanitize($data['prompt'] ?? $originalApp['user_prompt']),
                'model' => $data['model'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'options' => $data['options'] ?? [],
                'original_app_id' => $appId,
                'remix_type' => 'remix'
            ];

            $jobId = QueueManager::addJob($jobData);

            // Create new AI application record for remix
            $appData = [
                'post_id' => $post['post_id'],
                'job_id' => $jobId,
                'user_prompt' => $data['prompt'] ?? $originalApp['user_prompt'],
                'status' => 'queued',
                'queue_position' => 0,
                'original_app_id' => $appId,
                'remix_type' => 'remix'
            ];

            $newApp = AIApplication::create($appData);

            // Increment remix count on original
            AIApplication::incrementRemixCount($appId);

            self::jsonResponse(true, [
                'job' => [
                    'job_id' => $jobId,
                    'status' => 'queued',
                    'app_id' => $newApp['app_id'],
                    'post_id' => $post['post_id']
                ],
                'message' => 'AI remix queued successfully'
            ], 202);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'REMIX_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Fork AI application
     * POST /api/v1/ai/apps/{appId}/fork
     */
    public static function forkApplication($params)
    {
        $user = AuthMiddleware::requireAuth();
        $appId = $params['appId'] ?? null;

        if (!$appId) {
            self::jsonResponse(false, ['code' => 'INVALID_APP_ID'], 'Application ID is required', 400);
        }

        $originalApp = AIApplication::findById($appId);

        if (!$originalApp) {
            self::jsonResponse(false, ['code' => 'APP_NOT_FOUND'], 'Application not found', 404);
        }

        if ($originalApp['status'] !== 'completed') {
            self::jsonResponse(false, ['code' => 'APP_NOT_READY'], 'Application not yet completed', 400);
        }

        if (!$originalApp['allow_remixing']) {
            self::jsonResponse(false, ['code' => 'FORK_NOT_ALLOWED'], 'Forking not allowed for this application', 400);
        }

        try {
            // Get user's default wall
            $userWall = Wall::getUserDefaultWall($user['user_id']);
            if (!$userWall) {
                throw new Exception('User default wall not found');
            }

            // Create a post first
            $postData = [
                'wall_id' => $userWall['wall_id'],
                'author_id' => $user['user_id'],
                'content_text' => 'AI Fork: ' . substr($originalApp['user_prompt'], 0, 100) . '...',
                'post_type' => 'ai_app'
            ];
            $post = Post::create($postData);

            // Add fork job to queue
            $jobData = [
                'user_id' => $user['user_id'],
                'prompt' => $originalApp['user_prompt'],
                'model' => null, // Use same model as original
                'priority' => 'normal',
                'options' => [],
                'original_app_id' => $appId,
                'remix_type' => 'fork',
                'original_code' => [
                    'html' => $originalApp['html_content'],
                    'css' => $originalApp['css_content'],
                    'js' => $originalApp['js_content']
                ]
            ];

            $jobId = QueueManager::addJob($jobData);

            // Create new AI application record for fork
            $appData = [
                'post_id' => $post['post_id'],
                'job_id' => $jobId,
                'user_prompt' => $originalApp['user_prompt'],
                'status' => 'queued',
                'queue_position' => 0,
                'original_app_id' => $appId,
                'remix_type' => 'fork'
            ];

            $newApp = AIApplication::create($appData);

            // Increment remix count on original
            AIApplication::incrementRemixCount($appId);

            self::jsonResponse(true, [
                'job' => [
                    'job_id' => $jobId,
                    'status' => 'queued',
                    'app_id' => $newApp['app_id'],
                    'post_id' => $post['post_id']
                ],
                'message' => 'AI fork queued successfully'
            ], 202);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'FORK_FAILED'], $e->getMessage(), 400);
        }
    }

    /**
     * Check Ollama service status
     * GET /api/v1/ai/status
     */
    public static function getServiceStatus()
    {
        try {
            $isAvailable = OllamaService::isAvailable();
            
            self::jsonResponse(true, [
                'service' => [
                    'available' => $isAvailable,
                    'timestamp' => time()
                ]
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'SERVICE_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Get available models
     * GET /api/v1/ai/models
     */
    public static function getModels()
    {
        try {
            $models = OllamaService::getModels();
            
            self::jsonResponse(true, [
                'models' => $models
            ]);
        } catch (Exception $e) {
            self::jsonResponse(false, ['code' => 'MODELS_ERROR'], $e->getMessage(), 500);
        }
    }

    /**
     * Get current user's AI generation history
     * GET /api/v1/ai/history
     */
    public static function getMyHistory()
    {
        $user = AuthMiddleware::requireAuth();
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $apps = AIApplication::getUserApplications($user['user_id'], $limit, $offset);

        self::jsonResponse(true, [
            'history' => $apps,
            'count' => count($apps)
        ]);
    }

    /**
     * Get request JSON data
     */
    private static function getRequestData()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
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
