<?php

namespace Nettsite\NettMail\Core\Domain\Contacts;

use DateTimeImmutable;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;

/**
 * Parses CSV contact data and upserts contacts + list memberships. Upload
 * handling, column-mapping UI, and queued execution stay in the adapters.
 */
final class CsvContactImporter
{
    private const CONTACT_FIELDS = ['first_name', 'last_name', 'phone'];

    public function __construct(
        private readonly StorageAdapterContract $storage,
    ) {
    }

    /**
     * @param resource|string $csv          CSV content as a string, or an open stream resource
     * @param array<string, string> $columnMap CSV header => `email`, `first_name`, `last_name`,
     *                                          `phone`, or `metadata.<key>`
     * @param array<int, string> $tags
     */
    public function import(
        mixed $csv,
        array $columnMap,
        string $listId,
        array $tags = [],
        MembershipStatus $initialStatus = MembershipStatus::Subscribed,
    ): ImportResult {
        $stream = is_string($csv) ? $this->stringToStream($csv) : $csv;

        $header = fgetcsv($stream, escape: '');

        if ($header === false) {
            return new ImportResult();
        }

        $created = 0;
        $updated = 0;
        $invalid = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rowNumber++;

            $fields = $this->mapRow($header, $row, $columnMap);

            if ($fields['email'] === null || ! filter_var(EmailNormalizer::normalize($fields['email']), FILTER_VALIDATE_EMAIL)) {
                $invalid++;
                $errors[] = "Row {$rowNumber}: invalid email";

                continue;
            }

            $existing = $this->storage->findContactByEmail($fields['email']);
            $contact = $this->upsertContact($existing, $fields);
            $existing === null ? $created++ : $updated++;

            $this->upsertMembership($contact, $listId, $tags, $initialStatus);
        }

        if (is_string($csv)) {
            fclose($stream);
        }

        return new ImportResult($created, $updated, $invalid, $errors);
    }

    /**
     * @param array<int, string> $header
     * @param array<int, string> $row
     * @param array<string, string> $columnMap
     * @return array{email: ?string, first_name: ?string, last_name: ?string, phone: ?string, metadata: array<string, string>}
     */
    private function mapRow(array $header, array $row, array $columnMap): array
    {
        $data = array_combine($header, $row);

        $fields = [
            'email' => null,
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'metadata' => [],
        ];

        foreach ($columnMap as $csvColumn => $target) {
            $value = $data[$csvColumn] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if ($target === 'email' || in_array($target, self::CONTACT_FIELDS, true)) {
                $fields[$target] = $value;

                continue;
            }

            if (str_starts_with($target, 'metadata.')) {
                $fields['metadata'][substr($target, strlen('metadata.'))] = $value;
            }
        }

        return $fields;
    }

    /**
     * @param array{email: ?string, first_name: ?string, last_name: ?string, phone: ?string, metadata: array<string, string>} $fields
     */
    private function upsertContact(?Contact $existing, array $fields): Contact
    {
        $contact = $existing ?? new Contact(id: null, email: $fields['email']);

        if ($fields['first_name'] !== null) {
            $contact->firstName = $fields['first_name'];
        }

        if ($fields['last_name'] !== null) {
            $contact->lastName = $fields['last_name'];
        }

        if ($fields['phone'] !== null) {
            $contact->phone = $fields['phone'];
        }

        $contact->metadata = array_merge($contact->metadata, $fields['metadata']);

        return $this->storage->saveContact($contact);
    }

    /**
     * @param array<int, string> $tags
     */
    private function upsertMembership(Contact $contact, string $listId, array $tags, MembershipStatus $initialStatus): void
    {
        $membership = $this->storage->findMembership($contact->id, $listId);

        if ($membership === null) {
            $membership = new ListMembership(
                contactId: $contact->id,
                listId: $listId,
                status: $initialStatus,
                tags: $tags,
                subscribedAt: $initialStatus === MembershipStatus::Subscribed ? new DateTimeImmutable() : null,
            );
        } else {
            $membership->tags = array_values(array_unique([...$membership->tags, ...$tags]));
        }

        $this->storage->saveMembership($membership);
    }

    /**
     * @return resource
     */
    private function stringToStream(string $csv): mixed
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv);
        rewind($stream);

        return $stream;
    }
}
