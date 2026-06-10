<?php

namespace Nettsite\NettMail\Core\Drivers;

use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Drivers\Support\SymfonyEmailFactory;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Mail\SendResult;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class SmtpDriver implements MailDriverContract
{
    private readonly TransportInterface $transport;

    public function __construct(
        string $host,
        int $port,
        ?string $username = null,
        ?string $password = null,
        string $encryption = 'tls',
    ) {
        $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';

        $auth = $username !== null
            ? rawurlencode($username).($password !== null ? ':'.rawurlencode($password) : '').'@'
            : '';

        $this->transport = Transport::fromDsn("{$scheme}://{$auth}{$host}:{$port}");
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
