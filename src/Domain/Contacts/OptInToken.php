<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

use DateTimeImmutable;

final readonly class OptInToken
{
    public function __construct(
        public string $contactId,
        public string $listId,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
