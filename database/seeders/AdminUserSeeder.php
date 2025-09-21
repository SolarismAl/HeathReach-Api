<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\FirebaseService;
use App\Services\FirestoreService;
use Exception;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds - Create admin user in Firebase Auth and Firestore
     */
    public function run(): void
    {
        try {
            $firebaseService = app(FirebaseService::class);
            $firestoreService = app(FirestoreService::class);

            // Create admin user in Firebase Auth
            $result = $firebaseService->createUser(
                'admin@healthreach.com',
                'admin1234',
                'Admin',
                'admin'
            );

            if ($result['success']) {
                // Store admin data in Firestore users collection
                $userData = [
                    'firebase_uid' => $result['uid'],
                    'name' => 'Admin',
                    'email' => 'admin@healthreach.com',
                    'role' => 'admin',
                    'contact_number' => '+1-555-ADMIN',
                    'address' => 'HealthReach Headquarters',
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString(),
                ];

                $firestore = $firebaseService->getFirestore();
                $firestore->database()->collection('users')->document($result['uid'])->set($userData);

                echo "Admin user created successfully in Firebase Auth and Firestore\n";
            } else {
                echo "Failed to create admin user: " . $result['error'] . "\n";
            }
        } catch (Exception $e) {
            echo "Error creating admin user: " . $e->getMessage() . "\n";
        }
    }
}
