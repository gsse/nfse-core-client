<?php

declare(strict_types=1);

namespace Nfse\Core\Contracts;

use Nfse\Core\Security\Certificate;

interface XmlSignerInterface
{
    public function sign(string $xml, Certificate $certificate): string;
}
