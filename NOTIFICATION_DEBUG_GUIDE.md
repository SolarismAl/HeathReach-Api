# HealthReach Notification System - Debug Guide

## Issues Fixed

### 1. ✅ Blade Template Error (HTTP 500)
- **Problem**: `{{ ... }}` causing "Object of class Closure could not be converted to string"
- **Fix**: Removed problematic template syntax from `notifications.blade.php`

### 2. ✅ "Unknown User" in Admin Panel
- **Problem**: User dropdown showing "Unknown User" instead of actual names
- **Fix**: Enhanced user display with name, role, and partial ID for identification

### 3. ✅ Notification Not Showing in Mobile App
- **Problem**: User ID mismatch between web admin and mobile app
- **Fix**: Added comprehensive logging to track the entire notification flow

### 4. ✅ Individual User Selection
- **Problem**: Sending to all users instead of individual selection
- **Fix**: Added "Individual User" option with proper validation and user verification

## Testing Instructions

### Step 1: Check Laravel Logs
The system now has extensive logging. Check your Laravel logs at:
```
healthreach-api/storage/logs/laravel.log
```

### Step 2: Send a Test Notification

1. **Login to Admin Panel**
   - Go to: `http://your-domain/admin/login`
   - Login with admin credentials

2. **Navigate to Send Alerts**
   - Click "Send Alerts" in the sidebar

3. **Select Individual User**
   - Choose "Individual User" from the "Send To" dropdown
   - A user selection dropdown will appear
   - Select the specific patient you want to test with
   - Note the user ID shown (first 8 characters)

4. **Fill in Notification Details**
   - Title: "Test Notification"
   - Message: "This is a test notification"
   - Type: "General Information"
   - Priority: "Normal"

5. **Send Notification**
   - Click "Send Alert to Mobile App"
   - Should see: "Alert sent successfully to 1 user!"

### Step 3: Check Laravel Logs for Notification Creation

Look for these log entries:
```
=== ADMIN SEND NOTIFICATION ===
Sending notification to individual user: [user_id: xxx]
User verified: [user_data: ...]
Creating notification for user: [full details]
Notification creation result: [success: true]
```

### Step 4: Test Mobile App Notification Retrieval

1. **Open Mobile App**
   - Login with the SAME account you sent the notification to
   - Go to Notifications tab

2. **Check Laravel Logs for API Request**
   Look for:
   ```
   === NOTIFICATION INDEX REQUEST ===
   Full user object from middleware: [...]
   Extracted user_id: [user_id: xxx, role: patient]
   === FIRESTORE: getNotificationsByUser ===
   Querying notifications for user_id: [xxx]
   Processing notification document: [...]
   Notification query complete: [returned: 1]
   ```

### Step 5: Debug User ID Mismatch

If notifications still don't show, compare the user IDs:

1. **Get User ID from Admin Panel**
   - When selecting user in dropdown, note the ID shown
   - Example: `abcd1234...`

2. **Get User ID from Mobile App**
   - Use debug endpoint: `GET /api/test/debug-notifications/{userId}`
   - Replace `{userId}` with the ID from step 1

3. **Check Firebase Console**
   - Go to Firebase Console → Firestore Database
   - Check `users` collection
   - Find the user document
   - Note the document ID and `firebase_uid` field

4. **Check Notifications Collection**
   - Go to `notifications` collection
   - Find notifications with `user_id` matching your user
   - Verify the `user_id` field matches what the mobile app is using

## Debug Endpoints

### 1. Debug User Notifications
```
GET /api/test/debug-notifications/{userId}
```
Returns:
- User data
- User's notifications
- All notifications count
- Sample notifications

### 2. Create Test Notification
```
POST /api/test/create-test-notification/{userId}
```
Creates a test notification for the specified user.

### 3. Check All Notifications
```
GET /api/test/real-notifications
```
Returns all notifications in the system.

## Common Issues and Solutions

### Issue: "Unknown User" in Dropdown
**Cause**: User document missing `name` field
**Solution**: 
1. Check Firebase Console → users collection
2. Verify user has `name` field
3. If missing, update user profile in mobile app

### Issue: Notification Not Showing in Mobile App
**Cause**: User ID mismatch
**Solution**:
1. Check Laravel logs for both:
   - Notification creation: `user_id` used when creating
   - Notification retrieval: `user_id` used when querying
2. They must match exactly
3. Use debug endpoint to verify

### Issue: "Alert sent successfully to 8 patients!"
**Cause**: Selecting "Patients Only" instead of "Individual User"
**Solution**: 
1. Select "Individual User" from dropdown
2. Choose specific user from the list
3. Should show "Alert sent successfully to 1 user!"

## User ID Format

The system uses two possible user identifiers:
1. **firebase_uid**: The Firebase Authentication UID
2. **Document ID**: The Firestore document ID

**Priority**: The system prefers `firebase_uid` when available, falls back to document ID.

**For Notifications to Work**:
- Admin panel must use the same ID format as mobile app
- Check logs to see which ID is being used
- Both should use `firebase_uid` if available

## Sign Up Issue (Google Triggering on Regular Sign Up)

If Google Sign Up is triggering when clicking regular Sign Up:

**Check**:
1. The regular "Create Account" button should call `handleRegister()`
2. The "Sign up with Google" button should call `setShowGoogleModal(true)`
3. These are separate buttons with different `onPress` handlers

**Current Implementation**:
- Line 264-272: Regular Sign Up button → `onPress={handleRegister}`
- Line 280-289: Google Sign Up button → `onPress={() => setShowGoogleModal(true)}`

If issue persists, check for:
- Accidental form submission on Enter key
- Button overlap causing wrong button to be clicked
- JavaScript errors preventing proper button handling

## Next Steps

1. **Test with Debug Endpoints**: Use the debug endpoints to verify user IDs
2. **Check Laravel Logs**: Monitor logs during notification send and retrieval
3. **Verify Firebase Data**: Check Firestore console for actual data structure
4. **Compare IDs**: Ensure user IDs match between admin panel and mobile app

## Support

If issues persist after following this guide:
1. Share Laravel logs (last 100 lines)
2. Share Firebase Console screenshots (users and notifications collections)
3. Share mobile app console logs
4. Share the exact user ID being used in both systems
