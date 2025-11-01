<?php

/**
 * Search System API Routes
 * 
 * Add these routes to your main api.php router file
 */

use App\Controllers\SearchController;
use App\Middleware\RateLimitMiddleware;

// Unified search (with rate limiting - 30 requests per minute)
$router->get('/search', function() {
    RateLimitMiddleware::check('search', 30, 60); // 30 requests per minute
    SearchController::unifiedSearch();
});

// Trending searches
$router->get('/search/trending', function() {
    SearchController::getTrendingSearches();
});

/**
 * INTEGRATION INSTRUCTIONS:
 * 
 * Copy the route definitions above into your main public/api.php file
 * 
 * Total new endpoints: 2
 * - GET /api/v1/search
 * - GET /api/v1/search/trending
 * 
 * Query Parameters for /search:
 * - q: search query (required, 2-200 chars)
 * - type: all|post|wall|user|ai-app (default: all)
 * - sort: relevance|recent|popular (default: relevance)
 * - page: page number (default: 1)
 * - limit: results per page (default: 20, max: 50)
 * 
 * Example:
 * GET /api/v1/search?q=react&type=post&sort=recent&page=1&limit=20
 */
