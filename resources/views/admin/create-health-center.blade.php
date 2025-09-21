@extends('layouts.app')

@section('title', 'Add Health Center - HealthReach')
@section('page-title', 'Add Health Center')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus me-2"></i>Add New Health Center</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.health-centers.store') }}">
                    @csrf
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="name" class="form-label">Health Center Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required>{{ old('address') }}</textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">Latitude *</label>
                            <input type="number" step="any" class="form-control" id="latitude" name="latitude" value="{{ old('latitude') }}" required>
                            <small class="form-text text-muted">Example: 14.5995</small>
                        </div>
                        <div class="col-md-6">
                            <label for="longitude" class="form-label">Longitude *</label>
                            <input type="number" step="any" class="form-control" id="longitude" name="longitude" value="{{ old('longitude') }}" required>
                            <small class="form-text text-muted">Example: 120.9842</small>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.health-centers') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Health Centers
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Health Center
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
