<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use Exception;

class WebAdminController extends Controller
{
    protected $firestoreService;
    protected $activityLogService;

    public function __construct(FirestoreService $firestoreService, ActivityLogService $activityLogService)
    {
        $this->firestoreService = $firestoreService;
        $this->activityLogService = $activityLogService;
    }

    public function dashboard()
    {
        \Log::info('=== ADMIN DASHBOARD LOADED ===');
        
        // Get statistics
        $stats = $this->getStats();
        $recentUsers = $this->firestoreService->getCollection('users', ['limit' => 5]);
        $rawAppointments = $this->firestoreService->getCollection('appointments', ['limit' => 5]);

        // Process appointments to ensure service data is populated
        $recentAppointments = [];
        foreach ($rawAppointments as $appointmentId => $appointmentData) {
            $recentAppointments[$appointmentId] = $appointmentData;
            
            // Ensure service data is populated using the same helper methods as appointments page
            if (!isset($appointmentData['service']) || $appointmentData['service'] === null) {
                \Log::info('Dashboard: Service data is null for appointment ' . $appointmentId . ', attempting to fetch by service_id');
                
                if (isset($appointmentData['service_id'])) {
                    try {
                        $serviceResult = $this->firestoreService->getService($appointmentData['service_id']);
                        if ($serviceResult['success'] && isset($serviceResult['data'])) {
                            $recentAppointments[$appointmentId]['service'] = [
                                'service_name' => $serviceResult['data']->service_name ?? 'N/A',
                                'price' => $serviceResult['data']->price ?? 0,
                                'duration_minutes' => $serviceResult['data']->duration_minutes ?? 0,
                            ];
                            \Log::info('Dashboard: Successfully populated service data for appointment ' . $appointmentId);
                        }
                    } catch (Exception $e) {
                        \Log::error('Dashboard: Error fetching service for appointment ' . $appointmentId . ': ' . $e->getMessage());
                    }
                }
            }
        }

        // Debug appointments data
        \Log::info('Recent appointments from dashboard (processed):', $recentAppointments);
        \Log::info('Recent appointments count: ' . count($recentAppointments));

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentAppointments'));
    }

    public function users(Request $request)
    {
        $filters = [];
        if ($request->has('role') && $request->role !== '') {
            $filters['role'] = $request->role;
        }

        $users = $this->firestoreService->getCollection('users', $filters);
        return view('admin.users', compact('users'));
    }

    public function deleteUser($id)
    {
        \Log::info('=== DELETE USER REQUEST ===');
        \Log::info('User ID to delete: ' . $id);
        
        try {
            // Check if user exists
            $user = $this->firestoreService->getDocument('users', $id);
            if (!$user) {
                \Log::error('User not found for deletion: ' . $id);
                return redirect()->route('admin.users')->with('error', 'User not found.');
            }
            
            \Log::info('User found, proceeding with deletion:', $user);
            
            // Delete the user from Firestore
            $result = $this->firestoreService->deleteDocument('users', $id);
            
            if ($result) {
                \Log::info('User deleted successfully: ' . $id);
                
                // Log activity
                $this->activityLogService->log(
                    session('firebase_uid'),
                    'user_deleted',
                    'User deleted: ' . ($user['name'] ?? $user['email'] ?? $id)
                );
                
                return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
            } else {
                \Log::error('Failed to delete user: ' . $id);
                return redirect()->route('admin.users')->with('error', 'Failed to delete user.');
            }
            
        } catch (Exception $e) {
            \Log::error('Error deleting user: ' . $e->getMessage());
            return redirect()->route('admin.users')->with('error', 'Error deleting user: ' . $e->getMessage());
        }
    }

