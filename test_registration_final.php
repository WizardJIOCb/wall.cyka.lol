<?php
\ = json_encode([
    'username' => 'testuser_final',
    'email' => 'test_final@example.com',
    'password' => 'password123',
    'password_confirm' => 'password123'
]);

\ = curl_init();
curl_setopt(\, CURLOPT_URL, 'https://wall.cyka.lol/api/v1/auth/register');
curl_setopt(\, CURLOPT_POST, true);
curl_setopt(\, CURLOPT_POSTFIELDS, \);
curl_setopt(\, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt(\, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\, CURLOPT_SSL_VERIFYPEER, false);

\ = curl_exec(\);
\ = curl_getinfo(\, CURLINFO_HTTP_CODE);
curl_close(\);

echo " HTTP Code: \