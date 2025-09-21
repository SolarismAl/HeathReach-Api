@extends('layouts.app')

@section('title', 'Add Service - HealthReach')
@section('page-title', 'Add Service')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus me-2"></i>Add New Service</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('health-worker.services.store') }}">
                    @csrf
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="health_center_id" class="form-label">Health Center *</label>
                            <select class="form-select" id="health_center_id" name="health_center_id" required>
                                <option value="">Select Health Center</option>
                                @foreach($healthCenters as $centerId => $center)
                                    <option value="{{ $centerId }}" {{ old('health_center_id') === $centerId ? 'selected' : '' }}>
                                        {{ $center['name'] ?? 'Unknown Center' }}
                                        @if(isset($center['address']))
                                            - {{ $center['address'] }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Select the health center where this service will be provided</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Service Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="consultation" {{ old('category') === 'consultation' ? 'selected' : '' }}>Consultation</option>
                                <option value="checkup" {{ old('category') === 'checkup' ? 'selected' : '' }}>Check-up</option>
                                <option value="vaccination" {{ old('category') === 'vaccination' ? 'selected' : '' }}>Vaccination</option>
                                <option value="laboratory" {{ old('category') === 'laboratory' ? 'selected' : '' }}>Laboratory</option>
                                <option value="therapy" {{ old('category') === 'therapy' ? 'selected' : '' }}>Therapy</option>
                                <option value="emergency" {{ old('category') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                                <option value="dental" {{ old('category') === 'dental' ? 'selected' : '' }}>Dental</option>
                                <option value="maternity" {{ old('category') === 'maternity' ? 'selected' : '' }}>Maternity</option>
                                <option value="pediatric" {{ old('category') === 'pediatric' ? 'selected' : '' }}>Pediatric</option>
                                <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Describe what this service includes...">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price (₱) *</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="{{ old('price') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="duration" class="form-label">Duration (minutes) *</label>
                            <div class="input-group">
                                <input type="number" min="1" class="form-control" id="duration" name="duration" value="{{ old('duration', 30) }}" required>
                                <span class="input-group-text">min</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    <strong>Active Service</strong>
                                    <br><small class="text-muted">Patients can book appointments for active services</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('health-worker.services') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Services
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Service
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Service Preview Card -->
<div class="row mt-4">
    <div class="col-md-4 mx-auto">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Service Preview</h6>
            </div>
            <div class="card-body">
                <h6 id="previewName" class="card-title">Service Name</h6>
                <p id="previewDescription" class="card-text text-muted small">Service description will appear here...</p>
                <span id="previewCategory" class="badge bg-info mb-2">Category</span>
                <div class="row text-center">
                    <div class="col-6">
                        <small class="text-muted">Price</small>
                        <div id="previewPrice" class="fw-bold text-success">₱0.00</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Duration</small>
                        <div id="previewDuration" class="fw-bold">30 min</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Live preview functionality
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const descriptionInput = document.getElementById('description');
    const categoryInput = document.getElementById('category');
    const priceInput = document.getElementById('price');
    const durationInput = document.getElementById('duration');
    
    const previewName = document.getElementById('previewName');
    const previewDescription = document.getElementById('previewDescription');
    const previewCategory = document.getElementById('previewCategory');
    const previewPrice = document.getElementById('previewPrice');
    const previewDuration = document.getElementById('previewDuration');
    
    function updatePreview() {
        previewName.textContent = nameInput.value || 'Service Name';
        previewDescription.textContent = descriptionInput.value || 'Service description will appear here...';
        previewCategory.textContent = categoryInput.value ? categoryInput.options[categoryInput.selectedIndex].text : 'Category';
        previewPrice.textContent = '₱' + (parseFloat(priceInput.value) || 0).toFixed(2);
        previewDuration.textContent = (durationInput.value || 30) + ' min';
    }
    
    nameInput.addEventListener('input', updatePreview);
    descriptionInput.addEventListener('input', updatePreview);
    categoryInput.addEventListener('change', updatePreview);
    priceInput.addEventListener('input', updatePreview);
    durationInput.addEventListener('input', updatePreview);
});
</script>
@endsection
