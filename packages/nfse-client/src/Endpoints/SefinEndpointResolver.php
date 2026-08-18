<?php

declare(strict_types=1);

namespace Nfse\Client\Endpoints;

use Nfse\Client\Contracts\EndpointResolverInterface;
use Nfse\Client\DTO\Environment;
use Fiscal\Core\Exceptions\ValidationException;

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
