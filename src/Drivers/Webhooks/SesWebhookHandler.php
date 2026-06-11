<?php

namespace Nettsite\NettMail\Core\Drivers\Webhooks;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Contracts\WebhookHandlerContract;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Domain\Webhooks\NormalizedEvent;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * SES delivers events via SNS notifications. Verification checks the
 * notification's `TopicArn` against the configured secret, and — when an
 * HTTP client is supplied — verifies the SNS message signature against the
 * certificate at `SigningCertURL`.
 */
final class SesWebhookHandler implements WebhookHandlerContract
{
    /** @var array<string, string> */
    private array $certCache = [];

    public function __construct(
        private readonly ?ClientInterface $httpClient = null,
        private readonly ?RequestFactoryInterface $requestFactory = null,
    ) {
    }

    public function verify(string $rawBody, array $headers, string $secret): bool
    {
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return false;
        }

        if ($secret !== '' && ($payload['TopicArn'] ?? null) !== $secret) {
            return false;
        }

        if ($this->httpClient === null || $this->requestFactory === null) {
            return true;
        }

        return $this->verifySignature($payload);
    }

    public function parse(array $payload): array
    {
        $message = json_decode($payload['Message'] ?? '{}', true) ?? [];
        $eventType = $message['eventType'] ?? $message['notificationType'] ?? null;

        $type = match ($eventType) {
            'Send' => EventType::Sent,
            'Delivery' => EventType::Delivered,
            'Open' => EventType::Opened,
            'Click' => EventType::Clicked,
            'Bounce' => ($message['bounce']['bounceType'] ?? null) === 'Permanent' ? EventType::HardBounced : EventType::SoftBounced,
            'Complaint' => EventType::Complained,
            default => null,
        };

        if ($type === null) {
            return [];
        }

        return [new NormalizedEvent(
            type: $type,
            providerMessageId: $message['mail']['messageId'] ?? null,
            occurredAt: new DateTimeImmutable($message['mail']['timestamp'] ?? 'now'),
            rawPayload: $payload,
        )];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function verifySignature(array $payload): bool
    {
        $certUrl = $payload['SigningCertURL'] ?? null;
        $signature = $payload['Signature'] ?? null;

        if (! is_string($certUrl) || ! is_string($signature)) {
            return false;
        }

        if (preg_match('#^https://sns\.[a-z0-9-]+\.amazonaws\.com/#', $certUrl) !== 1) {
            return false;
        }

        if (! isset($this->certCache[$certUrl])) {
            $request = $this->requestFactory->createRequest('GET', $certUrl);
            $response = $this->httpClient->sendRequest($request);
            $this->certCache[$certUrl] = (string) $response->getBody();
        }

        $cert = $this->certCache[$certUrl];

        $signableKeys = ($payload['Type'] ?? null) === 'Notification'
            ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
            : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

        $signableString = '';

        foreach ($signableKeys as $key) {
            if (! isset($payload[$key])) {
                continue;
            }

            $signableString .= "{$key}\n{$payload[$key]}\n";
        }

        $publicKey = openssl_pkey_get_public($cert);

        if ($publicKey === false) {
            return false;
        }

        $algo = ($payload['SignatureVersion'] ?? '1') === '2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;

        return openssl_verify($signableString, base64_decode($signature), $publicKey, $algo) === 1;
    }
}
