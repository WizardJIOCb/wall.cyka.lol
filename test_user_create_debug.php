<?php
// Test User::create with debugging

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Testing User::create with debugging ===\n";

try {
    // Include required files
    require_once __DIR__ . '/src/Models/User.php';
    require_once __DIR__ . '/src/Utils/Database.php';
    
    // Test data
    $testData = [
        'username' => 'debug_test_user',
        'email' => 'debug_test@example.com',
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
        'display_name' => 'Debug Test User',
        'name' => 'Debug Test User', // Explicitly set name
        'email_verified' => 0
    ];
    
    echo "Test data:\n";
    print_r($testData);
    
    // Manually execute the same logic as User::create but with debugging
    $config = require __DIR__ . '/config/config.php';
    
    $sql = "INSERT INTO users (
        username, email, password_hash, display_name, name,
        bricks_balance, theme_preference, email_verified, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    echo "SQL Query: $sql\n";
    
    $params = [
        $testData['username'],
        $testData['email'],
        $testData['password_hash'],
        $testData['display_name'],
        $testData['name'],
        $config['bricks']['starting_balance'],
        $testData['theme_preference'] ?? 'light',
        isset($testData['email_verified']) ? (int)$testData['email_verified'] : 0
    ];
    
    echo "Parameters: " . json_encode($params) . "\n";
    
    // Execute the query
    echo "Executing query...\n";
    $stmt = \App\Utils\Database::query($sql, $params);
    echo "Query executed successfully!\n";
    
    $userId = \App\Utils\Database::lastInsertId();
    echo "User ID: $userId\n";
    
    // Now test the createDefaultWall method
    echo "Creating default wall...\n";
    $wallSql = "INSERT INTO walls (
        user_id, wall_slug, display_name, description, 
        privacy_level, allow_comments, allow_reactions, allow_reposts, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $wallParams = [
        $userId,
        $testData['username'],
        $testData['username'] . "'s Wall",
        'Welcome to my wall!',
        'public',
        true,
        true,
        true
    ];
    
    echo "Wall SQL: $wallSql\n";
    echo "Wall Parameters: " . json_encode($wallParams) . "\n";
    
    $wallStmt = \App\Utils\Database::query($wallSql, $wallParams);
    echo "Wall created successfully!\n";
    
    // Find the user
    $findSql = "SELECT * FROM users WHERE user_id = ? AND is_active = TRUE";
    $user = \App\Utils\Database::fetchOne($findSql, [$userId]);
    
    echo "User found:\n";
    print_r($user);
    
    // Clean up
    $conn = \App\Utils\Database::getConnection();
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$testData['username']]);
    
    $stmt2 = $conn->prepare("DELETE FROM walls WHERE user_id = ?");
    $stmt2->execute([$userId]);
    
    echo "Cleaned up test user and wall\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Try to clean up even if there was an error
    try {
        $conn = \App\Utils\Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$testData['username']]);
        
        echo "Cleaned up test user\n";
    } catch (Exception $cleanupError) {
        echo "Cleanup error: " . $cleanupError->getMessage() . "\n";
    }
}
?>