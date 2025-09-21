<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use App\DataTransferObjects\ApiResponse;
use Exception;

class AdminController extends Controller
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
     * Get admin statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $result = $this->firestoreService->getAdminStats();

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to retrieve admin statistics', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'admin_stats_viewed',
                'Viewed admin statistics dashboard',
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Admin statistics retrieved successfully', $result['data'])->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to retrieve admin statistics', null, 500)->toArray(),
                500
            );
        }
    }
}
