<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use Exception;

class WebHealthWorkerController extends Controller
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
        $user = session('user');
        \Log::info('=== HEALTH WORKER DASHBOARD ===');
        \Log::info('Health worker user data:', [$user]);
        \Log::info('Health worker health_center_id: ' . ($user['health_center_id'] ?? 'NOT SET'));
        
        // Get all appointments and filter by health center
        $allAppointments = $this->firestoreService->getCollection('appointments');
        \Log::info('Total appointments found: ' . count($allAppointments));
        
        $appointments = [];
        
        foreach ($allAppointments as $appointmentId => $appointmentData) {
            \Log::info('Processing appointment ' . $appointmentId . ' with health_center_id: ' . ($appointmentData['health_center_id'] ?? 'NOT SET'));
            
            // For now, show all appointments to health workers (we can filter later)
            // TODO: Properly associate health workers with health centers
            $appointments[$appointmentId] = $appointmentData;
            
            // Ensure service data is populated
            if (!isset($appointmentData['service']) || $appointmentData['service'] === null) {
                \Log::info('Health Worker Dashboard: Service data is null for appointment ' . $appointmentId . ', attempting to fetch by service_id');
                
                if (isset($appointmentData['service_id'])) {
                    try {
                        $serviceResult = $this->firestoreService->getService($appointmentData['service_id']);
                        if ($serviceResult['success'] && isset($serviceResult['data'])) {
                            $appointments[$appointmentId]['service'] = [
                                'service_name' => $serviceResult['data']->service_name ?? 'N/A',
                                'price' => $serviceResult['data']->price ?? 0,
                                'duration_minutes' => $serviceResult['data']->duration_minutes ?? 0,
                            ];
                            \Log::info('Health Worker Dashboard: Successfully populated service data for appointment ' . $appointmentId);
                        }
                    } catch (Exception $e) {
                        \Log::error('Health Worker Dashboard: Error fetching service for appointment ' . $appointmentId . ': ' . $e->getMessage());
                    }
                }
            }
        }
        
        \Log::info('Final appointments count for health worker dashboard: ' . count($appointments));

        // Get health worker's services
        $services = $this->firestoreService->getCollection('services', [
            'where' => [['health_center_id', '==', $user['health_center_id'] ?? '']]
        ]);

        // Get statistics
        $stats = $this->getHealthWorkerStats($user['health_center_id'] ?? '');

        return view('health-worker.dashboard', compact('appointments', 'services', 'stats'));
    }

    public function appointments(Request $request)
    {
        \Log::info('=== HEALTH WORKER APPOINTMENTS PAGE LOADED ===');
        
        $user = session('user');
        \Log::info('Health worker user:', $user);
        
        // Get all appointments first, then filter
        $allAppointments = $this->firestoreService->getCollection('appointments');
        \Log::info('=== ALL APPOINTMENTS FROM HEALTH WORKER ===');
        \Log::info('All appointments count: ' . count($allAppointments));
        \Log::info('All appointments data:', $allAppointments);
        
        $appointments = [];
        $healthCenterId = $user['health_center_id'] ?? '';
        \Log::info('Filtering by health center ID: ' . $healthCenterId);
        
        // If no health center ID, show all appointments for debugging
        if (empty($healthCenterId)) {
            \Log::warning('Health worker has no health_center_id assigned! Showing all appointments for debugging.');
            \Log::info('Available health center IDs in appointments:');
            foreach ($allAppointments as $id => $apt) {
                \Log::info('Appointment ' . $id . ' has health_center_id: ' . ($apt['health_center_id'] ?? 'NOT SET'));
            }
        }
        
        foreach ($allAppointments as $appointmentId => $appointmentData) {
            \Log::info('=== PROCESSING HEALTH WORKER APPOINTMENT ===');
            \Log::info('Appointment ID: ' . $appointmentId);
            \Log::info('Appointment health_center_id: ' . ($appointmentData['health_center_id'] ?? 'NOT SET'));
            \Log::info('User health_center_id: ' . $healthCenterId);
            
            // Filter by health center ID (or show all if no health center assigned)
            if (empty($healthCenterId) || ($appointmentData['health_center_id'] ?? '') === $healthCenterId) {
                \Log::info('Appointment matches health center, including...');
                
                // Apply additional filters
                $includeAppointment = true;
                
                if ($request->has('status') && $request->status !== '') {
                    if (($appointmentData['status'] ?? '') !== $request->status) {
                        $includeAppointment = false;
                    }
                }
                
                if ($request->has('date') && $request->date !== '') {
                    $appointmentDate = $appointmentData['date'] ?? '';
                    if (strpos($appointmentDate, $request->date) !== 0) {
                        $includeAppointment = false;
                    }
                }
                
                if ($includeAppointment) {
                    // Transform the data similar to admin controller
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
                        
                        // Extract nested data
                        'patient_name' => isset($appointmentData['user']['name']) ? $appointmentData['user']['name'] : 'N/A',
                        'patient_phone' => isset($appointmentData['user']['contact_number']) ? $appointmentData['user']['contact_number'] : null,
                        'patient_email' => isset($appointmentData['user']['email']) ? $appointmentData['user']['email'] : null,
                        
                        'health_center_name' => isset($appointmentData['health_center']['name']) ? $appointmentData['health_center']['name'] : 'N/A',
                        'health_center_address' => isset($appointmentData['health_center']['address']) ? $appointmentData['health_center']['address'] : null,
                        
                        'service_name' => $this->getServiceName($appointmentData),
                        'service_price' => $this->getServicePrice($appointmentData),
                        'service_duration' => $this->getServiceDuration($appointmentData),
                    ];
                    
                    \Log::info('Transformed health worker appointment:', $appointments[$appointmentId]);
                }
            } else {
                \Log::info('Appointment does not match health center, skipping...');
            }
        }
        
        \Log::info('Final filtered appointments count: ' . count($appointments));
        return view('health-worker.appointments', compact('appointments'));
    }

    public function updateAppointmentStatus(Request $request, $id)
    {
        \Log::info('=== HEALTH WORKER UPDATE APPOINTMENT STATUS CALLED ===');
        \Log::info('Current timestamp: ' . now());
        \Log::info('Appointment ID: ' . $id);
        \Log::info('Request data:', $request->all());
        \Log::info('Request method: ' . $request->method());
        \Log::info('Request URL: ' . $request->url());
        \Log::info('Session user:', [session('user')]);
        \Log::info('Session firebase_uid: ' . session('firebase_uid'));
        
        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $appointment = $this->firestoreService->getDocument('appointments', $id);
        \Log::info('Current appointment data:', $appointment);
        
        if (!$appointment) {
            \Log::error('Appointment not found: ' . $id);
            return redirect()->back()->with('error', 'Appointment not found.');
        }

        $updateData = [
            'status' => $request->status,
            'updated_at' => now()->toISOString(),
        ];

        if ($request->notes) {
            $updateData['health_worker_notes'] = $request->notes;
        }

        \Log::info('Update data to be applied:', $updateData);
        
        try {
            $result = $this->firestoreService->updateDocument('appointments', $id, $updateData);
            \Log::info('Update result:', ['success' => $result]);
            
            if (!$result) {
                \Log::error('Failed to update appointment in Firestore');
                return redirect()->back()->with('error', 'Failed to update appointment status.');
            }
            
            // Verify the update
            $updatedAppointment = $this->firestoreService->getDocument('appointments', $id);
            \Log::info('Updated appointment data:', $updatedAppointment);
            
        } catch (Exception $e) {
            \Log::error('Exception during appointment update:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error updating appointment: ' . $e->getMessage());
        }

        // Log activity
        $this->activityLogService->log(
            session('firebase_uid'),
            'appointment_status_updated',
            "Appointment status updated to {$request->status} for appointment {$id}"
        );

        \Log::info('Appointment status update completed successfully');
        return redirect()->back()->with('success', 'Appointment status updated successfully.');
    }

    public function services()
    {
        // Get all services (not filtered by user's health center for health worker view)
        $services = $this->firestoreService->getCollection('services');
        
        // Get all health centers for reference
        $healthCenters = $this->firestoreService->getCollection('health_centers');
        
        \Log::info('=== WEB SERVICES CONTROLLER ===');
        \Log::info('Services loaded: ' . count($services));
        \Log::info('Health Centers loaded: ' . count($healthCenters));
        
        foreach ($services as $serviceId => $service) {
            $centerId = $service['health_center_id'] ?? 'N/A';
            $centerName = 'Unknown';
            if (isset($healthCenters[$centerId])) {
                $centerName = $healthCenters[$centerId]['name'] ?? 'Unknown';
            }
            \Log::info("Service '{$service['name']}' (ID: {$serviceId}) -> Health Center: {$centerName} (ID: {$centerId})");
        }
        \Log::info('=== END WEB SERVICES CONTROLLER ===');
        
        return view('health-worker.services', compact('services', 'healthCenters'));
    }

    public function createService()
    {
        // Get all health centers for the dropdown
        $healthCenters = $this->firestoreService->getCollection('health_centers');
        return view('health-worker.create-service', compact('healthCenters'));
    }

    public function storeService(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'health_center_id' => 'required|string',
        ]);

        $serviceData = [
            'name' => $request->name,
            'description' => $request->description,
            'duration' => (int) $request->duration,
            'price' => (float) $request->price,
            'category' => $request->category,
            'health_center_id' => $request->health_center_id,
            'is_active' => $request->has('is_active'),
            'created_by' => session('firebase_uid'),
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        $this->firestoreService->createDocument('services', $serviceData);

        // Log activity
        $this->activityLogService->log(
            session('firebase_uid'),
            'service_created',
            'Service created: ' . $request->name
        );

        return redirect()->route('health-worker.services')->with('success', 'Service created successfully.');
    }

    public function healthCenters()
    {
        // Get all health centers
        $healthCenters = $this->firestoreService->getCollection('health_centers');
        
        // Get all services
        $allServices = $this->firestoreService->getCollection('services');
        
        // Group services by health center ID
        $servicesByCenter = [];
        \Log::info('=== GROUPING SERVICES BY HEALTH CENTER ===');
        foreach ($allServices as $serviceId => $service) {
            $centerId = $service['health_center_id'] ?? null;
            \Log::info('Service: ' . ($service['name'] ?? 'NO NAME') . ' belongs to health center: ' . $centerId);
            if ($centerId) {
                if (!isset($servicesByCenter[$centerId])) {
                    $servicesByCenter[$centerId] = [];
                }
                $servicesByCenter[$centerId][] = $service;
            }
        }
        \Log::info('Services grouped by center:', $servicesByCenter);
        
        // Add services to each health center
        foreach ($healthCenters as $centerId => $center) {
            $healthCenters[$centerId]['services'] = $servicesByCenter[$centerId] ?? [];
            \Log::info('Adding services to health center: ' . $centerId . ' (' . ($center['name'] ?? 'NO NAME') . ') - Services count: ' . count($healthCenters[$centerId]['services']));
        }
        
        // Clear any lingering references to prevent corruption
        unset($center);
        
        \Log::info('=== WEB HEALTH CENTERS CONTROLLER ===');
        \Log::info('Health Centers loaded: ' . count($healthCenters));
        \Log::info('All Services loaded: ' . count($allServices));
        
        // Debug: Log raw health centers data
        \Log::info('Raw Health Centers Data:');
        foreach ($healthCenters as $centerId => $center) {
            \Log::info('Health Center Firestore Doc ID: ' . $centerId . ', Internal health_center_id: ' . ($center['health_center_id'] ?? 'NO INTERNAL ID') . ', Name: ' . ($center['name'] ?? 'NO NAME'));
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
        
        // Debug: Log raw services data
        \Log::info('Raw Services Data:');
        foreach ($allServices as $serviceId => $service) {
            \Log::info("  Service ID: {$serviceId}");
            \Log::info("  Service Name: " . ($service['name'] ?? 'N/A'));
            \Log::info("  Health Center ID: " . ($service['health_center_id'] ?? 'N/A'));
            \Log::info("  Service Price: " . ($service['price'] ?? 'N/A'));
        }
        
        // Debug: Log services grouped by center
        \Log::info('Services Grouped by Center:');
        foreach ($servicesByCenter as $centerId => $services) {
            \Log::info("  Center ID: {$centerId} has " . count($services) . " services");
            foreach ($services as $service) {
                \Log::info("    - {$service['name']} (Price: $" . ($service['price'] ?? 'N/A') . ")");
            }
        }
        
        // Final result
        foreach ($healthCenters as $centerId => $center) {
            $servicesCount = count($center['services']);
            \Log::info("FINAL: Health Center '{$center['name']}' (ID: {$centerId}) has {$servicesCount} services");
            foreach ($center['services'] as $service) {
                \Log::info("  - Service: {$service['name']} (Price: $" . ($service['price'] ?? 'N/A') . ")");
            }
        }
        \Log::info('=== END WEB HEALTH CENTERS CONTROLLER ===');
        
        return view('health-worker.health-centers', compact('healthCenters'));
    }

    public function editService($id)
    {
        $service = $this->firestoreService->getDocument('services', $id);
        if (!$service) {
            return redirect()->route('health-worker.services')->with('error', 'Service not found.');
        }
        return view('health-worker.edit-service', compact('service', 'id'));
    }

    public function updateService(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'duration' => (int) $request->duration,
            'price' => (float) $request->price,
            'category' => $request->category,
            'is_active' => $request->has('is_active'),
            'updated_at' => now()->toISOString(),
        ];

        $this->firestoreService->updateDocument('services', $id, $updateData);

        // Log activity
        $this->activityLogService->log(
            session('firebase_uid'),
            'service_updated',
            'Service updated: ' . $request->name
        );

        return redirect()->route('health-worker.services')->with('success', 'Service updated successfully.');
    }

    public function deleteService($id)
    {
        $service = $this->firestoreService->getDocument('services', $id);
        if (!$service) {
            return redirect()->route('health-worker.services')->with('error', 'Service not found.');
        }

        $this->firestoreService->deleteDocument('services', $id);

        // Log activity
        $this->activityLogService->log(
            session('firebase_uid'),
            'service_deleted',
            'Service deleted: ' . $service['name']
        );

        return redirect()->route('health-worker.services')->with('success', 'Service deleted successfully.');
    }

    private function getServiceName($appointmentData)
    {
        // First try to get from nested service data
        if (isset($appointmentData['service']['service_name'])) {
            \Log::info('Service name found in nested data: ' . $appointmentData['service']['service_name']);
            return $appointmentData['service']['service_name'];
        }
        
        // If service data is null, try to fetch using service_id
        if (isset($appointmentData['service_id'])) {
            \Log::info('Service data is null, fetching by service_id: ' . $appointmentData['service_id']);
            try {
                $serviceResult = $this->firestoreService->getService($appointmentData['service_id']);
                if ($serviceResult['success'] && isset($serviceResult['data'])) {
                    $serviceName = $serviceResult['data']->service_name ?? 'N/A';
                    \Log::info('Fetched service name: ' . $serviceName);
                    return $serviceName;
                }
            } catch (Exception $e) {
                \Log::error('Error fetching service: ' . $e->getMessage());
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
                \Log::error('Error fetching service price: ' . $e->getMessage());
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
                \Log::error('Error fetching service duration: ' . $e->getMessage());
            }
        }
        
        return null;
    }

    private function getHealthWorkerStats($healthCenterId)
    {
        \Log::info('=== CALCULATING HEALTH WORKER STATS ===');
        \Log::info('Health center ID for stats: ' . ($healthCenterId ?: 'EMPTY'));
        
        // If no health center ID, calculate stats from all appointments (same logic as dashboard)
        if (empty($healthCenterId)) {
            \Log::info('No health center ID, calculating stats from all appointments');
            $allAppointments = $this->firestoreService->getCollection('appointments');
            
            $totalAppointments = count($allAppointments);
            $pendingAppointments = 0;
            $confirmedAppointments = 0;
            $completedAppointments = 0;
            
            foreach ($allAppointments as $appointment) {
                $status = $appointment['status'] ?? 'pending';
                switch ($status) {
                    case 'pending':
                        $pendingAppointments++;
                        break;
                    case 'confirmed':
                        $confirmedAppointments++;
                        break;
                    case 'completed':
                        $completedAppointments++;
                        break;
                }
            }
            
            $totalServices = count($this->firestoreService->getCollection('services'));
            
            \Log::info('Stats calculated from all appointments:', [
                'total' => $totalAppointments,
                'pending' => $pendingAppointments,
                'confirmed' => $confirmedAppointments,
                'completed' => $completedAppointments,
                'services' => $totalServices
            ]);
            
        } else {
            // Filter by health center ID
            \Log::info('Filtering by health center ID: ' . $healthCenterId);
            
            $totalAppointments = count($this->firestoreService->getCollection('appointments', [
                'where' => [['health_center_id', '==', $healthCenterId]]
            ]));

            $pendingAppointments = count($this->firestoreService->getCollection('appointments', [
                'where' => [
                    ['health_center_id', '==', $healthCenterId],
                    ['status', '==', 'pending']
                ]
            ]));

            $confirmedAppointments = count($this->firestoreService->getCollection('appointments', [
                'where' => [
                    ['health_center_id', '==', $healthCenterId],
                    ['status', '==', 'confirmed']
                ]
            ]));

            $completedAppointments = count($this->firestoreService->getCollection('appointments', [
                'where' => [
                    ['health_center_id', '==', $healthCenterId],
                    ['status', '==', 'completed']
                ]
            ]));

            $totalServices = count($this->firestoreService->getCollection('services', [
                'where' => [['health_center_id', '==', $healthCenterId]]
            ]));
        }

        return [
            'total_appointments' => $totalAppointments,
            'pending_appointments' => $pendingAppointments,
            'confirmed_appointments' => $confirmedAppointments,
            'completed_appointments' => $completedAppointments,
            'total_services' => $totalServices,
        ];
    }

    public function notifications()
    {
        \Log::info('=== HEALTH WORKER NOTIFICATIONS PAGE LOADED ===');
        
        // Get health centers for the form
        $healthCenters = $this->firestoreService->getCollection('health_centers');
        
        // Get statistics for the page
        $currentUserId = session('user')['firebase_uid'] ?? null;
        $allNotifications = $this->firestoreService->getCollection('notifications');
        $allUsers = $this->firestoreService->getCollection('users');
        
        // Count notifications sent by this health worker
        $myNotifications = 0;
        foreach ($allNotifications as $notification) {
            if (($notification['sender_id'] ?? null) === $currentUserId) {
                $myNotifications++;
            }
        }
        
        // Count patients
        $myPatients = 0;
        foreach ($allUsers as $user) {
            if (($user['role'] ?? 'patient') === 'patient') {
                $myPatients++;
            }
        }
        
        $stats = [
            'my_notifications' => $myNotifications,
            'my_patients' => $myPatients
        ];
        
        return view('health-worker.notifications', compact('healthCenters', 'stats'));
    }

    public function sendNotification(Request $request)
    {
        \Log::info('=== HEALTH WORKER SEND NOTIFICATION ===');
        \Log::info('Request data:', $request->all());
        
        $request->validate([
            'recipient' => 'required|in:patients,my_patients,specific_patient',
            'type' => 'required|in:appointment,service,general',
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'health_center_id' => 'nullable|string',
            'patient_id' => 'required_if:recipient,specific_patient|string'
        ]);

        try {
            // Prepare notification data
            $notificationData = [
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'priority' => 'normal',
                'created_at' => now()->toISOString(),
                'is_read' => false,
                'sender_role' => 'health_worker',
                'sender_id' => session('user')['firebase_uid'] ?? 'health_worker'
            ];

            // Add health center data if provided
            if ($request->health_center_id) {
                $healthCenter = $this->firestoreService->getDocument('health_centers', $request->health_center_id);
                if ($healthCenter) {
                    $notificationData['data'] = [
                        'health_center_id' => $request->health_center_id,
                        'health_center_name' => $healthCenter['name'] ?? 'Unknown'
                    ];
                }
            }

            // Get recipients based on selection
            $recipients = [];

            if ($request->recipient === 'specific_patient') {
                // Send to specific patient
                $recipients[] = $request->patient_id;
                \Log::info('Sending to specific patient:', [
                    'patient_id' => $request->patient_id,
                    'recipients_count' => 1
                ]);
            } else {
                // Get all users and filter patients
                $users = $this->firestoreService->getCollection('users');

                foreach ($users as $userId => $userData) {
                    $userRole = $userData['role'] ?? 'patient';
                    
                    if ($userRole === 'patient') {
                        // Use firebase_uid if available, otherwise fall back to document ID
                        $targetUserId = $userData['firebase_uid'] ?? $userId;
                        
                        if ($request->recipient === 'patients') {
                            // Send to all patients
                            $recipients[] = $targetUserId;
                        } elseif ($request->recipient === 'my_patients') {
                            // For now, send to all patients since we don't have a direct patient-health worker relationship
                            // In a real system, you'd filter based on appointments or assignments
                            $recipients[] = $targetUserId;
                        }
                    }
                }
            }

            \Log::info('Sending notification to recipients:', ['count' => count($recipients), 'type' => $request->recipient]);

            // Send notification to each recipient
            $successCount = 0;
            foreach ($recipients as $userId) {
                $notificationData['user_id'] = $userId;
                $notificationData['recipient_role'] = 'patient';
                
                $result = $this->firestoreService->createDocument('notifications', $notificationData);
                if ($result) {
                    $successCount++;
                }
            }

            // Log the activity
            $this->activityLogService->log(
                session('user')['firebase_uid'] ?? 'health_worker',
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
            
            return redirect()->route('health-worker.notifications')
                ->with('success', "Alert sent successfully to {$successCount} patients!");

        } catch (\Exception $e) {
            \Log::error('Error sending notification:', ['error' => $e->getMessage()]);
            
            return redirect()->route('health-worker.notifications')
                ->with('error', 'Failed to send alert: ' . $e->getMessage());
        }
    }

    public function getPatients()
    {
        try {
            \Log::info('=== GET PATIENTS API CALL ===');
            
            // Get all users and filter patients
            $users = $this->firestoreService->getCollection('users');
            $patients = [];

            foreach ($users as $userId => $userData) {
                $userRole = $userData['role'] ?? 'patient';
                
                if ($userRole === 'patient') {
                    // Use firebase_uid as the ID for consistency
                    $patientId = $userData['firebase_uid'] ?? $userId;
                    
                    $patients[] = [
                        'id' => $patientId,
                        'first_name' => $userData['first_name'] ?? 'Unknown',
                        'last_name' => $userData['last_name'] ?? 'User',
                        'email' => $userData['email'] ?? 'No email',
                        'phone' => $userData['contact_number'] ?? $userData['phone'] ?? 'No phone'
                    ];
                }
            }

            // Sort patients by name
            usort($patients, function($a, $b) {
                return strcmp($a['first_name'] . ' ' . $a['last_name'], $b['first_name'] . ' ' . $b['last_name']);
            });

            \Log::info('Found patients:', ['count' => count($patients)]);

            return response()->json([
                'success' => true,
                'patients' => $patients,
                'count' => count($patients)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching patients:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch patients: ' . $e->getMessage(),
                'patients' => []
            ], 500);
        }
    }
}
