<?php
echo "Testing registration...\n";

$data = [
    "username" => "direct_test_user",
    "email" => "direct_test@example.com",
    "password" => "password123",
    "password_confirm" => "password123"
];

$options = [
    "http" => [
        "header"  => "Content-Type: application/json\r\n",
        "method"  => "POST",
        "content" => json_encode($data),
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents("https://wall.cyka.lol/api/v1/auth/register", false, $context);

echo "Response: " . $result . "\n";
?>