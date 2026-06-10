<?php

namespace Nettsite\NettMail\Core\Contracts;

use Nettsite\NettMail\Core\Domain\Bounces\ParsedBounce;

interface BounceParserContract
{
    /**
     * Parses a raw bounce/DSN message, returning null if no recipient
     * could be identified (the message should be treated as unrecognised).
     */
    public function parse(string $rawMessage): ?ParsedBounce;
}
