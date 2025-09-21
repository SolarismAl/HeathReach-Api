<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\FirestoreService;
use App\Services\FirebaseService;

echo "=== HealthReach Firestore Setup ===\n";
echo "This script will initialize your Firestore database with required collections.\n\n";

try {
    // Initialize services
    echo "1. Initializing Firebase services...\n";
    $firebaseService = new FirebaseService();
    $firestoreService = new FirestoreService($firebaseService);
    
    echo "2. Testing Firestore connection...\n";
    
    // Try to create a test document to verify Firestore is working
    $testData = [
        'test' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'message' => 'Firestore setup test'
    ];
    
    $result = $firestoreService->createDocument('_test', $testData, 'setup-test');
    
    if ($result) {
        echo "✓ Firestore connection successful!\n";
        
        // Clean up test document
        $firestoreService->deleteDocument('_test', 'setup-test');
        echo "✓ Test cleanup completed\n";
        
        echo "\n3. Creating required collections...\n";
        
        // Initialize collections with sample data
        $collections = [
            'users' => [
                'description' => 'User profiles and authentication data',
                'sample' => [
                    'user_id' => 'sample-user-id',
                    'firebase_uid' => 'sample-firebase-uid',
                    'name' => 'Sample User',
                    'email' => 'sample@example.com',
                    'role' => 'patient',
                    'contact_number' => null,
                    'address' => null,
                    'fcm_token' => null,
                    'email_verified_at' => null,
                    'is_active' => true,
                    'created_at' => date('c'),
                    'updated_at' => date('c')
                ]
            ],
            'health_centers' => [
                'description' => 'Healthcare facilities and centers',
                'sample' => [
                    'center_id' => 'sample-center-id',
                    'name' => 'Sample Health Center',
                    'address' => 'Sample Address',
                    'contact_number' => '+1234567890',
                    'email' => 'center@example.com',
                    'services' => [],
                    'operating_hours' => 'Mon-Fri 8AM-6PM',
                    'is_active' => true,
                    'created_at' => date('c'),
                    'updated_at' => date('c')
                ]
            ],
            'services' => [
                'description' => 'Healthcare services offered',
                'sample' => [
                    'service_id' => 'sample-service-id',
                    'name' => 'Sample Service',
                    'description' => 'Sample healthcare service',
                    'category' => 'general',
                    'price' => 100.00,
                    'duration_minutes' => 30,
                    'health_center_id' => 'sample-center-id',
                    'health_worker_id' => 'sample-worker-id',
                    'is_active' => true,
                    'created_at' => date('c'),
                    'updated_at' => date('c')
                ]
            ],
            'appointments' => [
                'description' => 'Patient appointments and bookings',
                'sample' => [
                    'appointment_id' => 'sample-appointment-id',
                    'patient_id' => 'sample-patient-id',
                    'service_id' => 'sample-service-id',
                    'health_worker_id' => 'sample-worker-id',
                    'appointment_date' => date('Y-m-d'),
                    'appointment_time' => '10:00',
                    'status' => 'scheduled',
                    'notes' => 'Sample appointment notes',
                    'created_at' => date('c'),
                    'updated_at' => date('c')
                ]
            ],
            'notifications' => [
                'description' => 'Push notifications and messages',
                'sample' => [
                    'notification_id' => 'sample-notification-id',
                    'user_id' => 'sample-user-id',
                    'title' => 'Sample Notification',
                    'body' => 'This is a sample notification',
                    'type' => 'info',
                    'data' => [],
                    'is_read' => false,
                    'created_at' => date('c')
                ]
            ],
            'device_tokens' => [
                'description' => 'FCM device tokens for push notifications',
                'sample' => [
                    'token_id' => 'sample-token-id',
                    'user_id' => 'sample-user-id',
                    'token' => 'sample-fcm-token',
                    'platform' => 'android',
                    'created_at' => date('c'),
                    'updated_at' => date('c')
                ]
            ],
            'logs' => [
                'description' => 'Activity logs and audit trail',
                'sample' => [
                    'log_id' => 'sample-log-id',
                    'user_id' => 'sample-user-id',
                    'action' => 'sample_action',
                    'description' => 'Sample log entry',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Sample User Agent',
                    'metadata' => [],
                    'created_at' => date('c')
                ]
            ]
        ];
        
        foreach ($collections as $collectionName => $config) {
            echo "  Creating collection: $collectionName\n";
            echo "  Description: {$config['description']}\n";
            
            // Create sample document to initialize collection
            $sampleId = "sample-" . uniqid();
            $result = $firestoreService->createDocument($collectionName, $config['sample'], $sampleId);
            
            if ($result) {
                echo "  ✓ Collection $collectionName created successfully\n";
                
                // Remove sample document (optional - you can keep it for testing)
                // $firestoreService->deleteDocument($collectionName, $sampleId);
                echo "  ℹ Sample document kept for testing (ID: $sampleId)\n";
            } else {
                echo "  ✗ Failed to create collection $collectionName\n";
            }
            echo "\n";
        }
        
        echo "🎉 Firestore setup completed successfully!\n";
        echo "\nYour HealthReach backend is now ready to handle authentication and data operations.\n";
        echo "You can now test the login functionality in your mobile app.\n\n";
        
    } else {
        echo "✗ Firestore connection failed!\n";
        echo "Please make sure:\n";
        echo "1. Your Firebase project has Firestore enabled\n";
        echo "2. Your service account credentials are correct\n";
        echo "3. Your Firebase project ID is correct\n\n";
        echo "Visit: https://console.firebase.google.com/project/healthreach-9167b/firestore\n";
        echo "And click 'Create database' to set up Firestore.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Setup failed with error: " . $e->getMessage() . "\n\n";
    echo "This usually means Firestore is not enabled for your project.\n";
    echo "Please visit: https://console.firebase.google.com/project/healthreach-9167b/firestore\n";
    echo "And click 'Create database' to enable Firestore.\n\n";
    echo "After enabling Firestore, run this script again.\n";
}

echo "\n=== Setup Complete ===\n";
