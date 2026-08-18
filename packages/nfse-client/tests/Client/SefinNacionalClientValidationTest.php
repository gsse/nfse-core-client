<?php

declare(strict_types=1);

namespace Nfse\Client\Tests\Client;

use Fiscal\Core\Contracts\HttpTransportInterface;
use Fiscal\Core\Exceptions\XmlValidationException;
use Fiscal\Core\Security\Certificate;
use Fiscal\Core\Transport\HttpRequest;
use Fiscal\Core\Transport\HttpResponse;
use Fiscal\Core\Validation\DomXmlSchemaValidator;
use Nfse\Client\SefinNacionalClient;
use Nfse\Client\Contracts\EndpointResolverInterface;
use Nfse\Client\DTO\EmitNfseRequest;
use Nfse\Client\DTO\Environment;
use PHPUnit\Framework\TestCase;

final class SefinNacionalClientValidationTest extends TestCase
{
    private string $xsdPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xsdPath = sys_get_temp_dir() . '/nfse-core-client-xsd-' . bin2hex(random_bytes(5)) . '.xsd';

        $xsd = <<<'XSD'
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
  <xs:element name="DPS">
    <xs:complexType>
      <xs:sequence>
        <xs:element name="ValorServico" type="xs:decimal"/>
      </xs:sequence>
      <xs:attribute name="Id" type="xs:string" use="optional"/>
    </xs:complexType>
  </xs:element>
</xs:schema>
XSD;

        file_put_contents($this->xsdPath, $xsd);
    }

    protected function tearDown(): void
    {
        if (is_file($this->xsdPath)) {
            @unlink($this->xsdPath);
        }

        parent::tearDown();
    }

    public function testDoesNotSendRequestWhenXmlValidationFails(): void
    {
        $transport = new SpyHttpTransport();
        $client = new SefinNacionalClient(
            environment: Environment::PRODUCTION_RESTRICTED,
            endpointResolver: new FakeEndpointResolver(),
            transport: $transport,
            timeoutSeconds: 10,
            xmlValidator: new DomXmlSchemaValidator(),
            emitSchemaPath: $this->xsdPath
        );

        $this->expectException(XmlValidationException::class);

        try {
            $client->emit(new EmitNfseRequest('<DPS><Outro>1</Outro></DPS>'));
        } finally {
            $this->assertFalse($transport->called);
        }
    }

    public function testSendsRequestWhenXmlValidationPasses(): void
    {
        $transport = new SpyHttpTransport();
        $client = new SefinNacionalClient(
            environment: Environment::PRODUCTION_RESTRICTED,
            endpointResolver: new FakeEndpointResolver(),
            transport: $transport,
            timeoutSeconds: 10,
            xmlValidator: new DomXmlSchemaValidator(),
            emitSchemaPath: $this->xsdPath
        );

        $response = $client->emit(new EmitNfseRequest('<DPS Id="A1"><ValorServico>10.00</ValorServico></DPS>'));

        $this->assertTrue($transport->called);
        $this->assertSame(200, $response->statusCode());
    }
}

final class FakeEndpointResolver implements EndpointResolverInterface
{
    public function resolve(Environment $environment): string
    {
        return 'https://example.test/API/SefinNacional';
    }
}

final class SpyHttpTransport implements HttpTransportInterface
{
    public bool $called = false;

    public function send(HttpRequest $request, ?Certificate $certificate = null): HttpResponse
    {
        $this->called = true;
        return new HttpResponse(200, ['content-type' => ['application/xml']], '<retorno/>');
    }
}
