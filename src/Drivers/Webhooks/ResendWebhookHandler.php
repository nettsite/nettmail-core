<?php

namespace Nettsite\NettMail\Core\Drivers\Webhooks;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Contracts\WebhookHandlerContract;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Domain\Webhooks\NormalizedEvent;

/**
 * Resend signs webhooks using Svix (svix-id, svix-timestamp, svix-signature).
 */
final class ResendWebhookHandler implements WebhookHandlerContract
{
    private const TYPE_MAP = [
        'email.sent' => EventType::Sent,
        'email.delivered' => EventType::Delivered,
        'email.opened' => EventType::Opened,
        'email.clicked' => EventType::Clicked,
        'email.bounced' => EventType::HardBounced,
        'email.complained' => EventType::Complained,
    ];

    public function __construct(
        private readonly int $timestampToleranceSeconds = 300,
    ) {
    }

    public function verify(string $rawBody, array $headers, string $secret): bool
    {
        $id = $headers['svix-id'] ?? null;
        $timestamp = $headers['svix-timestamp'] ?? null;
        $signatureHeader = $headers['svix-signature'] ?? null;

        if ($id === null || $timestamp === null || $signatureHeader === null) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > $this->timestampToleranceSeconds) {
            return false;
        }

        $secretBytes = base64_decode(preg_replace('/^whsec_/', '', $secret));
        $expected = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$rawBody}", $secretBytes, true));

        foreach (explode(' ', $signatureHeader) as $candidate) {
            $value = str_starts_with($candidate, 'v1,') ? substr($candidate, 3) : $candidate;

            if (hash_equals($expected, $value)) {
                return true;
            }
        }

        return false;
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
            providerMessageId: $data['email_id'] ?? null,
            occurredAt: new DateTimeImmutable($payload['created_at'] ?? 'now'),
            rawPayload: $payload,
        )];
    }
}
