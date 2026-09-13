<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Store;
use WorkersPHP\KVNamespace;

// Laravel cache Store over the Worker's KV binding. KV is eventually
// consistent — fine for rate limiting and other soft caches, wrong for
// anything that must read its own writes immediately.
class WorkersKvStore implements Store
{
    private ?KVNamespace $client = null;

    // The binding name is kept rather than the client so resolving the
    // store never loads WorkersPHP classes outside the worker.
    public function __construct(
        private readonly string $binding,
        private readonly string $prefix = '',
    ) {}

    private function kv(): KVNamespace
    {
        return $this->client ??= new KVNamespace($this->binding);
    }

    public function get($key)
    {
        $raw = $this->kv()->get($this->prefix . $key, 'text');

        return $raw === null ? null : unserialize($raw);
    }

    public function many(array $keys)
    {
        $out = [];
        foreach ($keys as $key) $out[$key] = $this->get($key);

        return $out;
    }

    public function put($key, $value, $seconds)
    {
        $opts = [];
        if ($seconds > 0) $opts['expiration_ttl'] = max(60, $seconds); // KV minimum TTL is 60s

        $this->kv()->put($this->prefix . $key, serialize($value), $opts);

        return true;
    }

    public function putMany(array $values, $seconds)
    {
        $ok = true;
        foreach ($values as $key => $value) $ok = $this->put($key, $value, $seconds) && $ok;

        return $ok;
    }

    public function increment($key, $value = 1)
    {
        $current = (int) $this->get($key);
        $new = $current + $value;
        // TTL unknown here; RateLimiter re-puts with the window TTL on
        // its reset path, so a plain put matches Redis-store semantics
        // closely enough.
        $this->kv()->put($this->prefix . $key, serialize($new));

        return $new;
    }

    public function decrement($key, $value = 1)
    {
        $current = (int) $this->get($key);
        $new = $current - $value;
        $this->kv()->put($this->prefix . $key, serialize($new));

        return $new;
    }

    public function forever($key, $value)
    {
        $this->kv()->put($this->prefix . $key, serialize($value));

        return true;
    }

    public function touch($key, $seconds)
    {
        $value = $this->get($key);
        if ($value === null) return false;

        return $this->put($key, $value, $seconds);
    }

    public function forget($key)
    {
        $this->kv()->delete($this->prefix . $key);

        return true;
    }

    public function flush()
    {
        $cursor = null;
        do {
            $page = $this->kv()->list(array_filter(['prefix' => $this->prefix, 'cursor' => $cursor]));
            foreach ($page['keys'] as $entry) $this->kv()->delete($entry['name']);
            $cursor = $page['list_complete'] ? null : ($page['cursor'] ?? null);
        } while ($cursor);

        return true;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }
}
