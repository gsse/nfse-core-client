<?php

declare(strict_types=1);

namespace Nfse\Core\Contracts;

use Nfse\Core\DTO\Environment;

interface EndpointResolverInterface
{
    public function resolve(Environment $environment): string;
}
