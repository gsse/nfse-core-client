<?php

declare(strict_types=1);

namespace Nfse\Core\Security;

use Nfse\Core\Exceptions\ValidationException;

final class Certificate
{
    public function __construct(
        private readonly string $pkcs12Path,
        private readonly string $passphrase
    ) {
        if (!is_file($this->pkcs12Path)) {
            throw new ValidationException(sprintf('Certificate file not found: %s', $this->pkcs12Path));
        }

        if (!is_readable($this->pkcs12Path)) {
            throw new ValidationException(sprintf('Certificate file is not readable: %s', $this->pkcs12Path));
        }
    }

    public function pkcs12Path(): string
    {
        return $this->pkcs12Path;
    }

    public function passphrase(): string
    {
        return $this->passphrase;
    }
}
