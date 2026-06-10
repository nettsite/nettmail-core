<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

final class EmailNormalizer
{
    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }
}
