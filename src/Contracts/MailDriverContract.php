<?php

namespace Nettsite\NettMail\Core\Contracts;

use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;

interface MailDriverContract
{
    public function send(EmailMessage $message): SendResult;
}