    public function healthCenters()
    {
        \Log::info('=== HEALTH CENTERS PAGE LOADED ===');
        $healthCenters = $this->firestoreService->getCollection('health_centers');
        \Log::info('Health Centers Raw Data:', $healthCenters);
        \Log::info('Health Centers Count: ' . count($healthCenters));
        
        // Log each health center
        foreach ($healthCenters as $id => $center) {
            \Log::info('Health Center Firestore Doc ID: ' . $id . ', Internal health_center_id: ' . ($center['health_center_id'] ?? 'NO INTERNAL ID') . ', Name: ' . ($center['name'] ?? 'NO NAME'));
        }
        
        // Check for specific health center IDs from appointments
        $appointmentHealthCenterIds = ['0476757767e941bf8804', 'b2c1915005594f58aee7', '4c0f11737be74cce8e3c'];
        foreach ($appointmentHealthCenterIds as $hcId) {
            if (isset($healthCenters[$hcId])) {
                \Log::info('Found health center with ID ' . $hcId . ': ' . ($healthCenters[$hcId]['name'] ?? 'NO NAME'));
            } else {
                \Log::warning('Missing health center with ID: ' . $hcId);
            }
        }
        
        return view('admin.health-centers', compact('healthCenters'));
    }

    public function createHealthCenter()
    {
        return view('admin.create-health-center');
    }

