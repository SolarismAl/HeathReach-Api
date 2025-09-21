<?php

namespace App\DataTransferObjects;

class DeviceTokenData
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $token,
        public string $platform, // 'ios' | 'android' | 'web'
        public ?string $device_name = null,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'token' => $this->token,
            'platform' => $this->platform,
            'device_name' => $this->device_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            user_id: $data['user_id'],
            token: $data['token'],
            platform: $data['platform'],
            device_name: $data['device_name'] ?? null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null
        );
    }
}

class CreateDeviceTokenData
{
    public function __construct(
        public string $user_id,
        public string $token,
        public string $platform, // 'ios' | 'android' | 'web'
        public ?string $device_name = null
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'token' => $this->token,
            'platform' => $this->platform,
            'device_name' => $this->device_name,
        ];
    }
}
