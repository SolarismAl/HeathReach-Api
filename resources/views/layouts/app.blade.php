<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HealthReach Management')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 12px;
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
        }
        .stats-card-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }
        .stats-card-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stats-card-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .navbar-brand {
            font-weight: bold;
            color: #667eea !important;
        }

        /* Mobile Sidebar Styles */
        .sidebar-mobile {
            position: fixed;
            top: 0;
            left: -100%;
            width: 280px;
            height: 100vh;
            z-index: 1050;
            transition: left 0.3s ease;
        }
        
        .sidebar-mobile.show {
            left: 0;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
        
        .sidebar-overlay.show {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100%;
            }
            
            .mobile-menu-btn {
                display: block !important;
            }
        }
        
        @media (min-width: 992px) {
            .sidebar {
                position: relative;
                left: 0;
            }
            
            .mobile-menu-btn {
                display: none !important;
            }
            
            .sidebar-overlay {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem !important;
            }
            
            .navbar {
                padding: 0.5rem 1rem;
                margin-bottom: 1rem !important;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
                text-align: center;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 0.75rem !important;
            }
            
            .navbar {
                padding: 0.5rem;
                flex-direction: column;
                align-items: stretch;
            }
            
            .navbar-brand {
                text-align: center;
                margin-bottom: 0.5rem;
            }
            
            .card-header {
                padding: 0.75rem;
                font-size: 0.95rem;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .table thead th {
                padding: 0.5rem;
                font-size: 0.75rem;
            }
            
            .table tbody td {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .btn-group .btn {
                width: auto;
                margin-bottom: 0;
            }
            
            .modal-dialog {
                margin: 0.5rem;
            }
            
            .stats-card {
                padding: 1rem;
            }
        }

        /* Utility Classes */
        .hide-mobile {
            @media (max-width: 576px) {
                display: none !important;
            }
        }
        
        .show-mobile {
            display: none !important;
        }
        
        @media (max-width: 576px) {
            .show-mobile {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <div class="text-center mb-4">
                    <h4><i class="fas fa-heartbeat me-2"></i>HealthReach</h4>
                    <small class="text-light">Management Portal</small>
                </div>
                
                @if(session('user'))
                    <div class="text-center mb-4">
                        <div class="bg-white bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                        <div class="mt-2">
                            <strong>{{ session('user')['first_name'] ?? 'User' }} {{ session('user')['last_name'] ?? '' }}</strong>
                            <br>
                            <small class="text-light">{{ ucfirst(session('user')['role'] ?? 'user') }}</small>
                        </div>
                    </div>
                @endif

                <nav class="nav flex-column">
                    @if(session('user')['role'] === 'admin')
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                            <i class="fas fa-users me-2"></i> Users
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.health-centers*') ? 'active' : '' }}" href="{{ route('admin.health-centers') }}">
                            <i class="fas fa-hospital me-2"></i> Health Centers
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.appointments*') ? 'active' : '' }}" href="{{ route('admin.appointments') }}">
                            <i class="fas fa-calendar-check me-2"></i> Appointments
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.notifications*') ? 'active' : '' }}" href="{{ route('admin.notifications') }}">
                            <i class="fas fa-bell me-2"></i> Send Alerts
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.logs*') ? 'active' : '' }}" href="{{ route('admin.logs') }}">
                            <i class="fas fa-list-alt me-2"></i> Activity Logs
                        </a>
                    @elseif(session('user')['role'] === 'health_worker')
                        <a class="nav-link {{ request()->routeIs('health-worker.dashboard') ? 'active' : '' }}" href="{{ route('health-worker.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('health-worker.appointments*') ? 'active' : '' }}" href="{{ route('health-worker.appointments') }}">
                            <i class="fas fa-calendar-check me-2"></i> Appointments
                        </a>
                        <a class="nav-link {{ request()->routeIs('health-worker.health-centers*') ? 'active' : '' }}" href="{{ route('health-worker.health-centers') }}">
                            <i class="fas fa-hospital me-2"></i> Health Centers
                        </a>
                        <a class="nav-link {{ request()->routeIs('health-worker.services*') ? 'active' : '' }}" href="{{ route('health-worker.services') }}">
                            <i class="fas fa-stethoscope me-2"></i> Services
                        </a>
                        <a class="nav-link {{ request()->routeIs('health-worker.notifications*') ? 'active' : '' }}" href="{{ route('health-worker.notifications') }}">
                            <i class="fas fa-bell me-2"></i> Send Alerts
                        </a>
                    @endif
                    
                    <hr class="my-3">
                    <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content p-4">
                <!-- Top Navigation -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white rounded mb-4">
                    <div class="container-fluid d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-link mobile-menu-btn me-2 d-lg-none" 
                                    onclick="toggleSidebar()" 
                                    style="color: #667eea;">
                                <i class="fas fa-bars"></i>
                            </button>
                            <span class="navbar-brand mb-0">@yield('page-title', 'Dashboard')</span>
                        </div>
                        <div class="navbar-nav d-flex align-items-center">
                            <span class="nav-text">
                                <i class="fas fa-clock me-1"></i>
                                {{ now()->format('M d, Y - h:i A') }}
                            </span>
                        </div>
                    </div>
                </nav>

                <!-- Alerts -->
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

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Page Content -->
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Mobile Sidebar JavaScript -->
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }

        function closeSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }

        // Close sidebar when clicking on nav links (mobile)
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        closeSidebar();
                    }
                });
            });

            // Close sidebar on window resize if desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    closeSidebar();
                }
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html>
