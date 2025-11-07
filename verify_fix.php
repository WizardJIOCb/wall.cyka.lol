<?php
/**
 * Final test script to verify that the user registration issue is fixed
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Utils\Database;

echo "=== Wall Social Platform - Registration Fix Verification ===\n\n";

try {
    // Test 1: Database connection
    echo "1. Testing database connection...\n";
    $pdo = Database::getConnection();
    echo "   ✓ Database connection successful\n";
    
    // Test 2: Check if name column exists
    echo "2. Checking if name column exists...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'name'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "   ✓ Name column exists: {$column['Type']} " . 
             ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') .
             (isset($column['Default']) ? " DEFAULT '{$column['Default']}'" : '') . "\n";
    } else {
        echo "   ✗ Name column does not exist!\n";
        exit(1);
    }
    
    // Test 3: Test User::create method
    echo "3. Testing User::create method...\n";
    
    // Generate a unique username to avoid conflicts
    $testUsername = 'test_user_' . time();
    $testEmail = 'test_' . time() . '@example.com';
    
    $userData = [
        'username' => $testUsername,
        'email' => $testEmail,
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
        'display_name' => 'Test User',
        'name' => 'Test User',
        'theme_preference' => 'light',
        'email_verified' => 0
    ];
    
    $user = User::create($userData);
    
    if ($user) {
        echo "   ✓ User::create successful\n";
        echo "   Created user ID: {$user['user_id']}\n";
        
        // Clean up - delete the test user
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$user['user_id']]);
        echo "   ✓ Test user cleaned up\n";
    } else {
        echo "   ✗ User::create failed\n";
        exit(1);
    }
    
    echo "\n=== All tests passed! The registration issue should now be fixed. ===\n";
    echo "You can now test the registration endpoint at https://wall.cyka.lol/api/v1/auth/register\n";
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}