<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class NfseCore extends BaseConfig
{
    public string $environment = 'production_restricted';
    public string $certificatePath = '';
    public string $certificatePassphrase = '';
    public int $timeoutSeconds = 30;
    public bool $validateXml = false;
    public string $emitSchemaPath = '';
    public string $eventSchemaPath = '';
    public string $xsdBasePath = '';
    public string $xsdVersion = 'v1_00';
}
