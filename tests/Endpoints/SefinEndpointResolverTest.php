<?php

declare(strict_types=1);

namespace Nfse\Core\Tests\Endpoints;

use Nfse\Core\DTO\Environment;
use Nfse\Core\Endpoints\SefinEndpointResolver;
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
