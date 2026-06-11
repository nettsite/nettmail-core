<?php

use Nettsite\NettMail\Core\Drivers\ResendDriver;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Tests\Fakes\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

it('throws when an attachment file does not exist', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => 'resend-123'])));

    $driver = new ResendDriver('re_test_key', $httpClient, $factory, $factory);

    $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
        attachments: [['path' => '/nonexistent/file.txt', 'name' => 'file.txt']],
    ));
})->throws(RuntimeException::class);

it('sends an email via the resend api', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => 'resend-123'])));

    $driver = new ResendDriver('re_test_key', $httpClient, $factory, $factory);

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com', 'Sender'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
        html: '<p>Hi</p>',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->messageId)->toBe('resend-123');

    $request = $httpClient->lastRequest;

    expect((string) $request->getUri())->toBe('https://api.resend.com/emails')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer re_test_key');

    $body = json_decode((string) $request->getBody(), true);

    expect($body['from'])->toBe('Sender <sender@example.com>')
        ->and($body['to'])->toBe(['recipient@example.com'])
        ->and($body['html'])->toBe('<p>Hi</p>');
});

it('returns a failure result on a resend api error', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(422, ['Content-Type' => 'application/json'], json_encode(['message' => 'Invalid from address'])));

    $driver = new ResendDriver('re_test_key', $httpClient, $factory, $factory);

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('Invalid from address');
});
