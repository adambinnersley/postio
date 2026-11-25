<?php

namespace Postio;

use Postio\Exception\PostioException;
use Psr\SimpleCache\CacheInterface;

/**
 * Postcode-to-address lookup on Postio, with the same methods and public properties
 * as `adamb/afd` so a form built on AFD only has to change how it builds the object.
 *
 * Postio bills every postcode or UDPRN lookup that finds something, so a postcode's
 * full results are cached and picking an address from that list is answered from the
 * cache: one charge per postcode searched. The address key handed to the form carries
 * the postcode for that reason; without a cache entry it falls back to a billed UDPRN
 * lookup.
 */
class Postio
{
    public const DEFAULT_TTL = 2592000;

    /** @var array<string, mixed> */
    public array $addressInfo = [];
    public string $address1 = '';
    public string $address2 = '';
    public string $address3 = '';
    public string $town = '';
    public string $county = '';
    public string $postcode = '';
    public ?float $latitude = null;
    public ?float $longitude = null;

    private Client $client;
    private ?PostioException $lastError = null;
    private int $maxLines = 3;

    /**
     * @param string|Client $client Postio API key, or a configured Client
     * @param CacheInterface|null $cache Shared cache for postcode results; strongly recommended
     * @param int $ttl Seconds a postcode's results are kept
     */
    public function __construct(string|Client $client, private ?CacheInterface $cache = null, private int $ttl = self::DEFAULT_TTL)
    {
        $this->client = is_string($client) ? new Client($client) : $client;
    }

    /**
     * Number of address lines the form has room for; longer addresses are folded to fit.
     */
    public function setMaxLines(int $lines): static
    {
        $this->maxLines = max(1, $lines);
        return $this;
    }

    /**
     * Addresses on a postcode as `['address' => label, 'key' => key]`, in street order.
     *
     * @return array<int, array{address: string, key: string}>|false False when the postcode
     *         has no addresses or the lookup failed; `getLastError()` tells the two apart
     */
    public function findAddresses(string $postcode): array|false
    {
        $this->lastError = null;
        try {
            $addresses = $this->lookup($postcode);
        } catch (PostioException $e) {
            $this->lastError = $e;
            return false;
        }
        if (!$addresses) {
            return false;
        }
        $compact = str_replace(' ', '', self::formatPostcode($postcode));
        return array_map(fn (Address $a) => ['address' => $a->label(), 'key' => $compact . ':' . $a->udprn], $addresses);
    }

    /**
     * Loads the address for a key from `findAddresses()` into the public properties.
     */
    public function setAddress(string $key): static
    {
        $this->reset();
        $this->lastError = null;
        try {
            $address = $this->find($key);
        } catch (PostioException $e) {
            $this->lastError = $e;
            return $this;
        }
        if ($address) {
            $this->addressInfo = $address->toArray($this->maxLines);
            $this->address1 = $this->addressInfo['address1'];
            $this->address2 = $this->addressInfo['address2'];
            $this->address3 = $this->addressInfo['address3'];
            $this->town = $address->town;
            $this->county = $address->county;
            $this->postcode = $address->postcode;
            $this->latitude = $address->latitude;
            $this->longitude = $address->longitude;
        }
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAddressInfo(): array
    {
        return $this->addressInfo;
    }

    public function getLatitude(): float|false
    {
        return $this->latitude ?? false;
    }

    public function getLongitude(): float|false
    {
        return $this->longitude ?? false;
    }

    /**
     * Whether the API key works and Postio is reachable. Never billed.
     */
    public function programActive(): bool
    {
        try {
            return $this->client->connect();
        } catch (PostioException $e) {
            $this->lastError = $e;
            return false;
        }
    }

    /**
     * Why the last call came back empty, or null when the postcode simply had no addresses.
     */
    public function getLastError(): ?PostioException
    {
        return $this->lastError;
    }

    /**
     * Every address on a postcode, in street order.
     *
     * @return Address[]
     * @throws PostioException
     */
    public function lookup(string $postcode): array
    {
        $postcode = self::formatPostcode($postcode);
        if ($postcode === '') {
            return [];
        }
        $results = $this->cached($postcode);
        if ($results === null) {
            $results = $this->client->postcode($postcode);
            if ($results && $this->cache) {
                $this->cache->set($this->cacheKey($postcode), $results, $this->ttl);
            }
        }
        $addresses = array_map(fn (array $row) => new Address($row), $results);
        usort($addresses, [self::class, 'streetOrder']);
        return $addresses;
    }

    /**
     * The address for a key from `findAddresses()`, or a bare UDPRN.
     *
     * @throws PostioException
     */
    public function find(string $key): ?Address
    {
        [$postcode, $udprn] = str_contains($key, ':') ? explode(':', $key, 2) : ['', $key];
        if (!ctype_digit($udprn)) {
            return null;
        }
        foreach ($this->cached(self::formatPostcode($postcode)) ?? [] as $row) {
            if ((int)($row['udprn'] ?? 0) === (int)$udprn) {
                return new Address($row);
            }
        }
        $row = $this->client->udprn((int)$udprn);
        return $row ? new Address($row) : null;
    }

    /**
     * "ls14ap" => "LS1 4AP"; an empty string when it cannot be a UK postcode.
     */
    public static function formatPostcode(string $postcode): string
    {
        $compact = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $postcode));
        if (!preg_match('/^[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2}$/', $compact)) {
            return '';
        }
        return substr($compact, 0, -3) . ' ' . substr($compact, -3);
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function cached(string $postcode): ?array
    {
        if (!$this->cache || $postcode === '') {
            return null;
        }
        $results = $this->cache->get($this->cacheKey($postcode));
        return is_array($results) ? $results : null;
    }

    private function cacheKey(string $postcode): string
    {
        return 'pc.' . str_replace(' ', '', $postcode);
    }

    private static function streetOrder(Address $a, Address $b): int
    {
        $fields = fn (Address $x) => [
            (string)($x->data['dependent_thoroughfare'] ?? $x->data['thoroughfare'] ?? ''),
            (string)($x->data['building_number'] ?? ''),
            (string)($x->data['building_name'] ?? ''),
            (string)($x->data['sub_building_name'] ?? ''),
            $x->label(),
        ];
        foreach (array_map(null, $fields($a), $fields($b)) as [$left, $right]) {
            if (($cmp = strnatcasecmp($left, $right)) !== 0) {
                return $cmp;
            }
        }
        return 0;
    }

    private function reset(): void
    {
        $this->addressInfo = [];
        $this->address1 = $this->address2 = $this->address3 = $this->town = $this->county = $this->postcode = '';
        $this->latitude = $this->longitude = null;
    }
}
