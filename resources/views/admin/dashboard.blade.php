@extends('layouts.app')

@section('title', 'Admin Dashboard - HealthReach')
@section('page-title', 'Admin Dashboard')

@section('content')
<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x mb-2"></i>
                <h3>{{ $stats['total_users'] }}</h3>
                <p class="mb-0">Total Users</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card-success">
            <div class="card-body text-center">
                <i class="fas fa-hospital fa-2x mb-2"></i>
                <h3>{{ $stats['total_health_centers'] }}</h3>
                <p class="mb-0">Health Centers</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card-warning">
            <div class="card-body text-center">
                <i class="fas fa-stethoscope fa-2x mb-2"></i>
                <h3>{{ $stats['total_services'] }}</h3>
                <p class="mb-0">Services</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card-info">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                <h3>{{ $stats['total_appointments'] }}</h3>
                <p class="mb-0">Appointments</p>
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
                    <a href="{{ route('admin.health-centers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Health Center
                    </a>
                    <a href="{{ route('admin.users') }}" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                    <a href="{{ route('admin.appointments') }}" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-2"></i>View Appointments
                    </a>
                    <a href="{{ route('admin.logs') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-list-alt me-2"></i>Activity Logs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Users -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-user-plus me-2"></i>Recent Users</h5>
                <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                @if(count($recentUsers) > 0)
                    <div class="list-group list-group-flush">
                        @foreach(array_slice($recentUsers, 0, 5) as $user)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>
                                        @if(isset($user['name']))
                                            {{ $user['name'] }}
                                        @elseif(isset($user['first_name']))
                                            {{ $user['first_name'] }} {{ $user['last_name'] ?? '' }}
                                        @else
                                            Unknown User
                                        @endif
                                    </strong>
                                    <br>
                                    <small class="text-muted">{{ $user['email'] ?? 'No email' }}</small>
                                </div>
                                <span class="badge bg-{{ $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'health_worker' ? 'warning' : 'primary') }}">
                                    {{ ucfirst($user['role'] ?? 'user') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center">No users found.</p>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Recent Appointments -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-calendar-alt me-2"></i>Recent Appointments</h5>
                <a href="{{ route('admin.appointments') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                @if(count($recentAppointments) > 0)
                    <div class="list-group list-group-flush">
                        @foreach(array_slice($recentAppointments, 0, 5) as $appointment)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>
                                            @if(isset($appointment['user']['name']))
                                                {{ $appointment['user']['name'] }}
                                            @elseif(isset($appointment['user']) && is_array($appointment['user']) && isset($appointment['user']['first_name']))
                                                {{ $appointment['user']['first_name'] }} {{ $appointment['user']['last_name'] ?? '' }}
                                            @else
                                                Unknown Patient
                                            @endif
                                        </strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            @if(isset($appointment['date']) && isset($appointment['time']))
                                                {{ $appointment['date'] }} at {{ $appointment['time'] }}
                                            @elseif(isset($appointment['created_at']))
                                                {{ date('M d, Y', strtotime($appointment['created_at'])) }}
                                            @else
                                                Date not available
                                            @endif
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-stethoscope me-1"></i>
                                            @if(isset($appointment['service']['service_name']))
                                                {{ $appointment['service']['service_name'] }}
                                            @elseif(isset($appointment['service_name']))
                                                {{ $appointment['service_name'] }}
                                            @else
                                                Service not available
                                            @endif
                                        </small>
                                    </div>
                                    <span class="badge bg-{{ $appointment['status'] === 'completed' ? 'success' : ($appointment['status'] === 'confirmed' ? 'info' : ($appointment['status'] === 'cancelled' ? 'danger' : 'warning')) }}">
                                        {{ ucfirst($appointment['status'] ?? 'pending') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center">No appointments found.</p>
                @endif
            </div>
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
</script>
@endsection
