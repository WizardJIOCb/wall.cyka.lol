<?php

/**
 * Follow System API Routes
 * 
 * Add these routes to your main api.php router file
 */

use App\Controllers\FollowController;
use App\Middleware\AuthMiddleware;

// Follow/Unfollow a user
$router->post('/users/{userId}/follow', function($params) {
    AuthMiddleware::authenticate();
    FollowController::followUser($params);
});

$router->delete('/users/{userId}/unfollow', function($params) {
    AuthMiddleware::authenticate();
    FollowController::unfollowUser($params);
});

// Get followers and following lists
$router->get('/users/{userId}/followers', function($params) {
    FollowController::getFollowers($params);
});

$router->get('/users/{userId}/following', function($params) {
    FollowController::getFollowing($params);
});

// Get follow status
$router->get('/users/{userId}/follow-status', function($params) {
    AuthMiddleware::authenticate();
    FollowController::getFollowStatus($params);
});

// Get mutual followers
$router->get('/users/{userId}/mutual-followers', function($params) {
    AuthMiddleware::authenticate();
    FollowController::getMutualFollowers($params);
});

/**
 * INTEGRATION INSTRUCTIONS:
 * 
 * Copy the route definitions above into your main public/api.php file
 * in the appropriate section (after user routes, before post routes, etc.)
 * 
 * Total new endpoints: 6
 * - POST /api/v1/users/{userId}/follow
 * - DELETE /api/v1/users/{userId}/unfollow
 * - GET /api/v1/users/{userId}/followers
 * - GET /api/v1/users/{userId}/following
 * - GET /api/v1/users/{userId}/follow-status
 * - GET /api/v1/users/{userId}/mutual-followers
 */
