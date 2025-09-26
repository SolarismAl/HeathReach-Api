<?php

$logFile = 'storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found: $logFile\n";
    exit(1);
}

echo "=== Laravel Log Viewer ===\n";
echo "Showing last 100 lines of Laravel logs\n";
echo "Look for sections with === HEALTH CENTERS INDEX === and === SERVICES INDEX ===\n\n";

// Get last 100 lines of the log file
$lines = file($logFile);
$totalLines = count($lines);
$startLine = max(0, $totalLines - 100);

echo "Showing lines " . ($startLine + 1) . " to $totalLines of $totalLines total lines\n";
echo str_repeat("=", 80) . "\n";

for ($i = $startLine; $i < $totalLines; $i++) {
    echo $lines[$i];
}

echo str_repeat("=", 80) . "\n";
echo "End of log file\n\n";

echo "To see real-time logs, run: tail -f storage/logs/laravel.log\n";
echo "Or in PowerShell: Get-Content storage/logs/laravel.log -Wait -Tail 10\n";
