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
            // Add updated timestamp
            $data['updated_at'] = Carbon::now()->toISOString();
            
            $docRef = $this->firestore->collection($collection)->document($documentId);
            $docRef->update($data);
            return true;
        } catch (Exception $e) {
            Log::error('Firestore update error: ' . $e->getMessage());
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
                    $healthCenters[] = HealthCenterData::fromArray($document->data());
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

    public function getServices(): array
    {
        try {
            $collection = $this->firestore->collection('services');
            $documents = $collection->documents();
            
            $services = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $services[] = ServiceData::fromArray($document->data());
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
                $serviceData = ServiceData::fromArray($document->data());
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
            $docRef = $this->firestore->collection('appointments')->document($appointmentData->appointment_id);
            $docRef->set($appointmentData->toArray());
            
            return ['success' => true, 'data' => $appointmentData];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAppointmentsByUser(string $userId): array
    {
        try {
            $collection = $this->firestore->collection('appointments');
            $query = $collection->where('user_id', '=', $userId);
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

    public function getAllAppointments(): array
    {
        try {
            $collection = $this->firestore->collection('appointments');
            $documents = $collection->documents();
            
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
            $query = $collection->where('user_id', '=', $userId)->orderBy('date_sent', 'DESC');
            $documents = $query->documents();
            
            $notifications = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $notifications[] = NotificationData::fromArray($document->data());
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
            $query = $collection->orderBy('date_sent', 'DESC');
            $documents = $query->documents();
            
            $notifications = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $notifications[] = NotificationData::fromArray($document->data());
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
