<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

use DateTimeImmutable;

final class ListMembership
{
    /**
     * @param array<int, string> $tags
     */
    public function __construct(
        public string $contactId,
        public string $listId,
        public MembershipStatus $status = MembershipStatus::Subscribed,
        public array $tags = [],
        public ?DateTimeImmutable $subscribedAt = null,
    ) {
    }
}
