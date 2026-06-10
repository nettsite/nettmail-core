<?php

namespace Nettsite\NettMail\Core\Domain\Templates;

use RuntimeException;

final class MissingUnsubscribeLinkException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Broadcast templates must include the unsubscribe link block.');
    }
}
