<?php

namespace Nettsite\NettMail\Core\Domain\Tracking;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;

/**
 * Builds tracking event records from pixel requests and click redirects.
 * Persistence is left to the storage adapter.
 */
final class EventRecorder
{
    public function recordOpen(string $sendToken, ?DateTimeImmutable $at = null): TrackingEvent
    {
        return new TrackingEvent($sendToken, EventType::Opened, $at ?? new DateTimeImmutable());
    }

    public function recordClick(string $sendToken, string $linkHash, string $url, ?DateTimeImmutable $at = null): TrackingEvent
    {
        return new TrackingEvent($sendToken, EventType::Clicked, $at ?? new DateTimeImmutable(), linkHash: $linkHash, url: $url);
    }

    /**
     * The first-open timestamp wins regardless of source (pixel or
     * provider webhook) — so `opened_at` is only stamped once.
     */
    public function isFirstOpen(?DateTimeImmutable $existingOpenedAt): bool
    {
        return $existingOpenedAt === null;
    }
}
