<?php

use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;

it('normalises email addresses to lowercase and trimmed', function () {
    $contact = new Contact(id: null, email: '  Jane@Example.COM  ');

    expect($contact->email)->toBe('jane@example.com');
});

it('is not suppressed by default', function () {
    $contact = new Contact(id: null, email: 'jane@example.com');

    expect($contact->isSuppressed())->toBeFalse();
});

it('is suppressed when globally unsubscribed', function () {
    $contact = new Contact(id: null, email: 'jane@example.com', globalUnsubscribedAt: new DateTimeImmutable());

    expect($contact->isSuppressed())->toBeTrue()
        ->and($contact->isSuppressed(isOperationalTransactional: true))->toBeFalse();
});

it('is suppressed when hard-bounced', function () {
    $contact = new Contact(id: null, email: 'jane@example.com', bounceType: BounceType::Hard);

    expect($contact->isSuppressed())->toBeTrue()
        ->and($contact->isSuppressed(isOperationalTransactional: true))->toBeFalse();
});

it('is suppressed when marked as a complaint', function () {
    $contact = new Contact(id: null, email: 'jane@example.com', bounceType: BounceType::Complaint);

    expect($contact->isSuppressed())->toBeTrue();
});

it('is not suppressed for a soft bounce', function () {
    $contact = new Contact(id: null, email: 'jane@example.com', bounceType: BounceType::Soft);

    expect($contact->isSuppressed())->toBeFalse();
});
