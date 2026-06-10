<?php

use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Drivers\Webhooks\MailersendWebhookHandler;

it('verifies a valid hmac signature', function () {
    $secret = 'test-secret';
    $body = '{"type":"activity.sent"}';

    $headers = ['signature' => hash_hmac('sha256', $body, $secret)];

    expect((new MailersendWebhookHandler())->verify($body, $headers, $secret))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $headers = ['signature' => 'not-the-right-signature'];

    expect((new MailersendWebhookHandler())->verify('{}', $headers, 'test-secret'))->toBeFalse();
});

it('rejects when the signature header is missing', function () {
    expect((new MailersendWebhookHandler())->verify('{}', [], 'test-secret'))->toBeFalse();
});

it('maps a hard bounce event', function () {
    $events = (new MailersendWebhookHandler())->parse([
        'type' => 'activity.hard_bounced',
        'created_at' => '2024-01-01T00:00:00Z',
        'data' => ['email' => ['message' => ['id' => 'msg-123']]],
    ]);

    expect($events)->toHaveCount(1)
        ->and($events[0]->type)->toBe(EventType::HardBounced)
        ->and($events[0]->providerMessageId)->toBe('msg-123');
});

it('returns no events for unrecognised types', function () {
    expect((new MailersendWebhookHandler())->parse(['type' => 'activity.unknown']))->toBe([]);
});
