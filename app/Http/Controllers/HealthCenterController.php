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
            $userRole = $request->input('user_role', 'unknown');
            $firebaseUid = $request->input('firebase_uid', 'unknown');
            
            \Log::info('=== HEALTH CENTERS INDEX ===');
            \Log::info('User Role: ' . $userRole);
            \Log::info('Firebase UID: ' . $firebaseUid);
            \Log::info('Request IP: ' . $request->ip());
            \Log::info('User Agent: ' . $request->userAgent());
            
            $result = $this->firestoreService->getHealthCenters();
            
            \Log::info('Firestore Query Result: ' . json_encode([
                'success' => $result['success'],
                'data_count' => isset($result['data']) ? count($result['data']) : 0
            ]));

            if (!$result['success']) {
                \Log::error('Failed to retrieve health centers from Firestore');
                return response()->json(
                    ApiResponse::error('Failed to retrieve health centers', null, 500)->toArray(),
                    500
                );
            }

            $healthCenters = array_map(fn($center) => $center->toArray(), $result['data']);
            
            \Log::info('Health Centers Retrieved: ' . count($healthCenters) . ' centers');
            foreach ($healthCenters as $index => $center) {
                \Log::info("Health Center #{$index}: {$center['name']} (ID: {$center['health_center_id']}, Active: " . ($center['is_active'] ? 'Yes' : 'No') . ')');
            }
            
            // If this is a health worker, also log their services count
            if ($userRole === 'health_worker') {
                try {
                    $servicesResult = $this->firestoreService->getServices();
                    if ($servicesResult['success']) {
                        $allServices = $servicesResult['data'];
                        \Log::info('=== HEALTH WORKER SERVICES ANALYSIS ===');
                        foreach ($healthCenters as $center) {
                            $centerServices = array_filter($allServices, function($service) use ($center) {
                                return $service->health_center_id === $center['health_center_id'];
                            });
                            $servicesCount = count($centerServices);
                            \Log::info("Health Center '{$center['name']}' has {$servicesCount} services:");
                            foreach ($centerServices as $service) {
                                \Log::info("  - Service: {$service->service_name} (Price: $" . ($service->price ?? 'N/A') . ", Duration: " . ($service->duration_minutes ?? 'N/A') . " min, Active: " . ($service->is_active ? 'Yes' : 'No') . ')');
                            }
                        }
                        \Log::info('=== END SERVICES ANALYSIS ===');
                    }
                } catch (Exception $e) {
                    \Log::warning('Could not load services for analysis: ' . $e->getMessage());
                }
            }

            // Log the activity
            $this->activityLogService->log(
                $firebaseUid,
                'health_centers_viewed',
                'Viewed health centers list',
                $request->ip(),
                $request->userAgent()
            );
            
            \Log::info('=== END HEALTH CENTERS INDEX ===');

            return response()->json(
                ApiResponse::success('Health centers retrieved successfully', $healthCenters)->toArray()
            );

        } catch (Exception $e) {
            \Log::error('Exception in HealthCenterController@index: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
        $userRole = $request->input('user_role', 'unknown');
        $firebaseUid = $request->input('firebase_uid', 'unknown');
        
        \Log::info('=== CREATE HEALTH CENTER ===');
        \Log::info('User Role: ' . $userRole);
        \Log::info('Firebase UID: ' . $firebaseUid);
        \Log::info('Request Data: ' . json_encode($request->all()));
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed: ' . json_encode($validator->errors()->toArray()));
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }
        
        \Log::info('Validation passed successfully');

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
            
            \Log::info('Health Center Data Created: ' . json_encode($healthCenterData->toArray()));

            $result = $this->firestoreService->createHealthCenter($healthCenterData);
            
            \Log::info('Firestore Create Result: ' . json_encode([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'No message'
            ]));

            if (!$result['success']) {
                \Log::error('Failed to create health center in Firestore');
                return response()->json(
                    ApiResponse::error('Failed to create health center', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $firebaseUid,
                'health_center_created',
                "Created health center: {$healthCenterData->name}",
                $request->ip(),
                $request->userAgent()
            );
            
            \Log::info('Health center created successfully: ' . $healthCenterData->name);
            \Log::info('=== END CREATE HEALTH CENTER ===');

            return response()->json(
                ApiResponse::success('Health center created successfully', $healthCenterData->toArray())->toArray(),
                201
            );

        } catch (Exception $e) {
            \Log::error('Exception in HealthCenterController@store: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
        $userRole = $request->input('user_role', 'unknown');
        $firebaseUid = $request->input('firebase_uid', 'unknown');
        
        \Log::info('=== UPDATE HEALTH CENTER ===');
        \Log::info('User Role: ' . $userRole);
        \Log::info('Firebase UID: ' . $firebaseUid);
        \Log::info('Health Center ID: ' . $id);
        \Log::info('Update Data: ' . json_encode($request->all()));
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed: ' . json_encode($validator->errors()->toArray()));
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }
        
        \Log::info('Validation passed successfully');

        try {
            $updateData = $request->only(['name', 'address', 'contact_number', 'email', 'description', 'is_active']);
            $updateData['updated_at'] = now()->toISOString();
            
            \Log::info('Processed Update Data: ' . json_encode($updateData));

            $result = $this->firestoreService->updateHealthCenter($id, $updateData);
            
            \Log::info('Firestore Update Result: ' . json_encode([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'No message'
            ]));

            if (!$result['success']) {
                \Log::error('Failed to update health center in Firestore');
                return response()->json(
                    ApiResponse::error('Failed to update health center', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $firebaseUid,
                'health_center_updated',
                "Updated health center: {$id}",
                $request->ip(),
                $request->userAgent()
            );
            
            \Log::info('Health center updated successfully: ' . $id);
            \Log::info('=== END UPDATE HEALTH CENTER ===');

            return response()->json(
                ApiResponse::success('Health center updated successfully')->toArray()
            );

        } catch (Exception $e) {
            \Log::error('Exception in HealthCenterController@update: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
        $userRole = $request->input('user_role', 'unknown');
        $firebaseUid = $request->input('firebase_uid', 'unknown');
        
        \Log::info('=== DELETE HEALTH CENTER ===');
        \Log::info('User Role: ' . $userRole);
        \Log::info('Firebase UID: ' . $firebaseUid);
        \Log::info('Health Center ID to delete: ' . $id);
        
        try {
            $result = $this->firestoreService->deleteHealthCenter($id);
            
            \Log::info('Firestore Delete Result: ' . json_encode([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'No message'
            ]));

            if (!$result['success']) {
                \Log::error('Failed to delete health center from Firestore');
                return response()->json(
                    ApiResponse::error('Failed to delete health center', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $firebaseUid,
                'health_center_deleted',
                "Deleted health center: {$id}",
                $request->ip(),
                $request->userAgent()
            );
            
            \Log::info('Health center deleted successfully: ' . $id);
            \Log::info('=== END DELETE HEALTH CENTER ===');

            return response()->json(
                ApiResponse::success('Health center deleted successfully')->toArray()
            );

        } catch (Exception $e) {
            \Log::error('Exception in HealthCenterController@destroy: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(
                ApiResponse::error('Failed to delete health center', null, 500)->toArray(),
                500
            );
        }
    }
}
