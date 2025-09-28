<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Exception\Auth\InvalidPassword;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Exception;

class CustomAuthController extends Controller
{
    private FirestoreService $firestoreService;
    private ActivityLogService $activityLogService;
    private Auth $firebaseAuth;

    public function __construct(
        FirestoreService $firestoreService,
        ActivityLogService $activityLogService
    ) {
        $this->firestoreService = $firestoreService;
        $this->activityLogService = $activityLogService;
        
        // Initialize Firebase Auth
        $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
        $this->firebaseAuth = $factory->createAuth();
    }

    /**
     * Custom Firebase login that bypasses client-side Firebase issues
     */
    public function firebaseLogin(Request $request)
    {
        try {
            \Log::info('=== CUSTOM FIREBASE LOGIN ===');
            \Log::info('Request data:', $request->all());

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->input('email');
            $password = $request->input('password');

            \Log::info('Attempting Firebase Auth with email:', ['email' => $email]);

            // Use Firebase Auth to verify email/password
            try {
                // Try to sign in with Firebase Auth directly
                $signInResult = $this->firebaseAuth->signInWithEmailAndPassword($email, $password);
                
                // Get the Firebase user data from the sign-in result
                $firebaseUserData = $signInResult->data();
                $firebaseUid = $firebaseUserData['localId'];
                
                \Log::info('Firebase Auth successful for UID:', ['uid' => $firebaseUid]);
            } catch (InvalidPassword $e) {
                \Log::error('Firebase Auth - Invalid password:', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            } catch (UserNotFound $e) {
                \Log::error('Firebase Auth - User not found:', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            } catch (Exception $authException) {
                \Log::error('Firebase Auth failed:', ['error' => $authException->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed: ' . $authException->getMessage()
                ], 401);
            }

            // Find user in Firestore using Firebase UID
            $user = null;
            try {
                $user = $this->firestoreService->findByField('users', 'firebase_uid', $firebaseUid);
            } catch (\Exception $e) {
                \Log::warning('Firestore lookup by firebase_uid failed: ' . $e->getMessage());
            }

            if (!$user) {
                \Log::info('User not found by firebase_uid, trying email lookup');
                
                // Try to find by email as fallback
                try {
                    $user = $this->firestoreService->findByField('users', 'email', $email);
                } catch (\Exception $e) {
                    \Log::warning('Firestore lookup by email failed: ' . $e->getMessage());
                }
                
                if (!$user) {
                    \Log::info('User not found in Firestore, creating new user profile');
                    
                    // Determine role based on email
                    $role = $this->determineUserRole($email, $firebaseUser);
                    
                    // Create user profile
                    $user = [
                        'user_id' => 'user-' . \Illuminate\Support\Str::uuid(),
                        'firebase_uid' => $firebaseUid,
                        'name' => $firebaseUser->displayName ?? explode('@', $email)[0],
                        'email' => $email,
                        'role' => $role,
                        'contact_number' => null,
                        'address' => null,
                        'is_active' => true,
                        'created_at' => now()->toISOString(),
                        'updated_at' => now()->toISOString(),
                    ];
                    
                    // Save to Firestore
                    try {
                        $this->firestoreService->create('users', $user['user_id'], $user);
                        \Log::info('New user created in Firestore: ' . $user['user_id']);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to save user to Firestore: ' . $e->getMessage());
                    }
                } else {
                    // Update firebase_uid if different
                    if ($user['firebase_uid'] !== $firebaseUid) {
                        \Log::info('Updating firebase_uid for user: ' . $user['user_id']);
                        $user['firebase_uid'] = $firebaseUid;
                        try {
                            $this->firestoreService->update('users', $user['user_id'], ['firebase_uid' => $firebaseUid]);
                        } catch (\Exception $e) {
                            \Log::warning('Failed to update firebase_uid: ' . $e->getMessage());
                        }
                    }
                }
            }

            \Log::info('User found:', ['user_id' => $user['user_id']]);

            // Generate Firebase custom token for API access
            $customToken = $this->firebaseAuth->createCustomToken($firebaseUid, [
                'user_id' => $user['user_id'],
                'role' => $user['role']
            ]);

            \Log::info('Firebase custom token generated');

            // Log activity
            $this->activityLogService->log(
                $user['user_id'],
                'user_login',
                'User logged in via custom auth',
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'user_id' => $user['user_id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'contact_number' => $user['contact_number'] ?? null,
                        'address' => $user['address'] ?? null,
                        'is_active' => $user['is_active'] ?? true,
                        'created_at' => $user['created_at'] ?? null,
                        'updated_at' => $user['updated_at'] ?? null,
                    ],
                    'token' => $customToken->toString(),
                    'firebase_token' => $customToken->toString()
                ]
            ]);

        } catch (Exception $e) {
            \Log::error('Custom Firebase login error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine user role based on email patterns or Firebase custom claims
     */
    private function determineUserRole(string $email, $firebaseUser = null): string
    {
        // Check Firebase custom claims first if available
        if ($firebaseUser && method_exists($firebaseUser, 'customClaims')) {
            $customClaims = $firebaseUser->customClaims;
            if (isset($customClaims['role'])) {
                \Log::info('Role from Firebase custom claims: ' . $customClaims['role']);
                return $customClaims['role'];
            }
        }

        // Determine role based on email patterns
        $email = strtolower($email);
        
        // Admin patterns
        if (str_contains($email, 'admin') || 
            str_contains($email, 'administrator') ||
            in_array($email, ['admin@healthreach.com', 'admin@gmail.com', 'admin@example.com'])) {
            \Log::info('Role determined as admin based on email pattern');
            return 'admin';
        }
        
        // Health worker patterns
        if (str_contains($email, 'doctor') || 
            str_contains($email, 'nurse') || 
            str_contains($email, 'health') ||
            str_contains($email, 'worker') ||
            str_contains($email, 'medical') ||
            in_array($email, ['healthworker@gmail.com', 'doctor@gmail.com', 'nurse@gmail.com'])) {
            \Log::info('Role determined as health_worker based on email pattern');
            return 'health_worker';
        }
        
        // Default to patient
        \Log::info('Role defaulted to patient');
        return 'patient';
    }
}
