@extends('layouts.app')

@section('title', 'Send Alerts - Health Worker - HealthReach')
@section('page-title', 'Send Alerts to Patients')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Send Alert to Patients</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('health-worker.notifications.send') }}" method="POST">
                    @csrf
                    
                    <!-- Recipient Selection -->
                    <div class="mb-3">
                        <label for="recipient" class="form-label">Send To</label>
                        <select class="form-select @error('recipient') is-invalid @enderror" id="recipient" name="recipient" required onchange="togglePatientSelection()">
                            <option value="">Select Recipients</option>
                            <option value="patients" {{ old('recipient') == 'patients' ? 'selected' : '' }}>All Patients</option>
                            <option value="my_patients" {{ old('recipient') == 'my_patients' ? 'selected' : '' }}>My Patients Only</option>
                            <option value="specific_patient" {{ old('recipient') == 'specific_patient' ? 'selected' : '' }}>Specific Patient</option>
                        </select>
                        @error('recipient')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Health workers can only send alerts to patients</div>
                    </div>

                    <!-- Specific Patient Selection (Hidden by default) -->
                    <div class="mb-3" id="patientSelectionDiv" style="display: none;">
                        <label for="patient_id" class="form-label">Select Patient</label>
                        <div class="input-group">
                            <select class="form-select @error('patient_id') is-invalid @enderror" id="patient_id" name="patient_id">
                                <option value="">Loading patients...</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" onclick="loadPatients()" id="refreshPatientsBtn">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        @error('patient_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Select a specific patient to send the alert to</div>
                    </div>

                    <!-- Notification Type -->
                    <div class="mb-3">
                        <label for="type" class="form-label">Alert Type</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="appointment" {{ old('type') == 'appointment' ? 'selected' : '' }}>
                                <i class="fas fa-calendar"></i> Appointment Related
                            </option>
                            <option value="service" {{ old('type') == 'service' ? 'selected' : '' }}>
                                <i class="fas fa-stethoscope"></i> Service Update
                            </option>
                            <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>
                                <i class="fas fa-info-circle"></i> General Information
                            </option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Alert Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title') }}" 
                               placeholder="Enter alert title" maxlength="100" required>
                        <div class="form-text">Maximum 100 characters</div>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Message -->
                    <div class="mb-3">
                        <label for="message" class="form-label">Alert Message <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('message') is-invalid @enderror" 
                                  id="message" name="message" rows="4" 
                                  placeholder="Enter alert message" maxlength="500" required>{{ old('message') }}</textarea>
                        <div class="form-text">Maximum 500 characters</div>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Health Center (if applicable) -->
                    <div class="mb-3">
                        <label for="health_center" class="form-label">Related Health Center (Optional)</label>
                        <select class="form-select" id="health_center" name="health_center_id">
                            <option value="">Select Health Center</option>
                            @if(isset($healthCenters))
                                @foreach($healthCenters as $center)
                                    <option value="{{ $center['health_center_id'] }}" {{ old('health_center_id') == $center['health_center_id'] ? 'selected' : '' }}>
                                        {{ $center['name'] }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div class="form-text">Link this alert to a specific health center if relevant</div>
                    </div>

                    <!-- Send Button -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg" id="sendButton">
                            <span id="sendButtonText">
                                <i class="fas fa-paper-plane me-2"></i>Send Alert to Patients
                            </span>
                            <span id="sendButtonLoading" style="display: none;">
                                <i class="fas fa-spinner fa-spin me-2"></i>Sending Alert...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Preview Card -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Mobile App Preview</h6>
            </div>
            <div class="card-body">
                <div class="mobile-preview">
                    <div class="notification-preview" id="notificationPreview">
                        <div class="notification-icon">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title" id="previewTitle">Alert Title</div>
                            <div class="notification-message" id="previewMessage">Alert message will appear here...</div>
                            <div class="notification-time">Just now</div>
                        </div>
                    </div>
                </div>
                <small class="text-muted">This is how your alert will appear in the mobile app</small>
            </div>
        </div>

        <!-- Quick Templates for Health Workers -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-templates me-2"></i>Quick Templates</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="useTemplate('appointment_reminder')">
                        <i class="fas fa-clock me-1"></i> Appointment Reminder
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="useTemplate('test_results')">
                        <i class="fas fa-file-medical me-1"></i> Test Results Ready
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="useTemplate('schedule_change')">
                        <i class="fas fa-calendar-alt me-1"></i> Schedule Change
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="useTemplate('health_tip')">
                        <i class="fas fa-heart me-1"></i> Health Tip
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="useTemplate('follow_up')">
                        <i class="fas fa-user-md me-1"></i> Follow-up Required
                    </button>
                </div>
            </div>
        </div>

        <!-- My Statistics -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>My Alert Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-success">{{ $stats['my_notifications'] ?? 0 }}</h4>
                        <small>Alerts Sent</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-primary">{{ $stats['my_patients'] ?? 0 }}</h4>
                        <small>My Patients</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.mobile-preview {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 10px;
}

.notification-preview {
    background: white;
    border-radius: 12px;
    padding: 15px;
    display: flex;
    align-items: flex-start;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.notification-icon {
    width: 40px;
    height: 40px;
    background: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 12px;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
    font-size: 14px;
}

.notification-message {
    color: #666;
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 6px;
}

.notification-time {
    color: #999;
    font-size: 11px;
}

.form-control:focus, .form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
}
</style>

<script>
// Patient selection functionality
function togglePatientSelection() {
    const recipientSelect = document.getElementById('recipient');
    const patientSelectionDiv = document.getElementById('patientSelectionDiv');
    
    if (recipientSelect.value === 'specific_patient') {
        patientSelectionDiv.style.display = 'block';
        loadPatients(); // Load patients when shown
    } else {
        patientSelectionDiv.style.display = 'none';
    }
}

// Load patients from API
async function loadPatients() {
    const patientSelect = document.getElementById('patient_id');
    const refreshBtn = document.getElementById('refreshPatientsBtn');
    
    // Show loading state
    patientSelect.innerHTML = '<option value="">Loading patients...</option>';
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    refreshBtn.disabled = true;
    
    try {
        // Make API call to get patients
        const response = await fetch('/health-worker/api/patients', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // Clear and populate select
            patientSelect.innerHTML = '<option value="">Select a patient</option>';
            
            if (data.patients && data.patients.length > 0) {
                data.patients.forEach(patient => {
                    const option = document.createElement('option');
                    option.value = patient.id;
                    // Use full_name if available, otherwise combine first and last
                    const displayName = patient.full_name || `${patient.first_name} ${patient.last_name}`.trim();
                    option.textContent = `${displayName} (${patient.email})`;
                    patientSelect.appendChild(option);
                });
            } else {
                patientSelect.innerHTML = '<option value="">No patients found</option>';
            }
        } else {
            patientSelect.innerHTML = '<option value="">Error loading patients</option>';
        }
    } catch (error) {
        console.error('Error loading patients:', error);
        patientSelect.innerHTML = '<option value="">Error loading patients</option>';
    } finally {
        // Reset refresh button
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
        refreshBtn.disabled = false;
    }
}

// Form submission with loading state
document.querySelector('form').addEventListener('submit', function() {
    const sendButton = document.getElementById('sendButton');
    const sendButtonText = document.getElementById('sendButtonText');
    const sendButtonLoading = document.getElementById('sendButtonLoading');
    
    // Show loading state
    sendButton.disabled = true;
    sendButtonText.style.display = 'none';
    sendButtonLoading.style.display = 'inline';
});

// Live preview functionality
document.getElementById('title').addEventListener('input', function() {
    document.getElementById('previewTitle').textContent = this.value || 'Alert Title';
});

document.getElementById('message').addEventListener('input', function() {
    document.getElementById('previewMessage').textContent = this.value || 'Alert message will appear here...';
});

document.getElementById('type').addEventListener('change', function() {
    const icon = document.querySelector('.notification-icon i');
    const iconElement = document.querySelector('.notification-icon');
    
    switch(this.value) {
        case 'appointment':
            icon.className = 'fas fa-calendar';
            iconElement.style.background = '#0d6efd';
            break;
        case 'service':
            icon.className = 'fas fa-stethoscope';
            iconElement.style.background = '#28a745';
            break;
        default:
            icon.className = 'fas fa-info-circle';
            iconElement.style.background = '#17a2b8';
    }
});

// Template functionality for health workers
function useTemplate(type) {
    const titleInput = document.getElementById('title');
    const messageInput = document.getElementById('message');
    const typeSelect = document.getElementById('type');
    const recipientSelect = document.getElementById('recipient');
    
    switch(type) {
        case 'appointment_reminder':
            titleInput.value = 'Appointment Reminder';
            messageInput.value = 'This is a friendly reminder about your upcoming appointment. Please arrive 15 minutes early and bring your ID.';
            typeSelect.value = 'appointment';
            recipientSelect.value = 'my_patients';
            break;
        case 'test_results':
            titleInput.value = 'Test Results Available';
            messageInput.value = 'Your test results are now ready. Please schedule an appointment to discuss the results with your healthcare provider.';
            typeSelect.value = 'service';
            recipientSelect.value = 'my_patients';
            break;
        case 'schedule_change':
            titleInput.value = 'Schedule Change Notice';
            messageInput.value = 'There has been a change to your appointment schedule. Please check your appointments and contact us if you have questions.';
            typeSelect.value = 'appointment';
            recipientSelect.value = 'my_patients';
            break;
        case 'health_tip':
            titleInput.value = 'Health Tip of the Day';
            messageInput.value = 'Remember to stay hydrated and take your medications as prescribed. Your health is our priority!';
            typeSelect.value = 'general';
            recipientSelect.value = 'patients';
            break;
        case 'follow_up':
            titleInput.value = 'Follow-up Required';
            messageInput.value = 'It\'s time for your follow-up appointment. Please schedule your next visit to monitor your progress.';
            typeSelect.value = 'appointment';
            recipientSelect.value = 'my_patients';
            break;
    }
    
    // Trigger preview updates
    titleInput.dispatchEvent(new Event('input'));
    messageInput.dispatchEvent(new Event('input'));
    typeSelect.dispatchEvent(new Event('change'));
}

// Character count
document.getElementById('title').addEventListener('input', function() {
    const maxLength = 100;
    const currentLength = this.value.length;
    const formText = this.nextElementSibling;
    formText.textContent = `${currentLength}/${maxLength} characters`;
    
    if (currentLength > maxLength * 0.9) {
        formText.classList.add('text-warning');
    } else {
        formText.classList.remove('text-warning');
    }
});

document.getElementById('message').addEventListener('input', function() {
    const maxLength = 500;
    const currentLength = this.value.length;
    const formText = this.nextElementSibling;
    formText.textContent = `${currentLength}/${maxLength} characters`;
    
    if (currentLength > maxLength * 0.9) {
        formText.classList.add('text-warning');
    } else {
        formText.classList.remove('text-warning');
    }
});
</script>
@endsection
