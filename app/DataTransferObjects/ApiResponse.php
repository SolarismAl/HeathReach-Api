<?php

namespace App\DataTransferObjects;

class ApiResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public mixed $data = null,
        public ?array $errors = null,
        public ?int $status = null
    ) {}

    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        if ($this->status !== null) {
            $response['status'] = $this->status;
        }

        return $response;
    }

    public static function success(string $message, mixed $data = null): self
    {
        return new self(
            success: true,
            message: $message,
            data: $data
        );
    }

    public static function error(string $message, ?array $errors = null, int $status = 400): self
    {
        return new self(
            success: false,
            message: $message,
            errors: $errors,
            status: $status
        );
    }
}

class PaginatedResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public array $data,
        public int $current_page,
        public int $per_page,
        public int $total,
        public int $last_page,
        public ?string $next_page_url = null,
        public ?string $prev_page_url = null
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'pagination' => [
                'current_page' => $this->current_page,
                'per_page' => $this->per_page,
                'total' => $this->total,
                'last_page' => $this->last_page,
                'next_page_url' => $this->next_page_url,
                'prev_page_url' => $this->prev_page_url,
            ],
        ];
    }
}

class ApiError
{
    public function __construct(
        public string $message,
        public ?array $errors = null,
        public int $status = 400
    ) {}

    public function toArray(): array
    {
        $response = [
            'message' => $this->message,
            'status' => $this->status,
        ];

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }
}
