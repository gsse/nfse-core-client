<?php

declare(strict_types=1);

namespace Nfse\Core\Contracts;

use Nfse\Core\Security\Certificate;
use Nfse\Core\Transport\HttpRequest;
use Nfse\Core\Transport\HttpResponse;

interface HttpTransportInterface
{
    public function send(HttpRequest $request, ?Certificate $certificate = null): HttpResponse;
}
