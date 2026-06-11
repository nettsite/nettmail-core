<?php

namespace Nettsite\NettMail\Core\Drivers\Support;

use RuntimeException;

final class AttachmentReader
{
    public static function read(string $path): string
    {
        if (! is_readable($path)) {
            throw new RuntimeException("Unable to read attachment file: {$path}");
        }

        return file_get_contents($path);
    }
}
