<?php
/**
 * Test script for repost functionality - Docker version
 * This script should be run inside the Docker PHP container
 */

// Include the autoloader
require_once '/var/www/html/vendor/autoload.php';
require_once '/var/www/html/config/config.php';

use App\Models\Post;
use App\Models\Wall;

echo "=== Post Interaction Features Test ===\n\n";

try {
    // Test 1: Check if view_count column exists
    echo "Test 1: Checking if view_count column exists...\n";
    $db = Database::getConnection();
    $stmt = $db->prepare("DESCRIBE posts");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('view_count', $columns)) {
        echo "✓ view_count column exists\n";
    } else {
        echo "✗ view_count column missing\n";
    }
    
    // Test 2: Check if other counter columns exist
    echo "\nTest 2: Checking if other counter columns exist...\n";
    $requiredColumns = ['reaction_count', 'comment_count', 'share_count', 'is_pinned'];
    foreach ($requiredColumns as $column) {
        if (in_array($column, $columns)) {
            echo "✓ $column column exists\n";
        } else {
            echo "✗ $column column missing\n";
        }
    }
    
    // Test 3: Test incrementViewCount method
    echo "\nTest 3: Testing incrementViewCount method...\n";
    
    // Create a test post first
    $testPostData = [
        'wall_id' => 1,
        'author_id' => 1,
        'post_type' => 'text',
        'content_text' => 'Test post for view count',
        'content_html' => '<p>Test post for view count</p>',
        'is_repost' => false
    ];
    
    $post = Post::create($testPostData);
    echo "Created test post with ID: " . $post['post_id'] . "\n";
    
    // Get initial view count
    $initialPost = Post::findById($post['post_id']);
    $initialViewCount = $initialPost['view_count'] ?? 0;
    echo "Initial view count: $initialViewCount\n";
    
    // Increment view count
    Post::incrementViewCount($post['post_id']);
    
    // Check updated view count
    $updatedPost = Post::findById($post['post_id']);
    $updatedViewCount = $updatedPost['view_count'] ?? 0;
    echo "Updated view count: $updatedViewCount\n";
    
    if ($updatedViewCount == $initialViewCount + 1) {
        echo "✓ incrementViewCount method works correctly\n";
    } else {
        echo "✗ incrementViewCount method failed\n";
    }
    
    // Test 4: Test getPublicData includes counters
    echo "\nTest 4: Testing getPublicData includes counters...\n";
    $publicData = Post::getPublicData($updatedPost);
    
    $requiredFields = ['reaction_count', 'comment_count', 'share_count', 'view_count', 'is_pinned'];
    $allFieldsPresent = true;
    
    foreach ($requiredFields as $field) {
        if (array_key_exists($field, $publicData)) {
            echo "✓ $field field present in public data\n";
        } else {
            echo "✗ $field field missing from public data\n";
            $allFieldsPresent = false;
        }
    }
    
    if ($allFieldsPresent) {
        echo "✓ getPublicData includes all required counter fields\n";
    } else {
        echo "✗ getPublicData missing required counter fields\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "Basic functionality tests completed. For full integration testing, please use the API endpoints.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}