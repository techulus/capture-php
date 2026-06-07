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

class Capture
{
    private const API_URL = 'https://cdn.capture.page';
    private const EDGE_URL = 'https://edge.capture.page';

    private bool $useEdge;
    private int $timeout;

    public function __construct(
        private readonly string $key,
        private readonly string $secret,
        private readonly array $options = [],
    ) {
        if ($key === '' || $secret === '') {
            throw new \InvalidArgumentException('Key and Secret is required');
        }

        $this->useEdge = $this->normalizeBooleanOption($options['useEdge'] ?? false, 'useEdge');
        $this->timeout = $this->normalizeTimeout($options['timeout'] ?? 30);
    }

    public function buildImageUrl(string $url, array $options = []): string
    {
        return $this->buildUrl($url, 'image', $options);
    }

    public function buildPdfUrl(string $url, array $options = []): string
    {
        return $this->buildUrl($url, 'pdf', $options);
    }

    public function buildContentUrl(string $url, array $options = []): string
    {
        return $this->buildUrl($url, 'content', $options);
    }

    public function buildMetadataUrl(string $url, array $options = []): string
    {
        return $this->buildUrl($url, 'metadata', $options);
    }

    public function buildAnimatedUrl(string $url, array $options = []): string
    {
        return $this->buildUrl($url, 'animated', $options);
    }

    public function fetchImage(string $url, array $options = []): string
    {
        return $this->httpGet($this->buildImageUrl($url, $options));
    }

    public function fetchPdf(string $url, array $options = []): string
    {
        return $this->httpGet($this->buildPdfUrl($url, $options));
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchContent(string $url, array $options = []): array
    {
        $response = $this->httpGet($this->buildContentUrl($url, $options));
        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchMetadata(string $url, array $options = []): array
    {
        $response = $this->httpGet($this->buildMetadataUrl($url, $options));
        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    public function fetchAnimated(string $url, array $options = []): string
    {
        return $this->httpGet($this->buildAnimatedUrl($url, $options));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function createSession(array $options = []): array
    {
        return $this->sessionsRequest('', 'POST', $options);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSession(string $sessionId): array
    {
        return $this->sessionsRequest('/' . $this->escapeSessionId($sessionId), 'GET');
    }

    /**
     * @return array<string, mixed>
     */
    public function closeSession(string $sessionId): array
    {
        return $this->sessionsRequest('/' . $this->escapeSessionId($sessionId), 'DELETE');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function executeAction(string $sessionId, string $type, array $payload = []): array
    {
        return $this->sessionsRequest(
            '/' . $this->escapeSessionId($sessionId) . '/actions',
            'POST',
            ['type' => $type, 'payload' => $payload],
        );
    }

    private function buildUrl(string $url, string $requestType, array $options): string
    {
        if ($url === '') {
            throw new \InvalidArgumentException('url is required');
        }

        ksort($options);
        $params = array_merge($options, ['url' => $url]);
        $queryString = $this->encodeQueryString($params);
        $token = $this->generateToken($this->secret, $queryString);
        $baseUrl = $this->useEdge ? self::EDGE_URL : self::API_URL;

        return "{$baseUrl}/{$this->key}/{$token}/{$requestType}?{$queryString}";
    }

    private function generateToken(string $secret, string $queryString): string
    {
        return md5($secret . $queryString);
    }

    private function normalizeBooleanOption(mixed $value, string $name): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            if ($value === 0 || $value === 1) {
                return (bool) $value;
            }

            throw new \InvalidArgumentException(sprintf('Option "%s" must be a boolean value.', $name));
        }

        if (is_string($value)) {
            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        throw new \InvalidArgumentException(sprintf('Option "%s" must be a boolean value.', $name));
    }

    private function normalizeTimeout(mixed $value): int
    {
        if (is_int($value)) {
            if ($value >= 0) {
                return $value;
            }

            throw new \InvalidArgumentException('Option "timeout" must be a non-negative integer.');
        }

        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new \InvalidArgumentException('Option "timeout" must be a non-negative integer.');
    }

    private function encodeQueryString(array $params): string
    {
        $filtered = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $filtered[$key] = $value ? 'true' : 'false';
                continue;
            }

            if (is_scalar($value)) {
                $filtered[$key] = (string) $value;
                continue;
            }

            if ($value instanceof \Stringable) {
                $filtered[$key] = (string) $value;
                continue;
            }

            throw new \InvalidArgumentException(sprintf(
                'Option "%s" must be a scalar, stringable value, or null.',
                (string) $key,
            ));
        }

        return http_build_query($filtered, '', '&', PHP_QUERY_RFC1738);
    }

    private function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("HTTP request failed: {$error}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("HTTP Error: {$httpCode}");
        }

        return $response;
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
        curl_close($ch);

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
