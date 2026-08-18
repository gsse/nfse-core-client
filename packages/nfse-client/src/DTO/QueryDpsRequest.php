<?php

declare(strict_types=1);

namespace Nfse\Client\DTO;

use Fiscal\Core\Exceptions\ValidationException;

final class QueryDpsRequest
{
    public function __construct(
        private readonly string $dpsId,
        private readonly bool $headOnly = false
    ) {
        if (trim($this->dpsId) === '') {
            throw new ValidationException('dpsId cannot be empty.');
        }
    }

    public function dpsId(): string
    {
        return $this->dpsId;
    }

    public function headOnly(): bool
    {
        return $this->headOnly;
    }
}
