<?php

namespace Nettsite\NettMail\Core\Drivers\Webhooks;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Contracts\WebhookHandlerContract;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Domain\Webhooks\NormalizedEvent;

/**
 * Postmark does not sign webhook payloads. Verification instead checks a
 * shared secret token configured in the webhook URL/header. If no secret
 * is configured, verification is skipped.
 */
final class PostmarkWebhookHandler implements WebhookHandlerContract
{
    public function verify(string $rawBody, array $headers, string $secret): bool
    {
        if ($secret === '') {
            return true;
        }

        $token = $headers['x-nettmail-webhook-token'] ?? null;

        return $token !== null && hash_equals($secret, $token);
    }

    public function parse(array $payload): array
    {
        $recordType = $payload['RecordType'] ?? null;

        $type = match ($recordType) {
            'Delivery' => EventType::Delivered,
            'Open' => EventType::Opened,
            'Click' => EventType::Clicked,
            'SpamComplaint' => EventType::Complained,
            'Bounce' => ($payload['Type'] ?? null) === 'HardBounce' ? EventType::HardBounced : EventType::SoftBounced,
            default => null,
        };

        if ($type === null) {
            return [];
        }

        $occurredAt = $payload['DeliveredAt']
            ?? $payload['BouncedAt']
            ?? $payload['ReceivedAt']
            ?? 'now';

        return [new NormalizedEvent(
            type: $type,
            providerMessageId: $payload['MessageID'] ?? null,
            occurredAt: new DateTimeImmutable($occurredAt),
            rawPayload: $payload,
        )];
    }
}
