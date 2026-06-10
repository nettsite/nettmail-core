<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

final class MailingList
{
    public function __construct(
        public ?string $id,
        public string $name,
        public string $slug,
        public ?string $description = null,
        public bool $doubleOptin = false,
        public ?string $senderId = null,
    ) {
    }
}
