<?php

declare(strict_types=1);

namespace Nfse\Core\DTO;

use Nfse\Core\Exceptions\ValidationException;

final class EmitNfseRequest
{
    public function __construct(
        private readonly string $signedDpsXml,
        private readonly ?string $idempotencyKey = null,
        private readonly ?string $schemaPath = null
    ) {
        if (trim($this->signedDpsXml) === '') {
            throw new ValidationException('signedDpsXml cannot be empty.');
        }
    }

    public function signedDpsXml(): string
    {
        return $this->signedDpsXml;
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
