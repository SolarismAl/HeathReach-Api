# Notification System - FINAL FIX

## Issues Identified from Logs

### Issue 1: "Patient not found"
```
Selected patient_id from form: {"patient_id":"xfswazEvfPZLHp7GnIARkBRwwUJ3"}
Patient not found: {"patient_id":"xfswazEvfPZLHp7GnIARkBRwwUJ3"}
```

**Root Cause**: 
- The patient dropdown sends `firebase_uid` as the ID
- But `getUser()` method was only looking for document IDs
- User's actual document ID is `user-0cc17e70-2816-430f-8b93-b3ac30ac7f8f`
- User's firebase_uid is `xfswazEvfPZLHp7GnIARkBRwwUJ3`

**Fix Applied**:
Enhanced `FirestoreService::getUser()` to search by BOTH:
1. First tries to find by document ID
2. If not found, searches by `firebase_uid` field
3. Returns the user data regardless of which method found it

### Issue 2: "Unknown User" in Dropdown
The patient dropdown was showing "Unknown User" because:
- User data has a `name` field (e.g., "Sample 22")
- But the code was looking for `first_name` and `last_name` fields

**Fix Applied**:
Enhanced patient list API to:
1. Check for `name` field first
2. Split full name into first/last if needed
3. Fall back to `first_name`/`last_name` if they exist
4. Display proper names in dropdown

## Technical Changes Made

### 1. FirestoreService.php - Enhanced getUser() Method

**Before**:
```php
public function getUser(string $userId): array
{
    $docRef = $this->firestore->collection('users')->document($userId);
    $document = $docRef->snapshot();
    
    if ($document->exists()) {
        return ['success' => true, 'data' => $userData];
    }
    
    return ['success' => false, 'error' => 'User not found'];
}
```

**After**:
```php
public function getUser(string $userId): array
{
    // First, try to find by document ID
    $docRef = $this->firestore->collection('users')->document($userId);
    $document = $docRef->snapshot();
    
    if ($document->exists()) {
        return ['success' => true, 'data' => $userData];
    }
    
    // If not found, try to find by firebase_uid
    $query = $collection->where('firebase_uid', '=', $userId);
    $documents = $query->documents();
    
    foreach ($documents as $doc) {
        if ($doc->exists()) {
            return ['success' => true, 'data' => $userData];
        }
    }
    
    return ['success' => false, 'error' => 'User not found'];
}
```

### 2. WebHealthWorkerController.php - Enhanced getPatients() Method

**Before**:
```php
$patients[] = [
    'id' => $patientId,
    'first_name' => $userData['first_name'] ?? 'Unknown',
    'last_name' => $userData['last_name'] ?? 'User',
    'email' => $userData['email'] ?? 'No email',
];
```

**After**:
```php
// Handle both 'name' field and 'first_name'/'last_name' fields
$fullName = $userData['name'] ?? '';
$firstName = $userData['first_name'] ?? '';
$lastName = $userData['last_name'] ?? '';

// If we have a full name but no first/last, split it
if ($fullName && !$firstName && !$lastName) {
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0] ?? 'Unknown';
    $lastName = $nameParts[1] ?? '';
}

$patients[] = [
    'id' => $patientId,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'full_name' => $fullName ?: "$firstName $lastName",
    'email' => $userData['email'] ?? 'No email',
];
```

### 3. notifications.blade.php - Enhanced Patient Display

**Before**:
```javascript
option.textContent = `${patient.first_name} ${patient.last_name} (${patient.email})`;
```

**After**:
```javascript
const displayName = patient.full_name || `${patient.first_name} ${patient.last_name}`.trim();
option.textContent = `${displayName} (${patient.email})`;
```

## How It Works Now

### Data Flow:

1. **Patient Dropdown Loads**:
   ```
   GET /health-worker/api/patients
   → Returns: [{ id: "xfswazEvfPZLHp7GnIARkBRwwUJ3", full_name: "Sample 22", email: "sample22@gmail.com" }]
   → Displays: "Sample 22 (sample22@gmail.com)"
   ```

