<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Fix Admin Firebase UID ===" . PHP_EOL;

try {
    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        'projectId' => env('FIREBASE_PROJECT_ID', 'healthreach-9167b')
    ]);
    
    $userId = 'user-2551cef9-3323-43db-b3e8-a73317cf00ea';
    
    echo "Getting Firebase UID from Firebase Auth..." . PHP_EOL;
    
    // Get Firebase Auth instance
    $factory = (new Kreait\Firebase\Factory)->withServiceAccount(config('firebase.credentials'));
    $auth = $factory->createAuth();
    
    // Find user by email in Firebase Auth
    try {
        $firebaseUser = $auth->getUserByEmail('admin@healthreach.com');
        $firebaseUid = $firebaseUser->uid;
        
        echo "✅ Found Firebase user!" . PHP_EOL;
        echo "Firebase UID: $firebaseUid" . PHP_EOL;
        
        // Update the Firestore document with the correct firebase_uid
        $userRef = $firestore->collection('users')->document($userId);
        $userRef->update([
            ['path' => 'firebase_uid', 'value' => $firebaseUid],
            ['path' => 'updated_at', 'value' => new DateTime()]
        ]);
        
        echo "✅ Updated admin user with Firebase UID!" . PHP_EOL;
        
        // Verify the update
        $snapshot = $userRef->snapshot();
        if ($snapshot->exists()) {
            $data = $snapshot->data();
            echo "✅ Verified - Firebase UID: " . ($data['firebase_uid'] ?? 'NOT SET') . PHP_EOL;
            echo "✅ Role: " . ($data['role'] ?? 'NOT SET') . PHP_EOL;
            echo "✅ Email: " . ($data['email'] ?? 'NOT SET') . PHP_EOL;
        }
        
        echo PHP_EOL . "🎉 Admin user is now properly configured!" . PHP_EOL;
        echo "The API should now work with Firebase ID tokens." . PHP_EOL;
        
    } catch (Exception $e) {
        echo "❌ Firebase user not found: " . $e->getMessage() . PHP_EOL;
        echo "This means the user wasn't created in Firebase Auth during registration." . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
