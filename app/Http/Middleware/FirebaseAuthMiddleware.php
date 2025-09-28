<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use App\DataTransferObjects\ApiError;
use Exception;

class FirebaseAuthMiddleware
{
    private Auth $auth;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
        $this->auth = $factory->createAuth();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $token = $this->extractToken($request);
        
        // Enhanced debugging for mobile token issues
        \Log::info('=== FIREBASE AUTH MIDDLEWARE DEBUG ===');
        \Log::info('Request URL: ' . $request->fullUrl());
        \Log::info('User Agent: ' . $request->header('User-Agent'));
        \Log::info('Authorization Header: ' . ($request->header('Authorization') ? 'Present' : 'Missing'));
        \Log::info('Token extracted: ' . ($token ? 'Yes (length: ' . strlen($token) . ')' : 'No'));
        
        if ($token) {
            \Log::info('Token (first 50 chars): ' . substr($token, 0, 50) . '...');
        }
        
        if (!$token) {
            \Log::error('No authorization token found');
            return response()->json([
                'success' => false,
                'message' => 'Authorization token required'
            ], 401);
        }

        try {
            \Log::info('Attempting to verify Firebase token...');
            
            // Verify Firebase ID token
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
            
            \Log::info('Firebase token verified successfully');
            \Log::info('Firebase UID: ' . $firebaseUid);
            \Log::info('Token claims: ' . json_encode($verifiedIdToken->claims()->all()));
            
            // Get user from Firestore
            $firestoreService = app(\App\Services\FirestoreService::class);
            \Log::info('Looking up user in Firestore with firebase_uid: ' . $firebaseUid);
            
            // Try to get user from Firestore
            $user = null;
            try {
                $user = $firestoreService->findByField('users', 'firebase_uid', $firebaseUid);
                \Log::info('User found in Firestore: ' . json_encode($user));
            } catch (Exception $e) {
                \Log::warning('Firestore lookup failed: ' . $e->getMessage());
            }
            
            if (!$user) {
                \Log::error('User not found in Firestore with firebase_uid: ' . $firebaseUid);
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            \Log::info('User found: ' . json_encode($user));
            
            // Check if user is active
            if (!($user['is_active'] ?? true)) {
                \Log::error('User account is inactive: ' . $firebaseUid);
                return response()->json([
                    'success' => false,
                    'message' => 'User account is inactive'
                ], 403);
            }
            
            // Check role permissions
            if (!empty($roles) && !in_array($user['role'], $roles)) {
                \Log::error('Insufficient permissions. User role: ' . $user['role'] . ', Required roles: ' . implode(', ', $roles));
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }
            
            \Log::info('Authentication successful for user: ' . $firebaseUid);
            
            // Add user to request
            $request->merge(['user' => $user]);
            
            return $next($request);
            
        } catch (Exception $e) {
            \Log::error('Firebase token verification failed');
            \Log::error('Exception type: ' . get_class($e));
            \Log::error('Exception message: ' . $e->getMessage());
            \Log::error('Exception trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token: ' . $e->getMessage()
            ], 401);
        }
    }

    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');
        
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }
        
        return null;
    }
}