<?php

namespace Nettsite\NettMail\Core\Drivers\Webhooks;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Contracts\WebhookHandlerContract;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Domain\Webhooks\NormalizedEvent;

/**
 * Mailersend signs webhooks with a hex HMAC-SHA256 of the raw body in the
 * `Signature` header.
 */
final class MailersendWebhookHandler implements WebhookHandlerContract
{
    private const TYPE_MAP = [
        'activity.sent' => EventType::Sent,
        'activity.delivered' => EventType::Delivered,
        'activity.opened' => EventType::Opened,
        'activity.clicked' => EventType::Clicked,
        'activity.hard_bounced' => EventType::HardBounced,
        'activity.soft_bounced' => EventType::SoftBounced,
        'activity.unsubscribed' => EventType::Unsubscribed,
        'activity.spam_complaint' => EventType::Complained,
    ];

    public function verify(string $rawBody, array $headers, string $secret): bool
    {
        $signature = $headers['signature'] ?? null;

        if ($signature === null) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    public function parse(array $payload): array
    {
        $type = self::TYPE_MAP[$payload['type'] ?? ''] ?? null;

        if ($type === null) {
            return [];
        }

        $data = $payload['data'] ?? [];

        return [new NormalizedEvent(
            type: $type,
            providerMessageId: $data['email']['message']['id'] ?? null,
            occurredAt: new DateTimeImmutable($payload['created_at'] ?? 'now'),
            rawPayload: $payload,
        )];
    }
}
