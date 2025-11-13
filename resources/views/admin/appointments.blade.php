@extends('layouts.app')

@section('title', 'Appointments - HealthReach')
@section('page-title', 'All Appointments')

@section('content')
<script>
console.log('=== APPOINTMENTS BLADE TEMPLATE ===');
console.log('Appointments Data:', @json($appointments));
console.log('Appointments Count:', Object.keys(@json($appointments)).length);
console.log('Appointments Keys:', Object.keys(@json($appointments)));

// Log each appointment in detail
const appointments = @json($appointments);
Object.keys(appointments).forEach(appointmentId => {
    console.log('=== APPOINTMENT DETAILS ===');
    console.log('Appointment ID:', appointmentId);
    console.log('Appointment Data:', appointments[appointmentId]);
    console.log('Patient Name:', appointments[appointmentId].patient_name);
    console.log('Service Name:', appointments[appointmentId].service_name);
    console.log('Health Center Name:', appointments[appointmentId].health_center_name);
    console.log('Date:', appointments[appointmentId].date);
    console.log('Time:', appointments[appointmentId].time);
    console.log('Status:', appointments[appointmentId].status);
});

console.log('=== END APPOINTMENTS BLADE ===');
</script>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-check me-2"></i>All Appointments</h5>
            </div>
            <div class="card-body">
                @if(count($appointments) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Patient</th>
                                    <th>Health Center</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($appointments as $appointmentId => $appointment)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <strong>{{ $appointment['patient_name'] ?? 'N/A' }}</strong>
                                                    @if(isset($appointment['patient_phone']))
                                                        <br><small class="text-muted">{{ $appointment['patient_phone'] }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if(isset($appointment['health_center_name']))
                                                <strong>{{ $appointment['health_center_name'] }}</strong>
                                            @elseif(isset($appointment['health_center_id']))
                                                <small class="text-muted">ID: {{ $appointment['health_center_id'] }}</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $appointment['service_name'] ?? 'N/A' }}</strong>
                                            @if(isset($appointment['service_category']))
                                                <br><span class="badge bg-info">{{ ucfirst($appointment['service_category']) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($appointment['date']) && isset($appointment['time']))
                                                <strong>{{ date('M d, Y', strtotime($appointment['date'])) }}</strong>
                                                <br><small class="text-muted">{{ date('h:i A', strtotime($appointment['time'])) }}</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $status = $appointment['status'] ?? 'pending';
                                                $statusBadgeClass = $status === 'completed' ? 'success' : ($status === 'confirmed' ? 'info' : ($status === 'cancelled' ? 'danger' : 'warning'));
                                            @endphp
                                            <span class="badge bg-{{ $statusBadgeClass }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(isset($appointment['service_price']))
                                                <strong class="text-success">₱{{ number_format($appointment['service_price'], 2) }}</strong>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#appointmentModal{{ $appointmentId }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Appointment Details Modal -->
                                    <div class="modal fade" id="appointmentModal{{ $appointmentId }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Appointment Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Patient Information</h6>
                                                            <p><strong>Name:</strong> {{ $appointment['patient_name'] ?? 'N/A' }}</p>
                                                            <p><strong>Phone:</strong> {{ $appointment['patient_phone'] ?? 'N/A' }}</p>
                                                            <p><strong>Email:</strong> {{ $appointment['patient_email'] ?? 'N/A' }}</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Appointment Information</h6>
                                                            <p><strong>Service:</strong> {{ $appointment['service_name'] ?? 'N/A' }}</p>
                                                            <p><strong>Health Center:</strong> {{ $appointment['health_center_name'] ?? $appointment['health_center_id'] ?? 'N/A' }}</p>
                                                            <p><strong>Date:</strong> {{ isset($appointment['date']) && isset($appointment['time']) ? date('M d, Y', strtotime($appointment['date'])) . ' at ' . date('h:i A', strtotime($appointment['time'])) : 'N/A' }}</p>
                                                            <p><strong>Status:</strong> 
                                                                @php
                                                                    $modalStatus = $appointment['status'] ?? 'pending';
                                                                    $modalStatusBadgeClass = $modalStatus === 'completed' ? 'success' : ($modalStatus === 'confirmed' ? 'info' : ($modalStatus === 'cancelled' ? 'danger' : 'warning'));
                                                                @endphp
                                                                <span class="badge bg-{{ $modalStatusBadgeClass }}">
                                                                    {{ ucfirst($modalStatus) }}
                                                                </span>
                                                            </p>
                                                            @if(isset($appointment['service_price']))
                                                                <p><strong>Price:</strong> <span class="text-success">₱{{ number_format($appointment['service_price'], 2) }}</span></p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if(isset($appointment['patient_notes']))
                                                        <hr>
                                                        <h6>Patient Notes</h6>
                                                        <p>{{ $appointment['patient_notes'] }}</p>
                                                    @endif
                                                    @if(isset($appointment['health_worker_notes']))
                                                        <hr>
                                                        <h6>Health Worker Notes</h6>
                                                        <p>{{ $appointment['health_worker_notes'] }}</p>
                                                    @endif
                                                    @if(isset($appointment['created_at']))
                                                        <hr>
                                                        <small class="text-muted">
                                                            <strong>Created:</strong> {{ date('M d, Y h:i A', strtotime($appointment['created_at'])) }}
                                                        </small>
                                                    @endif
                                                </div>
                                                <!-- <div class="modal-footer">
                                                    @if($appointment['status'] === 'pending')
                                                        <form method="POST" action="{{ route('admin.appointments.update-status', $appointmentId) }}" style="display: inline;">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="status" value="confirmed">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="fas fa-check"></i> Accept
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('admin.appointments.update-status', $appointmentId) }}" style="display: inline;">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-times"></i> Decline
                                                            </button>
                                                        </form>
                                                    @elseif($appointment['status'] === 'confirmed')
                                                        <form method="POST" action="{{ route('admin.appointments.update-status', $appointmentId) }}" style="display: inline;">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="status" value="completed">
                                                            <button type="submit" class="btn btn-info btn-sm">
                                                                <i class="fas fa-check-circle"></i> Mark Complete
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('admin.appointments.update-status', $appointmentId) }}" style="display: inline;">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div> -->
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No appointments found</h5>
                        <p class="text-muted">Appointments will appear here once patients start booking services.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
