<?php

namespace Nettsite\NettMail\Core\Drivers;

use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Drivers\Support\SymfonyEmailFactory;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\SendmailTransport;

final class PhpMailDriver implements MailDriverContract
{
    private readonly SendmailTransport $transport;

    public function __construct(string $command = '/usr/sbin/sendmail -bs')
    {
        $this->transport = new SendmailTransport($command);
    }

    public function send(EmailMessage $message): SendResult
    {
        $email = SymfonyEmailFactory::make($message);

        try {
            $sentMessage = $this->transport->send($email);
        } catch (TransportExceptionInterface $e) {
            return SendResult::failure($e->getMessage());
        }

        return SendResult::success($sentMessage?->getMessageId() ?? '');
    }
}
