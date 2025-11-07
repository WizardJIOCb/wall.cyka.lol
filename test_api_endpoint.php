<?php
// Test API endpoint directly

echo "=== Testing API endpoint directly ===\n";

// Test data
$data = [
    'username' => 'api_endpoint_test',
    'email' => 'api_endpoint_test@example.com',
    'password' => 'password123',
    'password_confirm' => 'password123'
];

$jsonData = json_encode($data);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ],
        'content' => $jsonData
    ]
]);

echo "Sending request to API endpoint...\n";
echo "Data: $jsonData\n";

$result = file_get_contents('http://nginx/api/v1/auth/register', false, $context);

if ($result === FALSE) {
    echo "Error occurred during API request\n";
} else {
    echo "API Response:\n";
    echo $result . "\n";
}
?>