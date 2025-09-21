<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Debug Admin User ===" . PHP_EOL;

try {
    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        'projectId' => env('FIREBASE_PROJECT_ID', 'healthreach-9167b')
    ]);
    
    $userId = 'user-2551cef9-3323-43db-b3e8-a73317cf00ea';
    
    echo "Checking admin user: $userId" . PHP_EOL;
    
    // Get the document
    $userRef = $firestore->collection('users')->document($userId);
    $snapshot = $userRef->snapshot();
    
    if ($snapshot->exists()) {
        $data = $snapshot->data();
        
        echo "✅ User found!" . PHP_EOL;
        echo "Email: " . ($data['email'] ?? 'N/A') . PHP_EOL;
        echo "Name: " . ($data['name'] ?? 'N/A') . PHP_EOL;
        echo "Role: " . ($data['role'] ?? 'N/A') . PHP_EOL;
        echo "Firebase UID: " . ($data['firebase_uid'] ?? 'NOT SET') . PHP_EOL;
        echo "Is Active: " . (($data['is_active'] ?? true) ? 'true' : 'false') . PHP_EOL;
        
        // Check if firebase_uid is missing or incorrect
        if (!isset($data['firebase_uid']) || empty($data['firebase_uid'])) {
            echo PHP_EOL . "❌ ISSUE FOUND: firebase_uid is missing!" . PHP_EOL;
            echo "This is why the middleware can't find the user." . PHP_EOL;
            
            // Let's try to get the Firebase UID from the token
            echo PHP_EOL . "We need to set the firebase_uid field for this user." . PHP_EOL;
            echo "The middleware looks for users by firebase_uid, not by document ID." . PHP_EOL;
        } else {
            echo PHP_EOL . "✅ firebase_uid is set: " . $data['firebase_uid'] . PHP_EOL;
        }
        
    } else {
        echo "❌ User document not found!" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
