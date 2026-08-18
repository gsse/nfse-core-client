<?php

declare(strict_types=1);

namespace Nfse\Client\Tests\Endpoints;

use Nfse\Client\DTO\Environment;
use Nfse\Client\Endpoints\SefinEndpointResolver;
use PHPUnit\Framework\TestCase;

final class SefinEndpointResolverTest extends TestCase
{
    public function testResolvesProductionRestrictedEndpoint(): void
    {
        $resolver = new SefinEndpointResolver();

        $this->assertSame(
            'https://sefin.producaorestrita.nfse.gov.br/API/SefinNacional',
            $resolver->resolve(Environment::PRODUCTION_RESTRICTED)
        );
    }

    public function testResolvesProductionEndpoint(): void
    {
        $resolver = new SefinEndpointResolver();

        $this->assertSame(
            'https://sefin.nfse.gov.br/API/SefinNacional',
            $resolver->resolve(Environment::PRODUCTION)
        );
    }
}
