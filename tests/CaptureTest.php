<?php

declare(strict_types=1);

namespace Techulus\Capture\Tests;

use PHPUnit\Framework\TestCase;
use Techulus\Capture\Capture;
use Techulus\Capture\CaptureSessions;
use Techulus\Capture\CaptureSessionsException;

class CaptureTest extends TestCase
{
    public function testInitialization(): void
    {
        $client = new Capture('test_key', 'test_secret');
        $this->assertInstanceOf(Capture::class, $client);
    }

    public function testEmptyKeyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Key and Secret is required');
        new Capture('', 'secret');
    }

    public function testEmptySecretThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Key and Secret is required');
        new Capture('test', '');
    }

    public function testBuildImageUrl(): void
    {
        $client = new Capture('test', 'test');
        $url = $client->buildImageUrl('https://news.ycombinator.com/');
        $expected = 'https://cdn.capture.page/test/f37d5fb3ee4540a05bf4ffeed6dffa28/image?url=https%3A%2F%2Fnews.ycombinator.com%2F';
        $this->assertEquals($expected, $url);
    }

    public function testBuildImageUrlWithOptions(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildImageUrl('https://news.ycombinator.com/', ['full' => true, 'delay' => 2]);
        $this->assertStringContainsString('full=true', $url);
        $this->assertStringContainsString('delay=2', $url);
        $this->assertStringContainsString('url=https%3A%2F%2Fnews.ycombinator.com%2F', $url);
    }

    public function testBuildPdfUrl(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildPdfUrl('https://example.com');
        $this->assertStringContainsString('/pdf?', $url);
        $this->assertStringContainsString('url=https%3A%2F%2Fexample.com', $url);
    }

    public function testBuildContentUrl(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildContentUrl('https://example.com');
        $this->assertStringContainsString('/content?', $url);
        $this->assertStringContainsString('url=https%3A%2F%2Fexample.com', $url);
    }

    public function testBuildMetadataUrl(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildMetadataUrl('https://example.com');
        $this->assertStringContainsString('/metadata?', $url);
        $this->assertStringContainsString('url=https%3A%2F%2Fexample.com', $url);
    }

    public function testBuildAnimatedUrl(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildAnimatedUrl('https://example.com');
        $this->assertStringContainsString('/animated?', $url);
        $this->assertStringContainsString('url=https%3A%2F%2Fexample.com', $url);
    }

    public function testEdgeUrl(): void
    {
        $client = new Capture('test', 'secret', ['useEdge' => true]);
        $url = $client->buildImageUrl('https://example.com');
        $this->assertStringStartsWith('https://edge.capture.page', $url);
    }

    public function testCdnUrlByDefault(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildImageUrl('https://example.com');
        $this->assertStringStartsWith('https://cdn.capture.page', $url);
    }

    public function testEmptyUrlThrowsException(): void
    {
        $client = new Capture('test', 'secret');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('url is required');
        $client->buildImageUrl('');
    }

    public function testBooleanOptionEncoding(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildImageUrl('https://example.com', ['full' => true, 'lazy' => false]);
        $this->assertStringContainsString('full=true', $url);
        $this->assertStringContainsString('lazy=false', $url);
    }

    public function testNumericOptionEncoding(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildImageUrl('https://example.com', ['delay' => 5, 'quality' => 80]);
        $this->assertStringContainsString('delay=5', $url);
        $this->assertStringContainsString('quality=80', $url);
    }

    public function testNullValuesFiltered(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildImageUrl('https://example.com', ['none_value' => null, 'valid' => 'value']);
        $this->assertStringNotContainsString('none_value', $url);
        $this->assertStringContainsString('valid=value', $url);
    }

    public function testKeepsZeroAndFalseValues(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildImageUrl('https://example.com', [
            'delay' => 0,
            'full' => false,
            'darkMode' => false,
        ]);
        $this->assertStringContainsString('delay=0', $url);
        $this->assertStringContainsString('full=false', $url);
        $this->assertStringContainsString('darkMode=false', $url);
    }

    public function testTokenGeneration(): void
    {
        $client = new Capture('test', 'test');
        $url = $client->buildImageUrl('https://news.ycombinator.com/');
        $this->assertStringContainsString('/f37d5fb3ee4540a05bf4ffeed6dffa28/', $url);
    }

    public function testOptionsSortedForConsistentTokens(): void
    {
        $client = new Capture('test', 'secret');
        $url1 = $client->buildImageUrl('https://example.com', ['full' => true, 'delay' => 2]);
        $url2 = $client->buildImageUrl('https://example.com', ['delay' => 2, 'full' => true]);
        $this->assertEquals($url1, $url2);
    }

    public function testSpacesEncodedAsPlus(): void
    {
        $client = new Capture('test', 'secret');
        $url = $client->buildImageUrl('https://example.com', ['selector' => '.my class']);
        $this->assertStringContainsString('selector=.my+class', $url);
    }

    public function testCustomTimeout(): void
    {
        $client = new Capture('test', 'secret', ['timeout' => 120]);
        $this->assertInstanceOf(Capture::class, $client);
    }

    public function testNumericStringTimeoutIsAccepted(): void
    {
        $client = new Capture('test', 'secret', ['timeout' => '120']);
        $this->assertInstanceOf(Capture::class, $client);
    }

    public function testNegativeTimeoutThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "timeout" must be a non-negative integer.');
        new Capture('test', 'secret', ['timeout' => -1]);
    }

    public function testInvalidStringTimeoutThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "timeout" must be a non-negative integer.');
        new Capture('test', 'secret', ['timeout' => 'fast']);
    }

    public function testStringFalseDoesNotEnableEdge(): void
    {
        $client = new Capture('test', 'secret', ['useEdge' => 'false']);
        $url = $client->buildImageUrl('https://example.com');
        $this->assertStringStartsWith('https://cdn.capture.page', $url);
    }

    public function testStringTrueEnablesEdge(): void
    {
        $client = new Capture('test', 'secret', ['useEdge' => 'true']);
        $url = $client->buildImageUrl('https://example.com');
        $this->assertStringStartsWith('https://edge.capture.page', $url);
    }

    public function testInvalidUseEdgeValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "useEdge" must be a boolean value.');
        new Capture('test', 'secret', ['useEdge' => 'sometimes']);
    }

    public function testInvalidOptionValueThrowsException(): void
    {
        $client = new Capture('test', 'secret');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "devices" must be a scalar, stringable value, or null.');
        $client->buildImageUrl('https://example.com', ['devices' => ['desktop', 'mobile']]);
    }

    public function testSessionsBearerTokenUsesKeyAndSecret(): void
    {
        $client = new Capture('user_123', 'secret');
        $method = new \ReflectionMethod(CaptureSessions::class, 'sessionsBearerToken');

        $this->assertEquals('dXNlcl8xMjM6c2VjcmV0', $method->invoke($client->sessions()));
    }

    public function testSessionUrlUsesEdgeUrl(): void
    {
        $client = new Capture('user_123', 'secret');
        $method = new \ReflectionMethod(CaptureSessions::class, 'sessionUrl');

        $this->assertEquals(
            'https://edge.capture.page/v1/sessions/sess_123/actions',
            $method->invoke($client->sessions(), '/sess_123/actions'),
        );
    }

    public function testSessionIdEscaping(): void
    {
        $client = new Capture('user_123', 'secret');
        $method = new \ReflectionMethod(CaptureSessions::class, 'escapeSessionId');

        $this->assertEquals('sess_123%2Fchild', $method->invoke($client->sessions(), 'sess_123/child'));
    }

    public function testCaptureSessionsExceptionUsesApiErrorMessage(): void
    {
        $exception = new CaptureSessionsException(404, [
            'success' => false,
            'error' => 'Session not found',
        ]);

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals(['success' => false, 'error' => 'Session not found'], $exception->getBody());
        $this->assertEquals('Session not found', $exception->getMessage());
    }

    public function testLiveSessionScreenshotExampleDotCom(): void
    {
        $key = getenv('CAPTURE_API_KEY') ?: '';
        $secret = getenv('CAPTURE_API_SECRET') ?: '';

        if (getenv('CAPTURE_LIVE_SESSIONS') !== '1' || $key === '' || $secret === '') {
            $this->markTestSkipped(
                'Live sessions test requires CAPTURE_LIVE_SESSIONS=1, CAPTURE_API_KEY, and CAPTURE_API_SECRET',
            );
        }

        $client = new Capture($key, $secret);
        $sessionId = null;

        try {
            $created = $client->sessions()->create(['maxTtlSeconds' => 300]);
            $sessionId = $created['session']['id'];

            $client->sessions()->action($sessionId, 'goto', ['url' => 'https://example.com']);
            $screenshot = $client->sessions()->action($sessionId, 'screenshot', ['fullPage' => true]);

            $result = $screenshot['result'] ?? $screenshot;
            if (isset($result['screenshot']) && is_array($result['screenshot'])) {
                $result = $result['screenshot'];
            }

            $contentType = $result['contentType'] ?? $result['mimeType'] ?? null;
            $bodyBase64 = $result['bodyBase64'] ?? $result['base64'] ?? null;

            $this->assertSame('image/png', $contentType);
            $this->assertIsString($bodyBase64);
            $this->assertNotSame('', $bodyBase64);
        } finally {
            if (is_string($sessionId) && $sessionId !== '') {
                $client->sessions()->close($sessionId);
            }
        }
    }
}
