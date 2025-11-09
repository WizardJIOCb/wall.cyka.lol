<?php
// Debug comments API endpoint

echo "=== Debugging Comments API Endpoint ===\n";

// First, let's check if we can get posts
echo "Fetching posts...\n";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json'
        ]
    ]
]);

$result = file_get_contents('http://localhost:8080/api/v1/posts', false, $context);

if ($result === FALSE) {
    echo "Error occurred during posts request\n";
} else {
    echo "Posts Response:\n";
    echo $result . "\n";
    
    // Try to decode and get a post ID
    $postsData = json_decode($result, true);
    if ($postsData && isset($postsData['data']['posts']) && !empty($postsData['data']['posts'])) {
        $postId = $postsData['data']['posts'][0]['post_id'];
        echo "Using post ID: $postId\n";
        
        // Now test the comments endpoint
        echo "Fetching comments for post $postId...\n";
        $commentsContext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Content-Type: application/json'
                ]
            ]
        ]);
        
        $commentsResult = file_get_contents("http://localhost:8080/api/v1/posts/$postId/comments", false, $commentsContext);
        
        if ($commentsResult === FALSE) {
            echo "Error occurred during comments request\n";
        } else {
            echo "Comments Response:\n";
            echo $commentsResult . "\n";
        }
    } else {
        echo "No posts found or invalid response format\n";
    }
}
?>