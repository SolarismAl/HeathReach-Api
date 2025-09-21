<?php

echo "=== Testing Fixed API Endpoints ===" . PHP_EOL;

// Test admin stats endpoint
echo "1. Testing admin stats endpoint..." . PHP_EOL;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/admin/stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status Code: $httpCode" . PHP_EOL;
echo "Response: $response" . PHP_EOL . PHP_EOL;

// Test users endpoint
echo "2. Testing users endpoint..." . PHP_EOL;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/users');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status Code: $httpCode" . PHP_EOL;
echo "Response: $response" . PHP_EOL . PHP_EOL;

echo "=== Test Complete ===" . PHP_EOL;
echo "Note: 401 errors are expected without proper authentication" . PHP_EOL;
