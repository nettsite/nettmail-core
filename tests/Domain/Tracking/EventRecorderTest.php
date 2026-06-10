<?php

use Nettsite\NettMail\Core\Domain\Tracking\EventRecorder;
use Nettsite\NettMail\Core\Domain\Webhooks\EventType;

it('records an open event', function () {
    $event = (new EventRecorder())->recordOpen('send-token');

    expect($event->sendToken)->toBe('send-token')
        ->and($event->type)->toBe(EventType::Opened);
});

it('records a click event with the link hash and url', function () {
    $event = (new EventRecorder())->recordClick('send-token', 'hash123', 'https://example.com');

    expect($event->type)->toBe(EventType::Clicked)
        ->and($event->linkHash)->toBe('hash123')
        ->and($event->url)->toBe('https://example.com');
});

it('treats a null opened_at as a first open', function () {
    $recorder = new EventRecorder();

    expect($recorder->isFirstOpen(null))->toBeTrue()
        ->and($recorder->isFirstOpen(new DateTimeImmutable()))->toBeFalse();
});
