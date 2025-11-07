<?php
\ = curl_init();
curl_setopt(\, CURLOPT_URL, 'https://wall.cyka.lol/api/v1/auth/register');
curl_setopt(\, CURLOPT_POST, true);
curl_setopt(\, CURLOPT_POSTFIELDS, json_encode([
    'username' => 'testuser_final',
    'email' => 'test_final@example.com',
    'password' => 'password123',
    'password_confirm' => 'password123'
]));
curl_setopt(\, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt(\, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt(\, CURLOPT_HEADER, true);

\ = curl_exec(\);
\ = curl_getinfo(\, CURLINFO_HTTP_CODE);
\ = curl_getinfo(\, CURLINFO_HEADER_SIZE);
\ = substr(\, 0, \);
\ = substr(\, \);
curl_close(\);

echo " HTTP Code: \