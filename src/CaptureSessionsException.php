<?php

declare(strict_types=1);

namespace Techulus\Capture;

class CaptureSessionsException extends \RuntimeException
{
    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly array $body,
    ) {
        $message = isset($body['error']) && is_string($body['error'])
            ? $body['error']
            : "Capture Sessions API request failed with status {$statusCode}";

        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }
}
