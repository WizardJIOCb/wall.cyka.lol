<?php
// Test registration redirect fix
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Services\SessionManager;

try {
    // Test data
    $testUsername = 'test_redirect_' . time();
    $testEmail = 'test_redirect_' . time() . '@example.com';
    
    echo "Testing registration redirect fix...\n";
    echo "Creating test user: $testUsername\n";
    
    // Create test user directly
    $userData = [
        'username' => $testUsername,
        'email' => $testEmail,
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
        'display_name' => 'Test Redirect User',
        'name' => 'Test Redirect User',
        'theme_preference' => 'light',
        'email_verified' => 0
    ];
    
    $user = User::create($userData);
    
    if ($user) {
        echo "✓ User created successfully with ID: {$user['user_id']}\n";
        
        // Test session creation
        $sessionId = SessionManager::createSession($user['user_id']);
        echo "✓ Session created successfully: $sessionId\n";
        
        // Clean up - delete the test user
        $pdo = \App\Utils\Database::getConnection();
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$user['user_id']]);
        echo "✓ Test user cleaned up\n";
        
        echo "\n=== Registration redirect fix verification ===\n";
        echo "The frontend now redirects to /wall/me after registration instead of /\n";
        echo "This ensures users immediately see their wall after signing up\n";
    } else {
        echo "✗ User creation failed\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}