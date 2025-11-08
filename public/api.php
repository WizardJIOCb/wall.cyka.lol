<?php
/**
 * Wall Social Platform - Application Entry Point
 *
 * Main router and request handler for the application.
 * Routes all HTTP requests to appropriate controllers.
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

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

// Autoloader (will be replaced by Composer autoloader)
spl_autoload_register(function ($class) {
    // Remove namespace prefix if present
    $class = str_replace('App\\Controllers\\', '', $class);
    $class = str_replace('App\\Models\\', '', $class);
    $class = str_replace('App\\Services\\', '', $class);
    $class = str_replace('App\\Middleware\\', '', $class);
    $class = str_replace('App\\Utils\\', '', $class);
    $class = str_replace('App\\Core\\', '', $class);
    
    $paths = [
        __DIR__ . '/../src/Controllers/',
        __DIR__ . '/../src/Models/',
        __DIR__ . '/../src/Services/',
        __DIR__ . '/../src/Middleware/',
        __DIR__ . '/../src/Utils/',
        __DIR__ . '/../src/Core/',
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

// Import controllers
use App\Controllers\AuthController;
use App\Controllers\WallController;
use App\Controllers\PostController;
use App\Controllers\QueueController;
use App\Controllers\AIController;
use App\Controllers\AIGenerationProgressController;
use App\Controllers\BricksController;
use App\Controllers\UserController;
use App\Controllers\SearchController;
use App\Controllers\FollowController;
use App\Controllers\NotificationController;
use App\Controllers\MessagingController;
use App\Controllers\SocialController;
use App\Controllers\CommentController;
use App\Controllers\SettingsController;
use App\Controllers\DiscoverController;
use App\Controllers\UploadController;

// Import utils and models
use App\Utils\Database;

// CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$requestUri = strtok($requestUri, '?');

// Parse route
$route = trim($requestUri, '/');
$routeParts = explode('/', $route);

/**
 * Simple router implementation
 *
 * Routes format: METHOD /path/to/resource
 */
function route(string $method, string $pattern, callable $handler): bool {
    global $requestMethod, $route;

    if ($requestMethod !== $method) {
        return false;
    }

    // Convert pattern to regex
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
    $pattern = '#^' . $pattern . '$#';

    if (preg_match($pattern, $route, $matches)) {
        // Extract named parameters
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        // Call handler with parameters
        call_user_func($handler, $params);
        return true;
    }

    return false;
}

/**
 * Send JSON response
 */
