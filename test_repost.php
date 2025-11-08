<?php
// Test script for repost functionality

// Include necessary files directly
require_once 'src/Utils/Database.php';
require_once 'src/Models/Post.php';
require_once 'src/Models/Wall.php';

// Test data
$testPostData = [
    'wall_id' => 1,
    'author_id' => 1,
    'post_type' => 'text',
    'content_text' => 'This is a test post for repost functionality',
    'content_html' => '<p>This is a test post for repost functionality</p>',
    'is_repost' => false
];

echo "Creating test post...\n";

try {
    // Create a test post
    $post = Post::create($testPostData);
    echo "Test post created with ID: " . $post['post_id'] . "\n";
    
    // Test incrementing view count
    echo "Incrementing view count...\n";
    Post::incrementViewCount($post['post_id']);
    
    // Retrieve post to check view count
    $updatedPost = Post::findById($post['post_id']);
    echo "View count: " . ($updatedPost['view_count'] ?? 0) . "\n";
    
    echo "Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}