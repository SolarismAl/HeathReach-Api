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
            $user = $this->firestoreService->findByField('users', 'firebase_uid', $firebaseUid);

            if (!$user) {
                \Log::error('User not found in Firestore for Firebase UID:', ['uid' => $firebaseUid]);
                
                // Try to find by email as fallback
                $user = $this->firestoreService->findByField('users', 'email', $email);
                
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User profile not found'
                    ], 404);
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
}
