<?php
require_once __DIR__ . '/vendor/autoload.php';

// Use the debug version
spl_autoload_register(function ($class) {
    if ($class === 'App\Models\User') {
        require_once __DIR__ . '/src/Models/User.debug.php';
        return;
    }
    
    // For other classes, use the default autoloader
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
}, true, true);

use App\Models\User;

try {
    // Test via User::create with debugging
    $userData = [
        'username' => 'test_debug_user',
        'email' => 'test_debug_user@example.com',
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
        'display_name' => 'Test Debug User',
        'name' => 'Test Debug User',
        'theme_preference' => 'light',
        'email_verified' => 0
    ];

    echo "Calling User::create with data: " . print_r($userData, true) . "\n";

    $user = User::create($userData);
    echo "User::create successful: " . print_r($user, true) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}