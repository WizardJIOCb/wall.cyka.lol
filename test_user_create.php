<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;

try {
    \ = [
        'username' => 'testuser_direct',
        'email' => 'test_direct@example.com',
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
        'display_name' => 'Test User Direct',
        'name' => 'Test User Direct',
        'theme_preference' => 'light',
        'email_verified' => 0
    ];
    
    \ = User::create(\);
    echo " User created successfully: \