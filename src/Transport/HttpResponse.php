<?php

declare(strict_types=1);

namespace Nfse\Core\Transport;

final class HttpResponse
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly string $body
    ) {
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, list<string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }
}
