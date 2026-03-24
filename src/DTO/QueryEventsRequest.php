<?php

declare(strict_types=1);

namespace Nfse\Core\DTO;

use Nfse\Core\Exceptions\ValidationException;

final class QueryEventsRequest
{
    public function __construct(
        private readonly string $accessKey,
        private readonly ?string $eventType = null,
        private readonly ?int $sequence = null
    ) {
        if (trim($this->accessKey) === '') {
            throw new ValidationException('accessKey cannot be empty.');
        }

        if ($this->sequence !== null && $this->sequence < 1) {
            throw new ValidationException('sequence must be >= 1.');
        }
    }

    public function accessKey(): string
    {
        return $this->accessKey;
    }

    public function eventType(): ?string
    {
        return $this->eventType;
    }

    public function sequence(): ?int
    {
        return $this->sequence;
    }
}
