<?php

namespace Nettsite\NettMail\Core\Contracts;

use Nettsite\NettMail\Core\Domain\Contacts\Contact;
use Nettsite\NettMail\Core\Domain\Contacts\ListMembership;
use Nettsite\NettMail\Core\Domain\Contacts\MailingList;

/**
 * Grows incrementally per stage rather than being fully designed upfront,
 * so the contract reflects what the domain actually needs.
 */
interface StorageAdapterContract
{
    public function findContactByEmail(string $email): ?Contact;

    public function findContactById(string $id): ?Contact;

    /**
     * Creates the contact if it does not exist (matched by normalised
     * email), or updates the existing record. Returns the persisted
     * contact, including its assigned id.
     */
    public function saveContact(Contact $contact): Contact;

    public function findListById(string $id): ?MailingList;

    public function findListBySlug(string $slug): ?MailingList;

    public function saveList(MailingList $list): MailingList;

    public function findMembership(string $contactId, string $listId): ?ListMembership;

    public function saveMembership(ListMembership $membership): ListMembership;

    /**
     * Returns every contact suppressed via hard bounce, complaint, or
     * global unsubscribe (per Contact::isSuppressed()).
     *
     * @return array<int, Contact>
     */
    public function findSuppressedContacts(): array;
}
