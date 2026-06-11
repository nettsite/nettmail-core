<?php

use Nettsite\NettMail\Core\Domain\Bounces\BounceClassifier;
use Nettsite\NettMail\Core\Domain\Bounces\BouncePoller;
use Nettsite\NettMail\Core\Domain\Bounces\DsnParser;
use Nettsite\NettMail\Core\Domain\Bounces\MailboxMessage;
use Nettsite\NettMail\Core\Domain\Contacts\BounceType;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Tests\Fakes\FakeMailbox;
use Nettsite\NettMail\Core\Tests\Fakes\InMemoryStorageAdapter;

function bouncePollerFixture(string $name): string
{
    return file_get_contents(__DIR__.'/../../Fixtures/Bounces/'.$name);
}

it('classifies and suppresses a contact for a hard bounce, then moves it to processed', function () {
    $storage = new InMemoryStorageAdapter();
    $storage->saveContact(new Contact(id: null, email: 'nobody@invalid-domain.test'));

    $mailbox = new FakeMailbox([
        new MailboxMessage(id: '1', rawContent: bouncePollerFixture('hard-bounce.eml')),
    ]);

    $poller = new BouncePoller($mailbox, new DsnParser(), new BounceClassifier(), $storage);
    $result = $poller->poll();

    $contact = $storage->findContactByEmail('nobody@invalid-domain.test');

    expect($result->processed)->toBe(1)
        ->and($result->unrecognised)->toBe(0)
        ->and($contact->bounceType)->toBe(BounceType::Hard)
        ->and($contact->isSuppressed())->toBeTrue()
        ->and($mailbox->movedTo['1'])->toBe('Processed');
});

it('moves unparseable messages to the unrecognised folder', function () {
    $storage = new InMemoryStorageAdapter();

    $mailbox = new FakeMailbox([
        new MailboxMessage(id: '1', rawContent: "Subject: hello\r\n\r\nJust a normal email."),
    ]);

    $poller = new BouncePoller($mailbox, new DsnParser(), new BounceClassifier(), $storage);
    $result = $poller->poll();

    expect($result->processed)->toBe(0)
        ->and($result->unrecognised)->toBe(1)
        ->and($mailbox->movedTo['1'])->toBe('Unrecognised');
});

it('moves a parsed bounce to processed even when no contact matches', function () {
    $storage = new InMemoryStorageAdapter();

    $mailbox = new FakeMailbox([
        new MailboxMessage(id: '1', rawContent: bouncePollerFixture('hard-bounce.eml')),
    ]);

    $poller = new BouncePoller($mailbox, new DsnParser(), new BounceClassifier(), $storage);
    $result = $poller->poll();

    expect($result->processed)->toBe(1)
        ->and($mailbox->movedTo['1'])->toBe('Processed');
});

it('uses configured folder names', function () {
    $storage = new InMemoryStorageAdapter();

    $mailbox = new FakeMailbox([
        new MailboxMessage(id: '1', rawContent: "Subject: hello\r\n\r\nJust a normal email."),
    ]);

    $poller = new BouncePoller($mailbox, new DsnParser(), new BounceClassifier(), $storage, processedFolder: 'Done', unrecognisedFolder: 'Review');
    $poller->poll();

    expect($mailbox->movedTo['1'])->toBe('Review');
});
