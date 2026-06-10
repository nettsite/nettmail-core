<?php

namespace Nettsite\NettMail\Core\Mail;

final readonly class EmailAddress
{
    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {
    }
}
