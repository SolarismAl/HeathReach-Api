<?php

namespace App\Services;

use App\DataTransferObjects\ActivityLogData;
use Illuminate\Support\Str;

class ActivityLogService
{
    private FirestoreService $firestoreService;

    public function __construct(FirestoreService $firestoreService)
    {
        $this->firestoreService = $firestoreService;
    }

    public function log(
        string $userId,
        string $action,
        string $description,
        ?string $ipAddress = null,
        $userAgentOrData = null
    ): void {
        // Handle both string (user agent) and array (additional data) for backward compatibility
        $userAgent = null;
        $additionalData = null;
        
        if (is_string($userAgentOrData)) {
            $userAgent = $userAgentOrData;
        } elseif (is_array($userAgentOrData)) {
            $additionalData = $userAgentOrData;
            $userAgent = 'Web Interface'; // Default for web requests
        }

        $logData = new ActivityLogData(
            id: Str::uuid()->toString(),
            user_id: $userId,
            action: $action,
            description: $description,
            ip_address: $ipAddress,
            user_agent: $userAgent,
            created_at: now()->toISOString()
        );

        $this->firestoreService->createActivityLog($logData);
    }
}
