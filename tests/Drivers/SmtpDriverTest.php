<?php

use Nettsite\NettMail\Core\Drivers\SmtpDriver;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;

it('returns a failure result when the smtp connection fails', function () {
    $driver = new SmtpDriver(host: '127.0.0.1', port: 1);

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
        text: 'Hi',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->error)->not->toBeEmpty();
});
