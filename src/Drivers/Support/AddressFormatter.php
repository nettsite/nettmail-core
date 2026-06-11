<?php

namespace Nettsite\NettMail\Core\Drivers\Support;

use Nettsite\NettMail\Core\Mail\EmailAddress;

final class AddressFormatter
{
    /**
     * Format an address as an RFC 5322 mailbox string, e.g.
     * `"Some \"One\"" <user@example.com>` or `user@example.com`.
     */
    public static function format(EmailAddress $address): string
    {
        $name = self::sanitizeName($address->name);

        if ($name === null || $name === '') {
            return $address->email;
        }

        if (preg_match('/[,"<>()@:;\\\\\[\]]/', $name) === 1) {
            $name = '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $name).'"';
        }

        return "{$name} <{$address->email}>";
    }

    /**
     * Strip CR/LF from a display name, for structured (JSON) payloads
     * where header injection isn't a risk but newlines could still
     * corrupt the payload.
     */
    public static function sanitizeName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        return str_replace(["\r", "\n"], '', $name);
    }
}
