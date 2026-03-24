<?php

declare(strict_types=1);

namespace Nfse\Core\Endpoints;

use Nfse\Core\Contracts\EndpointResolverInterface;
use Nfse\Core\DTO\Environment;
use Nfse\Core\Exceptions\ValidationException;

final class SefinEndpointResolver implements EndpointResolverInterface
{
    public function resolve(Environment $environment): string
    {
        return match ($environment) {
            Environment::PRODUCTION_RESTRICTED => 'https://sefin.producaorestrita.nfse.gov.br/API/SefinNacional',
            Environment::PRODUCTION => 'https://sefin.nfse.gov.br/API/SefinNacional',
            default => throw new ValidationException('Unsupported NFS-e environment.'),
        };
    }
}
