<?php

namespace Nettsite\NettMail\Core\Drivers\Support;

use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class SymfonyEmailFactory
{
    public static function make(EmailMessage $message): Email
    {
        $email = (new Email())
            ->from(self::address($message->from))
            ->to(...array_map(self::address(...), $message->to))
            ->subject($message->subject);

        if ($message->cc !== []) {
            $email->cc(...array_map(self::address(...), $message->cc));
        }

        if ($message->bcc !== []) {
            $email->bcc(...array_map(self::address(...), $message->bcc));
        }

        if ($message->replyTo !== null) {
            $email->replyTo(self::address($message->replyTo));
        }

        if ($message->html !== null) {
            $email->html($message->html);
        }

        if ($message->text !== null) {
            $email->text($message->text);
        }

        foreach ($message->attachments as $attachment) {
            $email->attachFromPath($attachment['path'], $attachment['name']);
        }

        return $email;
    }

    private static function address(EmailAddress $address): Address
    {
        return new Address($address->email, $address->name ?? '');
    }
}
