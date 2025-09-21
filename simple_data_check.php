<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Simple Data Check & Creation ===" . PHP_EOL;

try {
    $firestoreService = app(\App\Services\FirestoreService::class);
    
    // Check admin stats (this will tell us what data exists)
    echo "Checking admin statistics..." . PHP_EOL;
    $stats = $firestoreService->getAdminStats();
    
    echo "Current stats:" . PHP_EOL;
    echo "- Total users: " . ($stats['total_users'] ?? 0) . PHP_EOL;
    echo "- Total patients: " . ($stats['total_patients'] ?? 0) . PHP_EOL;
    echo "- Total health workers: " . ($stats['total_health_workers'] ?? 0) . PHP_EOL;
    echo "- Total appointments: " . ($stats['total_appointments'] ?? 0) . PHP_EOL;
    echo "- Total health centers: " . ($stats['total_health_centers'] ?? 0) . PHP_EOL;
    echo "- Total services: " . ($stats['total_services'] ?? 0) . PHP_EOL;
    
    // If no data, let's create some basic data
    if (($stats['total_users'] ?? 0) <= 1) { // Only admin exists
        echo PHP_EOL . "Creating additional sample data..." . PHP_EOL;
        
        // Create sample users directly in Firestore
        $firestore = new Google\Cloud\Firestore\FirestoreClient([
            'projectId' => env('FIREBASE_PROJECT_ID', 'healthreach-9167b')
        ]);
        
        // Create sample patients
        for ($i = 1; $i <= 5; $i++) {
            $userData = [
                'firebase_uid' => 'sample-patient-uid-' . $i,
                'name' => "Patient User $i",
                'email' => "patient$i@example.com",
                'role' => 'patient',
                'contact_number' => '+123456789' . $i,
                'address' => "123 Patient Street $i",
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
                'is_active' => true
            ];
            
            $firestore->collection('users')->add($userData);
            echo "Created patient $i" . PHP_EOL;
        }
        
        // Create sample health workers
        for ($i = 1; $i <= 3; $i++) {
            $userData = [
                'firebase_uid' => 'sample-hw-uid-' . $i,
                'name' => "Dr. Health Worker $i",
                'email' => "healthworker$i@example.com",
                'role' => 'health_worker',
                'contact_number' => '+123456780' . $i,
                'address' => "456 Medical Center $i",
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
                'is_active' => true
            ];
            
            $firestore->collection('users')->add($userData);
            echo "Created health worker $i" . PHP_EOL;
        }
        
        // Create sample health centers
        for ($i = 1; $i <= 2; $i++) {
            $centerData = [
                'name' => "HealthReach Center $i",
                'address' => "789 Healthcare Ave $i",
                'contact_number' => '+123456770' . $i,
                'email' => "center$i@healthreach.com",
                'description' => "Full-service healthcare facility $i",
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
                'is_active' => true
            ];
            
            $firestore->collection('health_centers')->add($centerData);
            echo "Created health center $i" . PHP_EOL;
        }
        
        // Create sample appointments
        for ($i = 1; $i <= 8; $i++) {
            $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            $appointmentData = [
                'patient_id' => 'sample-patient-uid-' . rand(1, 5),
                'health_worker_id' => 'sample-hw-uid-' . rand(1, 3),
                'service_name' => 'General Consultation',
                'appointment_date' => new DateTime('+' . rand(1, 30) . ' days'),
                'status' => $statuses[array_rand($statuses)],
                'notes' => "Sample appointment notes $i",
                'created_at' => new DateTime(),
                'updated_at' => new DateTime()
            ];
            
            $firestore->collection('appointments')->add($appointmentData);
            echo "Created appointment $i" . PHP_EOL;
        }
        
        echo PHP_EOL . "Sample data created!" . PHP_EOL;
    }
    
    // Check stats again
    echo PHP_EOL . "Updated statistics:" . PHP_EOL;
    $newStats = $firestoreService->getAdminStats();
    echo "- Total users: " . ($newStats['total_users'] ?? 0) . PHP_EOL;
    echo "- Total patients: " . ($newStats['total_patients'] ?? 0) . PHP_EOL;
    echo "- Total health workers: " . ($newStats['total_health_workers'] ?? 0) . PHP_EOL;
    echo "- Total appointments: " . ($newStats['total_appointments'] ?? 0) . PHP_EOL;
    echo "- Total health centers: " . ($newStats['total_health_centers'] ?? 0) . PHP_EOL;
    
    echo PHP_EOL . "✅ Data check complete! Refresh your admin dashboard now." . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}
