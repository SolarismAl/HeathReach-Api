<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Creating Sample Data ===" . PHP_EOL;

try {
    $firestoreService = app(\App\Services\FirestoreService::class);
    
    // Create sample patients
    echo "Creating sample patients..." . PHP_EOL;
    for ($i = 1; $i <= 5; $i++) {
        $userData = [
            'firebase_uid' => 'sample-patient-' . $i,
            'name' => "Patient User $i",
            'email' => "patient$i@example.com",
            'role' => 'patient',
            'contact_number' => '+123456789' . $i,
            'address' => "123 Patient Street $i",
            'created_at' => now()->subDays(rand(1, 30))->toISOString(),
            'updated_at' => now()->toISOString(),
            'is_active' => true
        ];
        
        $docId = $firestoreService->createDocument('users', $userData);
        echo "Created patient: $docId" . PHP_EOL;
    }
    
    // Create sample health workers
    echo "Creating sample health workers..." . PHP_EOL;
    for ($i = 1; $i <= 3; $i++) {
        $userData = [
            'firebase_uid' => 'sample-health-worker-' . $i,
            'name' => "Dr. Health Worker $i",
            'email' => "healthworker$i@example.com",
            'role' => 'health_worker',
            'contact_number' => '+123456780' . $i,
            'address' => "456 Medical Center $i",
            'created_at' => now()->subDays(rand(1, 60))->toISOString(),
            'updated_at' => now()->toISOString(),
            'is_active' => true
        ];
        
        $docId = $firestoreService->createDocument('users', $userData);
        echo "Created health worker: $docId" . PHP_EOL;
    }
    
    // Create sample health centers
    echo "Creating sample health centers..." . PHP_EOL;
    for ($i = 1; $i <= 2; $i++) {
        $centerData = [
            'name' => "HealthReach Center $i",
            'address' => "789 Healthcare Ave $i",
            'contact_number' => '+123456770' . $i,
            'email' => "center$i@healthreach.com",
            'description' => "Full-service healthcare facility $i",
            'services' => ['General Medicine', 'Pediatrics', 'Emergency Care'],
            'operating_hours' => [
                'monday' => '8:00 AM - 6:00 PM',
                'tuesday' => '8:00 AM - 6:00 PM',
                'wednesday' => '8:00 AM - 6:00 PM',
                'thursday' => '8:00 AM - 6:00 PM',
                'friday' => '8:00 AM - 6:00 PM',
                'saturday' => '9:00 AM - 4:00 PM',
                'sunday' => 'Closed'
            ],
            'created_at' => now()->subDays(rand(1, 90))->toISOString(),
            'updated_at' => now()->toISOString(),
            'is_active' => true
        ];
        
        $docId = $firestoreService->createDocument('health_centers', $centerData);
        echo "Created health center: $docId" . PHP_EOL;
    }
    
    // Create sample appointments
    echo "Creating sample appointments..." . PHP_EOL;
    for ($i = 1; $i <= 10; $i++) {
        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        $appointmentData = [
            'patient_id' => 'sample-patient-' . rand(1, 5),
            'health_worker_id' => 'sample-health-worker-' . rand(1, 3),
            'health_center_id' => 'health-center-' . rand(1, 2),
            'service_name' => 'General Consultation',
            'appointment_date' => now()->addDays(rand(-10, 30))->toISOString(),
            'status' => $statuses[array_rand($statuses)],
            'notes' => "Sample appointment notes $i",
            'created_at' => now()->subDays(rand(1, 30))->toISOString(),
            'updated_at' => now()->toISOString()
        ];
        
        $docId = $firestoreService->createDocument('appointments', $appointmentData);
        echo "Created appointment: $docId" . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ Sample data created successfully!" . PHP_EOL;
    echo "Now you should see data in your admin dashboard and users list." . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error creating sample data: " . $e->getMessage() . PHP_EOL;
}
