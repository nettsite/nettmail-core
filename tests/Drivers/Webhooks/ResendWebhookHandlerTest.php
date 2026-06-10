<?php

use Nettsite\NettMail\Core\Domain\Webhooks\EventType;
use Nettsite\NettMail\Core\Drivers\Webhooks\ResendWebhookHandler;

function resendSignature(string $secret, string $id, string $timestamp, string $body): string
{
    $secretBytes = base64_decode(preg_replace('/^whsec_/', '', $secret));

    return 'v1,'.base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", $secretBytes, true));
}

it('verifies a valid svix signature', function () {
    $secret = 'whsec_'.base64_encode('test-secret');
    $body = '{"type":"email.sent"}';
    $headers = [
        'svix-id' => 'msg_123',
        'svix-timestamp' => '1700000000',
        'svix-signature' => resendSignature($secret, 'msg_123', '1700000000', $body),
    ];

    expect((new ResendWebhookHandler())->verify($body, $headers, $secret))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $secret = 'whsec_'.base64_encode('test-secret');
    $body = '{"type":"email.sent"}';
    $headers = [
        'svix-id' => 'msg_123',
        'svix-timestamp' => '1700000000',
        'svix-signature' => 'v1,invalidsignature==',
    ];

    expect((new ResendWebhookHandler())->verify($body, $headers, $secret))->toBeFalse();
});

it('rejects when signature headers are missing', function () {
    expect((new ResendWebhookHandler())->verify('{}', [], 'whsec_secret'))->toBeFalse();
});

it('maps a delivered event', function () {
    $events = (new ResendWebhookHandler())->parse([
        'type' => 'email.delivered',
        'created_at' => '2024-01-01T00:00:00Z',
        'data' => ['email_id' => 'abc-123'],
    ]);

    expect($events)->toHaveCount(1)
        ->and($events[0]->type)->toBe(EventType::Delivered)
        ->and($events[0]->providerMessageId)->toBe('abc-123');
});

it('returns no events for unrecognised types', function () {
    expect((new ResendWebhookHandler())->parse(['type' => 'email.unknown']))->toBe([]);
});
