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
            $userRole = $request->input('user_role', 'unknown');
            $firebaseUid = $request->input('firebase_uid', 'unknown');
            
            \Log::info('=== SERVICES INDEX ===');
            \Log::info('User Role: ' . $userRole);
            \Log::info('Firebase UID: ' . $firebaseUid);
            \Log::info('Request IP: ' . $request->ip());
            \Log::info('User Agent: ' . $request->userAgent());
            
            $result = $this->firestoreService->getServices();
            
            \Log::info('Firestore Query Result: ' . json_encode([
                'success' => $result['success'],
                'data_count' => isset($result['data']) ? count($result['data']) : 0
            ]));

            if (!$result['success']) {
                \Log::error('Failed to retrieve services from Firestore');
                return response()->json(
                    ApiResponse::error('Failed to retrieve services', null, 500)->toArray(),
                    500
                );
            }

            $services = array_map(fn($service) => $service->toArray(), $result['data']);
            
            \Log::info('Services Retrieved: ' . count($services) . ' services');
            foreach ($services as $index => $service) {
                \Log::info("Service #{$index}: {$service['service_name']} (ID: {$service['service_id']}, Health Center: {$service['health_center_id']}, Price: $" . ($service['price'] ?? 'N/A') . ', Active: ' . ($service['is_active'] ? 'Yes' : 'No') . ')');
            }
            
            // If this is a health worker, also log health center relationships
            if ($userRole === 'health_worker') {
                try {
                    $centersResult = $this->firestoreService->getHealthCenters();
                    if ($centersResult['success']) {
                        $allCenters = $centersResult['data'];
                        \Log::info('=== HEALTH WORKER CENTER-SERVICE RELATIONSHIPS ===');
                        
                        // Group services by health center
                        $servicesByCenter = [];
                        foreach ($services as $service) {
                            $centerId = $service['health_center_id'];
                            if (!isset($servicesByCenter[$centerId])) {
                                $servicesByCenter[$centerId] = [];
                            }
                            $servicesByCenter[$centerId][] = $service;
                        }
                        
                        // Log each health center and its services
                        foreach ($allCenters as $center) {
                            $centerArray = $center->toArray();
                            $centerId = $centerArray['health_center_id'];
                            $centerServices = $servicesByCenter[$centerId] ?? [];
                            $servicesCount = count($centerServices);
                            
                            \Log::info("Health Center: {$centerArray['name']} (ID: {$centerId})");
                            \Log::info("  - Status: " . ($centerArray['is_active'] ? 'Active' : 'Inactive'));
                            \Log::info("  - Services Count: {$servicesCount}");
                            
                            if ($servicesCount > 0) {
                                foreach ($centerServices as $service) {
                                    \Log::info("    * {$service['service_name']} - $" . ($service['price'] ?? 'N/A') . " (" . ($service['duration_minutes'] ?? 'N/A') . " min) [" . ($service['is_active'] ? 'Active' : 'Inactive') . "]");
                                }
                            } else {
                                \Log::info("    * No services found for this health center");
                            }
                        }
                        
                        // Log orphaned services (services without valid health center)
                        $validCenterIds = array_map(function($center) {
                            return $center->toArray()['health_center_id'];
                        }, $allCenters);
                        
                        $orphanedServices = array_filter($services, function($service) use ($validCenterIds) {
                            return !in_array($service['health_center_id'], $validCenterIds);
                        });
                        
                        if (count($orphanedServices) > 0) {
                            \Log::warning('=== ORPHANED SERVICES (No Valid Health Center) ===');
                            foreach ($orphanedServices as $service) {
                                \Log::warning("Orphaned Service: {$service['service_name']} (Health Center ID: {$service['health_center_id']})");
                            }
                        }
                        
                        \Log::info('=== END CENTER-SERVICE RELATIONSHIPS ===');
                    }
                } catch (Exception $e) {
                    \Log::warning('Could not load health centers for relationship analysis: ' . $e->getMessage());
                }
            }

            // Log the activity
            $this->activityLogService->log(
                $firebaseUid,
                'services_viewed',
                'Viewed services list',
                $request->ip(),
                $request->userAgent()
            );
            
            \Log::info('=== END SERVICES INDEX ===');

            return response()->json(
                ApiResponse::success('Services retrieved successfully', $services)->toArray()
            );

        } catch (Exception $e) {
            \Log::error('Exception in ServiceController@index: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
        $userRole = $request->input('user_role', 'unknown');
        $firebaseUid = $request->input('firebase_uid', 'unknown');
        
        \Log::info('=== CREATE SERVICE ===');
        \Log::info('User Role: ' . $userRole);
        \Log::info('Firebase UID: ' . $firebaseUid);
        \Log::info('Request Data: ' . json_encode($request->all()));
        
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
            \Log::error('Validation failed: ' . json_encode($validator->errors()->toArray()));
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }
        
        \Log::info('Validation passed successfully');

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
            
            \Log::info('Service Data Created: ' . json_encode($serviceData->toArray()));
            
            // Log health center relationship for health workers
            if ($userRole === 'health_worker') {
                try {
                    $centerResult = $this->firestoreService->getHealthCenter($serviceData->health_center_id);
                    if ($centerResult['success']) {
                        $centerData = $centerResult['data']->toArray();
                        \Log::info('=== SERVICE-CENTER RELATIONSHIP ===');
                        \Log::info("Creating service '{$serviceData->service_name}' for health center:");
                        \Log::info("  - Center Name: {$centerData['name']}");
                        \Log::info("  - Center ID: {$centerData['health_center_id']}");
                        \Log::info("  - Center Status: " . ($centerData['is_active'] ? 'Active' : 'Inactive'));
                        \Log::info("  - Service Price: $" . ($serviceData->price ?? 'N/A'));
                        \Log::info("  - Service Duration: " . ($serviceData->duration_minutes ?? 'N/A') . " minutes");
                        \Log::info('=== END RELATIONSHIP ===');
                    } else {
                        \Log::warning("Health center not found for ID: {$serviceData->health_center_id}");
                    }
                } catch (Exception $e) {
                    \Log::warning('Could not verify health center relationship: ' . $e->getMessage());
                }
            }

            $result = $this->firestoreService->createService($serviceData);
            
            \Log::info('Firestore Create Result: ' . json_encode([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'No message'
            ]));

            if (!$result['success']) {
                \Log::error('Failed to create service in Firestore');
                return response()->json(
                    ApiResponse::error('Failed to create service', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $firebaseUid,
                'service_created',
                "Created service: {$serviceData->service_name}",
                $request->ip(),
                $request->userAgent()
            );
            
            \Log::info('Service created successfully: ' . $serviceData->service_name);
            \Log::info('=== END CREATE SERVICE ===');

            return response()->json(
                ApiResponse::success('Service created successfully', $serviceData->toArray())->toArray(),
                201
            );

        } catch (Exception $e) {
            \Log::error('Exception in ServiceController@store: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
        $userRole = $request->input('user_role', 'unknown');
        $firebaseUid = $request->input('firebase_uid', 'unknown');
        
        \Log::info('=== UPDATE SERVICE ===');
        \Log::info('User Role: ' . $userRole);
        \Log::info('Firebase UID: ' . $firebaseUid);
        \Log::info('Service ID: ' . $id);
        \Log::info('Update Data: ' . json_encode($request->all()));
        
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
            \Log::error('Validation failed: ' . json_encode($validator->errors()->toArray()));
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }
        
        \Log::info('Validation passed successfully');

        try {
            $updateData = $request->only(['health_center_id', 'service_name', 'description', 'duration_minutes', 'price', 'is_active', 'schedule']);
            $updateData['updated_at'] = now()->toISOString();
            
            \Log::info('Processed Update Data: ' . json_encode($updateData));

            $result = $this->firestoreService->updateService($id, $updateData);
            
            \Log::info('Firestore Update Result: ' . json_encode([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'No message'
            ]));

            if (!$result['success']) {
                \Log::error('Failed to update service in Firestore');
                return response()->json(
                    ApiResponse::error('Failed to update service', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $firebaseUid,
                'service_updated',
                "Updated service: {$id}",
                $request->ip(),
                $request->userAgent()
            );
            
            \Log::info('Service updated successfully: ' . $id);
            \Log::info('=== END UPDATE SERVICE ===');

            return response()->json(
                ApiResponse::success('Service updated successfully')->toArray()
            );

        } catch (Exception $e) {
            \Log::error('Exception in ServiceController@update: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
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
        $userRole = $request->input('user_role', 'unknown');
        $firebaseUid = $request->input('firebase_uid', 'unknown');
        
        \Log::info('=== DELETE SERVICE ===');
        \Log::info('User Role: ' . $userRole);
        \Log::info('Firebase UID: ' . $firebaseUid);
        \Log::info('Service ID to delete: ' . $id);
        
        try {
            $result = $this->firestoreService->deleteService($id);
            
            \Log::info('Firestore Delete Result: ' . json_encode([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'No message'
            ]));

            if (!$result['success']) {
                \Log::error('Failed to delete service from Firestore');
                return response()->json(
                    ApiResponse::error('Failed to delete service', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $firebaseUid,
                'service_deleted',
                "Deleted service: {$id}",
                $request->ip(),
                $request->userAgent()
            );
            
            \Log::info('Service deleted successfully: ' . $id);
            \Log::info('=== END DELETE SERVICE ===');

            return response()->json(
                ApiResponse::success('Service deleted successfully')->toArray()
            );

        } catch (Exception $e) {
            \Log::error('Exception in ServiceController@destroy: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(
                ApiResponse::error('Failed to delete service', null, 500)->toArray(),
                500
            );
        }
    }
}
