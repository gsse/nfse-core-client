<?php

declare(strict_types=1);

namespace Nfse\Core\Contracts;

use Nfse\Core\Security\Certificate;

interface CertificateProviderInterface
{
    public function getCertificate(): Certificate;
}
