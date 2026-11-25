<?php

namespace Postio;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Postio\Exception\PostioException;
use Psr\Http\Message\ResponseInterface;

/**
 * Thin HTTP client for the Postio address API (https://postio.co.uk/docs/address).
 *
 * Every endpoint is a GET, so 429 and 5xx responses are retried with backoff; 4xx
 * responses are permanent and thrown straight away.
 */
class Client
{
    public const BASE_URI = 'https://api.postio.co.uk/v1/';

    private ClientInterface $http;

    /**
     * @param string $apiKey Postio key (`pk_...`)
     * @param ClientInterface|null $http Any Guzzle client (Laravel's `Http::buildClient()` included);
     *        requests use absolute URLs and carry their own timeouts
     * @param float $timeout Seconds before a single attempt is abandoned
     * @param int $retries Extra attempts after a transient failure
     */
    public function __construct(
        private string $apiKey,
        ?ClientInterface $http = null,
        private float $timeout = 4.0,
        private int $retries = 2
    ) {
        $this->http = $http ?? new HttpClient();
    }

    /**
     * Every delivery point on a postcode; an empty array when the postcode has none.
     *
     * @return array<int, array<string, mixed>>
     */
    public function postcode(string $postcode): array
    {
        return $this->get('address/postcode/' . rawurlencode($postcode))['results'] ?? [];
    }

    /**
     * One delivery point by its Royal Mail UDPRN, or null when it does not exist.
     *
     * @return array<string, mixed>|null
     */
    public function udprn(int $udprn): ?array
    {
        $results = $this->get('address/udprn/' . $udprn)['results'] ?? null;
        if (is_array($results) && array_is_list($results)) {
            return $results[0] ?? null;
        }
        return $results ?: null;
    }

    /**
     * Free type-ahead search; each result holds only `udprn` and `suggestion`.
     *
     * @return array<int, array{udprn: int, suggestion: string}>
     */
    public function search(string $query, int $maxResults = 10): array
    {
        return $this->get('address/search', ['q' => $query, 'max_results' => $maxResults])['results'] ?? [];
    }

    /**
     * Free key and health check.
     */
    public function connect(): bool
    {
        return (bool)($this->get('connect')['success'] ?? false);
    }

    /**
     * @param array<string, scalar> $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        $attempt = 0;
        while (true) {
            try {
                return $this->decode($this->http->request('GET', self::BASE_URI . $path, [
                    'headers' => ['x-api-key' => $this->apiKey, 'Accept' => 'application/json'],
                    'query' => $query,
                    'http_errors' => false,
                    'timeout' => $this->timeout,
                    'connect_timeout' => min($this->timeout, 3.0),
                ]));
            } catch (PostioException $e) {
                if (!$e->isTransient() || $attempt >= $this->retries) {
                    throw $e;
                }
            } catch (GuzzleException $e) {
                if ($attempt >= $this->retries) {
                    throw new PostioException($e->getMessage(), $e instanceof RequestException && $e->getResponse() ? $e->getResponse()->getStatusCode() : 0, 'connection_failed', null, $e);
                }
            }
            usleep((int)((200 * (3 ** $attempt) + random_int(0, 100)) * 1000));
            $attempt++;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $body = json_decode((string)$response->getBody(), true);
        if ($status >= 200 && $status < 300 && is_array($body)) {
            return $body;
        }
        throw new PostioException(
            is_array($body) ? (string)($body['details'] ?? $body['error'] ?? 'Postio request failed') : 'Postio returned an unreadable response',
            $status,
            is_array($body) ? (string)($body['error'] ?? '') : '',
            is_array($body) ? ($body['meta']['requestId'] ?? null) : null
        );
    }
}
