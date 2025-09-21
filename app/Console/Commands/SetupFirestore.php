<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;
use App\Services\FirestoreService;
use Exception;

class SetupFirestore extends Command
{
    protected $signature = 'firestore:setup';
    protected $description = 'Initialize Firestore database with required collections';

    private FirebaseService $firebaseService;
    private FirestoreService $firestoreService;

    public function __construct(FirebaseService $firebaseService, FirestoreService $firestoreService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService;
        $this->firestoreService = $firestoreService;
    }

    public function handle()
    {
        $this->info('=== HealthReach Firestore Setup ===');
        $this->info('Initializing Firestore database with required collections...');
        $this->newLine();

        try {
            // Test Firestore connection
            $this->info('Testing Firestore connection...');
            
            $testData = [
                'test' => true,
                'created_at' => now()->toISOString(),
                'message' => 'Firestore setup test'
            ];
            
            $result = $this->firestoreService->createDocument('_test', $testData, 'setup-test');
            
            if ($result) {
                $this->info('✓ Firestore connection successful!');
                
                // Clean up test document
                $this->firestoreService->deleteDocument('_test', 'setup-test');
                $this->info('✓ Test cleanup completed');
                $this->newLine();
                
                $this->info('Creating required collections...');
                $this->createCollections();
                
                $this->newLine();
                $this->info('🎉 Firestore setup completed successfully!');
                $this->info('Your HealthReach backend is now ready for authentication.');
                
            } else {
                $this->error('✗ Firestore connection failed!');
                $this->showFirestoreInstructions();
            }
            
        } catch (Exception $e) {
            $this->error('✗ Setup failed: ' . $e->getMessage());
            $this->newLine();
            $this->showFirestoreInstructions();
        }
    }

    private function createCollections()
    {
        $collections = [
            'users' => 'User profiles and authentication data',
            'health_centers' => 'Healthcare facilities and centers',
            'services' => 'Healthcare services offered',
            'appointments' => 'Patient appointments and bookings',
            'notifications' => 'Push notifications and messages',
            'device_tokens' => 'FCM device tokens for push notifications',
            'logs' => 'Activity logs and audit trail'
        ];

        foreach ($collections as $name => $description) {
            $this->info("  Creating collection: $name");
            $this->line("  Description: $description");
            
            // Create a placeholder document to initialize the collection
            $placeholderData = [
                'placeholder' => true,
                'collection' => $name,
                'description' => $description,
                'created_at' => now()->toISOString()
            ];
            
            $result = $this->firestoreService->createDocument($name, $placeholderData, '_placeholder');
            
            if ($result) {
                $this->info("  ✓ Collection $name created successfully");
            } else {
                $this->error("  ✗ Failed to create collection $name");
            }
        }
    }

    private function showFirestoreInstructions()
    {
        $this->newLine();
        $this->warn('Firestore database is not enabled for your project.');
        $this->info('Please follow these steps:');
        $this->newLine();
        $this->info('1. Visit: https://console.firebase.google.com/project/healthreach-9167b/firestore');
        $this->info('2. Click "Create database"');
        $this->info('3. Choose "Start in test mode" (for development)');
        $this->info('4. Select a location (e.g., us-central1)');
        $this->info('5. Click "Done"');
        $this->newLine();
        $this->info('After enabling Firestore, run this command again:');
        $this->info('php artisan firestore:setup');
    }
}
