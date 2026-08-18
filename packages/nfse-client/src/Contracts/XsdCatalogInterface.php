<?php

declare(strict_types=1);

namespace Nfse\Client\Contracts;

interface XsdCatalogInterface
{
    public function emitSchemaPath(?string $version = null): string;

    public function eventSchemaPath(?string $version = null): string;
}
