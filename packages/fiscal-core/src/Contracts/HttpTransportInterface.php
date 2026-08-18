<?php

declare(strict_types=1);

namespace Fiscal\Core\Contracts;

use Fiscal\Core\Security\Certificate;
use Fiscal\Core\Transport\HttpRequest;
use Fiscal\Core\Transport\HttpResponse;

interface HttpTransportInterface
{
    public function send(HttpRequest $request, ?Certificate $certificate = null): HttpResponse;
}
