<?php

namespace Nettsite\NettMail\Core\Tests\Fakes;

use Nettsite\NettMail\Core\Contracts\ContactSourceContract;

final class FakeContactSource implements ContactSourceContract
{
    /**
     * @param array<int, array{email: string, first_name?: string, last_name?: string, phone?: string, metadata?: array<string, mixed>, source_id?: string|int}> $records
     */
    public function __construct(
        private readonly array $records = [],
    ) {
    }

    public function label(): string
    {
        return 'Fake Source';
    }

    public function key(): string
    {
        return 'fake';
    }

    public function contacts(): iterable
    {
        yield from $this->records;
    }

    public function findContact(string|int $sourceId): ?array
    {
        foreach ($this->records as $record) {
            if (($record['source_id'] ?? null) === $sourceId) {
                return $record;
            }
        }

        return null;
    }
}
