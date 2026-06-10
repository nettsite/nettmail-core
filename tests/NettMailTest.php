<?php

use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\NettMail;
use Nettsite\NettMail\Core\Tests\Fakes\FakeMailDriver;

it('delegates sending to the configured driver', function () {
    $driver = new FakeMailDriver();
    $nettmail = new NettMail($driver);

    $message = new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
    );

    $result = $nettmail->send($message);

    expect($result->success)->toBeTrue()
        ->and($result->messageId)->toBe('fake-message-id')
        ->and($driver->lastMessage)->toBe($message);
});
