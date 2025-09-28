<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FirestoreService;
use App\Services\ActivityLogService;
use App\DataTransferObjects\AppointmentData;
use App\DataTransferObjects\CreateAppointmentData;
use App\DataTransferObjects\ApiResponse;
use App\DataTransferObjects\ApiError;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class AppointmentController extends Controller
{
    private FirestoreService $firestoreService;
    private ActivityLogService $activityLogService;

    public function __construct(
        FirestoreService $firestoreService,
        ActivityLogService $activityLogService
    ) {
        $this->firestoreService = $firestoreService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get appointments based on user role
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get user from middleware
            $user = $request->get('user');
            $userId = $user['user_id'] ?? $user['firebase_uid'] ?? null;
            $userRole = $user['role'] ?? 'patient';
            
            // Get status filter from request parameters
            $status = $request->input('status');
            \Log::info('AppointmentController::index - User:', ['user_id' => $userId, 'role' => $userRole, 'status_filter' => $status]);

            $result = match ($userRole) {
                'patient' => $this->firestoreService->getAppointmentsByUser($userId, $status),
                'health_worker', 'admin' => $this->firestoreService->getAllAppointments(),
                default => ['success' => false, 'error' => 'Invalid user role']
            };

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to retrieve appointments', null, 500)->toArray(),
                    500
                );
            }

            $appointments = array_map(fn($appointment) => $appointment->toArray(), $result['data']);

            // Log the activity
            $this->activityLogService->log(
                $userId,
                'appointments_viewed',
                'Viewed appointments list',
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Appointments retrieved successfully', $appointments)->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to retrieve appointments', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Create a new appointment
     */
    public function store(Request $request): JsonResponse
    {
        \Log::info('=== CREATE APPOINTMENT ===');
        \Log::info('Request data: ' . json_encode($request->all()));
        
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'health_center_id' => 'required|string',
            'service_id' => 'required|string',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(
                (new ApiError('Validation failed', '422', $validator->errors()->toArray()))->toArray(),
                422
            );
        }

        try {
            // Get user from middleware
            $user = $request->get('user');
            $currentUserId = $user['user_id'] ?? $user['firebase_uid'] ?? null;
            $userRole = $user['role'] ?? 'patient';
            $requestedUserId = $request->input('user_id');
            
            \Log::info('User from middleware: ' . json_encode($user));
            \Log::info('Current User ID: ' . $currentUserId);
            \Log::info('User Role: ' . $userRole);
            \Log::info('Requested User ID: ' . $requestedUserId);

            // Check if user can create appointment for this user
            if ($userRole === 'patient' && $currentUserId !== $requestedUserId) {
                return response()->json(
                    ApiResponse::error('Patients can only create appointments for themselves', null, 403)->toArray(),
                    403
                );
            }

            $appointmentData = new AppointmentData(
                appointment_id: Str::uuid()->toString(),
                user_id: $requestedUserId,
                health_center_id: $request->input('health_center_id'),
                service_id: $request->input('service_id'),
                date: $request->input('appointment_date'),
                time: $request->input('appointment_time'),
                status: 'pending',
                remarks: $request->input('notes'),
                created_at: now()->toISOString(),
                updated_at: now()->toISOString()
            );

            $result = $this->firestoreService->createAppointment($appointmentData);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to create appointment', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $this->activityLogService->log(
                $currentUserId,
                'appointment_created',
                "Created appointment for {$appointmentData->date} at {$appointmentData->time}",
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Appointment booked successfully', $appointmentData->toArray())->toArray(),
                201
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to create appointment', null, 500)->toArray(),
                500
            );
        }
    }

    /**
     * Update appointment status or reschedule
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,confirmed,cancelled,completed',
            'date' => 'sometimes|date|after_or_equal:today',
            'time' => 'sometimes|string',
            'remarks' => 'sometimes|nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(
                (new ApiError('Validation failed', $validator->errors()->toArray(), 422))->toArray(),
                422
            );
        }

        try {
            $currentUserId = $request->input('firebase_uid');
            $userRole = $request->input('user_role');

            // Only health workers and admins can update appointment status
            if ($userRole === 'patient' && $request->has('status')) {
                return response()->json(
                    ApiResponse::error('Patients cannot update appointment status', null, 403)->toArray(),
                    403
                );
            }

            $updateData = $request->only(['status', 'date', 'time', 'remarks']);
            $updateData['updated_at'] = now()->toISOString();

            $result = $this->firestoreService->updateAppointment($id, $updateData);

            if (!$result['success']) {
                return response()->json(
                    ApiResponse::error('Failed to update appointment', null, 500)->toArray(),
                    500
                );
            }

            // Log the activity
            $action = $request->has('status') ? 'appointment_status_updated' : 'appointment_rescheduled';
            $description = $request->has('status') 
                ? "Updated appointment status to: {$request->input('status')}"
                : "Rescheduled appointment to: {$request->input('date')} at {$request->input('time')}";

            $this->activityLogService->log(
                $currentUserId,
                $action,
                $description,
                $request->ip(),
                $request->userAgent()
            );

            return response()->json(
                ApiResponse::success('Appointment updated successfully')->toArray()
            );

        } catch (Exception $e) {
            return response()->json(
                ApiResponse::error('Failed to update appointment', null, 500)->toArray(),
                500
            );
        }
    }
}
