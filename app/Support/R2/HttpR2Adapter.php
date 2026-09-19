<?php

namespace App\Support\R2;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;

// Flysystem v3 adapter over an R2 HTTP endpoint (a Cloudflare worker with
// the bucket binding). R2 is flat: directories exist only as key prefixes.
class HttpR2Adapter implements FilesystemAdapter
{
    public function __construct(private readonly string $endpoint) {}

    // Laravel's FilesystemAdapter::url() looks for getUrl on custom
    // adapters; objects are served by the worker under /storage.
    public function getUrl(string $path): string
    {
        return '/storage/' . $path;
    }

    public function fileExists(string $path): bool
    {
        try {
            [$status] = $this->request('HEAD', $this->keyPath($path));

            return $status === 200;
        } catch (\Throwable $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        return $this->list(rtrim($path, '/') . '/', 1)['objects'] !== [];
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $mime = $config->get('mimetype') ?? match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => null,
        };
        $this->request('PUT', $this->keyPath($path), $contents, $mime ? ['Content-Type: ' . $mime] : []);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
        if (is_resource($contents)) fclose($contents);
    }

    public function read(string $path): string
    {
        [$status, , $body] = $this->request('GET', $this->keyPath($path));
        if ($status === 404) throw UnableToReadFile::fromLocation($path, 'not found');

        return $body;
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        $this->request('DELETE', $this->keyPath($path));
    }

    public function deleteDirectory(string $path): void
    {
        $cursor = null;
        do {
            $page = $this->list(rtrim($path, '/') . '/', 1000, $cursor);
            $keys = array_column($page['objects'], 'key');
            if ($keys) {
                $this->request('DELETE', '/', json_encode(['keys' => $keys]), ['Content-Type: application/json']);
            }
            $cursor = $page['truncated'] ? $page['cursor'] : null;
        } while ($cursor);
    }

    public function createDirectory(string $path, Config $config): void
    {
        // R2 has no directories; prefixes appear as objects are written.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // Bucket-wide policy; per-object visibility does not exist.
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, 'public');
    }

    public function mimeType(string $path): FileAttributes
    {
        $headers = $this->head($path, 'mimeType');

        return new FileAttributes($path, null, null, null, $headers['content-type'] ?? null);
    }

    public function lastModified(string $path): FileAttributes
    {
        $headers = $this->head($path, 'lastModified');

        return new FileAttributes($path, null, null, isset($headers['last-modified']) ? strtotime($headers['last-modified']) : null);
    }

    public function fileSize(string $path): FileAttributes
    {
        $headers = $this->head($path, 'fileSize');

        return new FileAttributes($path, isset($headers['content-length']) ? (int) $headers['content-length'] : null);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = $path === '' ? '' : rtrim($path, '/') . '/';
        $cursor = null;
        do {
            $page = $this->list($prefix, 1000, $cursor);
            foreach ($page['objects'] as $obj) {
                if (!$deep && str_contains(substr($obj['key'], strlen($prefix)), '/')) {
                    yield new DirectoryAttributes($prefix . strstr(substr($obj['key'], strlen($prefix)), '/', true));
                    continue;
                }
                yield new FileAttributes($obj['key'], $obj['size']);
            }
            $cursor = $page['truncated'] ? $page['cursor'] : null;
        } while ($cursor);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->write($destination, $this->read($source), $config);
    }

    private function head(string $path, string $metadata): array
    {
        [$status, $headers] = $this->request('HEAD', $this->keyPath($path));
        if ($status === 404) throw UnableToRetrieveMetadata::$metadata($path, 'not found');

        return $headers;
    }

    private function list(string $prefix, int $limit, ?string $cursor = null): array
    {
        $query = ['list' => 1, 'prefix' => $prefix, 'limit' => $limit];
        if ($cursor) $query['cursor'] = $cursor;
        [, , $body] = $this->request('GET', '/?' . http_build_query($query));

        return json_decode($body, true);
    }

    private function keyPath(string $path): string
    {
        return '/' . str_replace('%2F', '/', rawurlencode(ltrim($path, '/')));
    }

    /** @return array{0: int, 1: array<string, string>, 2: string} */
    private function request(string $method, string $path, ?string $body = null, array $headers = []): array
    {
        $ch = curl_init($this->endpoint . $path);
        $responseHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => $method === 'HEAD',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$responseHeaders) {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($k))] = trim($v);
                }

                return strlen($line);
            },
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false) throw new \RuntimeException("R2 endpoint unreachable: $method $path");
        if ($status >= 400 && $status !== 404) {
            throw new \RuntimeException("R2 endpoint error (HTTP $status): " . substr((string) $raw, 0, 500));
        }

        return [$status, $responseHeaders, (string) $raw];
    }
}
