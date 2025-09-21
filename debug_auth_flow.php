<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== DEBUGGING AUTHENTICATION FLOW ===\n\n";

try {
    // Initialize Firebase with service account path
    $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
    
    if (!file_exists($serviceAccountPath)) {
        throw new Exception("Firebase service account file not found at: $serviceAccountPath");
    }
    
    echo "Using Firebase service account: " . basename($serviceAccountPath) . "\n";
    
    $factory = (new Factory)->withServiceAccount($serviceAccountPath);
    $auth = $factory->createAuth();
    $firestore = $factory->createFirestore();
    
    echo "✅ Firebase initialized successfully\n\n";
    
    // Get admin user from Firebase Auth
    $adminEmail = 'admin@healthreach.com';
    echo "1. Getting admin user from Firebase Auth...\n";
    
    try {
        $firebaseUser = $auth->getUserByEmail($adminEmail);
        echo "✅ Found Firebase user: {$firebaseUser->uid}\n";
        echo "   Email: {$firebaseUser->email}\n";
        echo "   Email verified: " . ($firebaseUser->emailVerified ? 'Yes' : 'No') . "\n";
        echo "   Disabled: " . ($firebaseUser->disabled ? 'Yes' : 'No') . "\n\n";
        
        // Create a custom token (this is what the backend login endpoint does)
        echo "2. Creating custom token (backend login flow)...\n";
        $customToken = $auth->createCustomToken($firebaseUser->uid);
        echo "✅ Custom token created: " . substr($customToken, 0, 50) . "...\n\n";
        
        // Try to verify the custom token as an ID token (this is what middleware does)
        echo "3. Testing custom token verification as ID token...\n";
        try {
            $verifiedIdToken = $auth->verifyIdToken($customToken);
            echo "✅ Custom token verified as ID token (unexpected!)\n";
        } catch (Exception $e) {
            echo "❌ Custom token CANNOT be verified as ID token: " . $e->getMessage() . "\n";
            echo "   This is EXPECTED - custom tokens are not ID tokens!\n\n";
        }
        
        // Now let's simulate what should happen with a real ID token
        echo "4. Testing with a real Firebase ID token...\n";
        echo "   NOTE: We can't generate a real ID token server-side.\n";
        echo "   ID tokens must be obtained from Firebase client SDK after user authentication.\n\n";
        
        // Check Firestore user data
        echo "5. Checking admin user in Firestore...\n";
        $usersCollection = $firestore->collection('users');
        $query = $usersCollection->where('firebase_uid', '=', $firebaseUser->uid);
        $documents = $query->documents();
        
        $user = null;
        foreach ($documents as $document) {
            if ($document->exists()) {
                $user = $document->data();
                $user['id'] = $document->id();
                break;
            }
        }
        
        if ($user) {
            echo "✅ Found user in Firestore:\n";
            echo "   ID: {$user['id']}\n";
            echo "   Email: {$user['email']}\n";
            echo "   Role: {$user['role']}\n";
            echo "   Firebase UID: {$user['firebase_uid']}\n";
            echo "   Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n\n";
        } else {
            echo "❌ User not found in Firestore with firebase_uid: {$firebaseUser->uid}\n\n";
        }
        
        // Summary and recommendations
        echo "=== ANALYSIS SUMMARY ===\n";
        echo "❌ PROBLEM IDENTIFIED:\n";
        echo "   1. Backend /auth/login returns CUSTOM tokens\n";
        echo "   2. Middleware expects ID tokens\n";
        echo "   3. Custom tokens cannot be verified as ID tokens\n\n";
        
        echo "✅ SOLUTION OPTIONS:\n";
        echo "   Option 1: Change backend to NOT return custom tokens\n";
        echo "            - Let frontend use Firebase ID tokens directly\n";
        echo "            - Backend just validates and returns user data\n\n";
        
        echo "   Option 2: Change middleware to accept custom tokens\n";
        echo "            - Create separate middleware for custom JWT tokens\n";
        echo "            - Use different verification logic\n\n";
        
        echo "   Option 3: Hybrid approach (RECOMMENDED)\n";
        echo "            - Keep Firebase ID tokens for authentication\n";
        echo "            - Backend validates ID token and returns user data (no custom token)\n";
        echo "            - Frontend continues using Firebase ID tokens for API calls\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error getting Firebase user: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Firebase initialization failed: " . $e->getMessage() . "\n";
}

echo "=== END DEBUG ===\n";
