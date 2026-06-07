# Capture PHP SDK

Official PHP SDK for [Capture](https://capture.page) - Screenshot and content extraction API.

## Installation

```bash
composer require techulus/capture
```

## Quick Start

```php
use Techulus\Capture\Capture;

$client = new Capture('your-api-key', 'your-api-secret');

$imageUrl = $client->buildImageUrl('https://example.com');
echo $imageUrl;
```

## Features

- **Screenshot Capture**: Capture full-page or viewport screenshots as PNG/JPG
- **PDF Generation**: Convert web pages to PDF documents
- **Content Extraction**: Extract HTML and text content from web pages
- **Metadata Extraction**: Get page metadata (title, description, og tags, etc.)
- **Animated GIFs**: Create animated GIFs of page interactions
- **Browser Sessions**: Create stateful browser sessions and run actions
- **Zero Dependencies**: Uses only PHP built-in extensions (curl, json)

## Usage

### Initialize the Client

```php
use Techulus\Capture\Capture;

$client = new Capture('your-api-key', 'your-api-secret');

// Use edge endpoint for faster response times
$client = new Capture('your-api-key', 'your-api-secret', ['useEdge' => true]);
```

### Building URLs

#### Image Capture

```php
$imageUrl = $client->buildImageUrl('https://example.com');

$imageUrl = $client->buildImageUrl('https://example.com', [
    'full' => true,
    'delay' => 2,
    'vw' => 1920,
    'vh' => 1080,
]);
```

#### PDF Capture

```php
$pdfUrl = $client->buildPdfUrl('https://example.com');

$pdfUrl = $client->buildPdfUrl('https://example.com', [
    'format' => 'A4',
    'landscape' => true,
]);
```

#### Content Extraction

```php
$contentUrl = $client->buildContentUrl('https://example.com');
```

#### Metadata Extraction

```php
$metadataUrl = $client->buildMetadataUrl('https://example.com');
```

#### Animated GIF

```php
$animatedUrl = $client->buildAnimatedUrl('https://example.com');
```

### Fetching Data

#### Fetch Image

```php
$imageData = $client->fetchImage('https://example.com');
file_put_contents('screenshot.png', $imageData);
```

#### Fetch PDF

```php
$pdfData = $client->fetchPdf('https://example.com', ['full' => true]);
file_put_contents('page.pdf', $pdfData);
```

#### Fetch Content

```php
$content = $client->fetchContent('https://example.com');
echo $content['html'];
echo $content['textContent'];
echo $content['markdown'];
```

#### Fetch Metadata

```php
$metadata = $client->fetchMetadata('https://example.com');
print_r($metadata['metadata']);
```

#### Fetch Animated GIF

```php
$gifData = $client->fetchAnimated('https://example.com');
file_put_contents('animation.gif', $gifData);
```

### Browser Sessions

```php
$session = $client->createSession(['maxTtlSeconds' => 300]);
$sessionId = $session['session']['id'];

$client->executeAction($sessionId, 'goto', ['url' => 'https://example.com']);
$screenshot = $client->executeAction($sessionId, 'screenshot', ['fullPage' => true]);

$client->closeSession($sessionId);
```

## Configuration Options

### Constructor Options

- `useEdge` (bool): Use edge.capture.page instead of cdn.capture.page for faster response times
- `timeout` (int): cURL timeout in seconds. Defaults to `30`

## API Endpoints

The SDK supports two base URLs:

- **CDN**: `https://cdn.capture.page` (default)
- **Edge**: `https://edge.capture.page` (when `useEdge` is `true`)

## License

MIT

## Links

- [Website](https://capture.page)
- [Documentation](https://docs.capture.page)
- [GitHub](https://github.com/techulus/capture-php)

## Support

For support, please visit [capture.page](https://capture.page) or open an issue on GitHub.
