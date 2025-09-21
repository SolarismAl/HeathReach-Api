<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Fixing Admin Role via Tinker ===" . PHP_EOL;

try {
    // Get the Firestore client directly
    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        'projectId' => env('FIREBASE_PROJECT_ID', 'healthreach-9167b')
    ]);
    
    $userId = 'user-2551cef9-3323-43db-b3e8-a73317cf00ea';
    
    echo "Updating user role to admin..." . PHP_EOL;
    
    // Update the document
    $userRef = $firestore->collection('users')->document($userId);
    $userRef->update([
        ['path' => 'role', 'value' => 'admin'],
        ['path' => 'updated_at', 'value' => new DateTime()]
    ]);
    
    echo "✅ Role updated successfully!" . PHP_EOL;
    
    // Verify the update
    $snapshot = $userRef->snapshot();
    if ($snapshot->exists()) {
        $data = $snapshot->data();
        echo "✅ Verified - New role: " . $data['role'] . PHP_EOL;
        echo "📧 Email: " . $data['email'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    echo "Let's try alternative method..." . PHP_EOL;
    
    // Alternative: Use Laravel's Firestore service
    try {
        $firestoreService = app(\App\Services\FirestoreService::class);
        $result = $firestoreService->updateDocument('users', $userId, [
            'role' => 'admin',
            'updated_at' => now()->toISOString()
        ]);
        
        if ($result) {
            echo "✅ Alternative method worked!" . PHP_EOL;
        }
    } catch (Exception $e2) {
        echo "❌ Alternative also failed: " . $e2->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "Now logout and login again to see admin access!" . PHP_EOL;
