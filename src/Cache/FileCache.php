<?php

namespace Postio\Cache;

use DateInterval;

/**
 * PSR-16 cache in a local directory, for a single server or development.
 *
 * Entries are written to a temporary file and renamed into place, so a reader on
 * another worker never sees a half-written entry.
 */
class FileCache extends AbstractCache
{
    public function __construct(private string $directory)
    {
        $this->directory = rtrim($directory, '/\\');
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $raw = @file_get_contents($this->path($key));
        if ($raw === false) {
            return $default;
        }
        $entry = unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($entry) || ($entry['expires'] && $entry['expires'] < time())) {
            return $default;
        }
        return $entry['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $seconds = $this->seconds($ttl);
        if ($seconds === null) {
            return $this->delete($key);
        }
        $tmp = $this->path($key) . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, serialize(['expires' => $seconds ? time() + $seconds : 0, 'value' => $value])) === false) {
            return false;
        }
        if (!@rename($tmp, $this->path($key))) {
            @unlink($tmp);
            return false;
        }
        return true;
    }

    public function delete(string $key): bool
    {
        @unlink($this->path($key));
        return true;
    }

    public function clear(): bool
    {
        foreach (glob($this->directory . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }
        return true;
    }

    private function path(string $key): string
    {
        return $this->directory . '/' . sha1($key) . '.cache';
    }
}
