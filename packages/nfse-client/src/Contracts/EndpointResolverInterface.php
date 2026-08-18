<?php

declare(strict_types=1);

namespace Nfse\Client\Contracts;

use Nfse\Client\DTO\Environment;

interface EndpointResolverInterface
{
    public function resolve(Environment $environment): string;
}
