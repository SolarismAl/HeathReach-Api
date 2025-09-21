<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Fixing Admin Role Update ===" . PHP_EOL;

try {
    $firestoreService = app(\App\Services\FirestoreService::class);
    
    $email = 'admin@healthreach.com';
    $userId = 'user-2551cef9-3323-43db-b3e8-a73317cf00ea';
    
    echo "Attempting to update user: $userId" . PHP_EOL;
    
    // Try direct Firestore update
    $firestore = $firestoreService->getFirestore();
    $userRef = $firestore->collection('users')->document($userId);
    
    // Get current document
    $snapshot = $userRef->snapshot();
    
    if ($snapshot->exists()) {
        echo "✅ Document exists, current data:" . PHP_EOL;
        $currentData = $snapshot->data();
        echo "Role: " . ($currentData['role'] ?? 'not set') . PHP_EOL;
        echo "Email: " . ($currentData['email'] ?? 'not set') . PHP_EOL;
        
        // Update the role
        $userRef->update([
            ['path' => 'role', 'value' => 'admin'],
            ['path' => 'updated_at', 'value' => now()->toISOString()]
        ]);
        
        echo "✅ Role updated successfully!" . PHP_EOL;
        
        // Verify the update
        $updatedSnapshot = $userRef->snapshot();
        $updatedData = $updatedSnapshot->data();
        echo "New role: " . ($updatedData['role'] ?? 'not set') . PHP_EOL;
        
        echo PHP_EOL . "🎉 Admin account ready!" . PHP_EOL;
        echo "📧 Email: $email" . PHP_EOL;
        echo "🔑 Password: admin123456" . PHP_EOL;
        echo "👤 Role: admin" . PHP_EOL;
        
    } else {
        echo "❌ Document not found: $userId" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
}

echo PHP_EOL . "Script completed." . PHP_EOL;
