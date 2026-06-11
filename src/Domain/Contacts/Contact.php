<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

use DateTimeImmutable;

final class Contact
{
    public string $email;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public ?string $id,
        string $email,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $phone = null,
        public array $metadata = [],
        public ?string $sourceType = null,
        public ?string $sourceId = null,
        public ?DateTimeImmutable $globalUnsubscribedAt = null,
        public ?DateTimeImmutable $bouncedAt = null,
        public ?BounceType $bounceType = null,
        public int $consecutiveSoftBounces = 0,
    ) {
        $this->email = EmailNormalizer::normalize($email);
    }

    /**
     * A complaint suppresses all mail, including operational transactional
     * mail — ISP agreements don't carve out exemptions for complainers.
     *
     * A contact globally unsubscribed or hard-bounced is suppressed from
     * all broadcast and marketing sends. Operational transactional mail
     * is exempt from those two.
     */
    public function isSuppressed(bool $isOperationalTransactional = false): bool
    {
        if ($this->bounceType === BounceType::Complaint) {
            return true;
        }

        if ($isOperationalTransactional) {
            return false;
        }

        if ($this->globalUnsubscribedAt !== null) {
            return true;
        }

        return $this->bounceType === BounceType::Hard;
    }
}
