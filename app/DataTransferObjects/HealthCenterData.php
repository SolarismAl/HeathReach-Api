<?php

namespace App\DataTransferObjects;

class HealthCenterData
{
    public function __construct(
        public string $health_center_id,
        public string $name,
        public string $address,
        public ?string $contact_number = null,
        public ?string $email = null,
        public ?string $description = null,
        public bool $is_active = true,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}

    public function toArray(): array
    {
        return [
            'health_center_id' => $this->health_center_id,
            'name' => $this->name,
            'address' => $this->address,
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            health_center_id: $data['health_center_id'],
            name: $data['name'],
            address: $data['address'],
            contact_number: $data['contact_number'] ?? null,
            email: $data['email'] ?? null,
            description: $data['description'] ?? null,
            is_active: $data['is_active'] ?? true,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null
        );
    }
}
