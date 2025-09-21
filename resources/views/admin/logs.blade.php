@extends('layouts.app')

@section('title', 'Activity Logs - HealthReach')
@section('page-title', 'Activity Logs')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list-alt me-2"></i>Recent Activity Logs</h5>
                <small class="text-muted">Latest 100 activities</small>
            </div>
            <div class="card-body">
                @if(count($logs) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Timestamp</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $logId => $log)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @php
                                                    $action = $log['action'] ?? 'unknown';
                                                    $actionBadgeClass = $action === 'login' ? 'success' : ($action === 'logout' ? 'secondary' : 'primary');
                                                @endphp
                                                <div class="bg-{{ $actionBadgeClass }} rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                                    <i class="fas fa-user text-white" style="font-size: 12px;"></i>
                                                </div>
                                                <div>
                                                    @if(isset($log['user_name']))
                                                        <strong>{{ $log['user_name'] }}</strong>
                                                    @elseif(isset($log['user_id']))
                                                        <small class="text-muted">ID: {{ Str::limit($log['user_id'], 8) }}</small>
                                                    @else
                                                        <span class="text-muted">Unknown User</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $tableAction = $log['action'] ?? 'unknown';
                                                $tableActionBadgeClass = $tableAction === 'login' ? 'success' : 
                                                    ($tableAction === 'logout' ? 'secondary' : 
                                                    ($tableAction === 'created' || str_contains($tableAction, 'created') ? 'primary' : 
                                                    ($tableAction === 'updated' || str_contains($tableAction, 'updated') ? 'info' : 
                                                    ($tableAction === 'deleted' || str_contains($tableAction, 'deleted') ? 'danger' : 'warning'))));
                                            @endphp
                                            <span class="badge bg-{{ $tableActionBadgeClass }}">
                                                {{ ucfirst(str_replace('_', ' ', $tableAction)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-dark">{{ $log['description'] ?? 'No description' }}</span>
                                        </td>
                                        <td>
                                            @if(isset($log['timestamp']))
                                                <strong>{{ date('M d, Y', strtotime($log['timestamp'])) }}</strong>
                                                <br><small class="text-muted">{{ date('h:i:s A', strtotime($log['timestamp'])) }}</small>
                                            @elseif(isset($log['created_at']))
                                                <strong>{{ date('M d, Y', strtotime($log['created_at'])) }}</strong>
                                                <br><small class="text-muted">{{ date('h:i:s A', strtotime($log['created_at'])) }}</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#logModal{{ $logId }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Log Details Modal -->
                                    <div class="modal fade" id="logModal{{ $logId }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Activity Log Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>User Information:</strong><br>
                                                            @if(isset($log['user_name']))
                                                                {{ $log['user_name'] }}
                                                            @else
                                                                <span class="text-muted">Unknown User</span>
                                                            @endif
                                                            @if(isset($log['user_id']))
                                                                <br><small class="text-muted">ID: {{ $log['user_id'] }}</small>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Action:</strong><br>
                                                            @php
                                                                $modalAction = $log['action'] ?? 'unknown';
                                                                $modalActionBadgeClass = $modalAction === 'login' ? 'success' : 
                                                                    ($modalAction === 'logout' ? 'secondary' : 
                                                                    ($modalAction === 'created' || str_contains($modalAction, 'created') ? 'primary' : 
                                                                    ($modalAction === 'updated' || str_contains($modalAction, 'updated') ? 'info' : 
                                                                    ($modalAction === 'deleted' || str_contains($modalAction, 'deleted') ? 'danger' : 'warning'))));
                                                            @endphp
                                                            <span class="badge bg-{{ $modalActionBadgeClass }}">
                                                                {{ ucfirst(str_replace('_', ' ', $modalAction)) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <strong>Description:</strong><br>
                                                            {{ $log['description'] ?? 'No description available' }}
                                                        </div>
                                                    </div>
                                                    @if(isset($log['metadata']) && is_array($log['metadata']))
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <strong>Additional Details:</strong><br>
                                                                <pre class="bg-light p-2 rounded"><code>{{ json_encode($log['metadata'], JSON_PRETTY_PRINT) }}</code></pre>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Timestamp:</strong><br>
                                                            @if(isset($log['timestamp']))
                                                                {{ date('M d, Y h:i:s A', strtotime($log['timestamp'])) }}
                                                            @elseif(isset($log['created_at']))
                                                                {{ date('M d, Y h:i:s A', strtotime($log['created_at'])) }}
                                                            @else
                                                                <span class="text-muted">N/A</span>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Log ID:</strong><br>
                                                            <small class="text-muted font-monospace">{{ $logId }}</small>
                                                        </div>
                                                    </div>
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
                        <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No activity logs found</h5>
                        <p class="text-muted">Activity logs will appear here as users interact with the system.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Activity Summary Cards -->
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="card stats-card-success">
            <div class="card-body text-center">
                <i class="fas fa-sign-in-alt fa-2x mb-2"></i>
                <h3>{{ collect($logs)->where('action', 'login')->count() }}</h3>
                <p class="mb-0">Logins</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card-info">
            <div class="card-body text-center">
                <i class="fas fa-plus fa-2x mb-2"></i>
                <h3>{{ collect($logs)->filter(function($log) { return str_contains($log['action'] ?? '', 'created'); })->count() }}</h3>
                <p class="mb-0">Created</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card-warning">
            <div class="card-body text-center">
                <i class="fas fa-edit fa-2x mb-2"></i>
                <h3>{{ collect($logs)->filter(function($log) { return str_contains($log['action'] ?? '', 'updated'); })->count() }}</h3>
                <p class="mb-0">Updated</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <i class="fas fa-trash fa-2x mb-2"></i>
                <h3>{{ collect($logs)->filter(function($log) { return str_contains($log['action'] ?? '', 'deleted'); })->count() }}</h3>
                <p class="mb-0">Deleted</p>
            </div>
        </div>
    </div>
</div>
@endsection
