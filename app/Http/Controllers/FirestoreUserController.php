<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use Exception;

class FirestoreUserController extends Controller
{
    private FirestoreService $firestoreService;
    private ActivityLogService $activityLogService;

    public function __construct(
        FirestoreService $firestoreService,
        ActivityLogService $activityLogService
    ) {
        $this->firestoreService = $firestoreService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get all users (Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if ($user['role'] !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            // Get all users from Firestore
            $users = $this->firestoreService->getCollection('users', [], null, 'created_at', 'desc');

            // Remove sensitive data
            $users = array_map(function($user) {
                unset($user['firebase_uid']);
                return $user;
            }, $users);

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user role (Admin only)
     */
    public function updateRole(Request $request, string $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:patient,health_worker,admin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $currentUser = $request->user();
            
            if ($currentUser['role'] !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            // Get target user
            $targetUser = $this->firestoreService->getDocument('users', $userId);
            
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Prevent admin from changing their own role
            if ($currentUser['user_id'] === $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change your own role'
                ], 400);
            }

            $newRole = $request->role;
            $oldRole = $targetUser['role'];

            // Update role in Firestore
            $updated = $this->firestoreService->updateDocument('users', $userId, [
                'role' => $newRole
            ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user role'
                ], 500);
            }

            // Log activity
            $this->activityLogService->log(
                $currentUser['user_id'],
                'user_role_updated',
                "Changed user role from {$oldRole} to {$newRole}",
                $request->ip(),
                $request->userAgent(),
                [
                    'target_user_id' => $userId,
                    'old_role' => $oldRole,
                    'new_role' => $newRole
                ]
            );

            // Get updated user data
            $updatedUser = $this->firestoreService->getDocument('users', $userId);
            unset($updatedUser['firebase_uid']);

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully',
                'data' => $updatedUser
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics (Admin only)
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if ($user['role'] !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            // Get user counts by role
            $allUsers = $this->firestoreService->getCollection('users');
            
            $stats = [
                'total_users' => count($allUsers),
                'patients' => count(array_filter($allUsers, fn($u) => $u['role'] === 'patient')),
                'health_workers' => count(array_filter($allUsers, fn($u) => $u['role'] === 'health_worker')),
                'admins' => count(array_filter($allUsers, fn($u) => $u['role'] === 'admin')),
                'active_users' => count(array_filter($allUsers, fn($u) => $u['is_active'] ?? true))
            ];

            return response()->json([
                'success' => true,
                'message' => 'User statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate user account (Admin only)
     */
    public function deactivateUser(Request $request, string $userId): JsonResponse
    {
        try {
            $currentUser = $request->user();
            
            if ($currentUser['role'] !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            // Get target user
            $targetUser = $this->firestoreService->getDocument('users', $userId);
            
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Prevent admin from deactivating themselves
            if ($currentUser['user_id'] === $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate your own account'
                ], 400);
            }

            // Update user status in Firestore
            $updated = $this->firestoreService->updateDocument('users', $userId, [
                'is_active' => false
            ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to deactivate user'
                ], 500);
            }

            // Log activity
            $this->activityLogService->log(
                $currentUser['user_id'],
                'user_deactivated',
                "Deactivated user account",
                $request->ip(),
                $request->userAgent(),
                ['target_user_id' => $userId]
            );

            return response()->json([
                'success' => true,
                'message' => 'User account deactivated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivate user account (Admin only)
     */
    public function reactivateUser(Request $request, string $userId): JsonResponse
    {
        try {
            $currentUser = $request->user();
            
            if ($currentUser['role'] !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            // Get target user
            $targetUser = $this->firestoreService->getDocument('users', $userId);
            
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Update user status in Firestore
            $updated = $this->firestoreService->updateDocument('users', $userId, [
                'is_active' => true
            ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reactivate user'
                ], 500);
            }

            // Log activity
            $this->activityLogService->log(
                $currentUser['user_id'],
                'user_reactivated',
                "Reactivated user account",
                $request->ip(),
                $request->userAgent(),
                ['target_user_id' => $userId]
            );

            return response()->json([
                'success' => true,
                'message' => 'User account reactivated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reactivate user: ' . $e->getMessage()
            ], 500);
        }
    }
}
