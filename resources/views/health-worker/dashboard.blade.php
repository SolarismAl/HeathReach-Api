@extends('layouts.app')

@section('title', 'Health Worker Dashboard - HealthReach')
@section('page-title', 'Health Worker Dashboard')

@section('content')
<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                <h3>{{ $stats['total_appointments'] }}</h3>
                <p class="mb-0">Total Appointments</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card-warning">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h3>{{ $stats['pending_appointments'] }}</h3>
                <p class="mb-0">Pending</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card-info">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h3>{{ $stats['confirmed_appointments'] }}</h3>
                <p class="mb-0">Confirmed</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card-success">
            <div class="card-body text-center">
                <i class="fas fa-stethoscope fa-2x mb-2"></i>
                <h3>{{ $stats['total_services'] }}</h3>
                <p class="mb-0">Services</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Appointment Status Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie me-2"></i>Appointment Status</h5>
            </div>
            <div class="card-body">
                <canvas id="appointmentChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('health-worker.services.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Service
                    </a>
                    <a href="{{ route('health-worker.appointments') }}" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-2"></i>View Appointments
                    </a>
                    <a href="{{ route('health-worker.services') }}" class="btn btn-outline-primary">
                        <i class="fas fa-stethoscope me-2"></i>Manage Services
                    </a>
                    <button class="btn btn-outline-info" onclick="filterTodayAppointments()">
                        <i class="fas fa-calendar-day me-2"></i>Today's Appointments
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Appointments -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-calendar-alt me-2"></i>Recent Appointments</h5>
                <a href="{{ route('health-worker.appointments') }}" class="btn btn-sm btn-outline-primary">View All</a>
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($appointments, 0, 5) as $appointmentId => $appointment)
                                    <tr>
                                        <td>
                                            <strong>{{ $appointment['patient_name'] ?? 'N/A' }}</strong>
                                            @if(isset($appointment['patient_phone']))
                                                <br><small class="text-muted">{{ $appointment['patient_phone'] }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $appointment['service_name'] ?? 'N/A' }}</td>
                                        <td>
                                            @if(isset($appointment['appointment_date']))
                                                {{ date('M d, Y', strtotime($appointment['appointment_date'])) }}
                                                <br><small class="text-muted">{{ date('h:i A', strtotime($appointment['appointment_date'])) }}</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $appointment['status'] === 'completed' ? 'success' : ($appointment['status'] === 'confirmed' ? 'info' : ($appointment['status'] === 'cancelled' ? 'danger' : 'warning')) }}">
                                                {{ ucfirst($appointment['status'] ?? 'pending') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($appointment['status'] === 'pending')
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-success" onclick="updateStatus('{{ $appointmentId }}', 'confirmed')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="updateStatus('{{ $appointmentId }}', 'cancelled')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @elseif($appointment['status'] === 'confirmed')
                                                <button class="btn btn-outline-primary btn-sm" onclick="updateStatus('{{ $appointmentId }}', 'completed')">
                                                    <i class="fas fa-check-double"></i> Complete
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No appointments found</h6>
                        <p class="text-muted">Appointments will appear here once patients book services.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Services Overview -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-stethoscope me-2"></i>Your Services</h5>
                <a href="{{ route('health-worker.services') }}" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
            <div class="card-body">
                @if(count($services) > 0)
                    <div class="list-group list-group-flush">
                        @foreach(array_slice($services, 0, 5) as $serviceId => $service)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <strong>{{ $service['name'] ?? 'N/A' }}</strong>
                                    <br>
                                    <small class="text-muted">₱{{ number_format($service['price'] ?? 0, 2) }} • {{ $service['duration'] ?? 0 }} min</small>
                                </div>
                                <span class="badge bg-{{ isset($service['is_active']) && $service['is_active'] ? 'success' : 'secondary' }}">
                                    {{ isset($service['is_active']) && $service['is_active'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-stethoscope fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-2">No services yet</p>
                        <a href="{{ route('health-worker.services.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Service
                        </a>
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
// Appointment Status Chart
const ctx = document.getElementById('appointmentChart').getContext('2d');
const appointmentChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Confirmed', 'Completed'],
        datasets: [{
            data: [
                {{ $stats['pending_appointments'] }},
                {{ $stats['confirmed_appointments'] }},
                {{ $stats['completed_appointments'] }}
            ],
            backgroundColor: [
                '#ffc107',
                '#17a2b8',
                '#28a745'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

function updateStatus(appointmentId, status) {
    document.getElementById('statusInput').value = status;
    document.getElementById('statusForm').action = `/health-worker/appointments/${appointmentId}/status`;
    
    const actionText = status === 'confirmed' ? 'confirm' : (status === 'completed' ? 'complete' : 'cancel');
    document.getElementById('statusAction').textContent = actionText;
    document.getElementById('statusSubmit').textContent = `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Appointment`;
    
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function filterTodayAppointments() {
    const today = new Date().toISOString().split('T')[0];
    window.location.href = `{{ route('health-worker.appointments') }}?date=${today}`;
}
</script>
@endsection
