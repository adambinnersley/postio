<?php

namespace Postio\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * Wraps another cache so an outage (Redis down, disk full) costs a billed lookup
 * instead of failing the address search: reads miss, writes are dropped.
 */
class FailSafeCache extends AbstractCache
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            return $this->cache->get($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        try {
            return $this->cache->set($key, $value, $ttl);
        } catch (Throwable) {
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            return $this->cache->delete($key);
        } catch (Throwable) {
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            return $this->cache->clear();
        } catch (Throwable) {
            return false;
        }
    }
}
