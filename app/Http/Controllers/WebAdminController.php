<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;

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
        // Get statistics
        $stats = $this->getStats();
        $recentUsers = $this->firestoreService->getCollection('users', ['limit' => 5]);
        $recentAppointments = $this->firestoreService->getCollection('appointments', ['limit' => 5]);

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

    public function healthCenters()
    {
        $healthCenters = $this->firestoreService->getCollection('health_centers');
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
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $healthCenterData = [
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'latitude' => (float) $request->latitude,
            'longitude' => (float) $request->longitude,
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
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'is_active' => 'boolean',
        ]);

        $updateData = [
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'latitude' => (float) $request->latitude,
            'longitude' => (float) $request->longitude,
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
        $appointments = $this->firestoreService->getCollection('appointments');
        return view('admin.appointments', compact('appointments'));
    }

    public function logs()
    {
        $logs = $this->firestoreService->getCollection('logs', ['orderBy' => 'timestamp', 'direction' => 'desc', 'limit' => 100]);
        return view('admin.logs', compact('logs'));
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
}
