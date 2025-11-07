<?php
// Test registration via PHP script

$data = [
    'username' => 'api_test_user',
    'email' => 'api_test@example.com',
    'password' => 'password123',
    'password_confirm' => 'password123'
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents('https://wall.cyka.lol/api/v1/auth/register', false, $context);

if ($result === FALSE) {
    echo "Error occurred during registration\n";
} else {
    echo "Registration response:\n";
    echo $result . "\n";
}

// Also test with a different username to avoid conflicts
$data2 = [
    'username' => 'api_test_user_2',
    'email' => 'api_test_2@example.com',
    'password' => 'password123',
    'password_confirm' => 'password123'
];

$options2 = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data2),
    ],
];

$context2  = stream_context_create($options2);
$result2 = file_get_contents('https://wall.cyka.lol/api/v1/auth/register', false, $context2);

if ($result2 === FALSE) {
    echo "Error occurred during second registration\n";
} else {
    echo "Second registration response:\n";
    echo $result2 . "\n";
}
?>