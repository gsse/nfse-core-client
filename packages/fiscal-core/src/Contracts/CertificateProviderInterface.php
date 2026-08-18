<?php

declare(strict_types=1);

namespace Fiscal\Core\Contracts;

use Fiscal\Core\Security\Certificate;

interface CertificateProviderInterface
{
    public function getCertificate(): Certificate;
}
