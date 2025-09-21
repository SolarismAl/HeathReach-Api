<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Exception;

class UserController extends Controller
{
    /**
     * Helper method to authenticate user from token
     */
    private function authenticateUser(Request $request): array
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return ['success' => false, 'message' => 'Unauthorized', 'code' => 401];
        }

        $token = substr($authHeader, 7);
        $decoded = base64_decode($token);
        $parts = explode(':', $decoded);
        
        if (count($parts) !== 2) {
            return ['success' => false, 'message' => 'Invalid token', 'code' => 401];
        }

        $userId = $parts[0];
        $user = User::where('user_id', $userId)->first();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found', 'code' => 404];
        }

        return ['success' => true, 'user' => $user];
    }

    /**
     * Get all users (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $auth = $this->authenticateUser($request);
            if (!$auth['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $auth['message']
                ], $auth['code']);
            }

            $currentUser = $auth['user'];
            if ($currentUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }

            $users = User::select('user_id', 'name', 'email', 'role', 'contact_number', 'address', 'created_at')
                         ->orderBy('created_at', 'desc')
                         ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user role (admin only)
     */
    public function updateRole(Request $request, string $userId): JsonResponse
    {
        try {
            $auth = $this->authenticateUser($request);
            if (!$auth['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $auth['message']
                ], $auth['code']);
            }

            $currentUser = $auth['user'];
            if ($currentUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|in:patient,health_worker,admin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('user_id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->role = $request->role;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully',
                'data' => [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'contact_number' => $user->contact_number,
                    'address' => $user->address,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $auth = $this->authenticateUser($request);
            if (!$auth['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $auth['message']
                ], $auth['code']);
            }

            $user = $auth['user'];

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
                'contact_number' => 'sometimes|nullable|string|max:20',
                'address' => 'sometimes|nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->has('name')) {
                $user->name = $request->name;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('contact_number')) {
                $user->contact_number = $request->contact_number;
            }
            if ($request->has('address')) {
                $user->address = $request->address;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'contact_number' => $user->contact_number,
                    'address' => $user->address,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
