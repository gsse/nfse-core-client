<?php

declare(strict_types=1);

namespace Nfse\Core\DTO;

use Nfse\Core\Exceptions\ValidationException;

final class RegisterEventRequest
{
    public function __construct(
        private readonly string $accessKey,
        private readonly string $signedEventXml,
        private readonly ?string $idempotencyKey = null,
        private readonly ?string $schemaPath = null
    ) {
        if (trim($this->accessKey) === '') {
            throw new ValidationException('accessKey cannot be empty.');
        }

        if (trim($this->signedEventXml) === '') {
            throw new ValidationException('signedEventXml cannot be empty.');
        }
    }

    public function accessKey(): string
    {
        return $this->accessKey;
    }

    public function signedEventXml(): string
    {
        return $this->signedEventXml;
    }

    public function idempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function schemaPath(): ?string
    {
        return $this->schemaPath;
    }
}
