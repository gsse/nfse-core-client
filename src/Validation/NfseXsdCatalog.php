<?php

declare(strict_types=1);

namespace Nfse\Core\Validation;

use Nfse\Core\Contracts\XsdCatalogInterface;
use Nfse\Core\Exceptions\ValidationException;

final class NfseXsdCatalog implements XsdCatalogInterface
{
    public const DEFAULT_VERSION = 'v1_00';

    /**
     * @var array<string, array{emit: string, event: string}>
     */
    public const DEFAULT_INDEX = [
        'v1_00' => [
            'emit' => 'v1_00/dps_v1_00.xsd',
            'event' => 'v1_00/evento_v1_00.xsd',
        ],
    ];

    /**
     * @param array<string, array{emit: string, event: string}> $index
     */
    public function __construct(
        private readonly string $basePath,
        private readonly string $defaultVersion = self::DEFAULT_VERSION,
        private readonly array $index = self::DEFAULT_INDEX
    ) {
        if (trim($this->basePath) === '') {
            throw new ValidationException('basePath cannot be empty for NfseXsdCatalog.');
        }
    }

    public function emitSchemaPath(?string $version = null): string
    {
        return $this->resolve('emit', $version);
    }

    public function eventSchemaPath(?string $version = null): string
    {
        return $this->resolve('event', $version);
    }

    private function resolve(string $type, ?string $version): string
    {
        $resolvedVersion = $this->normalizeVersion($version ?? $this->defaultVersion);

        if (!isset($this->index[$resolvedVersion])) {
            throw new ValidationException(
                sprintf('XSD version "%s" not found in catalog index.', $resolvedVersion)
            );
        }

        $relativePath = $this->index[$resolvedVersion][$type] ?? '';
        if (!is_string($relativePath) || trim($relativePath) === '') {
            throw new ValidationException(
                sprintf('Invalid catalog entry for version "%s" and type "%s".', $resolvedVersion, $type)
            );
        }

        $path = rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);

        if (!is_file($path)) {
            throw new ValidationException(sprintf('XSD file not found for %s/%s: %s', $resolvedVersion, $type, $path));
        }

        if (!is_readable($path)) {
            throw new ValidationException(sprintf('XSD file is not readable for %s/%s: %s', $resolvedVersion, $type, $path));
        }

        return $path;
    }

    private function normalizeVersion(string $version): string
    {
        $trimmed = trim($version);
        if ($trimmed === '') {
            throw new ValidationException('XSD version cannot be empty.');
        }

        return $trimmed;
    }
}
