@extends('layouts.app')

@section('title', 'Send Alerts - Admin - HealthReach')
@section('page-title', 'Send Alerts to Mobile App Users')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Send Alert to Mobile App</h5>
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

                <form action="{{ route('admin.notifications.send') }}" method="POST">
                    @csrf
                    
                    <!-- Recipient Selection -->
                    <div class="mb-3">
                        <label for="recipient" class="form-label">Send To</label>
                        <select class="form-select @error('recipient') is-invalid @enderror" id="recipient" name="recipient" required onchange="toggleUserSelection()">
                            <option value="">Select Recipients</option>
                            <option value="all" {{ old('recipient') == 'all' ? 'selected' : '' }}>All Users</option>
                            <option value="patients" {{ old('recipient') == 'patients' ? 'selected' : '' }}>Patients Only</option>
                            <option value="health_workers" {{ old('recipient') == 'health_workers' ? 'selected' : '' }}>Health Workers Only</option>
                            <option value="individual" {{ old('recipient') == 'individual' ? 'selected' : '' }}>Individual User</option>
                        </select>
                        @error('recipient')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Individual User Selection (Hidden by default) -->
                    <div class="mb-3" id="userSelectionDiv" style="display: none;">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
                            <option value="">Choose a user...</option>
                            @if(isset($users))
                                @foreach($users as $userId => $user)
                                    <option value="{{ $user['firebase_uid'] ?? $userId }}" {{ old('user_id') == ($user['firebase_uid'] ?? $userId) ? 'selected' : '' }}>
                                        {{ $user['name'] ?? $user['email'] ?? 'Unknown User' }} 
                                        ({{ ucfirst($user['role'] ?? 'patient') }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Notification Type -->
                    <div class="mb-3">
                        <label for="type" class="form-label">Alert Type</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="admin" {{ old('type') == 'admin' ? 'selected' : '' }}>
                                <i class="fas fa-shield-alt"></i> Admin Alert (High Priority)
                            </option>
                            <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>
                                <i class="fas fa-info-circle"></i> General Information
                            </option>
                            <option value="appointment" {{ old('type') == 'appointment' ? 'selected' : '' }}>
                                <i class="fas fa-calendar"></i> Appointment Related
                            </option>
                            <option value="service" {{ old('type') == 'service' ? 'selected' : '' }}>
                                <i class="fas fa-stethoscope"></i> Service Update
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

                    <!-- Priority (Optional) -->
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority Level</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                        <div class="form-text">High and urgent alerts will be highlighted in the mobile app</div>
                    </div>

                    <!-- Send Button -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="sendButton">
                            <span id="sendButtonText">
                                <i class="fas fa-paper-plane me-2"></i>Send Alert to Mobile App
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
                            <i class="fas fa-bell"></i>
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

        <!-- Statistics Card -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Alert Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary">{{ $stats['total_notifications'] ?? 0 }}</h4>
                        <small>Total Sent</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success">{{ $stats['active_users'] ?? 0 }}</h4>
                        <small>Active Users</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Templates -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-templates me-2"></i>Quick Templates</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="useTemplate('maintenance')">
                        <i class="fas fa-tools me-1"></i> System Maintenance
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="useTemplate('update')">
                        <i class="fas fa-sync me-1"></i> App Update
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="useTemplate('reminder')">
                        <i class="fas fa-clock me-1"></i> Appointment Reminder
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="useTemplate('announcement')">
                        <i class="fas fa-bullhorn me-1"></i> General Announcement
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.mobile-preview {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    background: #667eea;
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
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}
</style>

<script>
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
        case 'admin':
            icon.className = 'fas fa-shield-alt';
            iconElement.style.background = '#dc3545';
            break;
        case 'appointment':
            icon.className = 'fas fa-calendar';
            iconElement.style.background = '#0d6efd';
            break;
        case 'service':
            icon.className = 'fas fa-stethoscope';
            iconElement.style.background = '#198754';
            break;
        default:
            icon.className = 'fas fa-bell';
            iconElement.style.background = '#667eea';
    }
});

// Toggle user selection visibility
function toggleUserSelection() {
    const recipientSelect = document.getElementById('recipient');
    const userSelectionDiv = document.getElementById('userSelectionDiv');
    const userSelect = document.getElementById('user_id');
    
    if (recipientSelect.value === 'individual') {
        userSelectionDiv.style.display = 'block';
        userSelect.required = true;
    } else {
        userSelectionDiv.style.display = 'none';
        userSelect.required = false;
        userSelect.value = '';
    }
}

// Template functionality
function useTemplate(type) {
    const titleInput = document.getElementById('title');
    const messageInput = document.getElementById('message');
    const typeSelect = document.getElementById('type');
    const recipientSelect = document.getElementById('recipient');
    
    switch(type) {
        case 'maintenance':
            titleInput.value = 'System Maintenance Notice';
            messageInput.value = 'HealthReach will undergo scheduled maintenance. Some features may be temporarily unavailable.';
            typeSelect.value = 'admin';
            recipientSelect.value = 'all';
            break;
        case 'update':
            titleInput.value = 'App Update Available';
            messageInput.value = 'A new version of HealthReach is available with improved features and bug fixes. Please update your app.';
            typeSelect.value = 'general';
            recipientSelect.value = 'all';
            break;
        case 'reminder':
            titleInput.value = 'Appointment Reminder';
            messageInput.value = 'Don\'t forget about your upcoming appointment. Please arrive 15 minutes early.';
            typeSelect.value = 'appointment';
            recipientSelect.value = 'patients';
            break;
        case 'announcement':
            titleInput.value = 'Important Announcement';
            messageInput.value = 'We have important updates to share with you. Please check the app for more details.';
            typeSelect.value = 'general';
            recipientSelect.value = 'all';
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
</script>
@endsection
