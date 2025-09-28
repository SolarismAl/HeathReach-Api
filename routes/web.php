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
    Route::put('/appointments/{id}/status', [WebAdminController::class, 'updateAppointmentStatus'])->name('appointments.update-status');
    
    // Activity Logs
    Route::get('/logs', [WebAdminController::class, 'logs'])->name('logs');
    
    // Debug route for appointments
    Route::get('/debug/appointments', function() {
        $firestoreService = app(App\Services\FirestoreService::class);
        $appointments = $firestoreService->getCollection('appointments');
        
        echo "<h1>Debug Appointments Data</h1>";
        echo "<h2>Total Appointments: " . count($appointments) . "</h2>";
        echo "<pre>";
        print_r($appointments);
        echo "</pre>";
        
        return response()->json([
            'count' => count($appointments),
            'data' => $appointments
        ]);
    })->name('debug.appointments');
    
    // Show recent Laravel logs
    Route::get('/debug/logs', function() {
        $logFile = storage_path('logs/laravel.log');
        
        if (!file_exists($logFile)) {
            return "Log file not found at: " . $logFile;
        }
        
        // Get last 100 lines of the log file
        $lines = file($logFile);
        $recentLines = array_slice($lines, -100);
        
        echo "<h1>📋 Recent Laravel Logs (Last 100 lines)</h1>";
        echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto; white-space: pre-wrap;'>";
        echo htmlspecialchars(implode('', $recentLines));
        echo "</pre>";
        
        return response()->json(['log_lines' => count($recentLines)]);
    })->name('debug.logs');
    
    // Test appointment update directly with inline error catching
    Route::get('/test/update-appointment/{id}', function($id) {
        $firestoreService = app(App\Services\FirestoreService::class);
        
        echo "<h1>🧪 Test Appointment Update with Error Details</h1>";
        echo "<p><strong>Appointment ID:</strong> {$id}</p>";
        
        // Get current appointment
        $appointment = $firestoreService->getDocument('appointments', $id);
        echo "<h2>📄 Current Appointment Data:</h2>";
        echo "<pre>";
        print_r($appointment);
        echo "</pre>";
        
        if (!$appointment) {
            echo "<p style='color: red;'>❌ Appointment not found!</p>";
            return;
        }
        
        // Try to update with direct Firestore access
        $updateData = [
            'status' => 'confirmed',
            'test_update' => 'test_value_' . time(),
            'updated_at' => now()->toISOString()
        ];
        
        echo "<h2>🔄 Attempting Direct Firestore Update...</h2>";
        echo "<p><strong>Update Data:</strong></p>";
        echo "<pre>";
        print_r($updateData);
        echo "</pre>";
        
        try {
            // Use our fixed FirestoreService
            $result = $firestoreService->updateDocument('appointments', $id, $updateData);
            
            if ($result) {
                echo "<p style='color: green;'>✅ FirestoreService update successful!</p>";
                
                // Verify update
                $updatedAppointment = $firestoreService->getDocument('appointments', $id);
                echo "<h2>📄 Updated Document Data:</h2>";
                echo "<pre>";
                print_r($updatedAppointment);
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>❌ FirestoreService update failed!</p>";
                echo "<p>Check Laravel logs for detailed error information.</p>";
                $result = false;
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>❌ Direct Firestore Error:</h2>";
            echo "<p><strong>Error Message:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
            echo "<p><strong>Error File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
            $result = false;
        }
        
        return response()->json([
            'success' => $result,
            'appointment' => $appointment,
            'updateData' => $updateData
        ]);
    })->name('test.update-appointment');
    
    // Debug raw services data from Firestore
    Route::get('/debug/services', function() {
        $firestoreService = app(App\Services\FirestoreService::class);
        $services = $firestoreService->getCollection('services');
        
        echo "<h1>🔍 Raw Services Data from Firestore</h1>";
        echo "<h2>Total Services: " . count($services) . "</h2>";
        
        foreach ($services as $docId => $data) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
            echo "<h3>📄 Service Document ID: <code>{$docId}</code></h3>";
            echo "<p><strong>Name:</strong> " . ($data['name'] ?? $data['service_name'] ?? 'N/A') . "</p>";
            echo "<p><strong>Health Center ID:</strong> " . ($data['health_center_id'] ?? 'N/A') . "</p>";
            echo "<p><strong>Price:</strong> " . ($data['price'] ?? 'N/A') . "</p>";
            echo "<h4>🗂️ Complete Data:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto;'>";
            print_r($data);
            echo "</pre>";
            echo "</div>";
        }
        
        return response()->json([
            'count' => count($services),
            'data' => $services
        ]);
    })->name('debug.services');
    
    // Debug raw health centers data from Firestore
    Route::get('/debug/health-centers', function() {
        $firestoreService = app(App\Services\FirestoreService::class);
        $healthCenters = $firestoreService->getCollection('health_centers');
        
        echo "<h1>🔍 Raw Health Centers Data from Firestore</h1>";
        echo "<h2>Total Health Centers: " . count($healthCenters) . "</h2>";
        
        foreach ($healthCenters as $docId => $data) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
            echo "<h3>📄 Document ID: <code>{$docId}</code></h3>";
            echo "<p><strong>Name:</strong> " . ($data['name'] ?? 'N/A') . "</p>";
            echo "<p><strong>Internal health_center_id:</strong> " . ($data['health_center_id'] ?? 'N/A') . "</p>";
            echo "<p><strong>Address:</strong> " . ($data['address'] ?? 'N/A') . "</p>";
            echo "<p><strong>Email:</strong> " . ($data['email'] ?? 'N/A') . "</p>";
            echo "<h4>🗂️ Complete Data:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto;'>";
            print_r($data);
            echo "</pre>";
            echo "</div>";
        }
        
        echo "<hr>";
        echo "<h2>🎯 Appointment Health Center IDs Check</h2>";
        $appointmentHealthCenterIds = ['0476757767e941bf8804', 'b2c1915005594f58aee7', '4c0f11737be74cce8e3c'];
        foreach ($appointmentHealthCenterIds as $hcId) {
            if (isset($healthCenters[$hcId])) {
                echo "<p>✅ <strong>Found:</strong> {$hcId} → " . ($healthCenters[$hcId]['name'] ?? 'NO NAME') . "</p>";
            } else {
                echo "<p>❌ <strong>Missing:</strong> {$hcId}</p>";
            }
        }
        
        return response()->json([
            'count' => count($healthCenters),
            'data' => $healthCenters,
            'appointment_health_center_ids' => $appointmentHealthCenterIds
        ]);
    })->name('debug.health-centers');
    
    // Fix health center data corruption
    Route::get('/fix/health-center', function() {
        $firestoreService = app(App\Services\FirestoreService::class);
        
        // The correct data for City Health Office
        $cityHealthOfficeData = [
            'health_center_id' => 'f5ca16e6-809a-457d-85c8-9210d3248b7a',
            'name' => 'City Health Office',
            'address' => 'Magallenes , Surigao City',
            'contact_number' => '12346789',
            'email' => 'city@gmail.com',
            'description' => 'City Health Office - Description',
            'is_active' => true,
            'created_at' => '2025-09-27T06:13:29.314231Z',
            'updated_at' => now()->toISOString()
        ];
        
        // Update the corrupted document
        $result = $firestoreService->updateDocument('health_centers', 'b2c1915005594f58aee7', $cityHealthOfficeData);
        
        if ($result) {
            echo "<h1>✅ Health Center Data Fixed!</h1>";
            echo "<p>Document 'b2c1915005594f58aee7' has been updated with correct City Health Office data.</p>";
            echo "<pre>";
            print_r($cityHealthOfficeData);
            echo "</pre>";
            echo "<p><a href='/admin/debug/health-centers'>Check Raw Data</a> | <a href='/health-worker/health-centers'>Check Health Centers Page</a></p>";
        } else {
            echo "<h1>❌ Failed to Fix Health Center Data</h1>";
        }
        
        return response()->json([
            'success' => $result,
            'data' => $cityHealthOfficeData
        ]);
    })->name('fix.health-center');
});

