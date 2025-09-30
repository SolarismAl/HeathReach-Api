# Render Environment Variables Checklist

## Required Environment Variables for Render

Go to your Render Dashboard → healthreach-api → Environment

Make sure these are set:

### Laravel Configuration
```
APP_NAME=HealthReach
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://healthreach-api.onrender.com
```

### Database (if using)
```
DB_CONNECTION=sqlite
```

### Firebase Configuration
```
FIREBASE_CREDENTIALS={"type":"service_account","project_id":"healthreach-9167b",...}
```

### Session & Cache
```
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

## How to Get Your APP_KEY

Run this locally:
```bash
cd healthreach-api
php artisan key:generate --show
```

Copy the output and add it to Render environment variables.

## How to Add Environment Variables in Render

1. Go to: https://dashboard.render.com
2. Select your `healthreach-api` service
3. Click "Environment" in the left sidebar
4. Click "Add Environment Variable"
5. Add each variable
6. Click "Save Changes"
7. Service will automatically redeploy

## Firebase Credentials Format

Your FIREBASE_CREDENTIALS should be a single-line JSON string:
```
{"type":"service_account","project_id":"healthreach-9167b","private_key_id":"...","private_key":"-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n","client_email":"...","client_id":"...","auth_uri":"...","token_uri":"...","auth_provider_x509_cert_url":"...","client_x509_cert_url":"..."}
```

Get this from your Firebase Console → Project Settings → Service Accounts → Generate New Private Key
