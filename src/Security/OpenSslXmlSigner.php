<?php

declare(strict_types=1);

namespace Nfse\Core\Security;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Nfse\Core\Contracts\XmlSignerInterface;
use Nfse\Core\Exceptions\SignatureException;

final class OpenSslXmlSigner implements XmlSignerInterface
{
    private const XMLDSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';
    private const ALGO_C14N_EXCLUSIVE = 'http://www.w3.org/2001/10/xml-exc-c14n#';
    private const ALGO_RSA_SHA256 = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    private const ALGO_SHA256 = 'http://www.w3.org/2001/04/xmlenc#sha256';

    public function __construct(
        private readonly ?string $targetElementId = null
    ) {
    }

    public function sign(string $xml, Certificate $certificate): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        $loaded = @$document->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOCDATA);

        if ($loaded === false || $document->documentElement === null) {
            throw new SignatureException('Invalid XML payload for signing.');
        }

        [$privateKey, $certificatePem] = $this->readPkcs12($certificate);
        $targetElement = $this->resolveTargetElement($document);
        $referenceUri = $this->resolveReferenceUri($targetElement);
        $digestValue = $this->createDigestValue($targetElement);
        $signatureElement = $this->buildSignatureElement($document, $referenceUri, $digestValue, $privateKey, $certificatePem);

        $targetElement->appendChild($signatureElement);

        $signedXml = $document->saveXML();

        if ($signedXml === false) {
            throw new SignatureException('Could not generate signed XML.');
        }

        return $signedXml;
    }

    /**
     * @return array{0: mixed, 1: string}
     */
    private function readPkcs12(Certificate $certificate): array
    {
        $content = file_get_contents($certificate->pkcs12Path());

        if (!is_string($content)) {
            throw new SignatureException('Could not read PFX certificate file.');
        }

        $parsed = openssl_pkcs12_read($content, $certificates, $certificate->passphrase());

        if ($parsed !== true) {
            throw new SignatureException('Could not parse PFX certificate. Check passphrase.');
        }

        if (!isset($certificates['pkey']) || !is_string($certificates['pkey']) || trim($certificates['pkey']) === '') {
            throw new SignatureException('Private key not found in PFX certificate.');
        }

        if (!isset($certificates['cert']) || !is_string($certificates['cert']) || trim($certificates['cert']) === '') {
            throw new SignatureException('X509 certificate not found in PFX certificate.');
        }

        $privateKey = openssl_pkey_get_private($certificates['pkey']);

        if ($privateKey === false) {
            throw new SignatureException('Could not load private key from PFX certificate.');
        }

        return [$privateKey, $certificates['cert']];
    }

    private function resolveTargetElement(DOMDocument $document): DOMElement
    {
        if ($this->targetElementId === null || trim($this->targetElementId) === '') {
            return $document->documentElement;
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query(sprintf("//*[@Id='%s' or @id='%s']", $this->targetElementId, $this->targetElementId));

        if ($nodes === false || $nodes->length === 0 || !$nodes->item(0) instanceof DOMElement) {
            throw new SignatureException(sprintf('Target element with Id/id "%s" not found in XML.', $this->targetElementId));
        }

        return $nodes->item(0);
    }

    private function resolveReferenceUri(DOMElement $targetElement): string
    {
        $id = $targetElement->getAttribute('Id');
        if ($id === '') {
            $id = $targetElement->getAttribute('id');
        }

        if ($id === '') {
            return '';
        }

        return '#' . $id;
    }

    private function createDigestValue(DOMElement $targetElement): string
    {
        $canonical = $this->canonicalizeWithoutSignature($targetElement);
        return base64_encode(hash('sha256', $canonical, true));
    }

    private function canonicalizeWithoutSignature(DOMElement $targetElement): string
    {
        $tempDocument = new DOMDocument('1.0', 'UTF-8');
        $tempDocument->preserveWhiteSpace = false;
        $tempDocument->formatOutput = false;

        $importedTarget = $tempDocument->importNode($targetElement, true);

        if (!$importedTarget instanceof DOMNode) {
            throw new SignatureException('Could not clone target XML element for canonicalization.');
        }

        $tempDocument->appendChild($importedTarget);

        $xpath = new DOMXPath($tempDocument);
        $xpath->registerNamespace('ds', self::XMLDSIG_NS);
        $signatureNodes = $xpath->query('.//ds:Signature', $tempDocument->documentElement);

        if ($signatureNodes !== false) {
            for ($index = $signatureNodes->length - 1; $index >= 0; $index--) {
                $signatureNode = $signatureNodes->item($index);
                if ($signatureNode !== null && $signatureNode->parentNode !== null) {
                    $signatureNode->parentNode->removeChild($signatureNode);
                }
            }
        }

        $canonical = $tempDocument->documentElement?->C14N(true, false);

        if (!is_string($canonical)) {
            throw new SignatureException('Could not canonicalize target XML element.');
        }

        return $canonical;
    }

    /**
     * @param mixed $privateKey
     */
    private function buildSignatureElement(
        DOMDocument $document,
        string $referenceUri,
        string $digestValue,
        $privateKey,
        string $certificatePem
    ): DOMElement {
        $signature = $document->createElementNS(self::XMLDSIG_NS, 'ds:Signature');
        $signedInfo = $document->createElementNS(self::XMLDSIG_NS, 'ds:SignedInfo');
        $canonicalizationMethod = $document->createElementNS(self::XMLDSIG_NS, 'ds:CanonicalizationMethod');
        $canonicalizationMethod->setAttribute('Algorithm', self::ALGO_C14N_EXCLUSIVE);
        $signatureMethod = $document->createElementNS(self::XMLDSIG_NS, 'ds:SignatureMethod');
        $signatureMethod->setAttribute('Algorithm', self::ALGO_RSA_SHA256);
        $reference = $document->createElementNS(self::XMLDSIG_NS, 'ds:Reference');
        $reference->setAttribute('URI', $referenceUri);

        $transforms = $document->createElementNS(self::XMLDSIG_NS, 'ds:Transforms');
        $transformEnveloped = $document->createElementNS(self::XMLDSIG_NS, 'ds:Transform');
        $transformEnveloped->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $transformC14n = $document->createElementNS(self::XMLDSIG_NS, 'ds:Transform');
        $transformC14n->setAttribute('Algorithm', self::ALGO_C14N_EXCLUSIVE);
        $transforms->appendChild($transformEnveloped);
        $transforms->appendChild($transformC14n);

        $digestMethod = $document->createElementNS(self::XMLDSIG_NS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', self::ALGO_SHA256);
        $digestValueNode = $document->createElementNS(self::XMLDSIG_NS, 'ds:DigestValue', $digestValue);

        $reference->appendChild($transforms);
        $reference->appendChild($digestMethod);
        $reference->appendChild($digestValueNode);

        $signedInfo->appendChild($canonicalizationMethod);
        $signedInfo->appendChild($signatureMethod);
        $signedInfo->appendChild($reference);
        $signature->appendChild($signedInfo);

        $canonicalSignedInfo = $this->canonicalizeSignedInfo($signature);
        if (!is_string($canonicalSignedInfo)) {
            throw new SignatureException('Could not canonicalize SignedInfo.');
        }

        $signed = openssl_sign($canonicalSignedInfo, $signatureRawValue, $privateKey, OPENSSL_ALGO_SHA256);
        if ($signed !== true || !is_string($signatureRawValue)) {
            throw new SignatureException('Could not create XML signature with private key.');
        }

        $signatureValue = $document->createElementNS(
            self::XMLDSIG_NS,
            'ds:SignatureValue',
            base64_encode($signatureRawValue)
        );

        $keyInfo = $document->createElementNS(self::XMLDSIG_NS, 'ds:KeyInfo');
        $x509Data = $document->createElementNS(self::XMLDSIG_NS, 'ds:X509Data');
        $x509Certificate = $document->createElementNS(
            self::XMLDSIG_NS,
            'ds:X509Certificate',
            $this->stripPemBoundaries($certificatePem)
        );

        $x509Data->appendChild($x509Certificate);
        $keyInfo->appendChild($x509Data);

        $signature->appendChild($signatureValue);
        $signature->appendChild($keyInfo);

        return $signature;
    }

    private function canonicalizeSignedInfo(DOMElement $signature): string
    {
        $temporary = new DOMDocument('1.0', 'UTF-8');
        $temporary->preserveWhiteSpace = false;
        $temporary->formatOutput = false;

        $importedSignature = $temporary->importNode($signature, true);
        $temporary->appendChild($importedSignature);

        $xpath = new DOMXPath($temporary);
        $xpath->registerNamespace('ds', self::XMLDSIG_NS);
        $signedInfo = $xpath->query('/ds:Signature/ds:SignedInfo')->item(0);

        if (!$signedInfo instanceof DOMElement) {
            throw new SignatureException('Could not access SignedInfo for canonicalization.');
        }

        $canonical = $signedInfo->C14N(true, false);
        if (!is_string($canonical)) {
            throw new SignatureException('Could not canonicalize SignedInfo.');
        }

        return $canonical;
    }

    private function stripPemBoundaries(string $certificatePem): string
    {
        $clean = str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' '],
            '',
            $certificatePem
        );

        return trim($clean);
    }
}