2. **Health Worker Selects Patient**:
   ```
   Form submits: patient_id = "xfswazEvfPZLHp7GnIARkBRwwUJ3"
   ```

3. **Backend Verifies Patient**:
   ```
   getUser("xfswazEvfPZLHp7GnIARkBRwwUJ3")
   → Tries document ID: NOT FOUND
   → Searches firebase_uid: FOUND!
   → Returns: { user_id: "user-0cc17e70-2816-430f-8b93-b3ac30ac7f8f", firebase_uid: "xfswazEvfPZLHp7GnIARkBRwwUJ3", name: "Sample 22" }
   ```

4. **Notification Created**:
   ```
   {
     "notification_id": "unique-uuid",
     "user_id": "xfswazEvfPZLHp7GnIARkBRwwUJ3",
     "title": "Appointment Reminder",
     "message": "...",
     "sender_id": "A2d9FVuJ2Tcc9dGZ6kjcV8ELnpT2"
   }
   ```

5. **Mobile App Retrieves**:
   ```
   User logs in with firebase_uid: "xfswazEvfPZLHp7GnIARkBRwwUJ3"
   → Queries notifications where user_id = "xfswazEvfPZLHp7GnIARkBRwwUJ3"
   → Finds the notification!
   ```

## Testing Steps

### Step 1: Clear Old Data
1. Delete the 8 duplicate notifications from Firebase Console

### Step 2: Test Patient Dropdown
1. Login as Health Worker
2. Go to Send Alerts
3. Select "Specific Patient"
4. Check dropdown - should show "Sample 22 (sample22@gmail.com)" NOT "Unknown User"

### Step 3: Send Notification
1. Select "Sample 22" from dropdown
2. Fill in title and message
3. Click Send
4. Should see: "Alert sent successfully to 1 patients!"

### Step 4: Check Logs
Visit: `https://healthreach-api.onrender.com/api/test/view-logs`

Look for:
```
=== SPECIFIC PATIENT SELECTED ===
Selected patient_id from form: {"patient_id":"xfswazEvfPZLHp7GnIARkBRwwUJ3"}
FirestoreService::getUser called with: {"userId":"xfswazEvfPZLHp7GnIARkBRwwUJ3"}
User not found by document ID, searching by firebase_uid
User found by firebase_uid: {"doc_id":"user-0cc17e70-2816-430f-8b93-b3ac30ac7f8f"}
Patient verified: {"patient_name":"Sample 22"}
Recipients array after adding specific patient: {"count":1}
```

### Step 5: Check Firebase
1. Go to Firebase Console → Firestore
2. Check `notifications` collection
3. Should see ONLY 1 new notification
4. Check `user_id` field = "xfswazEvfPZLHp7GnIARkBRwwUJ3"

### Step 6: Check Mobile App
1. Login to mobile app as Sample 22
2. Go to Notifications tab
3. Should see the notification!

## Why This Fix Works

### User ID Consistency:
The system now uses `firebase_uid` consistently throughout:

1. **Patient Dropdown**: Returns `firebase_uid` as `id`
2. **Notification Creation**: Uses `firebase_uid` as `user_id`
3. **Mobile App**: Queries by `firebase_uid`
4. **Backend Lookup**: Can find users by either document ID or `firebase_uid`

### Name Display:
The system now handles different name formats:
- Single `name` field (e.g., "Sample 22")
- Separate `first_name` and `last_name` fields
- Automatically splits full names when needed

## Expected Behavior

✅ Patient dropdown shows real names (not "Unknown User")
✅ Selecting patient doesn't show "Patient not found" error
✅ Only 1 notification created (not 8)
✅ Notification appears in mobile app for that patient
✅ Other patients don't see the notification
✅ Success message shows "1 patients"

## If Issues Persist

1. **Check the logs** at `/api/test/view-logs`
2. **Look for**: "User found by firebase_uid" message
3. **If still "Patient not found"**: Share the full log output
4. **Check Firebase Console**: Verify user has `firebase_uid` field set
