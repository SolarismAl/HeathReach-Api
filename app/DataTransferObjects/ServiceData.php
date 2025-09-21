<?php

namespace App\DataTransferObjects;

class ServiceData
{
    public function __construct(
        public string $service_id,
        public string $health_center_id,
        public string $service_name,
        public string $description,
        public ?int $duration_minutes = null,
        public ?float $price = null,
        public bool $is_active = true,
        public array $schedule = [], // Array of schedule objects
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}

    public function toArray(): array
    {
        return [
            'service_id' => $this->service_id,
            'health_center_id' => $this->health_center_id,
            'service_name' => $this->service_name,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'schedule' => $this->schedule,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            service_id: $data['service_id'],
            health_center_id: $data['health_center_id'],
            service_name: $data['service_name'],
            description: $data['description'],
            duration_minutes: $data['duration_minutes'] ?? null,
            price: $data['price'] ?? null,
            is_active: $data['is_active'] ?? true,
            schedule: $data['schedule'] ?? [],
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null
        );
    }
}

class CreateServiceData
{
    public function __construct(
        public string $health_center_id,
        public string $service_name,
        public string $description,
        public array $schedule = []
    ) {}

    public function toArray(): array
    {
        return [
            'health_center_id' => $this->health_center_id,
            'service_name' => $this->service_name,
            'description' => $this->description,
            'schedule' => $this->schedule,
        ];
    }
}
