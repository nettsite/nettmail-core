<?php

use Nettsite\NettMail\Core\Drivers\SesDriver;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\Tests\Fakes\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

it('sends an email via the ses v2 api with a signed request', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(200, ['Content-Type' => 'application/json'], json_encode(['MessageId' => 'ses-123'])));

    $driver = new SesDriver('AKIA_TEST', 'secret', 'us-east-1', $httpClient, $factory, $factory);

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com', 'Sender'),
        to: [new EmailAddress('recipient@example.com', 'Recipient')],
        subject: 'Hello',
        html: '<p>Hi</p>',
        text: 'Hi',
    ));

    expect($result->success)->toBeTrue()
        ->and($result->messageId)->toBe('ses-123');

    $request = $httpClient->lastRequest;

    expect($request->getUri()->__toString())->toBe('https://email.us-east-1.amazonaws.com/v2/email/outbound-emails')
        ->and($request->getHeaderLine('Authorization'))->toContain('AWS4-HMAC-SHA256 Credential=AKIA_TEST/')
        ->and($request->getHeaderLine('X-Amz-Date'))->not->toBeEmpty();

    $body = json_decode((string) $request->getBody(), true);

    expect($body['FromEmailAddress'])->toBe('Sender <sender@example.com>')
        ->and($body['Destination']['ToAddresses'])->toBe(['Recipient <recipient@example.com>'])
        ->and($body['Content']['Simple']['Subject']['Data'])->toBe('Hello')
        ->and($body['Content']['Simple']['Body']['Html']['Data'])->toBe('<p>Hi</p>')
        ->and($body['Content']['Simple']['Body']['Text']['Data'])->toBe('Hi');
});

it('sends raw mime content when attachments are present', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(200, ['Content-Type' => 'application/json'], json_encode(['MessageId' => 'ses-123'])));

    $driver = new SesDriver('AKIA_TEST', 'secret', 'us-east-1', $httpClient, $factory, $factory);

    $tmpFile = tempnam(sys_get_temp_dir(), 'nettmail');
    file_put_contents($tmpFile, 'attachment contents');

    $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
        text: 'Hi',
        attachments: [['path' => $tmpFile, 'name' => 'file.txt']],
    ));

    $body = json_decode((string) $httpClient->lastRequest->getBody(), true);

    expect($body['Content'])->toHaveKey('Raw')
        ->and(base64_decode($body['Content']['Raw']['Data']))->toContain('file.txt');

    unlink($tmpFile);
});

it('returns a failure result on a ses api error', function () {
    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(400, ['Content-Type' => 'application/json'], json_encode(['message' => 'Invalid recipient'])));

    $driver = new SesDriver('AKIA_TEST', 'secret', 'us-east-1', $httpClient, $factory, $factory);

    $result = $driver->send(new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
        text: 'Hi',
    ));

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('Invalid recipient');
});
