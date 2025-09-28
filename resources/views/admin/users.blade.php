@extends('layouts.app')

@section('title', 'Users Management - HealthReach')
@section('page-title', 'Users Management')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-users me-2"></i>All Users</h5>
                <div>
                    <form method="GET" class="d-inline-flex">
                        <select name="role" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                            <option value="">All Roles</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="health_worker" {{ request('role') === 'health_worker' ? 'selected' : '' }}>Health Worker</option>
                            <option value="patient" {{ request('role') === 'patient' ? 'selected' : '' }}>Patient</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if(count($users) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Health Center</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $userId => $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
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
                                                    @if(isset($user['date_of_birth']))
                                                        <br><small class="text-muted">{{ date('M d, Y', strtotime($user['date_of_birth'])) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $user['email'] ?? 'N/A' }}</td>
                                        <td>{{ $user['contact_number'] ?? $user['phone'] ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $role = $user['role'] ?? 'patient';
                                                $badgeClass = $role === 'admin' ? 'danger' : ($role === 'health_worker' ? 'warning' : 'primary');
                                            @endphp
                                            <span class="badge bg-{{ $badgeClass }}">
                                                {{ ucfirst($role) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(isset($user['health_center_id']) && $user['health_center_id'])
                                                <small class="text-muted">{{ $user['health_center_id'] }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ isset($user['is_active']) && $user['is_active'] ? 'success' : 'secondary' }}">
                                                {{ isset($user['is_active']) && $user['is_active'] ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(isset($user['created_at']))
                                                {{ date('M d, Y', strtotime($user['created_at'])) }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#userModal{{ $userId }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if(($user['role'] ?? 'patient') !== 'admin')
                                                    <button class="btn btn-outline-danger" onclick="confirmDelete('{{ $userId }}', '{{ $user['name'] ?? $user['first_name'] ?? 'User' }} {{ isset($user['name']) ? '' : ($user['last_name'] ?? '') }}')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- User Details Modal -->
                                    <div class="modal fade" id="userModal{{ $userId }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">User Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Name:</strong><br>
                                                            @if(isset($user['name']))
                                                                {{ $user['name'] }}
                                                            @elseif(isset($user['first_name']))
                                                                {{ $user['first_name'] }} {{ $user['last_name'] ?? '' }}
                                                            @else
                                                                Unknown User
                                                            @endif
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Email:</strong><br>
                                                            {{ $user['email'] ?? 'N/A' }}
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Phone:</strong><br>
                                                            {{ $user['contact_number'] ?? $user['phone'] ?? 'N/A' }}
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Gender:</strong><br>
                                                            {{ ucfirst($user['gender'] ?? 'N/A') }}
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Date of Birth:</strong><br>
                                                            {{ isset($user['date_of_birth']) ? date('M d, Y', strtotime($user['date_of_birth'])) : 'N/A' }}
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Role:</strong><br>
                                                            @php
                                                                $modalRole = $user['role'] ?? 'patient';
                                                                $modalBadgeClass = $modalRole === 'admin' ? 'danger' : ($modalRole === 'health_worker' ? 'warning' : 'primary');
                                                            @endphp
                                                            <span class="badge bg-{{ $modalBadgeClass }}">
                                                                {{ ucfirst($modalRole) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    @if(isset($user['address']))
                                                        <hr>
                                                        <strong>Address:</strong><br>
                                                        {{ $user['address'] }}
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
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No users found</h5>
                        <p class="text-muted">Users will appear here once they register.</p>
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
                <p>Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function confirmDelete(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteForm').action = `/admin/users/${userId}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection
