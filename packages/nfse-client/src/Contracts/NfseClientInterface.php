<?php

declare(strict_types=1);

namespace Nfse\Client\Contracts;

use Nfse\Client\DTO\EmitNfseRequest;
use Nfse\Client\DTO\NfseResponse;
use Nfse\Client\DTO\QueryDpsRequest;
use Nfse\Client\DTO\QueryEventsRequest;
use Nfse\Client\DTO\QueryNfseByAccessKeyRequest;
use Nfse\Client\DTO\RegisterEventRequest;

interface NfseClientInterface
{
    public function emit(EmitNfseRequest $request): NfseResponse;

    public function queryNfseByAccessKey(QueryNfseByAccessKeyRequest $request): NfseResponse;

    public function queryDps(QueryDpsRequest $request): NfseResponse;

    public function registerEvent(RegisterEventRequest $request): NfseResponse;

    public function queryEvents(QueryEventsRequest $request): NfseResponse;
}
