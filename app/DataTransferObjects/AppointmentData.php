<?php

namespace App\DataTransferObjects;

class AppointmentData
{
    public function __construct(
        public string $appointment_id,
        public string $user_id,
        public string $health_center_id,
        public string $date,
        public string $time,
        public string $status, // 'pending' | 'confirmed' | 'cancelled' | 'completed'
        public ?string $remarks = null,
        public ?UserData $user = null,
        public ?HealthCenterData $health_center = null,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}

    public function toArray(): array
    {
        return [
            'appointment_id' => $this->appointment_id,
            'user_id' => $this->user_id,
            'health_center_id' => $this->health_center_id,
            'date' => $this->date,
            'time' => $this->time,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'user' => $this->user?->toArray(),
            'health_center' => $this->health_center?->toArray(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            appointment_id: $data['appointment_id'],
            user_id: $data['user_id'],
            health_center_id: $data['health_center_id'],
            date: $data['date'],
            time: $data['time'],
            status: $data['status'],
            remarks: $data['remarks'] ?? null,
            user: isset($data['user']) ? UserData::fromArray($data['user']) : null,
            health_center: isset($data['health_center']) ? HealthCenterData::fromArray($data['health_center']) : null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null
        );
    }
}

class CreateAppointmentData
{
    public function __construct(
        public string $user_id,
        public string $health_center_id,
        public string $date,
        public string $time,
        public ?string $remarks = null
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'health_center_id' => $this->health_center_id,
            'date' => $this->date,
            'time' => $this->time,
            'remarks' => $this->remarks,
        ];
    }
}
