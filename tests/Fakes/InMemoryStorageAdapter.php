<?php

namespace Nettsite\NettMail\Core\Tests\Fakes;

use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Domain\Contacts\EmailNormalizer;
use Nettsite\NettMail\Core\Domain\Contacts\ListMembership;
use Nettsite\NettMail\Core\Domain\Contacts\MailingList;

final class InMemoryStorageAdapter implements StorageAdapterContract
{
    /** @var array<string, Contact> */
    private array $contacts = [];

    /** @var array<string, MailingList> */
    private array $lists = [];

    /** @var array<string, ListMembership> */
    private array $memberships = [];

    private int $nextContactId = 1;

    private int $nextListId = 1;

    public function findContactByEmail(string $email): ?Contact
    {
        $normalized = EmailNormalizer::normalize($email);

        foreach ($this->contacts as $contact) {
            if ($contact->email === $normalized) {
                return $contact;
            }
        }

        return null;
    }

    public function findContactById(string $id): ?Contact
    {
        return $this->contacts[$id] ?? null;
    }

    public function saveContact(Contact $contact): Contact
    {
        $existing = $contact->id !== null
            ? $this->findContactById($contact->id)
            : $this->findContactByEmail($contact->email);

        if ($existing !== null) {
            $contact->id = $existing->id;
        } else {
            $contact->id = (string) $this->nextContactId++;
        }

        $this->contacts[$contact->id] = $contact;

        return $contact;
    }

    public function findListById(string $id): ?MailingList
    {
        return $this->lists[$id] ?? null;
    }

    public function findListBySlug(string $slug): ?MailingList
    {
        foreach ($this->lists as $list) {
            if ($list->slug === $slug) {
                return $list;
            }
        }

        return null;
    }

    public function saveList(MailingList $list): MailingList
    {
        $list->id ??= (string) $this->nextListId++;

        $this->lists[$list->id] = $list;

        return $list;
    }

    public function findMembership(string $contactId, string $listId): ?ListMembership
    {
        return $this->memberships[$contactId.':'.$listId] ?? null;
    }

    public function saveMembership(ListMembership $membership): ListMembership
    {
        $this->memberships[$membership->contactId.':'.$membership->listId] = $membership;

        return $membership;
    }
}
