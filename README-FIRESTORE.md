# HealthReach API - Firebase Firestore Backend

## Overview
The HealthReach Laravel backend has been completely converted from SQL database to Firebase Firestore, providing real-time data synchronization and seamless integration with the Expo mobile application.

## Architecture Changes

### Database Migration to Firestore
- **SQL Tables → Firestore Collections**
  - `users` → `users` collection
  - `health_centers` → `health_centers` collection  
  - `services` → `services` collection
  - `appointments` → `appointments` collection
  - `notifications` → `notifications` collection
  - `device_tokens` → `device_tokens` collection
  - `logs` → `logs` collection

### Authentication System
- **Firebase Authentication** for user management
- **Custom token generation** for seamless mobile app integration
- **Role-based access control** (patient, health_worker, admin)
- **Firestore user profiles** linked to Firebase Auth UIDs

## Key Components

### Controllers
- `FirebaseAuthController` - Firebase Auth + Firestore user management
- `FirestoreUserController` - User administration with Firestore
- All existing controllers updated to use Firestore operations

### Services
- `FirestoreService` - Core Firestore CRUD operations
- `FirestoreCollectionService` - Collection initialization and structure
- `FirebaseService` - Firebase Auth integration
- `ActivityLogService` - Audit logging in Firestore

### Middleware
- `FirebaseAuthMiddleware` - Firebase token verification with Firestore user lookup
- Role-based route protection

## Firestore Collections Structure

### Users Collection
```json
{
  "user_id": "user-uuid",
  "firebase_uid": "firebase-auth-uid",
  "name": "User Name",
  "email": "user@example.com",
  "role": "patient|health_worker|admin",
  "contact_number": "+1234567890",
  "address": "User Address",
  "fcm_token": "fcm-token",
  "email_verified_at": "2025-01-01T00:00:00Z",
  "is_active": true,
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

### Health Centers Collection
```json
{
  "health_center_id": "hc-uuid",
  "name": "Health Center Name",
  "location": "Address",
  "contact_number": "+1234567890",
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

### Services Collection
```json
{
  "service_id": "svc-uuid",
  "health_center_id": "hc-uuid",
  "service_name": "Service Name",
  "description": "Service Description",
  "duration_minutes": 30,
  "price": 50.00,
  "is_active": true,
  "schedule": {
    "monday": ["09:00", "17:00"],
    "tuesday": ["09:00", "17:00"]
  },
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

### Appointments Collection
```json
{
  "appointment_id": "apt-uuid",
  "user_id": "user-uuid",
  "health_center_id": "hc-uuid",
  "service_id": "svc-uuid",
  "appointment_date": "2025-01-01",
  "appointment_time": "10:00",
  "status": "pending|confirmed|cancelled|completed",
  "notes": "Appointment notes",
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

## Setup Instructions

### 1. Firebase Configuration
Ensure your `.env` file contains:
```env
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CLIENT_EMAIL=your-service-account-email
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
```

### 2. Initialize Firestore Collections
```bash
php artisan firestore:seed --all
```

### 3. Create Admin User
```bash
php artisan firestore:seed --admin
```
- Email: `admin@healthreach.com`
- Password: `admin1234`

### 4. Seed Sample Data
```bash
php artisan firestore:seed --health-centers --services
```

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new patient
- `POST /api/auth/login` - Login with Firebase Auth
- `POST /api/auth/logout` - Logout and revoke tokens
- `GET /api/auth/profile` - Get user profile
- `PUT /api/auth/profile` - Update user profile

### User Management (Admin)
- `GET /api/users` - Get all users
- `PUT /api/users/{id}/role` - Update user role

### Health Centers
- `GET /api/health-centers` - List health centers
- `POST /api/health-centers` - Create health center (Admin)
- `PUT /api/health-centers/{id}` - Update health center
- `DELETE /api/health-centers/{id}` - Delete health center (Admin)

### Services
- `GET /api/services` - List services
- `POST /api/services` - Create service (Health Worker/Admin)
- `PUT /api/services/{id}` - Update service
- `DELETE /api/services/{id}` - Delete service

### Appointments
- `GET /api/appointments` - List appointments
- `POST /api/appointments` - Book appointment
- `PUT /api/appointments/{id}` - Update appointment
- `PATCH /api/appointments/{id}/status` - Update status (Health Worker/Admin)

## Security Features

### Firebase Authentication
- ID token verification on all protected routes
- Custom claims for role-based access
- Token refresh and revocation

### Role-Based Access Control
- **Patient**: Book appointments, view own data
- **Health Worker**: Manage appointments, services
- **Admin**: Full system access, user management

### Data Security
- Firestore security rules (configure in Firebase Console)
- Input validation and sanitization
- Activity logging for audit trails

## Real-time Features

### Firestore Real-time Updates
- Automatic data synchronization
- Live appointment status updates
- Real-time notifications

### Firebase Cloud Messaging
- Push notifications for appointment updates
- Device token management
- Targeted messaging by user role

## Testing

### Test Firebase Integration
```bash
# Test user registration
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Test login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@healthreach.com","password":"admin1234"}'
```

## Benefits of Firestore Migration

1. **Real-time Synchronization** - Instant data updates across all clients
2. **Scalability** - Auto-scaling NoSQL database
3. **Offline Support** - Built-in offline capabilities
4. **Security** - Firebase security rules and authentication
5. **Mobile Integration** - Seamless Expo app integration
6. **Cost Efficiency** - Pay-per-use pricing model

## Migration Notes

- All existing API endpoints maintain the same interface
- Data structures preserved for frontend compatibility
- Enhanced with real-time capabilities
- Improved security with Firebase Auth
- Better mobile app integration

The backend is now fully Firebase-integrated and ready for production use with the HealthReach Expo mobile application.
