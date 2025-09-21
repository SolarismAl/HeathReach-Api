<?php

namespace App\DataTransferObjects;

class ActivityLogData
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $action,
        public string $description,
        public ?string $ip_address = null,
        public ?string $user_agent = null,
        public string $created_at
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'description' => $this->description,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            user_id: $data['user_id'],
            action: $data['action'],
            description: $data['description'],
            ip_address: $data['ip_address'] ?? null,
            user_agent: $data['user_agent'] ?? null,
            created_at: $data['created_at']
        );
    }
}

class AdminStatsData
{
    public function __construct(
        public int $total_users,
        public int $total_appointments,
        public int $pending_appointments,
        public int $completed_appointments,
        public int $total_health_centers,
        public int $total_services,
        public array $recent_activities = []
    ) {}

    public function toArray(): array
    {
        return [
            'total_users' => $this->total_users,
            'total_appointments' => $this->total_appointments,
            'pending_appointments' => $this->pending_appointments,
            'completed_appointments' => $this->completed_appointments,
            'total_health_centers' => $this->total_health_centers,
            'total_services' => $this->total_services,
            'recent_activities' => $this->recent_activities,
        ];
    }
}
