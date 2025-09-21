<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking Database Data ===" . PHP_EOL;

try {
    $firestoreService = app(\App\Services\FirestoreService::class);
    
    // Check users
    echo "1. Checking Users Collection:" . PHP_EOL;
    $users = $firestoreService->getCollection('users');
    if ($users) {
        echo "   Total users found: " . count($users) . PHP_EOL;
        
        $roleCount = ['patient' => 0, 'health_worker' => 0, 'admin' => 0];
        foreach ($users as $userId => $userData) {
            $role = $userData['role'] ?? 'unknown';
            if (isset($roleCount[$role])) {
                $roleCount[$role]++;
            }
            echo "   - {$userData['name']} ({$userData['email']}) - Role: $role" . PHP_EOL;
        }
        
        echo "   Role breakdown:" . PHP_EOL;
        foreach ($roleCount as $role => $count) {
            echo "     $role: $count" . PHP_EOL;
        }
    } else {
        echo "   No users found!" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Check health centers
    echo "2. Checking Health Centers Collection:" . PHP_EOL;
    $centers = $firestoreService->getCollection('health_centers');
    if ($centers) {
        echo "   Total health centers: " . count($centers) . PHP_EOL;
        foreach ($centers as $centerId => $centerData) {
            echo "   - {$centerData['name']} ({$centerData['address']})" . PHP_EOL;
        }
    } else {
        echo "   No health centers found!" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Check appointments
    echo "3. Checking Appointments Collection:" . PHP_EOL;
    $appointments = $firestoreService->getCollection('appointments');
    if ($appointments) {
        echo "   Total appointments: " . count($appointments) . PHP_EOL;
        
        $statusCount = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($appointments as $appointmentId => $appointmentData) {
            $status = $appointmentData['status'] ?? 'unknown';
            if (isset($statusCount[$status])) {
                $statusCount[$status]++;
            }
        }
        
        echo "   Status breakdown:" . PHP_EOL;
        foreach ($statusCount as $status => $count) {
            echo "     $status: $count" . PHP_EOL;
        }
    } else {
        echo "   No appointments found!" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== Database Check Complete ===" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error checking database: " . $e->getMessage() . PHP_EOL;
}
