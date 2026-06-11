<?php

use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Mail\EmailAddress;
use Nettsite\NettMail\Core\Mail\EmailMessage;
use Nettsite\NettMail\Core\NettMail;
use Nettsite\NettMail\Core\Tests\Fakes\FakeMailDriver;
use Nettsite\NettMail\Core\Tests\Fakes\InMemoryStorageAdapter;

it('delegates sending to the configured driver', function () {
    $driver = new FakeMailDriver();
    $nettmail = new NettMail($driver, new InMemoryStorageAdapter());

    $message = new EmailMessage(
        from: new EmailAddress('sender@example.com'),
        to: [new EmailAddress('recipient@example.com')],
        subject: 'Hello',
    );

    $result = $nettmail->send($message);

    expect($result->success)->toBeTrue()
        ->and($result->messageId)->toBe('fake-message-id')
        ->and($driver->lastMessage)->toBe($message);
});

it('anonymises a contact on erasure and retains its id', function () {
    $storage = new InMemoryStorageAdapter();
    $nettmail = new NettMail(new FakeMailDriver(), $storage);

    $contact = $storage->saveContact(new Contact(id: null, email: 'jane@example.com', firstName: 'Jane'));

    expect($nettmail->eraseContact('jane@example.com'))->toBeTrue();

    $erased = $storage->findContactById($contact->id);

    expect($erased->email)->not->toBe('jane@example.com')
        ->and($erased->email)->toEndWith('@erased.invalid')
        ->and($erased->firstName)->toBeNull();
});

it('returns false when erasing an unknown contact', function () {
    $nettmail = new NettMail(new FakeMailDriver(), new InMemoryStorageAdapter());

    expect($nettmail->eraseContact('missing@example.com'))->toBeFalse();
});

it('exports suppressed contacts as csv', function () {
    $storage = new InMemoryStorageAdapter();
    $nettmail = new NettMail(new FakeMailDriver(), $storage);

    $storage->saveContact(new Contact(
        id: null,
        email: 'bounced@example.com',
        bounceType: BounceType::Hard,
        bouncedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
    ));

    $csv = $nettmail->exportSuppressions();

    expect($csv)->toContain('email,reason,suppressed_at')
        ->and($csv)->toContain('bounced@example.com,hard_bounce,2024-01-01T00:00:00+00:00');
});
