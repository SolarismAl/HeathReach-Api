<?php

namespace App\DataTransferObjects;

class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role, // 'patient' | 'health_worker' | 'admin'
        public ?string $contact_number = null,
        public ?string $address = null
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
        ];
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            role: $data['role'],
            contact_number: $data['contact_number'] ?? null,
            address: $data['address'] ?? null
        );
    }
}
