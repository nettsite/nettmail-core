<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;

/**
 * Shared create-or-update logic for contacts arriving from an external
 * source (host application user, CSV import, etc). Used by both
 * `NettMail::upsertContactFromSource()` and {@see ContactSynchronizer}.
 */
final class ContactUpserter
{
    /**
     * @param array{email: string, first_name?: string, last_name?: string, phone?: string, metadata?: array<string, mixed>} $data
     */
    public static function upsert(
        StorageAdapterContract $storage,
        string $sourceType,
        string|int $sourceId,
        array $data,
    ): Contact {
        $contact = $storage->findContactByEmail($data['email']);

        if ($contact === null) {
            $contact = new Contact(id: null, email: $data['email']);
        }

        if (array_key_exists('first_name', $data)) {
            $contact->firstName = $data['first_name'];
        }

        if (array_key_exists('last_name', $data)) {
            $contact->lastName = $data['last_name'];
        }

        if (array_key_exists('phone', $data)) {
            $contact->phone = $data['phone'];
        }

        if (array_key_exists('metadata', $data)) {
            $contact->metadata = array_merge($contact->metadata, $data['metadata']);
        }

        $contact->sourceType = $sourceType;
        $contact->sourceId = (string) $sourceId;

        return $storage->saveContact($contact);
    }
}
