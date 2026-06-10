<?php

use Nettsite\NettMail\Core\Drivers\MailgunDriver;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Tests\Fakes\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

it('sends an email via the mailgun api', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => 'mg-123', 'message' => 'Queued'])));

    $driver = new MailgunDriver('key-test', 'mg.example.com', $httpClient, $factory, $factory);

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com', 'Sender'),
        to: [new EmailAddress('recipient@example.com', 'Recipient')],
        subject: 'Hello',
        html: '<p>Hi</p>',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->messageId)->toBe('mg-123');

    $request = $httpClient->lastRequest;

    expect($request->getUri()->__toString())->toBe('https://api.mailgun.net/v3/mg.example.com/messages')
        ->and($request->getHeaderLine('Authorization'))->toBe('Basic '.base64_encode('api:key-test'));

    $body = (string) $request->getBody();

    expect($body)->toContain('name="from"')
        ->and($body)->toContain('Sender <sender@example.com>')
        ->and($body)->toContain('name="to"')
        ->and($body)->toContain('recipient@example.com')
        ->and($body)->toContain('name="html"')
        ->and($body)->toContain('<p>Hi</p>');
});

it('returns a failure result on a mailgun api error', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(400, ['Content-Type' => 'application/json'], json_encode(['message' => 'Invalid recipient'])));

    $driver = new MailgunDriver('key-test', 'mg.example.com', $httpClient, $factory, $factory);

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('Invalid recipient');
});
