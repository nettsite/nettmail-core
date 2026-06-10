<?php

use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Drivers\Webhooks\SesWebhookHandler;
use Nettsite\NettMail\Core\Tests\Fakes\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

function sesPayload(string $eventType, array $extra = []): array
{
    $message = array_merge([
        'eventType' => $eventType,
        'mail' => ['messageId' => 'ses-msg-123', 'timestamp' => '2024-01-01T00:00:00.000Z'],
    ], $extra);

    return [
        'Type' => 'Notification',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789012:nettmail',
        'Message' => json_encode($message),
        'Timestamp' => '2024-01-01T00:00:00.000Z',
        'SignatureVersion' => '1',
        'Signature' => 'fake-signature',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
        'MessageId' => 'sns-msg-123',
    ];
}

it('passes verification without an http client when the topic arn matches', function () {
    $payload = sesPayload('Delivery');

    $handler = new SesWebhookHandler();

    expect($handler->verify(json_encode($payload), [], 'arn:aws:sns:us-east-1:123456789012:nettmail'))->toBeTrue();
});

it('rejects when the topic arn does not match', function () {
    $payload = sesPayload('Delivery');

    $handler = new SesWebhookHandler();

    expect($handler->verify(json_encode($payload), [], 'arn:aws:sns:us-east-1:123456789012:other'))->toBeFalse();
});

it('skips topic arn check when no secret is configured', function () {
    $payload = sesPayload('Delivery');

    $handler = new SesWebhookHandler();

    expect($handler->verify(json_encode($payload), [], ''))->toBeTrue();
});

it('verifies the sns signature when an http client is provided', function () {
    $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

    $payload = sesPayload('Delivery');
    unset($payload['Signature']);

    $signableString = '';
    foreach (['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type'] as $key) {
        if (! isset($payload[$key])) {
            continue;
        }

        $signableString .= "{$key}\n{$payload[$key]}\n";
    }

    openssl_sign($signableString, $signature, $keyPair, OPENSSL_ALGO_SHA1);

    // Export a self-signed certificate for the key pair so openssl_verify can use it.
    $csr = openssl_csr_new(['commonName' => 'nettmail-test'], $keyPair);
    $x509 = openssl_csr_sign($csr, null, $keyPair, 1);
    openssl_x509_export($x509, $certPem);

    $payload['Signature'] = base64_encode($signature);

    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(200, [], $certPem));

    $handler = new SesWebhookHandler($httpClient, $factory);

    expect($handler->verify(json_encode($payload), [], ''))->toBeTrue();
});

it('rejects an invalid sns signature', function () {
    $payload = sesPayload('Delivery');
    $payload['Signature'] = base64_encode('not-a-real-signature');

    $factory = new Psr17Factory();
    $httpClient = new FakeHttpClient(new Response(200, [], 'invalid-cert'));

    $handler = new SesWebhookHandler($httpClient, $factory);

    expect($handler->verify(json_encode($payload), [], ''))->toBeFalse();
});

it('maps a delivery event', function () {
    $events = (new SesWebhookHandler())->parse(sesPayload('Delivery'));

    expect($events)->toHaveCount(1)
        ->and($events[0]->type)->toBe(EventType::Delivered)
        ->and($events[0]->providerMessageId)->toBe('ses-msg-123');
});

it('maps a permanent bounce to a hard bounce', function () {
    $events = (new SesWebhookHandler())->parse(sesPayload('Bounce', ['bounce' => ['bounceType' => 'Permanent']]));

    expect($events[0]->type)->toBe(EventType::HardBounced);
});

it('maps a transient bounce to a soft bounce', function () {
    $events = (new SesWebhookHandler())->parse(sesPayload('Bounce', ['bounce' => ['bounceType' => 'Transient']]));

    expect($events[0]->type)->toBe(EventType::SoftBounced);
});

it('returns no events for unrecognised types', function () {
    expect((new SesWebhookHandler())->parse(sesPayload('SomethingElse')))->toBe([]);
});
