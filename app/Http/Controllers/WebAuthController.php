<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\FirestoreService;
use App\Services\FirebaseService;
use Kreait\Firebase\Exception\Auth\InvalidIdToken;

class WebAuthController extends Controller
{
    protected $firestoreService;
    protected $firebaseService;

    public function __construct(FirestoreService $firestoreService, FirebaseService $firebaseService)
    {
        $this->firestoreService = $firestoreService;
        $this->firebaseService = $firebaseService;
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        \Log::info('=== WEB AUTH LOGIN ATTEMPT ===');
        \Log::info('Email: ' . $request->email);

        try {
            // Try to authenticate with Firebase using the existing service
            $auth = $this->firebaseService->getAuth();
            \Log::info('Firebase Auth service obtained successfully');
            
            // Get user by email first to check if they exist
            try {
                $firebaseUser = $auth->getUserByEmail($request->email);
                \Log::info('Firebase user found - UID: ' . $firebaseUser->uid);
                \Log::info('Firebase user email: ' . $firebaseUser->email);
                \Log::info('Firebase user displayName: ' . ($firebaseUser->displayName ?? 'null'));
            } catch (\Exception $e) {
                \Log::error('Firebase user not found: ' . $e->getMessage());
                return back()->withErrors(['email' => 'User not found or invalid credentials.']);
            }
            
            // Get user from Firestore
            \Log::info('Attempting to get user from Firestore with UID: ' . $firebaseUser->uid);
            $user = $this->firestoreService->getDocument('users', $firebaseUser->uid);
            \Log::info('Firestore query result: ' . json_encode($user));
            
            if (!$user) {
                \Log::error('User not found in Firestore database for UID: ' . $firebaseUser->uid);
                \Log::info('Checking if user exists with different document ID...');
                
                // Try to find user by email in Firestore
                try {
                    $allUsers = $this->firestoreService->getCollection('users');
                    \Log::info('Total users in Firestore: ' . count($allUsers));
                    
                    foreach ($allUsers as $docId => $userData) {
                        \Log::info('User document ID: ' . $docId . ', Email: ' . ($userData['email'] ?? 'no-email'));
                        if (isset($userData['email']) && $userData['email'] === $request->email) {
                            \Log::info('Found user by email with document ID: ' . $docId);
                            $user = $userData;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error searching users collection: ' . $e->getMessage());
                }
                
                if (!$user) {
                    return back()->withErrors(['email' => 'User not found in database. UID: ' . $firebaseUser->uid]);
                }
            }

            // Check if user is admin or health_worker
            \Log::info('User role: ' . ($user['role'] ?? 'no-role'));
            if (!in_array($user['role'], ['admin', 'health_worker'])) {
                \Log::error('Access denied for role: ' . ($user['role'] ?? 'no-role'));
                return back()->withErrors(['email' => 'Access denied. Admin or Health Worker access required.']);
            }

            // For web authentication, we'll create a custom token for session management
            \Log::info('Creating custom token for UID: ' . $firebaseUser->uid);
            $customToken = $this->firebaseService->createCustomToken($firebaseUser->uid);
            \Log::info('Custom token created successfully');

            // Store user data in session
            session([
                'user' => $user,
                'firebase_token' => $customToken,
                'firebase_uid' => $firebaseUser->uid
            ]);
            \Log::info('User data stored in session');

            // Redirect based on role
            if ($user['role'] === 'admin') {
                \Log::info('Redirecting to admin dashboard');
                return redirect()->route('admin.dashboard');
            } else {
                \Log::info('Redirecting to health worker dashboard');
                return redirect()->route('health-worker.dashboard');
            }

        } catch (\Exception $e) {
            \Log::error('Web auth login exception: ' . $e->getMessage());
            \Log::error('Exception trace: ' . $e->getTraceAsString());
            return back()->withErrors(['email' => 'Invalid credentials or authentication failed: ' . $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
