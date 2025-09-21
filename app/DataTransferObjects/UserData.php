<?php

namespace App\DataTransferObjects;

class UserData
{
    public function __construct(
        public string $user_id,
        public string $name,
        public string $email,
        public string $role, // 'patient' | 'health_worker' | 'admin'
        public ?string $contact_number = null,
        public ?string $address = null,
        public ?string $fcm_token = null,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
            'fcm_token' => $this->fcm_token,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'],
            name: $data['name'],
            email: $data['email'],
            role: $data['role'],
            contact_number: $data['contact_number'] ?? null,
            address: $data['address'] ?? null,
            fcm_token: $data['fcm_token'] ?? null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null
        );
    }
}
