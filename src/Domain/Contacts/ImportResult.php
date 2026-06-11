<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

final readonly class ImportResult
{
    /**
     * @param array<int, string> $errors
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $invalid = 0,
        public array $errors = [],
    ) {
    }
}
