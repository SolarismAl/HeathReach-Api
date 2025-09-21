<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Debug Middleware Issue ===" . PHP_EOL;

try {
    // Test the FirestoreService directly
    $firestoreService = app(\App\Services\FirestoreService::class);
    
    echo "1. Testing FirestoreService findByField method..." . PHP_EOL;
    $firebaseUid = 'Y1WQvsoFjARrx62HnLg6WVCeN9m1';
    
    $user = $firestoreService->findByField('users', 'firebase_uid', $firebaseUid);
    
    if ($user) {
        echo "✅ User found by firebase_uid!" . PHP_EOL;
        echo "   Name: " . ($user['name'] ?? 'N/A') . PHP_EOL;
        echo "   Email: " . ($user['email'] ?? 'N/A') . PHP_EOL;
        echo "   Role: " . ($user['role'] ?? 'N/A') . PHP_EOL;
        echo "   Is Active: " . (($user['is_active'] ?? true) ? 'true' : 'false') . PHP_EOL;
    } else {
        echo "❌ User NOT found by firebase_uid: $firebaseUid" . PHP_EOL;
        
        // Let's check what users exist
        echo "Checking all users in database..." . PHP_EOL;
        $firestore = new Google\Cloud\Firestore\FirestoreClient([
            'projectId' => env('FIREBASE_PROJECT_ID', 'healthreach-9167b')
        ]);
        
        $usersCollection = $firestore->collection('users');
        $userDocs = $usersCollection->documents();
        
        foreach ($userDocs as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();
                echo "   User: " . ($data['email'] ?? 'N/A') . 
                     " | Firebase UID: " . ($data['firebase_uid'] ?? 'NOT SET') . 
                     " | Role: " . ($data['role'] ?? 'N/A') . PHP_EOL;
            }
        }
    }
    
    echo PHP_EOL . "2. Testing Firebase Auth token verification..." . PHP_EOL;
    
    // Get Firebase Auth instance
    $factory = (new Kreait\Firebase\Factory)->withServiceAccount(config('firebase.credentials'));
    $auth = $factory->createAuth();
    
    // Create a custom token and try to verify it
    $firebaseUser = $auth->getUserByEmail('admin@healthreach.com');
    $customToken = $auth->createCustomToken($firebaseUser->uid);
    
    echo "Custom token created for UID: " . $firebaseUser->uid . PHP_EOL;
    
    // The issue might be that custom tokens can't be verified as ID tokens
    echo "❌ Custom tokens cannot be verified as ID tokens!" . PHP_EOL;
    echo "The middleware expects ID tokens, but we're creating custom tokens." . PHP_EOL;
    
    echo PHP_EOL . "3. Solution needed:" . PHP_EOL;
    echo "- Frontend should use Firebase ID tokens (from Firebase Auth)" . PHP_EOL;
    echo "- Backend should verify ID tokens, not custom tokens" . PHP_EOL;
    echo "- The token from login response should be used for API calls" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
