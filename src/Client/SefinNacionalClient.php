<?php

declare(strict_types=1);

namespace Nfse\Core\Client;

use Nfse\Core\Contracts\CertificateProviderInterface;
use Nfse\Core\Contracts\EndpointResolverInterface;
use Nfse\Core\Contracts\HttpTransportInterface;
use Nfse\Core\Contracts\NfseClientInterface;
use Nfse\Core\Contracts\XmlValidatorInterface;
use Nfse\Core\DTO\EmitNfseRequest;
use Nfse\Core\DTO\Environment;
use Nfse\Core\DTO\NfseResponse;
use Nfse\Core\DTO\QueryDpsRequest;
use Nfse\Core\DTO\QueryEventsRequest;
use Nfse\Core\DTO\QueryNfseByAccessKeyRequest;
use Nfse\Core\DTO\RegisterEventRequest;
use Nfse\Core\Exceptions\ApiException;
use Nfse\Core\Transport\HttpRequest;

final class SefinNacionalClient implements NfseClientInterface
{
    public function __construct(
        private readonly Environment $environment,
        private readonly EndpointResolverInterface $endpointResolver,
        private readonly HttpTransportInterface $transport,
        private readonly ?CertificateProviderInterface $certificateProvider = null,
        private readonly int $timeoutSeconds = 30,
        private readonly ?XmlValidatorInterface $xmlValidator = null,
        private readonly ?string $emitSchemaPath = null,
        private readonly ?string $eventSchemaPath = null
    ) {
    }

    public function emit(EmitNfseRequest $request): NfseResponse
    {
        $this->validateXml(
            xml: $request->signedDpsXml(),
            schemaPath: $request->schemaPath() ?? $this->emitSchemaPath
        );

        $headers = [];

        if ($request->idempotencyKey() !== null) {
            $headers['Idempotency-Key'] = $request->idempotencyKey();
        }

        return $this->send(
            method: 'POST',
            path: '/nfse',
            body: $request->signedDpsXml(),
            headers: $headers + ['Content-Type' => 'application/xml; charset=utf-8']
        );
    }

    public function queryNfseByAccessKey(QueryNfseByAccessKeyRequest $request): NfseResponse
    {
        return $this->send(
            method: 'GET',
            path: '/nfse/' . rawurlencode($request->accessKey())
        );
    }

    public function queryDps(QueryDpsRequest $request): NfseResponse
    {
        return $this->send(
            method: $request->headOnly() ? 'HEAD' : 'GET',
            path: '/dps/' . rawurlencode($request->dpsId())
        );
    }

    public function registerEvent(RegisterEventRequest $request): NfseResponse
    {
        $this->validateXml(
            xml: $request->signedEventXml(),
            schemaPath: $request->schemaPath() ?? $this->eventSchemaPath
        );

        $headers = ['Content-Type' => 'application/xml; charset=utf-8'];

        if ($request->idempotencyKey() !== null) {
            $headers['Idempotency-Key'] = $request->idempotencyKey();
        }

        return $this->send(
            method: 'POST',
            path: '/nfse/' . rawurlencode($request->accessKey()) . '/eventos',
            body: $request->signedEventXml(),
            headers: $headers
        );
    }

    public function queryEvents(QueryEventsRequest $request): NfseResponse
    {
        $path = '/nfse/' . rawurlencode($request->accessKey()) . '/eventos';

        if ($request->eventType() !== null) {
            $path .= '/' . rawurlencode($request->eventType());
        }

        if ($request->sequence() !== null) {
            $path .= '/' . $request->sequence();
        }

        return $this->send(method: 'GET', path: $path);
    }

    /**
     * @param array<string, string> $headers
     */
    private function send(string $method, string $path, ?string $body = null, array $headers = []): NfseResponse
    {
        $baseUrl = rtrim($this->endpointResolver->resolve($this->environment), '/');
        $url = $baseUrl . '/' . ltrim($path, '/');

        $defaultHeaders = [
            'Accept' => 'application/xml, application/json',
        ];

        $request = new HttpRequest(
            method: $method,
            url: $url,
            headers: $defaultHeaders + $headers,
            body: $body,
            timeoutSeconds: $this->timeoutSeconds
        );

        $response = $this->transport->send(
            request: $request,
            certificate: $this->certificateProvider?->getCertificate()
        );

        $json = null;
        $decoded = json_decode($response->body(), true);

        if (is_array($decoded)) {
            $json = $decoded;
        }

        $mappedResponse = new NfseResponse(
            statusCode: $response->statusCode(),
            headers: $response->headers(),
            body: $response->body(),
            json: $json
        );

        if ($mappedResponse->statusCode() >= 400) {
            throw new ApiException($mappedResponse);
        }

        return $mappedResponse;
    }

    private function validateXml(string $xml, ?string $schemaPath): void
    {
        if ($this->xmlValidator === null) {
            return;
        }

        $normalizedSchemaPath = $schemaPath !== null && trim($schemaPath) === '' ? null : $schemaPath;
        $this->xmlValidator->validate($xml, $normalizedSchemaPath);
    }
}
