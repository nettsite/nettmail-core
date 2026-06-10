<?php

use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Drivers\Webhooks\PostmarkWebhookHandler;

it('passes verification when no secret is configured', function () {
    expect((new PostmarkWebhookHandler())->verify('{}', [], ''))->toBeTrue();
});

it('verifies a matching shared token', function () {
    $headers = ['x-nettmail-webhook-token' => 'shared-secret'];

    expect((new PostmarkWebhookHandler())->verify('{}', $headers, 'shared-secret'))->toBeTrue();
});

it('rejects a missing or mismatched token when a secret is configured', function () {
    expect((new PostmarkWebhookHandler())->verify('{}', [], 'shared-secret'))->toBeFalse()
        ->and((new PostmarkWebhookHandler())->verify('{}', ['x-nettmail-webhook-token' => 'wrong'], 'shared-secret'))->toBeFalse();
});

it('maps a hard bounce event', function () {
    $events = (new PostmarkWebhookHandler())->parse([
        'RecordType' => 'Bounce',
        'Type' => 'HardBounce',
        'MessageID' => 'msg-123',
        'BouncedAt' => '2024-01-01T00:00:00Z',
    ]);

    expect($events)->toHaveCount(1)
        ->and($events[0]->type)->toBe(EventType::HardBounced)
        ->and($events[0]->providerMessageId)->toBe('msg-123');
});

it('maps a soft bounce event', function () {
    $events = (new PostmarkWebhookHandler())->parse([
        'RecordType' => 'Bounce',
        'Type' => 'SoftBounce',
        'BouncedAt' => '2024-01-01T00:00:00Z',
    ]);

    expect($events[0]->type)->toBe(EventType::SoftBounced);
});

it('maps a delivery event', function () {
    $events = (new PostmarkWebhookHandler())->parse([
        'RecordType' => 'Delivery',
        'MessageID' => 'msg-123',
        'DeliveredAt' => '2024-01-01T00:00:00Z',
    ]);

    expect($events[0]->type)->toBe(EventType::Delivered);
});

it('returns no events for unrecognised record types', function () {
    expect((new PostmarkWebhookHandler())->parse(['RecordType' => 'Unknown']))->toBe([]);
});
