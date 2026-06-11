<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

use Nettsite\NettMail\Core\Contracts\ContactSourceContract;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;

/**
 * Create-or-update sync from a {@see ContactSourceContract} into NettMail's
 * own contact table. Never deletes contacts (per spec §5.4).
 */
final class ContactSynchronizer
{
    public function __construct(
        private readonly ContactSourceContract $source,
        private readonly StorageAdapterContract $storage,
    ) {
    }

    public function syncAll(): SyncResult
    {
        $created = 0;
        $updated = 0;
        $skippedInvalid = 0;

        foreach ($this->source->contacts() as $data) {
            if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $skippedInvalid++;

                continue;
            }

            if ($this->storage->findContactByEmail($data['email']) !== null) {
                $updated++;
            } else {
                $created++;
            }

            ContactUpserter::upsert($this->storage, $this->source->key(), $data['source_id'] ?? $data['email'], $data);
        }

        return new SyncResult($created, $updated, $skippedInvalid);
    }

    public function syncOne(string|int $sourceId): ?Contact
    {
        $data = $this->source->findContact($sourceId);

        if ($data === null) {
            return null;
        }

        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return ContactUpserter::upsert($this->storage, $this->source->key(), $data['source_id'] ?? $sourceId, $data);
    }
}
