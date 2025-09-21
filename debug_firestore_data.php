<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Debug Firestore Data ===" . PHP_EOL;

try {
    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        'projectId' => env('FIREBASE_PROJECT_ID', 'healthreach-9167b')
    ]);
    
    echo "Checking users collection..." . PHP_EOL;
    $usersCollection = $firestore->collection('users');
    $userDocs = $usersCollection->documents();
    
    $userCount = 0;
    foreach ($userDocs as $doc) {
        if ($doc->exists()) {
            $userCount++;
            $data = $doc->data();
            
            echo "User $userCount:" . PHP_EOL;
            echo "  Document ID: " . $doc->id() . PHP_EOL;
            echo "  Data structure:" . PHP_EOL;
            foreach ($data as $key => $value) {
                if (is_object($value) && method_exists($value, 'formatAsString')) {
                    $value = $value->formatAsString();
                } elseif (is_object($value)) {
                    $value = '[Object: ' . get_class($value) . ']';
                } elseif (is_array($value)) {
                    $value = '[Array with ' . count($value) . ' items]';
                }
                echo "    $key: $value" . PHP_EOL;
            }
            echo PHP_EOL;
        }
    }
    
    echo "Total users found: $userCount" . PHP_EOL;
    
    // Now let's test the API endpoint directly
    echo PHP_EOL . "Testing API endpoint..." . PHP_EOL;
    
    // Create a test token (we'll use a simple one for testing)
    $testToken = 'test-admin-token';
    
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
    
    // Also test users endpoint
    echo PHP_EOL . "Testing users endpoint..." . PHP_EOL;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/users');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $testToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Users API Response (Status: $httpCode):" . PHP_EOL;
    echo $response . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
