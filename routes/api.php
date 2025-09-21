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

// Authentication routes using Firebase
Route::prefix('auth')->group(function () {
    Route::post('register', [FirebaseAuthController::class, 'register']);
    Route::post('login', [FirebaseAuthController::class, 'login']);
    Route::post('firebase-login', [CustomAuthController::class, 'firebaseLogin']); // Custom auth bypass
    Route::post('google', [FirebaseAuthController::class, 'googleLogin']);
    Route::post('logout', [FirebaseAuthController::class, 'logout'])->middleware('firebase.auth');
    Route::get('profile', [FirebaseAuthController::class, 'profile'])->middleware('firebase.auth');
    Route::post('forgot-password', [FirebaseAuthController::class, 'forgotPassword']);
});

// User management routes using Firestore
Route::middleware('firebase.auth')->prefix('users')->group(function () {
    Route::get('/', [FirestoreUserController::class, 'index'])->middleware('firebase.role:admin');
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
