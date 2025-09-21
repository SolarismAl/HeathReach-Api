<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;

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
        
        // Get health worker's appointments
        $appointments = $this->firestoreService->getCollection('appointments', [
            'where' => [['health_center_id', '==', $user['health_center_id'] ?? '']]
        ]);

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
        $user = session('user');
        $filters = ['where' => [['health_center_id', '==', $user['health_center_id'] ?? '']]];

        if ($request->has('status') && $request->status !== '') {
            $filters['where'][] = ['status', '==', $request->status];
        }

        if ($request->has('date') && $request->date !== '') {
            $filters['where'][] = ['appointment_date', '>=', $request->date . 'T00:00:00Z'];
            $filters['where'][] = ['appointment_date', '<=', $request->date . 'T23:59:59Z'];
        }

        $appointments = $this->firestoreService->getCollection('appointments', $filters);
        return view('health-worker.appointments', compact('appointments'));
    }

    public function updateAppointmentStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $appointment = $this->firestoreService->getDocument('appointments', $id);
        if (!$appointment) {
            return redirect()->back()->with('error', 'Appointment not found.');
        }

        $updateData = [
            'status' => $request->status,
            'updated_at' => now()->toISOString(),
        ];

        if ($request->notes) {
            $updateData['health_worker_notes'] = $request->notes;
        }

        $this->firestoreService->updateDocument('appointments', $id, $updateData);

        // Log activity
        $this->activityLogService->log(
            session('firebase_uid'),
            'appointment_status_updated',
            "Appointment status updated to {$request->status} for appointment {$id}"
        );

        return redirect()->back()->with('success', 'Appointment status updated successfully.');
    }

    public function services()
    {
        $user = session('user');
        $services = $this->firestoreService->getCollection('services', [
            'where' => [['health_center_id', '==', $user['health_center_id'] ?? '']]
        ]);
        return view('health-worker.services', compact('services'));
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
        $healthCenters = $this->firestoreService->getCollection('health_centers');
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

    private function getHealthWorkerStats($healthCenterId)
    {
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

        return [
            'total_appointments' => $totalAppointments,
            'pending_appointments' => $pendingAppointments,
            'confirmed_appointments' => $confirmedAppointments,
            'completed_appointments' => $completedAppointments,
            'total_services' => $totalServices,
        ];
    }
}
