<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseService;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use Exception;
use Carbon\Carbon;

class AuthController extends Controller
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
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
            // Create user in Firebase Auth
            $result = $this->firebaseService->createUser(
                $request->email,
                $request->password,
                $request->name,
                'patient'
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user in Firebase',
                    'error' => $result['error']
                ], 500);
            }

            // Store user data in Firestore
            $userData = [
                'firebase_uid' => $result['uid'],
                'name' => $request->name,
                'email' => $request->email,
                'role' => 'patient',
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'created_at' => Carbon::now()->toISOString(),
                'updated_at' => Carbon::now()->toISOString(),
            ];

            $documentId = $this->firestoreService->createDocument('users', $userData, $result['uid']);

            if (!$documentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to store user data in Firestore'
                ], 500);
            }

            // Log activity
            $this->activityLogService->log(
                $result['uid'],
                'user_registered',
                'User registered successfully',
                ['email' => $request->email]
            );

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'uid' => $result['uid'],
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'role' => $userData['role'],
                    'contact_number' => $userData['contact_number'],
                    'address' => $userData['address'],
                ]
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user with Firebase ID token
     */
    public function login(Request $request): JsonResponse
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
            // Verify Firebase ID token
            $verificationResult = $this->firebaseService->verifyIdToken($request->idToken);

            if (!$verificationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'error' => $verificationResult['error']
                ], 401);
            }

            $uid = $verificationResult['uid'];

            // Get user data from Firestore
            $userData = $this->firestoreService->getDocument('users', $uid);

            if (!$userData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found in Firestore'
                ], 404);
            }

            // Log activity
            $this->activityLogService->log(
                $uid,
                'user_login',
                'User logged in successfully',
                ['email' => $userData['email']]
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $userData,
                'token' => $request->idToken
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $uid = $request->input('firebase_uid');

            if (!$uid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase UID is required'
                ], 400);
            }

            // Get user data from Firestore
            $userData = $this->firestoreService->getDocument('users', $uid);

            if (!$userData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => $userData
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Google OAuth login with Firebase
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
            $result = $this->firebaseService->verifyIdToken($request->idToken);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google token',
                    'error' => $result['error']
                ], 401);
            }

            $uid = $result['uid'];
            $firebaseUser = $result['user'];

            // Check if user exists in Firestore
            $firestore = $this->firebaseService->getFirestore();
            $userDoc = $firestore->database()->collection('users')->document($uid)->snapshot();

            if (!$userDoc->exists()) {
                // Create new user from Google data
                $userData = [
                    'firebase_uid' => $uid,
                    'name' => $firebaseUser->displayName ?? 'Google User',
                    'email' => $firebaseUser->email,
                    'role' => 'patient',
                    'contact_number' => null,
                    'address' => null,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString(),
                ];

                $firestore->database()->collection('users')->document($uid)->set($userData);
            } else {
                $userData = $userDoc->data();
            }

            return response()->json([
                'success' => true,
                'message' => 'Google login successful',
                'data' => $userData,
                'token' => $request->idToken
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $uid = $request->input('firebase_uid');

            if ($uid) {
                // Log activity
                $this->activityLogService->log(
                    $uid,
                    'user_logout',
                    'User logged out successfully'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forgot password - handled by Firebase Auth
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
            // Firebase handles password reset via client SDK
            // This endpoint confirms the request was received
            return response()->json([
                'success' => true,
                'message' => 'Password reset email will be sent if the email exists'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
