<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FirestoreService;
use App\Services\FirebaseService;
use App\Services\ActivityLogService;
use App\DataTransferObjects\NotificationData;
use App\DataTransferObjects\CreateNotificationData;
use App\DataTransferObjects\ApiResponse;
use App\DataTransferObjects\ApiError;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class NotificationController extends Controller
{
    private FirestoreService $firestoreService;
    private FirebaseService $firebaseService;
    private ActivityLogService $activityLogService;

    public function __construct(
        FirestoreService $firestoreService,
        FirebaseService $firebaseService,
        ActivityLogService $activityLogService
    ) {
        $this->firestoreService = $firestoreService;
        $this->firebaseService = $firebaseService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get user from middleware
            $user = $request->get('user');
            $userId = $user['user_id'] ?? $user['firebase_uid'] ?? null;
            $userRole = $user['role'] ?? 'patient';
            
            \Log::info('=== NOTIFICATION INDEX REQUEST ===');
            \Log::info('Full user object from middleware:', $user);
            \Log::info('Extracted user_id:', ['user_id' => $userId, 'role' => $userRole]);

            // Admins can see all notifications, others see only their own
            if ($userRole === 'admin' && $request->has('all')) {
                // Get all notifications (admin only)
                $result = $this->firestoreService->getAllNotifications();
                \Log::info('Fetching ALL notifications for admin');
            } else {
                \Log::info('Fetching notifications for specific user:', ['user_id' => $userId]);
                
                // Try multiple user ID formats to handle inconsistencies
                $result = $this->firestoreService->getNotificationsByUser($userId);
                \Log::info('Primary notification query result:', [
                    'success' => $result['success'], 
                    'count' => count($result['data'] ?? []),
                    'query_user_id' => $userId
                ]);
                
                // If no results and we have both user_id and firebase_uid, try the other format
                if (empty($result['data']) && isset($user['user_id']) && isset($user['firebase_uid']) && $user['user_id'] !== $user['firebase_uid']) {
                    $alternateUserId = ($userId === $user['user_id']) ? $user['firebase_uid'] : $user['user_id'];
                    \Log::info('Trying alternate user ID format:', ['alternate_user_id' => $alternateUserId]);
                    
                    $alternateResult = $this->firestoreService->getNotificationsByUser($alternateUserId);
                    if (!empty($alternateResult['data'])) {
                        \Log::info('Found notifications with alternate user ID:', [
                            'alternate_user_id' => $alternateUserId,
                            'count' => count($alternateResult['data'])
                        ]);
                        $result = $alternateResult;
                    }
                }
                
                \Log::info('Final notification query result:', [
                    'success' => $result['success'], 
                    'count' => count($result['data'] ?? []),
                    'sample_notification' => !empty($result['data']) ? $result['data'][0] : null
                ]);
            }

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to retrieve notifications', null, 500)->toArray(),
                    500
                );
            }

            $notifications = array_map(fn($notification) => $notification->toArray(), $result['data']);

            return response()->json(
                ApiResponse::success('Notifications retrieved successfully', $notifications)->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to retrieve notifications', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Create and send a new notification
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|string|in:appointment,service,admin,general',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }

        try {
            $currentUserId = $request->input('firebase_uid');
            $userRole = $request->input('user_role');

            // Only health workers and admins can send notifications
            if (!in_array($userRole, ['health_worker', 'admin'])) {
                return response()->json(
                    ApiResponse::error('Insufficient permissions to send notifications', null, 403)->toArray(),
                    403
                );
            }

            $notificationData = new NotificationData(
                notification_id: Str::uuid()->toString(),
                user_id: $request->input('user_id'),
                title: $request->input('title'),
                message: $request->input('message'),
                date_sent: now()->toISOString(),
                is_read: false,
                type: $request->input('type'),
                created_at: now()->toISOString(),
                updated_at: now()->toISOString()
            );

            // Save notification to Firestore
            $result = $this->firestoreService->createNotification($notificationData);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to create notification', null, 500)->toArray(),
                    500
                );
            }

            // Get user's FCM tokens and send push notification
            $tokensResult = $this->firestoreService->getDeviceTokensByUser($request->input('user_id'));
            
            if ($tokensResult['success'] && !empty($tokensResult['data'])) {
                $tokens = array_map(fn($tokenData) => $tokenData->token, $tokensResult['data']);
                
                $fcmResult = $this->firebaseService->sendMulticastNotification(
                    $tokens,
                    $request->input('title'),
                    $request->input('message'),
                    $request->input('data', [])
                );

                // Log FCM result but don't fail the request if FCM fails
                if (!$fcmResult['success']) {
                    error_log("FCM notification failed: " . $fcmResult['error']);
                }
            }

            // Log the activity
            $this->activityLogService->log(
                $currentUserId,
                'notification_sent',
                "Sent notification: {$notificationData->title}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Notification sent successfully', $notificationData->toArray())->toArray(),
                201
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to send notification', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        try {
            $userId = $request->input('firebase_uid');

            // Update notification to mark as read
            $result = $this->firestoreService->updateNotification($id, [
                'is_read' => true,
                'updated_at' => now()->toISOString()
            ]);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to mark notification as read', null, 500)->toArray(),
                    500
                );
            }

            return response()->json(
                ApiResponse::success('Notification marked as read')->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to update notification', null, 500)->toArray(),
                500
            );
        }
    }
}
