@extends('layouts.app')

@section('title', 'Services - HealthReach')
@section('page-title', 'Services Management')

@section('content')

<script>
console.log('=== SERVICES BLADE TEMPLATE ===');
console.log('Services Data:', @json($services));
console.log('Services Count:', {{ count($services) }});
console.log('Services Keys:', Object.keys(@json($services)));

@if(isset($healthCenters))
console.log('Health Centers Available:', @json($healthCenters));
console.log('Health Centers Count:', {{ count($healthCenters ?? []) }});
@endif

@foreach($services as $serviceId => $service)
console.log('Service ID: {{ $serviceId }}');
console.log('Service Data:', @json($service));
console.log('Service Name:', '{{ $service['name'] ?? 'Unknown' }}');
console.log('Health Center ID:', '{{ $service['health_center_id'] ?? 'N/A' }}');
@if(isset($healthCenters) && isset($service['health_center_id']) && isset($healthCenters[$service['health_center_id']]))
console.log('Health Center Name:', '{{ $healthCenters[$service['health_center_id']]['name'] ?? 'Unknown' }}');
@else
console.log('Health Center Name:', 'Not Found');
@endif
console.log('Service Price:', {{ $service['price'] ?? 0 }});
console.log('Service Duration:', {{ $service['duration'] ?? 0 }});
console.log('Service Active:', {{ isset($service['is_active']) && $service['is_active'] ? 'true' : 'false' }});
console.log('---');
@endforeach

// Group services by health center
const servicesByCenter = {};
@foreach($services as $serviceId => $service)
const centerId = '{{ $service['health_center_id'] ?? 'unknown' }}';
if (!servicesByCenter[centerId]) {
    servicesByCenter[centerId] = [];
}
servicesByCenter[centerId].push({
    id: '{{ $serviceId }}',
    name: '{{ $service['name'] ?? 'Unknown' }}',
    price: {{ $service['price'] ?? 0 }},
    duration: {{ $service['duration'] ?? 0 }}
});
@endforeach

console.log('Services Grouped by Health Center:', servicesByCenter);

// Debug: Check for data inconsistencies
console.log('=== DATA CONSISTENCY CHECK ===');
@if(isset($healthCenters))
@foreach($healthCenters as $centerId => $center)
console.log('Health Center from Services Page:');
console.log('  ID: {{ $centerId }}');
console.log('  Name: {{ $center['name'] ?? 'N/A' }}');
console.log('  Address: {{ $center['address'] ?? 'N/A' }}');
console.log('  Email: {{ $center['email'] ?? 'N/A' }}');
@endforeach
@endif

// Count services per health center
const serviceCounts = {};
@foreach($services as $serviceId => $service)
const centerId = '{{ $service['health_center_id'] ?? 'unknown' }}';
if (!serviceCounts[centerId]) {
    serviceCounts[centerId] = 0;
}
serviceCounts[centerId]++;
@endforeach

console.log('Service Counts per Health Center:', serviceCounts);
console.log('=== END DATA CONSISTENCY CHECK ===');
console.log('=== END SERVICES BLADE ===');
</script>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-stethoscope me-2"></i>Your Services</h5>
                <a href="{{ route('health-worker.services.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Service
                </a>
            </div>
            <div class="card-body">
                @if(count($services) > 0)
                    <div class="row">
                        @foreach($services as $serviceId => $service)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title">{{ $service['name'] ?? 'N/A' }}</h6>
                                            <span class="badge bg-{{ isset($service['is_active']) && $service['is_active'] ? 'success' : 'secondary' }}">
                                                {{ isset($service['is_active']) && $service['is_active'] ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                        
                                        @if(isset($service['health_center_id']) && $service['health_center_id'])
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-hospital me-1"></i>Health Center ID: {{ Str::limit($service['health_center_id'], 20) }}
                                                </small>
                                            </div>
                                        @endif
                                        
                                        <p class="card-text text-muted small">
                                            {{ Str::limit($service['description'] ?? 'No description available', 100) }}
                                        </p>
                                        
                                        <div class="mb-2">
                                            <span class="badge bg-info">{{ $service['category'] ?? 'General' }}</span>
                                        </div>
                                        
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <small class="text-muted">Price</small>
                                                <div class="fw-bold text-success">₱{{ number_format($service['price'] ?? 0, 2) }}</div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Duration</small>
                                                <div class="fw-bold">{{ $service['duration'] ?? 0 }} min</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer bg-transparent">
                                        <div class="btn-group w-100">
                                            <a href="{{ route('health-worker.services.edit', $serviceId) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#serviceModal{{ $serviceId }}">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete('{{ $serviceId }}', '{{ $service['name'] ?? 'Service' }}')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Service Details Modal -->
                            <div class="modal fade" id="serviceModal{{ $serviceId }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $service['name'] ?? 'Service' }} Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Service Name:</strong><br>
                                                    {{ $service['name'] ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Category:</strong><br>
                                                    <span class="badge bg-info">{{ $service['category'] ?? 'General' }}</span>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <strong>Description:</strong><br>
                                                    {{ $service['description'] ?? 'No description available' }}
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <strong>Price:</strong><br>
                                                    <span class="text-success fw-bold">₱{{ number_format($service['price'] ?? 0, 2) }}</span>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Duration:</strong><br>
                                                    {{ $service['duration'] ?? 0 }} minutes
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Status:</strong><br>
                                                    <span class="badge bg-{{ isset($service['is_active']) && $service['is_active'] ? 'success' : 'secondary' }}">
                                                        {{ isset($service['is_active']) && $service['is_active'] ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if(isset($service['created_at']))
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <strong>Created:</strong><br>
                                                        {{ date('M d, Y h:i A', strtotime($service['created_at'])) }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-stethoscope fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No services found</h5>
                        <p class="text-muted">Create your first service to start accepting appointments.</p>
                        <a href="{{ route('health-worker.services.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Service
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteServiceName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone and will affect all related appointments.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Service</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function confirmDelete(serviceId, serviceName) {
    document.getElementById('deleteServiceName').textContent = serviceName;
    document.getElementById('deleteForm').action = `/health-worker/services/${serviceId}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection
