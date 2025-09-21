<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use App\DataTransferObjects\ApiResponse;
use App\DataTransferObjects\ApiError;
use Illuminate\Support\Facades\Validator;
use Exception;

class LogController extends Controller
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
     * Get activity logs (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userRole = $request->input('user_role');

            // Only admins can view activity logs
            if ($userRole !== 'admin') {
                return response()->json(
                    ApiResponse::error('Insufficient permissions', null, 403)->toArray(),
                    403
                );
            }

            $limit = $request->input('limit', 50);
            $result = $this->firestoreService->getActivityLogs($limit);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to retrieve activity logs', null, 500)->toArray(),
                    500
                );
            }

            $logs = array_map(fn($log) => $log->toArray(), $result['data']);

            return response()->json(
                ApiResponse::success('Activity logs retrieved successfully', $logs)->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to retrieve activity logs', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Create a new activity log entry
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }

        try {
            $userId = $request->input('firebase_uid');

            $this->activityLogService->log(
                $userId,
                $request->input('action'),
                $request->input('description'),
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Activity log created successfully')->toArray(),
                201
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to create activity log', null, 500)->toArray(),
                500
            );
        }
    }
}
