<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Updating User Role to Admin ===" . PHP_EOL;

try {
    $firestoreService = app(\App\Services\FirestoreService::class);
    
    $email = 'admin@healthreach.com';
    
    echo "Searching for user with email: $email" . PHP_EOL;
    
    // Find user by email
    $user = $firestoreService->findByField('users', 'email', $email);
    
    if ($user) {
        echo "✅ User found!" . PHP_EOL;
        echo "Current role: " . ($user['role'] ?? 'not set') . PHP_EOL;
        echo "User ID: " . $user['user_id'] . PHP_EOL;
        
        // Update role to admin
        $updateData = [
            'role' => 'admin',
            'updated_at' => now()->toISOString()
        ];
        
        $result = $firestoreService->updateDocument('users', $user['user_id'], $updateData);
        
        if ($result) {
            echo "✅ Role updated successfully!" . PHP_EOL;
            echo "📧 Email: $email" . PHP_EOL;
            echo "🔑 Password: admin123456" . PHP_EOL;
            echo "👤 Role: admin" . PHP_EOL;
            echo PHP_EOL . "🎉 You can now login as admin!" . PHP_EOL;
        } else {
            echo "❌ Failed to update role" . PHP_EOL;
        }
        
    } else {
        echo "❌ User not found with email: $email" . PHP_EOL;
        echo "Please make sure you registered with this exact email address." . PHP_EOL;
        
        // List all users to help debug
        echo PHP_EOL . "Listing all users to help debug:" . PHP_EOL;
        $allUsers = $firestoreService->getCollection('users');
        
        if ($allUsers && count($allUsers) > 0) {
            foreach ($allUsers as $userId => $userData) {
                echo "- Email: " . ($userData['email'] ?? 'N/A') . 
                     " | Role: " . ($userData['role'] ?? 'N/A') . 
                     " | Name: " . ($userData['name'] ?? 'N/A') . PHP_EOL;
            }
        } else {
            echo "No users found in database." . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "Script completed." . PHP_EOL;
