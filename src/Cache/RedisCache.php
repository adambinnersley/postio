<?php

namespace Postio\Cache;

use DateInterval;
use Redis;

/**
 * PSR-16 cache on a phpredis connection, so every web server shares one copy of
 * each postcode lookup.
 */
class RedisCache extends AbstractCache
{
    public function __construct(private Redis $redis, private string $prefix = 'postio:')
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($this->prefix . $key);
        return $value === false ? $default : unserialize($value, ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $seconds = $this->seconds($ttl);
        if ($seconds === null) {
            return $this->delete($key);
        }
        $value = serialize($value);
        return (bool)($seconds > 0
            ? $this->redis->setex($this->prefix . $key, $seconds, $value)
            : $this->redis->set($this->prefix . $key, $value));
    }

    public function delete(string $key): bool
    {
        $this->redis->del($this->prefix . $key);
        return true;
    }

    public function clear(): bool
    {
        $iterator = null;
        do {
            $keys = $this->redis->scan($iterator, $this->prefix . '*', 500);
            if ($keys) {
                $this->redis->del($keys);
            }
        } while ($iterator > 0);
        return true;
    }
}
