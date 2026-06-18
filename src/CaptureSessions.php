<?php

declare(strict_types=1);

namespace Techulus\Capture;

class CaptureSessions
{
    private const EDGE_URL = 'https://edge.capture.page';

    public function __construct(
        private readonly string $key,
        private readonly string $secret,
        private readonly int $timeout,
    ) {
    }

    /**
     * @param array<string, mixed> $options Browser session options,
     *     for example ['maxTtlSeconds' => 300, 'cdp' => true].
     * @return array<string, mixed>
     */
    public function create(array $options = []): array
    {
        return $this->sessionsRequest('', 'POST', $options);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $sessionId): array
    {
        return $this->sessionsRequest('/' . $this->escapeSessionId($sessionId), 'GET');
    }

    /**
     * @return array<string, mixed>
     */
    public function close(string $sessionId): array
    {
        return $this->sessionsRequest('/' . $this->escapeSessionId($sessionId), 'DELETE');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function action(string $sessionId, string $type, array $payload = []): array
    {
        return $this->sessionsRequest(
            '/' . $this->escapeSessionId($sessionId) . '/actions',
            'POST',
            ['type' => $type, 'payload' => $payload],
        );
    }

    private function sessionsBearerToken(): string
    {
        return base64_encode($this->key . ':' . $this->secret);
    }

    private function sessionUrl(string $path = ''): string
    {
        return self::EDGE_URL . '/v1/sessions' . $path;
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function sessionsRequest(string $path, string $method, ?array $body = null): array
    {
        $headers = ['Authorization: Bearer ' . $this->sessionsBearerToken()];
        $payload = null;

        if ($body !== null) {
            $payload = json_encode($body, JSON_THROW_ON_ERROR);
            $headers[] = 'Content-Type: application/json';
        }

        $response = $this->httpRequest($this->sessionUrl($path), $method, $headers, $payload);
        $decoded = $response['body'] === ''
            ? []
            : json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            $decoded = [];
        }

        if ($response['statusCode'] < 200 || $response['statusCode'] >= 300) {
            throw new CaptureSessionsException($response['statusCode'], $decoded);
        }

        return $decoded;
    }

    /**
     * @param list<string> $headers
     * @return array{statusCode: int, body: string}
     */
    private function httpRequest(string $url, string $method, array $headers = [], ?string $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($response === false) {
            throw new \RuntimeException("HTTP request failed: {$error}");
        }

        return ['statusCode' => $httpCode, 'body' => $response];
    }

    private function escapeSessionId(string $sessionId): string
    {
        if ($sessionId === '') {
            throw new \InvalidArgumentException('sessionId is required');
        }

        return rawurlencode($sessionId);
    }
}
