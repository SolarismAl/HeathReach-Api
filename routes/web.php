<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebAdminController;
use App\Http\Controllers\WebHealthWorkerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Admin Routes
Route::middleware(['web.auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [WebAdminController::class, 'dashboard'])->name('dashboard');
    
    // Users Management
    Route::get('/users', [WebAdminController::class, 'users'])->name('users');
    Route::delete('/users/{id}', [WebAdminController::class, 'deleteUser'])->name('users.delete');
    
    // Health Centers Management
    Route::get('/health-centers', [WebAdminController::class, 'healthCenters'])->name('health-centers');
    Route::get('/health-centers/create', [WebAdminController::class, 'createHealthCenter'])->name('health-centers.create');
    Route::post('/health-centers', [WebAdminController::class, 'storeHealthCenter'])->name('health-centers.store');
    Route::get('/health-centers/{id}/edit', [WebAdminController::class, 'editHealthCenter'])->name('health-centers.edit');
    Route::put('/health-centers/{id}', [WebAdminController::class, 'updateHealthCenter'])->name('health-centers.update');
    Route::delete('/health-centers/{id}', [WebAdminController::class, 'deleteHealthCenter'])->name('health-centers.delete');
    
    // Appointments Management
    Route::get('/appointments', [WebAdminController::class, 'appointments'])->name('appointments');
    
    // Activity Logs
    Route::get('/logs', [WebAdminController::class, 'logs'])->name('logs');
});

// Health Worker Routes
Route::middleware(['web.auth:health_worker'])->prefix('health-worker')->name('health-worker.')->group(function () {
    Route::get('/dashboard', [WebHealthWorkerController::class, 'dashboard'])->name('dashboard');
    
    // Appointments Management
    Route::get('/appointments', [WebHealthWorkerController::class, 'appointments'])->name('appointments');
    Route::post('/appointments/{id}/status', [WebHealthWorkerController::class, 'updateAppointmentStatus'])->name('appointments.update-status');
    
    // Health Centers Management
    Route::get('/health-centers', [WebHealthWorkerController::class, 'healthCenters'])->name('health-centers');
    
    // Services Management
    Route::get('/services', [WebHealthWorkerController::class, 'services'])->name('services');
    Route::get('/services/create', [WebHealthWorkerController::class, 'createService'])->name('services.create');
    Route::post('/services', [WebHealthWorkerController::class, 'storeService'])->name('services.store');
    Route::get('/services/{id}/edit', [WebHealthWorkerController::class, 'editService'])->name('services.edit');
    Route::put('/services/{id}', [WebHealthWorkerController::class, 'updateService'])->name('services.update');
    Route::delete('/services/{id}', [WebHealthWorkerController::class, 'deleteService'])->name('services.delete');
});
