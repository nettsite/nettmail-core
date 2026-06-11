<?php

use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\ContactSynchronizer;
use Nettsite\NettMail\Core\Tests\Fakes\FakeContactSource;
use Nettsite\NettMail\Core\Tests\Fakes\InMemoryStorageAdapter;

it('creates new contacts and updates existing ones, deduping by normalised email', function () {
    $storage = new InMemoryStorageAdapter();
    $source = new FakeContactSource([
        ['email' => 'Jane@Example.com', 'first_name' => 'Jane', 'source_id' => 1],
        ['email' => 'jane@example.com', 'last_name' => 'Doe', 'source_id' => 1],
        ['email' => 'john@example.com', 'first_name' => 'John', 'source_id' => 2],
    ]);

    $result = (new ContactSynchronizer($source, $storage))->syncAll();

    expect($result->created)->toBe(2)
        ->and($result->updated)->toBe(1)
        ->and($result->skippedInvalid)->toBe(0);

    $jane = $storage->findContactByEmail('jane@example.com');

    expect($jane->firstName)->toBe('Jane')
        ->and($jane->lastName)->toBe('Doe')
        ->and($jane->sourceType)->toBe('fake');
});

it('counts invalid emails as skipped without creating a contact', function () {
    $storage = new InMemoryStorageAdapter();
    $source = new FakeContactSource([
        ['email' => 'not-an-email', 'first_name' => 'Bad'],
        ['email' => 'good@example.com', 'first_name' => 'Good'],
    ]);

    $result = (new ContactSynchronizer($source, $storage))->syncAll();

    expect($result->created)->toBe(1)
        ->and($result->skippedInvalid)->toBe(1)
        ->and($storage->findContactByEmail('not-an-email'))->toBeNull();
});

it('merges metadata keys instead of replacing wholesale', function () {
    $storage = new InMemoryStorageAdapter();
    $source = new FakeContactSource([
        ['email' => 'jane@example.com', 'metadata' => ['plan' => 'free'], 'source_id' => 1],
        ['email' => 'jane@example.com', 'metadata' => ['region' => 'za'], 'source_id' => 1],
    ]);

    (new ContactSynchronizer($source, $storage))->syncAll();

    $jane = $storage->findContactByEmail('jane@example.com');

    expect($jane->metadata)->toBe(['plan' => 'free', 'region' => 'za']);
});

it('preserves suppression state across re-sync', function () {
    $storage = new InMemoryStorageAdapter();
    $source = new FakeContactSource([
        ['email' => 'jane@example.com', 'first_name' => 'Jane', 'source_id' => 1],
    ]);

    $synchronizer = new ContactSynchronizer($source, $storage);
    $contact = $synchronizer->syncOne(1);
    $contact->bounceType = BounceType::Hard;
    $storage->saveContact($contact);

    $synchronizer->syncOne(1);

    $jane = $storage->findContactByEmail('jane@example.com');

    expect($jane->bounceType)->toBe(BounceType::Hard)
        ->and($jane->isSuppressed())->toBeTrue();
});

it('finds a single contact by source id via syncOne', function () {
    $storage = new InMemoryStorageAdapter();
    $source = new FakeContactSource([
        ['email' => 'jane@example.com', 'first_name' => 'Jane', 'source_id' => 42],
    ]);

    $contact = (new ContactSynchronizer($source, $storage))->syncOne(42);

    expect($contact)->not->toBeNull()
        ->and($contact->email)->toBe('jane@example.com')
        ->and($contact->sourceId)->toBe('42');
});

it('returns null from syncOne when the source has no matching contact', function () {
    $storage = new InMemoryStorageAdapter();
    $source = new FakeContactSource([]);

    expect((new ContactSynchronizer($source, $storage))->syncOne(99))->toBeNull();
});
