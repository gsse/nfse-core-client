# Fiscal monorepo

Monorepo de bibliotecas PHP para integracao com documentos fiscais eletronicos.

## Pacotes

### `apex-dextra/fiscal-core`

Infraestrutura compartilhada entre os clientes fiscais:

- transporte HTTP com cURL;
- certificado digital A1 em PKCS#12;
- assinatura XML via OpenSSL;
- validacao XML e XSD;
- contratos e excecoes de infraestrutura.

Namespace: `Fiscal\\Core`.

### `apex-dextra/nfse-client`

Cliente para a NFS-e Padrao Nacional (SEFIN Nacional). Atualmente suporta:

- emissao de NFS-e (`POST /nfse`);
- consulta por chave de acesso (`GET /nfse/{chaveAcesso}`);
- consulta de DPS (`GET|HEAD /dps/{id}`);
- registro de evento (`POST /nfse/{chaveAcesso}/eventos`);
- consulta de eventos.

Namespace: `Nfse\\Client`.

Os pacotes de NF-e e NFC-e podem ser adicionados futuramente em `packages/nfe-client` e `packages/nfce-client`, reutilizando o `fiscal-core` sem misturar regras de dominio.

## Estrutura

```text
packages/
  fiscal-core/
    src/
    tests/
  nfse-client/
    src/
    tests/
```

## Desenvolvimento

```bash
composer install
composer test
```

O XML fiscal continua sendo montado pela aplicacao consumidora. O cliente pode valida-lo, assina-lo com um certificado A1 e envia-lo para a SEFIN.

Exemplo:

```php
<?php

declare(strict_types=1);

use Fiscal\Core\Security\A1FileCertificateProvider;
use Fiscal\Core\Security\OpenSslXmlSigner;
use Fiscal\Core\Transport\CurlHttpTransport;
use Fiscal\Core\Validation\DomXmlSchemaValidator;
use Nfse\Client\SefinNacionalClient;
use Nfse\Client\DTO\EmitNfseRequest;
use Nfse\Client\DTO\Environment;
use Nfse\Client\Endpoints\SefinEndpointResolver;

$certificateProvider = new A1FileCertificateProvider('/caminho/certificado.pfx', 'senha');
$signer = new OpenSslXmlSigner();

$signedXml = $signer->sign($dpsXml, $certificateProvider->getCertificate());

$client = new SefinNacionalClient(
    environment: Environment::PRODUCTION_RESTRICTED,
    endpointResolver: new SefinEndpointResolver(),
    transport: new CurlHttpTransport(),
    certificateProvider: $certificateProvider,
    xmlValidator: new DomXmlSchemaValidator(),
    emitSchemaPath: '/caminho/xsd/dps.xsd',
);

$response = $client->emit(new EmitNfseRequest($signedXml));
```

## Decisoes de arquitetura

`fiscal-core` nao conhece SEFIN, DPS, NF-e ou NFC-e. Cada cliente fiscal define seus DTOs, endpoints, protocolos e regras de negocio sobre a infraestrutura compartilhada.
