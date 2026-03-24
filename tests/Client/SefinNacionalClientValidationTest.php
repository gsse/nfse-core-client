<?php

declare(strict_types=1);

namespace Nfse\Core\Tests\Client;

use Nfse\Core\Client\SefinNacionalClient;
use Nfse\Core\Contracts\EndpointResolverInterface;
use Nfse\Core\Contracts\HttpTransportInterface;
use Nfse\Core\DTO\EmitNfseRequest;
use Nfse\Core\DTO\Environment;
use Nfse\Core\Exceptions\XmlValidationException;
use Nfse\Core\Security\Certificate;
use Nfse\Core\Transport\HttpRequest;
use Nfse\Core\Transport\HttpResponse;
use Nfse\Core\Validation\DomXmlSchemaValidator;
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
