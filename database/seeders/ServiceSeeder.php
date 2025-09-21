<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\FirestoreService;
use Illuminate\Support\Str;
use Exception;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds - Create services in Firestore
     */
    public function run(): void
    {
        try {
            $firestoreService = app(FirestoreService::class);
            
            // Get health centers from Firestore
            $healthCentersResult = $firestoreService->getHealthCenters();
            
            if (!$healthCentersResult['success']) {
                echo "Failed to retrieve health centers from Firestore\n";
                return;
            }
            
            $services = [
                [
                    'service_name' => 'General Consultation',
                    'description' => 'Routine health checkup and consultation with a general practitioner',
                    'duration_minutes' => 30,
                    'price' => 50.00,
                ],
                [
                    'service_name' => 'Blood Test',
                    'description' => 'Complete blood count and basic metabolic panel',
                    'duration_minutes' => 15,
                    'price' => 75.00,
                ],
                [
                    'service_name' => 'Vaccination',
                    'description' => 'Routine immunizations and travel vaccines',
                    'duration_minutes' => 20,
                    'price' => 25.00,
                ],
                [
                    'service_name' => 'Physical Therapy',
                    'description' => 'Rehabilitation and physical therapy sessions',
                    'duration_minutes' => 45,
                    'price' => 80.00,
                ],
                [
                    'service_name' => 'Dental Checkup',
                    'description' => 'Regular dental examination and cleaning',
                    'duration_minutes' => 30,
                    'price' => 60.00,
                ],
            ];

            foreach ($healthCentersResult['data'] as $healthCenter) {
                foreach ($services as $service) {
                    $serviceData = [
                        'service_id' => 'svc-' . Str::uuid(),
                        'health_center_id' => $healthCenter->health_center_id,
                        'service_name' => $service['service_name'],
                        'description' => $service['description'],
                        'duration_minutes' => $service['duration_minutes'],
                        'price' => $service['price'],
                        'is_active' => true,
                        'schedule' => [],
                    ];
                    
                    $documentId = $firestoreService->createDocument('services', $serviceData);
                    if ($documentId) {
                        echo "Created service: {$service['service_name']} for {$healthCenter->name}\n";
                    } else {
                        echo "Failed to create service: {$service['service_name']} for {$healthCenter->name}\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "Error creating services: " . $e->getMessage() . "\n";
        }
    }
}
