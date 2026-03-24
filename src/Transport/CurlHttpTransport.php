<?php

declare(strict_types=1);

namespace Nfse\Core\Transport;

use CurlHandle;
use Nfse\Core\Contracts\HttpTransportInterface;
use Nfse\Core\Exceptions\TransportException;
use Nfse\Core\Security\Certificate;

final class CurlHttpTransport implements HttpTransportInterface
{
    public function send(HttpRequest $request, ?Certificate $certificate = null): HttpResponse
    {
        $headers = [];
        $headerLines = [];

        foreach ($request->headers() as $key => $value) {
            $headerLines[] = sprintf('%s: %s', $key, $value);
        }

        $ch = curl_init($request->url());

        if (!$ch instanceof CurlHandle) {
            throw new TransportException('Could not initialize cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($request->method()),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $request->timeoutSeconds(),
            CURLOPT_TIMEOUT => $request->timeoutSeconds(),
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
                $trimmed = trim($line);
                if ($trimmed === '' || !str_contains($trimmed, ':')) {
                    return strlen($line);
                }

                [$name, $value] = array_map('trim', explode(':', $trimmed, 2));
                $normalized = strtolower($name);
                $headers[$normalized] ??= [];
                $headers[$normalized][] = $value;

                return strlen($line);
            },
        ]);

        if ($request->body() !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->body());
        }

        if (strtoupper($request->method()) === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        if ($certificate !== null) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
            curl_setopt($ch, CURLOPT_SSLCERT, $certificate->pkcs12Path());
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certificate->passphrase());
        }

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new TransportException(sprintf('cURL error [%d]: %s', $errno, $error));
        }

        if (!is_string($body)) {
            $body = '';
        }

        return new HttpResponse($statusCode, $headers, $body);
    }
}
