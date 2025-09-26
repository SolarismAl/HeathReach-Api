<?php

require_once 'vendor/autoload.php';

// Test reading the service account file
echo "Testing Firebase service account configuration...\n\n";

$serviceAccountPath = 'firebase-service-account.json';

if (!file_exists($serviceAccountPath)) {
    echo "❌ Service account file not found: $serviceAccountPath\n";
    exit(1);
}

echo "✅ Service account file exists\n";

$serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

if (!$serviceAccount) {
    echo "❌ Failed to parse service account JSON\n";
    exit(1);
}

echo "✅ Service account JSON parsed successfully\n";

// Check required fields
$requiredFields = [
    'type',
    'project_id', 
    'private_key_id',
    'private_key',
    'client_email',
    'client_id',
    'auth_uri',
    'token_uri'
];

foreach ($requiredFields as $field) {
    if (!isset($serviceAccount[$field]) || empty($serviceAccount[$field])) {
        echo "❌ Missing or empty field: $field\n";
    } else {
        $value = $field === 'private_key' ? '[REDACTED]' : $serviceAccount[$field];
        echo "✅ $field: $value\n";
    }
}

echo "\n";

// Test Firebase initialization
try {
    echo "Testing Firebase SDK initialization...\n";
    
    $factory = (new \Kreait\Firebase\Factory)
        ->withServiceAccount($serviceAccount);
        
    $auth = $factory->createAuth();
    echo "✅ Firebase Auth initialized successfully\n";
    
    $firestore = $factory->createFirestore();
    echo "✅ Firebase Firestore initialized successfully\n";
    
    echo "\n🎉 Firebase configuration is working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Firebase initialization failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