    public function storeHealthCenter(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $healthCenterData = [
            'health_center_id' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => $request->name,
            'address' => $request->address,
            'contact_number' => $request->contact_number,
            'email' => $request->email,
            'description' => $request->description,
            'is_active' => true,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        $this->firestoreService->createDocument('health_centers', $healthCenterData);

        // Log activity
        $this->activityLogService->log(
            session('firebase_uid'),
            'health_center_created',
            'Health center created: ' . $request->name
        );

        return redirect()->route('admin.health-centers')->with('success', 'Health center created successfully.');
    }

    public function editHealthCenter($id)
    {
        $healthCenter = $this->firestoreService->getDocument('health_centers', $id);
        if (!$healthCenter) {
            return redirect()->route('admin.health-centers')->with('error', 'Health center not found.');
        }
        return view('admin.edit-health-center', compact('healthCenter', 'id'));
    }

    public function updateHealthCenter(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $updateData = [
            'name' => $request->name,
            'address' => $request->address,
            'contact_number' => $request->contact_number,
            'email' => $request->email,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'updated_at' => now()->toISOString(),
        ];

        $this->firestoreService->updateDocument('health_centers', $id, $updateData);

        // Log activity
        $this->activityLogService->log(
            session('firebase_uid'),
            'health_center_updated',
            'Health center updated: ' . $request->name
        );

        return redirect()->route('admin.health-centers')->with('success', 'Health center updated successfully.');
    }

    public function deleteHealthCenter($id)
    {
        $healthCenter = $this->firestoreService->getDocument('health_centers', $id);
        if (!$healthCenter) {
            return redirect()->route('admin.health-centers')->with('error', 'Health center not found.');
        }

        $this->firestoreService->deleteDocument('health_centers', $id);

        // Log activity
        $this->activityLogService->log(
            session('firebase_uid'),
            'health_center_deleted',
            'Health center deleted: ' . $healthCenter['name']
        );

        return redirect()->route('admin.health-centers')->with('success', 'Health center deleted successfully.');
    }

    public function appointments()
    {
        \Log::info('=== APPOINTMENTS MANAGEMENT PAGE LOADED ===');
        
        try {
            // Try using raw Firestore data first since it already has nested structure
            $rawAppointments = $this->firestoreService->getCollection('appointments');
            \Log::info('=== APPOINTMENTS RAW DATA ===');
            \Log::info('Raw appointments from Firestore:', $rawAppointments);
            \Log::info('Appointments count: ' . count($rawAppointments));
            
            $appointments = [];
            foreach ($rawAppointments as $appointmentId => $appointmentData) {
                \Log::info('=== PROCESSING APPOINTMENT ===');
                \Log::info('Appointment ID: ' . $appointmentId);
                \Log::info('Raw appointment data:', $appointmentData);
                
                // Log specific nested data
                \Log::info('User data check:', [
                    'user_exists' => isset($appointmentData['user']),
                    'user_data' => $appointmentData['user'] ?? 'NOT SET',
                    'user_name' => isset($appointmentData['user']['name']) ? $appointmentData['user']['name'] : 'NO NAME'
                ]);
                
                \Log::info('Health Center data check:', [
                    'health_center_exists' => isset($appointmentData['health_center']),
                    'health_center_data' => $appointmentData['health_center'] ?? 'NOT SET',
                    'health_center_name' => isset($appointmentData['health_center']['name']) ? $appointmentData['health_center']['name'] : 'NO NAME'
                ]);
                
                \Log::info('Service data check:', [
                    'service_exists' => isset($appointmentData['service']),
                    'service_data' => $appointmentData['service'] ?? 'NOT SET',
                    'service_id' => $appointmentData['service_id'] ?? 'NO SERVICE ID',
                    'service_name' => isset($appointmentData['service']['service_name']) ? $appointmentData['service']['service_name'] : 'NO SERVICE NAME'
                ]);
                
                // Transform raw Firestore data to view format
                $appointments[$appointmentId] = [
                    'appointment_id' => $appointmentId,
                    'user_id' => $appointmentData['user_id'] ?? '',
                    'health_center_id' => $appointmentData['health_center_id'] ?? '',
                    'service_id' => $appointmentData['service_id'] ?? '',
                    'date' => $appointmentData['date'] ?? '',
                    'time' => $appointmentData['time'] ?? '',
                    'status' => $appointmentData['status'] ?? 'pending',
                    'remarks' => $appointmentData['remarks'] ?? '',
                    'created_at' => $appointmentData['created_at'] ?? '',
                    'updated_at' => $appointmentData['updated_at'] ?? '',
                    
                    // Extract nested data directly from Firestore
                    'patient_name' => $this->getPatientName($appointmentData),
                    'patient_phone' => isset($appointmentData['user']['contact_number']) ? $appointmentData['user']['contact_number'] : (isset($appointmentData['user']['phone']) ? $appointmentData['user']['phone'] : null),
                    'patient_email' => isset($appointmentData['user']['email']) ? $appointmentData['user']['email'] : null,
                    
                    'health_center_name' => isset($appointmentData['health_center']['name']) ? $appointmentData['health_center']['name'] : 'N/A',
                    'health_center_address' => isset($appointmentData['health_center']['address']) ? $appointmentData['health_center']['address'] : null,
                    
                    'service_name' => $this->getServiceName($appointmentData),
                    'service_price' => $this->getServicePrice($appointmentData),
                    'service_duration' => $this->getServiceDuration($appointmentData),
                ];
                
                \Log::info('Transformed raw appointment:', $appointments[$appointmentId]);
            }
            
            return view('admin.appointments', compact('appointments'));
            
        } catch (Exception $e) {
            \Log::error('Error loading appointments:', ['error' => $e->getMessage()]);
            $appointments = [];
            return view('admin.appointments', compact('appointments'));
        }
    }

    public function updateAppointmentStatus(Request $request, $id)
    {
        \Log::info('=== UPDATE APPOINTMENT STATUS ===');
        \Log::info('Appointment ID:', ['id' => $id]);
        \Log::info('Request data:', $request->all());
        
        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        try {
            // Get the appointment first
            $appointment = $this->firestoreService->getDocument('appointments', $id);
            \Log::info('Current appointment data:', $appointment);
            
            if (!$appointment) {
                \Log::error('Appointment not found:', ['id' => $id]);
                return redirect()->back()->with('error', 'Appointment not found.');
            }

            // Prepare update data
            $updateData = [
                'status' => $request->status,
                'updated_at' => now()->toISOString(),
            ];

            // Add admin notes if provided
            if ($request->filled('notes')) {
                $updateData['admin_notes'] = $request->notes;
            }

            \Log::info('Updating appointment with data:', $updateData);
            
            // Update the appointment
            $result = $this->firestoreService->updateDocument('appointments', $id, $updateData);
            \Log::info('Update result:', ['result' => $result]);

            // Log activity
            $this->activityLogService->log(
                session('firebase_uid'),
                'appointment_status_updated',
                'Updated appointment status to: ' . $request->status . ' for appointment ID: ' . $id
            );

            return redirect()->back()->with('success', 'Appointment status updated successfully.');
            
        } catch (Exception $e) {
            \Log::error('Error updating appointment status:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to update appointment status: ' . $e->getMessage());
        }
    }

    public function logs()
    {
        $logs = $this->firestoreService->getCollection('logs', ['orderBy' => 'timestamp', 'direction' => 'desc', 'limit' => 100]);
        return view('admin.logs', compact('logs'));
    }

    private function getServiceName($appointmentData)
    {
        // First try to get from nested service data
        if (isset($appointmentData['service']['service_name'])) {
            \Log::info('Service name found in nested data:', ['name' => $appointmentData['service']['service_name']]);
            return $appointmentData['service']['service_name'];
        }
        
        // If service data is null, try to fetch using service_id
        if (isset($appointmentData['service_id'])) {
            \Log::info('Service data is null, fetching by service_id:', ['service_id' => $appointmentData['service_id']]);
            try {
                $serviceResult = $this->firestoreService->getService($appointmentData['service_id']);
                if ($serviceResult['success'] && isset($serviceResult['data'])) {
                    $serviceName = $serviceResult['data']->service_name ?? 'N/A';
                    \Log::info('Fetched service name:', ['name' => $serviceName]);
                    return $serviceName;
                }
            } catch (Exception $e) {
                \Log::error('Error fetching service:', ['error' => $e->getMessage()]);
            }
        }
        
        \Log::info('No service name found, returning N/A');
        return 'N/A';
    }
    
    private function getServicePrice($appointmentData)
    {
        if (isset($appointmentData['service']['price'])) {
            return $appointmentData['service']['price'];
        }
        
        if (isset($appointmentData['service_id'])) {
            try {
                $serviceResult = $this->firestoreService->getService($appointmentData['service_id']);
                if ($serviceResult['success'] && isset($serviceResult['data'])) {
                    return $serviceResult['data']->price ?? null;
                }
            } catch (Exception $e) {
                \Log::error('Error fetching service price:', ['error' => $e->getMessage()]);
            }
        }
        
        return null;
    }
    
    private function getServiceDuration($appointmentData)
    {
        if (isset($appointmentData['service']['duration_minutes'])) {
            return $appointmentData['service']['duration_minutes'];
        }
        
        if (isset($appointmentData['service_id'])) {
            try {
                $serviceResult = $this->firestoreService->getService($appointmentData['service_id']);
                if ($serviceResult['success'] && isset($serviceResult['data'])) {
                    return $serviceResult['data']->duration_minutes ?? null;
                }
            } catch (Exception $e) {
                \Log::error('Error fetching service duration:', ['error' => $e->getMessage()]);
            }
        }
        
        return null;
    }

    private function getStats()
    {
        $totalUsers = count($this->firestoreService->getCollection('users'));
        $totalHealthCenters = count($this->firestoreService->getCollection('health_centers'));
        $totalServices = count($this->firestoreService->getCollection('services'));
        $totalAppointments = count($this->firestoreService->getCollection('appointments'));

        // Get appointments by status
        $pendingAppointments = count($this->firestoreService->getCollection('appointments', ['where' => [['status', '==', 'pending']]]));
        $confirmedAppointments = count($this->firestoreService->getCollection('appointments', ['where' => [['status', '==', 'confirmed']]]));
        $completedAppointments = count($this->firestoreService->getCollection('appointments', ['where' => [['status', '==', 'completed']]]));

        return [
            'total_users' => $totalUsers,
            'total_health_centers' => $totalHealthCenters,
            'total_services' => $totalServices,
            'total_appointments' => $totalAppointments,
            'pending_appointments' => $pendingAppointments,
            'confirmed_appointments' => $confirmedAppointments,
            'completed_appointments' => $completedAppointments,
        ];
    }
    
    private function getPatientName($appointmentData)
    {
        \Log::info('=== GET PATIENT NAME ===');
        \Log::info('Appointment user_id: ' . ($appointmentData['user_id'] ?? 'NO USER ID'));
        
        // ALWAYS try to fetch fresh user data first (to avoid stale cached data)
        if (isset($appointmentData['user_id'])) {
            \Log::info('Fetching fresh user data by user_id:', ['user_id' => $appointmentData['user_id']]);
            try {
                $userResult = $this->firestoreService->getUser($appointmentData['user_id']);
                \Log::info('User fetch result:', $userResult);
                
                if ($userResult['success'] && isset($userResult['data'])) {
                    $userData = $userResult['data'];
                    \Log::info('Fetched user data type: ' . gettype($userData));
                    \Log::info('Fetched user data:', (array)$userData);
                    
                    // Handle both object and array formats
                    $name = null;
                    if (is_object($userData)) {
                        $name = $userData->name ?? null;
                        $firstName = $userData->first_name ?? null;
                        $lastName = $userData->last_name ?? null;
                    } else {
                        $name = $userData['name'] ?? null;
                        $firstName = $userData['first_name'] ?? null;
                        $lastName = $userData['last_name'] ?? null;
                    }
                    
                    if ($name && $name !== 'Unknown User') {
                        \Log::info('Fetched fresh user name:', ['name' => $name]);
                        return $name;
                    }
                    
                    if ($firstName) {
                        $fullName = trim($firstName . ' ' . ($lastName ?? ''));
                        \Log::info('Constructed user name from fresh data:', ['name' => $fullName]);
                        return $fullName;
                    }
                }
            } catch (Exception $e) {
                \Log::error('Error fetching fresh user data:', ['error' => $e->getMessage()]);
            }
        }
        
        // Fallback to nested user data (but this is likely stale)
        \Log::info('Falling back to nested user data:', $appointmentData['user'] ?? 'NO USER DATA');
        if (isset($appointmentData['user']['name']) && $appointmentData['user']['name'] !== 'Unknown User') {
            \Log::info('Patient name found in nested data:', ['name' => $appointmentData['user']['name']]);
            return $appointmentData['user']['name'];
        }
        
        // Try first_name + last_name combination from nested data
        if (isset($appointmentData['user']['first_name'])) {
            $firstName = $appointmentData['user']['first_name'];
            $lastName = $appointmentData['user']['last_name'] ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
            \Log::info('Patient name constructed from nested first/last name:', ['name' => $fullName]);
            return $fullName;
        }
        
        \Log::info('No valid patient name found, returning Unknown User');
        return 'Unknown User';
    }

    public function notifications()
    {
        \Log::info('=== ADMIN NOTIFICATIONS PAGE LOADED ===');
        
        // Get statistics for the page
        $stats = [
            'total_notifications' => count($this->firestoreService->getCollection('notifications')),
            'active_users' => count($this->firestoreService->getCollection('users'))
        ];
        
        // Get all users for individual selection
        $users = $this->firestoreService->getCollection('users');
        
        return view('admin.notifications', compact('stats', 'users'));
    }

    public function sendNotification(Request $request)
    {
        \Log::info('=== ADMIN SEND NOTIFICATION ===');
        \Log::info('Request data:', $request->all());
        
        $request->validate([
            'recipient' => 'required|in:all,patients,health_workers,individual',
            'user_id' => 'required_if:recipient,individual|string',
            'type' => 'required|in:admin,general,appointment,service',
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'priority' => 'nullable|in:normal,high,urgent'
        ]);

        try {
            // Prepare notification data
            $notificationData = [
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'priority' => $request->priority ?? 'normal',
                'created_at' => now()->toISOString(),
                'is_read' => false,
                'sender_role' => 'admin',
                'sender_id' => session('user')['firebase_uid'] ?? 'admin'
            ];

            // Determine recipients based on selection
            $recipients = [];

            if ($request->recipient === 'individual') {
                // Send to specific user
                $targetUserId = $request->user_id;
                \Log::info('Sending notification to individual user:', ['user_id' => $targetUserId]);
                
                // Verify user exists
                $userCheck = $this->firestoreService->getUser($targetUserId);
                if (!$userCheck['success']) {
                    \Log::error('User not found for notification:', ['user_id' => $targetUserId]);
                    return redirect()->route('admin.notifications')
                        ->with('error', 'User not found. Please select a valid user.');
                }
                
                \Log::info('User verified:', ['user_data' => $userCheck['data']]);
                $recipients[] = $targetUserId;
            } else {
                // Send to multiple users based on role
                $users = $this->firestoreService->getCollection('users');
                \Log::info('Processing users for notification recipients:', ['total_users' => count($users)]);

                foreach ($users as $userId => $userData) {
                    $userRole = $userData['role'] ?? 'patient';
                    
                    \Log::info('Processing user:', [
                        'user_id' => $userId,
                        'role' => $userRole,
                        'firebase_uid' => $userData['firebase_uid'] ?? 'not_set',
                        'email' => $userData['email'] ?? 'no_email'
                    ]);
                    
                    // Use firebase_uid if available, otherwise fall back to document ID
                    $targetUserId = $userData['firebase_uid'] ?? $userId;
                    
                    if ($request->recipient === 'all') {
                        $recipients[] = $targetUserId;
                    } elseif ($request->recipient === 'patients' && $userRole === 'patient') {
                        $recipients[] = $targetUserId;
                    } elseif ($request->recipient === 'health_workers' && $userRole === 'health_worker') {
                        $recipients[] = $targetUserId;
                    }
                }
            }

            \Log::info('Sending notification to recipients:', ['count' => count($recipients), 'type' => $request->recipient]);

            // Send notification to each recipient
            $successCount = 0;
            foreach ($recipients as $userId) {
                // Create a unique notification for each user
                $uniqueNotificationData = [
                    'notification_id' => \Illuminate\Support\Str::uuid()->toString(),
                    'user_id' => $userId,
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                    'type' => $notificationData['type'],
                    'priority' => $notificationData['priority'],
                    'created_at' => now()->toISOString(),
                    'is_read' => false,
                    'sender_role' => $notificationData['sender_role'],
                    'sender_id' => $notificationData['sender_id'],
                    'recipient_role' => $request->recipient === 'all' ? null : ($request->recipient === 'patients' ? 'patient' : ($request->recipient === 'health_workers' ? 'health_worker' : null)),
                    'updated_at' => now()->toISOString(),
                ];
                
                \Log::info('Creating notification for user:', [
                    'notification_id' => $uniqueNotificationData['notification_id'],
                    'user_id' => $userId,
                    'title' => $uniqueNotificationData['title'],
                    'message' => $uniqueNotificationData['message'],
                    'type' => $uniqueNotificationData['type'],
                    'recipient_type' => $request->recipient
                ]);
                
                $result = $this->firestoreService->createDocument('notifications', $uniqueNotificationData);
                
                \Log::info('Notification creation result:', [
                    'user_id' => $userId,
                    'notification_id' => $uniqueNotificationData['notification_id'],
                    'success' => $result ? 'true' : 'false'
                ]);
                
                if ($result) {
                    $successCount++;
                }
            }

            // Log the activity
            $this->activityLogService->log(
                session('user')['firebase_uid'] ?? 'admin',
                'notification_sent',
                'notifications',
                null,
                [
                    'title' => $request->title,
                    'type' => $request->type,
                    'recipient' => $request->recipient,
                    'recipients_count' => $successCount
                ]
            );

            \Log::info('Notification sent successfully:', ['recipients' => $successCount]);
            
            $message = $request->recipient === 'individual' 
                ? "Alert sent successfully to 1 user!" 
                : "Alert sent successfully to {$successCount} users!";
            
            return redirect()->route('admin.notifications')
                ->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Error sending notification:', ['error' => $e->getMessage()]);
            
            return redirect()->route('admin.notifications')
                ->with('error', 'Failed to send alert: ' . $e->getMessage());
        }
    }
}
