# HealthReach Web Management Interface

A comprehensive web-based management interface for HealthReach administrators and healthcare workers to manage the system through a modern, responsive dashboard.

## Features

### 🔐 Authentication
- **Firebase Integration**: Secure authentication using existing Firebase backend
- **Role-based Access**: Separate interfaces for Admin and Health Worker roles
- **Session Management**: Secure session handling with automatic logout

### 👨‍💼 Admin Dashboard
- **User Management**: View, filter, and manage all system users
- **Health Centers**: Full CRUD operations for health centers
- **Statistics**: Real-time dashboard with appointment metrics and charts
- **Activity Logs**: Comprehensive audit trail of all system activities
- **Appointments Overview**: Monitor all appointments across the system

### 👩‍⚕️ Health Worker Dashboard
- **Appointment Management**: View, filter, and update appointment statuses
- **Service Management**: Create, edit, and manage healthcare services
- **Statistics**: Personal metrics and performance tracking
- **Real-time Updates**: Live appointment status management

## Technology Stack

- **Backend**: Laravel 10 with Firebase/Firestore integration
- **Frontend**: Bootstrap 5 with modern CSS styling
- **Charts**: Chart.js for data visualization
- **Icons**: Font Awesome 6
- **Authentication**: Firebase Auth with custom session management

## Installation & Setup

### Prerequisites
- Laravel 10 application with Firebase integration
- Firebase project with Firestore database
- Admin and Health Worker users in the system

### 1. Routes Configuration
The web routes are automatically configured in `routes/web.php`:
- `/login` - Authentication page
- `/admin/*` - Admin dashboard routes
- `/health-worker/*` - Health worker dashboard routes

### 2. Middleware Setup
Web authentication middleware is registered in `app/Http/Kernel.php`:
```php
'web.auth' => \App\Http\Middleware\WebAuthMiddleware::class,
```

### 3. Controllers
- `WebAuthController` - Handles authentication
- `WebAdminController` - Admin dashboard functionality
- `WebHealthWorkerController` - Health worker dashboard functionality

## Usage

### Admin Access
1. Navigate to `/login`
2. Login with admin credentials
3. Access admin dashboard with:
   - User management
   - Health center management
   - System statistics
   - Activity logs
   - All appointments overview

### Health Worker Access
1. Navigate to `/login`
2. Login with health worker credentials
3. Access health worker dashboard with:
   - Personal appointment management
   - Service creation and management
   - Performance statistics

## Features Overview

### Admin Features
- **Dashboard**: Overview statistics with charts
- **Users**: View all users with role filtering
- **Health Centers**: Complete CRUD with location mapping
- **Appointments**: System-wide appointment monitoring
- **Logs**: Comprehensive activity audit trail

### Health Worker Features
- **Dashboard**: Personal statistics and quick actions
- **Appointments**: Manage appointments with status updates
- **Services**: Create and manage healthcare services
- **Live Preview**: Real-time service preview while editing

## Security Features

- **Firebase Authentication**: Secure user verification
- **Role-based Access Control**: Separate admin/health worker permissions
- **Session Management**: Secure session handling
- **CSRF Protection**: Laravel CSRF token validation
- **Activity Logging**: All actions are logged for audit

## UI/UX Features

- **Responsive Design**: Mobile-friendly interface
- **Modern Styling**: Clean, professional appearance
- **Interactive Charts**: Data visualization with Chart.js
- **Live Updates**: Real-time form previews
- **Modal Dialogs**: User-friendly confirmation dialogs
- **Toast Notifications**: Success/error feedback

## File Structure

```
resources/views/
├── layouts/
│   └── app.blade.php              # Main layout template
├── auth/
│   └── login.blade.php            # Login page
├── admin/
│   ├── dashboard.blade.php        # Admin dashboard
│   ├── users.blade.php           # User management
│   ├── health-centers.blade.php   # Health center list
│   ├── create-health-center.blade.php
│   ├── edit-health-center.blade.php
│   ├── appointments.blade.php     # All appointments
│   └── logs.blade.php            # Activity logs
└── health-worker/
    ├── dashboard.blade.php        # Health worker dashboard
    ├── appointments.blade.php     # Appointment management
    ├── services.blade.php         # Service list
    ├── create-service.blade.php   # Create service
    └── edit-service.blade.php     # Edit service
```

## API Integration

The web interface integrates seamlessly with the existing Laravel API:
- Uses Firebase authentication
- Connects to Firestore database
- Maintains data consistency with mobile app
- Supports real-time updates

## Customization

### Styling
- Bootstrap 5 classes for responsive design
- Custom CSS variables for theming
- Font Awesome icons throughout
- Chart.js for data visualization

### Functionality
- Easily extendable controller methods
- Modular view components
- Reusable modal templates
- Configurable statistics and charts

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers (responsive design)

## Development Notes

- All views use Blade templating
- JavaScript functionality is vanilla JS with Bootstrap
- Charts use Chart.js library
- Forms include CSRF protection
- Error handling with user-friendly messages

## Troubleshooting

### Common Issues
1. **Login Issues**: Verify Firebase configuration and user roles
2. **Permission Denied**: Check user role assignments in Firestore
3. **Data Not Loading**: Verify Firestore service configuration
4. **Session Issues**: Clear browser cache and cookies

### Debug Mode
Enable Laravel debug mode to see detailed error messages:
```
APP_DEBUG=true
```

## Contributing

When adding new features:
1. Follow Laravel conventions
2. Use Bootstrap classes for styling
3. Include proper error handling
4. Add activity logging where appropriate
5. Test with both admin and health worker roles

## License

This web management interface is part of the HealthReach project and follows the same licensing terms.
