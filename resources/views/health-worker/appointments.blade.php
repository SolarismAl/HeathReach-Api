@extends('layouts.app')

@section('title', 'Appointments - HealthReach')
@section('page-title', 'Appointments Management')

@section('content')
<script>
console.log('=== HEALTH WORKER APPOINTMENTS BLADE TEMPLATE ===');
console.log('Appointments Data:', @json($appointments));
console.log('Appointments Count:', Object.keys(@json($appointments)).length);
console.log('Appointments Keys:', Object.keys(@json($appointments)));

// Log each appointment in detail
const appointments = @json($appointments);
Object.keys(appointments).forEach(appointmentId => {
    console.log('=== HEALTH WORKER APPOINTMENT DETAILS ===');
    console.log('Appointment ID:', appointmentId);
    console.log('Appointment Data:', appointments[appointmentId]);
    console.log('Patient Name:', appointments[appointmentId].patient_name);
    console.log('Service Name:', appointments[appointmentId].service_name);
    console.log('Health Center Name:', appointments[appointmentId].health_center_name);
    console.log('Date:', appointments[appointmentId].date);
    console.log('Time:', appointments[appointmentId].time);
    console.log('Status:', appointments[appointmentId].status);
    console.log('Remarks:', appointments[appointmentId].remarks);
});

console.log('=== END HEALTH WORKER APPOINTMENTS BLADE ===');
</script>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-calendar-check me-2"></i>Appointments</h5>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if(count($appointments) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Patient</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Notes</th>
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
                                                    @if(isset($appointment['patient_email']))
                                                        <br><small class="text-muted">{{ $appointment['patient_email'] }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ $appointment['service_name'] ?? 'N/A' }}</strong>
                                            @if(isset($appointment['service_price']))
                                                <br><small class="text-muted">₱{{ number_format($appointment['service_price'], 2) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($appointment['date']) && isset($appointment['time']))
                                                <strong>{{ date('M d, Y', strtotime($appointment['date'])) }}</strong>
                                                <br><small class="text-muted">{{ date('h:i A', strtotime($appointment['time'])) }}</small>
                                            @elseif(isset($appointment['appointment_date']))
                                                <strong>{{ date('M d, Y', strtotime($appointment['appointment_date'])) }}</strong>
                                                <br><small class="text-muted">{{ date('h:i A', strtotime($appointment['appointment_date'])) }}</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $hwStatus = $appointment['status'] ?? 'pending';
                                                $hwStatusBadgeClass = $hwStatus === 'completed' ? 'success' : ($hwStatus === 'confirmed' ? 'info' : ($hwStatus === 'cancelled' ? 'danger' : 'warning'));
                                            @endphp
                                            <span class="badge bg-{{ $hwStatusBadgeClass }}">
                                                {{ ucfirst($hwStatus) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(isset($appointment['remarks']) && !empty($appointment['remarks']))
                                                <small>{{ Str::limit($appointment['remarks'], 50) }}</small>
                                            @elseif(isset($appointment['health_worker_notes']))
                                                <small>{{ Str::limit($appointment['health_worker_notes'], 50) }}</small>
                                            @elseif(isset($appointment['patient_notes']))
                                                <small class="text-muted">Patient: {{ Str::limit($appointment['patient_notes'], 50) }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#appointmentModal{{ $appointmentId }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if(($appointment['status'] ?? 'pending') === 'pending')
                                                    <button class="btn btn-outline-success" onclick="updateStatus('{{ $appointmentId }}', 'confirmed')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="updateStatus('{{ $appointmentId }}', 'cancelled')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @elseif(($appointment['status'] ?? 'pending') === 'confirmed')
                                                    <button class="btn btn-outline-primary" onclick="updateStatus('{{ $appointmentId }}', 'completed')">
                                                        <i class="fas fa-check-double"></i>
                                                    </button>
                                                @endif
                                            </div>
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
                                                            <p><strong>Date:</strong> {{ isset($appointment['appointment_date']) ? date('M d, Y h:i A', strtotime($appointment['appointment_date'])) : 'N/A' }}</p>
                                                            <p><strong>Status:</strong> 
                                                                @php
                                                                    $hwModalStatus = $appointment['status'] ?? 'pending';
                                                                    $hwModalStatusBadgeClass = $hwModalStatus === 'completed' ? 'success' : ($hwModalStatus === 'confirmed' ? 'info' : ($hwModalStatus === 'cancelled' ? 'danger' : 'warning'));
                                                                @endphp
                                                                <span class="badge bg-{{ $hwModalStatusBadgeClass }}">
                                                                    {{ ucfirst($hwModalStatus) }}
                                                                </span>
                                                            </p>
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
                                                </div>
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
                        <p class="text-muted">
                            @if(request('status') || request('date'))
                                Try adjusting your filters or 
                                <a href="{{ route('health-worker.appointments') }}">view all appointments</a>.
                            @else
                                Appointments will appear here once patients book your services.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Appointment Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusForm" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="status" id="statusInput">
                    <p>Are you sure you want to <span id="statusAction"></span> this appointment?</p>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any notes about this appointment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="statusSubmit">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function updateStatus(appointmentId, status) {
    console.log('=== UPDATE STATUS FUNCTION CALLED ===');
    console.log('Appointment ID:', appointmentId);
    console.log('Status:', status);
    
    document.getElementById('statusInput').value = status;
    document.getElementById('statusForm').action = `/health-worker/appointments/${appointmentId}/status`;
    
    console.log('Form action set to:', document.getElementById('statusForm').action);
    console.log('Status input value set to:', document.getElementById('statusInput').value);
    
    const actionText = status === 'confirmed' ? 'confirm' : (status === 'completed' ? 'complete' : 'cancel');
    document.getElementById('statusAction').textContent = actionText;
    document.getElementById('statusSubmit').textContent = `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Appointment`;
    document.getElementById('statusSubmit').className = `btn btn-${status === 'confirmed' ? 'success' : (status === 'completed' ? 'primary' : 'danger')}`;
    
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

// Add form submission debugging
document.addEventListener('DOMContentLoaded', function() {
    const statusForm = document.getElementById('statusForm');
    if (statusForm) {
        statusForm.addEventListener('submit', function(e) {
            console.log('=== FORM SUBMISSION ===');
            console.log('Form action:', this.action);
            console.log('Form method:', this.method);
            console.log('Status value:', document.getElementById('statusInput').value);
            console.log('Notes value:', document.getElementById('notes').value);
            console.log('Form data:', new FormData(this));
            
            // Let the form submit normally
            console.log('Form submitting...');
        });
    }
});
</script>
@endsection
