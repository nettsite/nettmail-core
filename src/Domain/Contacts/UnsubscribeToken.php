<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

final readonly class UnsubscribeToken
{
    public function __construct(
        public string $contactId,
        public ?string $listId,
    ) {
    }
}
