<?php

declare(strict_types=1);

namespace Nfse\Core\Tests\Bridge\CodeIgniter;

use Nfse\Core\Bridge\CodeIgniter\NfseClientFactory;
use Nfse\Core\Client\SefinNacionalClient;
use Nfse\Core\DTO\EmitNfseRequest;
use Nfse\Core\Exceptions\ValidationException;
use Nfse\Core\Exceptions\XmlValidationException;
use PHPUnit\Framework\TestCase;

final class NfseClientFactoryTest extends TestCase
{
    private string $xsdBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xsdBasePath = sys_get_temp_dir() . '/nfse-factory-xsd-' . bin2hex(random_bytes(5));
        mkdir($this->xsdBasePath . '/v1_00', 0777, true);

        $emitXsd = <<<'XSD'
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
  <xs:element name="DPS">
    <xs:complexType>
      <xs:sequence>
        <xs:element name="ValorServico" type="xs:decimal"/>
      </xs:sequence>
    </xs:complexType>
  </xs:element>
</xs:schema>
XSD;
        $eventXsd = <<<'XSD'
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
  <xs:element name="Evento"/>
</xs:schema>
XSD;

        file_put_contents($this->xsdBasePath . '/v1_00/dps_v1_00.xsd', $emitXsd);
        file_put_contents($this->xsdBasePath . '/v1_00/evento_v1_00.xsd', $eventXsd);
    }

    protected function tearDown(): void
    {
        @unlink($this->xsdBasePath . '/v1_00/dps_v1_00.xsd');
        @unlink($this->xsdBasePath . '/v1_00/evento_v1_00.xsd');
        @rmdir($this->xsdBasePath . '/v1_00');
        @rmdir($this->xsdBasePath);

        parent::tearDown();
    }

    public function testBuildsClientFromValidConfig(): void
    {
        $client = NfseClientFactory::fromConfig([
            'environment' => 'production_restricted',
            'timeout_seconds' => 25,
            'validate_xml' => true,
            'emit_schema_path' => '/tmp/dps.xsd',
            'event_schema_path' => '/tmp/evento.xsd',
        ]);

        $this->assertInstanceOf(SefinNacionalClient::class, $client);
    }

    public function testThrowsForInvalidEnvironment(): void
    {
        $this->expectException(ValidationException::class);

        NfseClientFactory::fromConfig([
            'environment' => 'invalid',
        ]);
    }

    public function testUsesXsdCatalogWhenBasePathIsProvided(): void
    {
        $client = NfseClientFactory::fromConfig([
            'environment' => 'production_restricted',
            'validate_xml' => true,
            'xsd_base_path' => $this->xsdBasePath,
            'xsd_version' => 'v1_00',
        ]);

        $this->expectException(XmlValidationException::class);

        $client->emit(new EmitNfseRequest('<DPS><OutroCampo>1</OutroCampo></DPS>'));
    }
}
