<?php

declare(strict_types=1);

namespace Nfse\Core\Tests\Validation;

use Nfse\Core\Exceptions\ValidationException;
use Nfse\Core\Validation\NfseXsdCatalog;
use PHPUnit\Framework\TestCase;

final class NfseXsdCatalogTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/nfse-xsd-catalog-' . bin2hex(random_bytes(5));
        mkdir($this->baseDir . '/v1_00', 0777, true);
        mkdir($this->baseDir . '/v1_01', 0777, true);

        file_put_contents($this->baseDir . '/v1_00/dps_v1_00.xsd', '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"/>');
        file_put_contents($this->baseDir . '/v1_00/evento_v1_00.xsd', '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"/>');
        file_put_contents($this->baseDir . '/v1_01/dps_v1_01.xsd', '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"/>');
        file_put_contents($this->baseDir . '/v1_01/evento_v1_01.xsd', '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"/>');
    }

    protected function tearDown(): void
    {
        @unlink($this->baseDir . '/v1_00/dps_v1_00.xsd');
        @unlink($this->baseDir . '/v1_00/evento_v1_00.xsd');
        @unlink($this->baseDir . '/v1_01/dps_v1_01.xsd');
        @unlink($this->baseDir . '/v1_01/evento_v1_01.xsd');
        @rmdir($this->baseDir . '/v1_00');
        @rmdir($this->baseDir . '/v1_01');
        @rmdir($this->baseDir);

        parent::tearDown();
    }

    public function testResolvesDefaultVersionPaths(): void
    {
        $catalog = new NfseXsdCatalog($this->baseDir);

        $this->assertSame($this->baseDir . '/v1_00/dps_v1_00.xsd', $catalog->emitSchemaPath());
        $this->assertSame($this->baseDir . '/v1_00/evento_v1_00.xsd', $catalog->eventSchemaPath());
    }

    public function testResolvesSpecificVersionWithCustomIndex(): void
    {
        $catalog = new NfseXsdCatalog(
            basePath: $this->baseDir,
            defaultVersion: 'v1_01',
            index: [
                'v1_01' => [
                    'emit' => 'v1_01/dps_v1_01.xsd',
                    'event' => 'v1_01/evento_v1_01.xsd',
                ],
            ]
        );

        $this->assertSame($this->baseDir . '/v1_01/dps_v1_01.xsd', $catalog->emitSchemaPath());
        $this->assertSame($this->baseDir . '/v1_01/evento_v1_01.xsd', $catalog->eventSchemaPath('v1_01'));
    }

    public function testThrowsForUnknownVersion(): void
    {
        $catalog = new NfseXsdCatalog($this->baseDir);

        $this->expectException(ValidationException::class);
        $catalog->emitSchemaPath('v9_99');
    }

    public function testThrowsWhenMappedFileDoesNotExist(): void
    {
        $catalog = new NfseXsdCatalog(
            basePath: $this->baseDir,
            defaultVersion: 'v1_00',
            index: [
                'v1_00' => [
                    'emit' => 'v1_00/nao-existe.xsd',
                    'event' => 'v1_00/evento_v1_00.xsd',
                ],
            ]
        );

        $this->expectException(ValidationException::class);
        $catalog->emitSchemaPath();
    }
}
