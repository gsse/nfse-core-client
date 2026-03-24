<?php

declare(strict_types=1);

namespace Nfse\Core\Contracts;

interface XmlValidatorInterface
{
    public function validate(string $xml, ?string $xsdPath = null): void;
}
