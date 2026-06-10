<?php

use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;

it('holds message data with sensible defaults', function () {
    $message = new EmailMessage(
        from: new EmailAddress('sender@example.com', 'Sender'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
        html: '<p>Hello</p>',
    );

    expect($message->from->email)->toBe('sender@example.com')
        ->and($message->to)->toHaveCount(1)
        ->and($message->subject)->toBe('Hello')
        ->and($message->html)->toBe('<p>Hello</p>')
        ->and($message->text)->toBeNull()
        ->and($message->cc)->toBe([])
        ->and($message->bcc)->toBe([])
        ->and($message->attachments)->toBe([]);
});
