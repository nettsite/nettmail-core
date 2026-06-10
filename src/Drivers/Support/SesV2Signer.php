<?php

namespace Nettsite\NettMail\Core\Drivers\Support;

use DateTimeImmutable;

/**
 * Minimal AWS Signature Version 4 signer for SES v2 SendEmail requests
 * (single JSON POST, no query string).
 */
final readonly class SesV2Signer
{
    public function __construct(
        private string $accessKeyId,
        private string $secretAccessKey,
        private string $region,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function sign(string $method, string $host, string $path, string $body, DateTimeImmutable $now): array
    {
        $amzDate = $now->format('Ymd\THis\Z');
        $dateStamp = $now->format('Ymd');
        $payloadHash = hash('sha256', $body);

        $canonicalHeaders = "host:{$host}\nx-amz-content-sha256:{$payloadHash}\nx-amz-date:{$amzDate}\n";
        $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';

        $canonicalRequest = implode("\n", [$method, $path, '', $canonicalHeaders, $signedHeaders, $payloadHash]);

        $credentialScope = "{$dateStamp}/{$this->region}/ses/aws4_request";
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $amzDate, $credentialScope, hash('sha256', $canonicalRequest)]);

        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($dateStamp));

        return [
            'Authorization' => "AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}",
            'X-Amz-Date' => $amzDate,
            'X-Amz-Content-Sha256' => $payloadHash,
        ];
    }

    private function signingKey(string $dateStamp): string
    {
        $kDate = hash_hmac('sha256', $dateStamp, "AWS4{$this->secretAccessKey}", true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 'ses', $kRegion, true);

        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
}
