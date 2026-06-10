<?php

use Nettsite\NettMail\Core\Drivers\PhpMailDriver;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;

it('returns a failure result when the sendmail command is unavailable', function () {
    $driver = new PhpMailDriver(command: '/nonexistent/sendmail -bs');

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
        text: 'Hi',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->error)->not->toBeEmpty();
});
