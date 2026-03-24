<?php

declare(strict_types=1);

namespace Nfse\Core\Exceptions;

use Nfse\Core\DTO\NfseResponse;

final class ApiException extends NfseException
{
    public function __construct(
        private readonly NfseResponse $response,
        string $message = ''
    ) {
        parent::__construct(
            $message !== ''
                ? $message
                : sprintf('NFS-e API returned HTTP %d.', $response->statusCode())
        );
    }

    public function response(): NfseResponse
    {
        return $this->response;
    }
}
