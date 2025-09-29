<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use App\DataTransferObjects\UserData;
use App\DataTransferObjects\HealthCenterData;
use App\DataTransferObjects\ServiceData;
use App\DataTransferObjects\AppointmentData;
use App\DataTransferObjects\NotificationData;
use App\DataTransferObjects\DeviceTokenData;
use App\DataTransferObjects\ActivityLogData;
use App\DataTransferObjects\AdminStatsData;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FirestoreService
{
    private FirestoreClient $firestore;

    public function __construct()
    {
        $this->firestore = app(FirestoreClient::class);
    }

    public function createDocument(string $collection, array $data, ?string $documentId = null): ?string
    {
        try {
            // Add timestamps
            $data['created_at'] = Carbon::now()->toISOString();
            $data['updated_at'] = Carbon::now()->toISOString();
            
            if ($documentId) {
                $docRef = $this->firestore->collection($collection)->document($documentId);
            } else {
                $docRef = $this->firestore->collection($collection)->newDocument();
            }
            
            $docRef->set($data);
            return $docRef->id();
        } catch (\Exception $e) {
            Log::error('Firestore create error: ' . $e->getMessage());
            return null;
        }
    }

    public function getDocument(string $collection, string $documentId): ?array
    {
        try {
            $docRef = $this->firestore->collection($collection)->document($documentId);
            $snapshot = $docRef->snapshot();
            
            if ($snapshot->exists()) {
                $data = $snapshot->data();
                $data['id'] = $snapshot->id();
                return $data;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Firestore get error: ' . $e->getMessage());
            return null;
        }
    }

    public function updateDocument(string $collection, string $documentId, array $data): bool
    {
        try {
            Log::info('=== FIRESTORE UPDATE DOCUMENT ===');
            Log::info('Collection: ' . $collection);
            Log::info('Document ID: ' . $documentId);
            Log::info('Data to update:', $data);
            
            // Add updated timestamp
            $data['updated_at'] = Carbon::now()->toISOString();
            
            $docRef = $this->firestore->collection($collection)->document($documentId);
            
            // Check if document exists first
            $snapshot = $docRef->snapshot();
            if (!$snapshot->exists()) {
                Log::error('Document does not exist: ' . $collection . '/' . $documentId);
                return false;
            }
            
            Log::info('Document exists, proceeding with update...');
            
            // Try using set() with merge instead of update()
            Log::info('Using set with merge option instead of update');
            $docRef->set($data, ['merge' => true]);
            Log::info('Document updated successfully using set with merge');
            return true;
        } catch (Exception $e) {
            Log::error('Firestore update error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Find document by field value
     */
    public function findByField(string $collection, string $field, $value): ?array
    {
        try {
            $query = $this->firestore->collection($collection)->where($field, '=', $value);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $data['id'] = $document->id();
                    return $data;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Firestore findByField error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all documents from a collection
     */
    public function getCollection(string $collection): array
    {
        try {
            $collectionRef = $this->firestore->collection($collection);
            $documents = $collectionRef->documents();
            
            $results = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $results[$document->id()] = $data;
                }
            }
            
            return $results;
        } catch (\Exception $e) {
            Log::error('Firestore getCollection error: ' . $e->getMessage());
            return [];
        }
    }

    // Health Centers Collection
    public function createHealthCenter(HealthCenterData $healthCenterData): array
    {
        try {
            $docRef = $this->firestore->collection('health_centers')->document($healthCenterData->health_center_id);
            $docRef->set($healthCenterData->toArray());
            
            return ['success' => true, 'data' => $healthCenterData];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getHealthCenters(): array
    {
        try {
            $collection = $this->firestore->collection('health_centers');
            $documents = $collection->documents();
            
            $healthCenters = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    // Map Firestore data to DTO expected format
                    $mappedData = [
                        'health_center_id' => $document->id(), // Use document ID
                        'name' => $data['name'] ?? '',
                        'address' => $data['address'] ?? '',
                        'contact_number' => $data['phone'] ?? null, // Map phone to contact_number
                        'email' => $data['email'] ?? null,
                        'description' => $data['description'] ?? null,
                        'is_active' => $data['is_active'] ?? true,
                        'created_at' => $data['created_at'] ?? null,
                        'updated_at' => $data['updated_at'] ?? null,
                    ];
                    $healthCenters[] = HealthCenterData::fromArray($mappedData);
                }
            }
            
            return ['success' => true, 'data' => $healthCenters];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getHealthCenter(string $healthCenterId): array
    {
        try {
            $docRef = $this->firestore->collection('health_centers')->document($healthCenterId);
            $document = $docRef->snapshot();
            
            if ($document->exists()) {
                $healthCenterData = HealthCenterData::fromArray($document->data());
                return ['success' => true, 'data' => $healthCenterData];
            } else {
                return ['success' => false, 'error' => 'Health center not found'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateHealthCenter(string $healthCenterId, array $data): array
    {
        try {
            $docRef = $this->firestore->collection('health_centers')->document($healthCenterId);
            $docRef->update($data);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteHealthCenter(string $healthCenterId): array
    {
        try {
            $docRef = $this->firestore->collection('health_centers')->document($healthCenterId);
            $docRef->delete();
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getUser(string $userId): array
    {
        try {
            $docRef = $this->firestore->collection('users')->document($userId);
            $document = $docRef->snapshot();
            
            if ($document->exists()) {
                $data = $document->data();
                
                // Map Firestore data to DTO expected format
                $mappedData = [
                    'user_id' => $document->id(),
                    'firebase_uid' => $data['firebase_uid'] ?? $document->id(),
                    'name' => $data['name'] ?? '',
                    'email' => $data['email'] ?? '',
                    'contact_number' => $data['contact_number'] ?? $data['phone'] ?? '',
                    'role' => $data['role'] ?? 'patient',
                    'profile_picture' => $data['profile_picture'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'address' => $data['address'] ?? null,
                    'emergency_contact' => $data['emergency_contact'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'created_at' => $data['created_at'] ?? null,
                    'updated_at' => $data['updated_at'] ?? null,
                ];
                
                $userData = UserData::fromArray($mappedData);
                return ['success' => true, 'data' => $userData];
            } else {
                return ['success' => false, 'error' => 'User not found'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    // Services Collection
    public function createService(ServiceData $serviceData): array
    {
        try {
            $docRef = $this->firestore->collection('services')->document($serviceData->service_id);
            $docRef->set($serviceData->toArray());
            
            return ['success' => true, 'data' => $serviceData];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getServices(?string $healthCenterId = null): array
    {
        try {
            $collection = $this->firestore->collection('services');
            
            // If health center ID is provided, filter by it
            if ($healthCenterId) {
                $query = $collection->where('health_center_id', '=', $healthCenterId);
                $documents = $query->documents();
            } else {
                $documents = $collection->documents();
            }
            
            $services = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    // Map document ID to service_id if not present
                    $mappedData = [
                        'service_id' => $data['service_id'] ?? $document->id(),
                        'health_center_id' => $data['health_center_id'] ?? '',
                        'service_name' => $data['service_name'] ?? $data['name'] ?? '',
                        'description' => $data['description'] ?? '',
                        'duration_minutes' => $data['duration_minutes'] ?? $data['duration'] ?? null,
                        'price' => $data['price'] ?? null,
                        'is_active' => $data['is_active'] ?? true,
                        'schedule' => $data['schedule'] ?? [],
                        'created_at' => $data['created_at'] ?? null,
                        'updated_at' => $data['updated_at'] ?? null,
                    ];
                    $services[] = ServiceData::fromArray($mappedData);
                }
            }
            
            return ['success' => true, 'data' => $services];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateService(string $serviceId, array $data): array
    {
        try {
            $docRef = $this->firestore->collection('services')->document($serviceId);
            $docRef->update($data);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getService(string $serviceId): array
    {
        try {
            $docRef = $this->firestore->collection('services')->document($serviceId);
            $document = $docRef->snapshot();
            
            if ($document->exists()) {
                $data = $document->data();
                
                // Map Firestore data to DTO expected format
                $mappedData = [
                    'service_id' => $data['service_id'] ?? $document->id(),
                    'health_center_id' => $data['health_center_id'] ?? '',
                    'service_name' => $data['service_name'] ?? $data['name'] ?? '',
                    'description' => $data['description'] ?? '',
                    'duration_minutes' => $data['duration_minutes'] ?? $data['duration'] ?? null,
                    'price' => $data['price'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'schedule' => $data['schedule'] ?? [],
                    'created_at' => $data['created_at'] ?? null,
                    'updated_at' => $data['updated_at'] ?? null,
                ];
                
                $serviceData = ServiceData::fromArray($mappedData);
                return ['success' => true, 'data' => $serviceData];
            } else {
                return ['success' => false, 'error' => 'Service not found'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteService(string $serviceId): array
    {
        try {
            $docRef = $this->firestore->collection('services')->document($serviceId);
            $docRef->delete();
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Appointments Collection
    public function createAppointment(AppointmentData $appointmentData): array
    {
        try {
            // Fetch related data to populate the appointment
            $userData = null;
            $healthCenterData = null;
            $serviceData = null;
            
            // Get user data
            \Log::info('Attempting to fetch user with ID:', ['user_id' => $appointmentData->user_id]);
            $userResult = $this->getUser($appointmentData->user_id);
            \Log::info('User fetch result:', $userResult);
            if ($userResult['success'] && !empty($userResult['data'])) {
                $userData = $userResult['data'];
                \Log::info('User data populated successfully');
            } else {
                \Log::error('Failed to fetch user data:', [
                    'user_id' => $appointmentData->user_id,
                    'result' => $userResult
                ]);
            }
            
            // Get health center data
            \Log::info('Attempting to fetch health center with ID:', ['health_center_id' => $appointmentData->health_center_id]);
            $healthCenterResult = $this->getHealthCenter($appointmentData->health_center_id);
            \Log::info('Health center fetch result:', $healthCenterResult);
            if ($healthCenterResult['success'] && !empty($healthCenterResult['data'])) {
                $healthCenterData = $healthCenterResult['data'];
                \Log::info('Health center data populated successfully');
            } else {
                \Log::error('Failed to fetch health center data:', [
                    'health_center_id' => $appointmentData->health_center_id,
                    'result' => $healthCenterResult
                ]);
            }
            
            // Get service data
            \Log::info('Attempting to fetch service with ID:', ['service_id' => $appointmentData->service_id]);
            $serviceResult = $this->getService($appointmentData->service_id);
            \Log::info('Service fetch result:', $serviceResult);
            if ($serviceResult['success'] && !empty($serviceResult['data'])) {
                $serviceData = $serviceResult['data'];
                \Log::info('Service data populated successfully');
            } else {
                \Log::error('Failed to fetch service data:', [
                    'service_id' => $appointmentData->service_id,
                    'result' => $serviceResult
                ]);
            }
            
            // Create appointment with populated data
            $appointmentWithData = new AppointmentData(
                appointment_id: $appointmentData->appointment_id,
                user_id: $appointmentData->user_id,
                health_center_id: $appointmentData->health_center_id,
                service_id: $appointmentData->service_id,
                date: $appointmentData->date,
                time: $appointmentData->time,
                status: $appointmentData->status,
                remarks: $appointmentData->remarks,
                user: $userData,
                health_center: $healthCenterData,
                service: $serviceData,
                created_at: $appointmentData->created_at,
                updated_at: $appointmentData->updated_at
            );
            
            $docRef = $this->firestore->collection('appointments')->document($appointmentData->appointment_id);
            $appointmentArray = $appointmentWithData->toArray();
            \Log::info('Final appointment data being stored:', $appointmentArray);
            $docRef->set($appointmentArray);
            
            \Log::info('Appointment created successfully with ID:', ['appointment_id' => $appointmentData->appointment_id]);
            return ['success' => true, 'data' => $appointmentWithData];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAppointmentsByUser(string $userId, ?string $status = null): array
    {
        try {
            $collection = $this->firestore->collection('appointments');
            // Query by user_id (the correct field name)
            $query = $collection->where('user_id', '=', $userId);
            
            // Add status filter if provided
            if ($status && $status !== 'all') {
                $query = $query->where('status', '=', $status);
                \Log::info('Filtering appointments by status:', ['status' => $status]);
            }
            
            $documents = $query->documents();
            
            $appointments = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    
                    // Get the date and time from the stored data
                    $date = $data['date'] ?? '';
                    $time = $data['time'] ?? '';
                    
                    // Get related data
                    $userData = null;
                    $healthCenterData = null;
                    $serviceData = null;
                    
                    if (isset($data['user']) && is_array($data['user'])) {
                        $userData = UserData::fromArray($data['user']);
                    }
                    
                    if (isset($data['health_center']) && is_array($data['health_center'])) {
                        $healthCenterData = HealthCenterData::fromArray($data['health_center']);
                    }
                    
                    if (isset($data['service']) && is_array($data['service'])) {
                        $serviceData = ServiceData::fromArray($data['service']);
                    } else if (!empty($data['service_id'])) {
                        // Fetch service data if not stored in appointment
                        $serviceResult = $this->getService($data['service_id']);
                        if ($serviceResult['success'] && !empty($serviceResult['data'])) {
                            $serviceData = $serviceResult['data'];
                        }
                    }
                    
                    $mappedData = [
                        'appointment_id' => $document->id(),
                        'user_id' => $data['user_id'] ?? '',
                        'health_center_id' => $data['health_center_id'] ?? '',
                        'service_id' => $data['service_id'] ?? '',
                        'date' => $date,
                        'time' => $time,
                        'status' => $data['status'] ?? 'pending',
                        'remarks' => $data['remarks'] ?? null,
                        'user' => $userData?->toArray(),
                        'health_center' => $healthCenterData?->toArray(),
                        'service' => $serviceData?->toArray(),
                        'created_at' => $data['created_at'] ?? null,
                        'updated_at' => $data['updated_at'] ?? null,
                    ];
                    $appointments[] = AppointmentData::fromArray($mappedData);
                }
            }
            
            return ['success' => true, 'data' => $appointments];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllAppointments(): array
    {
        try {
            $collection = $this->firestore->collection('appointments');
            $documents = $collection->documents();
            
            $appointments = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    
                    // Get the date and time from the stored data
                    $date = $data['date'] ?? '';
                    $time = $data['time'] ?? '';
                    
                    // Get related data
                    $userData = null;
                    $healthCenterData = null;
                    $serviceData = null;
                    
                    if (isset($data['user']) && is_array($data['user'])) {
                        $userData = UserData::fromArray($data['user']);
                    }
                    
                    if (isset($data['health_center']) && is_array($data['health_center'])) {
                        $healthCenterData = HealthCenterData::fromArray($data['health_center']);
                    }
                    
                    if (isset($data['service']) && is_array($data['service'])) {
                        $serviceData = ServiceData::fromArray($data['service']);
                    } else if (!empty($data['service_id'])) {
                        // Fetch service data if not stored in appointment
                        $serviceResult = $this->getService($data['service_id']);
                        if ($serviceResult['success'] && !empty($serviceResult['data'])) {
                            $serviceData = $serviceResult['data'];
                        }
                    }
                    
                    $mappedData = [
                        'appointment_id' => $document->id(),
                        'user_id' => $data['user_id'] ?? '',
                        'health_center_id' => $data['health_center_id'] ?? '',
                        'service_id' => $data['service_id'] ?? '',
                        'date' => $date,
                        'time' => $time,
                        'status' => $data['status'] ?? 'pending',
                        'remarks' => $data['remarks'] ?? null,
                        'user' => $userData?->toArray(),
                        'health_center' => $healthCenterData?->toArray(),
                        'service' => $serviceData?->toArray(),
                        'created_at' => $data['created_at'] ?? null,
                        'updated_at' => $data['updated_at'] ?? null,
                    ];
                    $appointments[] = AppointmentData::fromArray($mappedData);
                }
            }
            
            return ['success' => true, 'data' => $appointments];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateAppointment(string $appointmentId, array $data): array
    {
        try {
            $docRef = $this->firestore->collection('appointments')->document($appointmentId);
            $docRef->update($data);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Notifications Collection
    public function createNotification(NotificationData $notificationData): array
    {
        try {
            $docRef = $this->firestore->collection('notifications')->document($notificationData->notification_id);
            $docRef->set($notificationData->toArray());
            
            return ['success' => true, 'data' => $notificationData];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getNotificationsByUser(string $userId): array
    {
        try {
            $collection = $this->firestore->collection('notifications');
            $query = $collection->where('user_id', '=', $userId);
            $documents = $query->documents();
            
            $notifications = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    
                    // Skip placeholder documents
                    if (isset($data['placeholder']) && $data['placeholder'] === true) {
                        continue;
                    }
                    
                    // Only process documents that have the required notification fields
                    if (!isset($data['title']) || !isset($data['message'])) {
                        continue;
                    }
                    
                    // Map Firestore data to DTO expected format
                    $mappedData = [
                        'notification_id' => $document->id(), // Use document ID
                        'user_id' => $data['user_id'] ?? '',
                        'title' => $data['title'] ?? '',
                        'message' => $data['message'] ?? '',
                        'date_sent' => $data['date_sent'] ?? $data['created_at'] ?? now()->toISOString(),
                        'is_read' => $data['is_read'] ?? false,
                        'type' => $data['type'] ?? 'general',
                        'created_at' => $data['created_at'] ?? null,
                        'updated_at' => $data['updated_at'] ?? null,
                    ];
                    $notifications[] = NotificationData::fromArray($mappedData);
                }
            }
            
            return ['success' => true, 'data' => $notifications];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllNotifications(): array
    {
        try {
            $collection = $this->firestore->collection('notifications');
            $documents = $collection->documents();
            
            $notifications = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    
                    // Skip placeholder documents
                    if (isset($data['placeholder']) && $data['placeholder'] === true) {
                        continue;
                    }
                    
                    // Only process documents that have the required notification fields
                    if (!isset($data['title']) || !isset($data['message'])) {
                        continue;
                    }
                    
                    // Map Firestore data to DTO expected format
                    $mappedData = [
                        'notification_id' => $document->id(), // Use document ID
                        'user_id' => $data['user_id'] ?? '',
                        'title' => $data['title'] ?? '',
                        'message' => $data['message'] ?? '',
                        'date_sent' => $data['date_sent'] ?? $data['created_at'] ?? now()->toISOString(),
                        'is_read' => $data['is_read'] ?? false,
                        'type' => $data['type'] ?? 'general',
                        'created_at' => $data['created_at'] ?? null,
                        'updated_at' => $data['updated_at'] ?? null,
                    ];
                    $notifications[] = NotificationData::fromArray($mappedData);
                }
            }
            
            return ['success' => true, 'data' => $notifications];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateNotification(string $notificationId, array $data): array
    {
        try {
            $docRef = $this->firestore->collection('notifications')->document($notificationId);
            $docRef->update($data);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Device Tokens Collection
    public function saveDeviceToken(DeviceTokenData $deviceTokenData): array
    {
        try {
            $docRef = $this->firestore->collection('device_tokens')->document($deviceTokenData->id);
            $docRef->set($deviceTokenData->toArray());
            
            return ['success' => true, 'data' => $deviceTokenData];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getDeviceTokensByUser(string $userId): array
    {
        try {
            $collection = $this->firestore->collection('device_tokens');
            $query = $collection->where('user_id', '=', $userId);
            $documents = $query->documents();
            
            $tokens = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $tokens[] = DeviceTokenData::fromArray($document->data());
                }
            }
            
            return ['success' => true, 'data' => $tokens];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllDeviceTokens(): array
    {
        try {
            $collection = $this->firestore->collection('device_tokens');
            $documents = $collection->documents();
            
            $tokens = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $tokens[] = DeviceTokenData::fromArray($document->data());
                }
            }
            
            return ['success' => true, 'data' => $tokens];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Activity Logs Collection
    public function createActivityLog(ActivityLogData $logData): array
    {
        try {
            $docRef = $this->firestore->collection('logs')->document($logData->id);
            $docRef->set($logData->toArray());
            
            return ['success' => true, 'data' => $logData];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getActivityLogs(int $limit = 50): array
    {
        try {
            $collection = $this->firestore->collection('logs');
            $query = $collection->orderBy('created_at', 'DESC')->limit($limit);
            $documents = $query->documents();
            
            $logs = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $logs[] = ActivityLogData::fromArray($document->data());
                }
            }
            
            return ['success' => true, 'data' => $logs];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Admin Statistics
    public function getAdminStats(): array
    {
        try {
            // Get counts from different collections
            $usersCount = $this->getCollectionCount('users');
            $appointmentsCount = $this->getCollectionCount('appointments');
            $healthCentersCount = $this->getCollectionCount('health_centers');
            $servicesCount = $this->getCollectionCount('services');
            
            // Get pending and completed appointments
            $pendingAppointments = $this->getAppointmentsByStatus('pending');
            $completedAppointments = $this->getAppointmentsByStatus('completed');
            
            // Get recent activities
            $recentActivities = $this->getActivityLogs(10);
            
            $stats = new AdminStatsData(
                total_users: $usersCount,
                total_appointments: $appointmentsCount,
                pending_appointments: count($pendingAppointments['data'] ?? []),
                completed_appointments: count($completedAppointments['data'] ?? []),
                total_health_centers: $healthCentersCount,
                total_services: $servicesCount,
                recent_activities: array_map(fn($log) => $log->toArray(), $recentActivities['data'] ?? [])
            );
            
            return ['success' => true, 'data' => $stats];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getCollectionCount(string $collectionName): int
    {
        try {
            $collection = $this->firestore->collection($collectionName);
            $documents = $collection->documents();
            return iterator_count($documents);
        } catch (Exception $e) {
            return 0;
        }
    }

    public function deleteDocument(string $collection, string $documentId): bool
    {
        try {
            $docRef = $this->firestore->collection($collection)->document($documentId);
            $docRef->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Firestore delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Query collection with conditions
     */
    public function queryCollection(string $collection, array $conditions = []): array
    {
        try {
            $query = $this->firestore->collection($collection);
            
            // Apply conditions
            foreach ($conditions as $condition) {
                if (count($condition) === 3) {
                    [$field, $operator, $value] = $condition;
                    $query = $query->where($field, $operator, $value);
                }
            }
            
            $documents = $query->documents();
            $results = [];
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $data['document_id'] = $document->id(); // Add document ID for reference
                    $results[] = $data;
                }
            }
            
            return $results;
        } catch (\Exception $e) {
            Log::error('Firestore query error: ' . $e->getMessage());
            return [];
        }
    }

    private function getAppointmentsByStatus(string $status): array
    {
        try {
            $collection = $this->firestore->collection('appointments');
            $query = $collection->where('status', '=', $status);
            $documents = $query->documents();
            
            $appointments = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $appointments[] = AppointmentData::fromArray($document->data());
                }
            }
            
            return ['success' => true, 'data' => $appointments];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
