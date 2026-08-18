<?php

declare(strict_types=1);

namespace Nfse\Client\DTO;

use Fiscal\Core\Exceptions\ValidationException;

final class QueryNfseByAccessKeyRequest
{
    public function __construct(private readonly string $accessKey)
    {
        if (trim($this->accessKey) === '') {
            throw new ValidationException('accessKey cannot be empty.');
        }
    }

    public function accessKey(): string
    {
        return $this->accessKey;
    }
}
