<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\FirebaseAuthController;
use App\Services\FirebaseService;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use App\Services\PasswordResetService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== HealthReach Password Reset Test ===\n\n";

try {
    // Initialize services
    $firestoreService = app(FirestoreService::class);
    $firebaseService = app(FirebaseService::class);
    $activityLogService = app(ActivityLogService::class);
    $passwordResetService = app(PasswordResetService::class);
    
    echo "✅ Services initialized successfully\n";
    
    // Test email (you can change this to your email)
    $testEmail = 'cr46112@gmail.com';
    
    // Check if user exists
    echo "🔍 Checking if user exists with email: $testEmail\n";
    $user = $firestoreService->findByField('users', 'email', $testEmail);
    
    if (!$user) {
        echo "❌ User not found. Creating test user...\n";
        
        // Create a test user
        $userData = [
            'user_id' => 'user-test-' . uniqid(),
            'firebase_uid' => 'firebase-test-' . uniqid(),
            'name' => 'Test User',
            'email' => $testEmail,
            'role' => 'patient',
            'contact_number' => '+1234567890',
            'address' => 'Test Address',
            'fcm_token' => null,
            'email_verified_at' => null,
            'is_active' => true,
        ];
        
        $documentId = $firestoreService->createDocument('users', $userData, $userData['user_id']);
        
        if ($documentId) {
            echo "✅ Test user created successfully\n";
            $user = $userData;
        } else {
            echo "❌ Failed to create test user\n";
            exit(1);
        }
    } else {
        echo "✅ User found: {$user['name']}\n";
    }
    
    // Test password reset token creation
    echo "\n📧 Testing password reset email...\n";
    $result = $passwordResetService->createPasswordResetToken($user, $testEmail);
    
    if ($result['success']) {
        echo "✅ Password reset process completed successfully!\n";
        echo "📧 Email would be sent to: $testEmail\n";
        echo "💡 Check your Laravel logs for email content (since MAIL_MAILER=log)\n";
    } else {
        echo "❌ Password reset failed: {$result['message']}\n";
    }
    
    // Test via HTTP controller
    echo "\n🌐 Testing via HTTP Controller...\n";
    
    $controller = new FirebaseAuthController(
        $firebaseService,
        $firestoreService,
        $activityLogService,
        $passwordResetService
    );
    
    // Create mock request
    $request = new Request();
    $request->merge(['email' => $testEmail]);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $request->server->set('HTTP_USER_AGENT', 'Test Script');
    
    $response = $controller->forgotPassword($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success']) {
        echo "✅ HTTP Controller test successful!\n";
        echo "📧 Message: {$responseData['message']}\n";
    } else {
        echo "❌ HTTP Controller test failed: {$responseData['message']}\n";
    }
    
    echo "\n=== Test Complete ===\n";
    echo "📝 Next steps:\n";
    echo "1. Configure real SMTP settings in .env file\n";
    echo "2. Test with your actual email address\n";
    echo "3. Check Laravel logs for email content\n";
    echo "4. Test the mobile app forgot password feature\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
