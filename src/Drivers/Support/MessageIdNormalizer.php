<?php

namespace Nettsite\NettMail\Core\Drivers\Support;

final class MessageIdNormalizer
{
    public static function strip(?string $id): ?string
    {
        return $id === null ? null : trim($id, '<>');
    }
}
