<?php

declare(strict_types=1);

namespace Nfse\Core\Contracts;

use Nfse\Core\DTO\EmitNfseRequest;
use Nfse\Core\DTO\NfseResponse;
use Nfse\Core\DTO\QueryDpsRequest;
use Nfse\Core\DTO\QueryEventsRequest;
use Nfse\Core\DTO\QueryNfseByAccessKeyRequest;
use Nfse\Core\DTO\RegisterEventRequest;

interface NfseClientInterface
{
    public function emit(EmitNfseRequest $request): NfseResponse;

    public function queryNfseByAccessKey(QueryNfseByAccessKeyRequest $request): NfseResponse;

    public function queryDps(QueryDpsRequest $request): NfseResponse;

    public function registerEvent(RegisterEventRequest $request): NfseResponse;

    public function queryEvents(QueryEventsRequest $request): NfseResponse;
}
