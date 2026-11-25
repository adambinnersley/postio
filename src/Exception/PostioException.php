<?php

namespace Postio\Exception;

use RuntimeException;

/**
 * A request Postio refused or could not answer.
 *
 * `$error` is Postio's machine-readable code (`invalid_api_key`, `insufficient_credit`, ...),
 * or `connection_failed` when the API could not be reached at all.
 */
class PostioException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly string $error = '',
        public readonly ?string $requestId = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $status, $previous);
    }

    /**
     * Whether retrying the same request later could succeed.
     */
    public function isTransient(): bool
    {
        return $this->status === 0 || $this->status === 429 || $this->status >= 500;
    }
}
