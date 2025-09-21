<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== TESTING ADMIN AUTHENTICATION ===\n\n";

try {
    // Initialize Firebase
    $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
    $factory = (new Factory)->withServiceAccount($serviceAccountPath);
    $auth = $factory->createAuth();
    $firestore = $factory->createFirestore();
    
    echo "✅ Firebase initialized successfully\n\n";
    
    // Get admin user from Firebase Auth
    $adminEmail = 'admin@healthreach.com';
    echo "1. Checking admin user in Firebase Auth...\n";
    
    try {
        $firebaseUser = $auth->getUserByEmail($adminEmail);
        echo "✅ Firebase user found: {$firebaseUser->uid}\n";
        echo "   Email: {$firebaseUser->email}\n";
        echo "   Email verified: " . ($firebaseUser->emailVerified ? 'Yes' : 'No') . "\n";
        echo "   Disabled: " . ($firebaseUser->disabled ? 'Yes' : 'No') . "\n\n";
        
        // Check Firestore user
        echo "2. Checking admin user in Firestore...\n";
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
            echo "✅ Firestore user found:\n";
            echo "   ID: {$user['id']}\n";
            echo "   Email: {$user['email']}\n";
            echo "   Role: {$user['role']}\n";
            echo "   Firebase UID: {$user['firebase_uid']}\n";
            echo "   Active: " . (isset($user['is_active']) && $user['is_active'] ? 'Yes' : 'No') . "\n\n";
            
            if ($user['role'] !== 'admin') {
                echo "❌ PROBLEM: User role is '{$user['role']}', should be 'admin'\n";
                echo "   This will cause 403 Forbidden errors on admin endpoints\n\n";
            } else {
                echo "✅ User has correct admin role\n\n";
            }
        } else {
            echo "❌ User not found in Firestore\n";
            echo "   This will cause 404 errors on admin endpoints\n\n";
        }
        
        // Test token creation and verification
        echo "3. Testing token flow...\n";
        
        // Create a custom token (what login endpoint returns)
        $customToken = $auth->createCustomToken($firebaseUser->uid);
        echo "✅ Custom token created: " . substr($customToken, 0, 50) . "...\n";
        
        // Try to verify custom token as ID token (this should fail)
        try {
            $verifiedIdToken = $auth->verifyIdToken($customToken);
            echo "❌ UNEXPECTED: Custom token verified as ID token!\n";
        } catch (Exception $e) {
            echo "✅ EXPECTED: Custom token cannot be verified as ID token\n";
            echo "   Error: " . $e->getMessage() . "\n\n";
        }
        
        echo "4. SOLUTION ANALYSIS:\n";
        echo "   The issue is likely one of these:\n";
        echo "   a) Firebase ID token has expired (tokens expire after 1 hour)\n";
        echo "   b) Frontend is somehow sending custom token instead of ID token\n";
        echo "   c) There's a token refresh issue in the frontend\n\n";
        
        echo "5. RECOMMENDED ACTIONS:\n";
        echo "   1. User should logout and login again to get fresh Firebase ID token\n";
        echo "   2. Check browser console for token-related errors\n";
        echo "   3. Verify the token being sent in network requests\n\n";
        
        echo "6. TESTING MIDDLEWARE DIRECTLY:\n";
        echo "   To test if middleware works, we need a real Firebase ID token\n";
        echo "   This can only be obtained from client-side Firebase Auth\n";
        echo "   Server-side custom tokens cannot be used for API authentication\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error getting Firebase user: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Firebase initialization failed: " . $e->getMessage() . "\n";
}

echo "=== END TEST ===\n";
