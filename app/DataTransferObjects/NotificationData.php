<?php

namespace App\DataTransferObjects;

class NotificationData
{
    public function __construct(
        public string $notification_id,
        public string $user_id,
        public string $title,
        public string $message,
        public string $date_sent,
        public bool $is_read,
        public string $type, // 'appointment' | 'service' | 'admin' | 'general'
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}

    public function toArray(): array
    {
        return [
            'notification_id' => $this->notification_id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'message' => $this->message,
            'date_sent' => $this->date_sent,
            'is_read' => $this->is_read,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            notification_id: $data['notification_id'],
            user_id: $data['user_id'],
            title: $data['title'],
            message: $data['message'],
            date_sent: $data['date_sent'],
            is_read: $data['is_read'],
            type: $data['type'],
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null
        );
    }
}

class CreateNotificationData
{
    public function __construct(
        public string $user_id,
        public string $title,
        public string $message,
        public string $type, // 'appointment' | 'service' | 'admin' | 'general'
        public ?array $data = null
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
