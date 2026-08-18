<?php

declare(strict_types=1);

namespace Fiscal\Core\Contracts;

interface XmlValidatorInterface
{
    public function validate(string $xml, ?string $xsdPath = null): void;
}
