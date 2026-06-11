<?php

use Nettsite\NettMail\Core\Drivers\MailersendDriver;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Tests\Fakes\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

it('throws when an attachment file does not exist', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(202, ['X-Message-Id' => 'ms-123']));

    $driver = new MailersendDriver('ms_test_key', $httpClient, $factory, $factory);

    $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
        attachments: [['path' => '/nonexistent/file.txt', 'name' => 'file.txt']],
    ));
})->throws(RuntimeException::class);

it('sends an email via the mailersend api', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(202, ['X-Message-Id' => 'ms-123']));

    $driver = new MailersendDriver('ms_test_key', $httpClient, $factory, $factory);

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com', 'Sender'),
        to: [new EmailAddress('recipient@example.com', 'Recipient')],
        subject: 'Hello',
        text: 'Hi',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->messageId)->toBe('ms-123');

    $body = json_decode((string) $httpClient->lastRequest->getBody(), true);

    expect($body['from'])->toBe(['email' => 'sender@example.com', 'name' => 'Sender'])
        ->and($body['to'])->toBe([['email' => 'recipient@example.com', 'name' => 'Recipient']])
        ->and($body['text'])->toBe('Hi');
});

it('returns a failure result on a mailersend api error', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(422, ['Content-Type' => 'application/json'], json_encode(['message' => 'Invalid recipient'])));

    $driver = new MailersendDriver('ms_test_key', $httpClient, $factory, $factory);

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('Invalid recipient');
});
