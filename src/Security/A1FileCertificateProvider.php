<?php

declare(strict_types=1);

namespace Nfse\Core\Security;

use Nfse\Core\Contracts\CertificateProviderInterface;

final class A1FileCertificateProvider implements CertificateProviderInterface
{
    public function __construct(
        private readonly string $pkcs12Path,
        private readonly string $passphrase
    ) {
    }

    public function getCertificate(): Certificate
    {
        return new Certificate($this->pkcs12Path, $this->passphrase);
    }
}
