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
