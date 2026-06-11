<?php

use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Drivers\Webhooks\MailgunWebhookHandler;

function mailgunPayload(string $secret, string $event, string $severity = '', ?string $timestamp = null): array
{
    $timestamp ??= (string) time();
    $token = 'token-123';

    $eventData = ['event' => $event, 'timestamp' => (int) $timestamp];

    if ($severity !== '') {
        $eventData['severity'] = $severity;
    }

    return [
        'signature' => [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => hash_hmac('sha256', $timestamp.$token, $secret),
        ],
        'event-data' => $eventData,
    ];
}

it('verifies a valid signature', function () {
    $secret = 'test-secret';
    $body = json_encode(mailgunPayload($secret, 'delivered'));

    expect((new MailgunWebhookHandler())->verify($body, [], $secret))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $payload = mailgunPayload('test-secret', 'delivered');
    $payload['signature']['signature'] = 'invalid';

    expect((new MailgunWebhookHandler())->verify(json_encode($payload), [], 'test-secret'))->toBeFalse();
});

it('rejects payloads without a signature object', function () {
    expect((new MailgunWebhookHandler())->verify('{}', [], 'test-secret'))->toBeFalse();
});

it('rejects a signature with a timestamp outside the tolerance window', function () {
    $secret = 'test-secret';
    $body = json_encode(mailgunPayload($secret, 'delivered', timestamp: (string) (time() - 1000)));

    expect((new MailgunWebhookHandler())->verify($body, [], $secret))->toBeFalse();
});

it('accepts a custom timestamp tolerance', function () {
    $secret = 'test-secret';
    $body = json_encode(mailgunPayload($secret, 'delivered', timestamp: (string) (time() - 1000)));

    expect((new MailgunWebhookHandler(timestampToleranceSeconds: 2000))->verify($body, [], $secret))->toBeTrue();
});

it('normalizes the message id by stripping angle brackets', function () {
    $payload = mailgunPayload('secret', 'delivered');
    $payload['event-data']['message']['headers']['message-id'] = '<abc123@domain.com>';

    $events = (new MailgunWebhookHandler())->parse($payload);

    expect($events[0]->providerMessageId)->toBe('abc123@domain.com');
});

it('maps a permanent failure to a hard bounce', function () {
    $events = (new MailgunWebhookHandler())->parse(mailgunPayload('secret', 'failed', 'permanent'));

    expect($events)->toHaveCount(1)
        ->and($events[0]->type)->toBe(EventType::HardBounced);
});

it('maps a temporary failure to a soft bounce', function () {
    $events = (new MailgunWebhookHandler())->parse(mailgunPayload('secret', 'failed', 'temporary'));

    expect($events[0]->type)->toBe(EventType::SoftBounced);
});

it('maps a delivered event', function () {
    $events = (new MailgunWebhookHandler())->parse(mailgunPayload('secret', 'delivered'));

    expect($events[0]->type)->toBe(EventType::Delivered);
});

it('returns no events for unrecognised types', function () {
    expect((new MailgunWebhookHandler())->parse(mailgunPayload('secret', 'unknown')))->toBe([]);
});
