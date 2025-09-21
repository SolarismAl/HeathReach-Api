<?php

// Simple script to check Laravel logs for Firebase authentication debugging
$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Laravel log file not found at: $logFile\n";
    exit(1);
}

echo "=== CHECKING LARAVEL LOGS FOR FIREBASE AUTH ISSUES ===\n\n";

// Get the last 100 lines of the log file
$lines = file($logFile);
$recentLines = array_slice($lines, -100);

$foundFirebaseDebug = false;

foreach ($recentLines as $line) {
    // Look for Firebase auth middleware debug entries
    if (strpos($line, 'FIREBASE AUTH MIDDLEWARE DEBUG') !== false ||
        strpos($line, 'Firebase token verification failed') !== false ||
        strpos($line, 'Token extracted') !== false ||
        strpos($line, 'Firebase UID') !== false ||
        strpos($line, 'Exception message') !== false) {
        
        echo $line;
        $foundFirebaseDebug = true;
    }
}

if (!$foundFirebaseDebug) {
    echo "No recent Firebase authentication debug entries found.\n";
    echo "Try logging in on mobile first, then run this script again.\n\n";
    
    echo "Last 10 log entries:\n";
    echo "==================\n";
    foreach (array_slice($recentLines, -10) as $line) {
        echo $line;
    }
}

echo "\n=== END OF LOG CHECK ===\n";
