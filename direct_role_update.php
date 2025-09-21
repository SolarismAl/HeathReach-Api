<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Direct Role Update ===" . PHP_EOL;

try {
    // Get Firebase Factory directly
    $factory = app('firebase.factory');
    $firestore = $factory->createFirestore();
    
    $userId = 'user-2551cef9-3323-43db-b3e8-a73317cf00ea';
    
    echo "Updating user: $userId" . PHP_EOL;
    
    // Direct Firestore update
    $userRef = $firestore->collection('users')->document($userId);
    
    // Update the document
    $userRef->update([
        ['path' => 'role', 'value' => 'admin'],
        ['path' => 'updated_at', 'value' => new \DateTime()]
    ]);
    
    echo "✅ Role updated to admin!" . PHP_EOL;
    
    // Verify the update
    $snapshot = $userRef->snapshot();
    if ($snapshot->exists()) {
        $data = $snapshot->data();
        echo "Verified role: " . $data['role'] . PHP_EOL;
        echo "Email: " . $data['email'] . PHP_EOL;
        echo "Name: " . $data['name'] . PHP_EOL;
    }
    
    echo PHP_EOL . "🎉 Admin role updated successfully!" . PHP_EOL;
    echo "Please logout and login again to see the changes." . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
