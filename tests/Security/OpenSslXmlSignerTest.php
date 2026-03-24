<?php

declare(strict_types=1);

namespace Nfse\Core\Tests\Security;

use Nfse\Core\Exceptions\SignatureException;
use Nfse\Core\Security\A1FileCertificateProvider;
use Nfse\Core\Security\OpenSslXmlSigner;
use PHPUnit\Framework\TestCase;

final class OpenSslXmlSignerTest extends TestCase
{
    private string $pkcs12Path;
    private string $passphrase;
    private string $certificatePem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passphrase = 'test-passphrase';
        $this->pkcs12Path = sys_get_temp_dir() . '/nfse-core-test-' . bin2hex(random_bytes(6)) . '.pfx';
        $this->certificatePem = $this->generateTemporaryPkcs12($this->pkcs12Path, $this->passphrase);
    }

    protected function tearDown(): void
    {
        if (is_file($this->pkcs12Path)) {
            @unlink($this->pkcs12Path);
        }

        parent::tearDown();
    }

    public function testSignsXmlWithEnvelopedSignature(): void
    {
        $xml = '<DPS Id="DPS123"><InfDPS><ValorServico>100.00</ValorServico></InfDPS></DPS>';
        $provider = new A1FileCertificateProvider($this->pkcs12Path, $this->passphrase);
        $signer = new OpenSslXmlSigner();

        $signed = $signer->sign($xml, $provider->getCertificate());

        $this->assertStringContainsString('<ds:Signature', $signed);
        $this->assertStringContainsString('<ds:SignedInfo>', $signed);
        $this->assertStringContainsString('<ds:DigestValue>', $signed);
        $this->assertStringContainsString('<ds:SignatureValue>', $signed);
        $this->assertStringContainsString('<ds:X509Certificate>', $signed);
        $this->assertTrue($this->verifySignatureValue($signed, $this->certificatePem));
    }

    public function testThrowsForInvalidXml(): void
    {
        $provider = new A1FileCertificateProvider($this->pkcs12Path, $this->passphrase);
        $signer = new OpenSslXmlSigner();

        $this->expectException(SignatureException::class);

        $signer->sign('<DPS>', $provider->getCertificate());
    }

    private function generateTemporaryPkcs12(string $outputPath, string $passphrase): string
    {
        $privateKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        $this->assertNotFalse($privateKey);

        $csr = openssl_csr_new(['commonName' => 'Nfse Core Test'], $privateKey, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($csr);

        $certificate = openssl_csr_sign($csr, null, $privateKey, 1, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($certificate);

        $this->assertTrue(openssl_x509_export($certificate, $certificatePem));
        $this->assertTrue(openssl_pkcs12_export_to_file($certificate, $outputPath, $privateKey, $passphrase));

        return $certificatePem;
    }

    private function verifySignatureValue(string $signedXml, string $certificatePem): bool
    {
        $document = new \DOMDocument();
        $document->loadXML($signedXml);

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $signedInfo = $xpath->query('//ds:Signature/ds:SignedInfo')->item(0);
        $signatureValueNode = $xpath->query('//ds:Signature/ds:SignatureValue')->item(0);

        if (!$signedInfo instanceof \DOMElement || !$signatureValueNode instanceof \DOMElement) {
            return false;
        }

        $canonicalSignedInfo = $signedInfo->C14N(true, false);
        if (!is_string($canonicalSignedInfo)) {
            return false;
        }

        $signatureRaw = base64_decode(trim($signatureValueNode->textContent), true);
        if (!is_string($signatureRaw)) {
            return false;
        }

        $publicKey = openssl_pkey_get_public($certificatePem);
        if ($publicKey === false) {
            return false;
        }

        return openssl_verify($canonicalSignedInfo, $signatureRaw, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }
}
