<?php

use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Domain\Contacts\ListMembership;
use Nettsite\NettMail\Core\Domain\Contacts\MailingList;
use Nettsite\NettMail\Core\Domain\Contacts\MembershipStatus;
use Nettsite\NettMail\Core\Tests\Fakes\InMemoryStorageAdapter;

it('dedupes contacts by normalised email on save', function () {
    $adapter = new InMemoryStorageAdapter();

    $first = $adapter->saveContact(new Contact(id: null, email: 'Jane@Example.com', firstName: 'Jane'));
    $second = $adapter->saveContact(new Contact(id: null, email: 'jane@example.com ', firstName: 'Janet'));

    expect($second->id)->toBe($first->id)
        ->and($adapter->findContactByEmail('JANE@EXAMPLE.COM')?->firstName)->toBe('Janet');
});

it('finds contacts by id', function () {
    $adapter = new InMemoryStorageAdapter();

    $saved = $adapter->saveContact(new Contact(id: null, email: 'jane@example.com'));

    expect($adapter->findContactById($saved->id))->toBe($saved)
        ->and($adapter->findContactById('missing'))->toBeNull();
});

it('saves and finds lists by id and slug', function () {
    $adapter = new InMemoryStorageAdapter();

    $list = $adapter->saveList(new MailingList(id: null, name: 'Newsletter', slug: 'newsletter'));

    expect($adapter->findListById($list->id))->toBe($list)
        ->and($adapter->findListBySlug('newsletter'))->toBe($list)
        ->and($adapter->findListBySlug('missing'))->toBeNull();
});

it('saves and finds list memberships', function () {
    $adapter = new InMemoryStorageAdapter();

    $contact = $adapter->saveContact(new Contact(id: null, email: 'jane@example.com'));
    $list = $adapter->saveList(new MailingList(id: null, name: 'Newsletter', slug: 'newsletter'));

    $membership = $adapter->saveMembership(new ListMembership(
        contactId: $contact->id,
        listId: $list->id,
        status: MembershipStatus::Pending,
    ));

    $found = $adapter->findMembership($contact->id, $list->id);

    expect($found)->toBe($membership)
        ->and($found->status)->toBe(MembershipStatus::Pending)
        ->and($adapter->findMembership($contact->id, 'missing'))->toBeNull();
});

it('finds only suppressed contacts', function () {
    $adapter = new InMemoryStorageAdapter();

    $active = $adapter->saveContact(new Contact(id: null, email: 'active@example.com'));
    $bounced = $adapter->saveContact(new Contact(id: null, email: 'bounced@example.com', bounceType: BounceType::Hard));

    expect($adapter->findSuppressedContacts())->toBe([$bounced])
        ->and($active)->not->toBeNull();
});
