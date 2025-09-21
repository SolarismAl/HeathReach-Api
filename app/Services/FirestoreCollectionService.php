<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FirestoreCollectionService
{
    private FirestoreClient $firestore;

    public function __construct()
    {
        $this->firestore = app(FirestoreClient::class);
    }

    /**
     * Initialize Firestore collections with proper structure
     */
    public function initializeCollections(): void
    {
        $this->createUsersCollection();
        $this->createHealthCentersCollection();
        $this->createServicesCollection();
        $this->createAppointmentsCollection();
        $this->createNotificationsCollection();
        $this->createDeviceTokensCollection();
        $this->createLogsCollection();
    }

    /**
     * Users Collection Structure
     */
    private function createUsersCollection(): void
    {
        $collection = $this->firestore->collection('users');
        
        // Create a sample document to establish collection structure
        $sampleUser = [
            'user_id' => 'sample-user-id',
            'name' => 'Sample User',
            'email' => 'sample@example.com',
            'role' => 'patient', // patient, health_worker, admin
            'contact_number' => '+1234567890',
            'address' => 'Sample Address',
            'fcm_token' => null,
            'email_verified_at' => null,
            'created_at' => Carbon::now()->toISOString(),
            'updated_at' => Carbon::now()->toISOString(),
            '_sample' => true // Mark as sample document
        ];
        
        $collection->document('sample-user')->set($sampleUser);
    }

    /**
     * Health Centers Collection Structure
     */
    private function createHealthCentersCollection(): void
    {
        $collection = $this->firestore->collection('health_centers');
        
        $sampleHealthCenter = [
            'health_center_id' => 'sample-hc-id',
            'name' => 'Sample Health Center',
            'location' => 'Sample Location',
            'contact_number' => '+1234567890',
            'created_at' => Carbon::now()->toISOString(),
            'updated_at' => Carbon::now()->toISOString(),
            '_sample' => true
        ];
        
        $collection->document('sample-health-center')->set($sampleHealthCenter);
    }

    /**
     * Services Collection Structure
     */
    private function createServicesCollection(): void
    {
        $collection = $this->firestore->collection('services');
        
        $sampleService = [
            'service_id' => 'sample-service-id',
            'health_center_id' => 'sample-hc-id',
            'service_name' => 'Sample Service',
            'description' => 'Sample service description',
            'duration_minutes' => 30,
            'price' => 50.00,
            'is_active' => true,
            'schedule' => [
                'monday' => ['09:00', '17:00'],
                'tuesday' => ['09:00', '17:00'],
                'wednesday' => ['09:00', '17:00'],
                'thursday' => ['09:00', '17:00'],
                'friday' => ['09:00', '17:00']
            ],
            'created_at' => Carbon::now()->toISOString(),
            'updated_at' => Carbon::now()->toISOString(),
            '_sample' => true
        ];
        
        $collection->document('sample-service')->set($sampleService);
    }

    /**
     * Appointments Collection Structure
     */
    private function createAppointmentsCollection(): void
    {
        $collection = $this->firestore->collection('appointments');
        
        $sampleAppointment = [
            'appointment_id' => 'sample-appointment-id',
            'user_id' => 'sample-user-id',
            'health_center_id' => 'sample-hc-id',
            'service_id' => 'sample-service-id',
            'appointment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '10:00',
            'status' => 'pending', // pending, confirmed, cancelled, completed
            'notes' => 'Sample appointment notes',
            'created_at' => Carbon::now()->toISOString(),
            'updated_at' => Carbon::now()->toISOString(),
            '_sample' => true
        ];
        
        $collection->document('sample-appointment')->set($sampleAppointment);
    }

    /**
     * Notifications Collection Structure
     */
    private function createNotificationsCollection(): void
    {
        $collection = $this->firestore->collection('notifications');
        
        $sampleNotification = [
            'notification_id' => 'sample-notification-id',
            'user_id' => 'sample-user-id',
            'title' => 'Sample Notification',
            'body' => 'This is a sample notification',
            'data' => [
                'type' => 'appointment',
                'appointment_id' => 'sample-appointment-id'
            ],
            'is_read' => false,
            'sent_at' => Carbon::now()->toISOString(),
            'created_at' => Carbon::now()->toISOString(),
            '_sample' => true
        ];
        
        $collection->document('sample-notification')->set($sampleNotification);
    }

    /**
     * Device Tokens Collection Structure
     */
    private function createDeviceTokensCollection(): void
    {
        $collection = $this->firestore->collection('device_tokens');
        
        $sampleDeviceToken = [
            'token_id' => 'sample-token-id',
            'user_id' => 'sample-user-id',
            'fcm_token' => 'sample-fcm-token',
            'device_type' => 'android', // android, ios, web
            'device_id' => 'sample-device-id',
            'is_active' => true,
            'created_at' => Carbon::now()->toISOString(),
            'updated_at' => Carbon::now()->toISOString(),
            '_sample' => true
        ];
        
        $collection->document('sample-device-token')->set($sampleDeviceToken);
    }

    /**
     * Activity Logs Collection Structure
     */
    private function createLogsCollection(): void
    {
        $collection = $this->firestore->collection('logs');
        
        $sampleLog = [
            'log_id' => 'sample-log-id',
            'user_id' => 'sample-user-id',
            'action' => 'sample_action',
            'description' => 'Sample log description',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Sample User Agent',
            'metadata' => [
                'additional' => 'data'
            ],
            'created_at' => Carbon::now()->toISOString(),
            '_sample' => true
        ];
        
        $collection->document('sample-log')->set($sampleLog);
    }

    /**
     * Clean up sample documents
     */
    public function cleanupSampleDocuments(): void
    {
        $collections = ['users', 'health_centers', 'services', 'appointments', 'notifications', 'device_tokens', 'logs'];
        
        foreach ($collections as $collectionName) {
            $collection = $this->firestore->collection($collectionName);
            $query = $collection->where('_sample', '=', true);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                $document->reference()->delete();
            }
        }
    }
}
