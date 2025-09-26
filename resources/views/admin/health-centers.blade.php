@extends('layouts.app')

@section('title', 'Health Centers - HealthReach')
@section('page-title', 'Health Centers Management')

@section('content')

<script>
console.log('=== ADMIN HEALTH CENTERS BLADE TEMPLATE ===');
console.log('Health Centers Data:', @json($healthCenters));
console.log('Health Centers Count:', {{ count($healthCenters) }});
console.log('Health Centers Keys:', Object.keys(@json($healthCenters)));

@foreach($healthCenters as $centerId => $center)
console.log('Admin - Health Center ID: {{ $centerId }}');
console.log('Admin - Health Center Data:', @json($center));
console.log('Admin - Health Center Name:', '{{ $center['name'] ?? 'Unknown' }}');
console.log('Admin - Health Center Services:', @json($center['services'] ?? []));
console.log('Admin - Services Count for {{ $center['name'] ?? 'Unknown' }}:', {{ isset($center['services']) && is_array($center['services']) ? count($center['services']) : 0 }});
@endforeach

console.log('=== END ADMIN HEALTH CENTERS BLADE ===');
</script>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-hospital me-2"></i>Health Centers</h5>
                <a href="{{ route('admin.health-centers.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Health Center
                </a>
            </div>
            <div class="card-body">
                @if(count($healthCenters) > 0)
                    <div class="row">
                        @foreach($healthCenters as $centerId => $center)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title">{{ $center['name'] ?? 'N/A' }}</h6>
                                            <span class="badge bg-{{ isset($center['is_active']) && $center['is_active'] ? 'success' : 'secondary' }}">
                                                {{ isset($center['is_active']) && $center['is_active'] ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                {{ $center['address'] ?? 'N/A' }}
                                            </small>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                {{ $center['phone'] ?? 'N/A' }}
                                            </small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i>
                                                {{ $center['email'] ?? 'N/A' }}
                                            </small>
                                        </div>
                                        
                                        @if(isset($center['latitude']) && isset($center['longitude']))
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-globe me-1"></i>
                                                    {{ $center['latitude'] }}, {{ $center['longitude'] }}
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="card-footer bg-transparent">
                                        <div class="btn-group w-100">
                                            <a href="{{ route('admin.health-centers.edit', $centerId) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#centerModal{{ $centerId }}">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete('{{ $centerId }}', '{{ $center['name'] ?? 'Health Center' }}')">
                                                <i class="fas fa-trash"></i> Delete
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
                                            <h5 class="modal-title">{{ $center['name'] ?? 'Health Center' }} Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Name:</strong><br>
                                                    {{ $center['name'] ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Status:</strong><br>
                                                    <span class="badge bg-{{ isset($center['is_active']) && $center['is_active'] ? 'success' : 'secondary' }}">
                                                        {{ isset($center['is_active']) && $center['is_active'] ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <strong>Address:</strong><br>
                                                    {{ $center['address'] ?? 'N/A' }}
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Phone:</strong><br>
                                                    {{ $center['phone'] ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Email:</strong><br>
                                                    {{ $center['email'] ?? 'N/A' }}
                                                </div>
                                            </div>
                                            @if(isset($center['latitude']) && isset($center['longitude']))
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Latitude:</strong><br>
                                                        {{ $center['latitude'] }}
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Longitude:</strong><br>
                                                        {{ $center['longitude'] }}
                                                    </div>
                                                </div>
                                            @endif
                                            @if(isset($center['created_at']))
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <strong>Created:</strong><br>
                                                        {{ date('M d, Y h:i A', strtotime($center['created_at'])) }}
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
                        <i class="fas fa-hospital fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No health centers found</h5>
                        <p class="text-muted">Add your first health center to get started.</p>
                        <a href="{{ route('admin.health-centers.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Health Center
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
                <p>Are you sure you want to delete <strong id="deleteCenterName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone and will affect all related services and appointments.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Health Center</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function confirmDelete(centerId, centerName) {
    document.getElementById('deleteCenterName').textContent = centerName;
    document.getElementById('deleteForm').action = `/admin/health-centers/${centerId}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection
