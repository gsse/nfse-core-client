<?php

declare(strict_types=1);

namespace Nfse\Core\Transport;

final class HttpRequest
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $method,
        private readonly string $url,
        private readonly array $headers = [],
        private readonly ?string $body = null,
        private readonly int $timeoutSeconds = 30
    ) {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }
}
