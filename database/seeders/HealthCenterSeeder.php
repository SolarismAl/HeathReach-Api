<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\FirestoreService;
use Illuminate\Support\Str;
use Exception;

class HealthCenterSeeder extends Seeder
{
    /**
     * Run the database seeds - Create health centers in Firestore
     */
    public function run(): void
    {
        try {
            $firestoreService = app(FirestoreService::class);

            $healthCenters = [
                [
                    'health_center_id' => 'hc-' . Str::uuid(),
                    'name' => 'City General Hospital',
                    'location' => '123 Main Street, Downtown',
                    'contact_number' => '+1-555-0123',
                ],
                [
                    'health_center_id' => 'hc-' . Str::uuid(),
                    'name' => 'Community Health Clinic',
                    'location' => '456 Oak Avenue, Midtown',
                    'contact_number' => '+1-555-0456',
                ],
                [
                    'health_center_id' => 'hc-' . Str::uuid(),
                    'name' => 'Family Medical Center',
                    'location' => '789 Pine Road, Uptown',
                    'contact_number' => '+1-555-0789',
                ],
            ];

            foreach ($healthCenters as $healthCenter) {
                $documentId = $firestoreService->createDocument('health_centers', $healthCenter);
                if ($documentId) {
                    echo "Created health center: {$healthCenter['name']}\n";
                } else {
                    echo "Failed to create health center: {$healthCenter['name']}\n";
                }
            }
        } catch (Exception $e) {
            echo "Error creating health centers: " . $e->getMessage() . "\n";
        }
    }
}
