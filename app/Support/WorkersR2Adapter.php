<?php

namespace App\Support;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use WorkersPHP\R2Bucket;

// Flysystem v3 adapter over the Worker's R2 bucket binding. R2 is flat:
// directories exist only as key prefixes.
class WorkersR2Adapter implements FilesystemAdapter
{
    public function __construct(private readonly R2Bucket $bucket) {}

    // Laravel's FilesystemAdapter::url() looks for getUrl on custom
    // adapters; objects are served by the worker under /storage.
    public function getUrl(string $path): string
    {
        return '/storage/' . $path;
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->bucket->head($path) !== null;
        } catch (\Throwable $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        return $this->bucket->list(['prefix' => rtrim($path, '/') . '/', 'limit' => 1])['objects'] !== [];
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
        $this->bucket->put($path, $contents, $mime ? ['contentType' => $mime] : []);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
        if (is_resource($contents)) fclose($contents);
    }

    public function read(string $path): string
    {
        $obj = $this->bucket->get($path);
        if ($obj === null) throw UnableToReadFile::fromLocation($path, 'not found');

        return $obj->body();
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
        $this->bucket->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $cursor = null;
        do {
            $opts = ['prefix' => rtrim($path, '/') . '/', 'limit' => 1000];
            if ($cursor) $opts['cursor'] = $cursor;
            $page = $this->bucket->list($opts);
            $keys = array_column($page['objects'], 'key');
            if ($keys) $this->bucket->delete($keys);
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
        $obj = $this->bucket->head($path);
        if ($obj === null) throw UnableToRetrieveMetadata::mimeType($path, 'not found');

        return new FileAttributes($path, null, null, null, $obj->contentType);
    }

    public function lastModified(string $path): FileAttributes
    {
        $obj = $this->bucket->head($path);
        if ($obj === null) throw UnableToRetrieveMetadata::lastModified($path, 'not found');

        return new FileAttributes($path, null, null, $obj->uploaded ? strtotime($obj->uploaded) : null);
    }

    public function fileSize(string $path): FileAttributes
    {
        $obj = $this->bucket->head($path);
        if ($obj === null) throw UnableToRetrieveMetadata::fileSize($path, 'not found');

        return new FileAttributes($path, $obj->size);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = $path === '' ? '' : rtrim($path, '/') . '/';
        $cursor = null;
        do {
            $opts = ['prefix' => $prefix, 'limit' => 1000];
            if ($cursor) $opts['cursor'] = $cursor;
            $page = $this->bucket->list($opts);
            foreach ($page['objects'] as $obj) {
                $key = is_array($obj) ? $obj['key'] : $obj->key;
                $size = is_array($obj) ? $obj['size'] : $obj->size;
                if (!$deep && str_contains(substr($key, strlen($prefix)), '/')) {
                    yield new DirectoryAttributes($prefix . strstr(substr($key, strlen($prefix)), '/', true));
                    continue;
                }
                yield new FileAttributes($key, $size);
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
        $this->bucket->put($destination, $this->read($source));
    }
}
