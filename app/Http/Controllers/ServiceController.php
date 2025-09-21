<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use App\DataTransferObjects\ServiceData;
use App\DataTransferObjects\CreateServiceData;
use App\DataTransferObjects\ApiResponse;
use App\DataTransferObjects\ApiError;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class ServiceController extends Controller
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
     * Get all services
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->firestoreService->getServices();

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to retrieve services', null, 500)->toArray(),
                    500
                );
            }

            $services = array_map(fn($service) => $service->toArray(), $result['data']);

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'services_viewed',
                'Viewed services list',
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Services retrieved successfully', $services)->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to retrieve services', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Create a new service
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'health_center_id' => 'required|string',
            'service_name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'schedule' => 'sometimes|array',
            'schedule.*.day' => 'required_with:schedule|string',
            'schedule.*.start_time' => 'required_with:schedule|string',
            'schedule.*.end_time' => 'required_with:schedule|string',
        ]);

        if ($validator->fails()) {
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }

        try {
            $serviceData = new ServiceData(
                service_id: Str::uuid()->toString(),
                health_center_id: $request->input('health_center_id'),
                service_name: $request->input('service_name'),
                description: $request->input('description'),
                duration_minutes: $request->input('duration_minutes'),
                price: $request->input('price'),
                is_active: $request->input('is_active', true),
                schedule: $request->input('schedule', []),
                created_at: now()->toISOString(),
                updated_at: now()->toISOString()
            );

            $result = $this->firestoreService->createService($serviceData);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to create service', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'service_created',
                "Created service: {$serviceData->service_name}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Service created successfully', $serviceData->toArray())->toArray(),
                201
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to create service', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Get a specific service
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->firestoreService->getService($id);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Service not found', null, 404)->toArray(),
                    404
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'service_viewed',
                "Viewed service: {$id}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Service retrieved successfully', $result['data']->toArray())->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to retrieve service', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Update a service
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'health_center_id' => 'sometimes|string',
            'service_name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'schedule' => 'sometimes|array',
            'schedule.*.day' => 'required_with:schedule|string',
            'schedule.*.start_time' => 'required_with:schedule|string',
            'schedule.*.end_time' => 'required_with:schedule|string',
        ]);

        if ($validator->fails()) {
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }

        try {
            $updateData = $request->only(['health_center_id', 'service_name', 'description', 'duration_minutes', 'price', 'is_active', 'schedule']);
            $updateData['updated_at'] = now()->toISOString();

            $result = $this->firestoreService->updateService($id, $updateData);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to update service', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'service_updated',
                "Updated service: {$id}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Service updated successfully')->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to update service', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Delete a service
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->firestoreService->deleteService($id);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to delete service', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $request->input('firebase_uid'),
                'service_deleted',
                "Deleted service: {$id}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Service deleted successfully')->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to delete service', null, 500)->toArray(),
                500
            );
        }
    }
}
