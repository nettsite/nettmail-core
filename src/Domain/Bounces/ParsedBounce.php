<?php

namespace Nettsite\NettMail\Core\Domain\Bounces;

use Nettsite\NettMail\Core\Domain\Contacts\BounceType;

final readonly class ParsedBounce
{
    public function __construct(
        public string $recipient,
        public BounceType $bounceType,
        public ?string $statusCode = null,
    ) {
    }
}
