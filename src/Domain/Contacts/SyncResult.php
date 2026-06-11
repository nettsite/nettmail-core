<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

final readonly class SyncResult
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skippedInvalid = 0,
    ) {
    }
}
