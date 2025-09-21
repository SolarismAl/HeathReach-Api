<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test Token Verification ===" . PHP_EOL;

try {
    // Get Firebase Auth instance
    $factory = (new Kreait\Firebase\Factory)->withServiceAccount(config('firebase.credentials'));
    $auth = $factory->createAuth();
    
    // Get the admin user from Firebase Auth
    $firebaseUser = $auth->getUserByEmail('admin@healthreach.com');
    echo "✅ Firebase user found: " . $firebaseUser->uid . PHP_EOL;
    
    // Create a custom token for testing
    $customToken = $auth->createCustomToken($firebaseUser->uid);
    echo "✅ Custom token created: " . substr($customToken->toString(), 0, 50) . "..." . PHP_EOL;
    
    // Test the middleware directly
    echo PHP_EOL . "Testing middleware with a fresh token..." . PHP_EOL;
    
    // Create a test request with the custom token
    $testToken = $customToken->toString();
    
    // Test the API endpoint with curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/admin/stats');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $testToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "API Response (Status: $httpCode):" . PHP_EOL;
    echo $response . PHP_EOL;
    
    if ($httpCode === 200) {
        echo "✅ API call successful with custom token!" . PHP_EOL;
    } else {
        echo "❌ API call failed. The issue might be:" . PHP_EOL;
        echo "1. Token format issue" . PHP_EOL;
        echo "2. Middleware configuration problem" . PHP_EOL;
        echo "3. Firebase service account issue" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}
