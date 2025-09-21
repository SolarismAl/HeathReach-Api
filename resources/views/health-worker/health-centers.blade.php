@extends('layouts.app')

@section('title', 'Health Centers - HealthReach')
@section('page-title', 'Health Centers')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-hospital me-2"></i>Health Centers</h5>
                <span class="badge bg-primary">{{ count($healthCenters) }} Centers</span>
            </div>
            <div class="card-body">
                @if(count($healthCenters) > 0)
                    <div class="row">
                        @foreach($healthCenters as $centerId => $center)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 border-{{ isset($center['is_active']) && $center['is_active'] ? 'success' : 'secondary' }}">
                                    <div class="card-header bg-{{ isset($center['is_active']) && $center['is_active'] ? 'success' : 'secondary' }} text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">{{ $center['name'] ?? 'Unknown Center' }}</h6>
                                            <span class="badge bg-light text-dark">
                                                {{ isset($center['is_active']) && $center['is_active'] ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- Contact Information -->
                                        <div class="mb-3">
                                            <h6 class="text-primary"><i class="fas fa-info-circle me-1"></i>Contact Info</h6>
                                            @if(isset($center['address']))
                                                <p class="mb-1"><i class="fas fa-map-marker-alt text-muted me-2"></i>{{ $center['address'] }}</p>
                                            @endif
                                            @if(isset($center['contact_number']))
                                                <p class="mb-1"><i class="fas fa-phone text-muted me-2"></i>{{ $center['contact_number'] }}</p>
                                            @endif
                                            @if(isset($center['email']))
                                                <p class="mb-1"><i class="fas fa-envelope text-muted me-2"></i>{{ $center['email'] }}</p>
                                            @endif
                                        </div>

                                        <!-- Description -->
                                        @if(isset($center['description']))
                                            <div class="mb-3">
                                                <h6 class="text-primary"><i class="fas fa-file-alt me-1"></i>Description</h6>
                                                <p class="text-muted small">{{ Str::limit($center['description'], 100) }}</p>
                                            </div>
                                        @endif

                                        <!-- Services Offered -->
                                        @if(isset($center['services']) && is_array($center['services']))
                                            <div class="mb-3">
                                                <h6 class="text-primary"><i class="fas fa-stethoscope me-1"></i>Services Offered</h6>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach(array_slice($center['services'], 0, 3) as $service)
                                                        <span class="badge bg-info">{{ $service }}</span>
                                                    @endforeach
                                                    @if(count($center['services']) > 3)
                                                        <span class="badge bg-secondary">+{{ count($center['services']) - 3 }} more</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Operating Hours -->
                                        @if(isset($center['operating_hours']) && is_array($center['operating_hours']))
                                            <div class="mb-3">
                                                <h6 class="text-primary"><i class="fas fa-clock me-1"></i>Operating Hours</h6>
                                                <div class="small">
                                                    @php
                                                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                                        $todayHours = null;
                                                        $currentDay = strtolower(date('l'));
                                                        if(isset($center['operating_hours'][$currentDay])) {
                                                            $todayHours = $center['operating_hours'][$currentDay];
                                                        }
                                                    @endphp
                                                    @if($todayHours)
                                                        <p class="mb-1 fw-bold text-success">
                                                            <i class="fas fa-calendar-day me-1"></i>Today: {{ $todayHours }}
                                                        </p>
                                                    @endif
                                                    <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#hoursModal{{ $centerId }}">
                                                        <i class="fas fa-eye me-1"></i>View All Hours
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                @if(isset($center['created_at']))
                                                    Added {{ date('M d, Y', strtotime($center['created_at'])) }}
                                                @else
                                                    No date available
                                                @endif
                                            </small>
                                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#centerModal{{ $centerId }}">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Health Center Details Modal -->
                            <div class="modal fade" id="centerModal{{ $centerId }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $center['name'] ?? 'Health Center Details' }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Contact Information</h6>
                                                    <p><strong>Address:</strong> {{ $center['address'] ?? 'N/A' }}</p>
                                                    <p><strong>Phone:</strong> {{ $center['contact_number'] ?? 'N/A' }}</p>
                                                    <p><strong>Email:</strong> {{ $center['email'] ?? 'N/A' }}</p>
                                                    <p><strong>Status:</strong> 
                                                        <span class="badge bg-{{ isset($center['is_active']) && $center['is_active'] ? 'success' : 'secondary' }}">
                                                            {{ isset($center['is_active']) && $center['is_active'] ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Additional Information</h6>
                                                    @if(isset($center['description']))
                                                        <p><strong>Description:</strong> {{ $center['description'] }}</p>
                                                    @endif
                                                    <p><strong>Created:</strong> {{ isset($center['created_at']) ? date('M d, Y h:i A', strtotime($center['created_at'])) : 'N/A' }}</p>
                                                    <p><strong>Last Updated:</strong> {{ isset($center['updated_at']) ? date('M d, Y h:i A', strtotime($center['updated_at'])) : 'N/A' }}</p>
                                                </div>
                                            </div>
                                            
                                            @if(isset($center['services']) && is_array($center['services']))
                                                <hr>
                                                <h6>Services Offered</h6>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($center['services'] as $service)
                                                        <span class="badge bg-info">{{ $service }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Operating Hours Modal -->
                            @if(isset($center['operating_hours']) && is_array($center['operating_hours']))
                                <div class="modal fade" id="hoursModal{{ $centerId }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Operating Hours - {{ $center['name'] ?? 'Health Center' }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="list-group">
                                                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                                        <div class="list-group-item d-flex justify-content-between align-items-center {{ strtolower(date('l')) === $day ? 'bg-light' : '' }}">
                                                            <strong>{{ ucfirst($day) }}</strong>
                                                            <span class="badge bg-{{ isset($center['operating_hours'][$day]) && $center['operating_hours'][$day] !== 'Closed' ? 'success' : 'secondary' }}">
                                                                {{ $center['operating_hours'][$day] ?? 'Not specified' }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-hospital fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No health centers found</h5>
                        <p class="text-muted">Health centers will appear here once they are created by administrators.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card-success">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h3>{{ collect($healthCenters)->where('is_active', true)->count() }}</h3>
                <p class="mb-0">Active Centers</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card-secondary">
            <div class="card-body text-center">
                <i class="fas fa-pause-circle fa-2x mb-2"></i>
                <h3>{{ collect($healthCenters)->where('is_active', false)->count() }}</h3>
                <p class="mb-0">Inactive Centers</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card-info">
            <div class="card-body text-center">
                <i class="fas fa-stethoscope fa-2x mb-2"></i>
                <h3>{{ collect($healthCenters)->sum(function($center) { return isset($center['services']) ? count($center['services']) : 0; }) }}</h3>
                <p class="mb-0">Total Services</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card-primary">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h3>{{ collect($healthCenters)->filter(function($center) { 
                    $today = strtolower(date('l'));
                    return isset($center['operating_hours'][$today]) && $center['operating_hours'][$today] !== 'Closed';
                })->count() }}</h3>
                <p class="mb-0">Open Today</p>
            </div>
        </div>
    </div>
</div>
@endsection
