<?php

// Test script to trigger health worker endpoints and see logging output

$baseUrl = 'http://127.0.0.1:8000/api';

// Simulate health worker request headers
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Agent: HealthReach-Mobile/1.0'
];

// Simulate health worker data (this would normally come from Firebase auth middleware)
$healthWorkerData = [
    'firebase_uid' => 'test-health-worker-uid-123',
    'user_role' => 'health_worker'
];

echo "=== Testing Health Worker Endpoints ===\n\n";

// Test 1: Get Health Centers
echo "1. Testing GET /health-centers\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/health-centers');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($healthWorkerData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        echo "Health Centers Found: " . count($data['data']) . "\n";
        foreach ($data['data'] as $center) {
            echo "  - {$center['name']} (ID: {$center['health_center_id']})\n";
        }
    }
}
echo "\n";

// Test 2: Get Services
echo "2. Testing GET /services\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/services');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($healthWorkerData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        echo "Services Found: " . count($data['data']) . "\n";
        foreach ($data['data'] as $service) {
            echo "  - {$service['service_name']} (Health Center ID: {$service['health_center_id']}, Price: $" . ($service['price'] ?? 'N/A') . ")\n";
        }
    }
}
echo "\n";

echo "=== Check Laravel logs at storage/logs/laravel.log for detailed logging ===\n";
echo "=== Look for sections marked with === HEALTH CENTERS INDEX === and === SERVICES INDEX === ===\n";
