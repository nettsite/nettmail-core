<?php

namespace Nettsite\NettMail\Core\Domain\Bounces;

final readonly class BouncePollResult
{
    public function __construct(
        public int $processed,
        public int $unrecognised,
    ) {
    }
}
