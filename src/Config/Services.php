<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseService;
use Nfse\Core\Bridge\CodeIgniter\NfseClientFactory;
use Nfse\Core\Contracts\NfseClientInterface;

class Services extends BaseService
{
    public static function nfseCore(bool $getShared = true): NfseClientInterface
    {
        if ($getShared) {
            return static::getSharedInstance('nfseCore');
        }

        /** @var NfseCore $config */
        $config = config('NfseCore');

        return NfseClientFactory::fromConfig([
            'environment' => $config->environment,
            'certificate_path' => $config->certificatePath,
            'certificate_passphrase' => $config->certificatePassphrase,
            'timeout_seconds' => $config->timeoutSeconds,
            'validate_xml' => $config->validateXml,
            'emit_schema_path' => $config->emitSchemaPath,
            'event_schema_path' => $config->eventSchemaPath,
            'xsd_base_path' => $config->xsdBasePath,
            'xsd_version' => $config->xsdVersion,
        ]);
    }
}
