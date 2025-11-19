@extends('layouts.app')

@section('title', 'Appointments - HealthReach')
@section('page-title', 'All Appointments')

@section('content')
<script>
console.log('=== APPOINTMENTS BLADE TEMPLATE ===');
console.log('Appointments Data:', @json($appointments));
console.log('Appointments Count:', Object.keys(@json($appointments)).length);
</script>

<!-- Statistics Cards -->
<div class="row mb-4">
    @php
        $totalAppointments = count($appointments);
        $pendingCount = 0;
        $confirmedCount = 0;
        $completedCount = 0;
        $cancelledCount = 0;
        $totalRevenue = 0;
        
        foreach($appointments as $appointment) {
            $status = $appointment['status'] ?? 'pending';
            switch($status) {
                case 'pending':
                    $pendingCount++;
                    break;
                case 'confirmed':
                    $confirmedCount++;
                    break;
                case 'completed':
                    $completedCount++;
                    if(isset($appointment['service_price'])) {
                        $totalRevenue += $appointment['service_price'];
                    }
                    break;
                case 'cancelled':
                    $cancelledCount++;
                    break;
            }
        }
    @endphp
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Appointments</p>
                        <h3 class="mb-0">{{ $totalAppointments }}</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded p-3">
                        <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Pending</p>
                        <h3 class="mb-0 text-warning">{{ $pendingCount }}</h3>
                        <small class="text-muted">Awaiting confirmation</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded p-3">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Confirmed</p>
                        <h3 class="mb-0 text-info">{{ $confirmedCount }}</h3>
                        <small class="text-muted">Upcoming appointments</small>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded p-3">
                        <i class="fas fa-check-circle fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Completed</p>
                        <h3 class="mb-0 text-success">{{ $completedCount }}</h3>
                        <small class="text-muted">Successfully finished</small>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded p-3">
                        <i class="fas fa-check-double fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Metrics Row -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Cancelled</p>
                        <h3 class="mb-0 text-danger">{{ $cancelledCount }}</h3>
                        <small class="text-muted">{{ $totalAppointments > 0 ? round(($cancelledCount / $totalAppointments) * 100, 1) : 0 }}% cancellation rate</small>
                    </div>
                    <div class="bg-danger bg-opacity-10 rounded p-3">
                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Revenue</p>
                        <h3 class="mb-0 text-success">₱{{ number_format($totalRevenue, 2) }}</h3>
                        <small class="text-muted">From completed appointments</small>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded p-3">
                        <i class="fas fa-peso-sign fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Completion Rate</p>
                        <h3 class="mb-0 text-primary">{{ $totalAppointments > 0 ? round(($completedCount / $totalAppointments) * 100, 1) : 0 }}%</h3>
                        <small class="text-muted">{{ $completedCount }} of {{ $totalAppointments }} appointments</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded p-3">
                        <i class="fas fa-chart-line fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Appointments Table -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-calendar-check me-2"></i>All Appointments</h5>
                <div class="d-flex align-items-center" style="gap: 0.5rem;">
                    <form method="GET" action="{{ route('admin.appointments') }}" class="d-flex align-items-center" style="gap: 0.5rem;" id="filterForm">
                        <select name="status" class="form-select form-select-sm" style="min-width: 140px;" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        <input type="date" name="date" class="form-control form-control-sm" style="min-width: 150px;" value="{{ request('date') }}" onchange="document.getElementById('filterForm').submit()">
                        @if(request('status') || request('date'))
                            <a href="{{ route('admin.appointments') }}" class="btn btn-sm btn-outline-secondary" title="Clear filters">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
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
                                <a href="{{ route('admin.appointments') }}">view all appointments</a>.
                            @else
                                Appointments will appear here once patients start booking services.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection