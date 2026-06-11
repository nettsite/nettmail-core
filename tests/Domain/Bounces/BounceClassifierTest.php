<?php

use Nettsite\NettMail\Core\Domain\Bounces\BounceClassifier;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;

it('classifies status codes by leading digit', function () {
    $classifier = new BounceClassifier();

    expect($classifier->classifyStatusCode('5.1.1'))->toBe(BounceType::Hard)
        ->and($classifier->classifyStatusCode('4.2.2'))->toBe(BounceType::Soft);
});

it('marks a contact as hard-bounced', function () {
    $classifier = new BounceClassifier();
    $contact = new Contact(id: null, email: 'jane@example.com');
    $now = new DateTimeImmutable();

    $classifier->recordHardBounce($contact, $now);

    expect($contact->bounceType)->toBe(BounceType::Hard)
        ->and($contact->bouncedAt)->toBe($now)
        ->and($contact->isSuppressed())->toBeTrue();
});

it('marks a contact as a complaint', function () {
    $classifier = new BounceClassifier();
    $contact = new Contact(id: null, email: 'jane@example.com');
    $now = new DateTimeImmutable();

    $classifier->recordComplaint($contact, $now);

    expect($contact->bounceType)->toBe(BounceType::Complaint)
        ->and($contact->isSuppressed())->toBeTrue();
});

it('does not suppress a contact after a single soft bounce', function () {
    $classifier = new BounceClassifier(softBounceThreshold: 3);
    $contact = new Contact(id: null, email: 'jane@example.com');
    $now = new DateTimeImmutable();

    $classifier->recordSoftBounce($contact, $now);

    expect($contact->bounceType)->toBe(BounceType::Soft)
        ->and($contact->consecutiveSoftBounces)->toBe(1)
        ->and($contact->isSuppressed())->toBeFalse();
});

it('escalates to a hard bounce after the configured number of consecutive soft bounces', function () {
    $classifier = new BounceClassifier(softBounceThreshold: 3);
    $contact = new Contact(id: null, email: 'jane@example.com');
    $now = new DateTimeImmutable();

    $classifier->recordSoftBounce($contact, $now);
    $classifier->recordSoftBounce($contact, $now);
    $classifier->recordSoftBounce($contact, $now);

    expect($contact->consecutiveSoftBounces)->toBe(3)
        ->and($contact->bounceType)->toBe(BounceType::Hard)
        ->and($contact->isSuppressed())->toBeTrue();
});

it('resets the soft bounce counter and clears a soft bounce on successful delivery', function () {
    $classifier = new BounceClassifier(softBounceThreshold: 3);
    $contact = new Contact(id: null, email: 'jane@example.com');
    $now = new DateTimeImmutable();

    $classifier->recordSoftBounce($contact, $now);
    $classifier->recordSuccessfulDelivery($contact);

    expect($contact->consecutiveSoftBounces)->toBe(0)
        ->and($contact->bounceType)->toBeNull()
        ->and($contact->bouncedAt)->toBeNull();
});

it('does not clear a hard bounce on successful delivery', function () {
    $classifier = new BounceClassifier();
    $contact = new Contact(id: null, email: 'jane@example.com');
    $now = new DateTimeImmutable();

    $classifier->recordHardBounce($contact, $now);
    $classifier->recordSuccessfulDelivery($contact);

    expect($contact->bounceType)->toBe(BounceType::Hard);
});

it('resets a stale soft bounce when the send is old enough and unbounced since', function () {
    $classifier = new BounceClassifier();
    $contact = new Contact(id: null, email: 'jane@example.com');
    $bouncedAt = new DateTimeImmutable('-10 days');
    $lastSentAt = new DateTimeImmutable('-8 days');
    $now = new DateTimeImmutable();

    $classifier->recordSoftBounce($contact, $bouncedAt);

    $reset = $classifier->resetStaleSoftBounces($contact, $lastSentAt, $now);

    expect($reset)->toBeTrue()
        ->and($contact->consecutiveSoftBounces)->toBe(0)
        ->and($contact->bounceType)->toBeNull()
        ->and($contact->bouncedAt)->toBeNull();
});

it('does not reset a soft bounce when the send is too recent', function () {
    $classifier = new BounceClassifier();
    $contact = new Contact(id: null, email: 'jane@example.com');
    $bouncedAt = new DateTimeImmutable('-2 days');
    $lastSentAt = new DateTimeImmutable('-2 days');
    $now = new DateTimeImmutable();

    $classifier->recordSoftBounce($contact, $bouncedAt);

    $reset = $classifier->resetStaleSoftBounces($contact, $lastSentAt, $now, resetDays: 7);

    expect($reset)->toBeFalse()
        ->and($contact->bounceType)->toBe(BounceType::Soft);
});

it('does not reset a soft bounce when a bounce occurred after the last send', function () {
    $classifier = new BounceClassifier();
    $contact = new Contact(id: null, email: 'jane@example.com');
    $lastSentAt = new DateTimeImmutable('-10 days');
    $bouncedAt = new DateTimeImmutable('-9 days');
    $now = new DateTimeImmutable();

    $classifier->recordSoftBounce($contact, $bouncedAt);

    $reset = $classifier->resetStaleSoftBounces($contact, $lastSentAt, $now);

    expect($reset)->toBeFalse()
        ->and($contact->bounceType)->toBe(BounceType::Soft);
});

it('never resets a hard bounce or complaint', function () {
    $classifier = new BounceClassifier();
    $hardBounced = new Contact(id: null, email: 'jane@example.com');
    $complained = new Contact(id: null, email: 'john@example.com');
    $lastSentAt = new DateTimeImmutable('-30 days');
    $now = new DateTimeImmutable();

    $classifier->recordHardBounce($hardBounced, new DateTimeImmutable('-31 days'));
    $classifier->recordComplaint($complained, new DateTimeImmutable('-31 days'));

    expect($classifier->resetStaleSoftBounces($hardBounced, $lastSentAt, $now))->toBeFalse()
        ->and($classifier->resetStaleSoftBounces($complained, $lastSentAt, $now))->toBeFalse()
        ->and($hardBounced->bounceType)->toBe(BounceType::Hard)
        ->and($complained->bounceType)->toBe(BounceType::Complaint);
});

it('is a no-op for contacts without soft bounces', function () {
    $classifier = new BounceClassifier();
    $contact = new Contact(id: null, email: 'jane@example.com');
    $lastSentAt = new DateTimeImmutable('-30 days');
    $now = new DateTimeImmutable();

    expect($classifier->resetStaleSoftBounces($contact, $lastSentAt, $now))->toBeFalse();
});
