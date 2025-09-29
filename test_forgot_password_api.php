<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Forgot Password API Endpoint ===\n\n";

try {
    // Test the exact same email from the mobile app logs
    $testEmail = 'alsolarapole@gmail.com';
    
    echo "📧 Testing forgot password for: $testEmail\n";
    
    // Create a proper HTTP request
    $request = new Illuminate\Http\Request();
    $request->merge(['email' => $testEmail]);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $request->server->set('HTTP_USER_AGENT', 'HealthReach Mobile App Test');
    
    // Initialize controller with all dependencies
    $firebaseService = app(App\Services\FirebaseService::class);
    $firestoreService = app(App\Services\FirestoreService::class);
    $activityLogService = app(App\Services\ActivityLogService::class);
    $passwordResetService = app(App\Services\PasswordResetService::class);
    
    $controller = new App\Http\Controllers\FirebaseAuthController(
        $firebaseService,
        $firestoreService,
        $activityLogService,
        $passwordResetService
    );
    
    echo "🚀 Calling forgotPassword method...\n";
    
    // Call the forgot password method
    $response = $controller->forgotPassword($request);
    
    // Get response data
    $statusCode = $response->getStatusCode();
    $responseData = json_decode($response->getContent(), true);
    
    echo "📊 Response Status Code: $statusCode\n";
    echo "📄 Response Data:\n";
    print_r($responseData);
    
    if ($responseData['success']) {
        echo "\n✅ SUCCESS! Forgot password API is working correctly!\n";
        echo "📧 Email should be sent to: $testEmail\n";
        
        // Check if we're using SMTP or log driver
        $mailDriver = env('MAIL_MAILER', 'smtp');
        if ($mailDriver === 'log') {
            echo "💡 Check Laravel logs for email content (MAIL_MAILER=log)\n";
        } else {
            echo "📬 Check your Gmail inbox for the password reset email\n";
        }
    } else {
        echo "\n❌ FAILED! Error: {$responseData['message']}\n";
        if (isset($responseData['errors'])) {
            echo "Validation errors:\n";
            print_r($responseData['errors']);
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
