<?php

namespace Postio\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

/**
 * The PSR-16 multiple-key methods, built on the single-key ones.
 */
abstract class AbstractCache implements CacheInterface
{
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $ok = true;
        foreach ($values as $key => $value) {
            $ok = $this->set($key, $value, $ttl) && $ok;
        }
        return $ok;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $ok = true;
        foreach ($keys as $key) {
            $ok = $this->delete($key) && $ok;
        }
        return $ok;
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * Seconds until expiry, 0 for no expiry, or null when the entry is already expired.
     */
    protected function seconds(null|int|DateInterval $ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTimeImmutable())->add($ttl)->getTimestamp() - time();
        }
        if ($ttl === null) {
            return 0;
        }
        return $ttl > 0 ? $ttl : null;
    }
}
