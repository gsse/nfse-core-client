<?php

declare(strict_types=1);

namespace Nfse\Core\DTO;

enum Environment: string
{
    case PRODUCTION_RESTRICTED = 'production_restricted';
    case PRODUCTION = 'production';
}
