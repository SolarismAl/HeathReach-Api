<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirestoreService;
use App\Services\FirebaseService;
use App\Services\FirestoreCollectionService;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FirestoreSeeder extends Command
{
    protected $signature = 'firestore:seed {--admin} {--health-centers} {--services} {--all}';
    protected $description = 'Seed Firestore collections with sample data';

    private FirestoreService $firestoreService;
    private FirebaseService $firebaseService;
    private FirestoreCollectionService $collectionService;

    public function __construct(
        FirestoreService $firestoreService,
        FirebaseService $firebaseService,
        FirestoreCollectionService $collectionService
    ) {
        parent::__construct();
        $this->firestoreService = $firestoreService;
        $this->firebaseService = $firebaseService;
        $this->collectionService = $collectionService;
    }

    public function handle()
    {
        $this->info('🔥 Starting Firestore seeding...');

        // Initialize collections structure
        $this->collectionService->initializeCollections();
        $this->info('✅ Firestore collections initialized');

        if ($this->option('admin') || $this->option('all')) {
            $this->seedAdminUser();
        }

        if ($this->option('health-centers') || $this->option('all')) {
            $this->seedHealthCenters();
        }

        if ($this->option('services') || $this->option('all')) {
            $this->seedServices();
        }

        // Clean up sample documents
        $this->collectionService->cleanupSampleDocuments();
        $this->info('🧹 Sample documents cleaned up');

        $this->info('🎉 Firestore seeding completed!');
    }

    private function seedAdminUser()
    {
        $this->info('👤 Seeding admin user...');

        try {
            // Check if admin already exists
            $existingAdmin = $this->firestoreService->findByField('users', 'email', 'admin@healthreach.com');
            
            if ($existingAdmin) {
                $this->warn('⚠️  Admin user already exists, skipping...');
                return;
            }

            // Create Firebase Auth user
            $firebaseResult = $this->firebaseService->createUser(
                'admin@healthreach.com',
                'admin123456', // Match the password from memories
                'HealthReach Admin',
                'admin'
            );

            if (!$firebaseResult['success']) {
                $this->error('❌ Failed to create Firebase admin user: ' . ($firebaseResult['error'] ?? 'Unknown error'));
                return;
            }

            // Create admin user in Firestore
            $adminId = 'admin-' . Str::uuid();
            $adminData = [
                'user_id' => $adminId,
                'firebase_uid' => $firebaseResult['uid'],
                'name' => 'Admin User',
                'email' => 'admin@healthreach.com',
                'role' => 'admin',
                'contact_number' => '+1-555-ADMIN',
                'address' => 'HealthReach Headquarters',
                'fcm_token' => null,
                'email_verified_at' => Carbon::now()->toISOString(),
                'is_active' => true
            ];

            $documentId = $this->firestoreService->createDocument('users', $adminData, $adminId);

            if ($documentId) {
                $this->info("✅ Admin user created with ID: {$adminId}");
                $this->info("📧 Email: admin@healthreach.com");
                $this->info("🔑 Password: admin1234");
            } else {
                $this->error('❌ Failed to create admin user in Firestore');
            }

        } catch (\Exception $e) {
            $this->error("❌ Error creating admin user: " . $e->getMessage());
        }
    }

    private function seedHealthCenters()
    {
        $this->info('🏥 Seeding health centers...');

        $healthCenters = [
            [
                'health_center_id' => 'hc-main-clinic',
                'name' => 'HealthReach Main Clinic',
                'location' => '123 Healthcare Ave, Medical District, City 12345',
                'contact_number' => '+1-555-HEALTH-1'
            ],
            [
                'health_center_id' => 'hc-community-center',
                'name' => 'Community Health Center',
                'location' => '456 Community St, Downtown, City 12345',
                'contact_number' => '+1-555-HEALTH-2'
            ],
            [
                'health_center_id' => 'hc-urgent-care',
                'name' => 'HealthReach Urgent Care',
                'location' => '789 Emergency Blvd, Uptown, City 12345',
                'contact_number' => '+1-555-HEALTH-3'
            ]
        ];

        foreach ($healthCenters as $centerData) {
            $existing = $this->firestoreService->findByField('health_centers', 'health_center_id', $centerData['health_center_id']);
            
            if (!$existing) {
                $documentId = $this->firestoreService->createDocument('health_centers', $centerData, $centerData['health_center_id']);
                if ($documentId) {
                    $this->info("✅ Created health center: {$centerData['name']}");
                }
            } else {
                $this->warn("⚠️  Health center already exists: {$centerData['name']}");
            }
        }
    }

    private function seedServices()
    {
        $this->info('🩺 Seeding services...');

        $services = [
            // Main Clinic Services
            [
                'service_id' => 'svc-general-consultation',
                'health_center_id' => 'hc-main-clinic',
                'service_name' => 'General Consultation',
                'description' => 'Comprehensive health check-up and consultation with experienced doctors',
                'duration_minutes' => 45,
                'price' => 75.00,
                'is_active' => true,
                'schedule' => [
                    'monday' => ['08:00', '17:00'],
                    'tuesday' => ['08:00', '17:00'],
                    'wednesday' => ['08:00', '17:00'],
                    'thursday' => ['08:00', '17:00'],
                    'friday' => ['08:00', '17:00'],
                    'saturday' => ['09:00', '13:00']
                ]
            ],
            [
                'service_id' => 'svc-blood-test',
                'health_center_id' => 'hc-main-clinic',
                'service_name' => 'Blood Test & Lab Work',
                'description' => 'Complete blood count, metabolic panel, and specialized lab tests',
                'duration_minutes' => 30,
                'price' => 120.00,
                'is_active' => true,
                'schedule' => [
                    'monday' => ['07:00', '11:00'],
                    'tuesday' => ['07:00', '11:00'],
                    'wednesday' => ['07:00', '11:00'],
                    'thursday' => ['07:00', '11:00'],
                    'friday' => ['07:00', '11:00'],
                    'saturday' => ['07:00', '10:00']
                ]
            ],
            [
                'service_id' => 'svc-vaccination',
                'health_center_id' => 'hc-main-clinic',
                'service_name' => 'Vaccination Services',
                'description' => 'Routine immunizations, travel vaccines, and flu shots',
                'duration_minutes' => 20,
                'price' => 35.00,
                'is_active' => true,
                'schedule' => [
                    'monday' => ['09:00', '16:00'],
                    'tuesday' => ['09:00', '16:00'],
                    'wednesday' => ['09:00', '16:00'],
                    'thursday' => ['09:00', '16:00'],
                    'friday' => ['09:00', '16:00']
                ]
            ],
            // Community Center Services
            [
                'service_id' => 'svc-pediatric-care',
                'health_center_id' => 'hc-community-center',
                'service_name' => 'Pediatric Care',
                'description' => 'Specialized healthcare services for children and adolescents',
                'duration_minutes' => 40,
                'price' => 85.00,
                'is_active' => true,
                'schedule' => [
                    'monday' => ['09:00', '17:00'],
                    'tuesday' => ['09:00', '17:00'],
                    'wednesday' => ['09:00', '17:00'],
                    'thursday' => ['09:00', '17:00'],
                    'friday' => ['09:00', '17:00']
                ]
            ],
            [
                'service_id' => 'svc-mental-health',
                'health_center_id' => 'hc-community-center',
                'service_name' => 'Mental Health Counseling',
                'description' => 'Professional counseling and mental health support services',
                'duration_minutes' => 60,
                'price' => 100.00,
                'is_active' => true,
                'schedule' => [
                    'monday' => ['10:00', '18:00'],
                    'tuesday' => ['10:00', '18:00'],
                    'wednesday' => ['10:00', '18:00'],
                    'thursday' => ['10:00', '18:00'],
                    'friday' => ['10:00', '16:00']
                ]
            ],
            // Urgent Care Services
            [
                'service_id' => 'svc-urgent-care',
                'health_center_id' => 'hc-urgent-care',
                'service_name' => 'Urgent Care',
                'description' => 'Immediate medical attention for non-emergency conditions',
                'duration_minutes' => 30,
                'price' => 150.00,
                'is_active' => true,
                'schedule' => [
                    'monday' => ['06:00', '22:00'],
                    'tuesday' => ['06:00', '22:00'],
                    'wednesday' => ['06:00', '22:00'],
                    'thursday' => ['06:00', '22:00'],
                    'friday' => ['06:00', '22:00'],
                    'saturday' => ['08:00', '20:00'],
                    'sunday' => ['08:00', '20:00']
                ]
            ],
            [
                'service_id' => 'svc-x-ray',
                'health_center_id' => 'hc-urgent-care',
                'service_name' => 'X-Ray Imaging',
                'description' => 'Digital X-ray imaging for diagnostic purposes',
                'duration_minutes' => 25,
                'price' => 200.00,
                'is_active' => true,
                'schedule' => [
                    'monday' => ['08:00', '20:00'],
                    'tuesday' => ['08:00', '20:00'],
                    'wednesday' => ['08:00', '20:00'],
                    'thursday' => ['08:00', '20:00'],
                    'friday' => ['08:00', '20:00'],
                    'saturday' => ['09:00', '17:00']
                ]
            ]
        ];

        foreach ($services as $serviceData) {
            $existing = $this->firestoreService->findByField('services', 'service_id', $serviceData['service_id']);
            
            if (!$existing) {
                $documentId = $this->firestoreService->createDocument('services', $serviceData, $serviceData['service_id']);
                if ($documentId) {
                    $this->info("✅ Created service: {$serviceData['service_name']}");
                }
            } else {
                $this->warn("⚠️  Service already exists: {$serviceData['service_name']}");
            }
        }
    }
}
