<?php

namespace Nettsite\NettMail\Core\Domain\Tracking;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;

final readonly class TrackingEvent
{
    public function __construct(
        public string $sendToken,
        public EventType $type,
        public DateTimeImmutable $occurredAt,
        public ?string $linkHash = null,
        public ?string $url = null,
    ) {
    }
}
