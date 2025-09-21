<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FirebaseService;
use App\Services\FirestoreService;

try {
    echo "=== Creating Admin User ===" . PHP_EOL;
    
    $firebaseService = app(FirebaseService::class);
    $firestoreService = app(FirestoreService::class);
    
    // Admin user data
    $email = 'admin@healthreach.com';
    $password = 'admin123456';
    $name = 'HealthReach Admin';
    
    echo "Creating Firebase user with email: $email" . PHP_EOL;
    
    // Create Firebase user
    $firebaseUser = $firebaseService->createUser([
        'email' => $email,
        'password' => $password,
        'displayName' => $name,
        'emailVerified' => true
    ]);
    
    if (!$firebaseUser['success']) {
        throw new Exception('Failed to create Firebase user: ' . $firebaseUser['error']);
    }
    
    $firebaseUid = $firebaseUser['uid'];
    echo "Firebase user created with UID: $firebaseUid" . PHP_EOL;
    
    // Create Firestore document
    $userData = [
        'firebase_uid' => $firebaseUid,
        'name' => $name,
        'email' => $email,
        'role' => 'admin',
        'contact_number' => '+1234567890',
        'address' => 'HealthReach Admin Office',
        'created_at' => now()->toISOString(),
        'updated_at' => now()->toISOString(),
        'is_active' => true
    ];
    
    echo "Creating Firestore document..." . PHP_EOL;
    $docId = $firestoreService->createDocument('users', $userData);
    echo "Firestore document created with ID: $docId" . PHP_EOL;
    
    echo PHP_EOL . "✅ Admin user created successfully!" . PHP_EOL;
    echo "📧 Email: $email" . PHP_EOL;
    echo "🔑 Password: $password" . PHP_EOL;
    echo "👤 Role: admin" . PHP_EOL;
    echo PHP_EOL . "You can now login with these credentials!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error creating admin user: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
