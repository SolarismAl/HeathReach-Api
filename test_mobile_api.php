<?php

// Simple test to simulate the exact mobile app request
echo "=== Mobile App API Simulation ===\n\n";

$url = 'http://127.0.0.1:8000/api/auth/forgot-password';
$data = json_encode(['email' => 'alsolarapole@gmail.com']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "📡 Making request to: $url\n";
echo "📄 Request data: $data\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "📊 HTTP Status Code: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
} else {
    echo "✅ Response received\n";
    echo "📄 Response body:\n";
    echo $response . "\n\n";
    
    // Try to decode JSON
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "📋 Parsed JSON:\n";
        print_r($decoded);
        
        if (isset($decoded['success']) && $decoded['success']) {
            echo "\n✅ SUCCESS! The API is working correctly!\n";
            echo "📧 Password reset email should be sent to: alsolarapole@gmail.com\n";
        } else {
            echo "\n❌ API returned error: " . ($decoded['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Failed to parse JSON response\n";
    }
}

echo "\n=== Test Complete ===\n";
