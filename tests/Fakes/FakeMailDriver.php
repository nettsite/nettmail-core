<?php

namespace Nettsite\NettMail\Core\Tests\Fakes;

use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;

final class FakeMailDriver implements MailDriverContract
{
    public ?EmailMessage $lastMessage = null;

    public function __construct(
        private readonly SendResult $result = new SendResult(success: true, messageId: 'fake-message-id'),
    ) {
    }

    public function send(EmailMessage $message): SendResult
    {
        $this->lastMessage = $message;

        return $this->result;
    }
}
