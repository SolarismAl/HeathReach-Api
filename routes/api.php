<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FirebaseAuthController;
use App\Http\Controllers\FirestoreUserController;
use App\Http\Controllers\HealthCenterController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CustomAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Test routes for debugging
Route::get('test/auth', [TestController::class, 'testAuth']);
Route::get('test/admin', [TestController::class, 'testAdmin']);
Route::get('test/firebase', [TestController::class, 'testFirebase']);
Route::get('test/status', function() {
    return response()->json([
        'success' => true,
        'message' => 'HealthReach API is running',
        'timestamp' => now(),
        'environment' => app()->environment()
    ]);
});

// Debug forgot password endpoint (no email sending)
Route::post('test/forgot-password', function(Request $request) {
    try {
        $email = $request->input('email');
        
        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required'
            ], 400);
        }
        
        // Simulate the forgot password process without actually sending email
        return response()->json([
            'success' => true,
            'message' => 'Password reset instructions have been sent to your email address',
            'debug' => [
                'email' => $email,
                'timestamp' => now(),
                'note' => 'This is a debug endpoint - no email was actually sent'
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Debug forgot password failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('test/quick-appointments', function() {
    return response()->json([
        'success' => true,
        'message' => 'Quick appointments test',
        'data' => [
            [
                'appointment_id' => 'test-1',
                'user_id' => 'user-test',
                'service_name' => 'Test Service',
                'appointment_date' => '2025-01-15',
                'appointment_time' => '10:00',
                'status' => 'pending'
            ]
        ],
        'timestamp' => now()
    ]);
});

Route::get('test/quick-notifications', function() {
    return response()->json([
        'success' => true,
        'message' => 'Quick notifications test',
        'data' => [
            [
                'notification_id' => 'notif-1',
                'user_id' => 'user-test',
                'title' => 'Appointment Reminder',
                'message' => 'You have an upcoming appointment tomorrow at 10:00 AM',
                'date_sent' => '2025-01-14T09:00:00Z',
                'is_read' => false,
                'type' => 'appointment_reminder'
            ],
            [
                'notification_id' => 'notif-2',
                'user_id' => 'user-test',
                'title' => 'Welcome to HealthReach',
                'message' => 'Thank you for joining HealthReach! Book your first appointment today.',
                'date_sent' => '2025-01-13T08:00:00Z',
                'is_read' => true,
                'type' => 'welcome'
            ]
        ],
        'timestamp' => now()
    ]);
});

Route::get('test/firestore-data', function() {
    try {
        $firestoreService = app(\App\Services\FirestoreService::class);
        
        // Get all collections data
        $users = $firestoreService->getCollection('users');
        $healthCenters = $firestoreService->getCollection('health_centers');
        $services = $firestoreService->getCollection('services');
        $appointments = $firestoreService->getCollection('appointments');
        $notifications = $firestoreService->getCollection('notifications');
        
        return response()->json([
            'success' => true,
            'message' => 'Firestore data retrieved',
            'data' => [
                'users_count' => count($users),
                'health_centers_count' => count($healthCenters),
                'services_count' => count($services),
                'appointments_count' => count($appointments),
                'notifications_count' => count($notifications),
                'sample_user' => !empty($users) ? array_values($users)[0] : null,
                'sample_health_center' => !empty($healthCenters) ? array_values($healthCenters)[0] : null,
                'sample_service' => !empty($services) ? array_values($services)[0] : null,
                'sample_appointment' => !empty($appointments) ? array_values($appointments)[0] : null,
                'sample_notification' => !empty($notifications) ? array_values($notifications)[0] : null,
            ],
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()
        ], 500);
    }
});

// Test endpoints without authentication for API testing
Route::get('test/real-health-centers', function() {
    try {
        $firestoreService = app(\App\Services\FirestoreService::class);
        $result = $firestoreService->getHealthCenters();
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::get('test/real-services', function() {
    try {
        $firestoreService = app(\App\Services\FirestoreService::class);
        $result = $firestoreService->getServices();
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::get('test/real-appointments', function() {
    try {
        $firestoreService = app(\App\Services\FirestoreService::class);
        $result = $firestoreService->getAllAppointments();
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::get('test/real-notifications', function() {
    try {
        $firestoreService = app(\App\Services\FirestoreService::class);
        $result = $firestoreService->getAllNotifications();
        
        return response()->json([
            'success' => true,
            'message' => 'Real notifications test',
            'data' => $result,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()
        ]);
    }
});

Route::get('test/debug-notifications/{userId}', function($userId) {
    try {
        $firestoreService = app(\App\Services\FirestoreService::class);
        
        // Get user data
        $userResult = $firestoreService->getUser($userId);
        
        // Get notifications for this user
        $notificationsResult = $firestoreService->getNotificationsByUser($userId);
        
        // Get all notifications to see what exists
        $allNotificationsResult = $firestoreService->getAllNotifications();
        
        return response()->json([
            'success' => true,
            'user_id' => $userId,
            'user_data' => $userResult,
            'user_notifications' => $notificationsResult,
            'all_notifications_count' => count($allNotificationsResult['data'] ?? []),
            'all_notifications_sample' => array_slice($allNotificationsResult['data'] ?? [], 0, 3),
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()
        ]);
    }
});

Route::post('test/create-test-notification/{userId}', function($userId) {
    try {
        $firestoreService = app(\App\Services\FirestoreService::class);
        
        // Create a test notification for this user
        $notificationData = [
            'notification_id' => \Illuminate\Support\Str::uuid()->toString(),
            'user_id' => $userId,
            'title' => 'Test Notification',
            'message' => 'This is a test notification created at ' . now()->toDateTimeString(),
            'date_sent' => now()->toISOString(),
            'is_read' => false,
            'type' => 'general',
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];
        
        $result = $firestoreService->createDocument('notifications', $notificationData);
        
        return response()->json([
            'success' => true,
            'message' => 'Test notification created',
            'notification_data' => $notificationData,
            'create_result' => $result,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()
        ]);
    }
});

Route::get('test/view-logs', function() {
    try {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            return response()->json([
                'success' => false,
                'error' => 'Log file not found',
                'path' => $logPath
            ]);
        }
        
        // Get last 200 lines of log
        $lines = [];
        $file = new \SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $startLine = max(0, $lastLine - 200);
        
        $file->seek($startLine);
        while (!$file->eof()) {
            $lines[] = $file->current();
            $file->next();
        }
        
        return response()->json([
            'success' => true,
            'total_lines' => $lastLine + 1,
            'showing_lines' => count($lines),
            'logs' => implode('', $lines),
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()
        ]);
    }
});

// Authentication routes using Firebase
Route::prefix('auth')->group(function () {
    Route::post('login', [FirebaseAuthController::class, 'login']);
    Route::post('firebase-login', [CustomAuthController::class, 'firebaseLogin']); // Custom auth bypass
    Route::post('google', [FirebaseAuthController::class, 'googleLogin']);
    Route::post('logout', [FirebaseAuthController::class, 'logout'])->middleware('firebase.auth');
    Route::get('profile', [FirebaseAuthController::class, 'profile'])->middleware('firebase.auth');
    Route::put('profile', [FirebaseAuthController::class, 'updateProfile'])->middleware('firebase.auth');
    Route::post('forgot-password', [FirebaseAuthController::class, 'forgotPassword']);
    Route::post('reset-password', [FirebaseAuthController::class, 'resetPassword']);
    Route::post('verify-reset-token', [FirebaseAuthController::class, 'verifyResetToken']);
    
    // Password management routes
    Route::post('change-password', [FirebaseAuthController::class, 'changePassword'])->middleware('firebase.auth');
    Route::post('set-password', [FirebaseAuthController::class, 'setPassword'])->middleware('firebase.auth');
    Route::get('has-password', [FirebaseAuthController::class, 'hasPassword'])->middleware('firebase.auth');
});

// User management routes using Firestore
Route::middleware('firebase.auth')->prefix('users')->group(function () {
    Route::get('/', [FirestoreUserController::class, 'index'])->middleware('firebase.role:admin');
    Route::get('/profile', [FirebaseAuthController::class, 'profile']);
    Route::put('/profile', [FirebaseAuthController::class, 'updateProfile']);
    Route::get('/{id}', [FirestoreUserController::class, 'show']);
    Route::put('/{id}', [FirestoreUserController::class, 'update']);
    Route::delete('/{id}', [FirestoreUserController::class, 'destroy'])->middleware('firebase.role:admin');
});

// Health Centers routes
Route::middleware('firebase.auth')->prefix('health-centers')->group(function () {
    Route::get('/', [HealthCenterController::class, 'index']);
    Route::post('/', [HealthCenterController::class, 'store'])->middleware('firebase.role:admin,health_worker');
    Route::get('/{id}', [HealthCenterController::class, 'show']);
    Route::put('/{id}', [HealthCenterController::class, 'update'])->middleware('firebase.role:admin,health_worker');
    Route::delete('/{id}', [HealthCenterController::class, 'destroy'])->middleware('firebase.role:admin,health_worker');
});

// Services routes
Route::prefix('services')->middleware('firebase.auth')->group(function () {
    Route::get('/', [ServiceController::class, 'index']);
    Route::get('/{id}', [ServiceController::class, 'show']);
    Route::post('/', [ServiceController::class, 'store'])->middleware('firebase.role:admin,health_worker');
    Route::put('/{id}', [ServiceController::class, 'update'])->middleware('firebase.role:admin,health_worker');
    Route::delete('/{id}', [ServiceController::class, 'destroy'])->middleware('firebase.role:admin,health_worker');
});

// Appointments routes
Route::prefix('appointments')->middleware('firebase.auth')->group(function () {
    Route::get('/', [AppointmentController::class, 'index']);
    Route::get('/{id}', [AppointmentController::class, 'show']);
    Route::post('/', [AppointmentController::class, 'store']);
    Route::put('/{id}', [AppointmentController::class, 'update']);
    Route::delete('/{id}', [AppointmentController::class, 'destroy']);
    Route::patch('/{id}/status', [AppointmentController::class, 'updateStatus'])->middleware('firebase.role:health_worker,admin');
});

// Notifications routes
Route::prefix('notifications')->middleware('firebase.auth')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/', [NotificationController::class, 'store'])->middleware('firebase.role:admin,health_worker');
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
});

// Device tokens routes
Route::prefix('device-tokens')->middleware('firebase.auth')->group(function () {
    Route::post('/', [DeviceTokenController::class, 'store']);
    Route::delete('/{token}', [DeviceTokenController::class, 'destroy']);
});

// Admin routes
Route::prefix('admin')->middleware(['firebase.auth', 'firebase.role:admin'])->group(function () {
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/logs', [LogController::class, 'index']);
});

// Health Worker specific routes
Route::prefix('health-worker')->middleware(['firebase.auth', 'firebase.role:health_worker,admin'])->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'getHealthWorkerAppointments']);
    Route::patch('/appointments/{id}/approve', [AppointmentController::class, 'approveAppointment']);
    Route::patch('/appointments/{id}/reject', [AppointmentController::class, 'rejectAppointment']);
    Route::patch('/appointments/{id}/complete', [AppointmentController::class, 'completeAppointment']);
});
