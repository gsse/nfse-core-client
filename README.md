# nfse-core-client

Biblioteca base em PHP para chamadas HTTP da NFS-e Padrao Nacional (SEFIN Nacional), pensada para ser dependencia de uma extensao maior.

## Objetivo

- Fornecer classes e metodos para requisicoes da API NFS-e.
- Isolar transporte, endpoints, certificado e contratos.
- Evitar acoplamento com frameworks no nucleo.

## Operacoes suportadas no cliente

- Emitir NFS-e (`POST /nfse`)
- Consultar NFS-e por chave (`GET /nfse/{chaveAcesso}`)
- Consultar DPS (`GET|HEAD /dps/{id}`)
- Registrar evento (`POST /nfse/{chaveAcesso}/eventos`)
- Consultar eventos (`GET /nfse/{chaveAcesso}/eventos/...`)

## Instalacao

```bash
composer require decoda/nfse-core-client
```

## Uso rapido

```php
<?php

declare(strict_types=1);

use Nfse\Core\Client\SefinNacionalClient;
use Nfse\Core\DTO\EmitNfseRequest;
use Nfse\Core\DTO\Environment;
use Nfse\Core\Endpoints\SefinEndpointResolver;
use Nfse\Core\Security\A1FileCertificateProvider;
use Nfse\Core\Security\OpenSslXmlSigner;
use Nfse\Core\Transport\CurlHttpTransport;
use Nfse\Core\Validation\DomXmlSchemaValidator;
use Nfse\Core\Validation\NfseXsdCatalog;

$certificateProvider = new A1FileCertificateProvider('/caminho/certificado.pfx', 'senha-do-certificado');
$xmlSigner = new OpenSslXmlSigner();

$client = new SefinNacionalClient(
    environment: Environment::PRODUCTION_RESTRICTED,
    endpointResolver: new SefinEndpointResolver(),
    transport: new CurlHttpTransport(),
    certificateProvider: $certificateProvider,
    xmlValidator: new DomXmlSchemaValidator(),
    emitSchemaPath: '/caminho/xsd/dps.xsd',
);

$xmlAssinado = $xmlSigner->sign('<DPS Id="ABC123">...</DPS>', $certificateProvider->getCertificate());
$response = $client->emit(new EmitNfseRequest($xmlAssinado));

if ($response->isSuccess()) {
    echo $response->body();
}
```

## Endpoints oficiais usados por padrao

- Producao restrita: `https://sefin.producaorestrita.nfse.gov.br/API/SefinNacional`
- Producao: `https://sefin.nfse.gov.br/API/SefinNacional`

## Observacoes

- Esta biblioteca nao implementa regras fiscais de negocio.
- Inclui assinador XML via OpenSSL em `OpenSslXmlSigner`.
- Inclui validador XML/XSD em `DomXmlSchemaValidator`.

## Validacao de XML

Voce monta o XML externamente. A extensao pode validar:

- Apenas estrutura XML (sem XSD): passe `xmlValidator`.
- Estrutura + XSD: passe `xmlValidator` e `emitSchemaPath`/`eventSchemaPath`, ou informe `schemaPath` por request.

Exemplo por request:

```php
$request = new EmitNfseRequest(
    signedDpsXml: $xmlAssinado,
    idempotencyKey: 'pedido-123',
    schemaPath: '/caminho/xsd/dps-v1.xsd'
);
```

## Catalogo de XSD por versao

Use `NfseXsdCatalog` para resolver caminhos de XSD por versao sem informar tudo manualmente.

Estrutura padrao esperada:

```text
/seu-diretorio-xsd/
  v1_00/
    dps_v1_00.xsd
    evento_v1_00.xsd
```

Exemplo:

```php
$catalog = new NfseXsdCatalog('/seu-diretorio-xsd', 'v1_00');

$client = new SefinNacionalClient(
    environment: Environment::PRODUCTION_RESTRICTED,
    endpointResolver: new SefinEndpointResolver(),
    transport: new CurlHttpTransport(),
    xmlValidator: new DomXmlSchemaValidator(),
    emitSchemaPath: $catalog->emitSchemaPath(),
    eventSchemaPath: $catalog->eventSchemaPath(),
);
```
