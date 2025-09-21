<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use App\DataTransferObjects\DeviceTokenData;
use App\DataTransferObjects\CreateDeviceTokenData;
use App\DataTransferObjects\ApiResponse;
use App\DataTransferObjects\ApiError;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class DeviceTokenController extends Controller
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
     * Save device token for push notifications
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'token' => 'required|string',
            'platform' => 'required|string|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }

        try {
            $currentUserId = $request->input('firebase_uid');
            $requestedUserId = $request->input('user_id');

            // Users can only save tokens for themselves
            if ($currentUserId !== $requestedUserId) {
                return response()->json(
                    ApiResponse::error('Unauthorized access', null, 403)->toArray(),
                    403
                );
            }

            $deviceTokenData = new DeviceTokenData(
                id: Str::uuid()->toString(),
                user_id: $requestedUserId,
                token: $request->input('token'),
                platform: $request->input('platform'),
                device_name: $request->input('device_name'),
                created_at: now()->toISOString(),
                updated_at: now()->toISOString()
            );

            $result = $this->firestoreService->saveDeviceToken($deviceTokenData);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to save device token', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $currentUserId,
                'device_token_saved',
                "Saved device token for {$deviceTokenData->platform}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Device token saved successfully', $deviceTokenData->toArray())->toArray(),
                201
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to save device token', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Get all device tokens (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userRole = $request->input('user_role');

            // Only admins can view all device tokens
            if ($userRole !== 'admin') {
                return response()->json(
                    ApiResponse::error('Insufficient permissions', null, 403)->toArray(),
                    403
                );
            }

            $result = $this->firestoreService->getAllDeviceTokens();

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to retrieve device tokens', null, 500)->toArray(),
                    500
                );
            }

            $tokens = array_map(fn($token) => $token->toArray(), $result['data']);

            return response()->json(
                ApiResponse::success('Device tokens retrieved successfully', $tokens)->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to retrieve device tokens', null, 500)->toArray(),
                500
            );
        }
    }
}
