<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\FirebaseService;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use App\DataTransferObjects\ApiResponse;
use App\DataTransferObjects\UserData;
use Exception;
use Carbon\Carbon;

class FirebaseAuthController extends Controller
{
    private FirebaseService $firebaseService;
    private FirestoreService $firestoreService;
    private ActivityLogService $activityLogService;

    public function __construct(
        FirebaseService $firebaseService,
        FirestoreService $firestoreService,
        ActivityLogService $activityLogService
    ) {
        $this->firebaseService = $firebaseService;
        $this->firestoreService = $firestoreService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Register a new user with Firebase Auth and Firestore
     */
    public function register(Request $request): JsonResponse
    {
        \Log::info('=== REGISTRATION REQUEST ===');
        \Log::info('Request data:', $request->all());
        \Log::info('Request headers:', $request->headers->all());
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|string|same:password',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        \Log::info('Validation passed, proceeding with registration');

        try {
            // Check if user already exists in Firestore
            $existingUser = $this->firestoreService->findByField('users', 'email', $request->email);
            if ($existingUser) {
                \Log::info('User already exists in Firestore');
                return response()->json([
                    'success' => false,
                    'message' => 'User with this email already exists'
                ], 422);
            }

            // Check if Firebase user already exists
            \Log::info('Checking if Firebase user exists');
            try {
                $existingFirebaseUser = $this->firebaseService->getUserByEmail($request->email);
                if ($existingFirebaseUser) {
                    \Log::info('Firebase user exists, checking Firestore user');
                    // Firebase user exists, check if Firestore user exists
                    $firestoreUser = $this->firestoreService->findByField('users', 'firebase_uid', $existingFirebaseUser['uid']);
                    if (!$firestoreUser) {
                        \Log::info('Firebase user exists but Firestore user missing, creating Firestore user');
                        // Create Firestore user for existing Firebase user
                        $userId = 'user-' . Str::uuid();
                        $userData = [
                            'user_id' => $userId,
                            'firebase_uid' => $existingFirebaseUser['uid'],
                            'name' => $request->name,
                            'email' => $request->email,
                            'role' => $request->role ?? 'patient',
                            'contact_number' => $request->contact_number,
                            'address' => $request->address,
                            'fcm_token' => null,
                            'email_verified_at' => null,
                            'is_active' => true,
                            'created_at' => now()->toISOString(),
                            'updated_at' => now()->toISOString()
                        ];
                        
                        $documentId = $this->firestoreService->createDocument('users', $userData, $userId);
                        if ($documentId) {
                            $customToken = $this->firebaseService->createCustomToken($existingFirebaseUser['uid']);
                            return response()->json([
                                'success' => true,
                                'message' => 'User profile created for existing Firebase user',
                                'data' => [
                                    'user' => [
                                        'user_id' => $userId,
                                        'firebase_uid' => $existingFirebaseUser['uid'],
                                        'name' => $request->name,
                                        'email' => $request->email,
                                        'role' => $request->role ?? 'patient',
                                        'contact_number' => $request->contact_number,
                                        'address' => $request->address
                                    ],
                                    'token' => $customToken
                                ]
                            ], 201);
                        }
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'User already exists'
                        ], 422);
                    }
                }
            } catch (\Exception $e) {
                \Log::info('Firebase user does not exist, proceeding with creation');
            }

            // Create user in Firebase Auth
            \Log::info('Creating Firebase user');
            $firebaseUser = $this->firebaseService->createUser(
                $request->email,
                $request->password,
                $request->name,
                $request->role ?? 'patient'
            );

            if (!$firebaseUser['success']) {
                \Log::error('Failed to create Firebase user:', $firebaseUser);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Firebase user',
                    'error' => $firebaseUser['error'] ?? 'Unknown error'
                ], 500);
            }

            // Generate custom user ID
            $userId = 'user-' . Str::uuid();
            \Log::info('Creating user document with ID: ' . $userId);

            // Create user document in Firestore
            $userData = [
                'user_id' => $userId,
                'firebase_uid' => $firebaseUser['uid'],
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role ?? 'patient',
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'fcm_token' => null,
                'email_verified_at' => null,
                'is_active' => true,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            $documentId = $this->firestoreService->createDocument('users', $userData, $userId);

            if (!$documentId) {
                \Log::error('Failed to create user document in Firestore');
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user profile'
                ], 500);
            }

            \Log::info('User document created successfully');

            // Generate custom token for immediate login
            $customToken = $this->firebaseService->createCustomToken($firebaseUser['uid']);
            \Log::info('Custom token generated for user');

            // Log activity
            $this->activityLogService->log(
                $userId,
                'user_registration',
                'User registered successfully',
                $request->ip(),
                $request->userAgent()
            );

            \Log::info('Registration completed successfully');

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'user_id' => $userId,
                        'firebase_uid' => $firebaseUser['uid'],
                        'name' => $request->name,
                        'email' => $request->email,
                        'role' => $request->role ?? 'patient',
                        'contact_number' => $request->contact_number,
                        'address' => $request->address
                    ],
                    'token' => $customToken
                ]
            ], 201);

        } catch (Exception $e) {
            \Log::error('Registration exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user with Firebase ID token
     */
    public function login(Request $request): JsonResponse
    {
        \Log::info('=== LOGIN REQUEST ===');
        \Log::info('Request data (idToken hidden):', [
            'idToken' => $request->has('idToken') ? 'Present (' . strlen($request->idToken) . ' chars)' : 'Missing'
        ]);
        
        $validator = Validator::make($request->all(), [
            'idToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            \Log::error('Login validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        \Log::info('Login validation passed, verifying Firebase token');

        try {
            \Log::info('Attempting to verify Firebase token with FirebaseService');
            
            // Verify Firebase ID token
            $verifiedToken = $this->firebaseService->verifyIdToken($request->idToken);
            \Log::info('Firebase token verification result:', $verifiedToken);

            if (!$verifiedToken['success']) {
                \Log::error('Firebase token verification failed:', $verifiedToken);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Firebase token: ' . ($verifiedToken['error'] ?? 'Unknown error')
                ], 401);
            }

            // Get user from Firestore using Firebase UID
            $firebaseUid = $verifiedToken['uid'];
            $user = $this->firestoreService->findByField('users', 'firebase_uid', $firebaseUid);

            if (!$user) {
                \Log::error('User not found in Firestore for Firebase UID: ' . $firebaseUid);
                
                // Try to get Firebase user info and create Firestore profile
                try {
                    $firebaseUser = $this->firebaseService->getAuth()->getUser($firebaseUid);
                    if ($firebaseUser) {
                        \Log::info('Creating missing Firestore profile for Firebase user');
                        $userId = 'user-' . Str::uuid();
                        $userData = [
                            'user_id' => $userId,
                            'firebase_uid' => $firebaseUid,
                            'name' => $firebaseUser->displayName ?? 'Unknown User',
                            'email' => $firebaseUser->email,
                            'role' => 'patient', // Default role
                            'contact_number' => null,
                            'address' => null,
                            'fcm_token' => null,
                            'email_verified_at' => $firebaseUser->emailVerified ? now()->toISOString() : null,
                            'is_active' => true,
                            'created_at' => now()->toISOString(),
                            'updated_at' => now()->toISOString()
                        ];
                        
                        $documentId = $this->firestoreService->createDocument('users', $userData, $userId);
                        if ($documentId) {
                            $user = $userData; // Use the newly created user data
                            \Log::info('Successfully created Firestore profile for existing Firebase user');
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to create Firestore profile: ' . $e->getMessage());
                }
                
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User profile not found and could not be created'
                    ], 404);
                }
            }

            // Generate custom token for API access
            $customToken = $this->firebaseService->createCustomToken($firebaseUid, [
                'user_id' => $user['user_id'],
                'role' => $user['role']
            ]);

            // Update FCM token if provided
            if ($request->has('fcm_token')) {
                $this->firestoreService->updateDocument('users', $user['user_id'], [
                    'fcm_token' => $request->fcm_token
                ]);
                $user['fcm_token'] = $request->fcm_token;
            }

            // Log activity
            $this->activityLogService->log(
                $user['user_id'],
                'user_login',
                'User logged in successfully',
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
                        'address' => $user['address'] ?? null
                    ],
                    'token' => $customToken
                ]
            ]);

        } catch (Exception $e) {
            \Log::error('Login exception:', [
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
     * Get user profile from Firebase token
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            // Get user from middleware (set by FirebaseAuthMiddleware)
            $user = $request->get('user');
            
            \Log::info('Profile request - User from middleware:', $user);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'user' => [
                        'user_id' => $user['user_id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'contact_number' => $user['contact_number'] ?? null,
                        'address' => $user['address'] ?? null,
                        'created_at' => $user['created_at'] ?? null
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user (revoke Firebase token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if ($user) {
                // Revoke Firebase tokens
                $this->firebaseService->revokeRefreshTokens($user['firebase_uid']);
                
                // Clear FCM token
                $this->firestoreService->updateDocument('users', $user['user_id'], [
                    'fcm_token' => null
                ]);

                // Log activity
                $this->activityLogService->log(
                    $user['user_id'],
                    'user_logout',
                    'User logged out successfully',
                    $request->ip(),
                    $request->userAgent()
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Google OAuth login
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'idToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify Google ID token with Firebase
            $verifiedToken = $this->firebaseService->verifyIdToken($request->idToken);

            if (!$verifiedToken['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google token: ' . $verifiedToken['error']
                ], 401);
            }

            $firebaseUid = $verifiedToken['uid'];
            $firebaseUser = $verifiedToken['user'];

            // Check if user exists in Firestore
            $user = $this->firestoreService->findByField('users', 'firebase_uid', $firebaseUid);

            if (!$user) {
                // Create new user from Google account
                $userId = 'user-' . Str::uuid();
                
                $userData = [
                    'user_id' => $userId,
                    'firebase_uid' => $firebaseUid,
                    'name' => $firebaseUser->displayName ?? 'Google User',
                    'email' => $firebaseUser->email,
                    'role' => 'patient',
                    'contact_number' => null,
                    'address' => null,
                    'fcm_token' => null,
                    'email_verified_at' => $firebaseUser->emailVerified ? now() : null,
                    'is_active' => true
                ];

                $documentId = $this->firestoreService->createDocument('users', $userData, $userId);

                if (!$documentId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create user profile'
                    ], 500);
                }

                $user = $userData;

                // Log registration activity
                $this->activityLogService->log(
                    $userId,
                    'google_registration',
                    'User registered via Google OAuth',
                    $request->ip(),
                    $request->userAgent()
                );
            }

            // Generate custom token for API access
            $customToken = $this->firebaseService->createCustomToken($firebaseUid, [
                'user_id' => $user['user_id'],
                'role' => $user['role']
            ]);

            // Update FCM token if provided
            if ($request->has('fcm_token')) {
                $this->firestoreService->updateDocument('users', $user['user_id'], [
                    'fcm_token' => $request->fcm_token
                ]);
                $user['fcm_token'] = $request->fcm_token;
            }

            // Log login activity
            $this->activityLogService->log(
                $user['user_id'],
                'google_login',
                'User logged in via Google OAuth',
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'success' => true,
                'message' => 'Google login successful',
                'data' => [
                    'user' => [
                        'user_id' => $user['user_id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'contact_number' => $user['contact_number'] ?? null,
                        'address' => $user['address'] ?? null
                    ],
                    'token' => $customToken
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forgot password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if user exists in Firestore
            $user = $this->firestoreService->findByField('users', 'email', $request->email);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user found with this email address'
                ], 404);
            }

            // For now, just return success (in production, you'd send an actual email)
            return response()->json([
                'success' => true,
                'message' => 'Password reset instructions have been sent to your email'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process password reset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        \Log::info('UpdateProfile - Request data:', $request->all());
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get user from middleware (Firebase middleware sets this)
            $user = $request->get('user');
            \Log::info('UpdateProfile - User from middleware:', ['user' => $user]);
            
            if (!$user) {
                \Log::error('UpdateProfile - No user found in request');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $updateData = array_filter([
                'name' => $request->name,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
            ], function($value) {
                return $value !== null;
            });

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data to update'
                ], 400);
            }

            // Update in Firestore
            \Log::info('UpdateProfile - Updating user in Firestore:', [
                'user_id' => $user['user_id'],
                'updateData' => $updateData
            ]);
            
            $updated = $this->firestoreService->updateDocument('users', $user['user_id'], $updateData);
            \Log::info('UpdateProfile - Firestore update result:', ['updated' => $updated]);

            if (!$updated) {
                \Log::error('UpdateProfile - Firestore update failed');
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile'
                ], 500);
            }

            // Update Firebase Auth display name if name changed
            if (isset($updateData['name'])) {
                $this->firebaseService->updateUser($user['firebase_uid'], [
                    'displayName' => $updateData['name']
                ]);
            }

            // Get updated user data
            $updatedUser = $this->firestoreService->getDocument('users', $user['user_id']);

            // Log activity
            $this->activityLogService->log(
                $user['user_id'],
                'profile_updated',
                'User profile updated',
                $request->ip(),
                $request->userAgent(),
                ['updated_fields' => array_keys($updateData)]
            );

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'user_id' => $updatedUser['user_id'],
                        'name' => $updatedUser['name'],
                        'email' => $updatedUser['email'],
                        'role' => $updatedUser['role'],
                        'contact_number' => $updatedUser['contact_number'] ?? null,
                        'address' => $updatedUser['address'] ?? null
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
