<?php

declare(strict_types=1);

namespace Nfse\Client\Exceptions;

use Nfse\Client\DTO\NfseResponse;

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
