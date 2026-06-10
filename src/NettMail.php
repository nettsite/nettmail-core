<?php

namespace Nettsite\NettMail\Core;

use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;

final class NettMail
{
    public function __construct(
        private readonly MailDriverContract $driver,
    ) {
    }

    public function send(EmailMessage $message): SendResult
    {
        return $this->driver->send($message);
    }
}
