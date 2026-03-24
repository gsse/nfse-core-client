<?php

declare(strict_types=1);

namespace Nfse\Core\Tests\Validation;

use Nfse\Core\Exceptions\ValidationException;
use Nfse\Core\Exceptions\XmlValidationException;
use Nfse\Core\Validation\DomXmlSchemaValidator;
use PHPUnit\Framework\TestCase;

final class DomXmlSchemaValidatorTest extends TestCase
{
    private string $xsdPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xsdPath = sys_get_temp_dir() . '/nfse-core-xsd-' . bin2hex(random_bytes(5)) . '.xsd';

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

    public function testValidatesWellFormedXmlWithoutSchema(): void
    {
        $validator = new DomXmlSchemaValidator();

        $validator->validate('<DPS><ValorServico>10.00</ValorServico></DPS>');

        $this->assertTrue(true);
    }

    public function testThrowsForMalformedXml(): void
    {
        $validator = new DomXmlSchemaValidator();

        $this->expectException(XmlValidationException::class);

        $validator->validate('<DPS>');
    }

    public function testValidatesAgainstXsd(): void
    {
        $validator = new DomXmlSchemaValidator();

        $validator->validate('<DPS Id="A1"><ValorServico>12.34</ValorServico></DPS>', $this->xsdPath);

        $this->assertTrue(true);
    }

    public function testThrowsWhenXmlDoesNotMatchXsd(): void
    {
        $validator = new DomXmlSchemaValidator();

        $this->expectException(XmlValidationException::class);

        $validator->validate('<DPS><OutroCampo>1</OutroCampo></DPS>', $this->xsdPath);
    }

    public function testThrowsWhenXsdDoesNotExist(): void
    {
        $validator = new DomXmlSchemaValidator();

        $this->expectException(ValidationException::class);

        $validator->validate('<DPS><ValorServico>10.00</ValorServico></DPS>', $this->xsdPath . '.missing');
    }
}
