<?php

namespace Nettsite\NettMail\Core\Domain\Bounces;

final readonly class MailboxMessage
{
    public function __construct(
        public string $id,
        public string $rawContent,
    ) {
    }
}
