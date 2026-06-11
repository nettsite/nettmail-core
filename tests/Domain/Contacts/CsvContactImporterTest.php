<?php

use Nettsite\NettMail\Core\Domain\Contacts\CsvContactImporter;
use Nettsite\NettMail\Core\Domain\Contacts\MailingList;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use Nettsite\NettMail\Core\Tests\Fakes\InMemoryStorageAdapter;

const COLUMN_MAP = [
    'Email' => 'email',
    'First Name' => 'first_name',
    'Last Name' => 'last_name',
    'Plan' => 'metadata.plan',
];

it('imports a clean csv, creating contacts and memberships', function () {
    $storage = new InMemoryStorageAdapter();
    $list = $storage->saveList(new MailingList(id: null, name: 'Newsletter', slug: 'newsletter'));

    $csv = "Email,First Name,Last Name,Plan\n".
        "jane@example.com,Jane,Doe,free\n".
        "john@example.com,John,Smith,pro\n";

    $result = (new CsvContactImporter($storage))->import($csv, COLUMN_MAP, $list->id, ['imported']);

    expect($result->created)->toBe(2)
        ->and($result->updated)->toBe(0)
        ->and($result->invalid)->toBe(0)
        ->and($result->errors)->toBe([]);

    $jane = $storage->findContactByEmail('jane@example.com');

    expect($jane->firstName)->toBe('Jane')
        ->and($jane->lastName)->toBe('Doe')
        ->and($jane->metadata)->toBe(['plan' => 'free']);

    $membership = $storage->findMembership($jane->id, $list->id);

    expect($membership->status)->toBe(MembershipStatus::Subscribed)
        ->and($membership->tags)->toBe(['imported'])
        ->and($membership->subscribedAt)->not->toBeNull();
});

it('updates existing contacts on duplicate emails and merges metadata', function () {
    $storage = new InMemoryStorageAdapter();
    $list = $storage->saveList(new MailingList(id: null, name: 'Newsletter', slug: 'newsletter'));

    $csv = "Email,First Name,Last Name,Plan\n".
        "jane@example.com,Jane,Doe,free\n".
        "jane@example.com,Jane,Doe,pro\n";

    $result = (new CsvContactImporter($storage))->import($csv, COLUMN_MAP, $list->id);

    expect($result->created)->toBe(1)
        ->and($result->updated)->toBe(1);

    $jane = $storage->findContactByEmail('jane@example.com');

    expect($jane->metadata)->toBe(['plan' => 'pro']);
});

it('counts invalid email rows without creating contacts', function () {
    $storage = new InMemoryStorageAdapter();
    $list = $storage->saveList(new MailingList(id: null, name: 'Newsletter', slug: 'newsletter'));

    $csv = "Email,First Name,Last Name,Plan\n".
        "not-an-email,Jane,Doe,free\n".
        "john@example.com,John,Smith,pro\n";

    $result = (new CsvContactImporter($storage))->import($csv, COLUMN_MAP, $list->id);

    expect($result->created)->toBe(1)
        ->and($result->invalid)->toBe(1)
        ->and($result->errors)->toBe(['Row 2: invalid email'])
        ->and($storage->findContactByEmail('not-an-email'))->toBeNull();
});

it('sets pending status for double opt-in lists', function () {
    $storage = new InMemoryStorageAdapter();
    $list = $storage->saveList(new MailingList(id: null, name: 'Newsletter', slug: 'newsletter', doubleOptin: true));

    $csv = "Email,First Name,Last Name,Plan\njane@example.com,Jane,Doe,free\n";

    (new CsvContactImporter($storage))->import($csv, COLUMN_MAP, $list->id, [], MembershipStatus::Pending);

    $jane = $storage->findContactByEmail('jane@example.com');
    $membership = $storage->findMembership($jane->id, $list->id);

    expect($membership->status)->toBe(MembershipStatus::Pending)
        ->and($membership->subscribedAt)->toBeNull();
});

it('applies tags and unions tags on re-import without duplicating them', function () {
    $storage = new InMemoryStorageAdapter();
    $list = $storage->saveList(new MailingList(id: null, name: 'Newsletter', slug: 'newsletter'));

    $csv = "Email,First Name,Last Name,Plan\njane@example.com,Jane,Doe,free\n";

    (new CsvContactImporter($storage))->import($csv, COLUMN_MAP, $list->id, ['vip']);
    (new CsvContactImporter($storage))->import($csv, COLUMN_MAP, $list->id, ['vip', 'newsletter']);

    $jane = $storage->findContactByEmail('jane@example.com');
    $membership = $storage->findMembership($jane->id, $list->id);

    expect($membership->tags)->toBe(['vip', 'newsletter']);
});
