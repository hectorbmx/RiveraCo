<?php

namespace App\Exceptions\Telephony;

use RuntimeException;

class GrandstreamApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly array $context = []
    ) {
        parent::__construct($message);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    public function context(): array
    {
        return $this->context;
    }
}