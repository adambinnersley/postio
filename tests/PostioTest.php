<?php

namespace Postio\Tests;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Postio\Cache\FileCache;
use Postio\Client;
use Postio\Postio;

class PostioTest extends TestCase
{
    private MockHandler $mock;
    private array $history = [];
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/postio-test-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new FileCache($this->cacheDir))->clear();
        @rmdir($this->cacheDir);
    }

    private function postio(array $responses, bool $cache = true): Postio
    {
        $this->mock = new MockHandler($responses);
        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($this->history));
        $client = new Client('pk_test', new HttpClient(['handler' => $stack, 'base_uri' => Client::BASE_URI]), 1.0, 1);
        return new Postio($client, $cache ? new FileCache($this->cacheDir) : null);
    }

    private static function json(int $status, array|string $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], is_string($body) ? $body : json_encode($body));
    }

    private static function fixture(): Response
    {
        return self::json(200, file_get_contents(__DIR__ . '/fixtures/postcode.json'));
    }

    public function testFindAddressesListsInStreetOrderAndSendsTheKey(): void
    {
        $postio = $this->postio([self::fixture()]);
        $list = $postio->findAddresses('ls19zz');

        $this->assertSame([
            ['address' => 'Example Trading Ltd, 3-9 Sample Street, Leeds', 'key' => 'LS19ZZ:90000003'],
            ['address' => 'Flat 2, Rose House, 12 Sample Street, Leeds', 'key' => 'LS19ZZ:90000001'],
            ['address' => 'Flat 10, Rose House, 12 Sample Street, Leeds', 'key' => 'LS19ZZ:90000002'],
        ], $list);
        $request = $this->history[0]['request'];
        $this->assertSame('/v1/address/postcode/LS1%209ZZ', $request->getUri()->getPath());
        $this->assertSame('pk_test', $request->getHeaderLine('x-api-key'));
    }

    public function testPickingAnAddressIsServedFromTheCache(): void
    {
        $postio = $this->postio([self::fixture()]);
        $postio->findAddresses('LS1 9ZZ');
        $postio->setMaxLines(2)->setAddress('LS19ZZ:90000002');

        $this->assertCount(1, $this->history);
        $this->assertSame('Flat 10, Rose House', $postio->address1);
        $this->assertSame('12 Sample Street', $postio->address2);
        $this->assertSame('', $postio->address3);
        $this->assertSame('Leeds', $postio->town);
        $this->assertSame('West Yorkshire', $postio->county);
        $this->assertSame('LS1 9ZZ', $postio->postcode);
        $this->assertSame(53.8, $postio->getLatitude());

        $postio->findAddresses('LS1 9ZZ');
        $this->assertCount(1, $this->history);
    }

    public function testPickingAnAddressWithoutTheCacheLooksUpTheUdprn(): void
    {
        $row = json_decode(file_get_contents(__DIR__ . '/fixtures/postcode.json'), true)['results'][0];
        $postio = $this->postio([self::json(200, ['success' => true, 'results' => [$row]])], false);
        $postio->setAddress('LS19ZZ:90000003');

        $this->assertSame('/v1/address/udprn/90000003', $this->history[0]['request']->getUri()->getPath());
        $this->assertSame('Example Trading Ltd', $postio->address1);
        $this->assertSame('3-9 Sample Street', $postio->address2);
        $this->assertSame('Leeds', $postio->town);
    }

    public function testUnknownPostcodeIsNotAnError(): void
    {
        $postio = $this->postio([self::json(200, ['success' => true, 'results' => [], 'meta' => []])]);
        $this->assertFalse($postio->findAddresses('LS1 9ZZ'));
        $this->assertNull($postio->getLastError());
    }

    public function testInvalidPostcodeMakesNoRequest(): void
    {
        $postio = $this->postio([]);
        $this->assertFalse($postio->findAddresses('not a postcode'));
        $this->assertCount(0, $this->history);
    }

    public function testOutOfCreditIsReportedAndNotRetried(): void
    {
        $postio = $this->postio([self::json(402, ['success' => false, 'results' => [], 'error' => 'insufficient_credit', 'details' => 'Top up', 'meta' => ['requestId' => 'r1']])]);
        $this->assertFalse($postio->findAddresses('LS1 9ZZ'));
        $this->assertSame('insufficient_credit', $postio->getLastError()->error);
        $this->assertSame(402, $postio->getLastError()->status);
        $this->assertCount(1, $this->history);
    }

    public function testServerErrorIsRetried(): void
    {
        $postio = $this->postio([self::json(503, ['success' => false, 'error' => 'unavailable']), self::fixture()]);
        $this->assertCount(3, $postio->findAddresses('LS1 9ZZ'));
        $this->assertCount(2, $this->history);
    }

    public function testBrokenCacheFallsBackToALiveLookup(): void
    {
        $broken = new class extends \Postio\Cache\AbstractCache {
            public function get(string $key, mixed $default = null): mixed { throw new \RuntimeException('down'); }
            public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool { throw new \RuntimeException('down'); }
            public function delete(string $key): bool { throw new \RuntimeException('down'); }
            public function clear(): bool { throw new \RuntimeException('down'); }
        };
        $this->mock = new MockHandler([self::fixture()]);
        $client = new Client('pk_test', new HttpClient(['handler' => HandlerStack::create($this->mock)]), 1.0, 0);
        $postio = new Postio($client, new \Postio\Cache\FailSafeCache($broken));

        $this->assertCount(3, $postio->findAddresses('LS1 9ZZ'));
    }

    public function testMalformedKeyIsIgnored(): void
    {
        $postio = $this->postio([]);
        $postio->setAddress('LS19ZZ:../../etc');
        $this->assertSame('', $postio->address1);
        $this->assertCount(0, $this->history);
    }
}
