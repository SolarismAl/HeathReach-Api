<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Direct Firestore Test ===" . PHP_EOL;

try {
    // Test direct Firestore connection
    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        'projectId' => env('FIREBASE_PROJECT_ID', 'healthreach-9167b')
    ]);
    
    echo "✅ Firestore client created successfully" . PHP_EOL;
    
    // Test reading users collection
    echo "Testing users collection..." . PHP_EOL;
    $usersCollection = $firestore->collection('users');
    $userDocs = $usersCollection->documents();
    
    $userCount = 0;
    $adminCount = 0;
    $patientCount = 0;
    $healthWorkerCount = 0;
    
    foreach ($userDocs as $doc) {
        if ($doc->exists()) {
            $userCount++;
            $data = $doc->data();
            $role = $data['role'] ?? 'unknown';
            
            echo "  User: {$data['name']} ({$data['email']}) - Role: $role" . PHP_EOL;
            
            switch ($role) {
                case 'admin': $adminCount++; break;
                case 'patient': $patientCount++; break;
                case 'health_worker': $healthWorkerCount++; break;
            }
        }
    }
    
    echo "Total users found: $userCount" . PHP_EOL;
    echo "  - Admins: $adminCount" . PHP_EOL;
    echo "  - Patients: $patientCount" . PHP_EOL;
    echo "  - Health Workers: $healthWorkerCount" . PHP_EOL;
    
    // If no users except admin, create some sample data
    if ($userCount <= 1) {
        echo PHP_EOL . "Creating sample users..." . PHP_EOL;
        
        // Create 3 patients
        for ($i = 1; $i <= 3; $i++) {
            $userData = [
                'firebase_uid' => 'direct-patient-' . $i,
                'name' => "Sample Patient $i",
                'email' => "patient$i@test.com",
                'role' => 'patient',
                'contact_number' => "+1234567890$i",
                'address' => "123 Test Street $i",
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
                'is_active' => true
            ];
            
            $docRef = $usersCollection->add($userData);
            echo "  Created patient $i with ID: " . $docRef->id() . PHP_EOL;
        }
        
        // Create 2 health workers
        for ($i = 1; $i <= 2; $i++) {
            $userData = [
                'firebase_uid' => 'direct-hw-' . $i,
                'name' => "Dr. Sample $i",
                'email' => "doctor$i@test.com",
                'role' => 'health_worker',
                'contact_number' => "+1234567880$i",
                'address' => "456 Medical St $i",
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
                'is_active' => true
            ];
            
            $docRef = $usersCollection->add($userData);
            echo "  Created health worker $i with ID: " . $docRef->id() . PHP_EOL;
        }
        
        echo "Sample users created!" . PHP_EOL;
    }
    
    // Test appointments collection
    echo PHP_EOL . "Testing appointments collection..." . PHP_EOL;
    $appointmentsCollection = $firestore->collection('appointments');
    $appointmentDocs = $appointmentsCollection->documents();
    
    $appointmentCount = 0;
    foreach ($appointmentDocs as $doc) {
        if ($doc->exists()) {
            $appointmentCount++;
        }
    }
    
    echo "Total appointments found: $appointmentCount" . PHP_EOL;
    
    // Create sample appointments if none exist
    if ($appointmentCount == 0) {
        echo "Creating sample appointments..." . PHP_EOL;
        
        $statuses = ['pending', 'confirmed', 'completed'];
        for ($i = 1; $i <= 5; $i++) {
            $appointmentData = [
                'patient_id' => 'direct-patient-' . rand(1, 3),
                'health_worker_id' => 'direct-hw-' . rand(1, 2),
                'service_name' => 'General Consultation',
                'appointment_date' => new DateTime('+' . rand(1, 30) . ' days'),
                'status' => $statuses[array_rand($statuses)],
                'notes' => "Sample appointment $i",
                'created_at' => new DateTime(),
                'updated_at' => new DateTime()
            ];
            
            $docRef = $appointmentsCollection->add($appointmentData);
            echo "  Created appointment $i with ID: " . $docRef->id() . PHP_EOL;
        }
    }
    
    // Test health centers
    echo PHP_EOL . "Testing health centers collection..." . PHP_EOL;
    $centersCollection = $firestore->collection('health_centers');
    $centerDocs = $centersCollection->documents();
    
    $centerCount = 0;
    foreach ($centerDocs as $doc) {
        if ($doc->exists()) {
            $centerCount++;
        }
    }
    
    echo "Total health centers found: $centerCount" . PHP_EOL;
    
    if ($centerCount == 0) {
        echo "Creating sample health center..." . PHP_EOL;
        
        $centerData = [
            'name' => 'HealthReach Main Center',
            'address' => '123 Healthcare Avenue',
            'contact_number' => '+1234567890',
            'email' => 'main@healthreach.com',
            'description' => 'Main healthcare facility',
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'is_active' => true
        ];
        
        $docRef = $centersCollection->add($centerData);
        echo "  Created health center with ID: " . $docRef->id() . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ Direct Firestore test complete!" . PHP_EOL;
    echo "Now refresh your admin dashboard - you should see data!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
}
