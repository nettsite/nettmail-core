<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

use DateTimeImmutable;

final readonly class Suppression
{
    public function __construct(
        public string $email,
        public SuppressionReason $reason,
        public DateTimeImmutable $suppressedAt,
    ) {
    }
}
