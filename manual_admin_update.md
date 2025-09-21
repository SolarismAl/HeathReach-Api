# Manual Admin Role Update

## Current Situation:
- User ID: `user-2551cef9-3323-43db-b3e8-a73317cf00ea`
- Email: `admin@healthreach.com`
- Current Role: `patient`
- Target Role: `admin`

## Option 1: Firebase Console (Recommended)
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project: `healthreach-9167b`
3. Go to **Firestore Database**
4. Navigate to collection: `users`
5. Find document: `user-2551cef9-3323-43db-b3e8-a73317cf00ea`
6. Edit the document
7. Change `role` field from `patient` to `admin`
8. Save changes

## Option 2: Laravel Tinker Command
Run this in your Laravel project:

```bash
cd c:\Users\USER\HealthReach\healthreach-api
php artisan tinker
```

Then execute:
```php
use Google\Cloud\Firestore\FirestoreClient;
$firestore = new FirestoreClient(['projectId' => 'healthreach-9167b']);
$userRef = $firestore->collection('users')->document('user-2551cef9-3323-43db-b3e8-a73317cf00ea');
$userRef->update([['path' => 'role', 'value' => 'admin']]);
echo "Role updated to admin!";
exit;
```

## Option 3: Create New Admin User
If the above doesn't work, register a new user with:
- Email: `superadmin@healthreach.com`
- Password: `admin123456`
- Then manually update that user's role

## After Role Update:
1. **Logout** from the current session
2. **Clear app cache** (use the "Clear Cache (Debug)" button)
3. **Login again** with admin credentials
4. Should redirect to admin dashboard

## Verification:
The login logs should show:
```
AuthContext: User role: admin
LandingPage - Redirecting to admin dashboard
```
