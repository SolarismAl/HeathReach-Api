<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== HealthReach Real Email Test ===\n\n";

try {
    // Test with your actual email
    $testEmail = 'alsolarapole@gmail.com'; // Your email from .env
    
    echo "📧 Testing real email sending to: $testEmail\n";
    
    // Initialize services
    $firestoreService = app(App\Services\FirestoreService::class);
    $passwordResetService = app(App\Services\PasswordResetService::class);
    
    // Check if user exists or create one
    $user = $firestoreService->findByField('users', 'email', $testEmail);
    
    if (!$user) {
        echo "Creating test user for your email...\n";
        $userData = [
            'user_id' => 'user-real-' . uniqid(),
            'firebase_uid' => 'firebase-real-' . uniqid(),
            'name' => 'Your Name',
            'email' => $testEmail,
            'role' => 'patient',
            'contact_number' => '+1234567890',
            'address' => 'Your Address',
            'fcm_token' => null,
            'email_verified_at' => null,
            'is_active' => true,
        ];
        
        $documentId = $firestoreService->createDocument('users', $userData, $userData['user_id']);
        if ($documentId) {
            $user = $userData;
            echo "✅ User created\n";
        }
    }
    
    if ($user) {
        echo "🚀 Sending real password reset email...\n";
        $result = $passwordResetService->createPasswordResetToken($user, $testEmail);
        
        if ($result['success']) {
            echo "✅ SUCCESS! Password reset email sent to your Gmail!\n";
            echo "📧 Check your inbox at: $testEmail\n";
            echo "📁 Also check your spam/junk folder\n";
            echo "🔗 The email contains a password reset link\n";
        } else {
            echo "❌ Failed to send email: {$result['message']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