function jsonResponse(bool $success, $data = null, ?string $message = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');

    $response = ['success' => $success];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if ($message !== null) {
        $response['message'] = $message;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ==================== ROUTES ====================

// Home Page - Welcome Screen
route('GET', '', function() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Wall Social Platform</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            .container {
                max-width: 1200px;
                width: 100%;
            }
            h1 {
                font-size: 3rem;
                margin-bottom: 1rem;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            }
            p {
                font-size: 1.25rem;
                margin-bottom: 2rem;
                opacity: 0.9;
            }
            .features {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
                margin-top: 3rem;
            }
            .feature {
                background: rgba(255, 255, 255, 0.1);
                padding: 1.5rem;
                border-radius: 12px;
                backdrop-filter: blur(10px);
            }
            .feature h3 {
                margin-bottom: 0.5rem;
                font-size: 1.25rem;
            }
            .status {
                margin-top: 3rem;
                padding: 1rem;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 8px;
            }
            .status-ok { color: #4ade80; }
            .status-error { color: #f87171; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧱 Wall Social Platform</h1>
            <p>AI-Powered Social Network with AI-Generated Web Applications</p>

            <div class="features">
                <div class="feature">
                    <h3>🤖 AI Generation</h3>
                    <p>Create web apps using AI prompts powered by Ollama</p>
                </div>
                <div class="feature">
                    <h3>🔄 Remix & Fork</h3>
                    <p>Collaborate and iterate on existing applications</p>
                </div>
                <div class="feature">
                    <h3>🧱 Bricks Currency</h3>
                    <p>Token-based system for managing AI resources</p>
                </div>
                <div class="feature">
                    <h3>💬 Social Features</h3>
                    <p>Comments, reactions, messaging, and friendships</p>
                </div>
            </div>

            <div class="status">
                <strong>Status:</strong>
                <span class="status-ok">✓ Application Running</span><br>
                <small>Phase 5: Social Features Complete - 80 API Endpoints!</small>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
});

// API Health Check
route('GET', 'health', function() {
    try {
        // Check database connection
        $db = Database::getConnection();
        $dbStatus = 'connected';
    } catch (Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    try {
        // Check Redis connection
        $redis = RedisConnection::getConnection();
        $redisStatus = 'connected';
    } catch (Exception $e) {
        $redisStatus = 'error: ' . $e->getMessage();
    }

    jsonResponse(true, [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'services' => [
            'database' => $dbStatus,
            'redis' => $redisStatus,
        ]
    ]);
});

// API Info
route('GET', 'api/v1', function() {
    jsonResponse(true, [
        'name' => 'Wall Social Platform API',
        'version' => '1.0',
        'status' => 'active',
        'endpoints' => [
            'auth' => '/api/v1/auth/*',
            'walls' => '/api/v1/walls/*',
            'posts' => '/api/v1/posts/*',
            'ai' => '/api/v1/ai/*',
            'users' => '/api/v1/users/*',
            'messaging' => '/api/v1/conversations/*',
            'bricks' => '/api/v1/bricks/*',
        ]
    ]);
});

// ==================== AUTHENTICATION ROUTES ====================

// Register
route('POST', 'api/v1/auth/register', function() {
    AuthController::register();
});

// Login
route('POST', 'api/v1/auth/login', function() {
    AuthController::login();
});

// Logout
route('POST', 'api/v1/auth/logout', function() {
    AuthController::logout();
});

// Get Current User
route('GET', 'api/v1/auth/me', function() {
    AuthController::getCurrentUser();
});

// Verify Session
route('GET', 'api/v1/auth/verify', function() {
    AuthController::verifySession();
});

// OAuth - Google Authorization URL
route('GET', 'api/v1/auth/google/url', function() {
    AuthController::getGoogleAuthUrl();
});

// OAuth - Google Callback
route('GET', 'api/v1/auth/google/callback', function() {
    AuthController::googleCallback();
});

// OAuth - Yandex Authorization URL
route('GET', 'api/v1/auth/yandex/url', function() {
    AuthController::getYandexAuthUrl();
});

// OAuth - Yandex Callback
route('GET', 'api/v1/auth/yandex/callback', function() {
    AuthController::yandexCallback();
});

// OAuth - Telegram Auth
route('POST', 'api/v1/auth/telegram', function() {
    AuthController::telegramAuth();
});

// ==================== USER PROFILE ROUTES ====================

// Get current user profile
route('GET', 'api/v1/users/me', function() {
    UserController::getMyProfile();
});

// Update current user profile
route('PATCH', 'api/v1/users/me', function() {
    UserController::updateMyProfile();
});

// Update user bio
route('PATCH', 'api/v1/users/me/bio', function() {
    UserController::updateBio();
});

// Search users by username (must be before dynamic {userId} route)
route('GET', 'api/v1/users/search', function() {
    UserController::searchUsers();
});

// Get user profile by ID
route('GET', 'api/v1/users/{userId}', function($params) {
    UserController::getUserProfile($params);
});

// Get current user's social links
route('GET', 'api/v1/users/me/links', function() {
    UserController::getMyLinks();
});

// Get user's public social links
route('GET', 'api/v1/users/{userId}/links', function($params) {
    UserController::getUserLinks($params);
});

// Add social link
route('POST', 'api/v1/users/me/links', function() {
    UserController::addLink();
});

// Update social link
route('PATCH', 'api/v1/users/me/links/{linkId}', function($params) {
    UserController::updateLink($params);
});

// Delete social link
route('DELETE', 'api/v1/users/me/links/{linkId}', function($params) {
    UserController::deleteLink($params);
});

// Reorder social links
route('POST', 'api/v1/users/me/links/reorder', function() {
    UserController::reorderLinks();
});

// ==================== WALL ROUTES ====================

// Get current user's wall
route('GET', 'api/v1/walls/me', function() {
    WallController::getMyWall();
});

// Get wall by ID or slug
route('GET', 'api/v1/walls/{wallIdOrSlug}', function($params) {
    WallController::getWall($params);
});

// Get user's wall
route('GET', 'api/v1/users/{userId}/wall', function($params) {
    WallController::getUserWall($params);
});

// Create new wall
route('POST', 'api/v1/walls', function() {
    WallController::createWall();
});

// Update wall
route('PATCH', 'api/v1/walls/{wallId}', function($params) {
    WallController::updateWall($params);
});

// Delete wall
route('DELETE', 'api/v1/walls/{wallId}', function($params) {
    WallController::deleteWall($params);
});

// Check slug availability
route('GET', 'api/v1/walls/check-slug/{slug}', function($params) {
    WallController::checkSlug($params);
});

// ==================== POST ROUTES ====================

// Get feed posts
route('GET', 'api/v1/posts/feed', function() {
    PostController::getFeed();
});

// Feed alias (for frontend compatibility)
route('GET', 'api/v1/feed', function() {
    PostController::getFeed();
});

// Create post
route('POST', 'api/v1/posts', function() {
    PostController::createPost();
});

// Get post by ID
route('GET', 'api/v1/posts/{postId}', function($params) {
    PostController::getPost($params);
});

// Increment post view count
route('POST', 'api/v1/posts/{postId}/view', function($params) {
    PostController::incrementViewCount($params);
});

// Get wall posts
route('GET', 'api/v1/walls/{wallId}/posts', function($params) {
    PostController::getWallPosts($params);
});

// Get user posts
route('GET', 'api/v1/users/{userId}/posts', function($params) {
    PostController::getUserPosts($params);
});

// Update post
route('PATCH', 'api/v1/posts/{postId}', function($params) {
    PostController::updatePost($params);
});

// Delete post
route('DELETE', 'api/v1/posts/{postId}', function($params) {
    PostController::deletePost($params);
});

// Toggle pin post
route('POST', 'api/v1/posts/{postId}/pin', function($params) {
    PostController::togglePin($params);
});

// Repost a post
route('POST', 'api/v1/posts/{postId}/repost', function($params) {
    PostController::repostPost($params);
});

// ==================== QUEUE ROUTES ====================

// Get queue status
route('GET', 'api/v1/queue/status', function() {
    QueueController::getQueueStatus();
});

// Get active jobs
route('GET', 'api/v1/queue/jobs', function() {
    QueueController::getActiveJobs();
});

// Get job status
route('GET', 'api/v1/queue/jobs/{jobId}', function($params) {
    QueueController::getJobStatus($params);
});

// Retry job
route('POST', 'api/v1/queue/jobs/{jobId}/retry', function($params) {
    QueueController::retryJob($params);
});

// Cancel job
route('POST', 'api/v1/queue/jobs/{jobId}/cancel', function($params) {
    QueueController::cancelJob($params);
});

// Clean old jobs
route('POST', 'api/v1/queue/clean', function() {
    QueueController::cleanOldJobs();
});

// ==================== AI ROUTES ====================

// Generate AI application
route('POST', 'api/v1/ai/generate', function() {
    AIController::generateApp();
});

// Get job status
route('GET', 'api/v1/ai/jobs/{jobId}', function($params) {
    AIController::getJobStatus($params);
});

// Get AI application
route('GET', 'api/v1/ai/apps/{appId}', function($params) {
    AIController::getApplication($params);
});

// Get user's AI applications
route('GET', 'api/v1/users/{userId}/ai-apps', function($params) {
    AIController::getUserApplications($params);
});

// Get current user's AI history
route('GET', 'api/v1/ai/history', function() {
    AIController::getMyHistory();
});

// Get popular AI applications
route('GET', 'api/v1/ai/apps/popular', function() {
    AIController::getPopularApplications();
});

// Get remixable AI applications
route('GET', 'api/v1/ai/apps/remixable', function() {
    AIController::getRemixableApplications();
});

// Remix AI application
route('POST', 'api/v1/ai/apps/{appId}/remix', function($params) {
    AIController::remixApplication($params);
});

// Fork AI application
route('POST', 'api/v1/ai/apps/{appId}/fork', function($params) {
    AIController::forkApplication($params);
});

// Check Ollama service status
route('GET', 'api/v1/ai/status', function() {
    AIController::getServiceStatus();
});

// Get available models
route('GET', 'api/v1/ai/models', function() {
    AIController::getModels();
});

// Stream generation progress via SSE
route('GET', 'api/v1/ai/generation/{jobId}/progress', function($params) {
    AIGenerationProgressController::streamProgress($params['jobId']);
});

// Stream generated content via SSE
route('GET', 'api/v1/ai/generation/{jobId}/content', function($params) {
    AIGenerationProgressController::streamContent($params['jobId']);
});

// Get generation status (non-streaming)
route('GET', 'api/v1/ai/generation/{jobId}/status', function($params) {
    AIGenerationProgressController::getStatus($params['jobId']);
});

// ==================== BRICKS CURRENCY ROUTES ====================

// Get user's brick balance
route('GET', 'api/v1/bricks/balance', function() {
    BricksController::getBalance();
});

// Get bricks statistics
route('GET', 'api/v1/bricks/stats', function() {
    BricksController::getStats();
});

// Claim daily bricks
route('POST', 'api/v1/bricks/claim', function() {
    BricksController::claimDaily();
});

// Get transaction history
route('GET', 'api/v1/bricks/transactions', function() {
    BricksController::getTransactions();
});

// Transfer bricks
route('POST', 'api/v1/bricks/transfer', function() {
    BricksController::transfer();
});

// Calculate cost
route('POST', 'api/v1/bricks/calculate-cost', function() {
    BricksController::calculateCost();
});

// Admin: Add bricks
route('POST', 'api/v1/bricks/admin/add', function() {
    BricksController::adminAdd();
});

// Admin: Remove bricks
route('POST', 'api/v1/bricks/admin/remove', function() {
    BricksController::adminRemove();
});

// ==================== SOCIAL FEATURES ROUTES ====================

// Add reaction
route('POST', 'api/v1/reactions', function() {
    SocialController::addReaction();
});

// Remove reaction
route('DELETE', 'api/v1/reactions/{reactableType}/{reactableId}', function($params) {
    SocialController::removeReaction($params);
});

// Get reactions
route('GET', 'api/v1/reactions/{reactableType}/{reactableId}', function($params) {
    SocialController::getReactions($params);
});

// ==================== COMMENTS ROUTES ====================

// Get post comments
route('GET', 'api/v1/posts/{postId}/comments', function($params) {
    CommentController::getPostComments($params);
});

// Create comment on post
route('POST', 'api/v1/posts/{postId}/comments', function($params) {
    CommentController::createComment($params);
});

// Get single comment
route('GET', 'api/v1/comments/{commentId}', function($params) {
    CommentController::getComment($params);
});

// Create reply to comment
route('POST', 'api/v1/comments/{commentId}/replies', function($params) {
    CommentController::createReply($params);
});

// Update comment
route('PATCH', 'api/v1/comments/{commentId}', function($params) {
    CommentController::updateComment($params);
});

// Delete comment
route('DELETE', 'api/v1/comments/{commentId}', function($params) {
    CommentController::deleteComment($params);
});

// React to comment
route('POST', 'api/v1/comments/{commentId}/reactions', function($params) {
    CommentController::reactToComment($params);
});

// Remove reaction from comment
route('DELETE', 'api/v1/comments/{commentId}/reactions', function($params) {
    CommentController::removeCommentReaction($params);
});

// Get comment reactions
route('GET', 'api/v1/comments/{commentId}/reactions', function($params) {
    CommentController::getCommentReactions($params);
});

// Get users who reacted to comment
route('GET', 'api/v1/comments/{commentId}/reactions/users', function($params) {
    CommentController::getCommentReactionUsers($params);
});

// ==================== FOLLOW SYSTEM ROUTES ====================

// Follow user
route('POST', 'api/v1/users/{userId}/follow', function($params) {
    FollowController::followUser($params);
});

// Unfollow user
route('DELETE', 'api/v1/users/{userId}/follow', function($params) {
    FollowController::unfollowUser($params);
});

// Get user's followers
route('GET', 'api/v1/users/{userId}/followers', function($params) {
    FollowController::getFollowers($params);
});

// Get users being followed
route('GET', 'api/v1/users/{userId}/following', function($params) {
    FollowController::getFollowing($params);
});

// Get follow status
route('GET', 'api/v1/users/{userId}/follow-status', function($params) {
    FollowController::getFollowStatus($params);
});

// ==================== NOTIFICATIONS ROUTES ====================

// Get notifications
route('GET', 'api/v1/notifications', function() {
    NotificationController::getNotifications();
});

// Get unread count
route('GET', 'api/v1/notifications/unread-count', function() {
    NotificationController::getUnreadCount();
});

// Mark notification as read
route('PATCH', 'api/v1/notifications/{notificationId}/read', function($params) {
    NotificationController::markAsRead($params);
});

// Mark all notifications as read
route('POST', 'api/v1/notifications/mark-all-read', function() {
    NotificationController::markAllAsRead();
});

// ==================== DISCOVER ROUTES ====================

// Get trending walls
route('GET', 'api/v1/discover/trending-walls', function() {
    DiscoverController::getTrendingWalls();
});

// Get popular posts
route('GET', 'api/v1/discover/popular-posts', function() {
    DiscoverController::getPopularPosts();
});

// Get suggested users
route('GET', 'api/v1/discover/suggested-users', function() {
    DiscoverController::getSuggestedUsers();
});

// Global search
route('GET', 'api/v1/search', function() {
    \App\Controllers\SearchController::unifiedSearch();
});

// Trending searches
route('GET', 'api/v1/search/trending', function() {
    \App\Controllers\SearchController::getTrendingSearches();
});

// ==================== SETTINGS ROUTES ====================

// Get user settings
route('GET', 'api/v1/users/me/settings', function() {
    SettingsController::getSettings();
});

// Update user settings
route('PATCH', 'api/v1/users/me/settings', function() {
    SettingsController::updateSettings();
});

// Change password
route('POST', 'api/v1/users/me/change-password', function() {
    SettingsController::changePassword();
});

// Delete account
route('DELETE', 'api/v1/users/me/account', function() {
    SettingsController::deleteAccount();
});

// ==================== UPLOAD ROUTES ====================

// Upload avatar
route('POST', 'api/v1/upload/avatar', function() {
    UploadController::uploadAvatar();
});

// ==================== MESSAGING ROUTES ====================

// Get conversations
route('GET', 'api/v1/conversations', function() {
    MessagingController::getConversations();
});

// Create conversation
route('POST', 'api/v1/conversations', function() {
    MessagingController::createConversation();
});

// Get conversation messages
route('GET', 'api/v1/conversations/{conversationId}/messages', function($params) {
    MessagingController::getMessages($params);
});

// Send message
route('POST', 'api/v1/conversations/{conversationId}/messages', function($params) {
    MessagingController::sendMessage($params);
});

// Mark conversation as read
route('PATCH', 'api/v1/conversations/{conversationId}/read', function($params) {
    MessagingController::markAsRead($params);
});

// Delete conversation
route('DELETE', 'api/v1/conversations/{conversationId}', function($params) {
    MessagingController::deleteConversation($params);
});

// Get typing indicators
route('GET', 'api/v1/conversations/{conversationId}/typing', function($params) {
    MessagingController::getTypingIndicators($params);
});

// Set typing indicator
route('POST', 'api/v1/conversations/{conversationId}/typing', function($params) {
    MessagingController::setTypingIndicator($params);
});

// 404 Not Found
http_response_code(404);
jsonResponse(false, [
    'code' => 'NOT_FOUND',
    'message' => 'Endpoint not found',
    'path' => $requestUri,
    'method' => $requestMethod
], null, 404);
