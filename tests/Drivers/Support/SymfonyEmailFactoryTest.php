<?php

use Nettsite\NettMail\Core\Drivers\Support\SymfonyEmailFactory;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;

it('builds a symfony email from an email message', function () {
    $message = new EmailMessage(
        from: new EmailAddress('sender@example.com', 'Sender'),
        to: [new EmailAddress('recipient@example.com', 'Recipient')],
        subject: 'Hello',
        html: '<p>Hi</p>',
        text: 'Hi',
        cc: [new EmailAddress('cc@example.com')],
        bcc: [new EmailAddress('bcc@example.com')],
        replyTo: new EmailAddress('reply@example.com'),
    );

    $email = SymfonyEmailFactory::make($message);

    expect($email->getFrom()[0]->getAddress())->toBe('sender@example.com')
        ->and($email->getFrom()[0]->getName())->toBe('Sender')
        ->and($email->getTo()[0]->getAddress())->toBe('recipient@example.com')
        ->and($email->getSubject())->toBe('Hello')
        ->and($email->getHtmlBody())->toBe('<p>Hi</p>')
        ->and($email->getTextBody())->toBe('Hi')
        ->and($email->getCc()[0]->getAddress())->toBe('cc@example.com')
        ->and($email->getBcc()[0]->getAddress())->toBe('bcc@example.com')
        ->and($email->getReplyTo()[0]->getAddress())->toBe('reply@example.com');
});
