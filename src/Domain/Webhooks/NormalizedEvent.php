<?php

namespace Nettsite\NettMail\Core\Domain\Webhooks;

use DateTimeImmutable;

final readonly class NormalizedEvent
{
    /**
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public EventType $type,
        public ?string $providerMessageId,
        public DateTimeImmutable $occurredAt,
        public array $rawPayload,
    ) {
    }
}
