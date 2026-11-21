<?php

namespace Glofora\GSend\Exceptions;

class GSendApiException extends GSendException
{
    /**
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array<string, mixed>|string|null  $responseBody
     * @param  \Throwable|null  $previous
     */
    public function __construct(
        string $message,
        protected int $statusCode = 0,
        protected mixed $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Get HTTP response status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get raw or parsed HTTP response body.
     */
    public function getResponseBody(): mixed
    {
        return $this->responseBody;
    }
}