// Health Worker Routes
Route::middleware(['web.auth:health_worker'])->prefix('health-worker')->name('health-worker.')->group(function () {
    Route::get('/dashboard', [WebHealthWorkerController::class, 'dashboard'])->name('dashboard');
    
    // Appointments Management
    Route::get('/appointments', [WebHealthWorkerController::class, 'appointments'])->name('appointments');
    Route::post('/appointments/{id}/status', [WebHealthWorkerController::class, 'updateAppointmentStatus'])->name('appointments.update-status');
    
    // Test route to check if health worker routes work
    Route::get('/test-route', function() {
        \Log::info('=== HEALTH WORKER TEST ROUTE ACCESSED ===');
        \Log::info('Session data:', [session()->all()]);
        \Log::info('User data:', [session('user')]);
        return 'Health worker test route works! Check Laravel logs.';
    })->name('test-route');
    
    // Test POST route to check if POST requests work
    Route::post('/test-post', function(\Illuminate\Http\Request $request) {
        \Log::info('=== HEALTH WORKER TEST POST ROUTE ACCESSED ===');
        \Log::info('Request data:', [$request->all()]);
        \Log::info('Request method: ' . $request->method());
        return 'Health worker POST test works! Data: ' . json_encode($request->all());
    })->name('test-post');
    
    // Test form for POST requests
    Route::get('/test-form', function() {
        return '
        <h1>Health Worker POST Test Form</h1>
        <form method="POST" action="/health-worker/test-post">
            ' . csrf_field() . '
            <input type="text" name="test_field" value="test_value" />
            <button type="submit">Submit Test</button>
        </form>
        
        <h2>Test Appointment Status Update</h2>
        <form method="POST" action="/health-worker/appointments/20bc1114-63d9-4c7a-9094-7946f0e37a48/status">
            ' . csrf_field() . '
            <input type="hidden" name="status" value="confirmed" />
            <textarea name="notes">Test notes</textarea>
            <button type="submit">Update Appointment Status</button>
        </form>
        ';
    })->name('test-form');
    
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
