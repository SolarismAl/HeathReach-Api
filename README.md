# HealthReach API

A comprehensive Laravel 10 REST API backend for the HealthReach mobile application with Firebase Authentication, Firestore, and Firebase Cloud Messaging (FCM) integration.

## Features

- **Firebase Authentication**: Complete user authentication with role-based access control
- **Firestore Integration**: Real-time database operations for all collections
- **Firebase Cloud Messaging**: Push notifications system
- **Role-Based Access**: Patient, Health Worker, and Admin roles with appropriate permissions
- **Activity Logging**: Comprehensive logging system for all user actions
- **Admin Dashboard**: Statistics and analytics endpoints
- **TypeScript Interface Matching**: All responses match Expo app TypeScript interfaces

## Installation

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Firebase Setup**
   - Create a Firebase project
   - Download the service account JSON file
   - Update `.env` with Firebase credentials:
   ```env
   FIREBASE_PROJECT_ID=your-firebase-project-id
   FIREBASE_PRIVATE_KEY_ID=your-private-key-id
   FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nyour-private-key\n-----END PRIVATE KEY-----\n"
   FIREBASE_CLIENT_EMAIL=firebase-adminsdk-xxxxx@your-project.iam.gserviceaccount.com
   FIREBASE_CLIENT_ID=your-client-id
   FCM_SERVER_KEY=your-fcm-server-key
   ```

4. **Start the Server**
   ```bash
   php artisan serve
   ```

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout

### Users
- `GET /api/users/{id}` - Get user profile
- `PUT /api/users/{id}` - Update user profile

### Health Centers
- `GET /api/health-centers` - List all health centers
- `POST /api/health-centers` - Create health center (admin only)

### Services
- `GET /api/services` - List all services
- `POST /api/services` - Create service (health worker/admin)

### Appointments
- `GET /api/appointments` - List appointments (role-based)
- `POST /api/appointments` - Create appointment
- `PUT /api/appointments/{id}` - Update appointment

### Notifications
- `GET /api/notifications` - List notifications
- `POST /api/notifications` - Send notification (health worker/admin)
- `PUT /api/notifications/{id}/read` - Mark as read

### Device Tokens
- `POST /api/device-tokens` - Save device token
- `GET /api/device-tokens` - List all tokens (admin only)

### Admin
- `GET /api/admin/stats` - Admin statistics (admin only)

### Logs
- `GET /api/logs` - Activity logs (admin only)
- `POST /api/logs` - Create log entry

## Authentication

All protected endpoints require a Firebase ID token in the Authorization header:
```
Authorization: Bearer <firebase-id-token>
```

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... },
  "status": 400
}
```

## Firestore Collections

- **users** - User profiles and authentication data
- **health_centers** - Health center information
- **services** - Available services
- **appointments** - Appointment bookings
- **notifications** - Push notifications history
- **device_tokens** - FCM device tokens
- **logs** - Activity logs

## Role-Based Access Control

- **Patient**: Can view/update own profile, create appointments, view own notifications
- **Health Worker**: Patient permissions + manage services, send notifications, view all appointments
- **Admin**: All permissions + manage health centers, view statistics, access logs

## Security Features

- Firebase ID token verification
- Role-based middleware protection
- Input validation and sanitization
- Comprehensive error handling
- Activity logging for audit trails

## Development

The API is built with:
- Laravel 10
- Firebase Admin SDK
- Google Cloud Firestore
- Firebase Cloud Messaging

All data structures match the TypeScript interfaces defined in the Expo mobile application for seamless integration.
