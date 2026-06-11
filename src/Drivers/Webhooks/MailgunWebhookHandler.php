<?php

namespace Nettsite\NettMail\Core\Drivers\Webhooks;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Contracts\WebhookHandlerContract;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Domain\Webhooks\NormalizedEvent;
use Nettsite\NettMail\Core\Drivers\Support\MessageIdNormalizer;

/**
 * Mailgun signs webhooks via a `signature` object included in the JSON
 * body: HMAC-SHA256 of `{timestamp}{token}` using the webhook signing key.
 */
final class MailgunWebhookHandler implements WebhookHandlerContract
{
    public function __construct(
        private readonly int $timestampToleranceSeconds = 300,
    ) {
    }

    public function verify(string $rawBody, array $headers, string $secret): bool
    {
        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($rawBody, true);
        $signature = $payload['signature'] ?? null;

        if (! is_array($signature)) {
            return false;
        }

        $timestamp = $signature['timestamp'] ?? null;
        $token = $signature['token'] ?? null;
        $sig = $signature['signature'] ?? null;

        if (! is_string($timestamp) || ! is_string($token) || ! is_string($sig)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > $this->timestampToleranceSeconds) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.$token, $secret);

        return hash_equals($expected, $sig);
    }

    public function parse(array $payload): array
    {
        $eventData = $payload['event-data'] ?? [];
        $event = $eventData['event'] ?? null;

        $type = match ($event) {
            'delivered' => EventType::Delivered,
            'opened' => EventType::Opened,
            'clicked' => EventType::Clicked,
            'complained' => EventType::Complained,
            'unsubscribed' => EventType::Unsubscribed,
            'failed' => ($eventData['severity'] ?? null) === 'permanent' ? EventType::HardBounced : EventType::SoftBounced,
            default => null,
        };

        if ($type === null) {
            return [];
        }

        return [new NormalizedEvent(
            type: $type,
            providerMessageId: MessageIdNormalizer::strip($eventData['message']['headers']['message-id'] ?? null),
            occurredAt: (new DateTimeImmutable())->setTimestamp((int) ($eventData['timestamp'] ?? time())),
            rawPayload: $payload,
        )];
    }
}
