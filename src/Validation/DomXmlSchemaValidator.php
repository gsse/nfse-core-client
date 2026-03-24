<?php

declare(strict_types=1);

namespace Nfse\Core\Validation;

use DOMDocument;
use LibXMLError;
use Nfse\Core\Contracts\XmlValidatorInterface;
use Nfse\Core\Exceptions\ValidationException;
use Nfse\Core\Exceptions\XmlValidationException;

final class DomXmlSchemaValidator implements XmlValidatorInterface
{
    public function validate(string $xml, ?string $xsdPath = null): void
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;

            $loaded = $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOCDATA | LIBXML_NONET);

            if ($loaded === false) {
                throw new XmlValidationException(
                    sprintf('Invalid XML payload: %s', $this->formatLibxmlErrors(libxml_get_errors()))
                );
            }

            $schema = $this->normalizeSchemaPath($xsdPath);
            if ($schema === null) {
                return;
            }

            if (!is_file($schema)) {
                throw new ValidationException(sprintf('XSD file not found: %s', $schema));
            }

            if (!is_readable($schema)) {
                throw new ValidationException(sprintf('XSD file is not readable: %s', $schema));
            }

            $isSchemaValid = $dom->schemaValidate($schema);

            if ($isSchemaValid === false) {
                throw new XmlValidationException(
                    sprintf('XML does not match XSD: %s', $this->formatLibxmlErrors(libxml_get_errors()))
                );
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @param array<LibXMLError> $errors
     */
    private function formatLibxmlErrors(array $errors): string
    {
        if ($errors === []) {
            return 'Unknown validation error.';
        }

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = trim($error->message);
        }

        return implode(' | ', $messages);
    }

    private function normalizeSchemaPath(?string $xsdPath): ?string
    {
        if ($xsdPath === null) {
            return null;
        }

        $trimmed = trim($xsdPath);
        return $trimmed === '' ? null : $trimmed;
    }
}
