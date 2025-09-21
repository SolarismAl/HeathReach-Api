<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use App\DataTransferObjects\HealthCenterData;
use App\DataTransferObjects\ApiResponse;
use App\DataTransferObjects\ApiError;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class HealthCenterController extends Controller
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
     * Get all health centers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->firestoreService->getHealthCenters();

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to retrieve health centers', null, 500)->toArray(),
                    500
                );
            }

            $healthCenters = array_map(fn($center) => $center->toArray(), $result['data']);

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'health_centers_viewed',
                'Viewed health centers list',
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Health centers retrieved successfully', $healthCenters)->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to retrieve health centers', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Create a new health center (admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }

        try {
            $healthCenterData = new HealthCenterData(
                health_center_id: Str::uuid()->toString(),
                name: $request->input('name'),
                address: $request->input('address'),
                contact_number: $request->input('contact_number'),
                email: $request->input('email'),
                description: $request->input('description'),
                is_active: $request->input('is_active', true),
                created_at: now()->toISOString(),
                updated_at: now()->toISOString()
            );

            $result = $this->firestoreService->createHealthCenter($healthCenterData);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to create health center', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'health_center_created',
                "Created health center: {$healthCenterData->name}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Health center created successfully', $healthCenterData->toArray())->toArray(),
                201
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to create health center', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Get a specific health center
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->firestoreService->getHealthCenter($id);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Health center not found', null, 404)->toArray(),
                    404
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'health_center_viewed',
                "Viewed health center: {$id}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Health center retrieved successfully', $result['data']->toArray())->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to retrieve health center', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Update a health center
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }

        try {
            $updateData = $request->only(['name', 'address', 'contact_number', 'email', 'description', 'is_active']);
            $updateData['updated_at'] = now()->toISOString();

            $result = $this->firestoreService->updateHealthCenter($id, $updateData);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to update health center', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'health_center_updated',
                "Updated health center: {$id}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Health center updated successfully')->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to update health center', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Delete a health center
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->firestoreService->deleteHealthCenter($id);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to delete health center', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'health_center_deleted',
                "Deleted health center: {$id}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Health center deleted successfully')->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to delete health center', null, 500)->toArray(),
                500
            );
        }
    }
}
