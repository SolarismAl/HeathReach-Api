<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Creating Admin User in Firebase ===" . PHP_EOL;

try {
    // Get Firebase service
    $firebaseService = app(\App\Services\FirebaseService::class);
    $firestoreService = app(\App\Services\FirestoreService::class);
    
    $email = 'admin@healthreach.com';
    $password = 'admin123456';
    $name = 'HealthReach Admin';
    
    echo "Creating Firebase user..." . PHP_EOL;
    
    // Create Firebase user
    $result = $firebaseService->createUser([
        'email' => $email,
        'password' => $password,
        'displayName' => $name,
        'emailVerified' => true
    ]);
    
    if ($result['success']) {
        echo "✅ Firebase user created successfully!" . PHP_EOL;
        echo "UID: " . $result['uid'] . PHP_EOL;
        
        // Create Firestore document
        $userData = [
            'firebase_uid' => $result['uid'],
            'name' => $name,
            'email' => $email,
            'role' => 'admin',
            'contact_number' => '+1234567890',
            'address' => 'Admin Office',
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'is_active' => true
        ];
        
        $docId = $firestoreService->createDocument('users', $userData);
        echo "✅ Firestore document created: " . $docId . PHP_EOL;
        
        echo PHP_EOL . "🎉 Admin user created successfully!" . PHP_EOL;
        echo "📧 Email: " . $email . PHP_EOL;
        echo "🔑 Password: " . $password . PHP_EOL;
        echo "👤 Role: admin" . PHP_EOL;
        
    } else {
        echo "❌ Failed to create Firebase user: " . $result['error'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    
    // If user already exists, that's actually good news!
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "✅ User already exists! You can use these credentials:" . PHP_EOL;
        echo "📧 Email: admin@healthreach.com" . PHP_EOL;
        echo "🔑 Password: admin123456" . PHP_EOL;
    }
}

echo PHP_EOL . "You can now login with the admin credentials!" . PHP_EOL;
