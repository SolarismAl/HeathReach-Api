<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use App\Services\FirestoreService;

class TestController extends Controller
{
    protected $firebaseService;
    protected $firestoreService;

    public function __construct(FirebaseService $firebaseService, FirestoreService $firestoreService)
    {
        $this->firebaseService = $firebaseService;
        $this->firestoreService = $firestoreService;
    }

    public function testAuth()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Authentication test endpoint working',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testAdmin()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Admin test endpoint working',
                'user' => auth()->user() ?? 'No authenticated user',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testFirebase()
    {
        try {
            \Log::info('=== FIREBASE SERVICE TEST ===');
            
            // Test Firebase service initialization
            $auth = $this->firebaseService->getAuth();
            \Log::info('Firebase Auth service obtained');
            
            // Test getting user by email
            $testEmail = 'admin@healthreach.com';
            \Log::info('Testing getUserByEmail for: ' . $testEmail);
            
            try {
                $firebaseUser = $auth->getUserByEmail($testEmail);
                \Log::info('Firebase user found - UID: ' . $firebaseUser->uid);
                
                // Test Firestore query
                \Log::info('Testing Firestore query for UID: ' . $firebaseUser->uid);
                $firestoreUser = $this->firestoreService->getDocument('users', $firebaseUser->uid);
                \Log::info('Firestore query result: ' . json_encode($firestoreUser));
                
                return response()->json([
                    'success' => true,
                    'message' => 'Firebase service test successful',
                    'firebase_user' => [
                        'uid' => $firebaseUser->uid,
                        'email' => $firebaseUser->email,
                        'displayName' => $firebaseUser->displayName
                    ],
                    'firestore_user' => $firestoreUser,
                    'timestamp' => now()->toISOString()
                ]);
                
            } catch (\Exception $e) {
                \Log::error('Firebase user lookup failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'error' => 'Firebase user lookup failed: ' . $e->getMessage(),
                    'step' => 'getUserByEmail'
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error('Firebase service test failed: ' . $e->getMessage());
            \Log::error('Exception trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'step' => 'service_initialization'
            ], 500);
        }
    }
}
