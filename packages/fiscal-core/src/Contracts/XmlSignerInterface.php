<?php

declare(strict_types=1);

namespace Fiscal\Core\Contracts;

use Fiscal\Core\Security\Certificate;

interface XmlSignerInterface
{
    public function sign(string $xml, Certificate $certificate): string;
}
