<?php

declare(strict_types=1);

namespace Nfse\Core\Bridge\CodeIgniter;

use Nfse\Core\Client\SefinNacionalClient;
use Nfse\Core\DTO\Environment;
use Nfse\Core\Endpoints\SefinEndpointResolver;
use Nfse\Core\Exceptions\ValidationException;
use Nfse\Core\Security\A1FileCertificateProvider;
use Nfse\Core\Transport\CurlHttpTransport;
use Nfse\Core\Validation\DomXmlSchemaValidator;
use Nfse\Core\Validation\NfseXsdCatalog;
use ValueError;

final class NfseClientFactory
{
    /**
     * @param array{
     *     environment: string,
     *     certificate_path?: string,
     *     certificate_passphrase?: string,
     *     timeout_seconds?: int,
     *     validate_xml?: bool,
     *     emit_schema_path?: string,
     *     event_schema_path?: string,
     *     xsd_base_path?: string,
     *     xsd_version?: string
     * } $config
     */
    public static function fromConfig(array $config): SefinNacionalClient
    {
        $environmentValue = $config['environment'] ?? '';

        try {
            $environment = Environment::from($environmentValue);
        } catch (ValueError $exception) {
            throw new ValidationException(
                sprintf('Unsupported environment "%s". Use "production_restricted" or "production".', $environmentValue),
                previous: $exception
            );
        }

        $certificatePath = trim((string) ($config['certificate_path'] ?? ''));
        $certificatePassphrase = (string) ($config['certificate_passphrase'] ?? '');
        $certificateProvider = null;

        if ($certificatePath !== '') {
            $certificateProvider = new A1FileCertificateProvider($certificatePath, $certificatePassphrase);
        }

        $timeoutSeconds = (int) ($config['timeout_seconds'] ?? 30);
        if ($timeoutSeconds < 1) {
            throw new ValidationException('timeout_seconds must be >= 1.');
        }

        $validateXml = (bool) ($config['validate_xml'] ?? false);
        $xmlValidator = $validateXml ? new DomXmlSchemaValidator() : null;
        $emitSchemaPath = self::normalizePath($config['emit_schema_path'] ?? null);
        $eventSchemaPath = self::normalizePath($config['event_schema_path'] ?? null);
        $xsdBasePath = self::normalizePath($config['xsd_base_path'] ?? null);
        $xsdVersion = self::normalizeText($config['xsd_version'] ?? null);

        if ($validateXml && $xsdBasePath !== null && ($emitSchemaPath === null || $eventSchemaPath === null)) {
            $catalog = new NfseXsdCatalog(
                basePath: $xsdBasePath,
                defaultVersion: $xsdVersion ?? NfseXsdCatalog::DEFAULT_VERSION
            );

            $emitSchemaPath ??= $catalog->emitSchemaPath();
            $eventSchemaPath ??= $catalog->eventSchemaPath();
        }

        return new SefinNacionalClient(
            environment: $environment,
            endpointResolver: new SefinEndpointResolver(),
            transport: new CurlHttpTransport(),
            certificateProvider: $certificateProvider,
            timeoutSeconds: $timeoutSeconds,
            xmlValidator: $xmlValidator,
            emitSchemaPath: $emitSchemaPath,
            eventSchemaPath: $eventSchemaPath
        );
    }

    private static function normalizePath(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    private static function normalizeText(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }
}
