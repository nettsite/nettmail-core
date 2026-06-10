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
     * A contact globally unsubscribed, hard-bounced, or marked as a
     * complaint is suppressed from all broadcast and marketing sends.
     * Operational transactional mail is exempt.
     */
    public function isSuppressed(bool $isOperationalTransactional = false): bool
    {
        if ($isOperationalTransactional) {
            return false;
        }

        if ($this->globalUnsubscribedAt !== null) {
            return true;
        }

        return $this->bounceType === BounceType::Hard || $this->bounceType === BounceType::Complaint;
    }
}
