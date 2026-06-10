<?php

namespace Nettsite\NettMail\Core\Domain\Bounces;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;

final class BounceClassifier
{
    public function __construct(
        private readonly int $softBounceThreshold = 3,
    ) {
    }

    public function classifyStatusCode(string $statusCode): BounceType
    {
        return str_starts_with($statusCode, '5.') ? BounceType::Hard : BounceType::Soft;
    }

    public function recordEvent(Contact $contact, BounceType $type, DateTimeImmutable $at): void
    {
        match ($type) {
            BounceType::Hard => $this->recordHardBounce($contact, $at),
            BounceType::Soft => $this->recordSoftBounce($contact, $at),
            BounceType::Complaint => $this->recordComplaint($contact, $at),
        };
    }

    public function recordHardBounce(Contact $contact, DateTimeImmutable $at): void
    {
        $contact->bounceType = BounceType::Hard;
        $contact->bouncedAt = $at;
    }

    public function recordComplaint(Contact $contact, DateTimeImmutable $at): void
    {
        $contact->bounceType = BounceType::Complaint;
        $contact->bouncedAt = $at;
    }

    /**
     * After `softBounceThreshold` consecutive soft bounces, the contact is
     * escalated to a hard bounce and suppressed from future sends.
     */
    public function recordSoftBounce(Contact $contact, DateTimeImmutable $at): void
    {
        $contact->consecutiveSoftBounces++;

        if ($contact->consecutiveSoftBounces >= $this->softBounceThreshold) {
            $this->recordHardBounce($contact, $at);

            return;
        }

        $contact->bounceType = BounceType::Soft;
        $contact->bouncedAt = $at;
    }

    public function recordSuccessfulDelivery(Contact $contact): void
    {
        $contact->consecutiveSoftBounces = 0;

        if ($contact->bounceType === BounceType::Soft) {
            $contact->bounceType = null;
            $contact->bouncedAt = null;
        }
    }
}
