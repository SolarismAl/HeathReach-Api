<?php

namespace App\DataTransferObjects;

class ApiError
{
    public function __construct(
        public string $message,
        public ?string $code = null,
        public ?array $details = null
    ) {}

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
            'details' => $this->details,
        ];
    }
}
